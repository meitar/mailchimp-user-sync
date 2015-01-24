(function() {
	'use strict';

	var User = function( data ) {
		this.id = m.prop( data.ID );
		this.username = m.prop( data.username );
		this.email = m.prop( data.email );
	};

	var Wizard = {};

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
		m.redraw();

		// Step 2: Get users
		var data = { action : 'mcs_wizard', mcs_action: 'get_users' };
		m.request( { method: "GET", url: ajaxurl, data: data, type: User })
			.then( Wizard.vm.users)
			.then( function() {

				// Step 3: Prepare batches
				Wizard.vm.batches( Wizard.prepareBatches() );

				// Step 4: Process batches
				Wizard.processNextBatch();
			});


	};

	/**
	 * Splits the array of user ID's into smaller batches
	 * Minimum batch size is 1, maximum batch size = 50.
	 */
	Wizard.prepareBatches = function() {
		var users =  Wizard.vm.users();
		var batches = [];
		var batchSize = Math.ceil( users.length / 10 );

		if( batchSize < 1 ) {
			batchSize = 1;
		} else if( batchSize > 50 ) {
			batchSize = 50;
		}

		while( users.length ) {
			batches.push( users.splice( 0, batchSize ) );
		}

		return batches;
	};

	/**
	 * Processes all batches and updates the progress bar
	 */
	Wizard.processNextBatch = function() {
		Wizard.processBatch().then( function() {

			// update current batch index
			Wizard.vm.currentBatchIndex( Wizard.vm.currentBatchIndex() + 1 );

			// update progress
			var percentagePerBatch = 100 / Wizard.vm.batches().length;
			Wizard.vm.progress( Math.round( percentagePerBatch * Wizard.vm.currentBatchIndex() ) );
			m.redraw();

			// process next batch if there are more left
			if( Wizard.vm.currentBatchIndex() < Wizard.vm.batches().length ) {
				Wizard.processNextBatch();
			}
		})
	};

	/**
	 * Processes a batch of users
	 */
	Wizard.processBatch = function() {

		var users = Wizard.vm.batches()[  Wizard.vm.currentBatchIndex()  ];

		// add line to log for each user
		for( var i=0; i < users.length; i++ ) {
			Wizard.addLogItem( "Subscribing or updating <strong>user #" + users[i].id() + "</strong> with username <strong>" + users[i].username() + "</strong> and email <strong>" + users[i].email() + "</strong>." );
		}

		var data = {
			action: "mcs_wizard",
			mcs_action: "subscribe_users",
			user_ids: users.map( function( user ) { return user.id(); } )
		};

		return m.request({
			method: "GET",
			data: data,
			url: ajaxurl
		});
	};


	// Add a line to the log
	Wizard.addLogItem = function( item ) {
		var logItems = Wizard.vm.logItems();
		logItems.push( item );
		Wizard.vm.logItems( logItems );
	};

	// View-Model
	Wizard.vm = (function() {
		var vm = {};

		vm.init = function() {
			vm.isRunning = m.prop( false );
			vm.users = m.prop([]);
			vm.currentBatchIndex = m.prop( 0 );
			vm.progress = m.prop( 0 );
			vm.batches = m.prop([]);
			vm.logItems = m.prop( [] );
		};

		return vm;
	})();

	// Controller
	Wizard.controller = function() {
		Wizard.vm.init();
	};


	// View
	Wizard.view = function( ctrl ) {

		// Wizard isn't running, show button to start it
		if( ! Wizard.vm.isRunning() ) {
			return m('p', [
				m('input', { type: 'submit', class: 'button', value: 'Synchronise All', onclick: Wizard.askToStart } )
			]);
		}

		// Show progress
		return [
			m('div.progress-bar', [
				m( "div.value", { style: "width: "+ Wizard.vm.progress() +"%" } ),
				m( "div.text", {}, Wizard.vm.progress() + "%" )
			]),
			m("div.log", [
				Wizard.vm.logItems().map( function( item ) {
					return m("p", m.trust(item) )
				})
			])
			];
	};

	m.module( document.getElementById('wizard'), Wizard );

})();