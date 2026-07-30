<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use App\Support\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $report = ReportService::build($request->query('period', 'monthly'));

        return view('Admin::reports', [
            ...$report,
            'periods' => ReportService::PERIODS,
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    // Print-optimised HTML for "Save as PDF" from the browser (no server-side
    // PDF dependency added to this production app).
    public function printable(Request $request): View
    {
        return view('Admin::report-print', ReportService::build($request->query('period', 'monthly')));
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $format = $request->query('format', 'csv');
        $report = ReportService::build($request->query('period', 'monthly'));

        Log::channel('admin_security')->info('Report exported', [
            'ip' => $request->ip(), 'period' => $report['period'], 'format' => $format,
        ]);

        return $format === 'excel'
            ? $this->excel($report)
            : $this->csv($report);
    }

    private function filename(array $report, string $ext): string
    {
        return 'gullakpe-report-'.$report['period'].'-'.now()->format('Ymd').'.'.$ext;
    }

    /** @param array{label:string,from:\Carbon\CarbonImmutable,to:\Carbon\CarbonImmutable,metrics:array} $report */
    private function rows(array $report): array
    {
        $rows = [];
        foreach ($report['metrics'] as $m) {
            $rows[] = [
                $m['label'],
                $m['count'] !== null ? (string) $m['count'] : '',
                $m['amount'] !== null ? number_format($m['amount'], 2, '.', '') : '',
            ];
        }

        return $rows;
    }

    private function csv(array $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['GullakPe '.$report['label'].' Report']);
            fputcsv($out, ['Range', $report['from']->format('d M Y H:i'), $report['to']->format('d M Y H:i')]);
            fputcsv($out, []);
            fputcsv($out, ['Metric', 'Count', 'Amount (₹)']);
            foreach ($this->rows($report) as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $this->filename($report, 'csv'), ['Content-Type' => 'text/csv']);
    }

    // Excel-readable export with zero dependencies: an HTML table served as
    // .xls with the ms-excel content type opens natively in Excel/Sheets.
    private function excel(array $report): Response
    {
        $rowsHtml = '';
        foreach ($this->rows($report) as $row) {
            $rowsHtml .= '<tr><td>'.e($row[0]).'</td><td>'.e($row[1]).'</td><td>'.e($row[2]).'</td></tr>';
        }

        $html = '<table border="1"><thead>'
            .'<tr><th colspan="3">GullakPe '.e($report['label']).' Report</th></tr>'
            .'<tr><td colspan="3">'.e($report['from']->format('d M Y H:i')).' — '.e($report['to']->format('d M Y H:i')).'</td></tr>'
            .'<tr><th>Metric</th><th>Count</th><th>Amount (INR)</th></tr>'
            .'</thead><tbody>'.$rowsHtml.'</tbody></table>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($report, 'xls').'"',
        ]);
    }
}
