<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@pln.co.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Admin',
            ],
            [
                'name' => 'Supervisor Kontrak',
                'email' => 'supervisor@pln.co.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Editor',
            ],
            [
                'name' => 'Staff Verifikasi',
                'email' => 'staff@pln.co.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Editor',
            ],
            [
                'name' => 'Manager Keuangan',
                'email' => 'manager@pln.co.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Viewer',
            ],
            [
                'name' => 'Auditor Internal',
                'email' => 'auditor@pln.co.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Viewer',
            ],
            [
                'name' => 'Staff Kontrak',
                'email' => 'kontrak@pln.co.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Editor',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'email_verified_at' => $userData['email_verified_at'],
            ]);

            // Attach role (legacy system - kept for backward compatibility)
            $role = \App\Models\Role::where('name', $userData['role'])->first();
            if ($role) {
                /** @phpstan-ignore-next-line */
                $user->roles()->attach($role->id);
            }
        }
    }
}
