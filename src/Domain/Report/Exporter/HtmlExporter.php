<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Report\Exporter;

use SmartAutoloadConverter\Domain\Report\Model\ConversionReport;

class HtmlExporter implements ReportExporterInterface
{
    public function getFormat(): string
    {
        return 'html';
    }

    public function export(ConversionReport $report): string
    {
        $data = $report->toArray();
        $status = $report->isSuccessful() ? '✅ Success' : '❌ Failed';
        $mode = $report->dryRun ? ' (Dry Run)' : '';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Smart Autoload Converter Report</title>
<style>
body{font-family:system-ui,sans-serif;max-width:900px;margin:2rem auto;padding:0 1rem;color:#1a1a2e}
h1{border-bottom:2px solid #0f3460}
table{width:100%;border-collapse:collapse;margin:1rem 0}
th,td{padding:.5rem;text-align:left;border-bottom:1px solid #ddd}
th{background:#0f3460;color:#fff}
.success{color:#16a34a}.failed{color:#dc2626}.skipped{color:#9ca3af}
.meta{background:#f3f4f6;padding:1rem;border-radius:8px;margin:1rem 0}
</style>
</head>
<body>
<h1>Smart Autoload Converter Report{$mode}</h1>
<div class="meta">
<p><strong>Status:</strong> {$status}</p>
<p><strong>Source:</strong> {$data['source_path']}</p>
<p><strong>Output:</strong> {$data['output_path']}</p>
<p><strong>Generated:</strong> {$data['generated_at']}</p>
<p><strong>Duration:</strong> {$data['total_duration_seconds']}s</p>
<p><strong>Files Changed:</strong> {$data['total_files_changed']}</p>
</div>
<h2>Pipeline Steps</h2>
<table>
<tr><th>Step</th><th>Status</th><th>Files</th><th>Duration</th><th>Error</th></tr>
HTML;

        foreach ($data['steps'] as $step) {
            $cssClass = match($step['status']) {
                'completed' => 'success',
                'failed' => 'failed',
                default => 'skipped',
            };
            $error = $step['error'] ?? '-';
            $html .= "<tr><td>{$step['name']}</td>";
            $html .= "<td class=\"{$cssClass}\">{$step['status']}</td>";
            $html .= "<td>{$step['files_changed']}</td>";
            $html .= "<td>{$step['duration_seconds']}s</td>";
            $html .= "<td>{$error}</td></tr>\n";
        }

        $html .= "</table>\n";

        if (!empty($data['manual_review_files'])) {
            $html .= "<h2>Manual Review Required</h2>\n<ul>\n";
            foreach ($data['manual_review_files'] as $file => $reason) {
                $html .= "<li><strong>{$file}</strong>: {$reason}</li>\n";
            }
            $html .= "</ul>\n";
        }

        $html .= "</body>\n</html>\n";

        return $html;
    }
}
