$(document).ready(function() {

	var win = $(this); //this = window
	
	if (window.matchMedia("(min-width: 60em)").matches) {
			$('header').append($('nav'));
            $('header').after($('.home #support'));
            $('#support').append($('.notices'));

		} else {
			$('footer').prepend($('nav'));
            $('#aside').append($('.home #support'));
            $('main').append($('.notices'));
		}
		
	if($('body').css('max-width') === '1200px') {
		$('header').append($('nav'));
        $('header').after($('.home #support'));
        $('#support').append($('.notices'));

	};
	
	$('#menu').click(function()  {

		$('html, body').animate({
	   		scrollTop: $("nav").offset().top
	   	}, 200);
		
	});
        
    //$('.sign-in a').click(function() {
    //   $('.sign-in').toggle();
    //   $('.account').toggle();
    //    
    //});
    
    $('.show-search').click(function() {
        $('#search').slideToggle();
        $('html, body').animate({
	   		scrollTop: $("#search").offset().top
	   	}, 200);
    })


    $('fieldset h5').click(function () {
        $(this).toggleClass('active');
        $(this).siblings('ul').slideToggle();
    });
    //$('.all-topics').change(function () {
    //    $('.topics').prop('checked', this.checked);
    //});
    //
    //$(".topics").change(function () {
    //    if ($(".topics:checked").length == $(".topics").length) {
    //        $('.all-topics').prop('checked', 'checked');
    //    } else {
    //        $('.all-topics').prop('checked', false);
    //    }
    //});
    //$('.all-locations').change(function () {
    //    $('.locations').prop('checked', this.checked);
    //});
    //
    //$(".locations").change(function () {
    //    if ($(".locations:checked").length == $(".locations").length) {
    //        $('.all-locations').prop('checked', 'checked');
    //    } else {
    //        $('.all-locations').prop('checked', false);
    //    }
    //});

});

$(window).on('resize', function(){

	var win = $(this); //this = window
	
	if (window.matchMedia("(min-width: 60em)").matches) {
		$('header').append($('nav'));
        $('header').after($('.home #support'));
        $('#support').append($('.notices'));

	} else {
		$('footer').prepend($('nav'));
        $('#aside').append($('.home #support'));
        $('aside').after($('.notices'));
	}
	

});
