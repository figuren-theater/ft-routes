<?php
/**
 * Figuren_Theater Routes Mercator.
 *
 * @package figuren-theater/routes/mercator
 */

namespace Figuren_Theater\Routes\Mercator;

use function add_action;
use function add_filter;
use function apply_filters;
use function get_current_blog_id;
use function get_option;
use function is_main_site;

/**
 * 'Mercator' itself is (by design) directly required by sunrise.php.
 *
 * This file contains related stuff that needs to be loaded with Mercator.
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'init', __NAMESPACE__ . '\\filter_canonical_urls_for_aliases' );
}


function filter_canonical_urls_for_aliases() : void {
	add_filter( 'get_canonical_url', __NAMESPACE__ . '\\canonical_urls_for_aliases', 5 );
	add_filter( 'wpseo_canonical', __NAMESPACE__ . '\\canonical_urls_for_aliases', 5 );
}

/**
 * [canonical_urls_for_aliases description]
 *
 * @package   Figuren_Theater
 * @version   2022-10-17
 * @author    Carsten Bach
 *
 * @param     string $original_url [description].
 * @return    [type]                     [description]
 */
function canonical_urls_for_aliases( string $original_url ) : string {

	$site_id            = get_current_blog_id();
	$current_mapping    = ( isset( $GLOBALS['mercator_current_mapping'] ) ) ? $GLOBALS['mercator_current_mapping'] : '';
	$mapped_site_domain = parse_url( $original_url ); // as fallback
	$mapped_site_domain = ( isset( $mapped_site_domain['host'] ) ) ? $mapped_site_domain['host'] : '';

	if ( empty( $current_mapping ) && ! is_main_site() )
		return $original_url;

	if ( is_object( $current_mapping ) && $site_id !== $current_mapping->get_site_id() )
		return $original_url;

	if ( is_object( $current_mapping ) )
		$mapped_site_domain = $current_mapping->get_domain();

	$url_wo_scheme = str_replace( [ 'https://', 'http://' ], '', get_option( 'siteurl' ) );
	$canonical_url = str_replace( $mapped_site_domain, $url_wo_scheme, $original_url );

	return $canonical_url;
}
