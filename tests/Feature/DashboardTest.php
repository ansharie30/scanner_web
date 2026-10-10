<?php

use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view library metrics and recent documents on the dashboard', function () {
    $user = User::factory()->create();
    $projectFolder = Folder::create([
        'name' => 'Project files',
        'created_by' => $user->id,
    ]);
    Folder::create([
        'name' => 'Archive',
        'created_by' => $user->id,
    ]);

    Document::create([
        'folder_id' => $projectFolder->id,
        'uploaded_by' => $user->id,
        'title' => 'Older contract',
        'original_name' => 'contract.pdf',
        'path' => 'documents/1/contract.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
        'source' => 'manual',
        'created_at' => now()->subDay(),
    ]);
    Document::create([
        'folder_id' => $projectFolder->id,
        'uploaded_by' => $user->id,
        'title' => 'Latest scanner report',
        'original_name' => 'report.pdf',
        'path' => 'documents/1/report.pdf',
        'mime_type' => 'application/pdf',
        'size' => 2048,
        'source' => 'scanner',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('stats', fn (Assert $page) => $page
            ->where('total_documents', 2)
            ->where('total_folders', 2)
            ->where('storage_bytes', 3072)
            ->where('scanner_uploads', 1)
        )
        ->has('recentDocuments', 2, fn (Assert $page) => $page
            ->where('title', 'Latest scanner report')
            ->where('folder_name', 'Project files')
            ->etc()
        )
        ->has('topFolders', 2, fn (Assert $page) => $page
            ->where('name', 'Project files')
            ->where('documents_count', 2)
            ->etc()
        )
    );
});
