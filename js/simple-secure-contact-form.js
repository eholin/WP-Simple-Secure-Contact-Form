jQuery(document).ready(function($){
	'use strict';

	var $inputArea = $('.input-area');
	autosize($inputArea);

	$inputArea.focus(function(){
		var $parent = $(this).closest('div');
		$parent.children('.textarea-label').css('transform', 'translate(0,-100px)');
	});

	$inputArea.focusout(function(){
		if ($(this).val().length === 0) {
			var $parent = $(this).closest('div');
			$parent.children('.textarea-label').css('transform', 'translate(0,0px)');
		}
	});

	$('.lptw-button').click(function(e){
		var $parent = $(this).closest('div'),
			$parent_form = $parent.find('form'),
			$buttonSpinner = $(this).find('.lptw-button-spinner'),
			gaEventCategory = $parent.data('eventcategory'),
			gaEventAction = $parent.data('eventaction'),
			gaEventLabel = $parent.data('eventlabel'),
			ymCounterID = $parent.data('counterid'),
			yaCounter = 'yaCounter' + ymCounterID,
			ymTargetName = $parent.data('targetname'),
			form_data = $parent_form.serialize() + '&action=contact_form';

		$buttonSpinner.css('display', 'inline-block');
		$.ajax({
			type: "post",
			dataType: "json",
			url: myAjax.ajaxurl,
			data: form_data,
			success: function(response){
				$parent.addClass('mode-send');
				$parent.children('.after-send-text').css('display', 'block');
				$buttonSpinner.css('display', 'none');
				if (response != 0) {
					// google analytics
					if (gaEventCategory != '' && gaEventAction != '') {
						ga('send', 'event', gaEventCategory, gaEventAction, gaEventLabel);
					}
					// yandex metrika
					if (ymCounterID != '' && ymTargetName != '') {
						window[yaCounter].reachGoal(ymTargetName);
					}
				}
			}
		});
		e.preventDefault();
	});

	$('.close-send-mode').click(function(e){
		e.preventDefault();
		var $parent = $(this).closest('div');
		$parent.removeClass('mode-send');
		$parent.children('.after-send-text').css('display', 'none');
	});

	// phone mask
	var $phoneInput = $('.your-phone-input');
	if ($phoneInput.length > 0) {
		var userMask = $phoneInput.data('mask');
		if (userMask != '') {
			$phoneInput.mask(userMask);
		}
	}

});
