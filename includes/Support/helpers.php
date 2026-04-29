<?php

use RTNotify\Core\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

function rt_notify_plugin(): Plugin
{
    return Plugin::instance();
}

function rt_notify_emit(array $event): void
{
    do_action('rt_notify_event', $event);
}

function rt_notify_settings(?string $path = null, $default = null)
{
    return rt_notify_plugin()->settings()->get($path, $default);
}
