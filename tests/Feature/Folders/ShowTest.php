<?php

use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('folder documents can be searched by title filename uploader and source', function () {
    $user = User::factory()->create();
    $uploader = User::factory()->create(['name' => 'Avery Uploader']);
    $folder = Folder::create(['name' => 'Searchable files', 'created_by' => $user->id]);
    $otherFolder = Folder::create(['name' => 'Other files', 'created_by' => $user->id]);

    $searchCases = [
        ['term' => 'quarterly', 'title' => 'Quarterly summary', 'original_name' => 'summary.pdf', 'source' => 'manual', 'uploaded_by' => $user->id],
        ['term' => 'budget-sheet', 'title' => 'Budget', 'original_name' => 'budget-sheet.pdf', 'source' => 'manual', 'uploaded_by' => $user->id],
        ['term' => 'avery', 'title' => 'Meeting notes', 'original_name' => 'notes.pdf', 'source' => 'manual', 'uploaded_by' => $uploader->id],
        ['term' => 'scanner', 'title' => 'Intake scan', 'original_name' => 'scan.pdf', 'source' => 'scanner', 'uploaded_by' => $user->id],
    ];

    $expectedIds = [];

    foreach ($searchCases as $index => $searchCase) {
        $document = Document::create([
            'folder_id' => $folder->id,
            'uploaded_by' => $searchCase['uploaded_by'],
            'title' => $searchCase['title'],
            'original_name' => $searchCase['original_name'],
            'path' => "documents/{$folder->id}/search-{$index}.pdf",
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'source' => $searchCase['source'],
        ]);

        $expectedIds[$searchCase['term']] = $document->id;
    }

    Document::create([
        'folder_id' => $otherFolder->id,
        'uploaded_by' => $user->id,
        'title' => 'Quarterly summary in another folder',
        'original_name' => 'other.pdf',
        'path' => "documents/{$otherFolder->id}/other.pdf",
        'mime_type' => 'application/pdf',
        'size' => 512,
        'source' => 'manual',
    ]);

    foreach ($expectedIds as $term => $expectedId) {
        $response = $this->actingAs($user)->get(route('folders.show', [
            'folder' => $folder->id,
            'search' => $term,
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('folders/Show')
            ->where('search', $term)
            ->where('documents.total', 1)
            ->where('documents.data.0.id', $expectedId)
        );
    }
});

test('folder document pagination returns ten items and preserves the full folder totals', function () {
    $user = User::factory()->create();
    $folder = Folder::create(['name' => 'Paged files', 'created_by' => $user->id]);

    foreach (range(1, 12) as $number) {
        Document::create([
            'folder_id' => $folder->id,
            'uploaded_by' => $user->id,
            'title' => "Report {$number}",
            'original_name' => "report-{$number}.pdf",
            'path' => "documents/{$folder->id}/report-{$number}.pdf",
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'source' => $number % 2 === 0 ? 'scanner' : 'manual',
            'created_at' => now()->addSeconds($number),
        ]);
    }

    $response = $this->actingAs($user)->get(route('folders.show', [
        'folder' => $folder->id,
        'search' => 'report',
        'page' => 2,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('folders/Show')
        ->where('search', 'report')
        ->where('totalDocuments', 12)
        ->where('totalSize', 12 * 1024)
        ->where('scannerUploads', 6)
        ->where('documents.total', 12)
        ->where('documents.per_page', 10)
        ->where('documents.current_page', 2)
        ->has('documents.data', 2)
        ->where('documents.data.0.title', 'Report 2')
        ->where('documents.data.1.title', 'Report 1')
        ->where('documents.prev_page_url', fn ($url) => str_contains($url, 'search=report'))
    );
});

test('folder documents can be filtered by inclusive added dates and page size', function () {
    $user = User::factory()->create();
    $folder = Folder::create(['name' => 'Dated files', 'created_by' => $user->id]);

    $documents = [
        ['title' => 'Before range', 'date' => '2026-10-09 23:59:59'],
        ['title' => 'Start of range', 'date' => '2026-10-10 00:00:00'],
        ['title' => 'End of range', 'date' => '2026-10-11 23:59:59'],
        ['title' => 'After range', 'date' => '2026-10-12 00:00:00'],
    ];

    foreach ($documents as $index => $documentData) {
        $document = Document::create([
            'folder_id' => $folder->id,
            'uploaded_by' => $user->id,
            'title' => $documentData['title'],
            'original_name' => "dated-{$index}.pdf",
            'path' => "documents/{$folder->id}/dated-{$index}.pdf",
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'source' => 'manual',
        ]);
        $document->created_at = Carbon::parse($documentData['date']);
        $document->save();
    }

    $response = $this->actingAs($user)->get(route('folders.show', [
        'folder' => $folder->id,
        'date_from' => '2026-10-10',
        'date_to' => '2026-10-11',
        'per_page' => 20,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('folders/Show')
        ->where('dateFrom', '2026-10-10')
        ->where('dateTo', '2026-10-11')
        ->where('perPage', 20)
        ->where('documents.total', 2)
        ->where('documents.per_page', 20)
        ->has('documents.data', 2)
        ->where('documents.data.0.title', 'End of range')
        ->where('documents.data.1.title', 'Start of range')
    );

    $dateToOnlyResponse = $this->actingAs($user)->get(route('folders.show', [
        'folder' => $folder->id,
        'date_to' => '2026-10-10',
        'per_page' => 20,
    ]));

    $dateToOnlyResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('dateFrom', '')
        ->where('dateTo', '2026-10-10')
        ->where('documents.total', 2)
    );
});

test('a folder can contain and display child folders', function () {
    $user = User::factory()->create();
    $parent = Folder::create(['name' => 'Parent folder', 'created_by' => $user->id]);

    $response = $this->actingAs($user)->post(route('folders.subfolders.store', $parent), [
        'name' => 'Child folder',
    ]);

    $child = Folder::query()->where('name', 'Child folder')->firstOrFail();

    $response->assertRedirect();
    $this->assertDatabaseHas('folders', [
        'id' => $child->id,
        'parent_id' => $parent->id,
    ]);

    Document::create([
        'folder_id' => $child->id,
        'uploaded_by' => $user->id,
        'title' => 'Child document',
        'original_name' => 'child.pdf',
        'path' => "documents/{$child->id}/child.pdf",
        'mime_type' => 'application/pdf',
        'size' => 4096,
        'source' => 'manual',
    ]);

    $this->actingAs($user)->get(route('folders.show', $parent))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('folders/Show')
            ->has('subfolders', 1, fn (Assert $page) => $page
                ->where('id', $child->id)
                ->where('name', 'Child folder')
                ->where('documents_count', 1)
                ->where('size', 4096)
                ->where('source', 'manual')
                ->where('created_by_name', $user->name)
                ->where('created_at', $child->created_at->format('M d, Y h:i A'))
                ->etc()
            )
        );
});

test('a subfolder cannot create another subfolder', function () {
    $user = User::factory()->create();
    $root = Folder::create(['name' => 'Root folder', 'created_by' => $user->id]);
    $child = $root->children()->create([
        'name' => 'Child folder',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('folders.subfolders.store', $child), ['name' => 'Grandchild folder'])
        ->assertForbidden();

    $this->assertDatabaseMissing('folders', [
        'name' => 'Grandchild folder',
        'parent_id' => $child->id,
    ]);
});

test('the folder index lists roots while totals include nested folders and files', function () {
    $user = User::factory()->create();
    $root = Folder::create(['name' => 'Root folder', 'created_by' => $user->id]);
    $child = Folder::create([
        'name' => 'Nested folder',
        'created_by' => $user->id,
        'parent_id' => $root->id,
    ]);

    Document::create([
        'folder_id' => $child->id,
        'uploaded_by' => $user->id,
        'title' => 'Nested file',
        'original_name' => 'nested.pdf',
        'path' => "documents/{$child->id}/nested.pdf",
        'mime_type' => 'application/pdf',
        'size' => 2048,
        'source' => 'manual',
    ]);

    $this->actingAs($user)->get(route('folders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('folders/Index')
            ->has('folders', 1, fn (Assert $page) => $page
                ->where('id', $root->id)
                ->where('children_count', 1)
                ->etc()
            )
            ->where('totalFolders', 2)
            ->where('totalDocuments', 1)
        );
});

test('deleting a parent folder removes nested folders and their stored files', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'super_admin']);
    $root = Folder::create(['name' => 'Delete root', 'created_by' => $admin->id]);
    $child = Folder::create([
        'name' => 'Delete child',
        'created_by' => $admin->id,
        'parent_id' => $root->id,
    ]);
    $grandchild = Folder::create([
        'name' => 'Delete grandchild',
        'created_by' => $admin->id,
        'parent_id' => $child->id,
    ]);
    $path = "documents/{$grandchild->id}/nested.pdf";
    Storage::disk('local')->put($path, 'document');
    $document = Document::create([
        'folder_id' => $grandchild->id,
        'uploaded_by' => $admin->id,
        'title' => 'Nested document',
        'original_name' => 'nested.pdf',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 8,
        'source' => 'manual',
    ]);

    $this->actingAs($admin)->delete(route('folders.destroy', $root))
        ->assertRedirect('/folders');

    $this->assertDatabaseMissing('folders', ['id' => $root->id]);
    $this->assertDatabaseMissing('folders', ['id' => $child->id]);
    $this->assertDatabaseMissing('folders', ['id' => $grandchild->id]);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    Storage::disk('local')->assertMissing($path);
});
