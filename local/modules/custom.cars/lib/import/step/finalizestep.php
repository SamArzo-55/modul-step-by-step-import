<?php

namespace Custom\Cars\Import\Step;

use Custom\Cars\Import\SessionStore;
use Custom\Cars\Import\StepResult;

final class FinalizeStep
{
    private SessionStore $store;

    public function __construct(?SessionStore $store = null)
    {
        $this->store = $store ?? new SessionStore();
    }

    public function run(string $token): StepResult
    {
        $state = $this->store->state($token);

        if ($state === null) {
            return StepResult::completed(1, 'The import is finished.');
        }

        $this->store->delete($token);

        return StepResult::completed(1, 'Imported ' . (int) $state['imported'] . ', skipped ' . (int) $state['skipped'] . '.');
    }
}
