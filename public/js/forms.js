(function($) {
    $.fn.forms  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            // Primary Location or Topic
            // Select Image or Document
            var arraySelects = form.find('select.array');

            // Secondary Locations, Topics, Images, Documents
            var multipleFieldInputs = form.find('.multiple-field.inputs');

            // Link to Media
            var mediaTypeSelect = form.find('input[type=radio][name=mediaTypeSelect]');

            // Entry status
            var entryEnabled = form.find('input[name=enabled]');

            // Submit buttons
            var submitButtons = form.find('input[type=button].submit');

            //-----------------------
            // Functions
            //-----------------------

            // Update media type input and toggle other affected inputs
            var onChangeMediaLinkType = function(mediaType) {
              var type = this.value;

              form.find('input#mediaType').val(type).end()
                  .find('input.mediaLink, label.mediaLink, select.mediaLink, .file.mediaLink').hide().end()
                  .find('input.mediaLink.'+type+', label.mediaLink.'+type+', select.mediaLink.'+type+', .file.mediaLink.'+type).show();
            };

            // Munge media link fields
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

            var idFromLink = function(link) {
              return link.attr('id').split('-').splice(1, 2).join('-');

            };

            //-----------------------
            // Events
            //-----------------------

            // Respond to Media Link type change
            mediaTypeSelect.change(onChangeMediaLinkType);

            // Set entry enabled based on submit button
            submitButtons.click(function(e) {
              entryEnabled.val(this.value == 'Publish' ? 1 : 0);
              form.submit();
            });

            // Submit the form already
            form.submit(function(e) {
              // Handle Media Link
              onSubmitMediaLink();

              // Array type input fields (eg fields[foo][]) with empty values are not correctly validated for presence by Craft.
              // Rather, they must be explicitly omitted from the request by removing the field's name attr.
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
