<?php

namespace App\Livewire\Permissions;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

#[Layout('layouts.app')]
#[Title('Permissions')]
class Index extends Component
{
    public bool $showModal = false;

    public string $name = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._\-]+$/', 'unique:permissions,name'],
        ];
    }

    public function create(): void
    {
        $this->authorize('permissions.manage');
        $this->reset('name');
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('permissions.manage');
        $data = $this->validate();

        Permission::findOrCreate($data['name'], 'web');

        $this->showModal = false;
        session()->flash('status', 'Permission created.');
    }

    public function delete(int $id): void
    {
        $this->authorize('permissions.manage');
        Permission::findOrFail($id)->delete();
        session()->flash('status', 'Permission deleted.');
    }

    public function render()
    {
        return view('livewire.permissions.index', [
            'permissions' => Permission::withCount('roles')->orderBy('name')->get(),
            'canManage' => auth()->user()->can('permissions.manage'),
        ]);
    }
}
