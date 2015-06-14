(function($) {
    $.fn.scrollingContent  = function(options) {
        var config = $.extend({}, $.fn.scrollingContent.defaults, options);

        return this.each(function() {
            var content = $(this);
            var scrollPositionThreshold = 0.85;
            var isLoadingContent = false;
            var contentLengthThreshold = 20;
            var endOfContent = false;

            //----------
            // Functions
            //----------

            var currentContentLength = function() {
              return $('.listing').length;
            };
          
            var loadMoreContent = function() {
              var offset = currentContentLength();

              console.log('hasFeaturedPost', config.hasFeaturedPost);
              if (!config.hasFeaturedPost) {
                // featured entry is simply the first entry, so skip it
                offset += 1;
              }

              isLoadingContent = true;

              $.get(
                '/unfilter?offset='+offset,
                function(data) {
                  if (data.length > contentLengthThreshold) {
                    content.append(data);
                  } else {
                    endOfContent = true;
                  }

                  isLoadingContent = false;
                }
              );
            };

            //-------
            // Events
            //-------
          
            $(window).scroll(function() {
              var currentPosition = $(window).scrollTop() / ($(document).height() - $(window).height());
        
              if (isLoadingContent || endOfContent) return;
        
              if (currentPosition > scrollPositionThreshold) {
                loadMoreContent();
              }
            });
        });
    };

    $.fn.scrollingContent.defaults = {
        hasFeaturedPost: false,
    };
}(jQuery));
