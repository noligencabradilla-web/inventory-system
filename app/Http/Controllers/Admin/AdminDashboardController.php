<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\Outbound;
use App\Models\Inbound;
use App\Models\ClientDirectDeduction;
use App\Models\PasswordResetRequest;
use App\Models\Category;
use App\Models\Stock;
use App\Models\User;
use App\Models\ClientMember;

use illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Dompdf\Dompdf;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $pendingRequests = StockRequest::where('status', 'pending')->count();
        $pendingPasswordResets = PasswordResetRequest::where('status', 'pending')->count();

        // Gather category analytics
        $categories = Category::all();
        $categoryAnalytics = [];

        foreach ($categories as $category) {
            // Total stock availability for this category
            $totalAvailability = Stock::where('category_id', $category->id)
                ->sum('stock');

            // Total approved items from outbound records for this category
            $totalRequested = DB::table('outbounds')
                ->join('stocks', 'outbounds.stock_id', '=', 'stocks.id')
                ->where('stocks.category_id', $category->id)
                ->where('outbounds.approval', 'approved')
                ->sum('outbounds.total') ?? 0;

            $categoryAnalytics[] = [
                'name' => $category->name,
                'availability' => $totalAvailability ?? 0,
                'requested' => $totalRequested ?? 0,
            ];
        }

        // --- Office analytics: count requests per office ---
        $officeCounts = StockRequest::select('office', DB::raw('COUNT(*) as total'))
            ->groupBy('office')
            ->orderByDesc('total')
            ->get();

        // prepare for charting (labels + values)
        $officeAnalytics = $officeCounts->map(function($r){
            return [ 'office' => $r->office ?? 'Unknown', 'count' => (int) $r->total ];
        })->values();

        // --- Item analytics: most requested items ---
        $itemCounts = DB::table('stock_request_items')
            ->join('stocks', 'stock_request_items.stock_id', '=', 'stocks.id')
            ->join('categories', 'stocks.category_id', '=', 'categories.id')
            ->select(
                'stocks.id_no',
                'stocks.description',
                'categories.name as category_name',
                'stocks.unit',
                DB::raw('SUM(stock_request_items.approved_qty) as total_requested')
            )
            ->where('stock_request_items.approved_qty', '>', 0)
            ->groupBy('stocks.id', 'stocks.description', 'categories.name', 'stocks.unit')
            ->orderByDesc('total_requested')
            ->limit(10)
            ->get();

        $itemAnalytics = $itemCounts->map(function($item) {
            return [
                'id_no' => $item->id_no,
                'description' => $item->description,
                'category' => $item->category_name,
                'unit' => $item->unit,
                'total_requested' => (int) $item->total_requested
            ];
        })->values();

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $lowStockAnalytics = $this->lowStockItems();
        $outStockAnalytics = $this->outOfStockItems();
        $monthlyConsumptionAnalytics = $this->monthlyConsumptionTrend($start, $end);
        return view(
            'admin.dashboard', 
            compact(
                'pendingRequests',
                'pendingPasswordResets',
                'categoryAnalytics',
                'officeAnalytics',
                'itemAnalytics',
                'lowStockAnalytics',
                'outStockAnalytics',
                'monthlyConsumptionAnalytics',
                )
        );
    }

    /**
     * Summary (transaction list): show every request with details.
     */
    public function summary(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $office = trim((string)$request->query('office', ''));
        $dateFrom = trim((string)$request->query('date_from', ''));
        $dateTo = trim((string)$request->query('date_to', ''));
        $type = trim((string)$request->query('type', 'all'));

        // Initialize empty collections
        $requests = collect();
        $urgentOutbounds = collect();
        $directRequests = collect();
        $inbounds = collect();

        // Filter based on transaction type
        if ($type === 'all' || $type === 'request') {
            $requestsQuery = StockRequest::with(['client', 'items.stock']);

            if ($q !== '') {
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

            if ($office !== '') {
                $requestsQuery->where('office', $office);
            }

            $requests = $requestsQuery->latest()->get();
        }

        if ($type === 'all' || $type === 'urgent') {
            $urgentOutbounds = Outbound::with(['stock', 'urgentRecipient'])
                ->where('is_urgent_outbound', true)
                ->latest()
                ->get();
        }

        if ($type === 'all' || $type === 'direct') {
            $directRequests = Outbound::with(['stock', 'member', 'client'])
                ->where('is_direct_request', true)
                ->latest()
                ->get();
        }

        if ($type === 'all' || $type === 'inbound') {
            $inboundsQuery = Inbound::with(['stock.category']);

            if ($q !== '') {
                $clean = ltrim($q, '#');
                $inboundsQuery->where(function ($qr) use ($clean) {
                    if (is_numeric($clean)) {
                        $qr->where('id', (int)$clean);
                    }
                    $qr->orWhereHas('stock', function ($qs) use ($clean) {
                        $qs->where('id_no', 'like', "%{$clean}%")
                           ->orWhere('description', 'like', "%{$clean}%");
                    });
                });
            }

            $inbounds = $inboundsQuery->latest()->get();
            
            // Group inbound records by date to identify potential import batches
            $groupedInbounds = [];
            $manualInbounds = collect();
            
            foreach ($inbounds as $inbound) {
                $dateKey = $inbound->created_at->format('Y-m-d');
                $timeKey = $inbound->created_at->format('H:i');
                
                // Check if multiple records were created within the same minute (likely import)
                $sameMinuteRecords = $inbounds->filter(function ($record) use ($inbound) {
                    return $record->id !== $inbound->id && 
                           $record->created_at->format('Y-m-d H:i') === $inbound->created_at->format('Y-m-d H:i');
                });
                
                if ($sameMinuteRecords->count() > 0) {
                    // This is likely an import batch
                    $batchKey = $inbound->created_at->format('Y-m-d H:i');
                    if (!isset($groupedInbounds[$batchKey])) {
                        $groupedInbounds[$batchKey] = collect();
                    }
                    $groupedInbounds[$batchKey]->push($inbound);
                } else {
                    // This is likely a manual entry
                    $manualInbounds->push($inbound);
                }
            }
            
            // Convert grouped collections to regular collections
            $groupedInbounds = collect($groupedInbounds)->map(function ($group) {
                return $group->sortBy('id');
            });
        }

        $offices = StockRequest::select('office')
            ->distinct()
            ->orderBy('office')
            ->pluck('office')
            ->filter();

        $reportData = $this->prepareSummaryReportData($q, $office, $dateFrom, $dateTo);

        return view('admin.summary', array_merge([
            'requests' => $requests,
            'urgentOutbounds' => $urgentOutbounds,
            'directRequests' => $directRequests,
            'inbounds' => $inbounds,
            'groupedInbounds' => $groupedInbounds ?? collect(),
            'manualInbounds' => $manualInbounds ?? collect(),
            'offices' => $offices,
            'q' => $q,
            'office' => $office,
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $reportData));
    }

    private function prepareSummaryReportData(string $q, string $office, ?string $dateFrom, ?string $dateTo): array
    {
        $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $dateTo ? Carbon::parse($dateTo)->endOfDay() : Carbon::now()->endOfMonth();

        $stockQuery = Stock::query();
        if ($q !== '') {
            $stockQuery->where(function ($query) use ($q) {
                $query->where('id_no', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $stocks = $stockQuery->orderBy('description')->get();

        $inboundTotals = Inbound::whereBetween('created_at', [$start, $end])
            ->select('stock_id', DB::raw('SUM(total) as total'))
            ->groupBy('stock_id')
            ->pluck('total', 'stock_id');

        $outboundTotals = Outbound::whereNotNull('deducted_at')
            ->whereBetween('deducted_at', [$start, $end])
            ->when($office !== '', fn($query) => $query->where('office', $office))
            ->select('stock_id', DB::raw('SUM(total) as total'))
            ->groupBy('stock_id')
            ->pluck('total', 'stock_id');

        $futureInboundTotals = Inbound::where('created_at', '>', $end)
            ->select('stock_id', DB::raw('SUM(total) as total'))
            ->groupBy('stock_id')
            ->pluck('total', 'stock_id');

        $futureOutboundTotals = Outbound::whereNotNull('deducted_at')
            ->where('deducted_at', '>', $end)
            ->when($office !== '', fn($query) => $query->where('office', $office))
            ->select('stock_id', DB::raw('SUM(total) as total'))
            ->groupBy('stock_id')
            ->pluck('total', 'stock_id');

        $stockSummaries = $stocks->map(function ($stock) use ($inboundTotals, $outboundTotals, $futureInboundTotals, $futureOutboundTotals) {
            $currentInbound = (int) ($inboundTotals[$stock->id] ?? 0);
            $currentOutbound = (int) ($outboundTotals[$stock->id] ?? 0);
            $futureInbound = (int) ($futureInboundTotals[$stock->id] ?? 0);
            $futureOutbound = (int) ($futureOutboundTotals[$stock->id] ?? 0);

            $currentStock = (int) $stock->stock;
            $endingBalance = $currentStock - $futureInbound + $futureOutbound;
            $startingBalance = $endingBalance - $currentInbound + $currentOutbound;
            $sum = $startingBalance + $currentInbound;

            return [
                'item' => $stock->description,
                'id_no' => $stock->id_no,
                'starting_balance' => $startingBalance,
                'inbound' => $currentInbound,
                'sum' => $sum,
                'outbound' => $currentOutbound,
                'ending_balance' => $endingBalance,
                'unit' => $stock->unit,
            ];
        });

        return compact('stocks', 'stockSummaries', 'dateFrom', 'dateTo', 'start', 'end');
    }

    public function generateSummaryReportPdf(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $office = trim((string)$request->query('office', ''));
        $dateFrom = trim((string)$request->query('date_from', ''));
        $dateTo = trim((string)$request->query('date_to', ''));

        $reportData = $this->prepareSummaryReportData($q, $office, $dateFrom, $dateTo);
        $reportData['office'] = $office;

        $pdf = new Dompdf();
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isFontSubsettingEnabled', true);
        $pdf->set_option('enablePhp', true);
        $pdf->set_option('enableJavascript', true);
        $pdf->setPaper('a4', 'portrait');

        $html = view('admin.summary-report-pdf', $reportData)->render();
        $pdf->set_option('chroot', base_path());
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="admin-summary-report.pdf"',
        ]);
    }

    

    /**
     * Return analytics data filtered by date range.
     * Used by AJAX requests from the admin dashboard modal.
     */
    public function chartData(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate || $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        }else{
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        }

        return response()->json([
            'categories' => $this->categoryAnalytics($start, $end),
            'offices' => $this->officeRequestAnalytics($start, $end),
            'items' => $this->mostRequestedItems($start, $end),

            // Additional analytics
            'lowStock' => $this->lowStockItems(),
            'outStock' => $this->outOfStockItems(),
            'requestStatus' => $this->requestStatusOverview($start, $end),
            'monthlyConsumption' => $this->monthlyConsumptionTrend($start, $end),
        ]);
    }

    private function emptyPayload(): array
    {
        return [
            'categories' => [],
            'offices' => [],
            'items' => [],
            'lowStock' => [],
            'outStock' => [],
            'requestStatus' => [],
            'monthlyConsumption' => [],
            'stockMovement' => [],
            'fastMoving' => [],
            'officeQuantity' => [],
            'pendingAging' => [],
        ];
    }

    private function categoryAnalytics(Carbon $start, Carbon $end): array
    {
        return Category::query()
            ->orderBy('name')
            ->get()
            ->map(function ($category) use ($start, $end) {
                $totalAvailability = Inbound::query()
                    ->join('stocks', 'inbounds.stock_id', '=', 'stocks.id')
                    ->where('stocks.category_id', $category->id)
                    ->whereBetween('inbounds.created_at', [$start, $end])
                    ->sum('inbounds.total');

                $totalRequested = Outbound::query()
                    ->join('stocks', 'outbounds.stock_id', '=', 'stocks.id')
                    ->where('stocks.category_id', $category->id)
                    ->where('outbounds.approval', 'approved')
                    ->whereBetween('outbounds.created_at', [$start, $end])
                    ->sum('outbounds.total');

                return [
                    'name' => $category->name,
                    'availability' => (int) $totalAvailability,
                    'requested' => (int) $totalRequested,
                ];
            })
            ->values()
            ->all();
    }

    private function officeRequestAnalytics(Carbon $start, Carbon $end): array
    {
        return StockRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->select('office', DB::raw('COUNT(*) as total'))
            ->groupBy('office')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'office' => $row->office ?: 'Unknown',
                'count' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * Most requested = number of approved/requested quantity from request items.
     * This follows your existing dashboard logic.
     */
    private function mostRequestedItems(Carbon $start, Carbon $end): array
    {
        return DB::table('stock_request_items')
            ->join('stock_requests', 'stock_request_items.stock_request_id', '=', 'stock_requests.id')
            ->join('stocks', 'stock_request_items.stock_id', '=', 'stocks.id')
            ->join('categories', 'stocks.category_id', '=', 'categories.id')
            ->whereBetween('stock_requests.created_at', [$start, $end])
            ->where('stock_request_items.approved_qty', '>', 0)
            ->select(
                'stocks.id',
                'stocks.id_no',
                'stocks.description',
                'categories.name as category_name',
                'stocks.unit',
                DB::raw('SUM(stock_request_items.approved_qty) as total_requested')
            )
            ->groupBy(
                'stocks.id',
                'stocks.id_no',
                'stocks.description',
                'categories.name',
                'stocks.unit'
            )
            ->orderByDesc('total_requested')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'id_no' => $item->id_no,
                'description' => $item->description,
                'category' => $item->category_name,
                'unit' => $item->unit,
                'total_requested' => (int) $item->total_requested,
            ])
            ->values()
            ->all();
    }

    /**
     * Uses your existing notification threshold: stock > 0 and stock <= 49.
     */
    private function lowStockItems(): array
    {
        $lowThreshold = 49;

         return Stock::query()
            ->leftJoin('categories', 'stocks.category_id', '=', 'categories.id')
            ->where('stocks.stock', '>', 0)
            ->where('stocks.stock', '<=', $lowThreshold)
            ->select(
                DB::raw("COALESCE(categories.name, 'Uncategorized') as category"),
                DB::raw('COUNT(stocks.id) as total')
            )
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($lowThreshold) {
                return [
                    'category' => $row->category,
                    'total' => (int) $row->total,
                    'threshold' => $lowThreshold,
                ];
            })
            ->values()
            ->all();
    }

    private function outOfStockItems(): array
    {
        return Stock::query()
        ->with('category')
        ->where('stock', '<=', 0)
        ->orderBy('description')
        ->get()
        ->groupBy(function ($stock) {
            return optional($stock->category)->name ?? 'Uncategorized';
        })
        ->map(function ($stocks, $categoryName) {
            return [
                'category' => $categoryName,
                'total' => $stocks->count(),
                'items' => $stocks->map(function ($stock) {
                    return [
                        'id_no' => $stock->id_no,
                        'description' => $stock->description,
                        'unit' => $stock->unit,
                        'stock' => (int) $stock->stock,
                    ];
                })->values(),
            ];
        })
        ->sortByDesc('total')
        ->values()
        ->all();
    }

    /**
     * Consumption uses deducted_at because your summary report already treats deducted_at
     * as the actual stock-out date.
     */
    private function monthlyConsumptionTrend(Carbon $start, Carbon $end): array
    {
        return Outbound::query()
            ->whereNotNull('deducted_at')
            ->whereBetween('deducted_at', [$start, $end])
            ->select(
                DB::raw("DATE_FORMAT(deducted_at, '%Y-%m') as month_key"),
                DB::raw("DATE_FORMAT(deducted_at, '%b %Y') as label"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('month_key', 'label')
            ->orderBy('month_key')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    public function notifications()
    {
        $user = auth()->user();
        
        // Get admin-specific notifications
        $notifications = $this->getAdminNotifications($user);
        
        return view('admin.notifications', compact('notifications'));
    }


    public function counts()
    {
        $pendingRequests = StockRequest::where('status', 'pending')->count();
        $pendingPasswordResets = PasswordResetRequest::where('status', 'pending')->count();
        $lowThreshold = 49;
        $lowStock = Stock::where('stock','>',0)->where('stock','<=',$lowThreshold)->count();
        $outStock = Stock::where('stock','<=',0)->count();
        
        // New: Urgent outbound notifications
        $urgentOutbounds = Outbound::where('is_urgent_outbound', true)
            ->where('approval', 'pending')
            ->count();
        
        // New: Expiring items (items with expiry date within 7 days)
        $expiringItems = 0;
        if (Schema::hasColumn('stocks', 'expiry_date')) {
            $sevenDaysFromNow = Carbon::now()->addDays(7);
            $expiringItems = Stock::where('expiry_date', '<=', $sevenDaysFromNow)
                ->where('expiry_date', '>', Carbon::now())
                ->where('stock', '>', 0)
                ->count();
        }
        
        // New: Recent client activity (new registrations in last 24 hours)
        $recentClients = User::where('role', 'client')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();
        
        // New: System health alerts (failed jobs count)
        $failedJobs = 0;
        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            // Table might not exist, ignore
        }

        $total = $pendingRequests + $pendingPasswordResets + $lowStock + $outStock + $urgentOutbounds + $expiringItems + $recentClients + $failedJobs;

        return response()->json([
            'pendingRequests' => $pendingRequests,
            'pendingPasswordResets' => $pendingPasswordResets,
            'lowStock' => $lowStock,
            'outStock' => $outStock,
            'urgentOutbounds' => $urgentOutbounds,
            'expiringItems' => $expiringItems,
            'recentClients' => $recentClients,
            'failedJobs' => $failedJobs,
            'total' => $total,
        ]);
    }

    /**
     * Client monitoring page (inventory + members combined)
     */
    public function clientMonitoring()
    {
        // Get all clients with their inventory items and members
        $clients = User::where('role', 'client')->get();
        
        $clientsWithFullData = $clients->map(function($client) {
            // Get approved inventory items for this client (matching client inventory logic)
            $approvedInventory = StockRequestItem::with(['stock'])
                ->whereHas('request', function($query) use ($client) {
                    $query->where('client_id', $client->id)
                          ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
                })
                ->where('approved_qty', '>', 0)
                ->get();

            // Get direct requests for this client
            $directRequests = Outbound::with(['stock'])
                ->where('client_id', $client->id)
                ->where('is_direct_request', true)
                ->where('approval', 'approved')
                ->whereIn('status', ['on process', 'received'])
                ->get();

            // Calculate inventory directly without creating temporary records
            // First, add regular inventory items
            $stockInventoryMap = [];
            
            foreach ($approvedInventory as $item) {
                $stockId = $item->stock->id;
                $myInventory = max(0, ($item->approved_qty ?? 0) - ($item->distributed_qty ?? 0));
                
                if (!isset($stockInventoryMap[$stockId])) {
                    $stockInventoryMap[$stockId] = (object)[
                        'id' => $item->id,
                        'stock' => $item->stock,
                        'approved_qty' => 0,
                        'distributed_qty' => 0,
                        'my_inventory' => 0,
                        'type' => 'inventory'
                    ];
                }
                
                // Add to existing or new item
                $stockInventoryMap[$stockId]->approved_qty += $item->approved_qty;
                $stockInventoryMap[$stockId]->distributed_qty += $item->distributed_qty ?? 0;
                $stockInventoryMap[$stockId]->my_inventory += $myInventory;
            }
            
            // Then, add direct request quantities to existing items or create new entries
            foreach ($directRequests as $directRequest) {
                $stockId = $directRequest->stock->id;
                
                // Calculate how much has been deducted from this direct request
                $deductedFromDirect = ClientDirectDeduction::where('stock_request_item_id', null)
                    ->whereHas('member', function($query) use ($client) {
                        $query->where('client_id', $client->id);
                    })
                    ->where('created_at', '>=', $directRequest->created_at)
                    ->sum('deducted_qty');
                
                $availableFromDirect = max(0, $directRequest->total - $deductedFromDirect);
                
                if (!isset($stockInventoryMap[$stockId])) {
                    $stockInventoryMap[$stockId] = (object)[
                        'id' => 'direct_' . $directRequest->id,
                        'stock' => $directRequest->stock,
                        'approved_qty' => 0,
                        'distributed_qty' => 0,
                        'my_inventory' => 0,
                        'type' => 'inventory'
                    ];
                }
                
                // Add to existing or new item
                $stockInventoryMap[$stockId]->approved_qty += $directRequest->total;
                $stockInventoryMap[$stockId]->distributed_qty += $deductedFromDirect;
                $stockInventoryMap[$stockId]->my_inventory += $availableFromDirect;
            }
            
            // Convert back to collection
            $inventoryItems = collect(array_values($stockInventoryMap));

            // Get members and their distributions including direct request items
            $members = ClientMember::where('client_id', $client->id)
                ->with(['distributions.stockRequestItem.stock', 'directDeductions'])
                ->get()
                ->map(function($member) {
                    // Regular distributions
                    $distributedQty = $member->distributions->sum('distributed_qty');
                    $usedQty = \Illuminate\Support\Facades\Schema::hasColumn('client_member_distributions', 'used_qty') 
                        ? $member->distributions->sum('used_qty') 
                        : 0;
                    
                    // Direct request items (original ones only, not usage records)
                    $directDeductions = $member->directDeductions->filter(function ($deduction) {
                        return $deduction->stock_request_item_id === null && !str_contains($deduction->reason ?? '', 'Used from direct request');
                    });
                    $directDistributedQty = $directDeductions->sum('deducted_qty');
                    
                    // Usage from direct request items
                    $directUsedQty = $member->directDeductions->filter(function ($deduction) {
                        return str_contains($deduction->reason ?? '', 'Used from direct request');
                    })->sum('deducted_qty');
                    
                    // Combined totals
                    $totalDistributedQty = $distributedQty + $directDistributedQty;
                    $totalUsedQty = $usedQty + $directUsedQty;
                    $availableQty = $totalDistributedQty - $totalUsedQty;
                    $usedValue = $totalUsedQty;

                    return (object)[
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'distributed_items' => $totalDistributedQty,
                        'available_items' => max(0, $availableQty),
                        'used_items' => $totalUsedQty,
                        'used_value' => $usedValue
                    ];
                });

            $totalAvailableInventory = $inventoryItems->sum('my_inventory');

            return (object)[
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'office' => $client->office,
                'inventory_items' => $inventoryItems,
                'inventory_items_count' => $inventoryItems->count(),
                'members' => $members,
                'members_count' => $members->count(),
                'total_distributed_items' => $members->sum('distributed_items'),
                'total_available_inventory' => $totalAvailableInventory
            ];
        })->filter(function($client) {
            return $client->inventory_items_count > 0 || $client->members_count > 0;
        });

        // Calculate statistics
        $totalClients = $clientsWithFullData->count();
        $totalInventoryItems = $clientsWithFullData->sum('inventory_items_count');
        $totalMembers = $clientsWithFullData->sum('members_count');
        $lowStockClients = $clientsWithFullData->filter(function($client) {
            return $client->inventory_items->contains(function($item) {
                return ($item->my_inventory ?? 0) <= 5;
            });
        })->count();

        return view('admin.client-monitoring', compact(
            'clientsWithFullData',
            'totalClients',
            'totalInventoryItems',
            'totalMembers',
            'lowStockClients'
        ));
    }

    
    /**
     * Get admin notifications based on system data
     */
    private function getAdminNotifications($user)
    {
        $notifications = collect();
        
        // Get read notifications from session
        $readKey = 'admin_read_notifications_' . $user->id;
        $currentRead = session($readKey, []);

        // Admin-specific notifications
        $notifications = $notifications->merge($this->getPendingRequestNotifications($currentRead));
        $notifications = $notifications->merge($this->getPasswordResetNotifications($currentRead));
        $notifications = $notifications->merge($this->getStockAlertNotifications($currentRead));
        $notifications = $notifications->merge($this->getUrgentOutboundNotifications($currentRead));
        $notifications = $notifications->merge($this->getExpiringItemNotifications($currentRead));
        $notifications = $notifications->merge($this->getNewClientNotifications($currentRead));
        $notifications = $notifications->merge($this->getSystemHealthNotifications($currentRead));

        return $notifications->sortByDesc('created_at');
    }

    /**
     * Get pending request notifications
     */
    private function getPendingRequestNotifications($currentRead = [])
    {
        $notifications = collect();
        
        $pendingRequests = StockRequest::where('status', 'pending')->get();

        foreach ($pendingRequests as $request) {
            $notificationId = 'pending_' . $request->id;
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'pending_requests',
                'title' => 'Pending Stock Request',
                'message' => "Request #{$request->id} from " . ($request->client->name ?? 'Unknown') . " needs your review",
                'created_at' => $request->created_at,
                'read' => $isRead,
                'action_url' => '/admin/requests#request-' . $request->id,
                'icon' => 'clock',
                'color' => 'orange'
            ]);
        }

        return $notifications;
    }

    /**
     * Get password reset notifications
     */
    private function getPasswordResetNotifications($currentRead = [])
    {
        $notifications = collect();
        
        $pendingPasswordResets = PasswordResetRequest::where('status', 'pending')->get();

        foreach ($pendingPasswordResets as $reset) {
            $notificationId = 'password_' . $reset->id;
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'password_resets',
                'title' => 'Password Reset Request',
                'message' => "User {$reset->email} is requesting password reset",
                'created_at' => $reset->created_at,
                'read' => $isRead,
                'action_url' => '/admin/password-reset',
                'icon' => 'lock',
                'color' => 'purple'
            ]);
        }

        return $notifications;
    }

    /**
     * Get stock alert notifications
     */
    private function getStockAlertNotifications($currentRead = [])
    {
        $notifications = collect();
        
        // Low stock alerts
        $lowThreshold = 49;
        $lowStock = \App\Models\Stock::where('stock','>',0)->where('stock','<=',$lowThreshold)->get();

        foreach ($lowStock as $stock) {
            $notificationId = 'low_' . $stock->id;
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'low_stock',
                'title' => 'Low Stock Alert',
                'message' => "{$stock->description} is running low ({$stock->stock} units remaining)",
                'created_at' => $stock->updated_at,
                'read' => $isRead,
                'action_url' => '/admin/stocks',
                'icon' => 'alert-triangle',
                'color' => 'yellow'
            ]);
        }

        // Out of stock alerts
        $outStock = \App\Models\Stock::where('stock','<=',0)->get();

        foreach ($outStock as $stock) {
            $notificationId = 'out_' . $stock->id;
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'out_of_stock',
                'title' => 'Out of Stock Alert',
                'message' => "{$stock->description} is completely out of stock",
                'created_at' => $stock->updated_at,
                'read' => $isRead,
                'action_url' => '/admin/stocks',
                'icon' => 'x-circle',
                'color' => 'red'
            ]);
        }

        return $notifications;
    }

    /**
     * Get urgent outbound notifications
     */
    private function getUrgentOutboundNotifications($currentRead = [])
    {
        $notifications = collect();
        
        $urgentOutbounds = Outbound::where('is_urgent_outbound', true)
            ->where('approval', 'pending')
            ->with(['stock', 'urgentRecipient'])
            ->get();

        foreach ($urgentOutbounds as $urgent) {
            $notificationId = 'urgent_' . $urgent->id;
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'urgent_outbounds',
                'title' => 'Urgent Outbound Request',
                'message' => "{$urgent->stock->description} for {$urgent->recipient_name} needs immediate approval",
                'created_at' => $urgent->created_at,
                'read' => $isRead,
                'action_url' => '/admin/summary?type=urgent',
                'icon' => 'alert-triangle',
                'color' => 'red'
            ]);
        }

        return $notifications;
    }

    /**
     * Get expiring item notifications
     */
    private function getExpiringItemNotifications($currentRead = [])
    {
        $notifications = collect();
        
        $expiringItems = collect();
        if (\Illuminate\Support\Facades\Schema::hasColumn('stocks', 'expiry_date')) {
            $sevenDaysFromNow = \Carbon\Carbon::now()->addDays(7);
            $expiringItems = \App\Models\Stock::where('expiry_date', '<=', $sevenDaysFromNow)
                ->where('expiry_date', '>', \Carbon\Carbon::now())
                ->where('stock', '>', 0)
                ->get();
        }

        foreach ($expiringItems as $item) {
            $daysLeft = $item->expiry_date->diffInDays(now());
            $notificationId = 'expiring_' . $item->id;
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'expiring_items',
                'title' => 'Expiring Item Alert',
                'message' => "{$item->description} expires in {$daysLeft} days",
                'created_at' => $item->updated_at,
                'read' => $isRead,
                'action_url' => '/admin/stocks',
                'icon' => 'clock',
                'color' => $daysLeft <= 3 ? 'red' : 'orange'
            ]);
        }

        return $notifications;
    }

    /**
     * Get new client notifications
     */
    private function getNewClientNotifications($currentRead = [])
    {
        $notifications = collect();
        
        $recentClients = \App\Models\User::where('role', 'client')
            ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(24))
            ->get();

        foreach ($recentClients as $client) {
            $notificationId = 'client_' . $client->id;
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'new_clients',
                'title' => 'New Client Registration',
                'message' => "{$client->name} has registered as a new client",
                'created_at' => $client->created_at,
                'read' => $isRead,
                'action_url' => '/admin/clients',
                'icon' => 'user-plus',
                'color' => 'green'
            ]);
        }

        return $notifications;
    }

    /**
     * Get system health notifications
     */
    private function getSystemHealthNotifications($currentRead = [])
    {
        $notifications = collect();
        
        $failedJobs = 0;
        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            // Table might not exist, ignore
        }

        if ($failedJobs > 0) {
            $notificationId = 'system_health';
            $isRead = in_array($notificationId, $currentRead);
            
            $notifications->push((object)[
                'id' => $notificationId,
                'type' => 'system_health',
                'title' => 'System Health Alert',
                'message' => "{$failedJobs} failed job" . ($failedJobs !== 1 ? 's' : '') . " detected",
                'created_at' => now(),
                'read' => $isRead,
                'action_url' => '/admin/system-health',
                'icon' => 'alert-triangle',
                'color' => 'red'
            ]);
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($id)
    {
        $user = auth()->user();
        $readKey = 'admin_read_notifications_' . $user->id;
        $currentRead = session($readKey, []);
        
        if (!in_array($id, $currentRead)) {
            $currentRead[] = $id;
            session([$readKey => $currentRead]);
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead()
    {
        $user = auth()->user();
        $notifications = $this->getAdminNotifications($user);
        $readKey = 'admin_read_notifications_' . $user->id;
        $currentRead = session($readKey, []);
        
        // Mark all current notifications as read
        foreach ($notifications as $notification) {
            if (!in_array($notification->id, $currentRead)) {
                $currentRead[] = $notification->id;
            }
        }
        
        session([$readKey => $currentRead]);
        
        return response()->json(['success' => true]);
    }
}
