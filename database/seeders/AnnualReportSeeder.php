<?php

namespace Database\Seeders;

use App\Models\AnnualReport;
use Illuminate\Database\Seeder;

class AnnualReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [
            [
                'year'                  => 2025,
                'substantive_status'    => 'soon',
                'substantive_reason'    => null,
                'financial_status'      => 'not_yet',
                'financial_reason'      => null,
                'is_published'          => true,
            ],
            [
                'year'                  => 2024,
                'substantive_status'    => 'published',
                'substantive_reason'    => null,
                'financial_status'      => 'published',
                'financial_reason'      => null,
                'is_published'          => true,
            ],
            [
                'year'                  => 2023,
                'substantive_status'    => 'published',
                'substantive_reason'    => null,
                'financial_status'      => 'published',
                'financial_reason'      => null,
                'is_published'          => true,
            ],
            [
                'year'                  => 2022,
                'substantive_status'    => 'published',
                'substantive_reason'    => null,
                'financial_status'      => 'published',
                'financial_reason'      => null,
                'is_published'          => true,
            ],
        ];

        foreach ($reports as $data) {
            AnnualReport::updateOrCreate(
                ['year' => $data['year']],
                $data,
            );
        }
    }
}
