$(function() {
  var contributorRadioButtons = $('ul.contributors');

  if (document.location.pathname.match(/^\/about\/contributors/)) {
    contributorRadioButtons.show();
  }

  if (m = document.location.pathname.match(/(^\/about\/contributors\/\d+\/\S+)/)) {
    contributorRadioButtons.find('input[value$="'+m[1]+'"]').attr('checked', 'checked');

    /**
     * offset[section] denotes number of already fetched entries for each section
     * where section is blog, news, notices, media or letters
     */
    var offset = {
      'blog': 0,
      'news': 0,
      'notices': 0,
      'media': 0,
      'letters': 0
    };

    /**
     * limit denotes how many entries to retrieve
     */
    const limit = 10;

    /**
     * list[section] denotes the dom element to which entries are appended to. 
     * where section is blog, news, notices, media or letters
     */
    var $list = {
      'blog': null,
      'news': null,
      'notices': null,
      'media': null,
      'letters': null
    }

    var pathArray = window.location.pathname.split('\/');
    var authorId = pathArray[3];

    /**
     * Contributors Page
     * 
     * Click handler for section arrows.
     */
    $('h3.my-content a').click(function (e) {
      var $link = $(this);
      var $parent = $link.parent();
      var section = $parent[0].dataset.section;
      $list[section] = $parent.next('ul.posts');

      if ($parent.hasClass('active')) {
        $list[section].hide();
      } else {
        if ($list[section].children().length == 0) {
          $link.append('<img id="spinner" src="/img/spinner.gif" style="padding-left: 8px">');

          $.get(
            '/about/contributor_entries',
            { authorId: authorId, section: section, offset: offset[section], limit: limit },
            function (result) {
              $list[section].html(result);
              $link.find('img').remove();
              if ($(result).filter('.listing').length == limit) {
                $list[section].append('<div style="text-align: center;"><input type="button" id="btn-load-more" value="Load More..." style="margin-bottom: .5em;" data-section="' + section + '"/></div>');
              }
              offset[section] = offset[section] + limit;
            }
          );
        }

        $list[section].show();
      }

      e.preventDefault();
    });

    /**
     * Contributors Page
     * 
     * Hide sections if they do not contain any entries.
     */
    $.each($('h3.my-content'), function (index, sectionHeadingElem) {      
      var section = sectionHeadingElem.dataset.section;
      if (section) {
        $.get(
          '/about/contributor_entries',
          { authorId: authorId, section: section, offset: offset[section], limit: limit },
          function (result) {
            if (result) {
              $(sectionHeadingElem).show();
            } else {
              $(sectionHeadingElem).hide();
            }
          }
        );
      }
    });

    /**
     * Contributors page
     * 
     * Click handler for 'Load More...' button. Fetches the posts depending on the 'limit' 
     * and appends them to the specified section (blog, news, notices, media, letters).
     *
     * #btn-load-more is not created in DOM. Therefore, we attach the listener using .on().
     */
    $(document).on('click', '#btn-load-more', function (e) {      
      $loadMoreBtn = $(this);
      var section = $loadMoreBtn.data('section');
      $list[section].append('<img id="spinner" src="/img/spinner.gif" style="padding-left: 8px">');

      $.get(
        '/about/contributor_entries',
        { authorId: authorId, section: section, offset: offset[section], limit: limit },
        function (result) {
          $list[section].append($('<div>').html(result));
          $list[section].find('#spinner').remove();
          $loadMoreBtn.parent().remove();
          if (result) {
            if ($(result).filter('.listing').length == limit)
              $list[section].append($loadMoreBtn.parent());
            offset[section] = offset[section] + limit;
          }
        }
      );

      e.preventDefault();
    });
  }

  contributorRadioButtons.find('input').click(function(e) {
    e.preventDefault();
    document.location = this.value;
  });
});
