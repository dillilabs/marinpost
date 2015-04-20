(function($) {
    $.fn.scrollingContent  = function() {
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
              var offset;
        
              if (isLoadingContent || endOfContent) return;
        
              if (currentPosition > scrollPositionThreshold) {
                loadMoreContent();
              }
            });
        });
    };
}(jQuery));
