<?php

namespace Database\Seeders;

use App\Models\Scheme;
use Illuminate\Database\Seeder;

class DemoSchemeSeeder extends Seeder
{
    /**
     * Loads a handful of realistic welfare schemes so the dashboard is
     * not empty on first boot. Guarded by SEED_DEMO_DATA in the entrypoint.
     */
    public function run(): void
    {
        $schemes = [
            ['name' => 'National Health Assurance', 'code' => 'NHA-2024', 'department' => 'Health & Family Welfare', 'category' => 'Health', 'status' => 'active', 'budget_allocated' => 1250000000, 'budget_disbursed' => 812000000, 'target_beneficiaries' => 4200000, 'enrolled_beneficiaries' => 3380000],
            ['name' => 'Girl Child Education Grant', 'code' => 'GCEG-2023', 'department' => 'Education', 'category' => 'Education', 'status' => 'active', 'budget_allocated' => 480000000, 'budget_disbursed' => 455000000, 'target_beneficiaries' => 900000, 'enrolled_beneficiaries' => 870000],
            ['name' => 'Smallholder Crop Subsidy', 'code' => 'SCS-2024', 'department' => 'Agriculture', 'category' => 'Agriculture', 'status' => 'active', 'budget_allocated' => 760000000, 'budget_disbursed' => 290000000, 'target_beneficiaries' => 1500000, 'enrolled_beneficiaries' => 640000],
            ['name' => 'Affordable Rural Housing', 'code' => 'ARH-2022', 'department' => 'Rural Development', 'category' => 'Housing', 'status' => 'suspended', 'budget_allocated' => 2100000000, 'budget_disbursed' => 1980000000, 'target_beneficiaries' => 350000, 'enrolled_beneficiaries' => 331000],
            ['name' => 'Urban Youth Employment', 'code' => 'UYE-2024', 'department' => 'Labour & Employment', 'category' => 'Employment', 'status' => 'active', 'budget_allocated' => 540000000, 'budget_disbursed' => 120000000, 'target_beneficiaries' => 600000, 'enrolled_beneficiaries' => 210000],
            ['name' => 'Senior Citizen Pension', 'code' => 'SCP-2019', 'department' => 'Social Justice', 'category' => 'Pension', 'status' => 'active', 'budget_allocated' => 1850000000, 'budget_disbursed' => 1720000000, 'target_beneficiaries' => 2800000, 'enrolled_beneficiaries' => 2720000],
            ['name' => 'Mid-day Meal Expansion', 'code' => 'MME-2023', 'department' => 'Education', 'category' => 'Nutrition', 'status' => 'active', 'budget_allocated' => 920000000, 'budget_disbursed' => 610000000, 'target_beneficiaries' => 5200000, 'enrolled_beneficiaries' => 4950000],
            ['name' => 'Microfinance Access Drive', 'code' => 'MAD-2021', 'department' => 'Finance', 'category' => 'Financial Inclusion', 'status' => 'closed', 'budget_allocated' => 300000000, 'budget_disbursed' => 300000000, 'target_beneficiaries' => 450000, 'enrolled_beneficiaries' => 448000],
        ];

        foreach ($schemes as $scheme) {
            Scheme::updateOrCreate(
                ['code' => $scheme['code']],
                $scheme + [
                    'start_date' => now()->subMonths(rand(6, 30))->startOfMonth()->toDateString(),
                    'end_date' => now()->addMonths(rand(6, 24))->endOfMonth()->toDateString(),
                    'description' => 'Demo scheme record — replace with real programme data.',
                ],
            );
        }
    }
}
