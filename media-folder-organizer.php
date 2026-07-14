<?php
/**
 * Plugin Name: Media Folder Organizer
 * Plugin URI: https://github.com/helloHupc/media-folder-organizer
 * Description: Organize WordPress media attachments in unlimited virtual folders with drag-and-drop ordering and media modal filtering.
 * Version: 1.0.4
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: hupc
 * Author URI: https://github.com/helloHupc
 * License: MIT
 * License URI: https://opensource.org/license/mit
 * Text Domain: media-folder-organizer
 */

defined( 'ABSPATH' ) || exit;

define( 'MFO_VERSION', '1.0.4' );
define( 'MFO_FILE', __FILE__ );
define( 'MFO_PATH', plugin_dir_path( __FILE__ ) );
define( 'MFO_URL', plugin_dir_url( __FILE__ ) );

require_once MFO_PATH . 'includes/class-mfo-taxonomy.php';
require_once MFO_PATH . 'includes/class-mfo-rest-controller.php';
require_once MFO_PATH . 'includes/class-mfo-admin.php';
require_once MFO_PATH . 'includes/class-mfo-plugin.php';

register_activation_hook( __FILE__, array( 'MFO_Plugin', 'activate' ) );

MFO_Plugin::instance();
