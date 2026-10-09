<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(Request $request, Folder $folder): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png'], // 20 MB
        ]);

        $file = $request->file('file');

        $originalName = $file->getClientOriginalName();
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $path = $file->store("documents/{$folder->id}", 'local');

        Document::create([
            'folder_id' => $folder->id,
            'uploaded_by' => $request->user()->id,
            'title' => trim($data['title'] ?? '') ?: pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'source' => 'manual',
        ]);

        return back();
    }

    public function preview(Document $document): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->file(
            Storage::disk('local')->path($document->path),
            ['Content-Type' => $document->mime_type ?? 'application/octet-stream']
        );
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isSuperAdmin() || $document->uploaded_by === $user->id, 403);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back();
    }
}