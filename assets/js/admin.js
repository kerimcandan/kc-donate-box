(function($){
	'use strict';

	// Config injected via wp_add_inline_script in admin_assets()
	var CFG = (window.kcdonatebox_admin || {});
	var linkTmpl   = CFG.link_row_tmpl   || '';
	var cryptoTmpl = CFG.crypto_row_tmpl || '';

	// Add Link
	$(document).on('click', '#kc-add-link', function(){
		var $rep = $('#kc-links-repeater');
		if (!$rep.length || !linkTmpl) return;
		var i = $rep.children('.kc-link-row').length;
		var html = linkTmpl.split('__INDEX__').join(String(i));
		$rep.append($(html));
	});

	// Remove Link
	$(document).on('click', '#kc-links-repeater .kc-remove-link', function(){
		$(this).closest('.kc-link-row').remove();
	});

	// Add Crypto
	$(document).on('click', '#kc-add-crypto', function(){
		var $rep = $('#kc-crypto-repeater');
		if (!$rep.length || !cryptoTmpl) return;
		var i = $rep.children('.kc-crypto-row').length;
		var html = cryptoTmpl.split('__INDEX__').join(String(i));
		$rep.append($(html));
	});

	// Remove Crypto
	$(document).on('click', '#kc-crypto-repeater .kc-remove-crypto', function(){
		$(this).closest('.kc-crypto-row').remove();
	});

	// Media picker (delegated)
	$(document).on('click', '.kc-media-btn', function(e){
		e.preventDefault();
		var $btn = $(this);
		var target = $('#'+$btn.data('target'));
		if (!target.length) return;

		var frame = wp.media({ title:'Choose image', multiple:false, library:{type:'image'} });
		frame.on('select', function(){
			var att = frame.state().get('selection').first().toJSON();
			target.val(att.url);
		});
		frame.open();
	});
})(jQuery);
