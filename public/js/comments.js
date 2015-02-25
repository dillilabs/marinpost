$(function() {
  var commentsToggleSel = '.toggle-comments-for-date';
  var commentsSel = '#wrap-comments-for-date';
  var commentsMdyDate = 'data-mdy-date';
  var commentsYmdDate = 'data-ymd-date';

  var resetDisqusEmbed = function(mdyDate, ymdDate) {
    DISQUS.reset({
      reload: true,
      config: function () {
        this.page.identifier = mdyDate;
        this.page.url = window.location.href + '/' + ymdDate;
        this.page.title = mdyDate;
      }
    });
  };

  $(commentsToggleSel).click(function(e) {
    var comments = $(this).next(commentsSel);
    var mdyDate, ymdDate;

    if (comments.length > 0) {
      comments.toggle();
    } else {
      comments = $(commentsSel);

      if (comments.length > 0) {
        mdyDate = $(this).attr(commentsMdyDate);
        ymdDate = $(this).attr(commentsYmdDate);

        resetDisqusEmbed(mdyDate, ymdDate);

        $(this).after(comments.detach());
      }
    }
  });
});
