<?php
namespace Custom\Cars\Service;

use Bitrix\Crm\Item;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIMNotify;
use Custom\Cars\Config\Car;

final class DeletionNotifier
{
    public function notify(Item $item): void
    {
        Loader::requireModule('im');

        CIMNotify::Add([
            'TO_USER_ID' => $item->getAssignedById(),
            'FROM_USER_ID' => 0,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => Car::MODULE_ID,
            'NOTIFY_EVENT' => 'car_deleted',
            'NOTIFY_MESSAGE' => $this->buildMessage($item),
        ]);
    }

    private function buildMessage(Item $item): string
    {
        return sprintf(
            'Car record #%d was deleted at %s by %s.',
            $item->getId(),
            (new DateTime())->toString(),
            CurrentUser::get()->getFullName()
        );
    }
}
