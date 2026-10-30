<?php

namespace Custom\Cars\Import\Step;

use Custom\Cars\Config\ImportSettings;
use Custom\Cars\Import\ProcessLock;
use Custom\Cars\Import\RowImporter;
use Custom\Cars\Import\RowsFile;
use Custom\Cars\Import\SessionStore;
use Custom\Cars\Import\StepResult;
use RuntimeException;
use Throwable;

final class ImportStep extends SessionStep
{
    private RowsFile $rowsFile;

    public function __construct(?SessionStore $store = null, ?ProcessLock $lock = null, ?RowsFile $rowsFile = null)
    {
        parent::__construct($store, $lock);

        $this->rowsFile = $rowsFile ?? new RowsFile();
    }

    protected function execute(string $token, array $state): StepResult
    {
        @set_time_limit(ImportSettings::TIME_LIMIT);

        $total = (int) $state['total'];

        if ($total <= 0) {
            throw new RuntimeException('There is nothing to import.');
        }

        $batch = $this->rowsFile->read(
            $this->store->path($token, 'rows'),
            (int) $state['pos'],
            min(ImportSettings::BATCH_SIZE, max(0, $total - (int) $state['offset']))
        );

        $importer = new RowImporter();
        $importer->prepare(array_filter(array_column($batch['entries'], 'row'), 'is_array'));

        $deadline = $this->deadline();
        $completed = true;

        foreach ($batch['entries'] as $entry) {
            if (microtime(true) >= $deadline) {
                $completed = false;
                break;
            }

            $errors = $this->importRow($importer, $entry['row']);

            if ($errors) {
                $state['skipped']++;
                $state['sampleError'] ??= 'Row ' . ($entry['row']['_ROW'] ?? '?') . ': ' . implode(' ', $errors);
            } else {
                $state['imported']++;
            }

            $state['offset']++;
            $state['pos'] = $entry['end'];
            $this->store->save($token, $state);
        }

        if ($completed && $batch['exhausted']) {
            $state['offset'] = $total;
            $this->store->save($token, $state);
        }

        return $this->result($state, $total);
    }

    private function result(array $state, int $total): StepResult
    {
        $summary = 'Imported ' . (int) $state['imported'] . ', skipped ' . (int) $state['skipped'] . ' of ' . $total . ' rows.';
        $extra = $state['skipped'] ? ['WARNING' => 'Skipped rows: ' . (int) $state['skipped'] . '. First problem: ' . $state['sampleError']] : [];

        return (int) $state['offset'] >= $total
            ? StepResult::completed($total, $summary, $extra)
            : StepResult::progress((int) $state['offset'], $total, $summary, $extra);
    }

    private function importRow(RowImporter $importer, ?array $row): array
    {
        if ($row === null) {
            return ['Row data is damaged.'];
        }

        try {
            return $importer->import($row);
        } catch (Throwable $e) {
            return [$e->getMessage()];
        }
    }
}
