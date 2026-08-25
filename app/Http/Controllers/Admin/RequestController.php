<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockRequest;
use App\Models\Stock;
use App\Models\Outbound;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\StockAllocation;


class RequestController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));

        $requestsQuery = StockRequest::with(['client', 'member', 'items.stock']);

        if ($q !== '') {
            // allow searching by ref no (id) or client name
            $clean = ltrim($q, '#');

            $requestsQuery->where(function ($qr) use ($clean) {
                if (is_numeric($clean)) {
                    $qr->where('id', (int)$clean);
                }

                $qr->orWhereHas('client', function ($qc) use ($clean) {
                    $qc->where('name', 'like', "%{$clean}%");
                });
            });
        }

        $requests = $requestsQuery->latest()->get();

        // Prepare tabbed grouping like the blade previously did
        $activeTab = request('tab', 'pending');

        $pending   = $requests->where('status', 'pending');
        $approved  = $requests->where('status', 'approved');
        $ready     = $requests->where('status', 'ready_to_receive');
        $rejected  = $requests->where('status', 'rejected');

        $shown = match ($activeTab) {
            'approved' => $approved,
            'ready_to_receive' => $ready,
            'rejected' => $rejected,
            default => $pending,
        };

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.requests._list', compact('shown','activeTab'))->render(),
                'count' => $shown->count(),
            ]);
        }

        return view('admin.requests.index', compact('requests', 'pending', 'approved', 'ready', 'rejected', 'shown', 'activeTab'));
    }

    /**
     * ✅ THIS IS THE METHOD YOUR ROUTE CALLS:
     * PUT /admin/requests/{stockRequest}/decision
     *
     * Handles:
     * - Save Decision (approved_qty[])
     * - Reject Whole Request (status=rejected)
     * - Mark as Approved (status=approved)
     * - Ready to Receive (status=ready_to_receive) -> generates code
     */
    public function decision(Request $request, StockRequest $stockRequest)
    {
        // Only allow actions on pending/approved/ready_to_receive in your flow
        // (You can relax this if needed)
        if (!in_array($stockRequest->status, ['pending', 'approved', 'ready_to_receive'])) {
            return back()->with('error', 'This request can no longer be updated.');
        }

        // If button sent "status" -> handle whole-request actions
        $status = $request->input('status');

        // ✅ 1) Reject whole request
        if ($status === 'rejected') {
            DB::transaction(function () use ($stockRequest) {
                foreach ($stockRequest->items as $item) {
                    $item->approved_qty = 0;
                    $item->status = 'rejected';
                    $item->save();
                }
                $stockRequest->status = 'rejected';
                $stockRequest->save();
            });

            return back()->with('success', 'Request rejected (whole request). Client will see it under Rejected.');
        }

        // ✅ 2) Mark as Approved (ONLY if there is at least 1 approved item qty > 0)
        if ($status === 'approved') {
            $hasApproved = $stockRequest->items()->where('approved_qty', '>', 0)->exists();

            if (!$hasApproved) {
                return back()->with('error', 'You must approve at least 1 item quantity first.');
            }

            $stockRequest->status = 'approved';
            $stockRequest->save();

            return back()->with('success', 'Request marked as APPROVED.');
        }

        // ✅ 3) Ready to Receive (Generate Code) (ONLY if approved + has approved items)
        if ($status === 'ready_to_receive') {
            if ($stockRequest->status !== 'approved') {
                return back()->with('error', 'Only APPROVED requests can be set to READY TO RECEIVE.');
            }

            $hasApproved = $stockRequest->items()->where('approved_qty', '>', 0)->exists();
            if (!$hasApproved) {
                return back()->with('error', 'No approved items found. Approve some items first.');
            }

            if (!$stockRequest->verification_code) {
                $stockRequest->verification_code = strtoupper(Str::random(6));
            }

            $stockRequest->status = 'ready_to_receive';
            $stockRequest->save();

            return back()->with('success', 'READY TO RECEIVE. Verification code generated.');
        }

        // ✅ 4) Otherwise: "Save Decision" (approved_qty array)
        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'Only PENDING requests can be decided (edit approved quantities).');
        }

        $data = $request->validate([
            'approved_qty'      => ['required', 'array'],
            'approved_qty.*'     => ['nullable', 'integer', 'min:0'],
            'rejection_reason'   => ['nullable', 'array'],
            'rejection_reason.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $hasApproved = false;

        DB::transaction(function () use ($stockRequest, $data, &$hasApproved) {
            foreach ($stockRequest->items as $item) {
                $key = (string)$item->id;
                $approved = (int)($data['approved_qty'][$key] ?? 0);

                $requested = (int)$item->requested_qty;
                $available = (int)($item->stock?->stock ?? 0);

                // clamp:
                $approved = max(0, $approved);
                $approved = min($approved, $requested);
                $approved = min($approved, $available);

                $item->approved_qty = $approved;

                if ($approved > 0) {
                    $item->status = 'approved';
                    $item->rejection_reason = null;
                    $hasApproved = true;
                } else {
                    $item->status = 'rejected';
                    // Save rejection reason if provided
                    $item->rejection_reason = $data['rejection_reason'][$key] ?? null;
                }

                $item->save();
            }

            $stockRequest->status = $hasApproved ? 'approved' : 'rejected';
            $stockRequest->save();
        });

        return back()->with(
            'success',
            $hasApproved
                ? 'Decision saved. Request is APPROVED (partial approval possible).'
                : 'Decision saved. Request is REJECTED (no approved items).'
        );
    }

    /**
     * Release: verify code then create outbound rows.
     * Stock deduction happens when outbound is marked RECEIVED (your latest requirement).
     */

    public function release(Request $httpRequest, StockRequest $stockRequest)
    {
        $httpRequest->validate([
            'verification_code' => 'required|string',
            'received_by' => 'required|string|max:255'
        ]);

        if ($stockRequest->status !== 'ready_to_receive') {
            return back()->with('error', 'Only READY TO RECEIVE requests can be released.');
        }

        if (trim($httpRequest->verification_code) !== trim((string) $stockRequest->verification_code)) {
            return back()->with('error', 'Invalid verification code.');
        }

        try {
            DB::transaction(function () use ($stockRequest, $httpRequest) {
                $req = StockRequest::where('id', $stockRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($req->status === 'released') {
                    throw new \Exception('This request was already released.');
                }

                $req->load(['items.stock', 'client']);

                $officeId = $this->resolveOfficeIdForRequest($req);

                if (! $officeId) {
                    throw new \Exception('Cannot deduct allocation because this request has no office ID.');
                }

                $req->received_by = $httpRequest->received_by;

                foreach ($req->items as $item) {
                    $approved = (int) ($item->approved_qty ?? 0);

                    if ($approved <= 0) {
                        continue;
                    }

                    $stock = Stock::where('id', $item->stock_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($approved > (int) $stock->stock) {
                        throw new \Exception(
                            "Not enough stock for {$stock->description}. Available: {$stock->stock}, Approved: {$approved}"
                        );
                    }

                    // Deduct from office allocation first
                    $this->deductStockAllocation(
                        stockId: $stock->id,
                        officeId: $officeId,
                        quantity: $approved
                    );

                    Outbound::create([
                        'stock_id'     => $stock->id,
                        'client_id'    => $req->client_id,
                        'office'       => $req->office,
                        'office_id'    => $officeId,
                        'description'  => $stock->description,
                        'total'        => $approved,
                        'approval'     => 'approved',
                        'status'       => 'on process',
                        'deducted_at'  => now(),
                        'received_by'  => $httpRequest->received_by,
                    ]);

                    // Deduct from main stock
                    $stock->decrement('stock', $approved);

                    $item->status = 'released';
                    $item->save();
                }

                $req->status = 'released';
                $req->received_by = $httpRequest->received_by;
                $req->save();
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('outbound.index')
            ->with('success', 'Released successfully. Stocks and allocations were deducted.');
    }

    private function resolveOfficeIdForRequest(StockRequest $request): ?int
    {
        if (! empty($request->office_id)) {
            return (int) $request->office_id;
        }

        if (! empty($request->client?->s_p_office_id)) {
            return (int) $request->client->s_p_office_id;
        }

        return null;
    }

    private function deductStockAllocation(int $stockId, int $officeId, int $quantity): void
    {
        $remaining = $quantity;

        $allocations = StockAllocation::query()
            ->where('stock_id', $stockId)
            ->where('office_id', $officeId)
            ->where('outstanding', '>', 0)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min((int) $allocation->outstanding, $remaining);

            $allocation->decrement('outstanding', $deduct);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \Exception('Requested quantity exceeds the remaining allocation for this office.');
        }
    }

/**
 * Helper for schema column check (safe).
 * Put this at the bottom of the same controller (inside class).
 */
private function schema_has_column(string $table, string $column): bool
{
    try {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    } catch (\Throwable $e) {
        return false;
    }
}

}
