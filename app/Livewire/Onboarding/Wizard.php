<?php

namespace App\Livewire\Onboarding;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.guest')]
#[Title('First-run setup')]
class Wizard extends Component
{
    use WithFileUploads;

    public int $step = 1;
    public int $totalSteps = 4;

    // Branding
    public string $appName = '';
    public $logo; // TemporaryUploadedFile|null

    // Super administrator
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /** @var array<int,array{key:string,label:string,done:bool}> */
    public array $checklist = [];

    public function mount(string $secret): void
    {
        $configured = (string) config('onboarding.secret');

        // No secret configured, wrong secret, or already onboarded -> hide.
        abort_if($configured === '' || ! hash_equals($configured, $secret), 404);

        if (Setting::isOnboarded()) {
            redirect()->route('login');

            return;
        }

        $this->appName = config('app.name', 'Scheme Monitor');
        $this->checklist = config('onboarding.default_steps');
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            2 => [
                'appName' => ['required', 'string', 'max:120'],
                'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            ],
            3 => [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            default => [],
        };
    }

    public function next(): void
    {
        $rules = $this->rulesForStep($this->step);
        if (! empty($rules)) {
            $this->validate($rules);
        }

        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function finish()
    {
        // Re-validate the data-bearing steps before committing.
        $this->validate($this->rulesForStep(2) + $this->rulesForStep(3));

        abort_if(Setting::isOnboarded(), 403);

        // Ensure the baseline roles & permissions exist.
        (new PermissionSeeder)->run();

        $logoPath = null;
        if ($this->logo) {
            $logoPath = $this->logo->store('branding', 'public');
        }

        $user = DB::transaction(function () use ($logoPath) {
            Setting::set('app_name', $this->appName);
            if ($logoPath) {
                Setting::set('logo_path', $logoPath);
            }

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'is_active' => true,
            ]);
            $user->assignRole('super-admin');

            // Persist the (now completed) onboarding checklist.
            $checklist = array_map(fn ($s) => $s + ['done' => true], $this->checklist);
            Setting::set('onboarding_steps', $checklist);
            Setting::set('onboarded_at', now()->toIso8601String());
            Setting::set('onboarding_completed', true);

            return $user;
        });

        Auth::login($user, remember: true);
        session()->regenerate();

        session()->flash('status', 'Welcome aboard! Onboarding is complete.');

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.onboarding.wizard');
    }
}
