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

  $('.show-menu').click(function() {
    htmlBody.animate({
      scrollTop: $('nav').offset().top
    }, 200);
  });
});

$(window).on('resize', function(){
  rejigger();
});
