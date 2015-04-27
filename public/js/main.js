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
    var duration = 500;
    $('nav ul ul').hide(effect, options, duration);
    $('nav').toggle(effect, options, duration);
  });

  $('.my-content a').click(function() {
  	$(this).toggleClass('active');
  });

  $('.mobile').click(function() {
    var effect = 'slide';

    // Set the options for the effect type chosen
    var options = { direction: 'left' };

    // Set the duration (default: 400 milliseconds)
    var duration = 500;
    $('nav ul ul').hide(effect, options, duration);
    $(this).next('ul').toggle(effect, options, duration);
  });
  
  $('.sub-nav').each(function(){
      if($(this).children().length == 0){
        $(this).hide();
      }
      if($(this).children(':visible').length == 0){
        $(this).hide();
      }
    });
    
    $('.filter h4, .sub-nav > h4').click(function() {
       $(this).toggleClass('open');
       $(this).siblings().slideToggle();
       $('.sub-nav h2').hide();
    });
});

$(window).on('resize', function(){
  rejigger();
});
