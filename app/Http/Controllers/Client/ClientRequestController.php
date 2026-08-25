<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientMember;
use App\Models\Stock;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientRequestController extends Controller
{
    public function index()
    {
        $requests = StockRequest::with(['member', 'items.stock'])
            ->where('client_id', Auth::id())
            ->latest()
            ->get();

        return view('client.requests.index', compact('requests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'min:1'],
            'member_id' => ['nullable', 'integer', 'exists:client_members,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $client = Auth::user()->load('office');
        $office = $client->office?->office;

        if (! empty($data['member_id'])) {
            $memberBelongsToClient = ClientMember::where('id', $data['member_id'])
                ->where('client_id', $client->id)
                ->exists();

            if (! $memberBelongsToClient) {
                return back()
                    ->withInput()
                    ->with('error', 'The selected member is invalid for your account.');
            }
        }

        try {
            DB::transaction(function () use ($data, $client, $office) {
                $preparedItems = $this->prepareRequestItems($data['items']);

                if (empty($preparedItems)) {
                    throw new \RuntimeException('No valid items to request.');
                }

                $stockRequest = StockRequest::create([
                    'client_id' => $client->id,
                    'member_id' => $data['member_id'] ?? null,
                    'office' => $office,
                    'reason' => $data['reason'] ?? null,
                    'status' => 'pending',
                    'verification_code' => null,
                ]);

                foreach ($preparedItems as $item) {
                    StockRequestItem::create([
                        'stock_request_id' => $stockRequest->id,
                        'stock_id' => $item['stock_id'],
                        'requested_qty' => $item['qty'],
                        'approved_qty' => 0,
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('client.requests')
            ->with('success', 'Request submitted. Wait for admin approval.');
    }

    public function cancel($id)
    {
        $stockRequest = StockRequest::where('client_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (! $stockRequest) {
            return response()->json([
                'error' => 'Request not found.',
            ], 404);
        }

        if ($stockRequest->status !== 'pending') {
            return response()->json([
                'error' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $stockRequest->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'success' => 'Request cancelled successfully.',
        ]);
    }

    private function prepareRequestItems(array $items): array
    {
        $preparedItems = [];

        foreach ($items as $stockId => $quantity) {
            $stock = Stock::where('id', $stockId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                continue;
            }

            $availableStock = (int) $stock->stock;
            $requestedQuantity = max(1, (int) $quantity);
            $finalQuantity = min($requestedQuantity, $availableStock);

            if ($finalQuantity <= 0) {
                continue;
            }

            $preparedItems[] = [
                'stock_id' => $stock->id,
                'qty' => $finalQuantity,
            ];
        }

        return $preparedItems;
    }
}