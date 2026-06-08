<?php

namespace App\Services;

use App\Models\StockAllocation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class StockAllocationService
{
    public function createStockAllocation($payload)
    {
        return StockAllocation::create([
            "inbound_id" => $payload['inbound_id'],
            "stock_id" => $payload['stock_id'],
            "office_id" => $payload['office_id'],
            'allocation' => $payload['allocation'],
            'outstanding' => $payload['allocation'],
        ]);
    }
    public function updateStockAllocation($payload)
    {
        $allocationData = StockAllocation::whereStockId($payload['stock_id'])
            ->whereOfficeId($payload['office_id'])
            ->whereNotNull('outstanding')
            ->first();
        $allocationData->decrement('outstanding', $payload['outstanding']);
        // return StockAllocation::updateOrCreate(
        //     ['id' => $payload['id']],
        //     [
        //         'outstanding' => $payload['outstanding'],
        //     ]
        // );
    }
}
