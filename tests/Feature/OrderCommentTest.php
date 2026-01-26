<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCommentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Client $client;
    protected User $clientUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->client = Client::factory()->for($this->tenant)->create();

        $clientRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Client']);
        $adminRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin']);

        $this->clientUser = User::factory()->for($this->tenant)->create([
            'client_id' => $this->client->id,
        ]);
        $this->clientUser->roles()->attach($clientRole);

        $this->adminUser = User::factory()->for($this->tenant)->create();
        $this->adminUser->roles()->attach($adminRole);
    }

    public function test_client_can_add_comment_to_their_order(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.comments.store', $order), [
                'body' => 'This is my comment',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_comments', [
            'order_id' => $order->id,
            'user_id' => $this->clientUser->id,
            'body' => 'This is my comment',
            'visibility' => 'client',
        ]);
    }

    public function test_client_comments_are_always_client_visible(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        $this->actingAs($this->clientUser)
            ->post(route('client.orders.comments.store', $order), [
                'body' => 'Client comment',
            ]);

        $comment = OrderComment::where('order_id', $order->id)->first();
        $this->assertEquals('client', $comment->visibility);
    }

    public function test_client_cannot_comment_on_other_clients_orders(): void
    {
        $otherClient = Client::factory()->for($this->tenant)->create();

        $otherOrder = Order::factory()
            ->for($this->tenant)
            ->for($otherClient, 'client')
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.comments.store', $otherOrder), [
                'body' => 'Unauthorized comment',
            ]);

        $response->assertStatus(403);
    }

    public function test_client_cannot_add_empty_comment(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.comments.store', $order), [
                'body' => '',
            ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_client_can_view_client_visible_comments(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        OrderComment::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'user_id' => $this->adminUser->id,
            'visibility' => 'client',
            'body' => 'Client visible comment',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.show', $order));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('comments', 1)
        );
    }

    public function test_client_cannot_view_internal_comments(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        // Create internal comment
        OrderComment::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'user_id' => $this->adminUser->id,
            'visibility' => 'internal',
            'body' => 'Internal staff comment',
        ]);

        // Create client-visible comment
        OrderComment::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'user_id' => $this->adminUser->id,
            'visibility' => 'client',
            'body' => 'Client visible comment',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.show', $order));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('comments', 1) // Only 1 client-visible comment
        );
    }

    public function test_comment_body_has_max_length(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        $longBody = str_repeat('a', 2001); // 2001 characters (max is 2000)

        $response = $this->actingAs($this->clientUser)
            ->post(route('client.orders.comments.store', $order), [
                'body' => $longBody,
            ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_comments_are_ordered_by_newest_first(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        $comment1 = OrderComment::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'user_id' => $this->clientUser->id,
            'visibility' => 'client',
            'body' => 'First comment',
            'created_at' => now()->subHours(2),
        ]);

        $comment2 = OrderComment::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'user_id' => $this->clientUser->id,
            'visibility' => 'client',
            'body' => 'Second comment',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.show', $order));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('comments.0.body', 'Second comment')
            ->where('comments.1.body', 'First comment')
        );
    }
}
