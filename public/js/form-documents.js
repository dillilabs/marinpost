(function($) {
    $.fn.formDocuments  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            var links = form.find('a.document-field');
            var openLink = links.filter('.open');
            var closeLink = links.filter('.close');
            var documentFieldInputs = form.find('.document-field.inputs');

            //-----------------------
            // Events
            //-----------------------

            // Open document
            openLink.click(function(e) {
              e.preventDefault();
              documentFieldInputs.show();
              openLink.hide();
            });

            // Close document
            closeLink.click(function(e) {
              e.preventDefault();
              documentFieldInputs.hide();
              openLink.show();
            });
        });
    };
}(jQuery));
