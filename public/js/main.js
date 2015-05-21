function rejigger() {
  if (window.matchMedia("(min-width: 51em)").matches) {
    $('.header-wrapper').append($('#mini-menu')); // Move #mini-menu to the end of the header wrapper
    $('.sidebar').prepend($('#support')); // Move the #support into .sidebar
    $('#share').after($('#donate')); // Move #donate after #share
    $('#donate').after($('#subscribe')); // Move #subscribe after #donate
    $('.sidebar, #support, #mini-menu').show(); // Show these which are hidden until page is loaded
  } else {
    $('header').after($('#mini-menu')); // Move #mini-menu after header
    $('#mini-menu').after($('#support')); // Move #support after #mini-menu
    $('#donate').after($('#subscribe')); // Move #subscribe after #donate
    $('#subscribe').after($('#share')); // Move #share after #subscribe-menu
  };
};

$(function() {
  var htmlBody = $('html, body');
  var searchFieldset = $('#search fieldset');
  var closePreviewWindow = $('.preview button.close');

  rejigger();

  $('.show-search').click(function() {
    searchFieldset.slideToggle();

    htmlBody.animate({
      scrollTop: $('body').offset().top
    }, 200);
  });

  $('.show-menu , .close').click(function() {
      // Set the effect type
    var effect = 'slide';

    // Set the options for the effect type chosen
    var options = { direction: 'left' };

    // Set the duration (default: 400 milliseconds)
    var duration = 200;
    $('nav ul ul').hide(effect, options, duration);
    $('nav').toggle(effect, options, duration);
    $('.mobile').removeClass('active');
  });

  $('.mobile').click(function() {
    $(this).toggleClass('active');
    $(this).next('ul').slideToggle();
    $('.mobile').not(this).removeClass('active');
    $('.mobile').not(this).next('ul').slideUp();
  });
  
  $('.sub-nav').each(function(){
      if($(this).children().length == 0){
        $(this).hide();
      }
      if($(this).children(':visible').length == 0){
        $(this).hide();
      }
    });
    
  if (window.matchMedia("(max-width: 51em)").matches) {
	    $('.filter h4, .sub-nav > h4').click(function() {
	       $(this).toggleClass('open');
	       $(this).siblings().slideToggle();
	       $('.sub-nav h2').hide();
	    });
	}

  $('.reset').click(function() {
  	$('.filter h5').not(':last-of-type').addClass('closed');
  	$('.filter h5').removeClass('active');
  	$('.filter ul').slideUp('fast');
  });

  $('.my-content').click(function() {
  	$(this).toggleClass('active');
  });

  closePreviewWindow.click(function() {
    window.close();
  });
});

$(window).on('resize', function(){
  rejigger();
});
