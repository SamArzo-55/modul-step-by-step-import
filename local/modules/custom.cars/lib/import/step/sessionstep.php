<?php

namespace Custom\Cars\Import\Step;

use Custom\Cars\Config\ImportSettings;
use Custom\Cars\Import\ProcessLock;
use Custom\Cars\Import\SessionStore;
use Custom\Cars\Import\StepResult;
use RuntimeException;

abstract class SessionStep
{
    protected SessionStore $store;
    private ProcessLock $lock;

    public function __construct(?SessionStore $store = null, ?ProcessLock $lock = null)
    {
        $this->store = $store ?? new SessionStore();
        $this->lock = $lock ?? new ProcessLock();
    }

    final public function run(string $token): StepResult
    {
        $state = $this->store->state($token);

        if ($state === null) {
            throw new RuntimeException('Import session not found. Upload the file again.');
        }

        if (!$this->lock->acquire($this->store->path($token, 'lock'))) {
            return StepResult::progress(0, 1, 'Another request is still working on this import, waiting...');
        }

        try {
            return $this->execute($token, $state);
        } finally {
            $this->lock->release();
        }
    }

    abstract protected function execute(string $token, array $state): StepResult;

    protected function deadline(): float
    {
        return microtime(true) + ImportSettings::BATCH_SECONDS;
    }
}
