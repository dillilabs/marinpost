(function($) {
    $.fn.filters  = function() {
        return this.each(function() {
            var filteredContent = $(this);
            var noFilters = $(':checkbox.all');
            var filters = $(':checkbox.filter');
            var section = document.location.pathname.split('/')[1];
            var loadMoreLink = $('#load-more-content'); // TODO activate on scroll
          
            // TODO refactor
            var filterType = function(e) {
              if (e.is('.location')) {
                return '.location';
              } else if (e.is('.topic')) {
                return '.topic';
              } else if (e.is('.author')) {
                return '.author';
              }
            };

            var activeFilters = function(type) {
              return filters.filter('.'+type+':checked').map(function() { return this.value; }).get().join();
            };
          
            var urlFor = function(section) {
              var locations = activeFilters('location');
              var topics = activeFilters('topic');
              var authors = activeFilters('author');

              return '/filter/'+section+'?locations='+locations+'&topics='+topics+'&authors='+authors;
            };

            var refreshViews = function() {
              var url = urlFor(section);

              filteredContent.load(url);
            };
          
            var currentContentLength = function() {
              return $('.listing').length;
            };
          
            // TODO ascertain end of content
            // and prevent further requests
            // taking into account the current state of the filters
            var loadMoreContent = function() {
              var url = urlFor(section);
              var offset = currentContentLength();

              $.get(
                url+'&offset='+offset,
                function(data) {
                  filteredContent.append(data);
                }
              );
            };
          
            filters.click(function() {
              var filter = $(this);
              var type = filterType(filter);
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
              var type = filterType(noFilter);
          
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
