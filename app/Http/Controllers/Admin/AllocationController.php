<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAllocation;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    public function index(Request $request)
    {
        $allocationRows = StockAllocation::query()
            ->with(['stock.category', 'office', 'inbound'])
            ->latest()
            ->get();

        $allocations = $allocationRows
            ->groupBy(fn ($row) => $row->stock_id . '-' . $row->office_id)
            ->map(function ($rows) {
                $first = $rows->first();

                $totalAllocated = (int) $rows->sum('allocation');
                $totalRemaining = (int) $rows->sum('outstanding');
                $totalReleased = $totalAllocated - $totalRemaining;

                return (object) [
                    'stock' => $first->stock,
                    'office' => $first->office,
                    'total_allocated' => $totalAllocated,
                    'requested_quantity' => $totalReleased,
                    'remaining_allocation' => $totalRemaining,
                    'allocation_count' => $rows->count(),
                    'latest_allocation_date' => $rows->sortByDesc('created_at')->first()?->created_at,
                ];
            })
            ->sortByDesc('latest_allocation_date')
            ->values();

        $categories = $allocations
            ->map(fn ($allocation) => $allocation->stock?->category?->name ?? 'Uncategorized')
            ->unique()
            ->sort()
            ->values();

        $offices = $allocations
            ->map(fn ($allocation) => $allocation->office?->office ?? 'Unknown Office')
            ->unique()
            ->sort()
            ->values();

        return view('admin.allocations.index', [
            'allocations' => $allocations,
            'categories' => $categories,
            'offices' => $offices,
            'totalAllocated' => $allocations->sum('total_allocated'),
            'totalRequested' => $allocations->sum('requested_quantity'),
            'totalRemaining' => $allocations->sum('remaining_allocation'),
            'totalOffices' => $offices->count(),
            'totalItems' => $allocations
                ->map(fn ($allocation) => $allocation->stock?->id)
                ->filter()
                ->unique()
                ->count(),
        ]);
    }
}