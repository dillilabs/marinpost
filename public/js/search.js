$(function() {
  var simple = $('form.simple');
  var advanced = $('form.advanced');
  var results = $('ul.posts.search');

  simple.submit(function() {
    $(this).addClass('processing');
  });

  advanced.submit(function() {
    $(this).addClass('processing').find('input[type=submit]').val('Processing').attr('disabled', 'disabled');
  });

  results.on('click', 'a.detail', function(e) {
    e.preventDefault();
    document.location = this.href + document.location.search;
  });
});
