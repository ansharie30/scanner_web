<?php

namespace App\Http\Controllers;

use App\Models\Folder;
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

    public function show(Folder $folder): Response
    {
        $documents = $folder->documents()
            ->with('uploader:id,name')
            ->latest()
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'original_name' => $doc->original_name,
                'size' => $doc->size,
                'source' => $doc->source,
                'uploaded_by' => $doc->uploaded_by,
                'uploader_name' => $doc->uploader?->name,
                'created_at' => $doc->created_at->format('M d, Y h:i A'),
            ]);

        return Inertia::render('folders/Show', [
            'folder' => $folder->only(['id', 'name']),
            'documents' => $documents,
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