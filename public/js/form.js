$(function() {
  var forms = $('form.content-submission');

  forms.each(function() {
    var form = $(this);
    var arraySelects = form.find('select.array');
    var optionalFieldToggles = form.find('.optional-field.toggle');

    // show/hide optional field inputs
    optionalFieldToggles.change(function() {
      var toggle = $(this);

      toggle.next('.optional-field.inputs').toggle();
    });

    form.submit(function() {

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
