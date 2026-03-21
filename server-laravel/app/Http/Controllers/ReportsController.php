<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Reports\AuditReport;
use App\Reports\ComplianceReport;
use App\Reports\OfficePerformanceReport;
use App\Reports\PipelineReport;
use App\Reports\TurnaroundReport;
use App\Services\ReportAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parse period query param → [periodStart, periodEnd] strings.
     * Accepts: week | month | quarter | year | custom (requires date_from + date_to).
     */
    private function parsePeriod(Request $request): array
    {
        $period = $request->query('period', 'month');
        $now    = Carbon::now();

        if ($period === 'custom') {
            $from = $request->query('date_from')
                ? Carbon::parse($request->query('date_from'))->startOfDay()
                : $now->copy()->startOfMonth();
            $to   = $request->query('date_to')
                ? Carbon::parse($request->query('date_to'))->endOfDay()
                : $now->copy()->endOfMonth();
            return [$from->toDateTimeString(), $to->toDateTimeString()];
        }

        [$start, $end] = match ($period) {
            'week'    => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year'    => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        return [$start->toDateTimeString(), $end->toDateTimeString()];
    }

    /**
     * Resolve the office_ids filter, honouring access scope.
     * Returns null if admin with no filter (unrestricted), or array otherwise.
     */
    private function resolveOfficeIds(Request $request): ?array
    {
        $user = $request->user();
        $accessible = ReportAccessService::accessibleOfficeIds($user);

        $requested = $request->query('office_ids');
        if ($requested) {
            $requestedArr = is_array($requested)
                ? $requested
                : explode(',', $requested);

            if ($accessible === null) {
                return $requestedArr; // admin can see any
            }
            // Intersect requested with accessible
            return array_values(array_intersect($requestedArr, $accessible));
        }

        return $accessible; // null = admin unrestricted, array = scoped
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/office-performance
    // ─────────────────────────────────────────────────────────────────────────
    public function officePerformance(Request $request)
    {
        [$periodStart, $periodEnd] = $this->parsePeriod($request);
        $officeIds = $this->resolveOfficeIds($request);

        $data = OfficePerformanceReport::getData($officeIds, $periodStart, $periodEnd);

        return response()->json([
            'success'      => true,
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'offices'      => ReportAccessService::accessibleOffices($request->user()),
            'data'         => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/office-performance/export?format=pdf|xlsx
    // ─────────────────────────────────────────────────────────────────────────
    public function officePerformanceExport(Request $request)
    {
        [$periodStart, $periodEnd] = $this->parsePeriod($request);
        $officeIds = $this->resolveOfficeIds($request);
        $format    = strtolower($request->query('format', 'pdf'));

        $data = OfficePerformanceReport::getData($officeIds, $periodStart, $periodEnd);

        if ($format === 'xlsx') {
            $export = new ReportExport(
                'Office Performance',
                OfficePerformanceReport::columns(),
                OfficePerformanceReport::toRows($data)
            );
            return Excel::download($export, 'office-performance.xlsx');
        }

        $pdf = Pdf::loadView('reports.office-performance', [
            'data'         => $data,
            'periodStart'  => substr($periodStart, 0, 10),
            'periodEnd'    => substr($periodEnd, 0, 10),
            'generatedAt'  => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('office-performance.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/pipeline
    // ─────────────────────────────────────────────────────────────────────────
    public function pipeline(Request $request)
    {
        $officeIds = $this->resolveOfficeIds($request);
        $status    = $request->query('status');

        $data = PipelineReport::getData($officeIds, $status);

        return response()->json([
            'success' => true,
            'offices' => ReportAccessService::accessibleOffices($request->user()),
            'data'    => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/pipeline/export?format=pdf|xlsx
    // ─────────────────────────────────────────────────────────────────────────
    public function pipelineExport(Request $request)
    {
        $officeIds = $this->resolveOfficeIds($request);
        $status    = $request->query('status');
        $format    = strtolower($request->query('format', 'pdf'));

        $data = PipelineReport::getData($officeIds, $status);

        if ($format === 'xlsx') {
            $export = new ReportExport(
                'Pipeline',
                PipelineReport::columns(),
                PipelineReport::toRows($data)
            );
            return Excel::download($export, 'pipeline.xlsx');
        }

        $pdf = Pdf::loadView('reports.pipeline', [
            'data'        => $data,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pipeline.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/compliance
    // ─────────────────────────────────────────────────────────────────────────
    public function compliance(Request $request)
    {
        [$periodStart, $periodEnd] = $this->parsePeriod($request);
        $officeIds = $this->resolveOfficeIds($request);

        $data = ComplianceReport::getData($officeIds, $periodStart, $periodEnd);

        return response()->json([
            'success'      => true,
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'data'         => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/compliance/export?format=pdf|xlsx
    // ─────────────────────────────────────────────────────────────────────────
    public function complianceExport(Request $request)
    {
        [$periodStart, $periodEnd] = $this->parsePeriod($request);
        $officeIds = $this->resolveOfficeIds($request);
        $format    = strtolower($request->query('format', 'pdf'));

        $data = ComplianceReport::getData($officeIds, $periodStart, $periodEnd);

        if ($format === 'xlsx') {
            $rows = ComplianceReport::flatRows($data);
            $headings = array_shift($rows);
            $export   = new ReportExport('ISO Compliance', $headings, $rows);
            return Excel::download($export, 'compliance.xlsx');
        }

        $pdf = Pdf::loadView('reports.compliance', [
            'data'         => $data,
            'generatedAt'  => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('compliance.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/audit/{docNo}
    // ─────────────────────────────────────────────────────────────────────────
    public function audit(Request $request, string $docNo)
    {
        // Access: must be able to see the origin office of this document
        $docRow = \Illuminate\Support\Facades\DB::table('documents')
            ->where('document_no', $docNo)
            ->select('office_id')
            ->first();

        if (!$docRow) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        if (!ReportAccessService::canAccessOffice($request->user(), $docRow->office_id)) {
            return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
        }

        $data = AuditReport::getData($docNo);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/audit/{docNo}/export
    // PDF ONLY — never Excel (rule #29)
    // ─────────────────────────────────────────────────────────────────────────
    public function auditExport(Request $request, string $docNo)
    {
        $docRow = \Illuminate\Support\Facades\DB::table('documents')
            ->where('document_no', $docNo)
            ->select('office_id')
            ->first();

        if (!$docRow) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        if (!ReportAccessService::canAccessOffice($request->user(), $docRow->office_id)) {
            return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
        }

        $data = AuditReport::getData($docNo);

        $pdf = Pdf::loadView('reports.audit', [
            'data'        => $data,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("audit-{$docNo}.pdf");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/turnaround
    // ─────────────────────────────────────────────────────────────────────────
    public function turnaround(Request $request)
    {
        [$periodStart, $periodEnd] = $this->parsePeriod($request);
        $officeIds = $this->resolveOfficeIds($request);

        $data = TurnaroundReport::getData($officeIds, $periodStart, $periodEnd);

        return response()->json([
            'success'      => true,
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'offices'      => ReportAccessService::accessibleOffices($request->user()),
            'data'         => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reports/turnaround/export?format=pdf|xlsx
    // ─────────────────────────────────────────────────────────────────────────
    public function turnaroundExport(Request $request)
    {
        [$periodStart, $periodEnd] = $this->parsePeriod($request);
        $officeIds = $this->resolveOfficeIds($request);
        $format    = strtolower($request->query('format', 'pdf'));

        $data = TurnaroundReport::getData($officeIds, $periodStart, $periodEnd);

        if ($format === 'xlsx') {
            $export = new ReportExport(
                'Turnaround',
                TurnaroundReport::columns(),
                TurnaroundReport::toRows($data)
            );
            return Excel::download($export, 'turnaround.xlsx');
        }

        $pdf = Pdf::loadView('reports.turnaround', [
            'data'        => $data,
            'periodStart' => substr($periodStart, 0, 10),
            'periodEnd'   => substr($periodEnd, 0, 10),
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('turnaround.pdf');
    }
}
