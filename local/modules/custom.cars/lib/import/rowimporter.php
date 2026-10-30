<?php

namespace Custom\Cars\Import;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Custom\Cars\Config\Car;
use Custom\Cars\Repository\VinRepository;
use Custom\Cars\Service\RegistrationDateFiller;
use Custom\Cars\Service\Validation;
use Custom\Cars\Service\VinNormalizer;
use RuntimeException;
use Throwable;

final class RowImporter
{
    private Factory $factory;
    private ValueConverter $converter;
    private UserResolver $users;
    private ContactResolver $contacts;
    private VinRepository $vins;
    private Validation $validation;
    private RegistrationDateFiller $registrationDate;
    private ?int $defaultUserId = null;

    public function __construct(
        ?ValueConverter $converter = null,
        ?UserResolver $users = null,
        ?ContactResolver $contacts = null,
        ?VinRepository $vins = null,
        ?RegistrationDateFiller $registrationDate = null
    ) {
        $factory = Container::getInstance()->getFactory(Car::entityTypeId());

        if ($factory === null) {
            throw new RuntimeException('Car smart process is not installed.');
        }

        $this->factory = $factory;
        $this->converter = $converter ?? new ValueConverter();
        $this->users = $users ?? new UserResolver();
        $this->contacts = $contacts ?? new ContactResolver();
        $this->vins = $vins ?? new VinRepository();
        $this->validation = new Validation($this->vins);
        $this->registrationDate = $registrationDate ?? new RegistrationDateFiller();
    }

    public function prepare(array $rows): void
    {
        $this->vins->preload(array_map('strval', array_column($rows, 'VIN')));
    }

    public function import(array $row): array
    {
        $item = $this->factory->createItem([
            Item::FIELD_NAME_TITLE => $this->title($row),
            Item::FIELD_NAME_ASSIGNED => $this->users->resolve((string) ($row['RESPONSIBLE'] ?? '')) ?? $this->defaultUserId(),
        ]);

        $errors = $this->fillFields($item, $row);

        $this->registrationDate->fill($item);
        $errors = array_merge($errors, $this->validation->validate($item)->getErrorMessages());

        return $errors ?: $this->save($item, $row);
    }

    private function fillFields(Item $item, array $row): array
    {
        $errors = [];

        foreach (array_intersect_key($row, Car::FIELDS) as $code => $value) {
            if ($value === '') {
                continue;
            }

            [$converted, $error] = $this->converter->convert($code, (string) $value);

            if ($error !== null) {
                $errors[] = $error;
                continue;
            }

            $item->set(Car::fieldName($code), $converted);
        }

        return $errors;
    }

    private function save(Item $item, array $row): array
    {
        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            $contactId = $this->contacts->resolve((string) ($row['CONTACT'] ?? ''), (string) ($row['PHONE'] ?? ''));

            if ($contactId !== null) {
                $item->setContactId($contactId);
            }

            $operation = $this->factory->getAddOperation($item);
            $operation->disableCheckWorkflows()->disableCheckFields();
            $result = $operation->launch();

            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();
                $this->contacts->revert();

                return $result->getErrorMessages() ?: ['Item was not saved.'];
            }

            $connection->commitTransaction();
            $this->contacts->confirm();
            $this->vins->remember(VinNormalizer::normalize((string) ($row['VIN'] ?? '')));

            return [];
        } catch (Throwable $e) {
            $connection->rollbackTransaction();
            $this->contacts->revert();

            return [$e->getMessage()];
        }
    }

    private function title(array $row): string
    {
        $title = (string) ($row['TITLE'] ?? '');

        return $title !== '' ? $title : trim(($row['BRAND'] ?? '') . ' ' . ($row['MODEL'] ?? ''));
    }

    private function defaultUserId(): int
    {
        return $this->defaultUserId ??= (int) CurrentUser::get()->getId();
    }
}
