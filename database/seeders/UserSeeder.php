<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Production hash from production_database.sql (phone login).
        // Also ensure local known password 1289 works via bcrypt below if hash differs.
        $exists = DB::table('users')->where('phone', '01785636359')->exists();

        if (! $exists) {
            DB::table('users')->insert([
                'name' => 'Ashique',
                'phone' => '01785636359',
                'password' => bcrypt('1289'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = User::query()->where('phone', '01785636359')->first();
        $admin = Role::query()->where('name', 'admin')->first();

        if ($user && $admin) {
            $user->roles()->syncWithoutDetaching([$admin->id]);
        }
    }
}
