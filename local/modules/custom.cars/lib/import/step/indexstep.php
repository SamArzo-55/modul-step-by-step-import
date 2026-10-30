<?php

namespace Custom\Cars\Import\Step;

use Custom\Cars\Config\ImportSettings;
use Custom\Cars\Import\StepResult;
use Custom\Cars\Xlsx\ElementScanner;
use Custom\Cars\Xlsx\StringIndexWriter;
use Custom\Cars\Xlsx\XmlText;

final class IndexStep extends SessionStep
{
    protected function execute(string $token, array $state): StepResult
    {
        $total = (int) $state['stringsSize'];

        if ($total <= 0 || !empty($state['stringsDone'])) {
            return StepResult::completed(1, 'The shared strings are ready.');
        }

        $writer = new StringIndexWriter(
            $this->store->path($token, 'str'),
            $this->store->path($token, 'idx'),
            (int) $state['strBytes'],
            (int) $state['idxBytes']
        );

        try {
            $scan = (new ElementScanner($this->store->path($token, 'strings.xml'), 'si'))->scan(
                (int) $state['stringsPos'],
                ImportSettings::INDEX_BATCH,
                $this->deadline(),
                static function (string $xml) use ($writer): void {
                    $writer->append(XmlText::textOf($xml));
                }
            );
        } finally {
            $writer->close();
        }

        $sizes = $writer->sizes();
        $state['stringsPos'] = $scan['position'];
        $state['strBytes'] = $sizes['data'];
        $state['idxBytes'] = $sizes['offsets'];
        $state['stringsDone'] = $scan['eof'];
        $this->store->save($token, $state);

        $summary = 'Shared strings indexed: ' . intdiv($sizes['offsets'], 4) . '.';

        return $scan['eof']
            ? StepResult::completed($total, $summary)
            : StepResult::progress($scan['position'], $total, $summary);
    }
}
