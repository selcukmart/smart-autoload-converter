<?php

declare(strict_types=1);

namespace App\Domain\Report\Exporter;

use App\Domain\Report\Model\ConversionReport;

interface ReportExporterInterface
{
    public function export(ConversionReport $report): string;

    public function getFormat(): string;
}
