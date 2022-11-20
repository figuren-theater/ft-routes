<?php
/**
 * The WordPress Core drop-in file sunrise.php
 *
 * It loads very early in the WordPress loading sequence,
 * before mu-plugins, any active plugins, and the active theme.
 * Because of this, what can be done in sunrise.php is limited,
 * and largely confined to executing pure PHP to set constants that override WordPress Core behavior.
 *
 * @package figuren-theater/routes
 * @since   1.0.0
 * @author  Carsten Bach  <mail@carsten-bach.de>
 */

declare(strict_types=1);

$_ft_mercator_path = FT_VENDOR_DIR . '/humanmade/';

/**
 * When trying to log in with an aliased domain, cookie domain is incorrect. 
 * Probably Mercator\SSO\initialize_cookie_domain needs to run whether SSO is disabled or not,
 * currently it will not load if sso is disabled.
 *
 * @see  https://github.com/humanmade/Mercator/issues/48#issuecomment-162063572
 * @see  https://github.com/humanmade/Mercator/issues/48#issuecomment-344936603 Alternative
 */
add_filter( 'mercator.sso.enabled', '__return_false' );
add_action( 'muplugins_loaded', 'Mercator\\SSO\\initialize_cookie_domain' );


// NOTE include gui.php before mercator.php itself!
require $_ft_mercator_path . 'mercator-gui/gui.php';
require $_ft_mercator_path . 'mercator/mercator.php';

unset( $_ft_mercator_path );
