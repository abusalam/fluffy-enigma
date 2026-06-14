<?php

namespace App\Models;

use Database\Factories\SchemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scheme extends Model
{
    /** @use HasFactory<SchemeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'department',
        'category',
        'status',
        'start_date',
        'end_date',
        'budget_allocated',
        'budget_disbursed',
        'target_beneficiaries',
        'enrolled_beneficiaries',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget_allocated' => 'decimal:2',
            'budget_disbursed' => 'decimal:2',
            'target_beneficiaries' => 'integer',
            'enrolled_beneficiaries' => 'integer',
        ];
    }

    public const STATUSES = ['active', 'suspended', 'closed', 'draft'];

    public const CATEGORIES = [
        'Health', 'Education', 'Agriculture', 'Housing',
        'Employment', 'Pension', 'Nutrition', 'Financial Inclusion',
    ];

    /** Budget utilisation as a 0-100 percentage. */
    public function getBudgetUtilisationAttribute(): float
    {
        $allocated = (float) $this->budget_allocated;

        return $allocated > 0
            ? round(((float) $this->budget_disbursed / $allocated) * 100, 1)
            : 0.0;
    }

    /** Enrollment progress as a 0-100 percentage. */
    public function getEnrollmentProgressAttribute(): float
    {
        $target = (int) $this->target_beneficiaries;

        return $target > 0
            ? round(((int) $this->enrolled_beneficiaries / $target) * 100, 1)
            : 0.0;
    }
}
