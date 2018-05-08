(function($) {
    $.fn.userForm  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            // CKEditor
            var wysiwygFields = form.find('textarea.wysiwyg');
            var excessContent = false;

            // Submit buttons
            var submitButtons = form.find('input[type=submit]');

            // Page unload warning
            var contentChanged = false;
            var submitButtonClicked = null;

            //-----------------------
            // Functions
            //-----------------------

            var removeEmptyImageFields = function() {
              var image = form.find('input[type=hidden][name="fields[userImage][]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (image.length && image.val().length == 0) {
                image.remove();
              }
            };

            var removeEmptyDocumentFields = function() {
              var document = form.find('input[type=hidden][name="fields[userDocuments][]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (document.length && document.val().length == 0) {
                document.remove();
              }

            };

            var temporarilyDisableSubmit = function(button) {
              var label = button.val();
              button.prop('disabled', true).val('Processing...');

              window.setTimeout(function(button) {
                button.prop('disabled', false).val(label);
              }, 2000, button, label);
            };

            //-----------------------
            // CKEditor
            //-----------------------

            wysiwygFields.each(function() {
              var maxCharCount = $(this).attr('data-limit');

              // Configure CKEditor toolbar and char counter/limiter
              CKEDITOR.replace(this, {
                customConfig: '/js/ckeditor/config.js',
                toolbar: [
                  {
                    name: 'userForm',
                    items: [
                      'Bold',
                      'Italic',
                      'Underline',
                      'Link',
                      'BulletedList',
                      'NumberedList',
                      'Outdent',
                      'Indent',
                      'Undo',
                      'Redo',
                      'Maximize'
                    ],
                  }
                ],
                wordcount: {
                  showParagraphs: false,
                  showWordCount: false,
                  showCharCount: true,
                  countSpacesAsChars: true,
                  countHTML: false,
                  countLineBreaks: false,
                  maxWordCount: -1,
                  maxCharCount: maxCharCount,
                  pasteWarningDuration: 0,
                },
              });

              // Record change to form content. See also form.on('keyup_change', ...)
              $.each(CKEDITOR.instances, function() {
                this.on('change', function() {
                  contentChanged = true;
                });
              });
            });

            //-----------------------
            // Events
            //-----------------------

            // Record change to form content. See also CKEditor
            form.on('keyup change', 'input, select, textarea', function() {
              contentChanged = true;
            });

            // Submit
            submitButtons.click(function(e) {
              if (excessContent) {
                alert('Content limit exceeded: please remove excess.');
                return false;
              }
              removeEmptyImageFields();
              removeEmptyDocumentFields();
              submitButtonClicked = true;
              temporarilyDisableSubmit($(this));
              form.submit();
            });

            // Prevend page unload if form content has changed
            $(window).on('beforeunload', function(){
              if(contentChanged && !submitButtonClicked) {
                return 'WARNING: Your content has not been saved. Please save your content or it will be lost.';
              }
            });
        });
    };
}(jQuery));
