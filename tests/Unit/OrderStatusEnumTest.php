<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusEnumTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertEquals('received', OrderStatus::RECEIVED->value);
        $this->assertEquals('assigned', OrderStatus::ASSIGNED->value);
        $this->assertEquals('in_progress', OrderStatus::IN_PROGRESS->value);
        $this->assertEquals('submitted', OrderStatus::SUBMITTED->value);
        $this->assertEquals('in_review', OrderStatus::IN_REVIEW->value);
        $this->assertEquals('approved', OrderStatus::APPROVED->value);
        $this->assertEquals('delivered', OrderStatus::DELIVERED->value);
        $this->assertEquals('closed', OrderStatus::CLOSED->value);
        $this->assertEquals('cancelled', OrderStatus::CANCELLED->value);
    }

    public function test_returns_correct_labels(): void
    {
        $this->assertEquals('Received', OrderStatus::RECEIVED->label());
        $this->assertEquals('Assigned', OrderStatus::ASSIGNED->label());
        $this->assertEquals('In Progress', OrderStatus::IN_PROGRESS->label());
        $this->assertEquals('Submitted', OrderStatus::SUBMITTED->label());
        $this->assertEquals('In Review', OrderStatus::IN_REVIEW->label());
        $this->assertEquals('Approved', OrderStatus::APPROVED->label());
        $this->assertEquals('Delivered', OrderStatus::DELIVERED->label());
        $this->assertEquals('Closed', OrderStatus::CLOSED->label());
        $this->assertEquals('Cancelled', OrderStatus::CANCELLED->label());
    }

    public function test_can_be_created_from_string(): void
    {
        $status = OrderStatus::from('delivered');
        $this->assertEquals(OrderStatus::DELIVERED, $status);
    }
}
