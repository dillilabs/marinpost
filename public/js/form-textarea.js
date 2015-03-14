(function($) {
    $.fn.formTextarea  = function(options) {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            var limitedTextFields = form.find('textarea.limited');

            //-----------------------
            // Functions
            //-----------------------

            var limitText = function(field, charsLeft) {
                var text = field.val();
                var count = text.length;
                var limit = field.attr('data-limit');
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
            // Hooks
            //-----------------------

            limitedTextFields.each(function() {
                var field = $(this);
                var charsLeft = field.next('.characters-remaining').children('.count');

                field.keypress(function() {
                    limitText(field, charsLeft);
                });
            });
        });
    };
}(jQuery));
