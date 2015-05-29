=== MailChimp Sync ===
Contributors: DvanKooten, ibericode, iMazed, hchouhan
Donate link: https://mc4wp.com/
Tags: mailchimp,users,sync,mailchimp list,synchronize,zapier,woocommerce
Requires at least: 3.8
Tested up to: 4.2.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Synchronize your WordPress Users with a MailChimp list.

== Description ==

Synchronize your registered WordPress users with a MailChimp list of your choice.

**This plugin is still in beta. You can definitely use it but it _could_ be subject to changes breaking backwards compatibility.**

> This plugin is an add-on for the [MailChimp for WordPress plugin](https://wordpress.org/plugins/mailchimp-for-wp/).
> To use it, you need at least MailChimp for WordPress v2.2.3 or MailChimp for WordPress Premium 2.5.5.

= MailChimp Sync, at a glance.. =

MailChimp Sync will watch for changes in your WordPress user base and automatically synchronize any changes with a selected MailChimp list.

- New users are automatically subscribed to your MailChimp list.
- When user data changes, the MailChimp subscriber will be updated as well. **Even when their email address is changed.**
- Deleted users will be unsubscribed from the selected MailChimp list as well.
- You can choose to only synchronize a specific user role, eg "customers"
- Manually have MailChimp Sync synchronize all existing users.
- Choose whether you want to use double opt-in and send a welcome email to new subscribers.
- WP CLI commands to synchronize a large amount of WordPress users at once.

After activation, the plugin will listen to all changes in your WordPress users and make sure everything stays in sync with the selected MailChimp list.

= Development of MailChimp Sync =

Bug reports (and Pull Requests) for [MailChimp Sync are welcomed on GitHub](https://github.com/ibericode/mailchimp-sync). Please note that GitHub is _not_ a support forum.

**More information**

- [MailChimp for WordPress](https://wordpress.org/plugins/mailchimp-for-wp/)
- Developers; follow or contribute to the [MailChimp Sync plugin on GitHub](https://github.com/ibericode/mailchimp-sync)
- Other [WordPress plugins](https://dannyvankooten.com/wordpress-plugins/#utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=more-info-link) by [Danny van Kooten](https://dannyvankooten.com#utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=more-info-link)
- [@DannyvanKooten](https://twitter.com/dannyvankooten) on Twitter

== Installation ==

= MailChimp for WordPress =

Since this plugin depends on the [MailChimp for WordPress plugin](https://wordpress.org/plugins/mailchimp-for-wp/), you will need to install that first.

= Installing MailChimp Sync =

1. In your WordPress admin panel, go to *Plugins > New Plugin*, search for **MailChimp Sync** and click "*Install now*"
1. Alternatively, download the plugin and upload the contents of `mailchimp-sync.zip` to your plugins directory, which usually is `/wp-content/plugins/`.
1. Activate the plugin
1. Set [your MailChimp API key](https://admin.mailchimp.com/account/api) in **MailChimp for WP > MailChimp Settings**.
1. Select a list to sync with in **MailChimp for WP > Sync**.]
1. (optional) Select a specific user role to synchronize.
1. (optional) synchronize your existing users.

== Frequently Asked Questions ==

= I think I found a bug. What now? =

Please report it on [GitHub issues](https://github.com/ibericode/mailchimp-sync/issues) if it's not in the list of known issues.

= I have another question =

Please open a topic on the [WordPress.org plugin support forums](https://wordpress.org/support/plugin/mailchimp-sync).

= Send additional user fields to MailChimp =

You can send additional user data by hooking into the `mailchimp_sync_user_data` filter.

`
add_filter( 'mailchimp_sync_user_data', function( $data, $user ) {
	$data['WEBSITE'] = $user->user_url;
}, 10, 2 );
`

= Can I run this from the command-line? =

Yes, you can. The plugin registers two [WP CLI](http://wp-cli.org/) commands.

`
wp mailchimp-sync sync-all 							# synchronize all users
wp mailchimp-sync sync-all --role=administrator 	# synchronize all users with "administrator" role
wp mailchimp-sync sync-user $user_id	 			# synchronize the user specified by the given ID
`

This is especially useful for synchronising a huge amount of users.

== Screenshots ==

1. synchronization settings
2. Status overview

== Changelog ==

= 1.0 - May 29, 2015 =

**Fixes**

- Force synchronization would not work on large data sets (> 10.000). The process is now batched.

**Improvements**

- Pause / resume the forced sync process

**Additions**

- Enable / disable auto-syncing
- Choose a user role to synchronize.
- [WP CLI](http://wp-cli.org/) commands: `wp mailchimp-sync sync-all` and `wp mailchimp-sync sync-user $user_id`. For more usage info, have a look at the [MailChimp Sync FAQ](https://wordpress.org/plugins/mailchimp-sync/faq/).
- `mailchimp_sync_user_data` to modify user data before it's sent to MailChimp.

= 0.1.2 - March 17, 2015 =

**Fixes**

- Synchronising would stop if a synchronize request failed
- Conflict with other plugins bundling old versions of Composer, throwing a fatal error on plugin activation
- Users who were deleted from a list would cause issues, they're now re-subscribed.

**Improvements**

- Added some feedback to Log whether a synchronization request succeeded or not.

= 0.1.1 - February 17, 2015 =

**Fixes**

- Force Sync got stuck on users without a valid email address. ([#10](https://github.com/ibericode/mailchimp-sync/issues/10), thanks [girandovoy](https://github.com/girandovoy))
- JSON response was malformed when any plugin threw a PHP notice

**Improvements**

- Progress log now auto-scrolls to bottom
- Progress log now shows time
- Progress log now shows more actions
- Add settings link to Plugin overview
- Various JavaScript improvements

= 0.1 - January 23, 2015 =

Initial release.

== Upgrade Notice ==

= 1.0 =
Fix for large data sets, sync a specific user role only, WP CLI commands & various other improvements.
