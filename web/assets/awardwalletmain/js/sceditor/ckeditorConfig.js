CKEDITOR.editorConfig = function (config) {
    config.contentsCss = ['/design/booking.css', '/assets/awardwalletmain/css/ckeditor.css?v=1'];
    config.bodyClass = 'ckeditorBody';
    config.extraPlugins = 'templates';
    config.enterMode = CKEDITOR.ENTER_BR;
    config.templates_replaceContent = true;
//    config.filebrowserBrowseUrl = '/browser/browse.php';
//    config.filebrowserImageBrowseUrl = '/lib/3dParty/ckeditor/filemanager/browser/default/browser.html?Connector=' + encodeURIComponent( '/lib/3dParty/ckeditor/filemanager/connectors/php/connector.php' );
//    config.filebrowserUploadUrl = '/lib/3dParty/ckeditor/filemanager/connectors/php/upload.php?Type=File';
//    config.filebrowserImageUploadUrl = '/lib/3dParty/ckeditor/filemanager/connectors/php/upload.php?Type=Image';
    config.allowedContent = true;
    config.extraAllowedContent = 'iframe[*];div(*){*}[*];p(*){*}[*];';
//    config.templates_files = [];
//    config.toolbar = [
//        ['Bold','Italic','Underline','-','TextColor','BGColor' ,'-','NumberedList','BulletedList','-','Cut','Copy','Paste','PasteText','Image','-','Undo','Redo']
//    ];
};
