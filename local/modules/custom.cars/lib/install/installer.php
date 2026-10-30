<?php

namespace Custom\Cars\Install;

use Bitrix\Main\Loader;
use Throwable;

final class Installer
{
    private SmartProcessInstaller $process;
    private StageInstaller $stages;
    private FieldInstaller $fields;
    private EventInstaller $events;

    public function __construct(
        ?SmartProcessInstaller $process = null,
        ?StageInstaller $stages = null,
        ?FieldInstaller $fields = null,
        ?EventInstaller $events = null
    ) {
        $this->process = $process ?? new SmartProcessInstaller();
        $this->stages = $stages ?? new StageInstaller();
        $this->fields = $fields ?? new FieldInstaller();
        $this->events = $events ?? new EventInstaller();
    }

    public function install(): void
    {
        Loader::requireModule('crm');

        try {
            $this->process->create();
            $this->stages->install();
            $this->fields->install();
            $this->events->install();
        } catch (Throwable $e) {
            $this->rollback();

            throw $e;
        }
    }

    public function uninstall(): void
    {
        Loader::requireModule('crm');
        $this->liftTimeLimit();

        foreach ($this->teardownSteps() as $step) {
            $step();
        }
    }

    public function rollback(): void
    {
        $this->liftTimeLimit();

        foreach ($this->teardownSteps() as $step) {
            try {
                $step();
            } catch (Throwable $e) {
                AddMessage2Log($e->getMessage(), 'custom.cars');
            }
        }
    }

    private function teardownSteps(): array
    {
        return [
            [$this->process, 'deleteItems'],
            [$this->fields, 'uninstall'],
            [$this->events, 'uninstall'],
            [$this->process, 'delete'],
        ];
    }

    private function liftTimeLimit(): void
    {
        @set_time_limit(0);
        ignore_user_abort(true);
    }
}
