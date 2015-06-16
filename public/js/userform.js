(function($) {
    $.fn.userForm  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            // Redactor
            var wysiwygFields = form.find('textarea.wysiwyg');

            // Textarea
            var limitedTextareaFields = form.find('textarea.limited');

            // Submit buttons
            var submitButtons = form.find('input[type=submit]');

            // Page unload warning
            var contentChanged = false;
            var submitButtonClicked = null;

            //-----------------------
            // Functions
            //-----------------------

            // Limit regular textarea fields
            var limitTextarea = function(field, limit, counter) {
              var text = field.val();
              var count = text.length;
              var ok = true;

              if (count > limit) {
                text = text.substring(0, limit);
                field.val(text);
                ok = false;
              }

              counter.text(count + ' of ' + limit + ' characters used');
              return ok;
            };

            var removeEmptyImageFields = function() {
              var image = form.find('input[type=hidden][name="fields[userImage][]"]');

              // Craft doesn't take kindly to empty multiple fields
              if (image.length && image.val().length == 0) {
                image.remove();
              }
            };

            var temporarilyDisableSubmit = function(btn) {
              var label = btn.val();
              btn.prop('disabled', true).val('Submitting...');

              window.setTimeout(function(btn) {
                btn.prop('disabled', false).val(label);
              }, 1500, btn, label);
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
                  // console.log('Words: ' + data.words + ', Characters: ' + data.characters + ', Characters w/o spaces: ' + (data.characters - data.spaces));
                  counters.text(data.characters + ' of ' + limit + ' characters used');
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

            // Enforce char limits in regular textareas
            limitedTextareaFields.each(function() {
              var field = $(this);
              var limit = field.attr('data-limit');
              var counter = field.closest('.field').find('.counter');

              field.keypress(function() {
                limitText(field, limit, counter);
              });
            });

            // Submit
            submitButtons.click(function(e) {
              removeEmptyImageFields();
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
