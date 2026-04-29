<?php

namespace RTNotify\Support;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $class): void
    {
        $prefix = 'RTNotify\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
        $file = RT_NOTIFY_PATH . 'includes/' . $relative . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
