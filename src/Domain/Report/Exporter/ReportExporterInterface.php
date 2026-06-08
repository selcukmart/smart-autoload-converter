<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Report\Exporter;

use SmartAutoloadConverter\Domain\Report\Model\ConversionReport;

interface ReportExporterInterface
{
    public function export(ConversionReport $report): string;

    public function getFormat(): string;
}
