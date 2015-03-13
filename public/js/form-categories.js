(function($) {
    $.fn.formCategories  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            var links = form.find('a.optional-category-field');
            var addLink = links.filter('.add');
            var removeLink = links.filter('.remove');

            //-----------------------
            // Functions
            //-----------------------

            var idFromLink = function(link) {
              return link.attr('id').split('-').splice(1, 2).join('-');

            };

            //-----------------------
            // Events
            //-----------------------

            // Add optional category
            addLink.click(function(e) {
              e.preventDefault();
              var link = $(this);
              var id = idFromLink(link);

              $('#input-'+id).show();
              link.nextAll('a.optional-category-field.add:first').show().end()
                  .hide();
            });

            // Remove optional category
            removeLink.click(function(e) {
              e.preventDefault();
              var link = $(this);
              var id = idFromLink(link);

              link.closest('.optional-category-field.inputs').hide();
              $('#add-'+id).show();
            });

        });
    };
}(jQuery));
