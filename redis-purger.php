<?php

namespace hoasted\WP\Nginx {

	class Redispurger {

		public function __construct()
		{
			require_once HC_WP_NGINX_HELPER_PATH . 'includes/redis-delete.php';
		}

		function purgePostOnComment( $comment_id, $comment )
		{
			$oldstatus = '';
			$approved = $comment->comment_approved;

			if ( $approved == null )
				$newstatus = false;
			elseif ( $approved == '1' )
				$newstatus = 'approved';
			elseif ( $approved == '0' )
				$newstatus = 'unapproved';
			elseif ( $approved == 'spam' )
				$newstatus = 'spam';
			elseif ( $approved == 'trash' )
				$newstatus = 'trash';
			else
				$newstatus = false;

			$this->purgePostOnCommentChange( $newstatus, $oldstatus, $comment );
		}

		function purgePostOnCommentChange( $newstatus, $oldstatus, $comment )
		{

			global $hc_wp_nginx_helper, $blog_id;
			if ( !$hc_wp_nginx_helper->options['enable_purge'] ) {
				return;
			}


			$_post_id = $comment->comment_post_ID;
			$_comment_id = $comment->comment_ID;

			$this->log( "* * * * *" );
			$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
			$this->log( "* Post :: " . get_the_title( $_post_id ) . " ($_post_id)." );
			$this->log( "* Comment :: $_comment_id." );
			$this->log( "* Status Changed from $oldstatus to $newstatus" );
			switch ( $newstatus ) {
				case 'approved':
					if ( $hc_wp_nginx_helper->options['purge_page_on_new_comment'] == 1 ) {
						$this->log( "* Comment ($_comment_id) approved. Post ($_post_id) purging..." );
						$this->log( "* * * * *" );
						$this->purgePost( $_post_id );
					}
					break;
				case 'spam':
				case 'unapproved':
				case 'trash':
					if ( $oldstatus == 'approve' ) {
						if ( $hc_wp_nginx_helper->options['purge_page_on_deleted_comment'] == 1 ) {
							$this->log( "* Comment ($_comment_id) removed as ($newstatus). Post ($_post_id) purging..." );
							$this->log( "* * * * *" );
							$this->purgePost( $_post_id );
						}
					}
					break;
			}
		}

		function purgePost( $_ID )
		{
			global $hc_wp_nginx_helper, $blog_id;
			if ( !$hc_wp_nginx_helper->options['enable_purge'] ) {
				return;
			}
			switch ( current_filter() ) {
				case 'publish_post':
					$this->log( "* * * * *" );
					$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
					$this->log( "* Post :: " . get_the_title( $_ID ) . " ($_ID)." );
					$this->log( "* Post ($_ID) published or edited and its status is published" );
					$this->log( "* * * * *" );
					break;

				case 'publish_page':
					$this->log( "* * * * *" );
					$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
					$this->log( "* Page :: " . get_the_title( $_ID ) . " ($_ID)." );
					$this->log( "* Page ($_ID) published or edited and its status is published" );
					$this->log( "* * * * *" );
					break;

				case 'comment_post':
				case 'wp_set_comment_status':
					break;

				default:
					$_post_type = get_post_type( $_ID );
					$this->log( "* * * * *" );
					$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
					$this->log( "* Custom post type '$_post_type' :: " . get_the_title( $_ID ) . " ($_ID)." );
					$this->log( "* CPT '$_post_type' ($_ID) published or edited and its status is published" );
					$this->log( "* * * * *" );
					break;
			}

			$this->log( "Function purgePost BEGIN ===" );

			if ( $hc_wp_nginx_helper->options['purge_homepage_on_edit'] == 1 ) {
				$this->_purge_homepage();
			}


			if ( current_filter() == 'comment_post' || current_filter() == 'wp_set_comment_status' ) {
				$this->_purge_by_options( $_ID, $blog_id, $hc_wp_nginx_helper->options['purge_page_on_new_comment'], $hc_wp_nginx_helper->options['purge_archive_on_new_comment'], $hc_wp_nginx_helper->options['purge_archive_on_new_comment'] );
			} else {
				$this->_purge_by_options( $_ID, $blog_id, $hc_wp_nginx_helper->options['purge_page_on_mod'], $hc_wp_nginx_helper->options['purge_archive_on_edit'], $hc_wp_nginx_helper->options['purge_archive_on_edit'] );
			}
            
            $this->purge_urls();
            
			$this->log( "Function purgePost END ^^^" );
		}

		private function _purge_by_options( $_post_ID, $blog_id, $_purge_page, $_purge_archive, $_purge_custom_taxa )
		{
			global $hc_wp_nginx_helper;

			$_post_type = get_post_type( $_post_ID );

			if ( $_purge_page ) {
				if ( $_post_type == 'post' || $_post_type == 'page' ) {
					$this->log( "Purging $_post_type (id $_post_ID, blog id $blog_id)" );
				} else {
					$this->log( "Purging custom post type '$_post_type' (id $_post_ID, blog id $blog_id)" );
				}

				$this->purgeUrl( get_permalink( $_post_ID ) );
			}

			if ( $_purge_archive ) {

				if ( function_exists( 'get_post_type_archive_link' ) && ( $_post_type_archive_link = get_post_type_archive_link( $_post_type ) ) ) {
					$this->log( "Purging post type archive (" . $_post_type . ")" );
					$this->purgeUrl( $_post_type_archive_link );
				}

				if ( $_post_type == 'post' ) {
					$this->log( "Purging date" );

					$day = get_the_time( 'd', $_post_ID );
					$month = get_the_time( 'm', $_post_ID );
					$year = get_the_time( 'Y', $_post_ID );

					if ( $year ) {
						$this->purgeUrl( get_year_link( $year ) );
						if ( $month ) {
							$this->purgeUrl( get_month_link( $year, $month ) );
							if ( $day )
								$this->purgeUrl( get_day_link( $year, $month, $day ) );
						}
					}
				}

				if ( $categories = wp_get_post_categories( $_post_ID ) ) {
					$this->log( "Purging category archives" );

					foreach ( $categories as $category_id ) {
						$this->log( "Purging category " . $category_id );
						$this->purgeUrl( get_category_link( $category_id ) );
					}
				}

				if ( $tags = get_the_tags( $_post_ID ) ) {
					$this->log( "Purging tag archives" );

					foreach ( $tags as $tag ) {
						$this->log( "Purging tag " . $tag->term_id );
						$this->purgeUrl( get_tag_link( $tag->term_id ) );
					}
				}

				if ( $author_id = get_post( $_post_ID )->post_author ) {
					$this->log( "Purging author archive" );
					$this->purgeUrl( get_author_posts_url( $author_id ) );
				}
			}

			if ( $_purge_custom_taxa ) {
				if ( $custom_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) ) ) {
					$this->log( "Purging custom taxonomies related" );
					foreach ( $custom_taxonomies as $taxon ) {

						if ( !in_array( $taxon, array( 'category', 'post_tag', 'link_category' ) ) ) {

							if ( $terms = get_the_terms( $_post_ID, $taxon ) ) {
								foreach ( $terms as $term ) {
									$this->purgeUrl( get_term_link( $term, $taxon ) );
								}
							}
						} else {
							$this->log( "Your built-in taxonomy '" . $taxon . "' has param '_builtin' set to false.", "WARNING" );
						}
					}
				}
			}
		}

		function purgeUrl( $url, $feed = true )
		{
			global $hc_wp_nginx_helper;
			
			$url = trailingslashit( $url );
			
			$this->log( "- Purging URL | " . $url );
			
			$parse = parse_url( $url );
			
			$host = $hc_wp_nginx_helper->options['redis_hostname'];
			
			$prefix = $hc_wp_nginx_helper->options['redis_prefix'];
			
			$_url_purge_base = $prefix . $parse['scheme'] . 'GET' . $parse['host'] . $parse['path'];
			
			delete_single_key( $_url_purge_base );
        }

		function log( $msg, $level = 'INFO' )
		{

			global $hc_wp_nginx_helper;
			if ( !$hc_wp_nginx_helper->options['enable_log'] ) {
				return;
			}

			$log_levels = array( "INFO" => 0, "WARNING" => 1, "ERROR" => 2, "NONE" => 3 );

			if ( $log_levels[$level] >= $log_levels[$hc_wp_nginx_helper->options['log_level']] ) {
				if ( $fp = fopen( $hc_wp_nginx_helper->functional_asset_path() . 'nginx.log', "a+" ) ) {
					fwrite( $fp, "\n" . gmdate( "Y-m-d H:i:s " ) . " | " . $level . " | " . $msg );
					fclose( $fp );
				}
			}

			return true;
		}

		function checkAndTruncateLogFile()
		{

			global $hc_wp_nginx_helper;

			$maxSizeAllowed = (is_numeric( $hc_wp_nginx_helper->options['log_filesize'] )) ? $hc_wp_nginx_helper->options['log_filesize'] * 1048576 : 5242880;

			$fileSize = filesize( $hc_wp_nginx_helper->functional_asset_path() . 'nginx.log' );

			if ( $fileSize > $maxSizeAllowed ) {

				$offset = $fileSize - $maxSizeAllowed;

				if ( $file_content = file_get_contents( $hc_wp_nginx_helper->functional_asset_path() . 'nginx.log', NULL, NULL, $offset ) ) {

					if ( $file_content = strstr( $file_content, "\n" ) ) {

						if ( $fp = fopen( $hc_wp_nginx_helper->functional_asset_path() . 'nginx.log', "w+" ) ) {
							fwrite( $fp, $file_content );
							fclose( $fp );
						}
					}
				}
			}
		}

		function purgeImageOnEdit( $attachment_id )
		{

			$this->log( "Purging media on edit BEGIN ===" );

			if ( wp_attachment_is_image( $attachment_id ) ) {

				$this->purgeUrl( wp_get_attachment_url( $attachment_id ), false );
				$attachment = wp_get_attachment_metadata( $attachment_id );

				if ( !empty( $attachment['sizes'] ) && is_array( $attachment['sizes'] ) ) {
					foreach ( $attachment['sizes'] as $size_name => $size ) {
						$resize_image = wp_get_attachment_image_src( $attachment_id, $size_name );
						if ( $resize_image )
							$this->purgeUrl( $resize_image[0], false );
					}
				}
				$this->purgeURL( get_attachment_link( $attachment_id ) );
			} else {
				$this->log( "Media (id $attachment_id) edited: no image", "WARNING" );
			}

			$this->log( "Purging media on edit END ^^^" );
		}

		function purge_on_post_moved_to_trash( $new_status, $old_status, $post )
		{

			global $hc_wp_nginx_helper, $blog_id;
			if ( !$hc_wp_nginx_helper->options['enable_purge'] ) {
				return;
			}
			if ( $new_status == 'trash' ) {

				$this->log( "# # # # #" );
				$this->log( "# Post '$post->post_title' (id $post->ID) moved to the trash." );
				$this->log( "# # # # #" );

				$this->log( "Function purge_on_post_moved_to_trash (post id $post->ID) BEGIN ===" );



				if ( $hc_wp_nginx_helper->options['purge_homepage_on_del'] == 1 ) {
					$this->_purge_homepage();
				}


				$this->_purge_by_options( $post->ID, $blog_id, false, $hc_wp_nginx_helper->options['purge_archive_on_del'], $hc_wp_nginx_helper->options['purge_archive_on_del'] );



				$this->log( "Function purge_on_post_moved_to_trash (post id $post->ID) END ===" );
			}

			return true;
		}

		private function _purge_homepage()
		{
			//  WPML installetd?
			if ( function_exists('icl_get_home_url') )
			{
				$homepage_url = trailingslashit( icl_get_home_url() );
				$this->log( sprintf( __( "Purging homepage (WPML) '%s'", "hoastedcache" ), $homepage_url ) );
			}
			else
			{
				$homepage_url = trailingslashit( home_url() );
				$this->log( sprintf( __( "Purging homepage '%s'", "hoastedcache" ), $homepage_url ) );
			}

			$this->purgeUrl( $homepage_url );

			return true;
		}

		private function _purge_personal_urls()
		{

			global $hc_wp_nginx_helper;

			$this->log( __( "Purging personal urls", "hoastedcache" ) );

			if ( isset( $hc_wp_nginx_helper->options['purgeable_url']['urls'] ) ) {

				foreach ( $hc_wp_nginx_helper->options['purgeable_url']['urls'] as $u ) {
					$this->purgeUrl( $u, false );
				}
			} else {
				$this->log( "- " . __( "No personal urls available", "hoastedcache" ) );
			}

			return true;
		}

		private function _purge_post_categories( $_post_id )
		{

			$this->log( __( "Purging category archives", "hoastedcache" ) );

			if ( $categories = wp_get_post_categories( $_post_id ) ) {
				foreach ( $categories as $category_id ) {
					$this->log( sprintf( __( "Purging category '%d'", "hoastedcache" ), $category_id ) );
					$this->purgeUrl( get_category_link( $category_id ) );
				}
			}

			return true;
		}

		private function _purge_post_tags( $_post_id )
		{

			$this->log( __( "Purging tags archives", "hoastedcache" ) );

			if ( $tags = get_the_tags( $_post_id ) ) {
				foreach ( $tags as $tag ) {
					$this->log( sprintf( __( "Purging tag '%s' (id %d)", "hoastedcache" ), $tag->name, $tag->term_id ) );
					$this->purgeUrl( get_tag_link( $tag->term_id ) );
				}
			}

			return true;
		}

		private function _purge_post_custom_taxa( $_post_id )
		{

			$this->log( __( "Purging post custom taxonomies related", "hoastedcache" ) );

			if ( $custom_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) ) ) {
				foreach ( $custom_taxonomies as $taxon ) {
					$this->log( sprintf( "+ " . __( "Purging custom taxonomy '%s'", "hoastedcache" ), $taxon ) );

					if ( !in_array( $taxon, array( 'category', 'post_tag', 'link_category' ) ) ) {

						if ( $terms = get_the_terms( $_post_id, $taxon ) ) {
							foreach ( $terms as $term ) {
								$this->purgeUrl( get_term_link( $term, $taxon ) );
							}
						}
					} else {
						$this->log( sprintf( "- " . __( "Your built-in taxonomy '%s' has param '_builtin' set to false.", "hoastedcache" ), $taxon ), "WARNING" );
					}
				}
			} else {
				$this->log( "- " . __( "No custom taxonomies", "hoastedcache" ) );
			}

			return true;
		}

		private function _purge_all_categories()
		{

			$this->log( __( "Purging all categories", "hoastedcache" ) );

			if ( $_categories = get_categories() ) {

				foreach ( $_categories as $c ) {
					$this->log( sprintf( __( "Purging category '%s' (id %d)", "hoastedcache" ), $c->name, $c->term_id ) );
					$this->purgeUrl( get_category_link( $c->term_id ) );
				}
			} else {
				$this->log( __( "No categories archives", "hoastedcache" ) );
			}

			return true;
		}

		private function _purge_all_posttags()
		{

			$this->log( __( "Purging all tags", "hoastedcache" ) );

			if ( $_posttags = get_tags() ) {

				foreach ( $_posttags as $t ) {
					$this->log( sprintf( __( "Purging tag '%s' (id %d)", "hoastedcache" ), $t->name, $t->term_id ) );
					$this->purgeUrl( get_tag_link( $t->term_id ) );
				}
			} else {
				$this->log( __( "No tags archives", "hoastedcache" ) );
			}

			return true;
		}

		private function _purge_all_customtaxa()
		{

			$this->log( __( "Purging all custom taxonomies", "hoastedcache" ) );

			if ( $custom_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) ) ) {

				foreach ( $custom_taxonomies as $taxon ) {
					$this->log( sprintf( "+ " . __( "Purging custom taxonomy '%s'", "hoastedcache" ), $taxon ) );

					if ( !in_array( $taxon, array( 'category', 'post_tag', 'link_category' ) ) ) {

						if ( $terms = get_terms( $taxon ) ) {
							foreach ( $terms as $term ) {
								$this->purgeUrl( get_term_link( $term, $taxon ) );
							}
						}
					} else {
						$this->log( sprintf( "- " . __( "Your built-in taxonomy '%s' has param '_builtin' set to false.", "hoastedcache" ), $taxon ), "WARNING" );
					}
				}
			} else {
				$this->log( "- " . __( "No custom taxonomies", "hoastedcache" ) );
			}

			return true;
		}

		private function _purge_all_taxonomies()
		{

			$this->_purge_all_categories();
			$this->_purge_all_posttags();
			$this->_purge_all_customtaxa();

			return true;
		}

		private function _purge_all_posts()
		{

			$this->log( __( "Purging all posts, pages and custom post types.", "hoastedcache" ) );

			$args = array(
				'numberposts' => 0,
				'post_type' => 'any',
				'post_status' => 'publish' );

			if ( $_posts = get_posts( $args ) ) {

				foreach ( $_posts as $p ) {
					$this->log( sprintf( "+ " . __( "Purging post id '%d' (post type '%s')", "hoastedcache" ), $p->ID, $p->post_type ) );
					$this->purgeUrl( get_permalink( $p->ID ) );
				}
			} else {
				$this->log( "- " . __( "No posts", "hoastedcache" ) );
			}

			return true;
		}

		private function _purge_all_date_archives()
		{

			$this->log( __( "Purging all date-based archives.", "hoastedcache" ) );

			$this->_purge_all_daily_archives();

			$this->_purge_all_monthly_archives();

			$this->_purge_all_yearly_archives();

			return true;
		}

		private function _purge_all_daily_archives()
		{

			global $wpdb;

			$this->log( __( "Purging all daily archives.", "hoastedcache" ) );

			$_query_daily_archives = $wpdb->prepare(
					"SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', DAYOFMONTH(post_date) AS 'dayofmonth', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date)
                ORDER BY post_date DESC"
			);

			if ( $_daily_archives = $wpdb->get_results( $_query_daily_archives ) ) {

				foreach ( $_daily_archives as $_da ) {
					$this->log( sprintf( "+ " . __( "Purging daily archive '%s/%s/%s'", "hoastedcache" ), $_da->year, $_da->month, $_da->dayofmonth ) );
					$this->purgeUrl( get_day_link( $_da->year, $_da->month, $_da->dayofmonth ) );
				}
			} else {
				$this->log( "- " . __( "No daily archives", "hoastedcache" ) );
			}
		}

		private function _purge_all_monthly_archives()
		{

			global $wpdb;

			$this->log( __( "Purging all monthly archives.", "hoastedcache" ) );

			$_query_monthly_archives = $wpdb->prepare(
					"SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date)
                ORDER BY post_date DESC"
			);

			if ( $_monthly_archives = $wpdb->get_results( $_query_monthly_archives ) ) {

				foreach ( $_monthly_archives as $_ma ) {
					$this->log( sprintf( "+ " . __( "Purging monthly archive '%s/%s'", "hoastedcache" ), $_ma->year, $_ma->month ) );
					$this->purgeUrl( get_month_link( $_ma->year, $_ma->month ) );
				}
			} else {
				$this->log( "- " . __( "No monthly archives", "hoastedcache" ) );
			}
		}

		private function _purge_all_yearly_archives()
		{

			global $wpdb;

			$this->log( __( "Purging all yearly archives.", "hoastedcache" ) );

			$_query_yearly_archives = $wpdb->prepare(
					"SELECT YEAR(post_date) AS 'year', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date)
                ORDER BY post_date DESC"
			);

			if ( $_yearly_archives = $wpdb->get_results( $_query_yearly_archives ) ) {

				foreach ( $_yearly_archives as $_ya ) {
					$this->log( sprintf( "+ " . __( "Purging yearly archive '%s'", "hoastedcache" ), $_ya->year ) );
					$this->purgeUrl( get_year_link( $_ya->year ) );
				}
			} else {
				$this->log( "- " . __( "No yearly archives", "hoastedcache" ) );
			}
		}

		function purge_them_all()
		{

			$this->log( __( "Let's purge everything!", "hoastedcache" ) );

			$this->_purge_homepage();

			$this->_purge_personal_urls();

			$this->_purge_all_posts();

			$this->_purge_all_taxonomies();

			$this->_purge_all_date_archives();

			$this->log( __( "Everthing purged!", "hoastedcache" ) );

			return true;
		}

		function purge_on_term_taxonomy_edited( $term_id, $tt_id, $taxon )
		{

			$this->log( __( "Term taxonomy edited or deleted", "hoastedcache" ) );

			if ( current_filter() == 'edit_term' && $term = get_term( $term_id, $taxon ) ) {
				$this->log( sprintf( __( "Term taxonomy '%s' edited, (tt_id '%d', term_id '%d', taxonomy '%s')", "hoastedcache" ), $term->name, $tt_id, $term_id, $taxon ) );
			} else if ( current_filter() == 'delete_term' ) {
				$this->log( sprintf( __( "A term taxonomy has been deleted from taxonomy '%s', (tt_id '%d', term_id '%d')", "hoastedcache" ), $taxon, $term_id, $tt_id ) );
			}

			$this->_purge_homepage();

			return true;
		}

		function purge_on_check_ajax_referer( $action, $result )
		{

			switch ( $action ) {
				case 'save-sidebar-widgets' :

					$this->log( __( "Widget saved, moved or removed in a sidebar", "hoastedcache" ) );

					$this->_purge_homepage();

					break;

				default :
					break;
			}

			return true;
		}

		function true_purge_all()
		{
			global $hc_wp_nginx_helper;
			$prefix = trim( $hc_wp_nginx_helper->options['redis_prefix'] );
			
			$this->log( '* * * * *' );
			
			// If Purge Cache link click from network admin then purge all
			if( is_network_admin() ) {
				delete_keys_by_wildcard( $prefix . '*' );
				$this->log( '* Purged Everything! * ' );
			} else { // Else purge only site specific cache
				$parse = wp_parse_url( get_site_url() );
				$parse['path'] = empty( $parse['path'] ) ? '/' : $parse['path'];
				delete_keys_by_wildcard( $prefix . $parse['scheme'] . 'GET' . $parse['host'] . $parse['path'] . '*' );
				$this->log( '* ' . get_site_url() . ' Purged! * ' );
			}
			
			$this->log( '* * * * *' );
		}
		
		function purge_urls()
		{
			global $hc_wp_nginx_helper;
			
			$parse = parse_url( site_url() );
			$host = $hc_wp_nginx_helper->options['redis_hostname'];
			$prefix = $hc_wp_nginx_helper->options['redis_prefix'];
			$_url_purge_base = $prefix . $parse['scheme'] . 'GET' . $parse['host'];
			
			$purge_urls = isset( $hc_wp_nginx_helper->options['purge_url'] ) && ! empty( $hc_wp_nginx_helper->options['purge_url'] ) ?
				explode( "\r\n", $hc_wp_nginx_helper->options['purge_url'] ) : array();
			
                        // Allow plugins/themes to modify/extend urls. Pass urls array in first parameter, second says if wildcards are allowed
                        $purge_urls = apply_filters('rt_nginx_helper_purge_urls', $purge_urls, true);
                        
                        if( is_array( $purge_urls ) && ! empty( $purge_urls ) ) {
				foreach ($purge_urls as $purge_url ) {
					$purge_url = trim( $purge_url );
					
					if( strpos( $purge_url, '*' ) === false ) {
						$purge_url = $_url_purge_base . $purge_url;
						$status = delete_single_key( $purge_url );
						if( $status ) {
						    $this->log( "- Purge URL | " . $purge_url );
						} else {
						    $this->log( "- Not Found | " . $purge_url, 'ERROR' );
						}
					} else {
						$purge_url = $_url_purge_base . $purge_url;
						$status = delete_keys_by_wildcard( $purge_url );
						if( $status ) {
						    $this->log( "- Purge Wild Card URL | " . $purge_url . " | ". $status . " url purged" );
						} else {
						    $this->log( "- Not Found | " . $purge_url, 'ERROR' );
						}
					}
				}
			}
		}
		
	}
}
