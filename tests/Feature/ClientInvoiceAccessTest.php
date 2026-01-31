<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientInvoiceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createClientUser($tenant): array
    {
        $this->ensureRoleExists($tenant, 'Client');

        $client = Client::factory()->for($tenant)->create();
        $user = User::factory()->for($tenant)->create([
            'client_id' => $client->id,
        ]);
        $user->assignRole('Client');

        return [$client, $user];
    }

    public function test_client_can_view_sent_invoice(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        [$client, $clientUser] = $this->createClientUser($tenant);

        $invoice = Invoice::factory()
            ->for($tenant)
            ->for($client)
            ->sent()
            ->create(['created_by' => $admin->id]);

        $response = $this->actingAs($clientUser)->get(route('client.invoices.show', $invoice));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Invoices/Show')
            ->has('invoice')
            ->where('invoice.id', $invoice->id)
            ->where('invoice.status', InvoiceStatus::SENT->value)
        );
    }

    public function test_client_cannot_view_draft_invoice(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        [$client, $clientUser] = $this->createClientUser($tenant);

        $invoice = Invoice::factory()
            ->for($tenant)
            ->for($client)
            ->draft()
            ->create(['created_by' => $admin->id]);

        $response = $this->actingAs($clientUser)->get(route('client.invoices.show', $invoice));

        $response->assertForbidden();
    }

    public function test_client_cannot_view_other_clients_invoice(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        [$client, $clientUser] = $this->createClientUser($tenant);

        $otherClient = Client::factory()->for($tenant)->create();
        $invoice = Invoice::factory()
            ->for($tenant)
            ->for($otherClient)
            ->sent()
            ->create(['created_by' => $admin->id]);

        $response = $this->actingAs($clientUser)->get(route('client.invoices.show', $invoice));

        $response->assertForbidden();
    }

    public function test_client_can_list_their_invoices(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        [$client, $clientUser] = $this->createClientUser($tenant);

        // Create a sent invoice (visible) and a draft invoice (hidden)
        Invoice::factory()->for($tenant)->for($client)->sent()->create(['created_by' => $admin->id]);
        Invoice::factory()->for($tenant)->for($client)->draft()->create(['created_by' => $admin->id]);

        $response = $this->actingAs($clientUser)->get(route('client.invoices.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Invoices/Index')
            ->has('invoices.data', 1) // Only the sent invoice should be visible
        );
    }

    public function test_client_cannot_download_draft_invoice_pdf(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        [$client, $clientUser] = $this->createClientUser($tenant);

        $invoice = Invoice::factory()
            ->for($tenant)
            ->for($client)
            ->draft()
            ->create(['created_by' => $admin->id]);

        $response = $this->actingAs($clientUser)->get(route('client.invoices.pdf', $invoice));

        $response->assertForbidden();
    }

    public function test_client_invoice_list_excludes_draft_invoices(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        [$client, $clientUser] = $this->createClientUser($tenant);

        Invoice::factory()->for($tenant)->for($client)->sent()->create(['created_by' => $admin->id]);
        Invoice::factory()->for($tenant)->for($client)->paid()->create(['created_by' => $admin->id]);
        Invoice::factory()->for($tenant)->for($client)->draft()->create(['created_by' => $admin->id]);

        $response = $this->actingAs($clientUser)->get(route('client.invoices.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Invoices/Index')
            ->has('invoices.data', 2) // Only sent + paid, not draft
        );
    }
}
