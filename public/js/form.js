$(function() {
  var forms = $('form.content-submission');
  var requiredSelectionsSelector = 'select.required';

  forms.submit(function(e) {
    // Required fields of field type = Category are not correctly validated
    // unless they are explicitly removed from the HTTP POST
    $(this).find(requiredSelectionsSelector).each(function() {
      var field = $(this);
      if (field.val().length == 0) {
        field.removeAttr('name');
      }
    });
  });
});
