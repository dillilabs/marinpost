$(function() {
  var simple = $('form.simple');
  var advanced = $('form.advanced');
  var results = $('ul.posts.search');

  simple.submit(function() {
    $(this).addClass('processing');
  });

  advanced.submit(function() {
    $(this).addClass('processing').find('input[type=submit]').val('Processing').attr('disabled', 'disabled');
  });

  results.on('click', 'a.detail', function(e) {
    e.preventDefault();
    document.location = this.href + document.location.search;
  });

  $.fn.scrollingSearchContent  = function(options) {
    var config = $.extend({}, $.fn.scrollingSearchContent.defaults, options);

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
          return $('ul.posts.search li').length;
        };

        var loadMoreContent = function() {
          isLoadingContent = true;

          $.get(
            '/search_custom/entries',
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

        //-------
        // Events
        //-------

        $(window).scroll(function() {
          var currentPosition = $(window).scrollTop() / ($(document).height() - $(window).height());

          if (isLoadingContent) return;

          if (currentPosition > scrollPositionThreshold) {
            if (!endOfContent) loadMoreContent();
          }
        });
        
    });
  };

  $.fn.scrollingSearchContent.defaults = {
      contentLimit: 10
  };
});