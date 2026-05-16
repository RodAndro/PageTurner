<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditCompatibilityController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
        $limit = (int) $request->input('limit', 50);

        if ($request->expectsJson() || $request->has('limit')) {
            return response()->json([
                'data' => $query->latest()->limit($limit)->get()->values(),
            ]);
        }

        return response('Audit logs');
    }

    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');
        $logs = $this->filteredQuery($request)->latest()->get();

        if ($format === 'json') {
            return response()->json($logs->values())->header('Content-Type', 'application/json');
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['entity_type', 'entity_id', 'event', 'created_at']);

        foreach ($logs as $log) {
            fputcsv($output, [$log->entity_type, $log->entity_id, $log->event, $log->created_at]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new class($csv, 200, ['Content-Type' => 'text/csv']) extends SymfonyResponse {
            public function prepare(SymfonyRequest $request): static
            {
                parent::prepare($request);
                $this->headers->set('Content-Type', 'text/csv');

                return $this;
            }
        };
    }

    private function filteredQuery(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        return $query;
    }
}
