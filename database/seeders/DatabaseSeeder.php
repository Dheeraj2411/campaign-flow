<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Plans first
        $this->call(PlanSeeder::class);

        // Create Admin User
        $admin = User::updateOrCreate([
            'email' => 'dheerajjha834@gmail.com',
        ], [
            'name' => 'Admin User',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        // Auto-create personal workspace if they don't have one
        if (!\App\Models\Workspace::where('owner_id', $admin->id)->exists()) {
            $workspace = \App\Models\Workspace::create([
                'owner_id' => $admin->id,
                'name'     => "Admin's Workspace",
                'slug'     => 'admin-workspace',
            ]);

            $workspace->members()->attach($admin->id, ['role' => 'owner']);
            $admin->update(['active_workspace_id' => $workspace->id]);
        }
    }
}
