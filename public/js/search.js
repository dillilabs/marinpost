$(function() {
  var simple = $('form.simple');
  var advanced = $('form.advanced');

  simple.submit(function() {
    $(this).addClass('processing');
  });

  advanced.submit(function() {
    $(this).addClass('processing').find('input[type=submit]').val('Processing').attr('disabled', 'disabled');
  });
});
