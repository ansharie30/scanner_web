<?php

use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('creates a folder for the authenticated API user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/folders', ['name' => 'Railway scans']);

    $folder = Folder::query()->where('name', 'Railway scans')->firstOrFail();

    $response->assertCreated()->assertJson([
        'id' => $folder->id,
        'name' => 'Railway scans',
    ]);

    $this->assertModelExists($folder);
    expect($folder->created_by)->toBe($user->id);
});

it('returns 401 when creating a folder without an API token', function () {
    $this->postJson('/api/folders', ['name' => 'Railway scans'])
        ->assertUnauthorized();

    $this->assertDatabaseMissing('folders', ['name' => 'Railway scans']);
});

it('returns a validation error when the folder name is missing', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/folders', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->assertDatabaseCount('folders', 0);
});

it('returns a validation error when the folder name is already taken', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    Folder::query()->create(['name' => 'Railway scans', 'created_by' => $user->id]);

    $this->postJson('/api/folders', ['name' => 'Railway scans'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->assertDatabaseCount('folders', 1);
});

it('creates a subfolder and uploads multiple scanner documents into it', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $parent = Folder::query()->create([
        'name' => 'Project files',
        'created_by' => $user->id,
    ]);

    $response = $this->post("/api/folders/{$parent->id}/subfolders", [
        'name' => 'Invoices',
        'files' => [
            UploadedFile::fake()->create('january.pdf', 20, 'application/pdf'),
            UploadedFile::fake()->create('february.pdf', 30, 'application/pdf'),
        ],
    ]);

    $child = Folder::query()->where('name', 'Invoices')->firstOrFail();

    $response->assertCreated()
        ->assertJsonPath('folder.id', $child->id)
        ->assertJsonPath('folder.parent_id', $parent->id)
        ->assertJsonCount(2, 'documents');

    $this->assertDatabaseHas('folders', [
        'id' => $child->id,
        'parent_id' => $parent->id,
        'source' => 'scanner',
    ]);
    $this->assertDatabaseCount('documents', 2);
    $this->assertDatabaseHas('documents', [
        'folder_id' => $child->id,
        'source' => 'scanner',
        'uploaded_by' => $user->id,
    ]);
    expect(Storage::disk('local')->allFiles("documents/{$child->id}"))->toHaveCount(2);
});

it('rejects creating an API subfolder inside another subfolder', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $root = Folder::query()->create([
        'name' => 'Root folder',
        'created_by' => $user->id,
    ]);
    $child = $root->children()->create([
        'name' => 'Child folder',
        'created_by' => $user->id,
    ]);

    $this->post("/api/folders/{$child->id}/subfolders", [
        'files' => [UploadedFile::fake()->create('scan.pdf', 10, 'application/pdf')],
    ])->assertForbidden();

    $this->assertDatabaseMissing('folders', [
        'parent_id' => $child->id,
    ]);
    $this->assertDatabaseCount('documents', 0);
});

it('generates a parent-based timestamp name when an API subfolder name is empty', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $parent = Folder::query()->create([
        'name' => 'ParentFolderName',
        'created_by' => $user->id,
    ]);

    foreach ([[], ['name' => '']] as $nameData) {
        $response = $this->post("/api/folders/{$parent->id}/subfolders", $nameData + [
            'files' => [UploadedFile::fake()->create('scan.pdf', 10, 'application/pdf')],
        ]);

        $response->assertCreated();
        expect($response->json('folder.name'))
            ->toMatch('/^ParentFolderName_SubFolder_\d{8}_\d{6}_\d{6}$/');
    }
});

it('requires authentication and files when creating a subfolder by API', function () {
    $parent = Folder::query()->create(['name' => 'Project files']);

    $this->postJson("/api/folders/{$parent->id}/subfolders", [
        'name' => 'Invoices',
        'files' => [UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf')],
    ])->assertUnauthorized();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/folders/{$parent->id}/subfolders", ['name' => 'Invoices'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('files');

    $this->assertDatabaseMissing('folders', [
        'name' => 'Invoices',
        'parent_id' => $parent->id,
    ]);
});
