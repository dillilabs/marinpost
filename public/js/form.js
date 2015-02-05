$(function() {
  var forms = $('form.content-submission');

  forms.each(function() {
    var form = $(this);
    var arraySelects = form.find('select.array');
    var optionalFieldToggles = form.find('.optional-field.toggle');
    var mediaType = form.find('input[type=radio][name=mediaType]');

    // show/hide optional field inputs
    optionalFieldToggles.change(function() {
      var toggle = $(this);

      toggle.next('.optional-field.inputs').toggle();
    });

    mediaType.change(function() {
      var type = this.value;

      form.find('input#mediaType').val(type);
      form.find('input.media, label.media').hide().end().find('input.media.'+type, 'label.media.'+type).show();
    });

    form.submit(function(e) {
      var selectedMediaType = form.find('input[type=radio][name=mediaType]:checked');

      if (selectedMediaType.length > 0) {
        form.find('input.media').not('.'+selectedMediaType.val()).removeAttr('name');
      }

      // Array type input fields (eg fields[foo][]) with empty values
      // are not correctly validated for presence by Craft.
      // Rather, they must be explicitly omitted from the request
      // by removing the field's name attr.
      arraySelects.each(function() {
        var select = $(this);

        if (select.val().length == 0) {
          select.removeAttr('name');
        }
      });

      // Remove optional field inputs if hidden
      optionalFieldToggles.each(function() {
        var toggle = $(this);

        if (! toggle.is(':checked')) {
          toggle.next('.optional-field.inputs').remove();
        }
      });
    });
  });
});
