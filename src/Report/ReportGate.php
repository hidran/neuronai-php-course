<?php
declare(strict_types=1);
namespace App\Report;
use App\Evidence\EvidenceLog;
final readonly class ReportGate
{
    /** @return list<string> */
    public static function problems(
        AuditReport $report,
        EvidenceLog $log,
        array $rubric,
    ): array {
        $problems = [];
        if ($report->score !== $rubric['score']) {
            $problems[] = "score {$report->score} is not the rubric "
                . "score {$rubric['score']}";
        }
        foreach ($report->findings as $finding) {
            foreach ($finding->evidence ?: ['(none)'] as $id) {
                if ($log->get($id) === null) {
                    $problems[] = "unknown evidence {$id}";
                }
            }
        }
        $reported = [];
        foreach ($report->checks as $check) {
            $reported[$check->evidence] = $check->status;
        }
        foreach ($log->all() as $id => $item) {
            if (($reported[$id] ?? null) !== $item['status']) {
                $problems[] = "check {$id} must be {$item['status']}";
            }
        }
        return $problems;
    }
}
