<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\AuditLogSearch;
use App\Models\AuditLogAlert;
use App\Models\User;
use App\Services\AuditLogService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Show audit logs dashboard
     */
    public function dashboard(): View
    {
        $totalLogs = AuditLog::online()->count();
        $criticalLogs = AuditLog::online()->critical()->count();
        $sensitiveLogs = AuditLog::online()->sensitive()->count();
        $recentLogs = AuditLog::online()->with('user')->latest()->limit(10)->get();

        $stats = [
            'total_logs' => $totalLogs,
            'critical_events' => $criticalLogs,
            'sensitive_operations' => $sensitiveLogs,
            'pending_alerts' => AuditLogAlert::pending()->count(),
        ];

        $eventCounts = AuditLog::online()
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'event')
            ->toArray();

        $userActivity = AuditLog::online()
            ->with('user')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'user' => $log->user?->email ?? 'System',
                    'count' => $log->count,
                ];
            });

        return view('admin.audit-logs.dashboard', [
            'stats' => $stats,
            'recentLogs' => $recentLogs,
            'eventCounts' => $eventCounts,
            'userActivity' => $userActivity,
        ]);
    }

    /**
     * List audit logs with filtering and search
     */
    public function index(Request $request): View
    {
        $query = AuditLog::online()->with('user');

        // Filters
        if ($request->input('user_id')) {
            $query->byUser($request->input('user_id'));
        }

        if ($request->input('event')) {
            $query->byEvent($request->input('event'));
        }

        if ($request->input('level')) {
            $query->byLevel($request->input('level'));
        }

        if ($request->input('start_date') && $request->input('end_date')) {
            $query->dateRange($request->input('start_date'), $request->input('end_date'));
        }

        if ($request->input('search')) {
            $query->search($request->input('search'));
        }

        $logs = $query->latest()->paginate(50);

        $users = \App\Models\User::select('id', 'email')->orderBy('email')->get();
        $events = AuditLog::distinct('event')->pluck('event')->sort();
        $levels = ['info', 'warning', 'critical'];
        $savedSearches = AuditLogSearch::where('user_id', Auth::id())->get();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'users' => $users,
            'events' => $events,
            'levels' => $levels,
            'savedSearches' => $savedSearches,
        ]);
    }

    /**
     * Show audit log details
     */
    public function show(int $id): View
    {
        $log = AuditLog::with('user', 'alerts')->findOrFail($id);

        return view('admin.audit-logs.show', [
            'log' => $log,
        ]);
    }

    /**
     * Save search filters
     */
    public function saveSearch(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_public' => 'boolean',
        ]);

        $filters = $request->only(['user_id', 'event', 'level', 'start_date', 'end_date', 'search']);

        AuditLogSearch::create([
            'user_id' => Auth::id(),
            'name' => $request->input('name'),
            'filters' => $filters,
            'query' => $request->input('search'),
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()->back()->with('success', 'Search saved successfully');
    }

    /**
     * Load saved search
     */
    public function loadSearch(int $searchId): RedirectResponse
    {
        $search = AuditLogSearch::findOrFail($searchId);

        // Verify access
        if ($search->user_id !== Auth::id() && !$search->is_public && !Auth::user()->is_admin) {
            abort(403);
        }

        // Redirect with filter parameters
        return redirect()->route('admin.audit-logs.index', $search->filters);
    }

    /**
     * Export audit logs
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'format' => 'required|in:csv,pdf',
        ]);

        $filters = $request->only(['user_id', 'event', 'level', 'start_date', 'end_date', 'search']);
        $data = $this->auditLogService->exportLogs($filters, $request->input('format'));

        if ($request->input('format') === 'csv') {
            return $this->exportCSV($data);
        }

        return $this->exportPDF($data);
    }

    /**
     * Export to CSV
     */
    protected function exportCSV($data): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=audit-logs-' . now()->format('Y-m-d-His') . '.csv',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            if (!empty($data)) {
                // Write header
                fputcsv($file, array_keys($data[0]));

                // Write rows
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF (basic implementation)
     */
    protected function exportPDF($data): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename=audit-logs-' . now()->format('Y-m-d-His') . '.pdf',
        ];

        // For a full PDF implementation, use a library like barryvdh/laravel-dompdf
        // For now, returning simple CSV as PDF
        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            if (!empty($data)) {
                fputcsv($file, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Archive old logs
     */
    public function archiveLogs(): RedirectResponse
    {
        try {
            $archived = $this->auditLogService->archiveOldLogs();

            return redirect()->back()
                ->with('success', "{$archived} audit logs archived successfully");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Archiving failed: ' . $e->getMessage());
        }
    }

    /**
     * View archived logs
     */
    public function archived(Request $request): View
    {
        $query = AuditLog::archived()->with('user');

        if ($request->input('search')) {
            $query->search($request->input('search'));
        }

        $logs = $query->latest()->paginate(50);

        return view('admin.audit-logs.archived', [
            'logs' => $logs,
        ]);
    }

    /**
     * Verify log integrity (check checksums)
     */
    public function verifyIntegrity(): RedirectResponse
    {
        $logs = AuditLog::online()->get();
        $tampered = 0;

        foreach ($logs as $log) {
            if (!$log->verifyChecksum()) {
                $tampered++;
            }
        }

        $message = $tampered > 0
            ? "⚠️ WARNING: {$tampered} audit logs appear to have been tampered with!"
            : 'All audit logs verified successfully';

        return redirect()->back()
            ->with($tampered > 0 ? 'warning' : 'success', $message);
    }
}
