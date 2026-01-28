<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderRevision;
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

    public function test_revision_loop_allows_designer_to_resubmit_work(): void
    {
        $this->order->update([
            'status' => OrderStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $revisionResponse = $this->actingAs($this->admin)
            ->post(route('orders.request-revision', $this->order), [
                'notes' => 'Need a cleaner outline.',
            ]);

        $revisionResponse->assertRedirect();

        $orderAfterRevision = $this->order->fresh();
        $this->assertEquals(OrderStatus::REVISION_REQUESTED, $orderAfterRevision->status);
        $this->assertDatabaseCount('order_revisions', 1);

        // Designer moves order back to in progress
        $this->actingAs($this->designer)
            ->patch(route('orders.status', $this->order), [
                'status' => OrderStatus::IN_PROGRESS->value,
            ])
            ->assertRedirect();

        $this->assertEquals(
            OrderStatus::IN_PROGRESS,
            $this->order->fresh()->status,
            'Order should be back in progress before resubmission.'
        );

        $file = UploadedFile::fake()->image('updated-design.png');
        $orderForSubmission = $this->order->fresh();
        $this->assertInstanceOf(OrderStatus::class, $orderForSubmission->status);
        $this->assertEquals(OrderStatus::IN_PROGRESS, $orderForSubmission->status);

        $submitResponse = $this->actingAs($this->designer)
            ->post(route('orders.submit-work', $orderForSubmission), [
                'files' => [$file],
                'notes' => 'Revision complete',
            ]);

        $submitResponse->assertRedirect();
        $submitResponse->assertSessionHasNoErrors();
        $submitResponse->assertSessionHas('success');

        $orderAfterSubmission = $this->order->fresh();
        $this->assertEquals(OrderStatus::SUBMITTED, $orderAfterSubmission->status);

        $revision = OrderRevision::latest()->first();
        $this->assertEquals('resolved', $revision->status);
        $this->assertNotNull($revision->resolved_at);
    }
}
