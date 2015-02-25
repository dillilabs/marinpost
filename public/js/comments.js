$(function() {
  var commentsToggleSel = '.toggle-comments-for-date';
  var commentsSel = '#wrap-comments-for-date';
  var commentsMdyDate = 'data-mdy-date';
  var commentsYmdDate = 'data-ymd-date';

  var resetDisqusEmbed = function(mdy, ymd) {
    console.log('resetting disqus', mdy, ymd);

    DISQUS.reset({
      reload: true,
      config: function () {
        this.page.identifier = mdy;
        this.page.url = window.location.href + '/' + ymd;
        this.page.title = mdy;
      }
    });
  };

  $(commentsToggleSel).click(function(e) {
    var button = $(this);
    var comments = button.next(commentsSel);
    var mdy, ymd;

    if (comments.length > 0) {
      comments.toggle();
    } else {
      comments = $(commentsSel);

      if (comments.length > 0) {
        mdy = button.attr(commentsMdyDate);
        ymd = button.attr(commentsYmdDate);

        resetDisqusEmbed(mdy, ymd);

        button.after(comments.detach());
      }
    }
  });
});
