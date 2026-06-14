<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_active = true;

    /** @var array<int,string> */
    public array $roles = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'nullable', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('users.manage');
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('users.manage');
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_active = (bool) $user->is_active;
        $this->password = '';
        $this->roles = $user->getRoleNames()->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('users.manage');
        $data = $this->validate();

        $user = $this->editingId ? User::findOrFail($this->editingId) : new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_active = $this->is_active;
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        // Prevent locking yourself out of super-admin if you're the only one.
        $user->syncRoles($this->roles);

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'User saved.');
    }

    public function delete(int $id): void
    {
        $this->authorize('users.manage');

        if ($id === auth()->id()) {
            session()->flash('status', 'You cannot delete your own account.');

            return;
        }

        $user = User::findOrFail($id);

        // Do not allow removing the last super administrator.
        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            session()->flash('status', 'Cannot delete the last super administrator.');

            return;
        }

        $user->delete();
        session()->flash('status', 'User deleted.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'is_active', 'roles']);
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->with('roles')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.users.index', [
            'users' => $users,
            'allRoles' => Role::orderBy('name')->pluck('name'),
            'canManage' => auth()->user()->can('users.manage'),
        ]);
    }
}
