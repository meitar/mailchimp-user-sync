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
		setAvailableFields();
		return false;
	}

	function removeFieldMapRow() {
		$(this).parents('.mc4wp-sync-field-map-row').remove();
		setAvailableFields();
	}

	function setAvailableFields() {
		var selectBoxes = $('.mc4wp-sync-field-map .mailchimp-field');
		selectBoxes.each(function() {
			var otherSelectBoxes = selectBoxes.not(this);
			var chosenFields = $.map( otherSelectBoxes, function(a,i) { return $(a).val(); });

			$(this).find('option').each(function() {
				$(this).prop('disabled', ( $.inArray($(this).val(), chosenFields) > -1 ));
			});
		});
	}


	$('.mc4wp-sync-field-map .mailchimp-field').change(setAvailableFields).trigger('change');
	$('.mc4wp-sync-field-map-add-row').click(addFieldMapRow);
	$('.mc4wp-sync-field-map-remove-row').click(removeFieldMapRow);

	if( wizardContainer ) {
		m.module( wizardContainer , Wizard );
	}
})();

module.exports = Admin;