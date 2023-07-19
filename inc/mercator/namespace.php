<?php
/**
 * Figuren_Theater Routes Mercator.
 *
 * @package figuren-theater/ft-routes
 */

namespace Figuren_Theater\Routes\Mercator;

use Cache_Enabler;
use function add_action;
use function add_filter;
use function get_current_blog_id;
use function get_option;
use function is_main_site;
use function is_wp_error;
use Mercator;

/**
 * 'Mercator' itself is (by design) directly required by sunrise.php.
 *
 * This file contains related stuff that needs to be loaded with Mercator.
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {

	add_action( 'init', __NAMESPACE__ . '\\filter_canonical_urls_for_aliases' );
}

/**
 * Add hooks to modify the canonical URL, even for aliased domains.
 *
 * @return void
 */
function filter_canonical_urls_for_aliases() : void {
	add_filter( 'get_canonical_url', __NAMESPACE__ . '\\canonical_urls_for_aliases', 5 );
	add_filter( 'wpseo_canonical', __NAMESPACE__ . '\\canonical_urls_for_aliases', 5 );

	// Also delete the files of cached site-aliases.
	add_action( 'cache_enabler_site_cache_cleared', __NAMESPACE__ . '\\clear_alias_site_cache', 10, 3 );
}

/**
 * Get un-aliased orignal URLs, if requesting from within an aliased URL.
 *
 * @param     string $original_url Original URL of anything (post, term, etc.).
 * @return    string               Un-Aliased original URL
 */
function canonical_urls_for_aliases( string $original_url ) : string {

	$site_id            = get_current_blog_id();
	$current_mapping    = ( isset( $GLOBALS['mercator_current_mapping'] ) ) ? $GLOBALS['mercator_current_mapping'] : '';
	$mapped_site_domain = parse_url( $original_url ); // As a fallback.
	$mapped_site_domain = ( isset( $mapped_site_domain['host'] ) ) ? $mapped_site_domain['host'] : '';

	if ( empty( $current_mapping ) && ! is_main_site() ) {
		return $original_url;
	}

	if ( is_object( $current_mapping ) && $site_id !== $current_mapping->get_site_id() ) {
		return $original_url;
	}

	if ( is_object( $current_mapping ) ) {
		$mapped_site_domain = $current_mapping->get_domain();
	}

	$unmapped_domain = get_option( 'siteurl' );

	if ( ! \is_string( $unmapped_domain ) ) {
		return $original_url;
	}

	$url_wo_scheme   = str_replace( [ 'https://', 'http://' ], '', $unmapped_domain );
	$canonical_url   = str_replace( $mapped_site_domain, $url_wo_scheme, $original_url );

	return $canonical_url;
}

/**
 * Also delete the files of cached site-aliases.
 *
 * Fires after the site cache has been cleared.
 *
 * @since  1.6.0
 * @since  1.8.0  The `$cache_cleared_index` parameter was added.
 *
 * @param  string        $site_cleared_url     Full URL of the site cleared.
 * @param  int           $site_cleared_id      Post ID of the site cleared.
 * @param  array<mixed>  $cache_cleared_index  Index of the cache cleared.
 *
 * @return void
 */
function clear_alias_site_cache( string $site_cleared_url, int $site_cleared_id, array $cache_cleared_index ) :void {

	$mappings = Mercator\Mapping::get_by_site( $site_cleared_id );

	if ( empty( $mappings ) || is_wp_error( $mappings ) ) {
		return;
	}

	$args = [];

	foreach ( $mappings as $mapping ) {
		$args['subpages']['exclude'] = [];
		$args['hooks']['include'] = 'cache_enabler_site_cache_cleared__ft_alias';

		Cache_Enabler::clear_page_cache_by_url(
			$mapping->get_domain(),
			$args
		);
	}
}
