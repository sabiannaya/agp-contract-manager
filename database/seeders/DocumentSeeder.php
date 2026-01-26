<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tickets = Ticket::all();

        if ($tickets->isEmpty()) {
            $this->command->warn('No tickets found. Please run TicketSeeder first.');
            return;
        }

        // Get first user to use as uploader
        $uploader = User::first();

        // Document types from migration: 'contract', 'invoice', 'handover_report', 'tax_id', 'tax_invoice'
        $documentTypes = ['contract', 'invoice', 'handover_report', 'tax_id', 'tax_invoice'];

        // Add documents to various tickets with different completion levels
        $documentData = [
            // Ticket with all 5 documents (complete)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/001')->first()->id,
                'documents' => [
                    ['type' => 'contract', 'name' => 'Kontrak_CTR_2024_001.pdf'],
                    ['type' => 'invoice', 'name' => 'INV_001_Jan_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_001_Jan.pdf'],
                    ['type' => 'tax_id', 'name' => 'NPWP_VND001.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_001_Jan.pdf'],
                ],
            ],
            // Ticket with 4 documents (missing tax_id)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/002')->first()->id,
                'documents' => [
                    ['type' => 'contract', 'name' => 'Kontrak_CTR_2024_002.pdf'],
                    ['type' => 'invoice', 'name' => 'INV_002_Feb_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_002_Feb.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_002_Feb.pdf'],
                ],
            ],
            // Ticket with 5 documents (complete)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/003')->first()->id,
                'documents' => [
                    ['type' => 'contract', 'name' => 'Kontrak_CTR_2024_003.pdf'],
                    ['type' => 'invoice', 'name' => 'INV_003_Mar_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_003_Mar.pdf'],
                    ['type' => 'tax_id', 'name' => 'NPWP_VND003.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_003_Mar.pdf'],
                ],
            ],
            // Ticket with 3 documents (missing handover_report and tax_id)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/004')->first()->id,
                'documents' => [
                    ['type' => 'contract', 'name' => 'Kontrak_CTR_2024_004.pdf'],
                    ['type' => 'invoice', 'name' => 'INV_004_T1_2024.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_004_T1.pdf'],
                ],
            ],
            // Ticket with 5 documents (complete)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/005')->first()->id,
                'documents' => [
                    ['type' => 'contract', 'name' => 'Kontrak_CTR_2024_005.pdf'],
                    ['type' => 'invoice', 'name' => 'INV_005_T1_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_005_T1.pdf'],
                    ['type' => 'tax_id', 'name' => 'NPWP_VND005.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_005_T1.pdf'],
                ],
            ],
            // Ticket with 2 documents (missing 3)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/006')->first()->id,
                'documents' => [
                    ['type' => 'invoice', 'name' => 'INV_004_T2_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_004_T2.pdf'],
                ],
            ],
            // Ticket with 4 documents (missing contract)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/007')->first()->id,
                'documents' => [
                    ['type' => 'invoice', 'name' => 'INV_006_Jun_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_006_Jun.pdf'],
                    ['type' => 'tax_id', 'name' => 'NPWP_VND006.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_006_Jun.pdf'],
                ],
            ],
            // Ticket with 3 documents (missing handover_report and tax_id)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/008')->first()->id,
                'documents' => [
                    ['type' => 'contract', 'name' => 'Kontrak_CTR_2024_004.pdf'],
                    ['type' => 'invoice', 'name' => 'INV_004_T3_2024.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_004_T3.pdf'],
                ],
            ],
            // Ticket with 1 document (missing 4)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/009')->first()->id,
                'documents' => [
                    ['type' => 'invoice', 'name' => 'INV_005_T2_2024.pdf'],
                ],
            ],
            // Ticket with 5 documents (complete)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/010')->first()->id,
                'documents' => [
                    ['type' => 'contract', 'name' => 'Kontrak_CTR_2024_007.pdf'],
                    ['type' => 'invoice', 'name' => 'INV_007_T1_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_007_T1.pdf'],
                    ['type' => 'tax_id', 'name' => 'NPWP_VND007.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_007_T1.pdf'],
                ],
            ],
            // Recent tickets with fewer documents (in progress)
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/018')->first()->id,
                'documents' => [
                    ['type' => 'invoice', 'name' => 'INV_009_T2_2024.pdf'],
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_009_T2.pdf'],
                ],
            ],
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/019')->first()->id,
                'documents' => [
                    ['type' => 'invoice', 'name' => 'INV_011_T1_2024.pdf'],
                    ['type' => 'tax_invoice', 'name' => 'FakturPajak_011_T1.pdf'],
                ],
            ],
            [
                'ticket_id' => $tickets->where('number', 'TKT/2024/020')->first()->id,
                'documents' => [
                    ['type' => 'handover_report', 'name' => 'BAST_CTR_2024_012_Dec.pdf'],
                ],
            ],
            // Latest tickets (2025) - minimal documents
            [
                'ticket_id' => $tickets->where('number', 'TKT/2025/001')->first()->id,
                'documents' => [
                    ['type' => 'invoice', 'name' => 'INV_001_T1_2025.pdf'],
                ],
            ],
        ];

        foreach ($documentData as $ticketDocs) {
            foreach ($ticketDocs['documents'] as $doc) {
                Document::create([
                    'ticket_id' => $ticketDocs['ticket_id'],
                    'type' => $doc['type'],
                    'original_name' => $doc['name'],
                    'file_path' => 'documents/' . date('Y/m') . '/' . uniqid() . '_' . $doc['name'],
                    'mime_type' => 'application/pdf',
                    'file_size' => rand(100000, 5000000), // Random file size between 100KB and 5MB
                    'uploaded_by_user_id' => $uploader?->id
                ]);
            }
        }
    }
}
