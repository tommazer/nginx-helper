<?php

namespace hoasted\WP\Nginx {

	function default_admin_sidebar()
	{
		?>
			<?php $purge_url = add_query_arg( array( 'nginx_helper_action' => 'purge', 'nginx_helper_urls' => 'all' ) ); ?>
			<?php $nonced_url = wp_nonce_url( $purge_url, 'nginx_helper-purge_all' ); ?>
			<form id="purgeall" action="" method="post" class="clearfix">
					<a href="<?php echo $nonced_url; ?>" class="button-primary"><?php _e( 'Purge Entire Cache', 'hoastedcache' ); ?></a>
			</form>
		<div class="postbox" id="support">
			<h3 class="hndle">
				<span><?php _e( 'Need Help?', 'hoastedcache' ); ?></span>
			</h3>
			<div class="inside">
				<p><?php printf( __( 'Please use our <a href="%s">free support forum</a>.', 'hoastedcache' ), 'http://rtcamp.com/support/forum/wordpress-nginx/' ); ?></p>
			</div>
		</div>

		<div class="postbox" id="social">
			<h3 class="hndle">
				<span><?php _e( 'Getting Social is Good', 'hoastedcache' ); ?></span>
			</h3>
			<div style="text-align:center;" class="inside">
				<a class="nginx-helper-facebook" title="<?php _e( 'Become a fan on Facebook', 'nginx-helper' ); ?>" target="_blank" href="http://www.facebook.com/rtCamp.solutions/"></a>
				<a class="hoastedcache-twitter" title="<?php _e( 'Follow us on Twitter', 'hoastedcache' ); ?>" target="_blank" href="https://twitter.com/rtcamp/"></a>
				<a class="nginx-helper-gplus" title="<?php _e( 'Add to Circle', 'hoastedcache' ); ?>" target="_blank" href="https://plus.google.com/110214156830549460974/posts"></a>
				<a class="nginx-helper-rss" title="<?php _e( 'Subscribe to our feeds', 'hoastedcache' ); ?>" target="_blank" href="http://feeds.feedburner.com/rtcamp/"></a>
			</div>
		</div>

		<div class="postbox" id="useful-links">
			<h3 class="hndle">
				<span><?php _e( 'Useful Links', 'hoastedcache' ); ?></span>
			</h3>
			<div class="inside">
				<ul role="list">
					<li role="listitem">
						<a href="https://rtcamp.com/wordpress-nginx/" title="<?php _e( 'WordPress-Nginx Solutions', 'hoastedcache' ); ?>"><?php _e( 'WordPress-Nginx Solutions', 'hoastedcache' ); ?></a>
					</li>
					<li role="listitem">
						<a href="https://rtcamp.com/services/wordPress-themes-design-development/" title="<?php _e( 'WordPress Theme Devleopment', 'hoastedcache' ); ?>"><?php _e( 'WordPress Theme Devleopment', 'hoastedcache' ); ?></a>
					</li>
					<li role="listitem">
						<a href="http://rtcamp.com/services/wordpress-plugins/" title="<?php _e( 'WordPress Plugin Development', 'hoastedcache' ); ?>"><?php _e( 'WordPress Plugin Development', 'hoastedcache' ); ?></a>
					</li>
					<li role="listitem">
						<a href="http://rtcamp.com/services/custom-wordpress-solutions/" title="<?php _e( 'WordPress Consultancy', 'hoastedcache' ); ?>"><?php _e( 'WordPress Consultancy', 'hoastedcache' ); ?></a>
					</li>
					<li role="listitem">
						<a href="https://rtcamp.com/easyengine/" title="<?php _e( 'easyengine (ee)', 'hoastedcache' ); ?>"><?php _e( 'easyengine (ee)', 'hoastedcache' ); ?></a>
					</li>        
				</ul>
			</div>
		</div>

		<div class="postbox" id="latest_news">
			<div title="<?php _e( 'Click to toggle', 'hoastedcache' ); ?>" class="handlediv"><br /></div>
			<h3 class="hndle"><span><?php _e( 'Latest News', 'hoastedcache' ); ?></span></h3>
			<div class="inside"><img src ="<?php echo admin_url(); ?>/images/wpspin_light.gif" /><?php _e( 'Loading...', 'hoastedcache' ); ?></div>
		</div><?php
	}

// End of default_admin_sidebar()
}