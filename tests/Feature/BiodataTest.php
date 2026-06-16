<?php

use App\Models\BadanPublik;
use App\Models\User;
use Livewire\Volt\Volt;

// ── Access control ────────────────────────────────────────────────────────

test('authenticated user can access biodata edit page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/biodata/edit');

    $response->assertOk();
});

test('guest cannot access biodata edit page', function () {
    $response = $this->get('/biodata/edit');

    $response->assertRedirect('/login');
});

// ── Form pre-filling ──────────────────────────────────────────────────────

test('biodata form shows existing data', function () {
    $user = User::factory()->create(['name' => 'Existing User']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Existing Badan Publik',
        'website' => 'https://existing.example.com',
        'telepon_badan_publik' => '0286-999999',
        'email_badan_publik' => 'existing@example.com',
        'alamat' => 'Existing Address',
        'telepon_responden' => '081999999999',
        'jabatan' => 'Existing Position',
    ]);

    $this->actingAs($user);

    Volt::test('pages.edit-biodata')
        ->assertSet('nama_badan_publik', 'Existing Badan Publik')
        ->assertSet('website', 'https://existing.example.com')
        ->assertSet('telepon_badan_publik', '0286-999999')
        ->assertSet('email_badan_publik', 'existing@example.com')
        ->assertSet('alamat', 'Existing Address')
        ->assertSet('nama_responden', 'Existing User')
        ->assertSet('telepon_responden', '081999999999')
        ->assertSet('jabatan', 'Existing Position');
});

// ── Update biodata ────────────────────────────────────────────────────────

test('can update badan publik fields', function () {
    $user = User::factory()->create(['name' => 'Original Name']);
    $badanPublik = BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Original BP',
        'website' => 'https://original.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'original@example.com',
        'alamat' => 'Original Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Original Position',
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.edit-biodata')
        ->set('nama_badan_publik', 'Updated Badan Publik')
        ->set('website', 'https://updated.example.com')
        ->set('telepon_badan_publik', '0286-111111')
        ->set('email_badan_publik', 'updated@example.com')
        ->set('alamat', 'Updated Address')
        ->set('nama_responden', 'Updated Name')
        ->set('telepon_responden', '081111111111')
        ->set('jabatan', 'Updated Position');

    $component->call('updateBiodata');

    $component->assertHasNoErrors();
    $component->assertRedirect(route('dashboard', absolute: false));

    $badanPublik->refresh();
    expect($badanPublik->nama_badan_publik)->toBe('Updated Badan Publik');
    expect($badanPublik->website)->toBe('https://updated.example.com');
    expect($badanPublik->telepon_badan_publik)->toBe('0286-111111');
    expect($badanPublik->email_badan_publik)->toBe('updated@example.com');
    expect($badanPublik->alamat)->toBe('Updated Address');

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
});

test('can update responden fields', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test BP',
        'website' => 'https://test.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Old Position',
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.edit-biodata')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'test@example.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'New Respondent Name')
        ->set('telepon_responden', '081222222222')
        ->set('jabatan', 'New Position');

    $component->call('updateBiodata');

    $component->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('New Respondent Name');

    $bp = $user->badanPublik;
    expect($bp->telepon_responden)->toBe('081222222222');
    expect($bp->jabatan)->toBe('New Position');
});

// ── Validation ────────────────────────────────────────────────────────────

test('validates required fields on update', function () {
    $user = User::factory()->create();
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test BP',
        'website' => 'https://test.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Position',
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.edit-biodata')
        ->set('nama_badan_publik', '')
        ->set('website', '')
        ->set('telepon_badan_publik', '')
        ->set('email_badan_publik', '')
        ->set('alamat', '')
        ->set('nama_responden', '')
        ->set('telepon_responden', '')
        ->set('jabatan', '');

    $component->call('updateBiodata');

    $component->assertHasErrors([
        'nama_badan_publik',
        'website',
        'telepon_badan_publik',
        'email_badan_publik',
        'alamat',
        'nama_responden',
        'telepon_responden',
        'jabatan',
    ]);
});

test('validates email format on update', function () {
    $user = User::factory()->create();
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test BP',
        'website' => 'https://test.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Position',
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.edit-biodata')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'not-an-email')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Test')
        ->set('telepon_responden', '081000000000')
        ->set('jabatan', 'Position');

    $component->call('updateBiodata');

    $component->assertHasErrors(['email_badan_publik']);
});

test('shows success message after update', function () {
    $user = User::factory()->create();
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test BP',
        'website' => 'https://test.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Position',
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.edit-biodata')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'test@example.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Test')
        ->set('telepon_responden', '081000000000')
        ->set('jabatan', 'Position');

    $component->call('updateBiodata');

    $component->assertHasNoErrors();
});
