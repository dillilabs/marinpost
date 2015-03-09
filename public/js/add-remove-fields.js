(function($) {
    $.fn.addRemove  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            // Secondary Locations, Topics, Images, Documents
            var multipleFieldLinks = form.find('a.multiple-field');
            var addMultipleFieldLink = multipleFieldLinks.filter('.add');
            var removeMultipleFieldLink = multipleFieldLinks.filter('.remove');
            var multipleFieldInputs = form.find('.multiple-field.inputs');

            //-----------------------
            // Functions
            //-----------------------

            var idFromLink = function(link) {
              return link.attr('id').split('-').splice(1, 2).join('-');

            };

            //-----------------------
            // Events
            //-----------------------

            // Add Location/Topic/Image/Document
            addMultipleFieldLink.click(function(e) {
              e.preventDefault();
              var link = $(this);
              var id = idFromLink(link);

              $('#input-'+id).show();
              link.nextAll('a.multiple-field.add:first').show().end()
                  .hide();
            });

            // Remove Location/Topic/Image/Document
            removeMultipleFieldLink.click(function(e) {
              e.preventDefault();
              var link = $(this);
              var id = idFromLink(link);

              link.closest('.multiple-field.inputs').hide();
              $('#add-'+id).show();
            });

        });
    };
}(jQuery));
