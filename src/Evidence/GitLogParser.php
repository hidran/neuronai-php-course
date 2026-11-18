<?php
declare(strict_types=1);
namespace App\Evidence;
final readonly class GitLogParser
{
    public const string FIELD = "\x1f";
    public const string RECORD = "\x1e";
    public const int MAX_BYTES = 6000;
    public static function facts(
        string $output,
        int $days,
        int $max,
    ): array {
        $records = array_values(array_filter(
            array_map(trim(...), explode(self::RECORD, $output)),
            fn (string $record): bool => $record !== '',
        ));
        $commits = [];
        $bytes = 0;
        foreach (array_slice($records, 0, $max) as $record) {
            [$id, $date, $author, $subject] =
                explode(self::FIELD, $record, 4) + ['', '', '', ''];
            $commit = [
                'id' => $id,
                'date' => $date,
                'author' => mb_substr($author, 0, 60),
                'subject' => mb_substr($subject, 0, 120),
            ];
            $bytes += strlen(json_encode($commit, JSON_THROW_ON_ERROR));
            if ($bytes > self::MAX_BYTES) {
                break;
            }
            $commits[] = $commit;
        }
        return [
            'window_days' => $days,
            'max_commits' => $max,
            'max_bytes' => self::MAX_BYTES,
            'truncated' => count($commits) < count($records),
            'commits' => $commits,
            'author_names' => array_values(array_unique(
                array_column($commits, 'author'),
            )),
            'author_note' => 'display names, not verified identities',
        ];
    }
}
