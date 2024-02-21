<?php
/**
 * Figuren_Theater Routes.
 *
 * @package figuren-theater/ft-routes
 */

namespace Figuren_Theater\Routes;

use Altis;
use function add_action;

use function apply_filters;

/**
 * Register module.
 *
 * @return void
 */
function register(): void {

	$default_settings = [
		'enabled' => true, // Needs to be set.
	];

	$options = [
		'defaults' => $default_settings,
	];

	Altis\register_module(
		'routes',
		DIRECTORY,
		'Routes',
		$options,
		__NAMESPACE__ . '\\bootstrap'
	);
}

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap(): void {

	add_action( 'init', __NAMESPACE__ . '\\set_rewrite_bases', 0 );

	// Plugins.
	Mercator\bootstrap();

	// Best practices.
	Disable_Public_JSON_REST_API\bootstrap();
	// @todo #9 This should|could be removed completely Network_Site_Url_Fix\bootstrap(); // DISABLED for being done by the .htaccess.
	Noblogredirect_Fix\bootstrap();
	Virtual_Uploads\bootstrap();
}

/**
 * [set_rewrite_base description]
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 */
function set_rewrite_bases(): void {

	/**
	 * Replace default rewrite bases esp. for german-speaking websites.
	 *
	 * @param array $ft_rr Rewrite key and human-readable rewrite replacement.
	 */
	$ft_rr = apply_filters(
		__NAMESPACE__ . '\\rewrite_bases',
		[
			'author_base'              => 'von',
			'search_base'              => 'suche',
			'pagination_base'          => 'seite',
			'comments_base'            => 'kommentare',
			'comments_pagination_base' => 'kommentar-seite',
		]
	);

	array_map(
		function ( $rr_prop, $ft_rr_value ): void {
			global $wp_rewrite;
			$wp_rewrite->$rr_prop = $ft_rr_value;
		},
		array_keys( $ft_rr ),
		$ft_rr
	);
}
