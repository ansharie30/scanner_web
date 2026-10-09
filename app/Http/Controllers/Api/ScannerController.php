<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    public function folders(): JsonResponse
    {
        return response()->json(
            Folder::orderBy('name')->get(['id', 'name'])
        );
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
}