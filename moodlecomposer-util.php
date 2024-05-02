<?php

global $CFG;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function moodlecomposer_get_env($varname, $default = null)
{
    if (php_sapi_name() === 'cli') {
        if (isset($_ENV[$varname])) {
            return $_ENV[$varname];
        }
        if (getenv($varname)) {
            return getenv($varname);
        }
    }

    if (isset($_ENV[$varname])) {
        return $_ENV[$varname];
    }

    return $default;
}
