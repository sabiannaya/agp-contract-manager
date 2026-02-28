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

        $this->command->info('🎫 Seeding tickets (payment submissions)...');
        $this->call(TicketSeeder::class);

        $this->command->info('📎 Seeding documents...');
        $this->call(DocumentSeeder::class);

        $this->command->info('💳 Seeding payment tracker (approvers, approval steps, payment cache)...');
        $this->call(PaymentTrackerSeeder::class);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Role Group', 'Email', 'Password', 'Notes'],
            [
                ['Admin',    'admin@pln.co.id',      'password', 'Full access · Contract master on several contracts'],
                ['Operator', 'supervisor@pln.co.id',  'password', 'Approver on most contracts'],
                ['Operator', 'staff@pln.co.id',       'password', 'Operator'],
                ['Viewer',   'manager@pln.co.id',     'password', 'Final-step approver · Read-only pages'],
                ['Viewer',   'auditor@pln.co.id',     'password', 'Read-only'],
                ['Operator', 'kontrak@pln.co.id',     'password', 'Contract staff · Approver · Creates payments'],
                ['Operator', 'john@pln.co.id',        'password', 'Operator · Approver candidate'],
                ['Operator', 'ken@pln.co.id',         'password', 'Operator · Approver candidate'],
                ['Operator', 'ray@pln.co.id',         'password', 'Operator · Non-stakeholder scenario'],
                ['Operator', 'sinta@pln.co.id',       'password', 'Operator · Procurement reviewer'],
                ['Viewer',   'rudi@pln.co.id',        'password', 'Viewer · Finance observer'],
                ['Viewer',   'nina@pln.co.id',        'password', 'Viewer · QA observer'],
            ]
        );
        $this->command->info('💡 You can login with any of the above credentials.');
    }
}
