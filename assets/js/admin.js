(function ($) {
  'use strict';

  // PHP'den gelen sablonlar
  var CFG = window.kcdobo_admin || {};
  var linkTmpl   = CFG.link_row_tmpl   || '';
  var cryptoTmpl = CFG.crypto_row_tmpl || '';

  // Add Link
  $(document).on('click', '#kcdobo-add-link', function () {
    var $rep = $('#kcdobo-links-repeater');
    if (!$rep.length || !linkTmpl) return;
    var i = $rep.children('.kcdobo-link-row').length;
    var html = linkTmpl.split('__INDEX__').join(String(i));
    $rep.append($(html));
  });

  // Remove Link
  $(document).on('click', '#kcdobo-links-repeater .kcdobo-remove-link', function () {
    $(this).closest('.kcdobo-link-row').remove();
  });

  // Add Crypto
  $(document).on('click', '#kcdobo-add-crypto', function () {
    var $rep = $('#kcdobo-crypto-repeater');
    if (!$rep.length || !cryptoTmpl) return;
    var i = $rep.children('.kcdobo-crypto-row').length;
    var html = cryptoTmpl.split('__INDEX__').join(String(i));
    $rep.append($(html));
  });

  // Remove Crypto
  $(document).on('click', '#kcdobo-crypto-repeater .kcdobo-remove-crypto', function () {
    $(this).closest('.kcdobo-crypto-row').remove();
  });

  // Media picker
  $(document).on('click', '.kcdobo-media-btn', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var target = $('#' + $btn.data('target'));
    if (!target.length) return;

    var frame = wp.media({ title: 'Choose image', multiple: false, library: { type: 'image' } });
    frame.on('select', function () {
      var att = frame.state().get('selection').first().toJSON();
      target.val(att.url);
    });
    frame.open();
  });
})(jQuery);
