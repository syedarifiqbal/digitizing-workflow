<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompleteOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_order_lifecycle_from_creation_to_client_portal(): void
    {
        Storage::fake('local');
        Notification::fake();

        [$tenant, $admin] = $this->createTenantAdmin();
        $this->ensureRoleExists($tenant, 'Designer');
        $this->ensureRoleExists($tenant, 'Client');

        $designer = User::factory()->for($tenant)->create();
        $designer->assignRole('Designer');

        $client = Client::factory()->for($tenant)->create();
        $clientUser = User::factory()->for($tenant)->create([
            'client_id' => $client->id,
        ]);
        $clientUser->assignRole('Client');

        // Admin creates an order for the client
        $createResponse = $this->actingAs($admin)->post(route('orders.store'), [
            'client_id' => $client->id,
            'title' => 'Left Chest Logo',
            'instructions' => 'Digitize with satin border.',
            'priority' => 'normal',
            'type' => 'digitizing',
            'quantity' => 25,
            'price_amount' => 150,
            'currency' => 'USD',
        ]);
        $createResponse->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::RECEIVED, $order->status);

        // Admin assigns a designer which auto-transitions the order to ASSIGNED
        $assignResponse = $this->actingAs($admin)->post(route('orders.assign', $order), [
            'designer_id' => $designer->id,
        ]);
        $assignResponse->assertRedirect();

        $order->refresh();
        $this->assertEquals($designer->id, $order->designer_id);
        $this->assertEquals(OrderStatus::ASSIGNED, $order->status);

        // Designer moves the order into progress
        $inProgressResponse = $this->actingAs($designer)->patch(route('orders.status', $order), [
            'status' => OrderStatus::IN_PROGRESS->value,
        ]);
        $inProgressResponse->assertRedirect();
        $this->assertEquals(OrderStatus::IN_PROGRESS, $order->fresh()->status);

        // Designer submits work which auto transitions to SUBMITTED
        $file = UploadedFile::fake()->create('design.pdf', 150, 'application/pdf');
        $submitResponse = $this->actingAs($designer)->post(route('orders.submit-work', $order), [
            'files' => [$file],
            'notes' => 'Ready for review',
        ]);
        $submitResponse->assertRedirect();
        $order->refresh();
        $this->assertEquals(OrderStatus::SUBMITTED, $order->status);

        // Admin reviews and approves the work
        $reviewResponse = $this->actingAs($admin)->patch(route('orders.status', $order), [
            'status' => OrderStatus::IN_REVIEW->value,
        ]);
        $reviewResponse->assertRedirect();
        $this->assertEquals(OrderStatus::IN_REVIEW, $order->fresh()->status);

        $approveResponse = $this->actingAs($admin)->patch(route('orders.status', $order), [
            'status' => OrderStatus::APPROVED->value,
        ]);
        $approveResponse->assertRedirect();
        $this->assertEquals(OrderStatus::APPROVED, $order->fresh()->status);

        // Admin delivers the approved order to the client
        $deliverResponse = $this->actingAs($admin)->post(route('orders.deliver', $order), [
            'message' => 'Files delivered to client portal.',
        ]);
        $deliverResponse->assertRedirect();
        $order->refresh();
        $this->assertEquals(OrderStatus::DELIVERED, $order->status);
        $this->assertNotNull($order->delivered_at);

        // Client can see the delivered order (with output files) inside their portal
        $clientView = $this->actingAs($clientUser)->get(route('client.orders.show', $order));

        $clientView->assertInertia(fn (Assert $page) => $page
            ->component('Client/Orders/Show')
            ->where('order.id', $order->id)
            ->where('order.status', OrderStatus::DELIVERED->value)
            ->where('showOutputFiles', true)
        );
    }
}
