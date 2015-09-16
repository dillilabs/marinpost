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
            var letters = $('#comments ul');
            var endOfLetters = false;

            //----------
            // Functions
            //----------

            var currentContentLength = function() {
              return $('.listing').length;
            };

            var loadMoreContent = function() {
              isLoadingContent = true;

              $.get(
                '/more/unfiltered',
                {
                  offset: currentContentLength(),
                  limit: config.contentLimit,
                },
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

            var lettersLength = function() {
              return $('#comments li').length;
            };

            var loadMoreLetters = function() {
              isLoadingContent = true;

              $.get(
                '/more/letters',
                {
                  offset: lettersLength(),
                  limit: config.letterLimit,
                  home: true,
                },
                function(data) {
                  if (data.length > contentLengthThreshold) {
                    letters.append(data);
                  } else {
                    endOfLetters = true;
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

              if (isLoadingContent) return;

              if (currentPosition > scrollPositionThreshold) {
                if (!endOfContent) loadMoreContent();
                if (!endOfLetters) loadMoreLetters();
              }
            });

            //-------------------------
            // Featured post slide show
            //-------------------------

            if (config.slideShow) {
              featuredPosts.slick({
                adaptiveHeight: true,
                autoplay: true,
                autoplaySpeed: 4000,
                dots: true,
                fade: true,
                infinite: true,
                speed: 500,
              });

              featuredPosts.show();
            }
        });
    };

    $.fn.scrollingContent.defaults = {
        slideShow: false,
        contentLimit: 10,
        letterLimit: 10,
    };
}(jQuery));
