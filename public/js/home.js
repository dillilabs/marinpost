(function($) {
    $.fn.scrollingContent  = function(options) {
        var config = $.extend({}, $.fn.scrollingContent.defaults, options);

        return this.each(function() {
            var content = $(this);
            var scrollPositionThreshold = 0.85;
            var isLoadingContent = false;
            var contentLengthThreshold = 20;
            var endOfContent = false;
            var featuredPosts = $('.featured-posts');

            //----------
            // Functions
            //----------

            var currentContentLength = function() {
              return $('.listing').length;
            };
          
            var loadMoreContent = function() {
              var offset = currentContentLength();

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

            //-------------------------
            // Featured post slide show
            //-------------------------

            if (config.hasFeaturedPost) {
              featuredPosts.slick({
                adaptiveHeight: true,
                autoplay: true,
                autoplaySpeed: 6000,
                dots: true,
                fade: true,
                infinite: true,
                speed: 1500,
              });

              featuredPosts.show();
            }
        });
    };

    $.fn.scrollingContent.defaults = {
        hasFeaturedPost: false,
    };
}(jQuery));
