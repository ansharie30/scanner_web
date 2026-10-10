<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $recentDocuments = Document::query()
            ->with('folder:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'folder_id', 'title', 'original_name', 'size', 'source', 'created_at'])
            ->map(fn (Document $document): array => [
                'id' => $document->id,
                'folder_id' => $document->folder_id,
                'folder_name' => $document->folder->name,
                'title' => $document->title,
                'original_name' => $document->original_name,
                'size' => $document->size,
                'source' => $document->source,
                'created_at' => $document->created_at->toIso8601String(),
            ]);

        $topFolders = Folder::query()
            ->withCount('documents')
            ->orderByDesc('documents_count')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name']);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_documents' => Document::query()->count(),
                'total_folders' => Folder::query()->count(),
                'storage_bytes' => Document::query()->sum('size'),
                'scanner_uploads' => Document::query()->where('source', 'scanner')->count(),
            ],
            'recentDocuments' => $recentDocuments,
            'topFolders' => $topFolders,
        ]);
    }
}
