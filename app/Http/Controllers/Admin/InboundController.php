<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inbound;
use App\Models\InboundAllocation;
use App\Models\Stock;
use App\Models\StockAllocation;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\StockAllocationService;

class InboundController extends Controller
{
    public function index(Request $request)
    {
        $inbounds = Inbound::query()
            ->join('stocks', 'inbounds.stock_id', '=', 'stocks.id')
            ->leftJoin('stock_allocations', 'inbounds.id', '=', 'inbound_id')
            ->select(
                'inbounds.id',
                'stocks.id_no',
                'stocks.description',
                'stocks.unit',
                'inbounds.total',
                DB::raw('COALESCE(SUM(allocation),0) as allocated')
            )
            ->groupBy(
                'inbounds.id',
                'stocks.id_no',
                'stocks.description',
                'stocks.unit',
                'inbounds.total'
            )
            ->orderByDesc('inbounds.created_at')
            ->get();

        $spOffices = \App\Models\SPOffices::where('is_active', true)->orderBy('created_at', 'desc')->get();
        $stocks = \App\Models\Stock::all();

        return view('admin.inbound.index', compact('inbounds', 'spOffices', 'stocks'));
    }

    public function create()
    {
        $stocks = Stock::orderBy('description')->get();
        $spOffices = \App\Models\SPOffices::where('is_active', true)->orderBy('created_at', 'desc')->get();

        return view('admin.inbound.create', compact('stocks', 'spOffices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'total' => 'required|integer|min:1',
            // 'office_id' => 'nullable|exists:s_p_offices,id',
            "office_allocation" => 'nullable|array',
            "office_allocation.*.office_id" => 'required_with:allocate|exists:s_p_offices,id',
            "office_allocation.*.quantity" => 'required_with:allocate|integer|min:1'
        ]);

        $hasAllocation = isset($validated['office_allocation']) && is_array($validated['office_allocation']);

        DB::transaction(function () use ($validated, $hasAllocation) {
            $newInbound = Inbound::create([
                'stock_id' => $validated['stock_id'],
                'total' => $validated['total'],
            ]);

            $stock = Stock::findOrFail($validated['stock_id']);
            $stock->increment('total', $validated['total']);
            $stock->increment('stock', $validated['total']);

            if ($hasAllocation) {
                foreach ($validated['office_allocation'] as $allocation) {
                    if ($allocation['quantity'] > 0) {

                        $payload = [
                            'inbound_id' => $newInbound->id,
                            'stock_id' => $stock->id,
                            'office_id' => $allocation['office_id'],
                            'allocation' => $allocation['quantity'],
                        ];

                        $stockService = new StockAllocationService();
                        $stockService->createStockAllocation($payload);
                    }
                }
            }
        });

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Inbound added and stock updated.');
    }

    public function generateReportPdf(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $inbounds = $this->inboundQuery($request)
            ->orderByDesc('inbounds.created_at')
            ->get();

        $summary = [
            'records' => $inbounds->count(),
            'total_quantity' => $inbounds->sum('total'),
        ];

        $html = view('admin.inbound-report-pdf', compact(
            'inbounds',
            'dateFrom',
            'dateTo',
            'summary'
        ))->render();

        $pdf = new Dompdf();
        $pdf->setPaper('a4', 'portrait');
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isFontSubsettingEnabled', true);
        $pdf->set_option('chroot', base_path());
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inbound-report.pdf"',
        ]);
    }

    public function template()
    {
        $categories = Category::orderBy('name')->get(['name', 'code']);

        if (! $categories->contains('name', 'Unknown')) {
            $categories->push((object) [
                'name' => 'Unknown',
                'code' => 'UK',
            ]);
        }

        $stocks = Stock::with('category')
            ->orderBy('description')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inbound Template');

        $stockLookup = $spreadsheet->createSheet();
        $stockLookup->setTitle('stocks_lookup');

        foreach ($stocks as $index => $stock) {
            $row = $index + 1;

            $stockLookup->setCellValue("A{$row}", $stock->description);
            $stockLookup->setCellValue("B{$row}", $stock->unit ?? 'pcs');
            $stockLookup->setCellValue("C{$row}", optional($stock->category)->name ?? 'Unknown');
        }

        $categoryLookup = $spreadsheet->createSheet();
        $categoryLookup->setTitle('categories_lookup');

        foreach ($categories as $index => $category) {
            $categoryLookup->setCellValue('A' . ($index + 1), $category->name);
        }

        $stockCount = max($stocks->count(), 1);
        $categoryCount = max($categories->count(), 1);

        $instruction = 'Instructions: Enter or select Description. Unit defaults to pcs if blank. Quantity is required. Category may use code such as CS.';

        $sheet->setCellValue('A1', $instruction);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()
            ->setItalic(true)
            ->setSize(9)
            ->setColor(new Color('FF666666'));

        $sheet->fromArray(['Description', 'Unit', 'Quantity', 'Category'], null, 'A2');

        foreach (range('A', 'D') as $column) {
            $sheet->getStyle($column . '2')->getFont()
                ->setBold(true)
                ->setColor(new Color('FFFFFFFF'));

            $sheet->getStyle($column . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FF4472C4');

            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        for ($row = 3; $row <= 1000; $row++) {
            if ($stocks->count() > 0) {
                $descriptionValidation = $sheet->getCell("A{$row}")->getDataValidation();
                $descriptionValidation->setType(DataValidation::TYPE_LIST);
                $descriptionValidation->setAllowBlank(true);
                $descriptionValidation->setShowDropDown(true);
                $descriptionValidation->setShowErrorMessage(false);
                $descriptionValidation->setFormula1("=stocks_lookup!\$A\$1:\$A\${$stockCount}");

                $sheet->setCellValue(
                    "B{$row}",
                    '=IF($A' . $row . '<>"",IFERROR(VLOOKUP($A' . $row . ',stocks_lookup!$A$1:$C$' . $stockCount . ',2,FALSE),"pcs"),"")'
                );

                $sheet->setCellValue(
                    "D{$row}",
                    '=IF($A' . $row . '<>"",IFERROR(VLOOKUP($A' . $row . ',stocks_lookup!$A$1:$C$' . $stockCount . ',3,FALSE),""),"")'
                );
            }

            $categoryValidation = $sheet->getCell("D{$row}")->getDataValidation();
            $categoryValidation->setType(DataValidation::TYPE_LIST);
            $categoryValidation->setAllowBlank(true);
            $categoryValidation->setShowDropDown(true);
            $categoryValidation->setFormula1("=categories_lookup!\$A\$1:\$A\${$categoryCount}");
        }

        $stockLookup->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
        $categoryLookup->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'inbound-template.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $rows = $this->readImportRows($request);
        $headerRow = $this->detectHeaderRow($rows);
        $columns = $this->mapColumns($rows[$headerRow] ?? []);

        $imported = 0;
        $createdStocks = 0;
        $errors = [];
        $aggregates = [];

        foreach ($rows as $index => $row) {
            if ($index === 1 || $index === $headerRow) {
                continue;
            }

            $description = trim((string) ($row[$columns['description']] ?? ''));
            $unit = trim((string) ($row[$columns['unit']] ?? 'pcs'));
            $quantityRaw = trim((string) ($row[$columns['quantity']] ?? ''));
            $categoryValue = trim((string) ($row[$columns['category']] ?? ''));

            if ($description === '' && $quantityRaw === '' && $categoryValue === '') {
                continue;
            }

            $quantity = $this->parseQuantity($quantityRaw);

            if ($quantity === null) {
                $errors[] = "Row {$index}: invalid quantity '{$quantityRaw}'";
                continue;
            }

            $key = 'DESC:' . strtolower(preg_replace('/\s+/', ' ', $description));

            if (! isset($aggregates[$key])) {
                $aggregates[$key] = [
                    'description' => $description,
                    'unit' => $unit ?: 'pcs',
                    'category' => $categoryValue,
                    'quantity' => 0,
                ];
            }

            $aggregates[$key]['quantity'] += $quantity;
        }

        DB::transaction(function () use ($aggregates, &$imported, &$createdStocks) {
            foreach ($aggregates as $item) {
                $category = $this->resolveCategory($item['category']);

                $stock = Stock::whereRaw('LOWER(TRIM(description)) = ?', [
                    strtolower(trim($item['description'])),
                ])->first();

                if (! $stock) {
                    $stock = Stock::create([
                        'category_id' => $category->id,
                        'id_no' => $this->generateStockIdForCategory($category),
                        'description' => $item['description'] ?: 'Imported item',
                        'unit' => $item['unit'] ?: 'pcs',
                        'price' => 0,
                        'total' => 0,
                        'stock' => 0,
                        'hidden' => false,
                    ]);

                    $createdStocks++;
                } else {
                    $stock->update([
                        'category_id' => $category->id,
                        'unit' => $stock->unit ?: ($item['unit'] ?: 'pcs'),
                    ]);
                }

                if ($item['quantity'] > 0) {
                    Inbound::create([
                        'stock_id' => $stock->id,
                        'total' => $item['quantity'],
                    ]);

                    $stock->increment('total', $item['quantity']);
                    $stock->increment('stock', $item['quantity']);

                    $imported++;
                }
            }
        });

        $message = "Imported: {$imported}";

        if ($createdStocks > 0) {
            $message .= ", Stocks created: {$createdStocks}";
        }

        if (count($errors) > 0) {
            $message .= ', Errors: ' . implode('; ', array_slice($errors, 0, 5));
        }

        return back()->with('success', $message);
    }

    public function showInboundAllocations(Inbound $inbound)
    {
        $allocations = $inbound->allocations;
        $allocationsMap = $allocations->map(function ($item) {
            return [
                'office_name' => $item->office->office ?? 'Unknown',
                'allocation' => $item->allocation,
                // 'created_at' => $item->created_at->toDateTimeString(),
            ];
        });
        $data = [
            'inbound_id' => $inbound->id,
            'stock_description' => $inbound->stock->description,
            'total' => $inbound->total,
            'created_at' => \Carbon\Carbon::parse($inbound->created_at)->format('F j, Y'),
            'allocations' => $allocationsMap,
        ];
        return response()->json($data);
    }

    public function show(Inbound $inbound)
    {
        return redirect()->route('inbound.index');
    }

    public function suggestions(Request $request)
    {
        $query = trim($request->input('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $stocks = Stock::with('category')
            ->whereRaw('LOWER(description) LIKE ?', ['%' . strtolower($query) . '%'])
            ->orderBy('description')
            ->limit(10)
            ->get()
            ->map(fn($stock) => [
                'id' => $stock->id,
                'description' => $stock->description,
                'id_no' => $stock->id_no,
                'unit' => $stock->unit ?? 'pcs',
                'category_name' => optional($stock->category)->name ?? 'Unknown',
                'category_code' => optional($stock->category)->code ?? 'UK',
            ]);

        return response()->json($stocks);
    }

    private function inboundQuery(Request $request)
    {
        $query = DB::table('inbounds')
            ->join('stocks', 'inbounds.stock_id', '=', 'stocks.id')
            ->leftJoin('categories', 'stocks.category_id', '=', 'categories.id')
            ->select(
                'stocks.id_no',
                'stocks.description',
                'stocks.unit',
                'inbounds.total',
                'categories.name as category_name',
                'categories.code as category_code',
                'inbounds.created_at'
            );

        if ($request->query('date_from')) {
            $query->whereDate('inbounds.created_at', '>=', $request->query('date_from'));
        }

        if ($request->query('date_to')) {
            $query->whereDate('inbounds.created_at', '<=', $request->query('date_to'));
        }

        return $query;
    }

    private function readImportRows(Request $request): array
    {
        $path = $request->file('file')->getPathname();
        $ext = strtolower($request->file('file')->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($path);

            return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }

        $rows = [];
        $handle = fopen($path, 'r');

        if (! $handle) {
            return $rows;
        }

        $index = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $index++;

            if (isset($data[0]) && str_starts_with(trim($data[0]), '#')) {
                continue;
            }

            $rows[$index] = [
                'A' => $data[0] ?? null,
                'B' => $data[1] ?? null,
                'C' => $data[2] ?? null,
                'D' => $data[3] ?? null,
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function detectHeaderRow(array $rows): int
    {
        foreach ([1, 2] as $index) {
            $text = strtolower(implode('|', array_map('strval', $rows[$index] ?? [])));

            if (str_contains($text, 'description') && str_contains($text, 'quantity')) {
                return $index;
            }
        }

        return 1;
    }

    private function mapColumns(array $header): array
    {
        $columns = [
            'description' => 'A',
            'unit' => 'B',
            'quantity' => 'C',
            'category' => 'D',
        ];

        foreach ($header as $column => $value) {
            $heading = strtolower(trim((string) $value));

            if (str_contains($heading, 'description')) {
                $columns['description'] = $column;
            } elseif (str_contains($heading, 'unit')) {
                $columns['unit'] = $column;
            } elseif (str_contains($heading, 'quantity') || str_contains($heading, 'qty')) {
                $columns['quantity'] = $column;
            } elseif (str_contains($heading, 'category')) {
                $columns['category'] = $column;
            }
        }

        return $columns;
    }

    private function parseQuantity(string $value): ?int
    {
        $value = str_replace(["\xc2\xa0", ','], [' ', ''], $value);

        if (! preg_match('/\d+/', $value, $match)) {
            return null;
        }

        return (int) $match[0];
    }

    private function resolveCategory(?string $value): Category
    {
        $name = trim((string) $value);

        if ($name === '') {
            $name = 'Unknown';
        }

        $category = Category::whereRaw('LOWER(TRIM(name)) = ?', [
            strtolower($name),
        ])->first();

        if ($category) {
            return $category;
        }

        return Category::create([
            'name' => $name,
            'code' => $this->generateCategoryCode($name),
        ]);
    }

    private function generateCategoryCode(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z]/', '', strtoupper($name));
        $base = str_pad(substr($clean, 0, 2), 2, 'X');

        if (! Category::where('code', $base)->exists()) {
            return $base;
        }

        foreach (range('A', 'Z') as $letter) {
            $try = $base[0] . $letter;

            if (! Category::where('code', $try)->exists()) {
                return $try;
            }
        }

        foreach (range('A', 'Z') as $letter) {
            $try = $letter . $base[1];

            if (! Category::where('code', $try)->exists()) {
                return $try;
            }
        }

        return strtoupper(substr(md5($name . time()), 0, 2));
    }

    private function generateStockIdForCategory(Category $category): string
    {
        $code = $category->code ?: 'UK';

        $lastStock = Stock::where('id_no', 'like', $code . '-%')
            ->orderByDesc('id_no')
            ->first();

        $nextNumber = 1;

        if ($lastStock && str_contains($lastStock->id_no, '-')) {
            $nextNumber = ((int) explode('-', $lastStock->id_no)[1]) + 1;
        }

        return $code . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
