<?php
/**
 * Remove plugin data when the user has explicitly opted in.
 *
 * @package SEOForGeneratePress
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$seogp_settings = get_option( 'seogp_settings', array() );

if ( is_array( $seogp_settings ) && ! empty( $seogp_settings['delete_data_on_uninstall'] ) ) {
	delete_option( 'seogp_settings' );
	delete_option( 'seogp_version' );
	delete_post_meta_by_key( '_seogp_search_title' );
	delete_post_meta_by_key( '_seogp_meta_description' );
	delete_post_meta_by_key( '_seogp_noindex' );
}
