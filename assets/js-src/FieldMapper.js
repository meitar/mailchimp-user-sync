var FieldMapper = function( $context ) {

	var $ = window.jQuery;

	function addRow() {
		var $row = $(".row").last();
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

	function removeRow() {
		$(this).parents('.row').remove();
		setAvailableFields();
	}

	function setAvailableFields() {
		var selectBoxes = $context.find('.mailchimp-field');
		selectBoxes.each(function() {
			var otherSelectBoxes = selectBoxes.not(this);
			var chosenFields = $.map( otherSelectBoxes, function(a,i) { return $(a).val(); });

			$(this).find('option').each(function() {
				$(this).prop('disabled', ( $.inArray($(this).val(), chosenFields) > -1 ));
			});
		});
	}

	$context.find('.user-field').suggest( ajaxurl + "?action=mcs_autocomplete_user_field" );
	$context.find('.mailchimp-field').change(setAvailableFields).trigger('change');
	$context.find('.add-row').click(addRow);
	$context.find('.remove-row').click(removeRow);
};

module.exports = FieldMapper;