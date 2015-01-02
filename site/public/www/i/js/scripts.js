$(document).ready(function() {
	$('#termin a[href="#prihlaska"]').click(function() {
		$('#frm-application-trainingId').val($(this).data('id'));
	});
	$('#uskutecnene-terminy a[href="#uskutecnene-terminy"]').click(function() {
		$('#uskutecnene-terminy-container').fadeToggle('fast');
		return false;
	});
	$('#uskutecnene-terminy-container').hide();

	var APPLICATION = APPLICATION || {};
	APPLICATION.loadData = function(event) {
		$('#loadDataControls > span').hide();
		$('#loadDataWait').show();
		event.preventDefault();
		var load = $.ajax({
			url: $('#loadData').data('url'),
			data: {
				country: $('#frm-application-country').val(),
				companyId: $('#frm-application-companyId').val().replace(/ /g, ''),
			},
			timeout: 5000
		});
		load.done(function(data) {
			$('#loadDataControls > span').hide();
			$('#loadDataAgain').show();
			if (data.status == 200) {
				$.each(['companyId', 'companyTaxId', 'company', 'street', 'city', 'zip', 'country'], function(key, value) {
					$('#frm-application-' + value).val(data[value]);
				});
			} else if (data.status == 400) {
				$('#loadDataNotFound').show();
			} else {
				$('#loadDataError').show();
			}
		});
		load.fail(function() {
			$('#loadDataControls > span').hide();
			$('#loadDataAgain').show();
			$('#loadDataError').show();
		});
	};
	$('#loadData a').click(APPLICATION.loadData);
	$('#loadDataAgain a').click(APPLICATION.loadData);
	$('#loadData').show();
});
