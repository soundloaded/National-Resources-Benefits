<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FundingSource;
use Carbon\Carbon;

class FundingSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            // Business
            [
                'title' => 'Small Business Growth Grant',
                'description' => 'Funding to help small businesses expand operations, hire new employees, or purchase equipment.',
                'amount_min' => 5000,
                'amount_max' => 25000,
                'category' => 'business',
                'url' => 'https://www.sba.gov/funding-programs/grants',
                'deadline' => Carbon::now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'title' => 'Startup Innovation Application',
                'description' => 'Capital injection for tech startups and innovative new business models.',
                'amount_min' => 25000,
                'amount_max' => 100000,
                'category' => 'business',
                'url' => 'https://www.example.gov/startup-grant',
                'deadline' => Carbon::now()->addMonths(6),
                'is_active' => true,
            ],
            
            // Personal
            [
                'title' => 'Personal Hardship Assistance',
                'description' => 'Emergency financial assistance for individuals facing unexpected hardship, medical bills, or job loss.',
                'amount_min' => 1000,
                'amount_max' => 5000,
                'category' => 'personal',
                'url' => 'https://www.usa.gov/benefits',
                'deadline' => Carbon::now()->addMonths(1),
                'is_active' => true,
            ],
            [
                'title' => 'Debt Consolidation Support',
                'description' => 'Programs designed to help individuals consolidate high-interest debt.',
                'amount_min' => 5000,
                'amount_max' => 20000,
                'category' => 'personal',
                'url' => 'https://www.example.org/debt-help',
                'deadline' => null, // Ongoing
                'is_active' => true,
            ],

            // Education
            [
                'title' => 'Continuing Education Scholarship',
                'description' => 'Scholarships for adults returning to school or seeking professional certification.',
                'amount_min' => 2000,
                'amount_max' => 10000,
                'category' => 'education',
                'url' => 'https://studentaid.gov/',
                'deadline' => Carbon::now()->addMonths(4),
                'is_active' => true,
            ],

            // Real Estate / Home Buyers
            [
                'title' => 'First-Time Home Buyer Credit',
                'description' => 'Tax credits and down payment assistance for first-time home buyers.',
                'amount_min' => 5000,
                'amount_max' => 15000,
                'category' => 'home_buyers',
                'url' => 'https://www.hud.gov/buying',
                'deadline' => Carbon::now()->addYear(),
                'is_active' => true,
            ],
            [
                'title' => 'Real Estate Investment Loan',
                'description' => 'Low-interest loans for rehabilitating distressed properties.',
                'amount_min' => 50000,
                'amount_max' => 500000,
                'category' => 'real_estate',
                'url' => 'https://www.example.com/rei-loans',
                'deadline' => Carbon::now()->addMonths(2),
                'is_active' => true,
            ],

            // Community
            [
                'title' => 'Community Development Block Grant',
                'description' => 'Funds for local organizations improving neighborhood infrastructure and services.',
                'amount_min' => 10000,
                'amount_max' => 50000,
                'category' => 'community',
                'url' => 'https://www.hud.gov/program_offices/comm_planning/cdbg',
                'deadline' => Carbon::now()->addMonths(5),
                'is_active' => true,
            ],
            
             // Minorities
            [
                'title' => 'Minority Business Enterprise Fund',
                'description' => 'Dedicated funding pool for minority-owned businesses and entrepreneurs.',
                'amount_min' => 10000,
                'amount_max' => 75000,
                'category' => 'minorities',
                'url' => 'https://www.mbda.gov/',
                'deadline' => Carbon::now()->addMonths(3),
                'is_active' => true,
            ],
        ];

        foreach ($sources as $source) {
            FundingSource::create($source);
        }
    }
}
