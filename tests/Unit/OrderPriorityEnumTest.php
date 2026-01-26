<?php

namespace Tests\Unit;

use App\Enums\OrderPriority;
use PHPUnit\Framework\TestCase;

class OrderPriorityEnumTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertEquals('normal', OrderPriority::NORMAL->value);
        $this->assertEquals('rush', OrderPriority::RUSH->value);
    }

    public function test_returns_correct_labels(): void
    {
        $this->assertEquals('Normal', OrderPriority::NORMAL->label());
        $this->assertEquals('Rush', OrderPriority::RUSH->label());
    }

    public function test_can_be_created_from_string(): void
    {
        $priority = OrderPriority::from('rush');
        $this->assertEquals(OrderPriority::RUSH, $priority);
    }
}
