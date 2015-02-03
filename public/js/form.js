$(function() {
  var forms = $('form.content-submission');

  forms.each(function() {
    var form = $(this);
    var imageUploadToggles = form.find('.image-upload.toggle');
    var requiredSelects = form.find('select.required');

    // show/hide image upload inputs
    imageUploadToggles.change(function() {
      var toggle = $(this);

      toggle.next('.image-upload.inputs').toggle();
    });

    form.submit(function() {

      // Required fields of Craft Category field type
      // with empty values are not correctly validated for presence
      // unless they are explicitly omitted from the HTTP POST
      // via removing the name attribute of input/select field.
      requiredSelects.each(function() {
        var select = $(this);

        if (select.val().length == 0) {
          select.removeAttr('name');
        }
      });

      // Remove image upload inputs if hidden
      imageUploadToggles.each(function() {
        var toggle = $(this);

        if (! toggle.is(':checked')) {
          toggle.next('.image-upload.inputs').remove();
        }
      });
    });
  });
});
