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
				<p><?php printf( __( 'Please <a href="%s">contact us</a>.', 'hoastedcache' ), 'https://www.hoasted.com/contact' ); ?></p>
			</div>
		</div>

		<?php
	}

// End of default_admin_sidebar()
}