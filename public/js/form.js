$(function() {
  var forms = $('form.content-submission');
  var requiredSelectionsSelector = 'select.required';
  var optionalFilesSelector = 'input[type=file].optional';

  forms.submit(function(e) {
    var form = $(this);

    // Required fields of Craft Category field type
    // with empty values are not correctly validated for presence
    // unless they are explicitly removed from the HTTP POST.
    form.find(requiredSelectionsSelector).each(function() {
      var field = $(this);

      if (field.val().length == 0) {
        // remove the name attribute of input field to prevent inclusion in POST
        field.removeAttr('name');
      }
    });

    // Optional input type=file fields have associated fields
    // which are required if a file is selected for upload.
    // To prevent the server from incorrectly invalidating the optional file
    // and its associated fields IF NO FILE IS SELECTED
    // we need to omit the fields from the HTTP POST.
    form.find(optionalFilesSelector).each(function() {
      var field = $(this);
      var name = field.attr('name').split(']')[0]+']'; 
      
      if (field.val().length == 0) {
        form.find('input[name^="'+name+'"]').each(function() {
          // remove the name attribute of input field to prevent inclusion in POST
          $(this).removeAttr('name');
        });
      }
    });
  });
});
