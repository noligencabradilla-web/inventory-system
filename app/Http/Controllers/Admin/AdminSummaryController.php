<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\Outbound;
use App\Models\Stock;
use App\Models\StockRequest;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSummaryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $office = trim((string) $request->query('office', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $offices = StockRequest::select('office')
            ->whereNotNull('office')
            ->where('office', '<>', '')
            ->distinct()
            ->orderBy('office')
            ->pluck('office');

        $reportData = $this->prepareSummaryReportData(
            q: $q,
            office: $office,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            paginate: true
        );

        return view('admin.summary', array_merge([
            'offices' => $offices,
            'q' => $q,
            'office' => $office,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $reportData));
    }

    public function generatePdf(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $q = trim((string) $request->query('q', ''));
        $office = trim((string) $request->query('office', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $reportData = $this->prepareSummaryReportData(
            q: $q,
            office: $office,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            paginate: false
        );

        $reportData['office'] = $office;

        $pdf = new Dompdf();
        $pdf->set_option('isRemoteEnabled', false);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isFontSubsettingEnabled', false);
        $pdf->set_option('defaultFont', 'Arial');
        $pdf->setPaper('a4', 'landscape');

        $html = view('admin.summary-report-pdf', $reportData)->render();

        $pdf->set_option('chroot', base_path());
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="admin-summary-report.pdf"',
        ]);
    }

    private function prepareSummaryReportData(string $q,string $office,?string $dateFrom,?string $dateTo,bool $paginate = false,int $perPage = 15): array {
        $start = $dateFrom
            ? Carbon::parse($dateFrom)->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $dateTo
            ? Carbon::parse($dateTo)->endOfDay()
            : Carbon::now()->endOfDay();

        $stockQuery = Stock::query();

        if ($q !== '') {
            $stockQuery->where(function ($query) use ($q) {
                $query->where('id_no', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $stocks = $paginate
            ? $stockQuery->orderBy('description')->paginate($perPage)->withQueryString()
            : $stockQuery->orderBy('description')->get();

        $stockCollection = $paginate
            ? $stocks->getCollection()
            : $stocks;

        $stockIds = $stockCollection->pluck('id');

        $inboundTotals = collect();
        $outboundTotals = collect();
        $futureInboundTotals = collect();
        $futureOutboundTotals = collect();

        if ($stockIds->count() > 0) {
            $inboundTotals = Inbound::whereIn('stock_id', $stockIds)
                ->whereBetween('created_at', [$start, $end])
                ->select('stock_id', DB::raw('SUM(total) as total'))
                ->groupBy('stock_id')
                ->pluck('total', 'stock_id');

            $outboundTotals = Outbound::whereIn('stock_id', $stockIds)
                ->whereNotNull('deducted_at')
                ->whereBetween('deducted_at', [$start, $end])
                ->when($office !== '', fn ($query) => $query->where('office', $office))
                ->select('stock_id', DB::raw('SUM(total) as total'))
                ->groupBy('stock_id')
                ->pluck('total', 'stock_id');

            $futureInboundTotals = Inbound::whereIn('stock_id', $stockIds)
                ->where('created_at', '>', $end)
                ->select('stock_id', DB::raw('SUM(total) as total'))
                ->groupBy('stock_id')
                ->pluck('total', 'stock_id');

            $futureOutboundTotals = Outbound::whereIn('stock_id', $stockIds)
                ->whereNotNull('deducted_at')
                ->where('deducted_at', '>', $end)
                ->when($office !== '', fn ($query) => $query->where('office', $office))
                ->select('stock_id', DB::raw('SUM(total) as total'))
                ->groupBy('stock_id')
                ->pluck('total', 'stock_id');
        }

        $stockSummaries = $stockCollection->map(function ($stock) use (
            $inboundTotals,
            $outboundTotals,
            $futureInboundTotals,
            $futureOutboundTotals
        ) {
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

        if ($paginate) {
            $stocks->setCollection($stockSummaries);
            $stockSummaries = $stocks;
        }

        return [
            'stocks' => $stocks,
            'stockSummaries' => $stockSummaries,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'start' => $start,
            'end' => $end,
        ];
    }
}