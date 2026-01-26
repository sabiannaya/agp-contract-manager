<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use App\Models\Ticket;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Basic counts
        $stats = [
            'vendors' => [
                'total' => Vendor::count(),
                'active' => Vendor::where('is_active', true)->count(),
            ],
            'contracts' => [
                'total' => Contract::count(),
                'active' => Contract::where('is_active', true)->count(),
                'total_amount' => Contract::where('is_active', true)->sum('amount'),
            ],
            'tickets' => [
                'total' => Ticket::count(),
                'complete' => Ticket::where('status', 'complete')->count(),
                'incomplete' => Ticket::where('status', 'incomplete')->count(),
            ],
            'documents' => [
                'total' => Document::count(),
            ],
        ];

        // Recent tickets
        $recentTickets = Ticket::with(['contract', 'vendor'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($ticket) => [
                'id' => $ticket->id,
                'number' => $ticket->number,
                'date' => $ticket->date->format('Y-m-d'),
                'status' => $ticket->status,
                'contract_number' => $ticket->contract?->number,
                'vendor_name' => $ticket->vendor?->name,
            ]);

        // Contracts by cooperation type
        $contractsByType = Contract::select('cooperation_type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->where('is_active', true)
            ->groupBy('cooperation_type')
            ->get()
            ->keyBy('cooperation_type')
            ->map(fn ($item) => [
                'count' => $item->count,
                'total_amount' => (float) $item->total_amount,
            ]);

        // Tickets by status
        $ticketsByStatus = Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn ($item) => $item->count);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'contractsByType' => $contractsByType,
            'ticketsByStatus' => $ticketsByStatus,
        ]);
    }
}
