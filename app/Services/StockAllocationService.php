<?php

namespace App\Services;

use App\Models\StockAllocation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class StockAllocationService
{
    public function createStockAllocation($inboundAllocationId, $allocation, $outstanding)
    {
        return StockAllocation::create([
            'inbound_allocation_id' => $inboundAllocationId,
            'allocation' => $allocation,
            'outstanding' => $outstanding,
        ]);
    }
    public function updateStockAllocation($inboundAllocationId, $outstanding)
    {
        return StockAllocation::updateOrCreate(
            ['inbound_allocation_id' => $inboundAllocationId],
            [
                // 'allocation' => $allocation,
                'outstanding' => $outstanding,
            ]
        );
    }
}
