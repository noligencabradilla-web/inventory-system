<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Outbound;
use App\Models\Stock;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\UrgentOutboundRecipient;
use App\Models\ClientMember;
use Dompdf\Dompdf;
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
        if (!empty($search)) {
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
        $clients = \App\Models\User::where('role', 'client')->get();
        $members = ClientMember::with('client')->get();
        $offices = Outbound::whereNotNull('office')->where('office', '<>', '')->distinct()->orderBy('office')->pluck('office');

        return view('admin.outbound.index', compact('outbounds', 'clients', 'members', 'offices', 'dateFrom', 'dateTo', 'office', 'search'));
    }

    public function create()
    {
        $stocks = Stock::all();
        $clients = \App\Models\User::where('role', 'client')->get();
        return view('admin.outbound.create', compact('stocks', 'clients'));
    }

    public function searchRecipients(Request $request)
    {
        $term = $request->get('term', '');
        $results = [];

        if (strlen($term) >= 2) {
            // Search clients
            $clients = \App\Models\User::where('role', 'client')
                ->where(function($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                          ->orWhere('office', 'like', "%{$term}%");
                })
                ->get();

            foreach ($clients as $client) {
                $results[] = [
                    'id' => $client->id,
                    'name' => $client->name,
                    'office' => $client->office,
                    'type' => 'client'
                ];
            }

            // Search client members
            $members = ClientMember::with('client')
                ->where(function($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                          ->orWhere('email', 'like', "%{$term}%");
                })
                ->get();

            foreach ($members as $member) {
                $office = $member->client->office ?? 'non office member';
                $results[] = [
                    'id' => $member->id,
                    'client_id' => $member->client_id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'office' => $office,
                    'client_name' => $member->client->name,
                    'type' => 'member',
                    'has_office' => !empty($member->client->office)
                ];
            }

            // Search urgent recipients
            $urgentRecipients = UrgentOutboundRecipient::search($term)->get();

            foreach ($urgentRecipients as $urgent) {
                $results[] = [
                    'id' => $urgent->id,
                    'name' => $urgent->name,
                    'office' => $urgent->office,
                    'type' => 'urgent'
                ];
            }
        }

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $isUrgentOutbound = $request->input('is_urgent_outbound', 'false') === 'true';
        
        if ($isUrgentOutbound) {
            $request->validate([
                'stock_id'  => 'required|exists:stocks,id',
                'urgent_recipient_name' => 'required|string|max:255',
                'urgent_recipient_office' => 'nullable|string|max:255',
                'total'     => 'required|integer|min:1',
                'reason'    => 'nullable|string|max:1000',
            ]);
        } else {
            $request->validate([
                'stock_id'  => 'required|exists:stocks,id',
                'client_id' => 'required|exists:users,id',
                'office'    => 'required|string',
                'total'     => 'required|integer|min:1',
                'reason'    => 'nullable|string|max:1000',
            ]);
        }

        // Approval and status are set automatically when admin creates an outbound
        $data = $request->only('stock_id','total','reason');
        $data['approval'] = 'approved';
        $data['status'] = 'received';

        if ($isUrgentOutbound) {
            // Handle urgent recipient
            $urgentRecipientName = $request->input('urgent_recipient_name');
            $urgentRecipientOffice = $request->input('urgent_recipient_office', '');
            $urgentRecipientId = $request->input('urgent_recipient_id');

            if ($urgentRecipientId) {
                // Use existing urgent recipient
                $data['urgent_recipient_id'] = $urgentRecipientId;
                $data['urgent_recipient_name'] = $urgentRecipientName;
                $data['urgent_recipient_office'] = $urgentRecipientOffice;
            } else {
                // Create new urgent recipient
                $urgentRecipient = UrgentOutboundRecipient::create([
                    'name' => $urgentRecipientName,
                    'office' => $urgentRecipientOffice,
                    'reason' => 'Urgent outbound request',
                ]);
                
                $data['urgent_recipient_id'] = $urgentRecipient->id;
                $data['urgent_recipient_name'] = $urgentRecipientName;
                $data['urgent_recipient_office'] = $urgentRecipientOffice;
            }
            
            $data['is_urgent_outbound'] = true;
            $data['client_id'] = null;
            $data['office'] = $urgentRecipientOffice;
        } else {
            // Handle regular client or member
            $data['client_id'] = $request->input('client_id');
            $data['office'] = $request->input('office');
            $data['is_urgent_outbound'] = false;
            
            // Check if this is a member selection or direct client request
            $memberId = $request->input('member_id');
            if ($memberId) {
                $data['member_id'] = $memberId;
                $data['is_direct_request'] = true;
            } else {
                // This is a direct request from main client/office (no member selected)
                $data['is_direct_request'] = true;
            }
        }

        // If status is received on create, perform deduction and set deducted_at atomically
        if (($data['status'] ?? '') === 'received') {
            try {
                DB::transaction(function() use ($data) {
                    // lock stock
                    $stock = Stock::where('id', $data['stock_id'])->lockForUpdate()->firstOrFail();

                    if ($data['total'] > $stock->stock) {
                        throw new \Exception("Not enough stock to deduct. Available: {$stock->stock}, Outbound: {$data['total']}");
                    }

                    // create outbound with deducted_at
                    $out = Outbound::create($data + ['deducted_at' => now()]);

                    // decrement stock
                    $stock->decrement('stock', $data['total']);
                });
            } catch (\Throwable $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }

            return redirect()->route('outbound.index')->with('success', 'Outbound created and stock deducted.');
        }

        // default: create without deduction
        $out = Outbound::create($data + ['deducted_at' => null]);

        // If approval isn't approved and status isn't received, create a pending StockRequest
        if (($data['approval'] ?? '') !== 'approved' && ($data['status'] ?? '') !== 'received') {
            try {
                DB::transaction(function() use ($data, $out) {
                    $req = StockRequest::create([
                        'client_id' => $data['client_id'],
                        'office' => $data['office'] ?? '',
                        'status' => 'pending',
                    ]);

                    StockRequestItem::create([
                        'stock_request_id' => $req->id,
                        'stock_id' => $data['stock_id'],
                        'requested_qty' => $data['total'],
                        'approved_qty' => 0,
                    ]);
                });
            } catch (\Throwable $e) {
                // log but continue — outbound was created
                \Log::error('Failed to create stock request for outbound: '.$e->getMessage());
            }
        }

        return redirect()->route('outbound.index')->with('success', 'Outbound created.');
    }

    public function update(Request $request, Outbound $outbound)
    {
        $request->validate([
            'status' => 'required|in:on process,declined,received',
        ]);

        // ✅ If marking RECEIVED, deduct stock ONCE
        if ($request->status === 'received') {

            // already deducted before? do nothing
            if ($outbound->deducted_at) {
                $outbound->status = 'received';
                $outbound->save();

                return back()->with('success', 'Outbound updated (already deducted before).');
            }

            try {
                DB::transaction(function () use ($outbound) {

                    // lock outbound row
                    $ob = Outbound::where('id', $outbound->id)->lockForUpdate()->firstOrFail();

                    // lock stock row
                    $stock = Stock::where('id', $ob->stock_id)->lockForUpdate()->firstOrFail();

                    // safety check
                    if ($ob->total > $stock->stock) {
                        throw new \Exception("Not enough stock to deduct. Available: {$stock->stock}, Outbound: {$ob->total}");
                    }

                    // ✅ deduct stock
                    $stock->decrement('stock', $ob->total);

                    // ✅ mark deducted
                    $ob->deducted_at = now();
                    $ob->status = 'received';
                    $ob->save();
                });

            } catch (\Throwable $e) {
                return back()->with('error', $e->getMessage());
            }

            return back()->with('success', 'Outbound marked RECEIVED and stock deducted.');
        }

        // other statuses: just update (no deduction)
        $outbound->status = $request->status;
        $outbound->save();

        return back()->with('success', 'Outbound updated.');
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
        if (!empty($search)) {
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
        $pdf->set_option('enablePhp', true);
        $pdf->set_option('enableJavascript', true);
        $pdf->setPaper('a4', 'portrait');

        $html = view('admin.outbound-report-pdf', compact('outbounds', 'dateFrom', 'dateTo', 'summary'))->render();
        $pdf->set_option('chroot', base_path());
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="outbound-report.pdf"',
        ]);
    }
}
