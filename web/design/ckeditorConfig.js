CKEDITOR.editorConfig = function( config )
{
    config.contentsCss = ['/design/mainStyle.css', '/design/booking.css'];
	config.bodyClass = 'ckeditorBody';
	config.enterMode = CKEDITOR.ENTER_BR;
	config.coreStyles_bold = { element : 'span', attributes : {'class': 'bold'} };
    config.filebrowserBrowseUrl = '/browser/browse.php';
    config.filebrowserImageBrowseUrl = '/lib/3dParty/ckeditor/filemanager/browser/default/browser.html?Connector=' + encodeURIComponent( '/lib/3dParty/ckeditor/filemanager/connectors/php/connector.php' );
    config.filebrowserUploadUrl = '/lib/3dParty/ckeditor/filemanager/connectors/php/upload.php?Type=File';
    config.filebrowserImageUploadUrl = '/lib/3dParty/ckeditor/filemanager/connectors/php/upload.php?Type=Image';
    config.allowedContent = true;
    config.templates_files = [];
    config.toolbar = [
        ['Bold','Italic','Underline','-','TextColor','BGColor','-','NumberedList','BulletedList','-','Cut','Copy','Paste','PasteText','-','Undo','Redo']
    ];
};
