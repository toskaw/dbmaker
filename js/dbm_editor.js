(function(){
	tinymce.create('tinymce.plugins.dbm_buttons', {
		init: function(ed, url) {
			ed.addButton('add_cf', {
				title: 'dbm_shortcode',
				icon: 'code',
				cmd: 'shortcode_cmd'
			});
			ed.addCommand( 'shortcode_cmd', function() {
				ed.windowManager.open(
					{
						url: url + '/../view/form.php',
						width: 480,
						height: 270,
						title: 'テキストボックス名とラベル名をご入力ください'
					},
					{
						custom_param: 1 
					}
				);
/*
				var selected_text = ed.selection.getContent(),
				return_text = '[example]' + selected_text + '[/example]';
				ed.execCommand( 'mceInsertContent', 0, return_text );
*/
			});
		},
		createControl : function(n, cm) {
			return null;
		},

	});
	tinymce.PluginManager.add('DBM_shortcode_plugin', tinymce.plugins.dbm_buttons);
})();
