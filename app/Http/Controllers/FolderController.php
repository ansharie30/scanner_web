<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FolderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('folders/Index', [
            'folders' => Folder::withCount('documents')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:folders,name'],
        ]);

        Folder::create([
            'name' => trim($data['name']),
            'created_by' => $request->user()->id,
        ]);

        return back();
    }

    public function show(Request $request, Folder $folder): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['sometimes', 'integer', Rule::in([10, 20, 50])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $search = trim($validated['search'] ?? '');
        $dateFrom = $validated['date_from'] ?? '';
        $dateTo = $validated['date_to'] ?? '';
        $perPage = (int) ($validated['per_page'] ?? 10);

        $documentsQuery = $folder->documents()->with('uploader:id,name');

        if ($search !== '') {
            $documentsQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhereHas('uploader', fn (Builder $uploaderQuery) => $uploaderQuery
                        ->where('name', 'like', "%{$search}%"));
            });
        }

        if ($dateFrom !== '') {
            $documentsQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $documentsQuery->whereDate('created_at', '<=', $dateTo);
        }

        $documents = $documentsQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Document $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'original_name' => $document->original_name,
                'size' => $document->size,
                'source' => $document->source,
                'uploaded_by' => $document->uploaded_by,
                'uploader_name' => $document->uploader?->name,
                'created_at' => $document->created_at->format('M d, Y h:i A'),
            ]);

        return Inertia::render('folders/Show', [
            'folder' => $folder->only(['id', 'name']),
            'documents' => $documents,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'perPage' => $perPage,
            'totalDocuments' => $folder->documents()->count(),
            'totalSize' => $folder->documents()->sum('size'),
            'scannerUploads' => $folder->documents()->where('source', 'scanner')->count(),
        ]);
    }

    public function update(Request $request, Folder $folder): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('folders', 'name')->ignore($folder->id)],
        ]);

        $folder->update(['name' => trim($data['name'])]);

        return back();
    }

    public function destroy(Request $request, Folder $folder): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        Storage::disk('local')->deleteDirectory("documents/{$folder->id}");
        $folder->delete();

        return redirect('/folders');
    }
}
