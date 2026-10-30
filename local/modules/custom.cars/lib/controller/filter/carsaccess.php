<?php

namespace Custom\Cars\Controller\Filter;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Custom\Cars\Service\AccessChecker;

final class CarsAccess extends Base
{
    public const READ = 'read';
    public const ADD = 'add';

    private string $permission;
    private AccessChecker $checker;

    public function __construct(string $permission = self::READ, ?AccessChecker $checker = null)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        $this->permission = $permission;
        $this->checker = $checker ?? new AccessChecker();
    }

    public function onBeforeAction(Event $event)
    {
        $allowed = $this->permission === self::ADD ? $this->checker->canAdd() : $this->checker->canRead();

        if ($allowed) {
            return null;
        }

        $this->addError(new Error('Access denied.', 'ACCESS_DENIED'));

        return new EventResult(EventResult::ERROR, null, null, $this);
    }
}
