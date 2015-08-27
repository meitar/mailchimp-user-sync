var Admin = (function() {
	'use strict';

	var Wizard = require('./Wizard.js');
	var $ = window.jQuery;
	var wizardContainer = document.getElementById('wizard');

	function addFieldMapRow() {
		var $row = $(".mc4wp-sync-field-map-row").last();
		var $newRow = $row.clone();

		// empty select boxes and set new `name` attribute
		$newRow.find("select").val('').each(function () {
			this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
				return '[' + (parseInt(p1, 10) + 1) + ']';
			});
		});

		$newRow.insertAfter($row);
		return false;
	}

	function removeFieldMapRow() {
		$(this).parents('.mc4wp-sync-field-map-row').remove();
	}


	$('.mc4wp-sync-field-map-add-row').click(addFieldMapRow);
	$('.mc4wp-sync-field-map-remove-row').click(removeFieldMapRow);

	if( wizardContainer ) {
		m.module( wizardContainer , Wizard );
	}
})();

module.exports = Admin;