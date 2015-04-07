(function($) {
    $.fn.forms  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            // Select Image, Document, Location and Topic
            var arrayInputSelects = form.find('select.array');

            // Containers for optional Locations and Topics
            var optionalCategoryContainers = form.find('.optional-category-field.inputs');

            // Link to Media
            var mediaTypeSelect = form.find('input[type=radio][name=mediaTypeSelect]');

            // Section ID
            var sectionId = form.find('input[name=sectionId]');

            // Entry ID
            var entryId = form.find('input[name=entryId]');

            // Entry status
            var entryEnabled = form.find('input[name=enabled]');

            var dateFields = form.find('input.date');

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

            // Optional Locations and Topics are removed in the UI by hiding them,
            // so it is necessary to actually remove them prior to POST.
            var removeHiddenOptionalCategories = function() {
              optionalCategoryContainers.not(':visible').remove();

            };

            var removeEmptyImageFields = function() {
              var image = form.find('select[name$="[fields][image][]"]');
              var by = form.find('input[type=text][name$="[fields][by]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (image.val() == '') {
                image.remove();

                // If all fields are empty then assume no image
                // and explicitly remove all of the associated fields
                // else Craft will assume incomplete data and return validation errors.
                if (by.val().trim().length == 0) {
                  form.find('.image-field.inputs').remove();
                }
              }
            };

            var removeEmptyDocumentFields = function() {
              var doc = form.find('select[name$="[fields][document][]"]');
              var title = form.find('input[type=text][name$="[fields][documentTitle]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (doc.val() == '') {
                doc.remove();

                // If all fields are empty then assume no document
                // and explicitly remove all of the associated fields
                // else Craft will assume incomplete data and return validation errors.
                if (title.val().trim().length == 0) {
                  form.find('.document-field.inputs').remove();
                }
              }

            };

            //-----------------------
            // Hooks
            //-----------------------

            dateFields.datepicker({
              dateFormat: 'mm/dd/yy'
            });

            //-----------------------
            // Events
            //-----------------------

            // Respond to Media Link type change
            mediaTypeSelect.change(onChangeMediaLinkType);

            // Set entry enabled based on submit button
            submitButtons.click(function(e) {
              var submitType = $(this).attr('data-submit');
              var section;

              switch (submitType) {
                case 'publish':
                  entryEnabled.val(1);
                  form.submit();
                  break;

                case 'save':
                  entryEnabled.val(0);
                  form.submit();
                  break;

                case 'cancel':
                  switch (sectionId.val()) {
                    case '2':
                      section = 'news';
                      break;
                    case '3':
                      section = 'blog';
                      break;
                    case '4':
                      section = 'notices';
                      break;
                    case '5':
                      section = 'letters';
                      break;
                    case '6':
                      section = 'media';
                      break;
                  }

                  document.location = '/account/'+section;
              }
            });

            // Submit the form already
            form.submit(function(e) {
              // Handle Media Link
              onSubmitMediaLink();

              removeHiddenOptionalCategories();
              removeEmptyImageFields();
              removeEmptyDocumentFields();

              // Array type input fields (eg fields[foo][]) with empty values are not correctly validated for presence by Craft.
              // Rather, they must be explicitly omitted from the request by removing the field's name attr.
              arrayInputSelects.each(function() {
                var select = $(this);

                if (select.val().length == 0) {
                  select.removeAttr('name');
                }
              });
            });
        });
    };
}(jQuery));
