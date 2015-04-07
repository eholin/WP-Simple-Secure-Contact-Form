jQuery(document).ready(function($) {
    'use strict';
    autosize($('.input-area'));

    $('.input-area').focus(function() {
        var parent = $(this).closest('div').attr('id');
        $('#' + parent).children('.textarea-label').css('transform', 'translate(0,-100px)');
    });

    $('.input-area').focusout(function() {
        if ($(this).val().length === 0) {
            var parent = $(this).closest('div').attr('id');
            $('#' + parent).children('.textarea-label').css('transform', 'translate(0,0px)');
        }
    });

    $('.lptw-button').click(function(e) {
        var parent = $(this).closest('div').attr('id');
        var parent_form = $(this).closest('form').attr('id');
        var form_data = $('#' + parent_form).serialize() + '&action=contact_form';

        $.ajax({
            type: "post",
            dataType: "json",
            url: myAjax.ajaxurl,
            data: form_data,
            success: function(response) {
                if (response != 0) {
                    $('#' + parent).addClass('mode-send');
                    $('#' + parent).children('.after-send-text').css('display', 'block');
                } else {
                    $('#' + parent).addClass('mode-send');
                    $('#' + parent).children('.after-send-text').css('display', 'block');
                }
            }
        });
        e.preventDefault();
    });

    $('.close-send-mode').click(function(e) {
        var parent = $(this).closest('div').attr('id');
        $('#' + parent).removeClass('mode-send');
        $('#' + parent).children('.after-send-text').css('display', 'none');
        e.preventDefault();
    });

});
