(function($) {
    $.fn.filters  = function() {
        var section = document.location.pathname.split('/')[1];

        return this.each(function() {
            var filteredContent = $(this);
            var toggleFilters = $('#filters fieldset h5');
            var noFilters = $(':checkbox.all');
            var filters = $(':checkbox.filter');
            var contentLengthThreshold = 20;
            var scrollPositionThreshold = 0.85;
            var isLoadingContent = false;
            var endOfContent = false;

            //----------
            // Functions
            //----------
          
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

              return '/filter?section='+section+'&locations='+locations+'&topics='+topics+'&authors='+authors;
            };

            var refreshViews = function() {
              var url = urlFor(section);

              filteredContent.load(url);
            };
          
            var currentContentLength = function() {
              return $('.listing').length;
            };
          
            var loadMoreContent = function() {
              var url = urlFor(section);
              var offset = currentContentLength();

              isLoadingContent = true;

              $.get(
                url+'&offset='+offset,
                function(data) {
                  if (data.length > contentLengthThreshold) {
                    filteredContent.append(data);
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
          
            toggleFilters.click(function() {
              var toggle = $(this);

              toggle.toggleClass('active').siblings('ul').slideToggle();
            });

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
