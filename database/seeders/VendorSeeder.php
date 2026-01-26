<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = [
            [
                'code' => 'VND001',
                'name' => 'PT Mitra Teknik Indonesia',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'join_date' => '2023-01-15',
                'contact_person' => 'Budi Santoso',
                'tax_id' => '01.234.567.8-901.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND002',
                'name' => 'CV Karya Mandiri',
                'address' => 'Jl. Ahmad Yani No. 45, Surabaya',
                'join_date' => '2023-03-20',
                'contact_person' => 'Siti Rahayu',
                'tax_id' => '02.345.678.9-012.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND003',
                'name' => 'PT Solusi Energi Nusantara',
                'address' => 'Jl. Gatot Subroto No. 78, Bandung',
                'join_date' => '2023-05-10',
                'contact_person' => 'Ahmad Hidayat',
                'tax_id' => '03.456.789.0-123.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND004',
                'name' => 'PT Bangun Sejahtera',
                'address' => 'Jl. Diponegoro No. 234, Semarang',
                'join_date' => '2023-07-05',
                'contact_person' => 'Dewi Lestari',
                'tax_id' => '04.567.890.1-234.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND005',
                'name' => 'CV Listrik Prima',
                'address' => 'Jl. Pahlawan No. 56, Medan',
                'join_date' => '2023-09-12',
                'contact_person' => 'Rudi Hartono',
                'tax_id' => '05.678.901.2-345.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND006',
                'name' => 'PT Teknologi Masa Depan',
                'address' => 'Jl. HR Rasuna Said No. 89, Jakarta Selatan',
                'join_date' => '2024-01-08',
                'contact_person' => 'Rina Wulandari',
                'tax_id' => '06.789.012.3-456.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND007',
                'name' => 'CV Sinergi Konstruksi',
                'address' => 'Jl. Imam Bonjol No. 12, Yogyakarta',
                'join_date' => '2024-03-15',
                'contact_person' => 'Hendra Gunawan',
                'tax_id' => '07.890.123.4-567.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND008',
                'name' => 'PT Infrastruktur Terpadu',
                'address' => 'Jl. Veteran No. 67, Malang',
                'join_date' => '2024-05-20',
                'contact_person' => 'Yuni Astuti',
                'tax_id' => '08.901.234.5-678.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND009',
                'name' => 'CV Elektro Jaya',
                'address' => 'Jl. Merdeka No. 34, Palembang',
                'join_date' => '2024-08-10',
                'contact_person' => 'Agus Setiawan',
                'tax_id' => '09.012.345.6-789.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND010',
                'name' => 'PT Global Maintenance Services',
                'address' => 'Jl. Thamrin No. 156, Jakarta Pusat',
                'join_date' => '2024-10-05',
                'contact_person' => 'Linda Permata',
                'tax_id' => '10.123.456.7-890.000',
                'is_active' => true,
            ],
            [
                'code' => 'VND011',
                'name' => 'CV Cahaya Terang',
                'address' => 'Jl. Pemuda No. 91, Makassar',
                'join_date' => '2022-11-20',
                'contact_person' => 'Eko Prasetyo',
                'tax_id' => '11.234.567.8-901.000',
                'is_active' => false, // Inactive vendor
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::create($vendor);
        }
    }
}
