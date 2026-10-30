<?php

namespace Custom\Cars\Config;

final class ImportSettings
{
    public const BATCH_SIZE = 1000;

    public const BATCH_SECONDS = 20;

    public const TIME_LIMIT = 120;

    public const SESSION_TTL = 86400;

    public const LOOKUP_CHUNK = 500;

    public const XML_CHUNK = 1048576;

    public const PARSE_BATCH_ROWS = 5000;

    public const INDEX_BATCH = 20000;

    public const MAX_EMPTY_ROWS = 100;
}
