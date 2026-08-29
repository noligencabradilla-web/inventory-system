<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientMember;
use App\Models\Outbound;
use App\Models\Stock;
use App\Models\StockAllocation;
use App\Models\UrgentOutboundRecipient;
use App\Models\User;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutboundController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $office = $request->input('office');
        $search = $request->input('search');

        $query = Outbound::with(['stock', 'client', 'member'])->latest();

        if ($dateFrom) {
            $query->whereRaw("DATE(COALESCE(deducted_at, created_at)) >= ?", [$dateFrom]);
        }

        if ($dateTo) {
            $query->whereRaw("DATE(COALESCE(deducted_at, created_at)) <= ?", [$dateTo]);
        }

        if ($office && $office !== 'all') {
            $query->where('office', $office);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                })
                ->orWhere('office', 'like', "%{$search}%")
                ->orWhereHas('stock', function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('id_no', 'like', "%{$search}%");
                })
                ->orWhereHas('member', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
            });
        }

        $outbounds = $query->get();

        $clients = User::join('s_p_offices', 's_p_offices.id', '=', 'users.s_p_office_id')
            ->with('clientMembers')
            ->where('role', 'client')
            ->select(
                'users.id',
                'users.name',
                's_p_offices.id as office_id',
                's_p_offices.office'
            )
            ->get();

        $members = ClientMember::with('client.office')->get();

        $offices = Outbound::whereNotNull('office')
            ->where('office', '<>', '')
            ->distinct()
            ->orderBy('office')
            ->pluck('office');

        return view('admin.outbound.index', compact(
            'outbounds',
            'clients',
            'members',
            'offices',
            'dateFrom',
            'dateTo',
            'office',
            'search'
        ));
    }

    public function create()
    {
        $stocks = Stock::all();

        $clients = User::join('s_p_offices', 's_p_offices.id', '=', 'users.s_p_office_id')
            ->with('clientMembers')
            ->where('role', 'client')
            ->select(
                'users.id',
                'users.name',
                's_p_offices.id as office_id',
                's_p_offices.office'
            )
            ->get();

        $members = ClientMember::with('client.office')->get();

        return view('admin.outbound.create', compact('stocks', 'clients', 'members'));
    }

    public function searchRecipients(Request $request)
    {
        $term = trim($request->get('term', ''));

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $results = [];

        $clients = User::with('office')
            ->where('role', 'client')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhereHas('office', function ($officeQuery) use ($term) {
                        $officeQuery->where('office', 'like', "%{$term}%");
                    });
            })
            ->get();

        foreach ($clients as $client) {
            $results[] = [
                'id' => $client->id,
                'name' => $client->name,
                'office_id' => $client->s_p_office_id,
                'office' => $client->office?->office ?? 'No assigned office',
                'type' => 'client',
            ];
        }

        $members = ClientMember::with('client.office')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->get();

        foreach ($members as $member) {
            $client = $member->client;

            $results[] = [
                'id' => $member->id,
                'client_id' => $member->client_id,
                'name' => $member->name,
                'email' => $member->email,
                'office_id' => $client?->s_p_office_id,
                'office' => $client?->office?->office ?? 'No assigned office',
                'client_name' => $client?->name ?? 'Unknown client',
                'type' => 'member',
            ];
        }

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $isUrgentOutbound = $request->input('is_urgent_outbound', 'false') === 'true';

        if ($isUrgentOutbound) {
            $validated = $request->validate([
                'stock_id' => ['required', 'exists:stocks,id'],
                'urgent_recipient_name' => ['required', 'string', 'max:255'],
                'urgent_recipient_office' => ['nullable', 'string', 'max:255'],
                'total' => ['required', 'integer', 'min:1'],
                'reason' => ['nullable', 'string', 'max:1000'],
            ]);

            try {
                DB::transaction(function () use ($validated) {
                    $stock = Stock::where('id', $validated['stock_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((int) $validated['total'] > (int) $stock->stock) {
                        throw new \Exception("Not enough stock to deduct. Available: {$stock->stock}, Outbound: {$validated['total']}");
                    }

                    $urgentRecipient = UrgentOutboundRecipient::create([
                        'name' => $validated['urgent_recipient_name'],
                        'office' => $validated['urgent_recipient_office'] ?? 'Non-member',
                        'reason' => $validated['reason'] ?? 'Urgent outbound request',
                    ]);

                    Outbound::create([
                        'stock_id' => $validated['stock_id'],
                        'client_id' => null,
                        'member_id' => null,
                        'office' => $validated['urgent_recipient_office'] ?? 'Non-member',
                        'office_id' => null,
                        'description' => $stock->description,
                        'total' => $validated['total'],
                        'reason' => $validated['reason'] ?? null,
                        'approval' => 'approved',
                        'status' => 'received',
                        'is_direct_request' => false,
                        'is_urgent_outbound' => true,
                        'urgent_recipient_id' => $urgentRecipient->id,
                        'urgent_recipient_name' => $validated['urgent_recipient_name'],
                        'urgent_recipient_office' => $validated['urgent_recipient_office'] ?? 'Non-member',
                        'deducted_at' => now(),
                    ]);

                    $stock->decrement('stock', $validated['total']);
                });
            } catch (\Throwable $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()
                ->route('outbound.index')
                ->with('success', 'Non-member outbound created and stock deducted.');
        }

        $validated = $request->validate([
            'stock_id' => ['required', 'exists:stocks,id'],
            'client_id' => ['required', 'exists:users,id'],
            'office' => ['required', 'string'],
            'office_id' => ['required', 'integer', 'exists:s_p_offices,id'],
            'member_id' => ['nullable', 'integer', 'exists:client_members,id'],
            'total' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $data = [
            'stock_id' => $validated['stock_id'],
            'client_id' => $validated['client_id'],
            'member_id' => $validated['member_id'] ?? null,
            'office' => $validated['office'],
            'office_id' => $validated['office_id'],
            'total' => $validated['total'],
            'reason' => $validated['reason'] ?? null,
            'approval' => 'approved',
            'status' => 'received',
            'is_direct_request' => true,
            'is_urgent_outbound' => false,
        ];

        try {
            DB::transaction(function () use ($data) {
                $stock = Stock::where('id', $data['stock_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $data['total'] > (int) $stock->stock) {
                    throw new \Exception("Not enough stock to deduct. Available: {$stock->stock}, Outbound: {$data['total']}");
                }

                $this->deductStockAllocation(
                    stockId: (int) $data['stock_id'],
                    officeId: (int) $data['office_id'],
                    quantity: (int) $data['total']
                );

                Outbound::create($data + [
                    'description' => $stock->description,
                    'deducted_at' => now(),
                ]);

                $stock->decrement('stock', $data['total']);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('outbound.index')
            ->with('success', 'Outbound created and stock deducted.');
    }

    public function update(Request $request, Outbound $outbound)
    {
        $request->validate([
            'status' => 'required|in:on process,declined,received',
        ]);

        if ($request->status === 'received') {
            if ($outbound->deducted_at) {
                $outbound->status = 'received';
                $outbound->save();

                return back()->with('success', 'Outbound updated. Stock was already deducted before.');
            }

            try {
                DB::transaction(function () use ($outbound) {
                    $ob = Outbound::where('id', $outbound->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $stock = Stock::where('id', $ob->stock_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((int) $ob->total > (int) $stock->stock) {
                        throw new \Exception("Not enough stock to deduct. Available: {$stock->stock}, Outbound: {$ob->total}");
                    }

                    if (! empty($ob->office_id)) {
                        $this->deductStockAllocation(
                            stockId: (int) $ob->stock_id,
                            officeId: (int) $ob->office_id,
                            quantity: (int) $ob->total
                        );
                    }

                    $stock->decrement('stock', $ob->total);

                    $ob->deducted_at = now();
                    $ob->status = 'received';
                    $ob->save();
                });
            } catch (\Throwable $e) {
                return back()->with('error', $e->getMessage());
            }

            return back()->with('success', 'Outbound marked RECEIVED. Stock and allocation were deducted.');
        }

        $outbound->status = $request->status;
        $outbound->save();

        return back()->with('success', 'Outbound updated.');
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

    public function generateReportPdf(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $office = $request->input('office');
        $search = $request->input('search');

        $query = Outbound::with(['stock', 'client', 'member'])->latest();

        if ($dateFrom) {
            $query->whereRaw("DATE(COALESCE(deducted_at, created_at)) >= ?", [$dateFrom]);
        }

        if ($dateTo) {
            $query->whereRaw("DATE(COALESCE(deducted_at, created_at)) <= ?", [$dateTo]);
        }

        if ($office && $office !== 'all') {
            $query->where('office', $office);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                })
                ->orWhere('office', 'like', "%{$search}%")
                ->orWhereHas('stock', function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('id_no', 'like', "%{$search}%");
                })
                ->orWhereHas('member', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
            });
        }

        $outbounds = $query->get();

        $summary = [
            'records' => $outbounds->count(),
            'total_quantity' => $outbounds->sum('total'),
        ];

        $pdf = new Dompdf();
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isFontSubsettingEnabled', true);
        $pdf->setPaper('a4', 'portrait');

        $html = view('admin.outbound-report-pdf', compact(
            'outbounds',
            'dateFrom',
            'dateTo',
            'summary'
        ))->render();

        $pdf->set_option('chroot', base_path());
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="outbound-report.pdf"',
        ]);
    }
}