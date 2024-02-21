<?php
/**
 * Figuren_Theater Routes Network_Site_Url_Fix.
 *
 * @package figuren-theater/ft-routes
 */

namespace Figuren_Theater\Routes\Network_Site_Url_Fix;

use FT_WP_DIR;

use function add_filter;

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap(): void {

	// Fix network admin URL to include the "/wp/" base.
	add_filter( 'network_site_url', __NAMESPACE__ . '\\network_site_url_incl_wp', 10 );
}

/**
 * Fix network admin URL to include the "/wp/" base.
 *
 * Filters the network site URL.
 *
 * @see https://core.trac.wordpress.org/ticket/23221
 * @author Daniel Bachhuber
 *
 * @param string $url    The complete network site URL including scheme and path.
 */
function network_site_url_incl_wp( string $url ): string {

	$urls_to_fix = [
		'/wp-admin/network/',
		'/wp-login.php',
		'/wp-activate.php',
		'/wp-signup.php',
	];
	foreach ( $urls_to_fix as $maybe_fix_url ) {
		$fixed_wp_url = FT_WP_DIR . $maybe_fix_url;
		if ( false !== stripos( $url, $maybe_fix_url )
			&& false === stripos( $url, $fixed_wp_url ) ) {
			$url = str_replace( $maybe_fix_url, $fixed_wp_url, $url );
		}
	}

	return $url;
}
