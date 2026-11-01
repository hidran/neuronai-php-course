<?php
declare(strict_types=1);
namespace App;
final class RunGuard
{
    private bool $canceled = false;
    public function __construct(private readonly float $deadline)
    {
    }
    public function cancel(): void
    {
        $this->canceled = true;
    }
    public function stopped(): bool
    {
        return $this->canceled || microtime(true) > $this->deadline;
    }
    public function check(): void
    {
        if ($this->canceled) {
            throw new AuditCanceled('canceled by the user');
        }
        if (microtime(true) > $this->deadline) {
            throw new AuditFailed('the audit deadline was reached');
        }
    }
}
