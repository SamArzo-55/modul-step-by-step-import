<?php

namespace Custom\Cars\Import\Step;

use Custom\Cars\Config\Car;
use Custom\Cars\Config\ImportSettings;
use Custom\Cars\Import\HeaderMapper;
use Custom\Cars\Import\ProcessLock;
use Custom\Cars\Import\RowsFile;
use Custom\Cars\Import\SessionStore;
use Custom\Cars\Import\StepResult;
use Custom\Cars\Service\Validation;
use Custom\Cars\Xlsx\RowReader;
use Custom\Cars\Xlsx\StringIndexReader;
use RuntimeException;

final class ParseStep extends SessionStep
{
    private HeaderMapper $headers;
    private RowsFile $rowsFile;

    public function __construct(?SessionStore $store = null, ?ProcessLock $lock = null, ?HeaderMapper $headers = null, ?RowsFile $rowsFile = null)
    {
        parent::__construct($store, $lock);

        $this->headers = $headers ?? new HeaderMapper();
        $this->rowsFile = $rowsFile ?? new RowsFile();
    }

    protected function execute(string $token, array $state): StepResult
    {
        $size = (int) $state['sheetSize'];

        if (!empty($state['parseDone'])) {
            return StepResult::completed($size, $this->summary($state));
        }

        $strings = (int) $state['stringsSize'] > 0
            ? new StringIndexReader($this->store->path($token, 'str'), $this->store->path($token, 'idx'))
            : null;

        $batch = (new RowReader($this->store->path($token, 'sheet.xml'), $strings))->read(
            (int) $state['sheetPos'],
            ImportSettings::PARSE_BATCH_ROWS,
            (int) $state['emptyRun'],
            (int) $state['lastRow'],
            $this->deadline()
        );

        $records = $this->toRecords($state, $batch['rows']);

        $state['rowsBytes'] = $this->rowsFile->append($this->store->path($token, 'rows'), $records, (int) $state['rowsBytes']);
        $state['total'] += count($records);
        $state['sheetPos'] = $batch['position'];
        $state['emptyRun'] = $batch['emptyRun'];
        $state['lastRow'] = $batch['lastRow'];

        if ($batch['eof']) {
            $this->assertHasData($state);
            $state['parseDone'] = true;
        }

        $this->store->save($token, $state);

        return $batch['eof']
            ? StepResult::completed($size, $this->summary($state))
            : StepResult::progress($batch['position'], $size, $this->summary($state));
    }

    private function toRecords(array &$state, array $rows): array
    {
        $records = [];

        foreach ($rows as $number => $cells) {
            if (empty($state['headerDone'])) {
                $state['columns'] = $this->headers->columns($cells);
                $this->assertRequiredColumns($state['columns']);
                $state['headerDone'] = true;
                continue;
            }

            $record = [];

            foreach ($state['columns'] as $index => $code) {
                $record[$code] = trim((string) ($cells[$index] ?? ''));
            }

            if (implode('', $record) !== '') {
                $record['_ROW'] = $number;
                $records[] = $record;
            }
        }

        return $records;
    }

    private function assertRequiredColumns(array $columns): void
    {
        $missing = [];

        foreach (Validation::REQUIRED as $code) {
            if (!in_array($code, $columns, true)) {
                $missing[] = Car::label($code);
            }
        }

        if ($missing) {
            throw new RuntimeException('Required columns not found in the file: ' . implode(', ', $missing) . '.');
        }
    }

    private function assertHasData(array $state): void
    {
        if (empty($state['headerDone'])) {
            throw new RuntimeException('The first worksheet is empty.');
        }

        if ((int) $state['total'] === 0) {
            throw new RuntimeException('The file has a header but no data rows.');
        }
    }

    private function summary(array $state): string
    {
        return 'Rows found: ' . (int) $state['total'] . '.';
    }
}
