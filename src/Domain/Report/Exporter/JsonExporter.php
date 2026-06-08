<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Report\Exporter;

use SmartAutoloadConverter\Domain\Report\Model\ConversionReport;

class JsonExporter implements ReportExporterInterface
{
    public function getFormat(): string
    {
        return 'json';
    }

    public function export(ConversionReport $report): string
    {
        return json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
