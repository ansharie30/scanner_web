<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ScannerController extends Controller
{
    public function folders(): JsonResponse
    {
        return response()->json(
            Folder::query()->orderBy('name')->get(['id', 'name', 'parent_id'])
        );
    }

    public function storeFolder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:folders,name'],
        ]);

        $folder = Folder::create([
            'name' => trim($data['name']),
            'created_by' => $request->user()->id,
            'source' => 'scanner',
        ]);

        return response()->json([
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
        ], 201);
    }

    public function upload(Request $request, Folder $folder): JsonResponse
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

        $document = Document::create([
            'folder_id' => $folder->id,
            'uploaded_by' => $request->user()->id,
            'title' => trim($data['title'] ?? '') ?: pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'source' => 'scanner',
        ]);

        return response()->json([
            'message' => 'Uploaded.',
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'folder' => $folder->name,
            ],
        ], 201);
    }

    public function storeSubfolderWithUploads(Request $request, Folder $folder): JsonResponse
    {
        abort_unless($folder->parent_id === null, 403, 'Subfolders can only be created inside top-level folders.');

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255', 'unique:folders,name'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png'],
        ]);
        $requestedName = trim($data['name'] ?? '');
        $subfolderName = $requestedName !== ''
            ? $requestedName
            : Str::substr($folder->name, 0, 220).'_SubFolder_'.now()->format('Ymd_His_u');

        $subfolderId = null;

        try {
            $documents = DB::transaction(function () use ($data, $folder, $request, $subfolderName, &$subfolderId): array {
                $subfolder = $folder->children()->create([
                    'name' => $subfolderName,
                    'created_by' => $request->user()->id,
                    'source' => 'scanner',
                ]);
                $subfolderId = $subfolder->id;

                return collect($data['files'])
                    ->map(function (UploadedFile $file) use ($request, $subfolder): array {
                        $originalName = $file->getClientOriginalName();
                        $path = $file->store("documents/{$subfolder->id}", 'local');

                        $document = Document::create([
                            'folder_id' => $subfolder->id,
                            'uploaded_by' => $request->user()->id,
                            'title' => pathinfo($originalName, PATHINFO_FILENAME),
                            'original_name' => $originalName,
                            'path' => $path,
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize(),
                            'source' => 'scanner',
                        ]);

                        return [
                            'id' => $document->id,
                            'title' => $document->title,
                            'original_name' => $document->original_name,
                            'size' => $document->size,
                        ];
                    })
                    ->all();
            });
        } catch (Throwable $exception) {
            if ($subfolderId !== null) {
                Storage::disk('local')->deleteDirectory("documents/{$subfolderId}");
            }

            throw $exception;
        }

        $subfolder = Folder::query()->findOrFail($subfolderId);

        return response()->json([
            'message' => 'Subfolder created and files uploaded.',
            'folder' => [
                'id' => $subfolder->id,
                'name' => $subfolder->name,
                'parent_id' => $subfolder->parent_id,
            ],
            'documents' => $documents,
        ], 201);
    }
}
