<?php

namespace Custom\Cars\Controller;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;

abstract class BaseController extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [
            new Authentication(),
            new HttpMethod([HttpMethod::METHOD_POST]),
            new Csrf(),
        ];
    }
}
