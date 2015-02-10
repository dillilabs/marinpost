(function($) {
    $.fn.filters  = function() {
        return this.each(function() {
            var filteredContent = $(this);
            var noFilters = $(':checkbox.all');
            var filters = $(':checkbox.filter');
            var section = document.location.pathname.split('/')[1];
            var loadMoreLink = $('#load-more-content'); // TODO activate on scroll
          
            var activeFilters = function(type) {
              return filters.filter('.'+type+':checked').map(function() { return this.value; }).get().join();
            };
          
            var refreshViews = function() {
              filteredContent.load('/filter/'+section+'?locations='+activeFilters('location')+'&topics='+activeFilters('topic'));
            };
          
            var currentContentLength = function() {
              return $('.listing').length;
            };
          
            var loadMoreContent = function() {
              $.get(
                '/more/'+section+'?locations='+activeFilters('location')+'&topics='+activeFilters('topic')+'&offset='+currentContentLength(),
                function(data) {
                  filteredContent.append(data);
                }
              );
            };
          
            filters.click(function() {
              var filter = $(this);
              var type = filter.is('.location') ? '.location' : '.topic';
              var typeFilters;
          
              if (filter.is(':checked')) {
                noFilters.filter(type).prop('checked', false);
              } else {
                typeFilters = filters.filter(type);
                noFilters.filter(type).prop('checked', !typeFilters.is(':checked'));
              }
          
              refreshViews();
            });
          
            noFilters.click(function() {
              var noFilter = $(this);
              var type = noFilter.is('.location') ? '.location' : '.topic';
          
              if (noFilter.is(':checked')) {
                filters.filter(type).prop('checked', false);
              }
          
              refreshViews();
            });
          
            // TODO activate on scroll
            loadMoreLink.click(function(e) {
              e.preventDefault();
              loadMoreContent();
            });
        });
    };
}(jQuery));
