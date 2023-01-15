<?php
/**
 * Plugin Name:     figuren.theater | Routes
 * Plugin URI:      https://github.com/figuren-theater/ft-routes
 * Description:     Modern tales of browser headers, domains, mappings and its pathes. Including some (still needed) historic patches for a nice WordPress Multisite setup like figuren.theater.
 * Author:          figuren.theater
 * Author URI:      https://figuren.theater
 * Text Domain:     figurentheater
 * Domain Path:     /languages
 * Version:         1.0.4
 *
 * @package         figuren-theater/routes
 */

namespace Figuren_Theater\Routes;

const DIRECTORY = __DIR__;

add_action( 'altis.modules.init', __NAMESPACE__ . '\\register' );
