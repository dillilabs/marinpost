CKEDITOR.plugins.addExternal('wordcount', '/js/ckeditor/wordcount/', 'plugin.js');
CKEDITOR.plugins.addExternal('notification', '/js/ckeditor/notification/', 'plugin.js');

CKEDITOR.editorConfig = function(config) {
  config.extraPlugins = 'wordcount,notification';
  config.disableNativeSpellChecker = false;
  config.linkShowAdvancedTab = false;
  config.linkShowTargetTab = false;
  config.font_style = {
    element: 'span',
    styles: {'font-family': 'Times,Georgia,serif'},
    overrides: [{element: 'font', attributes: {'face': null}}]
  };
  config.toolbar = [
    {
      name: 'whatever',
      items: [
        'Bold',
        'Italic',
        'Underline',
        'HorizontalRule',
        'Link',
        'Image',
        'BulletedList',
        'NumberedList',
        'Outdent',
        'Indent',
        'JustifyLeft',
        'Font',
        'FontSize',
        'TextColor',
        'BGColor',
        'Undo',
        'Redo',
        'Maximize'
      ],
    }
  ];
};
