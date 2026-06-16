<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Settings')]
class General extends Component
{
    use WithFileUploads;

    public string $appName = '';

    /** Newly uploaded logo (TemporaryUploadedFile) or null. */
    public $logo = null;

    /** Path of the currently stored logo, if any. */
    public ?string $currentLogo = null;

    public function mount(): void
    {
        $this->appName = (string) Setting::get('app_name', config('app.name'));
        $this->currentLogo = Setting::get('logo_path');
    }

    protected function rules(): array
    {
        return [
            'appName' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ];
    }

    public function save(): void
    {
        $this->authorize('settings.manage');
        $data = $this->validate();

        Setting::set('app_name', $data['appName']);

        if ($this->logo) {
            $previous = Setting::get('logo_path');
            $path = $this->logo->store('branding', 'public');
            Setting::set('logo_path', $path);

            if ($previous && $previous !== $path) {
                Storage::disk('public')->delete($previous);
            }

            $this->currentLogo = $path;
            $this->logo = null;
        }

        session()->flash('status', 'Settings updated.');
    }

    public function removeLogo(): void
    {
        $this->authorize('settings.manage');

        if ($previous = Setting::get('logo_path')) {
            Storage::disk('public')->delete($previous);
        }

        Setting::set('logo_path', null);
        $this->currentLogo = null;
        $this->logo = null;

        session()->flash('status', 'Logo removed.');
    }

    public function render()
    {
        return view('livewire.settings.general');
    }
}
