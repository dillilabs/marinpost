(function($) {
    $.fn.contentForm  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            // Form action
            var inputAction = form.find('input[name=action]');

            // Section ID
            var sectionId = form.find('input[name=sectionId]');

            // Entry ID
            var entryId = form.find('input[name=entryId]');

            // Entry status
            var entryEnabled = form.find('input[name=enabled]');

            // Select Location and Topic
            var arrayInputSelects = form.find('select.array');

            // Containers for optional Locations and Topics
            var optionalCategoryContainers = form.find('.optional-category-field.inputs');

            // Link to Media
            var mediaTypeSelect = form.find('input[type=radio][name=mediaTypeSelect]');

            // Date picker
            var dateFields = form.find('input.date');

            // Redactor
            var wysiwygFields = form.find('textarea.wysiwyg');
            var excessContent = false;

            // Textarea
            var limitedTextareaFields = form.find('textarea.limited');

            // Add/remove category links
            var categoryLinks = form.find('a.optional-category-field');
            var addCategoryLink = categoryLinks.filter('.add');
            var removeCategoryLink = categoryLinks.filter('.remove');

            // Submit buttons
            var submitButtons = form.find('input[type=button].submit');

            // Page unload warning
            var contentChanged = false;
            var submitButtonClicked = null;

            //-----------------------
            // Functions
            //-----------------------

            // Update media type input and toggle other affected inputs
            var onChangeMediaLinkType = function(mediaType) {
              var type = this.value;

              form.find('input#mediaType').val(type);
              form.find('input.mediaLink, label.mediaLink, select.mediaLink, .file.mediaLink, .mediaLink.wrapper').hide().end();
              form.find('input.mediaLink.'+type+', label.mediaLink.'+type+', select.mediaLink.'+type+', .file.mediaLink.'+type+', .mediaLink.wrapper.'+type).show();
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
              // fields[blogImages][]
              // fields[mediaImages][]
              // fields[newsImages][]
              // fields[noticeImages][]
              var image = form.find('input[type=hidden][name$="Images][]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (image.length && image.val().length == 0) {
                image.remove();
              }
            };

            var removeEmptyDocumentFields = function() {
              // fields[blogDocuments][]
              // fields[noticeDocuments][]
              // fields[mediaLink][???][fields][document][]
              var document = form.find('input[type=hidden][name$="Documents][]"], input[type=hidden][name$="[fields][document][]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (document.length && document.val().length == 0) {
                document.remove();
              }

            };

            var countableCharacter = function(event) {
              var key = event.which;
              var ctrl = event.ctrlKey || event.metaKey;

              if (key == 8 || key == 46 || key == 27 || key == 16 || (ctrl && key == 65) || (ctrl && key == 82) || (ctrl && key == 116)) {
                // bs, del, esc, shift, ctrl-a, ctrl-r, ctrl-f5
                return false;
              }

              return true;
            };

            // Limit chars of regular textarea field.
            var limitTextarea = function(field, limit, counter) {
              var text = field.val();
              var count = text.length;

              if (count > limit) {
                text = text.substring(0, limit-1);
                field.val(text);
              }
            };

            // Count chars of regular textarea field.
            var countTextarea = function(field, limit, counter) {
              var count = field.val().length;
              counter.text(count + ' of ' + limit + ' characters used');
            };

            //-----------------------
            // Preview
            //-----------------------

            var enablePreview = function() {
              inputAction.val('mpEntry/previewEntry');
              form.attr('target', '_blank');
            };

            var disablePreview = function() {
              inputAction.val('entries/saveEntry');
              form.removeAttr('target');
            };

            //-----------------------
            // Redactor
            //-----------------------

            wysiwygFields.each(function() {
              var textarea = $(this);
              var limit = textarea.attr('data-limit');
              var counters =textarea.closest('.field').find('.counter');
              var plugins = ['underline', 'counter', 'limiter'];
              var buttons = ['bold', 'italic', 'link'];

              switch (textarea.attr('name')) {
                case 'fields[letterContent]':
                  plugins = plugins.concat(['fontsize']);
                  buttons = buttons.concat(['fontsize', 'unorderedlist', 'orderedlist', 'outdent', 'indent', 'horizontalrule']);
                  break;
                case 'fields[blogContent]':
                case 'fields[noticeContent]':
                  plugins = plugins.concat(['addimage','fontsize', 'fontcolor', 'fontfamily']);
                  buttons = ['html'].concat(buttons);
                  buttons = buttons.concat(['fontsize', 'fontcolor', 'fontfamily', 'unorderedlist', 'orderedlist', 'outdent', 'indent', 'alignment', 'horizontalrule']);
                  break;
              }

              plugins = plugins.concat('fullscreen'); // rightmost button

              textarea.redactor({
                minHeight: 200,
                maxHeight: 800,
                plugins: plugins,
                buttons: buttons,
                toolbarFixed: true,
                limiter: limit,
                changeCallback: function(e) {
                  contentChanged = true;
                },
                codeKeydownCallback: function(e) {
                  contentChanged = true;
                },
                counterCallback: function(data) {
                  if (data.characters <= limit) {
                    excessContent = false;
                    counters.text(data.characters + ' of ' + limit + ' characters used');
                  } else {
                    excessContent = true;
                    counters.text('CONTENT LIMIT EXCEEDED: editing disabled until excess is removed.');
                  }
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

            // Add optional category
            addCategoryLink.click(function(e) {
              var link = $(this);
              var id = link.attr('data-id');

              $('#'+id).show();
              link.nextAll('a.optional-category-field.add:first').show();
              link.hide();
              e.preventDefault();
            });

            // Remove optional category
            removeCategoryLink.click(function(e) {
              var link = $(this);
              var id = link.attr('data-id');
              var input = link.closest('.optional-category-field.inputs');

              input.hide().next('.add').hide();
              input.hide().prev('.add').show();
              e.preventDefault();
            });

            // Enforce limits in regular textareas
            limitedTextareaFields.each(function() {
              var field = $(this);
              var limit = field.attr('data-limit');
              var counter = field.closest('.field').find('.counter');

              field.keyup(function(e) {
                if (countableCharacter(e)) {
                  limitTextarea(field, limit, counter);
                  countTextarea(field, limit, counter);
                }
              });
            });

            // Respond to Media Link type change
            mediaTypeSelect.change(onChangeMediaLinkType);

            // Record change to form content. See also Redactor
            form.on('keyup change', 'input, select, textarea', function() {
              contentChanged = true;
            });

            // Set entry enabled based on submit button
            submitButtons.click(function(e) {
              var submitType = $(this).attr('data-submit');
              var section = '';

              if (excessContent && submitType != 'cancel') {
                alert('Content limit exceeded: please remove excess.');
                return false;
              }

              submitButtonClicked = true;

              switch (submitType) {
                case 'preview':
                  enablePreview();
                  form.submit();
                  submitButtonClicked = false;
                  break;

                case 'save':
                  entryEnabled.val(0);
                  disablePreview();
                  $(this).prop('disabled', true).val('Saving...');
                  form.submit();
                  break;

                case 'publish':
                  entryEnabled.val(1);
                  disablePreview();
                  $(this).prop('disabled', true).val('Publishing...');
                  form.submit();
                  break;
                
                case 'submitForReview':
                    $(this).prop('disabled', true).val('Submitting for Review...');
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

                  document.location = '/account/'+section;
              }
            });

            // Prevent page unload if form content has changed
            $(window).on('beforeunload', function() {
              if(contentChanged && !submitButtonClicked) {
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
