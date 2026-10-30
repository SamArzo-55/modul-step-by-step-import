<?php

namespace Custom\Cars\Import;

final class StepResult
{
    private string $status;
    private int $processed;
    private int $total;
    private string $summary;

    private array $extra;

    private function __construct(string $status, int $processed, int $total, string $summary, array $extra)
    {
        $this->status = $status;
        $this->total = max(1, $total);
        $this->processed = min($processed, $this->total);
        $this->summary = $summary;
        $this->extra = $extra;
    }

    public static function progress(int $processed, int $total, string $summary, array $extra = []): self
    {
        return new self('PROGRESS', $processed, $total, $summary, $extra);
    }

    public static function completed(int $total, string $summary, array $extra = []): self
    {
        return new self('COMPLETED', $total, $total, $summary, $extra);
    }

    public function toArray(): array
    {
        return [
            'STATUS' => $this->status,
            'PROCESSED_ITEMS' => $this->processed,
            'TOTAL_ITEMS' => $this->total,
            'SUMMARY' => $this->summary,
        ] + $this->extra;
    }
}
