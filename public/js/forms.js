(function($) {
    $.fn.forms  = function() {
        return this.each(function() {
            var form = $(this);

            // Primary Location or Topic
            // Select Image or Document
            var arraySelects = form.find('select.array');

            // Secondary Locations / Topics, Images, Documents
            var multipleFieldLinks = form.find('a.multiple-field');
            var addMultipleFieldLink = multipleFieldLinks.filter('.add');
            var removeMultipleFieldLink = multipleFieldLinks.filter('.remove');
            var multipleFieldInputs = form.find('.multiple-field.inputs');

            // Link to Media
            var mediaTypeSelect = form.find('input[type=radio][name=mediaTypeSelect]');

            // Submit buttons
            var submitButtons = form.find('input[type=button].submit');

            // Entry status
            var entryEnabled = form.find('input[name=enabled]');

            var onChangeMediaLinkType = function(mediaType) {
              var type = this.value;

              // update media type input
              // and toggle other affected inputs
              form.find('input#mediaType').val(type).end()
                  .find('input.mediaLink, label.mediaLink, select.mediaLink, .file.mediaLink').hide().end()
                  .find('input.mediaLink.'+type+', label.mediaLink.'+type+', select.mediaLink.'+type+', .file.mediaLink.'+type).show();
            };

            var onSubmitMediaLink = function() {
              var mediaType = mediaTypeSelect.filter(':checked');
              var originalMediaType, originalMediaLinkBlockId;

              mediaType = mediaType.length > 0 ? mediaType.val() : false;

              if (mediaType) {
                // remove unselected media type inputs from the POST
                form.find('input.mediaLink, select.mediaLink').not('.'+mediaType).remove();

                originalMediaType = form.find('input#originalMediaType');
                originalMediaType = originalMediaType.length > 0 ? originalMediaType.val() : false;

                // if media type has changed
                if (mediaType != originalMediaType) {
                  originalMediaLinkBlockId = form.find('input#originalMediaLinkBlockId');
                  originalMediaLinkBlockId = originalMediaLinkBlockId.length > 0 ? originalMediaLinkBlockId.val() : false;

                  if (originalMediaLinkBlockId) {
                    // then for all affected matrix fields
                    // replace the portion of the name attribute which references
                    // the old Matrix block id, with "new1"

                    form.find('input, select').filter('[name^="fields[mediaLink]"]').each(function() {
                      this.name = this.name.replace(originalMediaLinkBlockId.toString(), 'new1');
                    });
                  }
                }

                // remove non-model input
                mediaTypeSelect.remove();
              }
            };

            // Add Location/Topic/Image/Document
            addMultipleFieldLink.click(function(e) {
              e.preventDefault();
              var link = $(this);
              var idSuffix = link.attr('id').split('-').splice(1, 2).join('-');

              $('#input-'+idSuffix).show();
              link.nextAll('a.multiple-field.add:first').show().end()
                  .hide();
            });

            // Remove Location/Topic/Image/Document
            removeMultipleFieldLink.click(function(e) {
              e.preventDefault();
              var link = $(this);
              var idSuffix = link.attr('id').split('-').splice(1, 2).join('-');

              link.closest('.multiple-field.inputs').hide();
              $('#add-'+idSuffix).show();
            });

            mediaTypeSelect.change(onChangeMediaLinkType);

            // Set entry enabled based on submit button
            submitButtons.click(function(e) {
              entryEnabled.val(this.value == 'Publish' ? 1 : 0);
              form.submit();
            });

            form.submit(function(e) {
              onSubmitMediaLink();

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
    };
}(jQuery));
