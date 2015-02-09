$(function() {
  // Front-end content submission forms
  var forms = $('form.content-submission');

  forms.each(function() {
    var form = $(this);

    // Primary Location / Topic
    var arraySelects = form.find('select.array');

    // Link to Media
    var mediaType = form.find('input[type=radio][name=mediaType]');

    // Secondary Locations / Topics, Images, Documents
    var multipleFieldLinks = form.find('a.multiple-field');
    var addMultipleFieldLink = multipleFieldLinks.filter('.add');
    var removeMultipleFieldLink = multipleFieldLinks.filter('.remove');
    var multipleFieldInputs = form.find('.multiple-field.inputs');

    // Add Location/Topic/Image/Document
    addMultipleFieldLink.click(function(e) {
      e.preventDefault();
      var link = $(this);
      var idSuffix = link.attr('id').split('-').splice(1, 2).join('-');
      $('#input-'+idSuffix).show();
      link.nextAll('a.multiple-field.add:first').show();
      link.hide();
    });

    // Remove Location/Topic/Image/Document
    removeMultipleFieldLink.click(function(e) {
      e.preventDefault();
      var link = $(this);
      var idSuffix = link.attr('id').split('-').splice(1, 2).join('-');
      link.closest('.multiple-field.inputs').hide();
      $('#add-'+idSuffix).show();
    });

    // Link to Media
    mediaType.change(function() {
      var type = this.value;
      form.find('input#mediaType').val(type);
      form.find('input.media, label.media').hide().end().find('input.media.'+type, 'label.media.'+type).show();
    });

    form.submit(function(e) {
      // Link to Media only
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

      // Remove hidden Locations, Topics, Images, Documents
      multipleFieldInputs.not(':visible').remove();
    });
  });
});
