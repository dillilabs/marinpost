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
            var limitedTextFields = form.find('textarea.limited');

            // Submit buttons
            var submitButtons = form.find('input[type=submit]');

            // Page unload
            var formChanged = false;

            var buttonClicked = null;

            //-----------------------
            // Functions
            //-----------------------

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

            // TODO Enforce limits in textareas
            limitedTextFields.each(function() {
              var field = $(this);
              var limit = field.attr('data-limit');
              var charsLeft = field.next('.characters-remaining').children('.count');

              field.keypress(function() {
                limitText(field, limit, charsLeft);
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
