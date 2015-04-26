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

            // Date picker
            var dateFields = form.find('input.date');

            // Redactor
            var wysiwygFields = form.find('textarea.wysiwyg');

            // Textarea
            var limitedTextFields = form.find('textarea.limited');

            // Submit buttons
            var submitButtons = form.find('input[type=button].submit');

            // Page unload
            var formChanged = false;

            var buttonClicked = null;

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
              var image = form.find('input[type=hidden][name$="[fields][image][]"]');
              var credit = form.find('input[type=hidden][name$="[fields][credit]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (image.length && image.val().length == 0) {
                image.remove();

                // If all fields are empty then assume no image
                // and explicitly remove all of the associated fields
                // else Craft will assume incomplete data and return validation errors.
                if (credit.length && credit.val().trim().length == 0) {
                  form.find('.image-field.inputs').remove();
                }
              }
            };

            var removeEmptyDocumentFields = function() {
              var doc = form.find('input[type=hidden][name$="[fields][document][]"]');
              var title = form.find('input[type=hidden][name$="[fields][documentTitle]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (doc.length && doc.val().length == 0) {
                doc.remove();

                // If all fields are empty then assume no document
                // and explicitly remove all of the associated fields
                // else Craft will assume incomplete data and return validation errors.
                if (title.length && title.val().trim().length == 0) {
                  form.find('.document-field.inputs').remove();
                }
              }

            };

            // TODO
            var limitText = function(field, limit, charsLeft) {
              var text = field.val();
              var count = text.length;
              var ok = true;

              if (count > limit) {
                text = text.substring(0, limit);
                field.val(text);
                ok = false;
              }

              charsLeft.text(limit > count ? limit - count : 0);
              return ok;
            };

            //-----------------------
            // Redactor
            //-----------------------

            wysiwygFields.each(function() {
              var textarea = $(this);
              var limit = textarea.attr('data-limit');
              var buttons = ['html', 'formatting', 'bold', 'italic', 'deleted', 'unorderedlist', 'orderedlist', 'outdent', 'indent', 'link', 'alignment', 'horizontalrule', 'fontfamily', 'fontsize', 'fontcolor'];
              var plugins = ['fullscreen','counter','limiter','fontsize','fontcolor','fontfamily'];

              textarea.redactor({
                minHeight: 200,
                maxHeight: 800,
                buttons: buttons,
                plugins: plugins,
                toolbarFixed: true,
                limiter: limit,
                changeCallback: function(e) {
                  formChanged = true;
                },
                codeKeydownCallback: function(e) {
                  formChanged = true;
                },
                counterCallback: function(data) {
                  // console.log('Words: ' + data.words + ', Characters: ' + data.characters + ', Characters w/o spaces: ' + (data.characters - data.spaces));
                },
              });
            });

            //-----------------------
            // Date picker
            //-----------------------

            dateFields.datepicker({
              dateFormat: 'mm/dd/yy'
            });

            //-----------------------
            // Events
            //-----------------------

            // Record change to form content. See also Redactor
            form.on('keyup change', 'input, select, textarea', function() {
                formChanged = true;
            });

            // TODO Enforce limits in textareas
            limitedTextFields.each(function() {
              var field = $(this);
              var limit = field.attr('data-limit');
              var charsLeft = field.next('.characters-remaining').children('.count');

              field.keypress(function() {
                limitText(field, limit, charsLeft);
              });
            });

            // Respond to Media Link type change
            mediaTypeSelect.change(onChangeMediaLinkType);

            // Set entry enabled based on submit button
            submitButtons.click(function(e) {
              var submitType = $(this).attr('data-submit');
              var section = '';

              switch (submitType) {
                case 'preview':
                  // document.location = $(this).attr('data-url');
                  alert('Coming Soon');
                  e.preventDefault();
                  break;

                case 'save':
                  entryEnabled.val(0);
                  buttonClicked = true;
                  form.submit();
                  break;

                case 'publish':
                  entryEnabled.val(1);
                  buttonClicked = true;
                  form.submit();
                  break;

                case 'cancel':
                  if (!confirm('Are you sure?')) {
                    e.preventDefault();
                    break;
                  }

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

                  buttonClicked = true;
                  document.location = '/account/'+section;
              }
            });

            // Prevend page unload if form content has changed
            $(window).on('beforeunload', function(){
              var richText = $('#blogContent, #noticeContent');
              var richTextContent = richText.length > 0 && richText.val().length > 0;

              if((formChanged || richTextContent) && !buttonClicked) {
                return 'WARNING: Your content has not been saved. Please save your content or it will be lost.';
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
