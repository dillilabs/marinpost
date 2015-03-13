(function($) {
    $.fn.formImages  = function() {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            var links = form.find('a.image-field');
            var openLink = links.filter('.open');
            var closeLink = links.filter('.close');
            var imageFieldInputs = form.find('.image-field.inputs');
            var selectImage = form.find('select.image');
            var displayImage = $('img.display-image');
            var defaultImageUrl = '/img/default-list.png';

            //-----------------------
            // Events
            //-----------------------

            // Open image
            openLink.click(function(e) {
              e.preventDefault();
              imageFieldInputs.show();
              openLink.hide();
            });

            // Close image
            closeLink.click(function(e) {
              e.preventDefault();
              imageFieldInputs.hide();
              openLink.show();
            });

            // Show selected image
            selectImage.change(function() {
              if (selectImage.val() == '') {
                displayImage.prop('src', defaultImageUrl);
              } else {
                displayImage.prop('src', selectImage.children(':selected').attr('data-url'));
              }
            });
        });
    };
}(jQuery));
