<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(DocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');
        $ticketId = $data['ticket_id'];

        // Delete existing document of same type for this ticket
        $existingDoc = Document::where('ticket_id', $ticketId)
            ->where('type', $data['type'])
            ->first();

        if ($existingDoc) {
            $existingDoc->delete();
        }

        // Store the file
        $path = $file->store("documents/{$ticketId}", 'local');

        // Create document record
        Document::create([
            'ticket_id' => $ticketId,
            'type' => $data['type'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by_user_id' => $request->user()?->id,
        ]);

        // Update ticket status
        $ticket = Ticket::find($ticketId);
        $ticket->updateStatus();

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function download(Document $document): StreamedResponse
    {
        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Document not found.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name
        );
    }

    public function preview(Document $document): StreamedResponse
    {
        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Document not found.');
        }

        return Storage::disk('local')->response(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    public function destroy(Document $document): RedirectResponse
    {
        $ticketId = $document->ticket_id;

        $document->delete();

        // Update ticket status
        $ticket = Ticket::find($ticketId);
        if ($ticket) {
            $ticket->updateStatus();
        }

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Upload multiple documents at once.
     */
    public function uploadMultiple(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'documents' => ['required', 'array'],
            'documents.*.type' => ['required', 'in:' . implode(',', Document::TYPES)],
            'documents.*.file' => [
                'required',
                'file',
                'max:' . Document::MAX_FILE_SIZE,
                'mimes:pdf,jpg,jpeg,png',
            ],
        ]);

        foreach ($request->input('documents', []) as $index => $docData) {
            $file = $request->file("documents.{$index}.file");

            if (!$file) {
                continue;
            }

            // Delete existing document of same type
            $existingDoc = Document::where('ticket_id', $ticket->id)
                ->where('type', $docData['type'])
                ->first();

            if ($existingDoc) {
                $existingDoc->delete();
            }

            // Store the file
            $path = $file->store("documents/{$ticket->id}", 'local');

            // Create document record
            Document::create([
                'ticket_id' => $ticket->id,
                'type' => $docData['type'],
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by_user_id' => $request->user()?->id,
            ]);
        }

        // Update ticket status
        $ticket->updateStatus();

        return back()->with('success', 'Documents uploaded successfully.');
    }
}
