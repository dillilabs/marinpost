(function($) {
    $.fn.formWysiwyg = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            var wysiwygFields = form.find('textarea.wysiwyg');

            //-----------------------
            // Hooks
            //-----------------------

            wysiwygFields.redactor({
                minHeight: 200,
                maxHeight: 800,
                buttons: ['html','formatting','bold','italic','unorderedlist','orderedlist','link','image','video'],
                plugins: ['fullscreen'],
                toolbarFixed: true
            });
        });
    };
}(jQuery));
