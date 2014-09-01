jQuery(function($) {
	tinymce.create('tinymce.plugins.vhub_plugin', {
			init : function(ed, url) {
				 ed.addButton('vhub_plugin', {
						title : 'VideoNab',
						image : url+'/../css/images/menu-icon-small-new.png',
						onclick : function() {
							window.parent.send_to_editor( '[videonab]' );
						}
				 });
			},
			createControl : function(n, cm) {
				return null;
			},
			getInfo : function() {
				return {
						longname : "VideoNab",
						author : '---',
						authorurl : '---',
						infourl : '---',
						version : "---"
				};
			}
	});
	tinymce.PluginManager.add('vhub_plugin', tinymce.plugins.vhub_plugin);
});