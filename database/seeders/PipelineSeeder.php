<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pipeline;

class PipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            'Lead',
            'Viewing',
            'Booking',
            'SPA Signed',
            'Loan Submitted',
            'Loan Approved',
            'Legal Processing',
            'Completed',
            'Commission Paid',
        ];

        foreach ($stages as $stage) {
            Pipeline::firstOrCreate(['name' => $stage]);
        }
    }
}
