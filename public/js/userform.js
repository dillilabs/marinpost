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

            // Page unload
            var formChanged = false;

            var buttonClicked = null;

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

              counter.text(count + ' of ' + limit + ' characters remaining');
              return ok;
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
                  formChanged = true;
                },
                codeKeydownCallback: function(e) {
                  formChanged = true;
                },
                counterCallback: function(data) {
                  // console.log('Words: ' + data.words + ', Characters: ' + data.characters + ', Characters w/o spaces: ' + (data.characters - data.spaces));
                  counters.text(data.characters + ' of ' + limit + ' characters remaining');
                },
              });
            });

            //-----------------------
            // Events
            //-----------------------

            // Record change to form content. See also Redactor
            form.on('keyup change', 'input, select, textarea', function() {
                formChanged = true;
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

            // Set entry enabled based on submit button
            submitButtons.click(function(e) {
              buttonClicked = true;
              form.submit();
            });

            // Prevend page unload if form content has changed
            $(window).on('beforeunload', function(){
              var richText = $('#blogContent, #noticeContent');
              var richTextContent = richText.length > 0 && richText.val().length > 0;

              if((formChanged || richTextContent) && !buttonClicked) {
                return 'WARNING: Your content has not been saved. Please save your content or it will be lost.';
              }
            });
        });
    };
}(jQuery));
