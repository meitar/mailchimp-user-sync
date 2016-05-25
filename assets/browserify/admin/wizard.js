
var Log = require('./log.js');
var User = require('./user.js');

var Wizard = (function() {

	var started = false, running = false, done = false;
	var userCount = 0;
	var usersProcessed = 0;
	var progress = m.prop( 0 );
	var log = new Log();
	var batch = m.prop([]);
	var settingsForm, unsavedChanges = m.prop(false);

	function controller() {
		settingsForm = document.getElementById('settings-form');

		settingsForm.addEventListener('change', function() {
			unsavedChanges(true);
			m.redraw();
		});
	}

	function askToStart() {
		var sure = confirm( "Are you sure you want to start synchronising all of your users? This can take a while if you have many users, please don't close your browser window." );
		if( sure ) {
			start();
		}
	}

	function start() {
		started = true;
		running = true;

		fetchTotalUserCount()
			.then(prepareBatch)
			.then(subscribeFromBatch);

	}

	function resume() {
		running = true;
		subscribeFromBatch();
		m.redraw();
	}

	function pause() {
		running = false;
		m.redraw();
	}

	function fetchTotalUserCount() {
		var deferred = m.deferred();

		var data = { action : 'mcs_wizard', mcs_action: 'get_user_count' };
		m.request({ method: "GET", url: ajaxurl, data: data }).then(function(data) {
			log.addLine("Found " + data + " users.");
			userCount = data;
			deferred.resolve();
		});

		return deferred.promise;
	}

	function prepareBatch() {

		var deferred = m.deferred();

		m.request( {
			method: "GET",
			url: ajaxurl,
			data: {
				action: 'mcs_wizard',
				mcs_action: 'get_users',
				offset: usersProcessed,
				limit: 1
			},
			type: User
		}).then( function( users ) {
			log.addLine("Fetched " + users.length + " users.");
			batch( users );
			deferred.resolve();
			m.redraw();
		}, function( error ) {
			log.addLine( "Error fetching users. Error: " + error );
		});

		return deferred.promise;
	}

	function subscribeFromBatch() {

		if( ! running || done ) {
			return;
		}

		// do we have users left in this batch>
		if( batch().length === 0 ) {

			// no? so are we ready?
			if( usersProcessed < userCount ) {
				prepareBatch()
					.then(subscribeFromBatch);
			}

			return;
		}

		// Get next user
		var user = batch().shift();

		// Add line to log
		log.addLine("Updating <strong> #" + user.id() + " " + user.username() + " &lt;" + user.email() + "&gt;</strong>" );

		// Perform subscribe request
		var data = {
			action: "mcs_wizard",
			mcs_action: "subscribe_users",
			user_id: user.id()
		};

		m.request({
			method: "GET",
			data: data,
			url: ajaxurl
		}).then(function( response ) {

			usersProcessed++;

			if( response.success ) {
				log.addLine( "Success!" );
			}

			if( response.error ) {
				log.addLine( "Error: " + response.error );
			}

			updateProgress();
			subscribeFromBatch();
		}, function( error ) {
			log.addLine( "Error: " + error );
		});
	}

	function updateProgress() {
		// update progress
		var newProgress = Math.round( usersProcessed / userCount * 100 );

		progress( newProgress );

		if( newProgress >= 100 ) {
			done = true;
			log.addLine("Done!");
		}
	}

	/**
	 * View
	 *
	 * @returns {*}
	 */
	function view() {

		// Wizard isn't running, show button to start it
		if( ! started ) {
			return m('p', [
				m('input', { type: 'button', class: 'button', value: 'Synchronise All', onclick: askToStart, disabled: unsavedChanges() } ),
				unsavedChanges() ? m('span.help', ' — Please save your changes first.') : ''
			]);
		} else

		// Show progress
		return [
			(
				( done ) ? '' : m("p",[
					m("input", { type: 'button', class: 'button', value: ( running ) ? "Pause" : "Resume", onclick: ( running ) ? pause : resume })
				]) ),
			m('div.progress-bar', [
				m( "div.value", { style: "width: "+ progress() +"%" } ),
				m( "div.text", {}, ( progress() == 100 ) ? "Done!" : "Working: " + progress() + "%" )
			]),

			log.render()
		];
	}

	return {
		'controller': controller,
		'view': view
	}
})();



module.exports = Wizard;