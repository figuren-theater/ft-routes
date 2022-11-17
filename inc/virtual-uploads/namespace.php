<?php
/**
 * Figuren_Theater Routes Virtual_Uploads.
 *
 * @package figuren-theater/routes/virtual_uploads
 */

namespace Figuren_Theater\Routes\Virtual_Uploads;

use FT_ROOT_DIR;

use ABSPATH;
use WP_CONTENT_DIR;

use Mercator\Mapping;
use function Mapping\get_by_site;

use function add_action;
use function add_filter;
use function apply_filters;
use function get_current_blog_id;
use function get_site_url;
use function get_sites;
use function got_mod_rewrite;
use function insert_with_markers;
use function is_super_admin;
use function wp_list_pluck;

/**
 * Name of the virtual uploads folder per domain.
 *
 * Normally in a WPMU all uploads are saved to 
 * a folderstructure including the site_ID.
 * 
 * This is not nice and could be a security concern.
 * In addition it's not very helpful for SEO 
 * when all attachments are hosted on a different domain.
 *
 * So let's give each domain a personell, but virtual folder
 * directly in the root of the domain. 
 * 
 * This folder is rewritten to WordPress' native folders.
 */
const FOLDER = '__media';


/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'init', __NAMESPACE__ . '\\load', 0 );
}


/**
 *  (CORE-BUG) Non-WP rewrites not saved on a multisite install 
 *  
 *  @see https://core.trac.wordpress.org/ticket/19896
 *  @date 2011 !!!!
 *  
 * Some clarification here though:
 * 
 * add_rewrite_rule(  
 *     sanitize_title( $region ) . '/([0-9]{4})/([0-9]{1,2})/([^/]+)(?:/([0-9]+))?/?$',
 *     'index.php?year=$matches[1]&monthnum=$matches[2]&name=$matches[3]&page=$matches[4]' ,
 *     'top'
 * );
 * 
 * works because the index.php? makes it a wp_rewrite
 * 
 * Without it, it's considered a non wp rule and would be written to .htaccess.
 * However with multisite, that doesn't/can't happen so this doesn't even 
 * register as a rewrite rule as it is never written to .htaccess.
 * 
 * add_rewrite_rule(  
 *     sanitize_title( $region ) . '/?$', 
 *     '$matches[1]' ,
 *     'top'
 * );
 *
 * add_rewrite_rule('^uploads/([^/]*)?','content/uploads/sites/'.get_current_blog_id().'/$1','top');
 */
function load() {

	// filter (visible) URL path from
	// assets.figuren.theater/uploads/site/(ID)/2022/03/some-image.jpg
	// to a domain-specific folder called
	// domain.tld/__media/2022/03/some-image.jpg
	add_filter( 'upload_dir', __NAMESPACE__ . '\\filter__upload_dir', 0 );

	// write rewrite-rules to .htaccess
	// on all following actions
	$_action_hooks = [
		// when Site is created
		'wp_initialize_site',
		// when homeurl|siteurl is updated
		'update_option_siteurl',
		'update_option_home',
		'update_option_rewrite_rules',
		// when Domainmapping is CUD
		'mercator.mapping.created',
		'mercator.mapping.updated',
		'mercator.mapping.deleted',
		// MU actions
		'make_spam_blog',
		'make_ham_blog',
		'archive_blog',
		'unarchive_blog',
		// when Site is deleted
		'make_delete_blog',
		'make_undelete_blog',
	];

	array_map( 
		function( string $action ) : void {
			add_action( $action, __NAMESPACE__ . '\\update_htaccess', 910 );
		}, 
		$_action_hooks
	);

}


/**
 * '__media' is a ugly hardcoded virtual directory
 *
 * ... to help with proper rewrite rules for media below 
 * the prefered domainname of the currently viewed site.
 *
 * It is used and needs to be updated at the following locations:
 * - /.htaccess
 * - /content/mu-plugins/FT/ft-routes/inc/virtual-uploads/namespace.php
 * - /content/mu-plugins/Figuren_Theater/src/FeaturesRepo/UtilityFeature__managed_core_options.php
 *
 * @package [package]
 * @since   2.10
 *
 * @param  array $upload_dir [description]
 * 
 * @return array             [description]
 */
function filter__upload_dir( array $upload_dir ) : array {
	$old_baseurl           = $upload_dir['baseurl'];
	$upload_dir['baseurl'] = get_site_url( null, '/' . FOLDER, 'https' );
	$upload_dir['url']     = str_replace( $old_baseurl, $upload_dir['baseurl'], $upload_dir['url'] );

	// change default location for uploads of the primary blog
	if ( 1 === get_current_blog_id() ) {
		$upload_dir['basedir'] = WP_CONTENT_DIR . '/uploads/sites/1';
		$upload_dir['path']    = $upload_dir['basedir'] . $upload_dir['subdir'];
	}
	return $upload_dir;
}

/**
 * Write the site id as a mapping ref for __media
 * everytime a new site is created.
 *
 * CLONED (and modified) from //wp-admin\includes\misc.php#L237
 * 
 * Updates the htaccess file with the current rules if it is writable.
 *
 * Always writes to the file if it exists and is writable to ensure that we
 * blank out old rules.
 *
 * @since 1.5.0
 *
 * @return bool|null True on write success, false on failure.
 */
function update_htaccess() {

	if ( ! is_super_admin() )
		return;

	// Ensure get_home_path() is declared.
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$htaccess_file = FT_ROOT_DIR . '/.htaccess';

	/*
	 * Check wether it is needed, allowed and possible 
	 * to update the root .htaccess file.
	 */
	if ( ! can_update_htaccess( $htaccess_file ) )
		return false;

	add_filter( 'insert_with_markers_inline_instructions', __NAMESPACE__ . '\\htaccess_instructions', 10, 2 );

	return insert_with_markers( 
		$htaccess_file, 
		__NAMESPACE__, 
		explode( "\n", generate_htaccess_rules() )
	);
}


/**
 * Check wether it is needed, allowed and possible 
 * to update the root .htaccess file.
 * 
 * If the file doesn't already exist check for write access to the directory
 * and whether we have some rules. Else check for write access to the file.
 * 
 * @package [package]
 * @since   2.11
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param  string $htaccess Absolute path to root .htaccess file.
 *
 * @return bool             Allowed to write to .htaccess?
 */
function can_update_htaccess( string $htaccess ) : bool {

	global $wp_rewrite;

	// Ensure got_mod_rewrite() is declared.
	require_once ABSPATH . 'wp-admin/includes/misc.php';

	if ( ! got_mod_rewrite() )
		return false;

	if ( ! file_exists( $htaccess ) && is_writable( dirname( $htaccess ) ) && $wp_rewrite->using_mod_rewrite_permalinks() )
		return true;

	if ( is_writable( $htaccess ) )
		return true;

	return false;
}



/**
 * Prevent l10n of htaccess dev-instructions via insert_with_markers().
 *
 * Normally the lines returned by this function come localised from WordPress core,
 * which leads to regular but unnneccessary changes of the htaccess file.
 *
 * This filter prevents this behaviour.
 *
 * @package [package]
 * @since   2.11
 *
 * @param   array  $instructions Array of comment lines to add next to our htaccess rules.
 * @param   string $marker       Name of the marker referenced.
 * 
 * @return  array                Unchanged, but un-localised default text.
 */
function htaccess_instructions( array $instructions, string $marker ) : array {
	return [
		'#----------------------------------------------------------------------',
		'# Handle upload_url redirects with custom URLs',
		'# based on __media',
		'#',
		'# "__media" is a ugly hardcoded virtual directory',
		'#',
		'# ... to help with proper rewrite rules for media below ',
		'# the prefered domainname of the currently viewed site.',
		'#',
		'# It is used and needs to be updated at the following locations:',
		'# - /.htaccess (automatically done)',
		'# - /content/mu-plugins/FT/ft-routes/inc/virtual-uploads/namespace.php',
		'# - /content/mu-plugins/Figuren_Theater/src/FeaturesRepo/UtilityFeature__managed_core_options.php',
		'#',
		"# The directives (lines) between 'BEGIN $marker' and 'END $marker' are",
		'# dynamically generated, and should only be modified via WordPress filters.',
		'# Any changes to the directives between these markers will be overwritten.',
		'#----------------------------------------------------------------------',
	];
}

/**
 * Retrieves mod_rewrite-formatted rewrite rules to write to .htaccess.
 *
 * Does not actually write to the .htaccess file, but creates the rules for
 * the process that will.
 *
 * Will add the non_wp_rules property rules to the .htaccess file before
 * the WordPress rewrite rules one.
 *
 * @since 1.5.0
 *
 * @return string
 */
function generate_htaccess_rules() : string {

	$args = [
		'orderby'       => 'path_length',
		// 'orderby'       => 'path',
		// 'order'       => 'DESC',
		'no_found_rows' => false,
	];

	// get all our sites
	$ft_sites = wp_list_pluck( get_sites( $args ), 'siteurl', 'id' );

	$rules  = "<IfModule mod_rewrite.c>\n";
	$rules .= "RewriteEngine On\n";
	$rules .= "RewriteCond %{REQUEST_URI} !^/\.well\-known/acme\-challenge/\n\n";

	// Add in the rules that don't redirect to WP's index.php (and thus shouldn't be handled by WP at all).
	foreach ( $ft_sites as $site_id => $domain ) {
		$rules .= generate_domain_rule( $site_id, $domain );
	}

	/**
	 * NOT WORKING # all requests, that are not '/__media/'
	 *  RewriteCond %{REQUEST_URI} !^/__media/$ [NC]
	 *  
	 * WORKING # all requests, that do not only be '/__media/', 
	 * without following folders and file.
	 */
	// $rules .= 'RewriteCond %{REQUEST_URI} !^/(' . FOLDER . ")/$ [NC]\n";
	$rules .= 'RewriteCond %{REQUEST_URI} !/(' . FOLDER . ")/$ [NC]\n";
	// $rules .= 'RewriteCond %{REQUEST_URI} ^/' . FOLDER . "/(.*)$ [NC]\n";
	$rules .= 'RewriteCond %{REQUEST_URI} /' . FOLDER . "/(.*)$ [NC]\n";
	// $rules .= 'RewriteRule ^' . FOLDER . "/(.*)$ content/uploads/sites/%{ENV:FT_SITE_ID}/$1 [L]\n";
	$rules .= 'RewriteRule ' . FOLDER . "/(.*)$ content/uploads/sites/%{ENV:FT_SITE_ID}/$1 [L]\n";

	$rules .= '</IfModule>';

	/**
	 * Filters the list of rewrite rules formatted for output to an .htaccess file.
	 *
	 * @since 1.5.0
	 *
	 * @param string $rules mod_rewrite Rewrite rules formatted for .htaccess.
	 */
	return apply_filters( __NAMESPACE__ . '\\generate_htaccess_rules', $rules );
}


function generate_domain_rule( int $site_id, string $domain ) : string {

	// $mappings = Mercator\Mapping::get_by_site( $site_id );
	$mappings = \Mercator\Mapping::get_by_site( $site_id );
	$rules    = '';

	if ( ! empty( $mappings ) ) {
		foreach ( $mappings as $mapping ) {
			$rules .= 'RewriteCond %{HTTP_HOST} ^' . _mask( $mapping->get_domain() ) . "\.(.*)$ [NC,OR]\n";
		}
	}

	$rules .= 'RewriteCond %{HTTP_HOST} ^' . _mask( $domain ) . "\.(.*)$ [NC]\n";

	$maybepath = _is_subdir_install( $domain );
	if ( $maybepath ) {
		$rules .= 'RewriteCond %{REQUEST_URI} ^' . $maybepath . "/(.*)$ [NC]\n";
	}

	$rules .= 'RewriteRule . - [E=FT_SITE_ID:' . $site_id . "]\n\n";

	return $rules;
}




function _mask( string $url ) : string {

	// url 2 array
	$_url = explode( '.', $url );
	// clean up the first array entry, as it contains the scheme 'https://'
	$_url = str_replace( [ 'https://', 'http://' ], '', $_url );
	// chop off the last array entry eg '.test|.theater|.whatever'
	array_pop( $_url );

	// join it back together with masked dots ;)
	return join( '\.', $_url );
}


function _is_subdir_install( string $url ) : string|bool {
	// url 2 array
	$_url = explode( '.', $url );
	// chop off the last array entry eg '.test|.theater|.whatever'
	$last_part = array_pop( $_url );

	$path = strpos( $last_part, '/' );
	if ( $path )
		return substr( $last_part, $path );

	return false;
}









/*
add_action( 'admin_footer', function(){
	// var_dump(generate_htaccess_rules());
	// ft__media_mod_rewrite_rules();
	// \do_action( 'qm/debug', generate_htaccess_rules() );
} );*/

