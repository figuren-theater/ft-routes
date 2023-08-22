<?php
/**
 * Figuren_Theater Routes Disable_Public_JSON_REST_API.
 *
 * @package figuren-theater/ft-routes
 */

namespace Figuren_Theater\Routes\Disable_Public_JSON_REST_API;

use function add_action;
use function add_filter;
use function is_user_logged_in;
use function remove_action;

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {

	add_action( 'init', __NAMESPACE__ . '\\load', 0 );

}

/**
 * Load the modifications to the REST API.
 *
 * @return void
 */
function load() :void {

	// Disable some endpoints for unauthenticated users.
	add_filter( 'rest_endpoints', __NAMESPACE__ . '\\disable_default_endpoints', 1000 );

	/*
	 * Remove REST API info from head and HTTP-headers
	 * taken from
	 * https://gist.github.com/timwhitlock/ef62645c41ca61718fb2be7adcb641c6
	 * https://github.com/dmchale/disable-json-api/blob/master/disable-json-api.php
	 *
	 * nice explanation
	 * und furter infos
	 * https://wordpress.stackexchange.com/questions/211467/remove-json-api-links-in-header-html
	 *
	 * also remove actions added by wp-includes/default-filters.php
	 */
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );

	// Disables oembed, which needs the REST API.
	remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
}

/*
 * Directly from the 'REST API Handbook'
 * https://developer.wordpress.org/rest-api/frequently-asked-questions/#require-authentication-for-all-requests
 *
 * and originated in this interesting (!) thread
 * https://stackoverflow.com/questions/41191655/safely-disable-wp-rest-api
 *
 * Can I disable the REST API?
 *
 * You should not disable the REST API;
 * doing so will break WordPress Admin functionality
 * that depends on the API being active.
 * However, you may use a filter to require that
 * API consumers be authenticated,
 * which effectively prevents anonymous external access.
 * See below for more information.
 *
 * Require Authentication for All Requests
 *
 * You can require authentication for all REST API requests
 * by adding an is_user_logged_in check to
 * the rest_authentication_errors filter.
 *
 * ```
 * add_filter( 'rest_authentication_errors', function( $result ) {
 * 	// If a previous authentication check was applied,
 * 	// pass that result along without modification.
 * 	if ( true === $result || is_wp_error( $result ) ) {
 * 		return $result;
 * 	}
 *
 * 	// No authentication has been performed yet.
 * 	// Return an error if user is not logged in.
 * 	if ( ! is_user_logged_in() ) {
 * 		return new WP_Error(
 * 			'rest_not_logged_in',
 * 			__( 'You are not currently logged in.' ),
 * 			array( 'status' => 401 )
 * 		);
 * 	}
 *
 * 	// Our custom authentication check should have no effect
 * 	// on logged-in requests
 * 	return $result;
 * });
 * ```
*/

/**
 * Filters the array of available REST API endpoints.
 *
 * AGAIN inspired by the great stackoverflow-post
 * change the filter to make it a little less forcing
 *
 * Disable some endpoints for unauthenticated users.
 *
 * @param array<string, mixed> $endpoints The available endpoints. An array of matching regex patterns, each mapped to an array of callbacks for the endpoint. These take the format or `'/path/regex' => array( array( $callback, $bitmask ).
 *
 * @return array<string, mixed> $endpoints
 */
function disable_default_endpoints( array $endpoints ) :array {

	if ( is_user_logged_in() ) {
		return $endpoints;
	}

	$endpoints_to_remove = [
		'/wp-site-health/v1',

		/**
		 * Disable oembeds of f.t-domains for privacy reasons
		 *
		 * @todo #8
		 *
		 * '/oembed/1.0',
		 */

		// '/wp/v2',
		'/wp/v2/media',
		'/wp/v2/types',
		'/wp/v2/statuses',
		'/wp/v2/taxonomies',
		'/wp/v2/tags',
		'/wp/v2/users',
		'/wp/v2/comments',
		'/wp/v2/settings',
		'/wp/v2/themes',
		'/wp/v2/sidebars',
		'/wp/v2/widget-types',
		'/wp/v2/widgets',
		'/wp/v2/plugins',
		'/wp/v2/blocks',
		// '/wp/v2/oembed',
		'/wp/v2/posts',
		'/wp/v2/pages',
		'/wp/v2/block-renderer',
		'/wp/v2/search',
		'/wp/v2/categories',
		'/wp/v2/menu-locations',

		'/wp/v2/global-styles',
		'/wp/v2/pattern-directory',
		'/wp/v2/block-directory',
		'/wp/v2/block-patterns',
		'/wp/v2/block-types',
		'/wp/v2/templates',
		'/wp/v2/template-parts',
		'/wp-block-editor',

		'/menu-items',
		'/wp/v2/navigation',
		'/wp/v2/menus',
		'/wp/v2/menu-items',

		'/__experimental',
		'/yoast',
		'/yoast/v1',
		'/yoast/v1/file_size',
		'/yoast/v1/statistics',
		'/yoast/v1/alerts',
		'/yoast/v1/configuration',
		'/yoast/v1/import',
		'/yoast/v1/indexing',
		'/yoast/v1/link-indexing',
		'/yoast/v1/integrations',
		'/yoast/v1/meta',
		'/yoast/v1/wincher',
		'/yoast/v1/workouts',

		'/koko-analytics/v1',
		'/koko-analytics/v1/stats',
		'/koko-analytics/v1/posts',
		'/koko-analytics/v1/referrers',
		'/koko-analytics/v1/settings',
		'/koko-analytics/v1/realtime',
		'/koko-analytics/v1/reset',

		'/wp/v2/ft_theme',

		'/wp/v2/ft_milestone',

		'/wp/v2/ft_product',

		'/wp-site-health/v1/tests',
		'/wp-site-health/v1/directory-sizes',

		'/wp-block-editor/v1',
		'/wp-block-editor/v1/url-details',
		'/wp-block-editor/v1/export',

		'/wpmn/v1',
		'/wpmn/v1/networks',

		'/wp/v2/distributor',
		'/wp/v2/dt_meta',
		'/wp/v2/dt_subscription',

		'/wp/v2/ft_site',
		'/wp/v2/ft_site_shadow',
		'/wp/v2/ft_production',
		'/wp/v2/ft_production_shadow',
		'/wp/v2/tb_prod_subsite',

		'/wp/v2/ft_feature',
		'/wp/v2/ft_feature_shadow',
		'/wp/v2/ft_level',
		'/wp/v2/ft_level_shadow',

		'/wp/v2/ft_link',
		'/wp/v2/link_category',

		'/wp/v2/ft_geolocation',
		'/wp/v2/ft_az_index',

		'/wp/v2/hm-utility',

	];

	/**
	 * Filters the endpoints that will be removed from the default stack.
	 *
	 * @param array  $endpoints_to_remove List of REST API endpoints that will be made un-available to the public.
	 */
	$endpoints_to_remove = apply_filters( __NAMESPACE__ . '\\endpoints_to_remove', $endpoints_to_remove );

	$endpoints = array_filter(
		$endpoints,
		function( $k ) use ( $endpoints_to_remove ) {

			// Reduce keys.
			$_k = explode( '/(?P', $k );
			$k  = $_k[0];
			// Our exclusion list is max. 3 levels deep
			// so we have to make sure
			// the keys to compare against
			// match this length at maximum.
			$_ks    = explode( '/', $k );
			$_count = count( $_ks );

			// With 5 or more parts,
			// we had 4 or more slashes.
			if ( 4 < $_count ) {
				$k = join( '/', array_slice( $_ks, 0, 4 ) );
			}

			$_ep = array_flip( $endpoints_to_remove );
			return ! isset( $_ep[ $k ] );
		},
		ARRAY_FILTER_USE_KEY
	);

	return $endpoints;
}
