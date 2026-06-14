<?php

namespace App\Livewire\Roles;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Roles')]
class Index extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    /** @var array<int,string> */
    public array $permissions = [];

    protected array $protectedRoles = ['super-admin'];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9\- ]+$/i', Rule::unique('roles', 'name')->ignore($this->editingId)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function create(): void
    {
        $this->authorize('roles.manage');
        $this->reset(['editingId', 'name', 'permissions']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('roles.manage');
        $role = Role::findOrFail($id);
        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('roles.manage');
        $data = $this->validate();

        $role = $this->editingId
            ? Role::findOrFail($this->editingId)
            : new Role(['guard_name' => 'web']);

        // The super-admin role name is immutable.
        if ($this->editingId && in_array($role->name, $this->protectedRoles, true)) {
            $data['name'] = $role->name;
        }

        $role->name = $data['name'];
        $role->guard_name = 'web';
        $role->save();

        // super-admin always implicitly has everything (Gate::before).
        if (! in_array($role->name, $this->protectedRoles, true)) {
            $role->syncPermissions($this->permissions);
        }

        $this->showModal = false;
        session()->flash('status', 'Role saved.');
    }

    public function delete(int $id): void
    {
        $this->authorize('roles.manage');
        $role = Role::findOrFail($id);

        if (in_array($role->name, $this->protectedRoles, true)) {
            session()->flash('status', 'This role is protected and cannot be deleted.');

            return;
        }

        if ($role->users()->exists()) {
            session()->flash('status', 'Cannot delete a role that is still assigned to users.');

            return;
        }

        $role->delete();
        session()->flash('status', 'Role deleted.');
    }

    public function render()
    {
        return view('livewire.roles.index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get(),
            'allPermissions' => Permission::orderBy('name')->pluck('name'),
            'protectedRoles' => $this->protectedRoles,
            'canManage' => auth()->user()->can('roles.manage'),
        ]);
    }
}
