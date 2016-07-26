<?php

defined( 'ABSPATH' ) or exit;

/**
 * Upgrade old filter to new subscriber filter, setting the correct interest ID's.
 *
 * @ignore
 * @access private
 *
 * @param MC4WP_MailChimp_Subscriber $subscriber
 * @param WP_User $user
 *
 * @return MC4WP_MailChimp_Subscriber
 */
function __mailchimp_sync_update_groupings_to_interests( MC4WP_MailChimp_Subscriber $subscriber, WP_User $user ) {

   
    return $subscriber;
}
