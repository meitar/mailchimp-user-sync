
var Log = require('./Log.js');
var Wizard = {};

/**
 * Ask user to start wizard. This might take a while if they have a lot of users..
 */
Wizard.askToStart = function() {

	var sure = confirm( "Are you sure you want to start synchronising all of your users? This can take a while if you have many users, please don't close your browser window." );
	if( sure ) {
		Wizard.start();
	}
};

/**
 * Starts the Wizard
 */
Wizard.start = function() {

	// Step 1: Initialize vars
	Wizard.vm.isRunning( true );

	// Step 2: Get users
	var data = { action : 'mcs_wizard', mcs_action: 'get_users' };

	// Add line to log
	Wizard.vm.log.addLine( "Fetching users.." );

	m.request( { method: "GET", url: ajaxurl, data: data, type: User })
		.then( function( users ) {

			Wizard.vm.log.addLine("Fetched " + users.length + " users.");

			// Store users
			Wizard.vm.users( users );

			// Store user count
			Wizard.vm.userCount( users.length );

			// Step 3: Subscribe users
			Wizard.subscribeNextUser();
		}, function( error ) {
			Wizard.vm.log.addLine( "Error fetching users. Error: " + error );
		});
};

/**
 * Processes all batches and updates the progress bar
 */
Wizard.subscribeNextUser = function() {

	var users = Wizard.vm.users(),
		timeout = 0;

	// bail if no users left
	if( users.length == 0 ) {
		return false;
	}

	// Get first user
	var user = users.shift();

	// Add line to log
	Wizard.vm.log.addLine("Synchronising <strong>user #" + user.id() + " " + user.username() + "</strong> (Email: <strong>" + user.email() + "</strong>)." );

	// Perform subscribe request
	var data = {
		action: "mcs_wizard",
		mcs_action: "subscribe_users",
		user_ids: [ user.id() ]
	};

	m.request({
		method: "GET",
		data: data,
		url: ajaxurl
	}).then(function( data ) {

		Wizard.vm.log.addTextToLastLine( ( data.success ) ? "Success!" : "Error." );
		Wizard.updateProgress();

		// call self and clear scheduled call
		window.clearTimeout( timeout );
		Wizard.subscribeNextUser();

	}, function( error ) {
		Wizard.vm.log.addLine( "Error: " + error );
	});

	// Proceed with next user in 500ms
	timeout = setTimeout( Wizard.subscribeNextUser, 500 );
};

Wizard.updateProgress = function() {
	// update progress
	var progress = Math.round( ( Wizard.vm.userCount() - Wizard.vm.users().length  ) / Wizard.vm.userCount() * 100 );
	Wizard.vm.progress( progress );

	if( progress === 100 ) {
		Wizard.vm.log.addLine("Done!");
	}
};

// View-Model
Wizard.vm = (function() {
	var vm = {};

	vm.init = function() {
		vm.isRunning = m.prop( false );
		vm.users = m.prop([]);
		vm.userCount = m.prop( 0 );
		vm.progress = m.prop( 0 );
		vm.log = new Log();
	};

	return vm;
})();

// Controller
Wizard.controller = function() {
	Wizard.vm.init();
};


// View
Wizard.view = function( ctrl ) {

	var vm = Wizard.vm;

	// Wizard isn't running, show button to start it
	if( ! vm.isRunning() ) {
		return m('p', [
			m('input', { type: 'submit', class: 'button', value: 'Synchronise All', onclick: Wizard.askToStart } )
		]);
	}

	// Show progress
	return [
		m('div.progress-bar', [
			m( "div.value", { style: "width: "+ Wizard.vm.progress() +"%" } ),
			m( "div.text", {}, ( vm.progress() == 100 ) ? "Done!" : "Working: " + Wizard.vm.progress() + "%" )
		]),
		Wizard.vm.log.render()
	];
};

module.exports = Wizard;