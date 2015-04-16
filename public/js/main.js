function rejigger() {
  if (window.matchMedia("(min-width: 51em)").matches) {
    $('header').after($('nav'));
    $('.header-wrapper').append($('#mini-menu'));
    $('.sidebar').prepend($('#support'));
    $('#share').after($('#donate'));
    $('#donate').after($('.revenue-opportunity'));
    $('.revenue-opportunity').after($('#subscribe'));
  } else {
    $('#mini-menu').after($('nav'));
    $('header').after($('#mini-menu'));
    $('#mini-menu').after($('#support'));
    $('#share').before($('#donate'));
    $('#subscribe').after($('#share'));
    $('.sidebar').prepend($('.revenue-opportunity'));
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
    $('nav').slideToggle();
  });

  $('.my-content a').click(function() {
  	$(this).toggleClass('active');
  });

  $('.sidebar').show();

});

$(window).on('resize', function(){
  rejigger();
  
});
