<?php

namespace App\Livewire\ShortLinks;

use App\Models\ShortLink;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Short links')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $destination_url = '';

    public string $code = '';

    public string $title = '';

    public bool $is_active = true;

    /** Reserved first path segments that must not be used as a code. */
    protected array $reserved = ['s', 'setup', 'login', 'logout', 'dashboard', 'users', 'roles', 'permissions', 'links', 'livewire', 'up', 'storage', 'build', 'vendor'];

    protected function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:2000'],
            'code' => [
                'nullable', 'string', 'alpha_dash', 'min:6', 'max:40',
                Rule::notIn($this->reserved),
                Rule::unique('short_links', 'code')->ignore($this->editingId),
            ],
            'title' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('shortlinks.manage');
        $this->resetForm();
        $this->showModal = true;
    }

    /** Owner-or-admin guard for a single link. */
    protected function authorizeLink(ShortLink $link): void
    {
        $user = auth()->user();
        abort_unless(
            $user->can('shortlinks.view_all') || $link->created_by === $user->id,
            403,
        );
    }

    public function edit(int $id): void
    {
        $this->authorize('shortlinks.manage');
        $link = ShortLink::findOrFail($id);
        $this->authorizeLink($link);
        $this->editingId = $link->id;
        $this->destination_url = $link->destination_url;
        $this->code = $link->code;
        $this->title = (string) $link->title;
        $this->is_active = (bool) $link->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('shortlinks.manage');
        $data = $this->validate();

        $code = $data['code'] ?: $this->uniqueCode();

        if ($this->editingId) {
            $link = ShortLink::findOrFail($this->editingId);
            $this->authorizeLink($link);
            $link->update([
                'destination_url' => $data['destination_url'],
                'code' => $code,
                'title' => $data['title'] ?: null,
                'is_active' => $this->is_active,
            ]);
        } else {
            ShortLink::create([
                'destination_url' => $data['destination_url'],
                'code' => $code,
                'title' => $data['title'] ?: null,
                'is_active' => $this->is_active,
                'created_by' => auth()->id(),
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Short link saved.');
    }

    public function toggle(int $id): void
    {
        $this->authorize('shortlinks.manage');
        $link = ShortLink::findOrFail($id);
        $this->authorizeLink($link);
        $link->update(['is_active' => ! $link->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('shortlinks.manage');
        $link = ShortLink::findOrFail($id);
        $this->authorizeLink($link);
        $link->delete();
        session()->flash('status', 'Short link deleted.');
    }

    protected function uniqueCode(): string
    {
        do {
            $code = strtolower(Str::random(6));
        } while (ShortLink::where('code', $code)->exists() || in_array($code, $this->reserved, true));

        return $code;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'destination_url', 'code', 'title']);
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $user = auth()->user();

        $links = ShortLink::query()
            ->visibleTo($user)
            ->when($this->search, fn ($q) => $q
                ->where(fn ($w) => $w->where('title', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('destination_url', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(10);

        // Totals reflect only what this user is allowed to see.
        $scope = ShortLink::query()->visibleTo($user);

        return view('livewire.short-links.index', [
            'links' => $links,
            'viewAll' => $user->can('shortlinks.view_all'),
            'totals' => [
                'links' => (clone $scope)->count(),
                'clicks' => (int) (clone $scope)->sum('clicks'),
                'unique' => (int) (clone $scope)->sum('unique_clicks'),
            ],
            'canManage' => $user->can('shortlinks.manage'),
        ]);
    }
}
