(function($) {
    $.fn.userForm  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            // Redactor
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
            // Redactor
            //-----------------------

            wysiwygFields.each(function() {
              var textarea = $(this);
              var limit = textarea.attr('data-limit');
              var counters =textarea.closest('.field').find('.counter');
              var plugins = ['fullscreen', 'counter', 'limiter', 'underline'];
              var buttons = ['bold', 'italic', 'unorderedlist', 'orderedlist', 'link', 'outdent', 'indent'];

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
            // Events
            //-----------------------

            // Record change to form content. See also Redactor
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
