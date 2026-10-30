<?php

namespace Custom\Cars\Import;

final class ProcessLock
{
    private $handle = null;

    public function acquire(string $path): bool
    {
        $handle = fopen($path, 'c');

        if (!$handle || !flock($handle, LOCK_EX | LOCK_NB)) {
            if ($handle) {
                fclose($handle);
            }

            return false;
        }

        $this->handle = $handle;

        return true;
    }

    public function release(): void
    {
        if ($this->handle !== null) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }
    }
}
