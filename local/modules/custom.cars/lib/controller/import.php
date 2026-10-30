<?php

namespace Custom\Cars\Controller;

use Bitrix\Main\Error;
use Custom\Cars\Controller\Filter\CarsAccess;
use Custom\Cars\Import\Step\FinalizeStep;
use Custom\Cars\Import\Step\ImportStep;
use Custom\Cars\Import\Step\IndexStep;
use Custom\Cars\Import\Step\ParseStep;
use Custom\Cars\Import\Step\UploadStep;
use Custom\Cars\Import\StepResult;
use Throwable;

final class Import extends BaseController
{
    public function configureActions(): array
    {
        $access = ['+prefilters' => [new CarsAccess(CarsAccess::ADD)]];

        return [
            'uploadFile' => $access,
            'indexStrings' => $access,
            'parseSheet' => $access,
            'importRows' => $access,
            'finalize' => $access,
            'cancel' => $access,
        ];
    }

    public function uploadFileAction(): ?array
    {
        return $this->respond(fn (): StepResult => (new UploadStep())->run($this->getRequest()->getFile('file')));
    }

    public function indexStringsAction(string $token = ''): ?array
    {
        return $this->respond(fn (): StepResult => (new IndexStep())->run($token));
    }

    public function parseSheetAction(string $token = ''): ?array
    {
        return $this->respond(fn (): StepResult => (new ParseStep())->run($token));
    }

    public function importRowsAction(string $token = ''): ?array
    {
        return $this->respond(fn (): StepResult => (new ImportStep())->run($token));
    }

    public function finalizeAction(string $token = ''): ?array
    {
        return $this->respond(fn (): StepResult => (new FinalizeStep())->run($token));
    }

    public function cancelAction(string $token = ''): ?array
    {
        return $this->respond(fn (): StepResult => (new FinalizeStep())->run($token));
    }

    private function respond(callable $step): ?array
    {
        try {
            return $step()->toArray();
        } catch (Throwable $e) {
            $this->addError(new Error($e->getMessage(), 'IMPORT_FAILED'));

            return null;
        }
    }
}
