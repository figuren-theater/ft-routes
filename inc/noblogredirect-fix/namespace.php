<?php
/**
 * Figuren_Theater Routes Noblogredirect_Fix.
 *
 * @package figuren-theater/ft-routes
 */

namespace Figuren_Theater\Routes\Noblogredirect_Fix;

use function remove_action;

use NOBLOGREDIRECT;

/**
 * Now those 404 pages are redirecting to the 404 page not found template for your theme
 * and bad subdomains entered will go to whereever you specified in the NOBLOGREDIRECT.
 *
 * Bootstrap module, when enabled.
 *
 * @see  http://frumph.net/wordpress/wordpress-3-0-multisite-subdomain-installation-noblogredirect-behavior-fix/
 *
 * @return void
 */
function bootstrap() :void {

	if ( defined( 'NOBLOGREDIRECT' ) && NOBLOGREDIRECT ) {
		/**
		 * FIX NOBLOGREDIRECT interferring with 404
		 *
		 * @see  wp\wp-includes\ms-functions.php#L2224
		 */
		remove_action( 'template_redirect', 'maybe_redirect_404' );
	}
}

