<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = config('plans');

        foreach ($plans as $slug => $data) {
            \App\Models\Plan::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'                   => $data['name'],
                    'price'                  => $data['price'] ?? 0,
                    'max_contacts'           => $data['max_contacts'],
                    'max_campaigns'          => $data['max_campaigns'],
                    'max_messages_per_month' => $data['max_messages_per_month'],
                    'features'               => $data['features'],
                    'is_active'              => true,
                ]
            );
        }
    }
}
