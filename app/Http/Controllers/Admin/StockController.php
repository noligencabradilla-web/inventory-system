<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Category;

class StockController extends Controller
{
    // Show all stocks
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $availability = $request->query('availability');

        $stocks = Stock::with('category')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('id_no', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($q) {
                            $categoryQuery->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($availability === 'in', function ($query) {
                $query->where('stock', '>=', 50);
            })
            ->when($availability === 'low', function ($query) {
                $query->where('stock', '>', 0)
                    ->where('stock', '<=', 49);
            })
            ->when($availability === 'out', function ($query) {
                $query->where('stock', '<=', 0);
            })
            ->orderBy('description')
            ->paginate(15)
            ->withQueryString();

        $allCategories = Category::orderBy('name')->get();

        return view('admin.stocks.index', compact('stocks', 'allCategories'));
    }

    // Show form to create a stock
    public function create()
    {
        $categories = Category::all();
        return view('admin.stocks.create', compact('categories'));
    }

    // Store new stock
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'id_no' => 'required|string|unique:stocks,id_no',
            'description' => 'required|string',
            'unit' => 'required|string',
            'stock' => 'required|integer|min:0',
            'hidden' => 'boolean'
        ]);

        Stock::create($request->only(['category_id','id_no','description','unit','stock','hidden']));

        // Check if request is AJAX (from modal)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => 'Stock added successfully.'
            ]);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock added.');
    }

    // Update an existing stock item
    public function update(Request $request, Stock $stock)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'id_no' => 'required|string|unique:stocks,id_no,' . $stock->id,
            'description' => 'required|string',
            'unit' => 'required|string',
            'total' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'hidden' => 'boolean'
        ]);

        $stock->update($request->only(['category_id','id_no','description','unit','total','stock','hidden']));

        return redirect()->route('stocks.index')
            ->with('success', 'Stock updated.');
    }

    // Generate next stock ID based on category
    public function generateId($categoryId)
    {
        $category = Category::find($categoryId);
        
        if (!$category || !$category->code) {
            return response()->json(['error' => 'Category code not set'], 400);
        }

        // Get the last stock with this category code
        $lastStock = Stock::where('id_no', 'like', $category->code . '-%')
            ->orderBy('id_no', 'desc')
            ->first();

        if ($lastStock) {
            // Extract number from last ID (e.g., "CS-001" -> "001")
            $lastNumber = (int) explode('-', $lastStock->id_no)[1];
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $newId = $category->code . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return response()->json(['id_no' => $newId]);
    }

    // Assign or create category for a stock (AJAX)
    public function assignCategory(Request $request, Stock $stock)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'new_category_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $categoryId = $request->input('category_id');
        $newName = trim((string) $request->input('new_category_name', ''));

        if (!$categoryId && $newName !== '') {
            $code = $this->generateCategoryCode($newName);
            $category = Category::create(['name' => $newName, 'code' => $code]);
            $categoryId = $category->id;
        }

        if ($categoryId) {
            $stock->category_id = $categoryId;
            // update description when supplied from modal
            if ($request->filled('description')) {
                $stock->description = $request->input('description');
            }
            $stock->save();
            $categoryName = Category::find($categoryId)?->name ?? null;
            return response()->json([
                'success' => true,
                'category_name' => $categoryName,
                'stock_description' => $stock->description,
                'stock_id_no' => $stock->id_no,
            ]);
        }

        // If no category selected or created, assign to `Unknown` (table requires a category_id)
        $unknown = Category::firstOrCreate(['name' => 'Unknown'], ['code' => 'UK']);
        $stock->category_id = $unknown->id;
        if ($request->filled('description')) {
            $stock->description = $request->input('description');
        }
        $stock->save();
        return response()->json([
            'success' => true,
            'category_name' => $unknown->name,
            'stock_description' => $stock->description,
            'stock_id_no' => $stock->id_no,
        ]);
    }

    // Edit stock via modal (AJAX)
    public function editModal(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'hidden' => 'nullable|boolean',
        ]);

        $validated['hidden'] = $request->boolean('hidden');

        $stock->update($validated);

        return response()->json([
            'success' => true,
            'stock' => $stock->load('category'),
        ]);
    }

    // Helper: generate 2-letter category code (tries sensible fallbacks)
    private function generateCategoryCode(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z]/', '', strtoupper($name));
        $base = str_pad(substr($clean, 0, 2), 2, 'X');

        if (!Category::where('code', $base)->exists()) {
            return $base;
        }

        $letters = range('A', 'Z');
        foreach ($letters as $l) {
            $try = $base[0] . $l;
            if (!Category::where('code', $try)->exists()) return $try;
        }

        foreach ($letters as $l) {
            $try = $l . $base[1];
            if (!Category::where('code', $try)->exists()) return $try;
        }

        return strtoupper(substr(md5($name . time()), 0, 2));
    }
}
