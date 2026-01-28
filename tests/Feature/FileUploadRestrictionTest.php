<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Client $client;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->tenant = Tenant::factory()->create([
            'settings' => [
                'allowed_input_extensions' => 'jpg,png',
                'max_upload_mb' => 1,
            ],
        ]);

        $this->client = Client::factory()->for($this->tenant)->create();

        $adminRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin']);

        $this->admin = User::factory()->for($this->tenant)->create();
        $this->admin->roles()->attach($adminRole);
    }

    public function test_admin_cannot_upload_disallowed_file_types(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 50);

        $response = $this->actingAs($this->admin)
            ->post(route('orders.store'), [
                'client_id' => $this->client->id,
                'title' => 'Bad Upload',
                'priority' => 'normal',
                'type' => 'digitizing',
                'attachments' => [$file],
            ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    public function test_admin_cannot_upload_files_exceeding_max_size(): void
    {
        $file = UploadedFile::fake()->create('large.jpg', 2048); // 2 MB

        $response = $this->actingAs($this->admin)
            ->post(route('orders.store'), [
                'client_id' => $this->client->id,
                'title' => 'Huge Upload',
                'priority' => 'normal',
                'type' => 'digitizing',
                'attachments' => [$file],
            ]);

        $response->assertSessionHasErrors('attachments.0');
    }
}

