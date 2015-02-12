$(document).ready(function() {

	rejigger();
	
	$('#menu').click(function()  {

		$('html, body').animate({
	   		scrollTop: $("nav").offset().top
	   	}, 200);
		
	});
/*        
    $('.sign-in, .sign-out').click(function() {
       $('.sign-in,.sign-up, .sign-out, .account').toggle();
    });
*/    
    $('.show-search').click(function() {
        $('#search fieldset').slideToggle();
        $('html, body').animate({
	   		scrollTop: $("body").offset().top
	   	}, 200);
    });
    
    $('.show-menu').click(function() {
        $('html, body').animate({
	   		scrollTop: $("nav").offset().top
	   	}, 200);
    });


    $('fieldset h5').click(function () {
        $(this).toggleClass('active');
        $(this).siblings('ul').slideToggle();
    });
/*
    $('.all-topics').change(function () {
        $('.topics').prop('checked', this.checked);
    });

    $(".topics").change(function () {
        if ($(".topics:checked").length == $(".topics").length) {
            $('.all-topics').prop('checked', 'checked');
        } else {
            $('.all-topics').prop('checked', false);
        }
    });
    $('.all-locations').change(function () {
        $('.locations').prop('checked', this.checked);
    });
    
    $(".locations").change(function () {
        if ($(".locations:checked").length == $(".locations").length) {
            $('.all-locations').prop('checked', 'checked');
        } else {
            $('.all-locations').prop('checked', false);
        }
    });
*/
});

$(window).on('resize', function(){

	rejigger();
	
});

function rejigger() {
	
	if (window.matchMedia("(min-width: 51em)").matches) {
		$('header').after($('nav'));
        $('.header-wrapper').append($('#mini-menu'));
        $('.sidebar').prepend($('#support'));
        $('#subscribe').append($('#donate'));
	} else {
		$('footer').prepend($('nav'));
        $('header').after($('#mini-menu'));
        $('#mini-menu').after($('#support'));
        $('#share').before($('#donate'));
	};
		
};
