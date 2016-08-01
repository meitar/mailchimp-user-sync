<?php

namespace MC4WP\Sync;

use MC4WP_MailChimp;
use MC4WP_MailChimp_Subscriber;
use WP_User;

class UserSubscriber {

    /**
     * @var Users
     */
    protected $users;

    /**
     * @var MC4WP_MailChimp
     */
    protected $mailchimp;

    /**
     * @var string
     */
    protected $list_id;

    /**
     * @var string
     */
    public $error_message = '';

    /**
     * Subscriber2 constructor.
     *
     * @param Users $users
     * @param string $list_id
     */
    public function __construct( Users $users, $list_id ) {
        $this->users = $users;
        $this->mailchimp = new MC4WP_MailChimp();
        $this->list_id = $list_id;
    }

    /**
     * @param int $user_id
     * @param bool $double_optin
     * @param string $email_type
     * @param bool $replace_interests
     * @param bool $send_welcome (Unused)
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function subscribe( $user_id, $double_optin = false, $email_type = 'html', $replace_interests = false, $send_welcome = null ) {
        $user = $this->users->user( $user_id );

        $subscriber = new MC4WP_MailChimp_Subscriber();
        $subscriber->email_address = $user->user_email;
        $subscriber->merge_fields = $this->users->get_user_merge_fields( $user );
        $subscriber->email_type = $email_type;
        $subscriber->status = $double_optin ? 'pending' : 'subscribed';

        /**
         * Filter data that is sent to MailChimp
         *
         * @param MC4WP_MailChimp_Subscriber $subscriber
         * @param WP_User $user
         */
        $subscriber = apply_filters( 'mailchimp_sync_subscriber_data', $subscriber, $user );

        // perform the call
        $update_existing = true;
        $member = $this->mailchimp->list_subscribe( $this->list_id, $subscriber->email_address, $subscriber->to_array(), $update_existing, $replace_interests );
        $success = is_object( $member ) && ! empty( $member->id );

        if( ! $success ) {
            $this->error_message = $this->mailchimp->get_error_message();
            return false;
        }

        // Store member ID
        $this->users->set_subscriber_uid( $user_id, $member->unique_email_id );
        return true;
    }

    /**
     * @param $user_id
     * @param string $email_type
     * @param bool $replace_interests
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function update( $user_id, $email_type = 'html', $replace_interests = false ) {
        return $this->subscribe( $user_id, false, $email_type, $replace_interests );
    }

    /**
     * @param int $user_id
     * @param string $email_address
     * @param string $subscriber_uid        (optional)
     * @param null $send_goodbye            (unused)
     * @param null $send_notification       (unused)
     * @param null $delete_member           (unused)
     *
     * @return bool
     */
    public function unsubscribe( $user_id, $email_address, $subscriber_uid = null, $send_goodbye = null, $send_notification = null, $delete_member = null ) {

        // fetch subscriber_uid
        if( is_null( $subscriber_uid ) ) {
            $subscriber_uid = $this->users->get_subscriber_uid( $user_id );
        }

        // if user is not even subscribed, just bail.
        if( empty( $subscriber_uid ) ) {
            return true;
        }

        $success = $this->mailchimp->list_unsubscribe( $this->list_id, $email_address );
        $this->error_message = $this->mailchimp->get_error_message();

        if( $success ) {
            $this->users->delete_subscriber_uid( $user_id );
        }

        return $success;
    }
}