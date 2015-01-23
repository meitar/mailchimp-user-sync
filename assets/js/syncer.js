(function($) {
	'use strict';

	// Vars
	var ajaxurl = ( window.ajaxurl || '/wp-admin/admin-ajax.php' ),
		$progressBar = $("#sync-progress");


	// Functions

	/**
	 * Splits the array of user ID's into smaller batches
	 * Minimum batch size is 1, maximum batch size = 50.
	 *
	 * @param user_ids
	 * @returns {Array}
	 */
	function prepareBatches( user_ids ) {

		var batches = [];
		var batchSize = Math.ceil( user_ids.length / 10 );

		if( batchSize < 1 ) {
			batchSize = 1;
		} else if( batchSize > 50 ) {
			batchSize = 50;
		}

		while( user_ids.length ) {
			batches.push( user_ids.splice( 0, batchSize ) );
		}

		return batches;
	}

	/**
	 * Perform an AJAX request to subscribe the given user ID's
	 *
	 * @param user_ids
	 */
	function subscribeUserIds( user_ids ) {
		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action     : 'mailchimp_sync',
				sync_action: 'subscribe_users',
				user_ids: user_ids
			},
			success: function( data ) {
				updateProgress( data.progress );
			},
			dataType: "json"
		});
	}


	/**
	 * Updates the progress bar element
	 *
	 * @param progress
	 */
	function updateProgress( progress ) {
		$progressBar.find('.progress-bar-value').css('width', progress + '%' );
		$progressBar.find('.progress-text').text( progress + '%' );
	}


	function runSync() {

		// step 1. get User ID's
		$.getJSON( ajaxurl, {
			action     : 'mailchimp_sync',
			sync_action: 'get_users'
		}, function( data ) {

			// step 2. prepare batches
			var batches = prepareBatches( data );

			for( var i = 0; i < batches.length; i++ ) {
				subscribeUserIds( batches[i] );
			}

		});

	}


	// Event listeners
	$("#start-manual-sync").click( runSync );

})(window.jQuery);