<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;

class ReassignOrderAction
{
    public function __construct(private readonly AssignOrderAction $assignOrderAction)
    {
    }

    public function execute(Order $order, User $newDesigner, User $reassignedBy): OrderAssignment
    {
        // AssignOrderAction handles ending previous assignments
        return $this->assignOrderAction->execute($order, $newDesigner, $reassignedBy);
    }
}
