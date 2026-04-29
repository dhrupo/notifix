<?php
/**
 * Plugin Name: Notifix
 * Plugin URI: https://example.com/notifix
 * Description: Real-time social proof and event notification engine for WordPress.
 * Version: 0.1.0
 * Author: Codex
 * Author URI: https://example.com
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: notifix
 */

if (! defined('ABSPATH')) {
    exit;
}

define('RT_NOTIFY_VERSION', '1.0.0');
define('RT_NOTIFY_FILE', __FILE__);
define('RT_NOTIFY_PATH', plugin_dir_path(__FILE__));
define('RT_NOTIFY_URL', plugin_dir_url(__FILE__));

require_once RT_NOTIFY_PATH . 'includes/Support/Autoloader.php';
require_once RT_NOTIFY_PATH . 'includes/Support/helpers.php';

\RTNotify\Support\Autoloader::register();

register_activation_hook(RT_NOTIFY_FILE, [\RTNotify\Core\Plugin::class, 'activate']);
register_deactivation_hook(RT_NOTIFY_FILE, [\RTNotify\Core\Plugin::class, 'deactivate']);

add_action('plugins_loaded', static function () {
    rt_notify_plugin()->boot();
});
