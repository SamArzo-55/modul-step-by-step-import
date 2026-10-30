<?php

namespace Custom\Cars\Import\Step;

use Custom\Cars\Import\SessionStore;
use Custom\Cars\Import\StepResult;
use Custom\Cars\Xlsx\Package;
use RuntimeException;
use Throwable;

final class UploadStep
{
    private SessionStore $store;

    public function __construct(?SessionStore $store = null)
    {
        $this->store = $store ?? new SessionStore();
    }

    public function run(?array $file): StepResult
    {
        $code = (int) ($file['error'] ?? -1);

        if (!$file || $code !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The file was not uploaded (error code ' . $code . '). Check upload_max_filesize and post_max_size.');
        }

        $package = new Package((string) $file['tmp_name']);
        $token = $this->store->create();

        try {
            $state = $this->store->state($token);
            $state['sheetSize'] = $package->extract($package->sheetPath(), $this->store->path($token, 'sheet.xml'));

            $strings = $package->sharedStringsPath();
            $state['stringsSize'] = $strings === null ? 0 : $package->extract($strings, $this->store->path($token, 'strings.xml'));

            $this->store->save($token, $state);
        } catch (Throwable $e) {
            $this->store->delete($token);

            throw $e;
        } finally {
            $package->close();
        }

        return StepResult::completed(1, 'The file is uploaded.', ['TOKEN' => $token]);
    }
}
