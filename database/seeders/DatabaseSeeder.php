<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Seed permissions and roles first (authentication & authorization)
        $this->command->info('📋 Seeding permissions...');
        $this->call(PermissionSeeder::class);

        $this->command->info('👥 Seeding roles...');
        $this->call(RoleSeeder::class);

        // Seed users FIRST before assigning them to role groups
        $this->command->info('👤 Seeding users...');
        $this->call(UserSeeder::class);

        // Seed new page-based role groups and assign users to them
        $this->command->info('🔐 Seeding role groups (new permission system)...');
        $this->call(RoleGroupSeeder::class);

        // Seed business data in order of dependencies
        $this->command->info('🏢 Seeding vendors...');
        $this->call(VendorSeeder::class);

        $this->command->info('📄 Seeding contracts...');
        $this->call(ContractSeeder::class);

        $this->command->info('🎫 Seeding tickets...');
        $this->call(TicketSeeder::class);

        $this->command->info('📎 Seeding documents...');
        $this->call(DocumentSeeder::class);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@pln.co.id', 'password'],
                ['Editor', 'supervisor@pln.co.id', 'password'],
                ['Editor', 'staff@pln.co.id', 'password'],
                ['Viewer', 'manager@pln.co.id', 'password'],
                ['Viewer', 'auditor@pln.co.id', 'password'],
                ['Editor', 'kontrak@pln.co.id', 'password'],
            ]
        );
        $this->command->info('💡 You can login with any of the above credentials.');
    }
}
