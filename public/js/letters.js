(function($) {
  $.fn.letters  = function(options) {
    var config = $.extend({}, $.fn.letters.defaults, options);

    return this.each(function() {
      var selectDate = $('div#select-letter-date');
      var resetLink = $('a#reset-letter-filter');
      var baseUrl = '/letters/';

      var content = $('.posts');
      var scrollPositionThreshold = 0.85;
      var isLoadingContent = false;
      var contentLengthThreshold = 20;
      var endOfContent = false;

      //-------
      // Date picker
      //-------

      selectDate.datepicker({
        dateFormat: 'yy/mm/dd',
        defaultDate: config.date,
        onSelect: function() {
          document.location = baseUrl + this.value;
        }
      });

      //----------
      // Functions
      //----------

      var loadMoreContent = function() {
        isLoadingContent = true;

        $.get(
          '/more/letters',
          {
            offset: currentContentLength(),
            limit: config.limit,
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

      var currentContentLength = function() {
        return content.find('li').length;
      };

      //-------
      // Events
      //-------

      resetLink.click(function() {
        document.location = baseUrl;
      });

      $(window).scroll(function() {
        var currentPosition = $(window).scrollTop() / ($(document).height() - $(window).height());

        if (isLoadingContent || endOfContent) return;

        if (currentPosition > scrollPositionThreshold) {
          loadMoreContent();
        }
      });
    });
  };

  $.fn.letters.defaults = {
    date: null,
    limit: 10,
  };
}(jQuery));
