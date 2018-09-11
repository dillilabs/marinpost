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
  config.fontSize_style = {
    element: 'span',
    styles: {'font-size': '14px'},
    overrides: [{element: 'font', attributes: {'size': null}}]
  };
  config.colorButton_colors = '000,800000,8B4513,2F4F4F,008080,000080,4B0082,696969,B22222,A52A2A,DAA520,006400,40E0D0,0000CD,800080,808080,F00,FF8C00,FFD700,008000,0FF,00F,EE82EE,A9A9A9,FFA07A,FFA500,FFFF00,00FF00,AFEEEE,ADD8E6,DDA0DD,D3D3D3,FFF0F5,FAEBD7,FFFFE0,F0FFF0,F0FFFF,F0F8FF,E6E6FA,FFF';
  config.toolbar = [
    {
      name: 'default',
      items: [
        'Bold',
        'Italic',
        'Underline',
        'HorizontalRule',
        'Link',
        'BulletedList',
        'NumberedList',
        'Outdent',
        'Indent',
        'JustifyLeft',
        'JustifyCenter',
        'JustifyRight',
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
  config.pasteFilter = 'plain-text';
};
