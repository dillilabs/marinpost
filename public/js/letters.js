(function($) {
  $.fn.letters  = function(options) {
    var config = $.extend({}, $.fn.letters.defaults, options);

    return this.each(function() {
      var selectDate = $('div#select-letter-date');
      var resetLink = $('a#reset-letter-filter');
      var baseUrl = '/letters/';

      selectDate.datepicker({
        dateFormat: 'yy/mm/dd',
        defaultDate: config.date,
        onSelect: function() {
          document.location = baseUrl + this.value;
        }
      });

      resetLink.click(function() {
        document.location = baseUrl;
      });
    });
  };

  $.fn.letters.defaults = {
    date: null,
  };
}(jQuery));
