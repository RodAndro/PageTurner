<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBookImport;
use App\Jobs\ProcessUserImport;
use App\Jobs\ProcessExport;
use Illuminate\Support\Facades\Auth;
use App\Models\ImportLog;
use App\Models\ExportLog;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    /**
     * Show book import form
     */
    public function showBookImportForm()
    {
        return view('admin.import-export.books-import', [
            'categories' => Category::all(),
        ]);
    }

    /**
     * Download book import template
     */
    public function downloadBookTemplate()
    {
        $templateData = [
            ['ISBN', 'Title', 'Author', 'Price', 'Stock', 'Category', 'Description'],
            ['978-0-13-110362-7', 'Sample Book', 'Sample Author', '29.99', '50', 'Fiction', 'Sample description'],
        ];

        $fileName = 'book_import_template_' . now()->format('Y-m-d-His') . '.csv';
        $filePath = 'templates/' . $fileName;

        // Create CSV content
        $csvContent = '';
        foreach ($templateData as $row) {
            $csvContent .= implode(',', array_map(function($cell) {
                return '"' . str_replace('"', '""', $cell) . '"';
            }, $row)) . "\n";
        }

        Storage::disk('local')->put($filePath, $csvContent);

        return response()->download(Storage::disk('local')->path($filePath), $fileName)->deleteFileAfterSend();
    }

    /**
     * Store book import
     */
    public function storeBookImport(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
            'duplicate_mode' => 'required|in:skip,update',
        ]);

        // Count rows in file
        $file = $request->file('file');
        $filePath = $file->store('imports', 'local');

        // Count file rows (approximate)
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
            $file->getClientOriginalExtension() === 'csv' ? 'Csv' : 'Xlsx'
        );
        $spreadsheet = $reader->load(Storage::disk('local')->path($filePath));
        $totalRows = $spreadsheet->getActiveSheet()->getHighestRow() - 1; // Subtract header

        $importLog = ImportLog::create([
            'user_id' => Auth::id(),
            'module_type' => 'books',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending',
            'total_rows' => $totalRows,
        ]);

        // Process based on file size
        if ($totalRows > 10000) {
            // Queue large imports
            ProcessBookImport::dispatch($importLog, $request->input('duplicate_mode'));
            return redirect()->route('admin.import-export.status')
                ->with('success', 'Large import queued for processing. You will receive an email when completed.');
        } else {
            // Process immediately for small imports
            try {
                ProcessBookImport::dispatchSync($importLog, $request->input('duplicate_mode'));
                return redirect()->route('admin.import-export.status')
                    ->with('success', 'Books imported successfully!');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Show order export form
     */
    public function showOrderExportForm()
    {
        return view('admin.import-export.orders-export', [
            'statuses' => ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Failed', 'Cart'],
        ]);
    }

    /**
     * Show book export form
     */
    public function showBookExportForm(): \Illuminate\View\View
    {
        return view('admin.import-export.books-export', [
            'categories' => Category::orderBy('name')->get(),
            'stockStatuses' => [
                'in_stock' => 'In stock',
                'low_stock' => 'Low stock',
                'out_of_stock' => 'Out of stock',
            ],
        ]);
    }

    /**
     * Export books with filters and selected columns
     */
    public function exportBooks(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx,pdf',
            'category' => 'nullable|exists:categories,id',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0|gte:price_min',
            'stock_status' => 'nullable|in:in_stock,low_stock,out_of_stock',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'columns' => 'nullable|array',
        ]);

        $filters = $request->only([
            'category',
            'price_min',
            'price_max',
            'stock_status',
            'date_from',
            'date_to',
        ]);

        $selectedColumns = $request->input('columns', [
            'isbn',
            'title',
            'author',
            'category_name',
            'price',
            'stock_quantity',
            'description',
        ]);
        $format = $request->input('format', 'xlsx');

        $fileName = 'books_export_' . now()->format('Y-m-d-His') . '.' . $format;
        $filePath = 'exports/' . $fileName;

        $recordCount = (new \App\Exports\BooksExport($filters, $selectedColumns))->query()->count();

        $exportLog = ExportLog::create([
            'user_id' => Auth::id(),
            'module_type' => 'books',
            'export_format' => $format,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'pending',
            'total_records' => $recordCount,
            'filters' => $filters,
            'selected_columns' => $selectedColumns,
        ]);

        if ($recordCount > 10000) {
            ProcessExport::dispatch($exportLog);
            return redirect()->route('admin.import-export.status')
                ->with('success', 'Large book export queued for processing. You will receive an email when ready.');
        }

        try {
            ProcessExport::dispatchSync($exportLog);
            return redirect()->route('admin.import-export.download-export', ['export_log_id' => (int) $exportLog->id]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export orders
     */
    public function exportOrders(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx,pdf',
            'status' => 'nullable|in:Pending,Processing,Shipped,Delivered,Cancelled,Failed,Cart',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'customer_email' => 'nullable|string|max:255',
            'financial_report' => 'nullable|boolean',
            'columns' => 'nullable|array',
        ]);

        $filters = [
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'customer_email' => $request->input('customer_email'),
        ];
        $moduleType = $request->boolean('financial_report') ? 'orders_financial' : 'orders';

        $selectedColumns = $request->input('columns', ['id', 'order_number', 'customer_name', 'total_amount', 'status']);
        $format = $request->input('format', 'xlsx');

        $fileName = 'orders_export_' . now()->format('Y-m-d-His') . '.' . $format;
        $filePath = 'exports/' . $fileName;

        // Count records to determine if queuing is needed
        $query = Order::query();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['customer_email'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('email', 'like', '%' . $filters['customer_email'] . '%');
            });
        }

        $recordCount = $query->count();

        $exportLog = ExportLog::create([
            'user_id' => Auth::id(),
            'module_type' => $moduleType,
            'export_format' => $format,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'pending',
            'total_records' => $recordCount,
            'filters' => $filters,
            'selected_columns' => $selectedColumns,
        ]);

        if ($recordCount > 10000) {
            // Queue large exports
            ProcessExport::dispatch($exportLog);
            return redirect()->route('admin.import-export.status')
                ->with('success', 'Large export queued for processing. You will receive an email when ready.');
        } else {
            // Process immediately
            try {
                ProcessExport::dispatchSync($exportLog);
                return redirect()->route('admin.import-export.download-export', ['export_log_id' => (int) $exportLog->id]);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Download exported file
     */
    public function downloadExport(int $export_log_id): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $exportLog = ExportLog::findOrFail($export_log_id);

        if (!Storage::disk('local')->exists($exportLog->file_path)) {
            return redirect()->back()->with('error', 'Export file not found.');
        }

        return response()->download(Storage::disk('local')->path($exportLog->file_path), $exportLog->file_name);
    }

    /**
     * Show import/export status
     */
    public function showStatus(Request $request): \Illuminate\View\View
    {
        $imports = ImportLog::where('user_id', Auth::id())
            ->latest()
            ->limit(10)
            ->get();

        $exports = ExportLog::where('user_id', Auth::id())
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.import-export.status', [
            'imports' => $imports,
            'exports' => $exports,
        ]);
    }

    /**
     * Show user import form
     */
    public function showUserImportForm()
    {
        return view('admin.import-export.users-import');
    }

    /**
     * Store user import
     */
    public function storeUserImport(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
        ]);

        $file = $request->file('file');
        $filePath = $file->store('imports', 'local');

        // Count file rows
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
            $file->getClientOriginalExtension() === 'csv' ? 'Csv' : 'Xlsx'
        );
        $spreadsheet = $reader->load(Storage::disk('local')->path($filePath));
        $totalRows = $spreadsheet->getActiveSheet()->getHighestRow() - 1;

        $importLog = ImportLog::create([
            'user_id' => Auth::id(),
            'module_type' => 'users',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending',
            'total_rows' => $totalRows,
        ]);

        if ($totalRows > 10000) {
            ProcessUserImport::dispatch($importLog);
            return redirect()->route('admin.import-export.status')
                ->with('success', 'Large user import queued for processing.');
        } else {
            try {
                ProcessUserImport::dispatchSync($importLog);
                return redirect()->route('admin.import-export.status')
                    ->with('success', 'Users imported successfully!');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Show user export form
     */
    public function showUserExportForm()
    {
        return view('admin.import-export.users-export');
    }

    /**
     * Export users
     */
    public function exportUsers(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx,pdf',
            'redact_pii' => 'nullable|boolean',
        ]);

        $filters = [
            'redact_pii' => $request->boolean('redact_pii', false),
        ];

        $selectedColumns = array_values(array_unique(array_merge(
            ['id'],
            $request->input('columns', ['name', 'email', 'role', 'email_verified', 'created_at'])
        )));
        $format = $request->input('format', 'xlsx');

        $fileName = 'users_export_' . now()->format('Y-m-d-His') . '.' . $format;
        $filePath = 'exports/' . $fileName;

        $recordCount = User::count();

        $exportLog = ExportLog::create([
            'user_id' => Auth::id(),
            'module_type' => 'users',
            'export_format' => $format,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'pending',
            'total_records' => $recordCount,
            'filters' => $filters,
            'selected_columns' => $selectedColumns,
        ]);

        if ($recordCount > 10000) {
            ProcessExport::dispatch($exportLog);
            return redirect()->route('admin.import-export.status')
                ->with('success', 'Large user export queued for processing.');
        } else {
            try {
                ProcessExport::dispatchSync($exportLog);
                return redirect()->route('admin.import-export.download-export', ['export_log_id' => (int) $exportLog->id]);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
            }
        }
    }
}
