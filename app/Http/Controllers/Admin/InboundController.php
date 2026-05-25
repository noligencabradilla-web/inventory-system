<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inbound;
use App\Models\Stock;
use Dompdf\Dompdf;

class InboundController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $inboundsQuery = \DB::table('inbounds')
            ->join('stocks', 'inbounds.stock_id', '=', 'stocks.id')
            ->leftJoin('categories', 'stocks.category_id', '=', 'categories.id')
            ->select(
                'stocks.id_no',
                'stocks.description',
                'stocks.unit',
                'inbounds.total',
                'categories.name as category_name',
                'inbounds.created_at'
            );

        if ($dateFrom) {
            $inboundsQuery->whereDate('inbounds.created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $inboundsQuery->whereDate('inbounds.created_at', '<=', $dateTo);
        }

        $inbounds = $inboundsQuery
            ->orderByDesc('inbounds.created_at')
            ->get();

        return view('admin.inbound.index', compact('inbounds'));
    }

    public function create()
    {
        $stocks = Stock::all();
        return view('admin.inbound.create', compact('stocks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'total' => 'required|integer|min:1'
        ]);

        $inbound = Inbound::create($request->only('stock_id', 'total'));

        // update stock
        $stock = Stock::find($request->stock_id);
        $stock->increment('total', $request->total);
        $stock->increment('stock', $request->total);

        return redirect()->route('inbound.index')->with('success', 'Inbound added and stock updated.');
    }

    public function generateReportPdf(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $inboundsQuery = \DB::table('inbounds')
            ->join('stocks', 'inbounds.stock_id', '=', 'stocks.id')
            ->leftJoin('categories', 'stocks.category_id', '=', 'categories.id')
            ->select(
                'stocks.id_no',
                'stocks.description',
                'stocks.unit',
                'inbounds.total',
                'categories.name as category_name',
                'inbounds.created_at'
            );

        if ($dateFrom) {
            $inboundsQuery->whereDate('inbounds.created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $inboundsQuery->whereDate('inbounds.created_at', '<=', $dateTo);
        }

        $inbounds = $inboundsQuery
            ->orderByDesc('inbounds.created_at')
            ->get();

        $summary = [
            'records' => $inbounds->count(),
            'total_quantity' => $inbounds->sum('total'),
        ];

        $pdf = new Dompdf();
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isFontSubsettingEnabled', true);
        $pdf->set_option('enablePhp', true);
        $pdf->set_option('enableJavascript', true);
        $pdf->setPaper('a4', 'portrait');

        $html = view('admin.inbound-report-pdf', compact('inbounds', 'dateFrom', 'dateTo', 'summary'))->render();
        $pdf->set_option('chroot', base_path());
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inbound-report.pdf"',
        ]);
    }

    /**
     * Download an XLSX template for bulk inbound import.
     * Columns: Description | Quantity | Category
     * - The Category column has a dropdown populated from current categories + 'Unknown'.
     * - Template contains only the header (no instructional/sample text).
     */
    public function template()
    {
        // categories for dropdown (always include 'Unknown')
        $categories = \App\Models\Category::orderBy('name')->pluck('name')->toArray();
        if (!in_array('Unknown', $categories)) $categories[] = 'Unknown';

        // prepare stocks lookup (id_no, description, category)
        $stocks = \App\Models\Stock::with('category')->orderBy('id_no')->get();
        $stockRows = [];
        foreach ($stocks as $s) {
            $stockRows[] = [ $s->id_no, $s->description, optional($s->category)->name ?? 'Unknown' ];
        }

        // If PhpSpreadsheet is available, build an XLSX with Stock ID dropdown + VLOOKUP autofill for Description/Category/Price
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Inbound Template');

            // Add a hidden lookup sheet for stocks (description, id_no, category)
            $stockLookup = $spreadsheet->createSheet();
            $stockLookup->setTitle('stocks_lookup');
            foreach ($stockRows as $i => $rowVals) {
                // store description in column A (used for dropdown), id_no in B, category in C
                $stockLookup->setCellValue('A' . ($i + 1), $rowVals[1]);
                $stockLookup->setCellValue('B' . ($i + 1), $rowVals[0]);
                $stockLookup->setCellValue('C' . ($i + 1), $rowVals[2]);
            }
            $stockLookupCount = count($stockRows);

            // Instruction row (row 1) — visible guidance for users
            $instructionText = $stockLookupCount > 0
                ? 'Instructions: Enter or select Description (dropdown suggests existing stocks, but new descriptions are fully allowed). Quantity is required. Unit defaults to pcs if blank. Category auto-fill from Description when known.'
                : 'Instructions: Enter new item descriptions (no existing stocks in database yet). Quantity is required. Unit defaults to pcs if blank. Category will be created automatically if missing.';
            $sheet->fromArray([$instructionText], null, 'A1');
            $instCell = $sheet->getCell('A1');
            $instCell->getStyle()->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF666666'));
            $sheet->mergeCells('A1:D1');

            // Header row (row 2): Description, Unit, Quantity, Category
            $sheet->fromArray(['Description', 'Unit', 'Quantity', 'Category'], null, 'A2');
            // Style header row
            for ($col = 'A'; $col <= 'D'; $col++) {
                $headerCell = $sheet->getCell($col . '2');
                $headerCell->getStyle()->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
                $headerCell->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
            }

            // Add a hidden lookup sheet for categories
            $catLookup = $spreadsheet->createSheet();
            $catLookup->setTitle('categories_lookup');
            foreach ($categories as $i => $name) {
                $catLookup->setCellValue('A' . ($i + 1), $name);
            }
            $catLookupCount = count($categories);

            // Set data validation (dropdown) on Description column (A3..A1000) pointing to stocks_lookup descriptions
            // Allow free-text entry so users can type new items; the dropdown simply suggests existing stocks.
            $maxRows = 1000;
            for ($row = 3; $row <= $maxRows; $row++) {
                // Description field - use dropdown if stocks exist, otherwise completely free text
                $cellDesc = $sheet->getCell('A' . $row);
                if ($stockLookupCount > 0) {
                    $validationDesc = $cellDesc->getDataValidation();
                    $validationDesc->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    // Allow free-text entry with no restrictions or warnings
                    $validationDesc->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                    $validationDesc->setAllowBlank(true);
                    $validationDesc->setShowInputMessage(false);
                    $validationDesc->setShowErrorMessage(false);
                    $validationDesc->setShowDropDown(true);
                    $validationDesc->setFormula1('=stocks_lookup!$A$1:$A$' . $stockLookupCount);
                }
                // If no existing stocks, no validation applied - completely free text entry

                // Category autofill via formula when Description chosen (lookup by description)
                // Place category in column E and price in column D (Description=A, Unit=B, Quantity=C, Price=D, Category=E)
                $sheet->setCellValue('D' . $row, '=IF($A' . $row . '<>"",IFERROR(VLOOKUP($A' . $row . ',stocks_lookup!$A$1:$D$' . $stockLookupCount . ',3,FALSE),""),"")');
                $sheet->setCellValue('E' . $row, '=IF($A' . $row . '<>"",IFERROR(VLOOKUP($A' . $row . ',stocks_lookup!$A$1:$D$' . $stockLookupCount . ',4,FALSE),""),"")');

                // Category dropdown data validation as fallback when not using existing stock descriptions
                $cellE = $sheet->getCell('E' . $row);
                $validationE = $cellE->getDataValidation();
                $validationE->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationE->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationE->setAllowBlank(true);
                $validationE->setShowInputMessage(true);
                $validationE->setShowErrorMessage(true);
                $validationE->setShowDropDown(true);
                $validationE->setFormula1('=categories_lookup!$A$1:$A$' . $catLookupCount);
            }

            // Hide lookup sheets
            $catLookup->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            $stockLookup->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

            // Output XLSX (stream directly)
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $fileName = 'inbound-template.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $writer->save('php://output');
            exit;
        }

        // Fallback: return a CSV header-only template when PhpSpreadsheet is not available.
        // Include a commented suggestions line (starts with #) listing current stock descriptions — importer will skip comment lines.
        $stockDescriptions = array_map(function($r){ return $r[1]; }, $stockRows);
        $suggestions = '# Suggestions: ' . implode(' | ', $stockDescriptions) . "\n";
        $csv = "Description,Unit,Quantity,Price,Category\n" . $suggestions;
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inbound-template.csv"',
        ]);
    }

    /**
     * Import inbound records from uploaded Excel/CSV file.
     * - Auto-creates categories when missing (with auto-generated 2-letter code)
     * - Auto-creates stocks when `Stock ID` not matched (uses category automation for id_no)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ]);

        $path = $request->file('file')->getPathname();
        if (!is_readable($path)) {
            return back()->with('error', 'Uploaded file is not readable.');
        }

        $ext = strtolower(pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_EXTENSION));
        $rows = [];
        $usedSpreadsheet = false;

        // Prefer PhpSpreadsheet detection/reading first — handles mislabeled uploads (XLSX content with .csv extension)
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true); // A,B,C...
                $usedSpreadsheet = true;
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                // If the user supplied an Excel filename, return a helpful error; otherwise fall back to CSV parsing.
                if (in_array($ext, ['xlsx', 'xls'])) {
                    return back()->with('error', 'Uploaded Excel file could not be read: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                if (in_array($ext, ['xlsx', 'xls'])) {
                    return back()->with('error', 'Uploaded Excel file could not be read: ' . $e->getMessage());
                }
            }
        }

        // CSV fallback when spreadsheet reader wasn't used / failed
        if (! $usedSpreadsheet) {
            $handle = fopen($path, 'r');
            if (!$handle) return back()->with('error', 'Unable to open uploaded file.');
            $idx = 0;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $idx++;
                if (isset($data[0]) && str_starts_with(trim($data[0]), '#')) continue; // comments
                // Map to A,B,C,D,E to support Description,Unit,Quantity,Price,Category
                $rows[$idx] = [
                    'A' => $data[0] ?? null,
                    'B' => $data[1] ?? null,
                    'C' => $data[2] ?? null,
                    'D' => $data[3] ?? null,
                    'E' => $data[4] ?? null,
                ];
            }
            fclose($handle);
        }

        $imported = 0;
        $createdStocks = 0;
        $errors = [];

        // Determine column mapping from header row (row 2 in template with instruction row; row 1 in older imports)
        $colMap = ['description' => 'A', 'unit' => null, 'quantity' => 'B', 'price' => null, 'category' => 'C', 'id' => null];
        $headerRowIndex = 1; // assume older format without instruction row
        if (isset($rows[2]) && is_array($rows[2])) {
            // New template format: instruction at row 1, header at row 2
            $headerVals = array_values($rows[2]);
            $headerText = strtolower(implode('|', $headerVals));
            if (str_contains($headerText, 'description') && str_contains($headerText, 'unit')) {
                $headerRowIndex = 2;
            }
        }
        
        if (isset($rows[$headerRowIndex]) && is_array($rows[$headerRowIndex])) {
            foreach ($rows[$headerRowIndex] as $col => $val) {
                $h = strtolower(trim((string) $val));
                if (str_contains($h, 'description')) $colMap['description'] = $col;
                elseif (str_contains($h, 'unit')) $colMap['unit'] = $col;
                elseif (str_contains($h, 'quantity') || str_contains($h, 'qty')) $colMap['quantity'] = $col;
                elseif (str_contains($h, 'price')) $colMap['price'] = $col;
                elseif (str_contains($h, 'category')) $colMap['category'] = $col;
                elseif (str_contains($h, 'stock id') || $h === 'id' || str_contains($h, 'id_no')) $colMap['id'] = $col;
            }
        }

        // Ensure sensible defaults
        if (!$colMap['description']) $colMap['description'] = 'A';
        if (!$colMap['quantity']) $colMap['quantity'] = 'B';
        if (!$colMap['category']) $colMap['category'] = 'C';

        // Aggregate rows so identical items (by Stock ID if provided, else Description+Category) are combined
        $aggregates = []; // key => [description, unit, categoryName, id_no, quantity]

        foreach ($rows as $index => $row) {
            // Skip instruction row (row 1) and header row
            if ($index === 1 || $index === $headerRowIndex) continue;

            $description = trim((string) ($row[$colMap['description']] ?? ''));
            $unit = trim((string) ($row[$colMap['unit']] ?? ''));
            $quantityRaw = trim((string) ($row[$colMap['quantity']] ?? ''));
            $priceRaw = $colMap['price'] ? trim((string) ($row[$colMap['price']] ?? '')) : '';

            // Extract numeric value from quantity (e.g., "5 pcs" → 5, "5.5 pieces" → 5.5, "5,000" → 5000)
            $quantityNumeric = null;
            if (preg_match('/(\d+(?:[.,]\d{3})*(?:[.,]\d+)?)/', str_replace(["\xc2\xa0"], [' '], $quantityRaw), $matches)) {
                $quantityNumeric = (float) str_replace(',', '.', $matches[1]);
            }

            // Fallback: if mapped quantity cell contains unit text (e.g. 'pcs'), scan the entire row for the first numeric value
            if ($quantityNumeric === null) {
                foreach ($row as $colKey => $cellVal) {
                    $cell = trim((string) $cellVal);
                    if ($cell === '') continue;
                    if (preg_match('/(\d+(?:[.,]\d{3})*(?:[.,]\d+)?)/', str_replace(["\xc2\xa0"], [' '], $cell), $m)) {
                        $quantityNumeric = (float) str_replace(',', '.', $m[1]);

                        // If the numeric was found in the column that was actually the 'unit' column,
                        // assume the original mapped quantity cell held the unit text and swap accordingly.
                        if (isset($colMap['unit']) && $colKey === $colMap['unit']) {
                            if ($quantityRaw !== '' && !preg_match('/\d/', $quantityRaw)) {
                                $unit = $quantityRaw;
                            }
                        }

                        break;
                    }
                }
            }

            $categoryName = trim((string) ($row[$colMap['category']] ?? ''));
            $idNo = isset($colMap['id']) && isset($row[$colMap['id']]) ? trim((string) ($row[$colMap['id']] ?? '')) : null;
            $priceNumeric = null;
            if ($priceRaw !== '') {
                if (preg_match('/(\d+(?:[.,]\d{3})*(?:[.,]\d+)?)/', str_replace(["\xc2\xa0"], [' '], $priceRaw), $m)) {
                    $priceNumeric = (float) str_replace(',', '.', $m[1]);
                }
            }

            if ($description === '' && $quantityRaw === '' && $categoryName === '' && empty($idNo)) continue; // empty row

            if ($quantityNumeric === null) {
                $errors[] = "Row $index: invalid quantity '" . ($row[$colMap['quantity']] ?? '') . "'";
                continue;
            }
            
            $quantity = (int) $quantityNumeric;

            // Normalize keys: prefer Stock ID when supplied, otherwise normalize by description.
            $normDesc = strtolower(preg_replace('/\s+/', ' ', trim($description)));
            $normDescKey = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normDesc); // remove punctuation for key stability
            if ($idNo !== null && $idNo !== '') {
                $key = 'ID:' . strtolower(trim($idNo));
            } else {
                $key = 'DESC:' . $normDescKey;
            }

            if (!isset($aggregates[$key])) {
                $aggregates[$key] = [
                    'description' => $description,
                    'unit' => $unit,
                    'category' => $categoryName,
                    'id_no' => $idNo,
                    'quantity' => 0,
                    'price' => $priceNumeric,
                ];
            }

            if ($aggregates[$key]['price'] === null && $priceNumeric !== null) {
                $aggregates[$key]['price'] = $priceNumeric;
            }

            $aggregates[$key]['quantity'] += $quantity;
        }

        // Process aggregates: create/find stocks and create a single inbound per aggregate
        foreach ($aggregates as $key => $item) {
            $description = $item['description'];
            $categoryName = $item['category'];
            $idNo = $item['id_no'];
            $quantity = $item['quantity'];

            // Resolve or map category
            $category = null;
            if ($categoryName) {
                $category = \App\Models\Category::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->first();
            }

            if (!$category) {
                $category = \App\Models\Category::firstOrCreate(['name' => 'Unknown'], ['code' => 'UK']);
                if (empty($category->code)) {
                    if (!\App\Models\Category::where('code', 'UK')->exists()) {
                        $category->code = 'UK';
                        $category->save();
                    }
                }
            }

            // Find existing stock by Stock ID first, then match by normalized description.
            $stock = null;
            if ($idNo) {
                $stock = Stock::where('id_no', $idNo)->first();
            }

            if (!$stock && $description !== '') {
                $searchDesc = strtolower(trim($description));
                $stock = Stock::whereRaw('LOWER(TRIM(description)) = ?', [$searchDesc])->first();
            }

            if (!$stock) {
                // Create new stock with generated or provided id_no
                if (empty($idNo)) {
                    $newId = $this->generateStockIdForCategory($category);
                } else {
                    // ensure uniqueness: if idNo already exists somehow, fallback
                    if (Stock::where('id_no', $idNo)->exists()) {
                        $newId = $this->generateStockIdForCategory($category);
                    } else {
                        $newId = $idNo;
                    }
                }

                $stock = Stock::create([
                    'category_id' => $category->id,
                    'id_no' => $newId,
                    'description' => $description ?: 'Imported item',
                    'unit' => $item['unit'] ?? 'pcs',
                    'price' => $item['price'] ?? 0,
                    'total' => 0,
                    'stock' => 0,
                    'hidden' => false,
                ]);
                $createdStocks++;
            }

            if ($quantity > 0) {
                Inbound::create(['stock_id' => $stock->id, 'total' => $quantity]);
                $stock->increment('total', $quantity);
                $stock->increment('stock', $quantity);
                $imported++;
            } else {
                // Still save stock changes if any, but don't create inbound record for zero quantity
                $stock->save();
            }
        }

        $msg = "Imported: $imported";
        if ($createdStocks) $msg .= ", Stocks created: $createdStocks";
        if (count($errors)) $msg .= ", Errors: " . implode('; ', array_slice($errors, 0, 5));

        return back()->with('success', $msg);
    }

    // Generate a 2-letter unique category code (tries sensible fallbacks)
    private function generateCategoryCode(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z]/', '', strtoupper($name));
        $base = str_pad(substr($clean, 0, 2), 2, 'X');

        // If base is available, use it; otherwise try variations
        if (!\App\Models\Category::where('code', $base)->exists()) {
            return $base;
        }

        $letters = range('A', 'Z');
        // try firstChar + A..Z
        foreach ($letters as $l) {
            $try = $base[0] . $l;
            if (!\App\Models\Category::where('code', $try)->exists()) return $try;
        }

        // try A..Z + secondChar
        foreach ($letters as $l) {
            $try = $l . $base[1];
            if (!\App\Models\Category::where('code', $try)->exists()) return $try;
        }

        // fallback — deterministic but unlikely to collide
        return strtoupper(substr(md5($name . time()), 0, 2));
    }

    // Generate next stock id_no for a category (re-uses stock id automation)
    private function generateStockIdForCategory(\App\Models\Category $category): string
    {
        $lastStock = Stock::where('id_no', 'like', $category->code . '-%')
            ->orderBy('id_no', 'desc')
            ->first();

        if ($lastStock) {
            $lastNumber = (int) explode('-', $lastStock->id_no)[1];
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $category->code . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Show a single inbound record. (Resource route expects this.)
     * Currently redirects to index — create a dedicated view if you want a detail page.
     */
    public function show(Inbound $inbound)
    {
        return redirect()->route('inbound.index');
    }

    /**
     * API endpoint to get stock suggestions for autocomplete.
     * Filters stocks by description (case-insensitive partial match).
     * Returns JSON array of {id, description, id_no, unit, category_name}
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen(trim($query)) < 1) {
            return response()->json([]);
        }

        $stocks = Stock::with('category')
            ->whereRaw('LOWER(description) LIKE ?', ['%' . strtolower($query) . '%'])
            ->orderBy('description')
            ->limit(10)
            ->get()
            ->map(function($stock) {
                return [
                    'id' => $stock->id,
                    'description' => $stock->description,
                    'id_no' => $stock->id_no,
                    'unit' => $stock->unit ?? 'pcs',
                    'category_name' => optional($stock->category)->name ?? 'Unknown',
                ];
            });

        return response()->json($stocks);
    }
}


