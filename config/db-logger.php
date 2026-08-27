<?php

return [
    'connection' => env('DB_LOGGER_CONNECTION', null),
    'table' => env('DB_LOGGER_TABLE', 'logs'),
    'name' => env('DB_LOGGER_NAME', 'fingo-application'), //Default logger name (`logger` field in the database)
];
