<?php

namespace hoasted\WP\Nginx {
    function nginx_support_options_page() { ?>
        <form id="support" action="" method="post" class="clearfix">
            <div class="postbox">
                <h3 class="hndle">
                    <span><?php _e( 'Support Forums', 'hoastedcache' ); ?></span>
                </h3>
                <div class="inside">
                    <table class="form-table">
                        <tr valign="top">
                            <th><?php _e( 'Free Support', 'hoastedcache' ); ?></th>
                            <td>
                                <a href="https://rtcamp.com/support/forum/wordpress-nginx/" title="<?php _e( 'Free Support Forum', 'hoastedcache' ); ?>" target="_blank"><?php _e( 'Link to forum', 'hoastedcache' ); ?></a>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th><?php _e( 'Premium Support', 'hoastedcache' ); ?></th>
                            <td>
                                <a href="https://rtcamp.com/wordpress-nginx/pricing/" title="<?php _e( 'Premium Support Forum', 'hoastedcache' ); ?>" target="_blank"><?php _e( 'Link to forum', 'hoastedcache' ); ?></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </form><?php
    }
}