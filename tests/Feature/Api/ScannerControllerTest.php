<?php

use App\Models\Folder;
use App\Models\User;
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
