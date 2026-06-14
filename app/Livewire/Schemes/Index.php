<?php

namespace App\Livewire\Schemes;

use App\Models\Scheme;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Schemes')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $code = '';
    public string $department = '';
    public string $category = '';
    public string $scheme_status = 'draft';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public $budget_allocated = 0;
    public $budget_disbursed = 0;
    public $target_beneficiaries = 0;
    public $enrolled_beneficiaries = 0;
    public string $description = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40', Rule::unique('schemes', 'code')->ignore($this->editingId)],
            'department' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::in(Scheme::CATEGORIES)],
            'scheme_status' => ['required', Rule::in(Scheme::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget_allocated' => ['numeric', 'min:0'],
            'budget_disbursed' => ['numeric', 'min:0', 'lte:budget_allocated'],
            'target_beneficiaries' => ['integer', 'min:0'],
            'enrolled_beneficiaries' => ['integer', 'min:0', 'lte:target_beneficiaries'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('schemes.manage');
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('schemes.manage');
        $s = Scheme::findOrFail($id);
        $this->editingId = $s->id;
        $this->name = $s->name;
        $this->code = $s->code;
        $this->department = (string) $s->department;
        $this->category = (string) $s->category;
        $this->scheme_status = $s->status;
        $this->start_date = optional($s->start_date)->toDateString();
        $this->end_date = optional($s->end_date)->toDateString();
        $this->budget_allocated = $s->budget_allocated;
        $this->budget_disbursed = $s->budget_disbursed;
        $this->target_beneficiaries = $s->target_beneficiaries;
        $this->enrolled_beneficiaries = $s->enrolled_beneficiaries;
        $this->description = (string) $s->description;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('schemes.manage');
        $data = $this->validate();

        Scheme::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'code' => $data['code'],
                'department' => $data['department'] ?: null,
                'category' => $data['category'] ?: null,
                'status' => $data['scheme_status'],
                'start_date' => $data['start_date'] ?: null,
                'end_date' => $data['end_date'] ?: null,
                'budget_allocated' => $data['budget_allocated'],
                'budget_disbursed' => $data['budget_disbursed'],
                'target_beneficiaries' => $data['target_beneficiaries'],
                'enrolled_beneficiaries' => $data['enrolled_beneficiaries'],
                'description' => $data['description'] ?: null,
            ],
        );

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Scheme saved.');
    }

    public function delete(int $id): void
    {
        $this->authorize('schemes.manage');
        Scheme::findOrFail($id)->delete();
        session()->flash('status', 'Scheme deleted.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'code', 'department', 'category',
            'start_date', 'end_date', 'description',
        ]);
        $this->scheme_status = 'draft';
        $this->budget_allocated = 0;
        $this->budget_disbursed = 0;
        $this->target_beneficiaries = 0;
        $this->enrolled_beneficiaries = 0;
        $this->resetErrorBag();
    }

    public function render()
    {
        $schemes = Scheme::query()
            ->when($this->search, fn ($q) => $q
                ->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('department', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.schemes.index', [
            'schemes' => $schemes,
            'statuses' => Scheme::STATUSES,
            'categories' => Scheme::CATEGORIES,
            'canManage' => auth()->user()->can('schemes.manage'),
        ]);
    }
}
