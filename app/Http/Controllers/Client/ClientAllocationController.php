<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\StockAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientAllocationController extends Controller
{
    public function index(Request $request)
    {
        $client = $request->user();

        if (! $client->s_p_office_id) {
            return view('client.allocations.index', [
                'allocations' => collect(),
                'totalAllocated' => 0,
                'totalRequested' => 0,
                'totalRemaining' => 0,
                'officeName' => 'No assigned office',
            ]);
        }

        $allocations = StockAllocation::query()
            ->with(['stock.category'])
            ->where('office_id', $client->s_p_office_id)
            ->select(
                'stock_id',
                DB::raw('SUM(allocation) as total_allocated'),
                DB::raw('SUM(outstanding) as remaining_allocation'),
                DB::raw('SUM(allocation - outstanding) as requested_quantity')
            )
            ->groupBy('stock_id')
            ->orderByDesc('remaining_allocation')
            ->get();

        return view('client.allocations.index', [
            'allocations' => $allocations,
            'totalAllocated' => $allocations->sum('total_allocated'),
            'totalRequested' => $allocations->sum('requested_quantity'),
            'totalRemaining' => $allocations->sum('remaining_allocation'),
            'officeName' => $client->office?->office ?? 'No assigned office',
        ]);
    }
}