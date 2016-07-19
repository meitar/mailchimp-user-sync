<?php

namespace MC4WP\Sync;

use MC4WP_MailChimp;
use MC4WP_MailChimp_Subscriber;

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
    public function subscribe( $user_id, $double_optin = false, $email_type = 'html', $replace_interests = false, $send_welcome = false ) {
        $user = $this->users->user( $user_id );
        $merge_vars = $this->users->get_user_merge_vars( $user );

        $subscriber = new MC4WP_MailChimp_Subscriber();
        $subscriber->email_address = $user->user_email;
        $subscriber->merge_fields = $merge_vars;
        $subscriber->email_type = $email_type;
        $subscriber->status = $double_optin ? 'pending' : 'subscribed';

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
     * @param boolean $send_goodbye         (Unused)
     * @param boolean $send_notification    (Unused)
     * @param boolean $delete_member        (Unused)
     *
     * @return bool
     */
    public function unsubscribe( $user_id, $email_address = '', $send_goodbye = null, $send_notification = null, $delete_member = null ) {

        $is_subscribed = $this->users->get_subscriber_uid( $user_id );

        // do nothing if we're not subscribed
        if( empty( $is_subscribed ) ) {
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