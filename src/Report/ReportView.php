<?php
declare(strict_types=1);
namespace App\Report;
final readonly class ReportView
{
    public static function render(AuditReport $report): string
    {
        $lines = ["Score: {$report->score}/100", $report->summary,
            ''];
        foreach ($report->checks as $check) {
            $lines[] = sprintf('  %-12s %s', $check->check,
                $check->status);
        }
        $lines[] = '';
        foreach ($report->findings as $finding) {
            $lines[] = sprintf('[%s] %s: %s',
                strtoupper($finding->severity), $finding->category,
                $finding->description);
            $lines[] = "    -> {$finding->recommendation}"
                . ' (' . implode(', ', $finding->evidence) . ')';
        }
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
