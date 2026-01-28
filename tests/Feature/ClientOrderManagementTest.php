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

class ClientOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Client $client;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->tenant = Tenant::factory()->create([
            'settings' => [
                'order_number_prefix' => 'TEST-',
                'allowed_input_extensions' => 'jpg,jpeg,png,pdf',
                'max_upload_mb' => 25,
            ],
        ]);

        $this->client = Client::factory()->for($this->tenant)->create();

        $clientRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Client']);

        $this->clientUser = User::factory()->for($this->tenant)->create([
            'client_id' => $this->client->id,
        ]);
        $this->clientUser->roles()->attach($clientRole);
    }

    public function test_client_can_create_order(): void
    {
        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'Test Order',
                'description' => 'Test description',
                'quantity' => 10,
                'priority' => 'normal',
                'type' => 'digitizing',
                'is_quote' => false,
                'input_files' => [$file],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'client_id' => $this->client->id,
            'title' => 'Test Order',
            'quantity' => 10,
            'status' => OrderStatus::RECEIVED->value,
        ]);
    }

    public function test_client_cannot_create_order_without_required_fields(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => '',
                'quantity' => 0,
            ]);

        $response->assertSessionHasErrors(['title', 'quantity', 'priority', 'type', 'input_files']);
    }

    public function test_client_cannot_upload_disallowed_file_types(): void
    {
        $file = UploadedFile::fake()->create('document.exe', 100);

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'Test Order',
                'quantity' => 10,
                'priority' => 'normal',
                'type' => 'digitizing',
                'is_quote' => false,
                'input_files' => [$file],
            ]);

        $response->assertSessionHasErrors('input_files');
    }

    public function test_order_gets_unique_order_number(): void
    {
        $file = UploadedFile::fake()->image('design.jpg');

        $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'Order 1',
                'quantity' => 10,
                'priority' => 'normal',
                'type' => 'digitizing',
                'is_quote' => false,
                'input_files' => [$file],
            ]);

        $file2 = UploadedFile::fake()->image('design2.jpg');

        $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'Order 2',
                'quantity' => 5,
                'priority' => 'normal',
                'type' => 'vector',
                'is_quote' => false,
                'input_files' => [$file2],
            ]);

        $orders = Order::where('tenant_id', $this->tenant->id)->get();
        $this->assertCount(2, $orders);
        $this->assertNotEquals($orders[0]->order_number, $orders[1]->order_number);
    }

    public function test_rush_orders_are_marked_correctly(): void
    {
        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'Rush Order',
                'quantity' => 10,
                'priority' => 'rush',
                'type' => 'digitizing',
                'is_quote' => false,
                'input_files' => [$file],
            ]);

        $order = Order::where('title', 'Rush Order')->first();
        $this->assertEquals('rush', $order->priority->value);
    }

    public function test_quote_orders_are_marked_correctly(): void
    {
        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'Quote Request',
                'quantity' => 10,
                'priority' => 'normal',
                'type' => 'digitizing',
                'is_quote' => true,
                'input_files' => [$file],
            ]);

        $order = Order::where('title', 'Quote Request')->first();
        $this->assertTrue($order->is_quote);
    }

    public function test_order_has_correct_initial_status(): void
    {
        $file = UploadedFile::fake()->image('design.jpg');

        $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'New Order',
                'quantity' => 10,
                'priority' => 'normal',
                'type' => 'digitizing',
                'is_quote' => false,
                'input_files' => [$file],
            ]);

        $order = Order::where('title', 'New Order')->first();
        $this->assertEquals(OrderStatus::RECEIVED, $order->status);
    }

    public function test_order_stores_creator_user_id(): void
    {
        $file = UploadedFile::fake()->image('design.jpg');

        $this->actingAs($this->clientUser)
            ->post(route('client.orders.store'), [
                'title' => 'New Order',
                'quantity' => 10,
                'priority' => 'normal',
                'type' => 'digitizing',
                'is_quote' => false,
                'input_files' => [$file],
            ]);

        $order = Order::where('title', 'New Order')->first();
        $this->assertEquals($this->clientUser->id, $order->created_by);
    }
}
