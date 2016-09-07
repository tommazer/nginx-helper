<?php
/**
 * @author: Tom Mazer <tom@hoasted.com>
 *
 * Parts of code based off http://wordpress.org/extend/plugins/nginx-helper/ http://wordpress.org/extend/plugins/nginx-manager/ by http://profiles.wordpress.org/hpatoio/ and http://profiles.wordpress.org/rukbat/
 */
namespace hoasted\WP\Nginx {
	if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER[ 'PHP_SELF' ] ) ) {
		die( 'You are not allowed to call this page directly.' );
	}

	function hc_wp_nginx_helper_install() {
		global $wp_roles, $hc_wp_nginx_helper;

                if ( ! current_user_can( 'activate_plugins' ) ) {
                    return;
                }

		$role = get_role( 'administrator' );

		if ( empty( $role ) ) {
			update_site_option( "hc_wp_nginx_helper_init_check", __( 'Sorry, you need to be an administrator to use Hoasted Cache', 'hoastedcache' ) );
			return;
		}

		$role->add_cap( 'Hoadted Cache | Config' );
		$role->add_cap( 'Hoasted Cache | Purge cache' );

		$hc_wp_nginx_helper_get_options = get_site_option( 'hc_wp_nginx_helper_global_options' );

		if ( empty( $hc_wp_nginx_helper_get_options ) ) {
			$hc_wp_nginx_helper_get_options = hc_wp_nginx_helper_get_options();
			update_site_option( "hc_wp_nginx_helper_global_options", $hc_wp_nginx_helper_get_options );
		}

		if ( is_multisite() ) {
			$blogs = get_blogs_of_user( true );
			foreach ( $blogs as $b ) {
				$hc_wp_nginx_helper_options = get_blog_option( $b->userblog_id, 'hc_wp_nginx_helper_options' );
				if ( empty( $hc_wp_nginx_helper_options ) ) {
					$hc_wp_nginx_helper_options = hc_wp_nginx_helper_get_options();
					update_blog_option( $b->userblog_id, "hc_wp_nginx_helper_options", $hc_wp_nginx_helper_options );
				}
			}
		} else {
			$hc_wp_nginx_helper_options = get_option( 'hc_wp_nginx_helper_options' );
			if ( empty( $hc_wp_nginx_helper_options ) ) {
				$hc_wp_nginx_helper_options = hc_wp_nginx_helper_get_options();
				update_option( "hc_wp_nginx_helper_options", $hc_wp_nginx_helper_options );
			}
		}
		wp_schedule_event( time(), 'daily', 'hc_wp_nginx_helper_check_log_file_size_daily' );
	}

	function hc_wp_nginx_helper_uninstall() {
		wp_clear_scheduled_hook( 'hc_wp_nginx_helper_check_log_file_size_daily' );
		delete_site_option( 'hc_wp_nginx_helper_options' );
		hc_wp_nginx_helper_remove_capability( 'Hoasted Cache | Config' );
		hc_wp_nginx_helper_remove_capability( 'Hoasted Cache | Purge cache' );
	}

	function hc_wp_nginx_helper_remove_capability( $capability ) {
		$check_order = array( "subscriber", "contributor", "author", "editor", "administrator" );

		foreach ( $check_order as $role ) {
			$role = get_role( $role );
			$role->remove_cap( $capability );
		}
	}

	function hc_wp_nginx_helper_get_options() {
		$hc_wp_nginx_helper_get_options = array( );
		$hc_wp_nginx_helper_get_options[ 'log_level' ] = 'INFO';
		$hc_wp_nginx_helper_get_options[ 'log_filesize' ] = 5;

		$hc_wp_nginx_helper_get_options[ 'enable_purge' ] = 0;
		$hc_wp_nginx_helper_get_options[ 'enable_map' ] = 0;
		$hc_wp_nginx_helper_get_options[ 'enable_log' ] = 0;
		$hc_wp_nginx_helper_get_options[ 'enable_stamp' ] = 0;

		$hc_wp_nginx_helper_get_options[ 'purge_homepage_on_new' ] = 1;
		$hc_wp_nginx_helper_get_options[ 'purge_homepage_on_edit' ] = 1;
		$hc_wp_nginx_helper_get_options[ 'purge_homepage_on_del' ] = 1;

		$hc_wp_nginx_helper_get_options[ 'purge_archive_on_new' ] = 1;
		$hc_wp_nginx_helper_get_options[ 'purge_archive_on_edit' ] = 1;
		$hc_wp_nginx_helper_get_options[ 'purge_archive_on_del' ] = 1;

		$hc_wp_nginx_helper_get_options[ 'purge_archive_on_new_comment' ] = 0;
		$hc_wp_nginx_helper_get_options[ 'purge_archive_on_deleted_comment' ] = 0;

		$hc_wp_nginx_helper_get_options[ 'purge_page_on_mod' ] = 1;
		$hc_wp_nginx_helper_get_options[ 'purge_page_on_new_comment' ] = 1;
		$hc_wp_nginx_helper_get_options[ 'purge_page_on_deleted_comment' ] = 1;

		$hc_wp_nginx_helper_get_options[ 'purge_method' ] = 'get_request';

		return $hc_wp_nginx_helper_get_options;
	}
}