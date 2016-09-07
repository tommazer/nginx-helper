<?php

namespace hoasted\WP\Nginx {
    function nginx_support_options_page() { ?>
        <form id="support" action="" method="post" class="clearfix">
            <div class="postbox">
                <h3 class="hndle">
                    <span><?php _e( 'Support', 'hoastedcache' ); ?></span>
                </h3>
                <div class="inside">
                    <table class="form-table">
                        <tr valign="top">
                            <th><?php _e( 'Support', 'hoastedcache' ); ?></th>
                            <td>
                                <a href="https://www.hoasted.com/contact" title="<?php _e( 'Contact us', 'hoastedcache' ); ?>" target="_blank"><?php _e( 'Contact us', 'hoastedcache' ); ?></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </form><?php
    }
}