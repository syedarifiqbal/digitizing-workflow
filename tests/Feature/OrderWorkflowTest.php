<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Client $client;
    protected User $admin;
    protected User $designer;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->tenant = Tenant::factory()->create([
            'settings' => [
                'allowed_input_extensions' => 'jpg,png',
                'allowed_output_extensions' => 'dst,emb,png',
                'max_upload_mb' => 50,
                'auto_submit_on_upload' => true,
                'auto_review_on_submit' => false,
            ],
        ]);

        $this->client = Client::factory()->for($this->tenant)->create();

        $adminRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin']);
        $designerRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Designer']);

        $this->admin = User::factory()->for($this->tenant)->create();
        $this->admin->roles()->attach($adminRole);

        $this->designer = User::factory()->for($this->tenant)->create();
        $this->designer->roles()->attach($designerRole);

        $this->order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create([
                'status' => OrderStatus::RECEIVED,
                'designer_id' => $this->designer->id,
                'created_by' => $this->admin->id,
            ]);
    }

    public function test_admin_can_transition_order_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('orders.status', $this->order), [
                'status' => OrderStatus::ASSIGNED->value,
            ]);

        $response->assertRedirect();

        $this->assertEquals(OrderStatus::ASSIGNED, $this->order->fresh()->status);
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('orders.status', $this->order), [
                'status' => OrderStatus::DELIVERED->value,
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals(OrderStatus::RECEIVED, $this->order->fresh()->status);
    }

    public function test_revision_order_is_created_from_delivered_order(): void
    {
        // Transition order to DELIVERED via admin
        $this->order->update([
            'status' => OrderStatus::DELIVERED,
            'delivered_at' => now(),
        ]);

        // Create an input file for the parent order
        $this->order->files()->create([
            'tenant_id' => $this->tenant->id,
            'uploaded_by_user_id' => $this->admin->id,
            'type' => 'input',
            'disk' => 'local',
            'path' => "orders/{$this->order->id}/test-file.png",
            'original_name' => 'test-file.png',
            'mime_type' => 'image/png',
            'size' => 1024,
        ]);

        // Store a fake file so the copy works
        Storage::disk('local')->put("orders/{$this->order->id}/test-file.png", 'fake content');

        $response = $this->actingAs($this->admin)
            ->post(route('orders.create-revision', $this->order), [
                'notes' => 'Please fix the outline on the left side.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert new revision order was created
        $revisionOrder = Order::where('parent_order_id', $this->order->id)->first();
        $this->assertNotNull($revisionOrder);
        $this->assertStringContainsString('-R1', $revisionOrder->order_number);
        $this->assertEquals(OrderStatus::RECEIVED, $revisionOrder->status);
        $this->assertNull($revisionOrder->designer_id);
        $this->assertEquals($this->order->client_id, $revisionOrder->client_id);

        // Assert input files were copied
        $this->assertCount(1, $revisionOrder->files()->where('type', 'input')->get());
    }
}
