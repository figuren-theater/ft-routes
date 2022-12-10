<?php
/**
 * Figuren_Theater Routes.
 *
 * @package figuren-theater/routes
 */

namespace Figuren_Theater\Routes;

use Altis;
use function Altis\register_module;

use function add_action;
use function apply_filters;

/**
 * Register module.
 */
function register() {

	$default_settings = [
		'enabled' => true, // needs to be set
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
 */
function bootstrap() {



	add_action( 'init', __NAMESPACE__ . '\\set_rewrite_bases', 0 );

	// Plugins
	Mercator\bootstrap();
	
	// Best practices
	Disable_Public_JSON_REST_API\bootstrap();
	Network_Site_Url_Fix\bootstrap();
	Noblogredirect_Fix\bootstrap();
	Virtual_Uploads\bootstrap();
}


/**
 * [set_rewrite_base description]
 *
 * @global  WP_Rewrite $wp_rewrite WordPress rewrite component.
 */
function set_rewrite_bases() : void {

	/**
	 * @todo  [$ft_rr description]
	 * @var [type]
	 */
	$ft_rr = apply_filters( 
		__NAMESPACE__ . '\\rewrite_bases',
		[
			'author_base'              => 'von',
			'search_base'              => 'suche',
			'pagination_base'          => 'seite',
			'comments_base'            => 'kommentare',
			'comments_pagination_base' => 'kommentar-seite',
		],
	);

	array_map( 
		function( $rr_prop, $ft_rr_value ) : void {
			global $wp_rewrite;
			$wp_rewrite->$rr_prop = $ft_rr_value;
		}, 
		array_keys( $ft_rr ),
		$ft_rr
	);
}


