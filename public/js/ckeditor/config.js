CKEDITOR.plugins.addExternal('wordcount', '/js/ckeditor/wordcount/', 'plugin.js');
CKEDITOR.plugins.addExternal('notification', '/js/ckeditor/notification/', 'plugin.js');

CKEDITOR.editorConfig = function(config) {
  config.extraPlugins = 'wordcount,notification';
};
