<?php
/**
 * Plugin Name: KC Donate Box
 * Plugin URI:  https://github.com/kerimcandan/kc-donate-box
 * Description: Adds a customizable donate/support box under posts (repeatable links + multiple crypto wallets with QR). Shortcodes: [kc_donate_box], [kc_support_box]
 * Version:     1.6.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Kerim Candan
 * Author URI:  https://kerimcandan.com/
 * License:     GPLv2 or later
 * Text Domain: kc-donate-box
 * Domain Path: /languages
 */



if (!defined('ABSPATH')) { exit; }

class KC_Donate_Box {
    const OPT        = 'kc_donate_box_opts';
    const LEGACY_OPT = 'kc_support_box_opts';
    const VER        = '1.6.0';

    public static function init() {
        add_action('admin_init',             array(__CLASS__, 'register_settings'));
        add_action('admin_menu',             array(__CLASS__, 'admin_menu'));
        add_action('admin_enqueue_scripts',  array(__CLASS__, 'admin_assets'));

        add_filter('the_content',            array(__CLASS__, 'inject_box'));

        add_shortcode('kc_donate_box',       array(__CLASS__, 'shortcode'));
        add_shortcode('kc_support_box',      array(__CLASS__, 'shortcode')); // legacy alias

        add_action('wp_enqueue_scripts',     array(__CLASS__, 'enqueue_front_assets'));

        add_action('admin_post_kc_donate_box_reset', array(__CLASS__, 'handle_reset'));

        // Not: uninstall artık uninstall.php ile yapılıyor (bu dosyada hook yok).
    }

    /* ---------------- Defaults ---------------- */
    public static function defaults() {
        return array(
            'enabled'        => 1,
            'on_singular'    => 1,
            'title'          => 'Heads up',
            'message'        => 'If you enjoyed this post or found it helpful, you can support my work 😊',
            'links'          => array(
                array('label' => '☕ Buy me a coffee',    'url' => 'https://buymeacoffee.com/kerimcandan', 'enabled' => 1),
                array('label' => '💙 Support via PayPal', 'url' => 'https://www.paypal.me/kerimcandan',   'enabled' => 1),
            ),
            'cryptos'        => array(
                array(
                    'enabled'       => 1,
                    'type'          => 'bitcoin',  // bitcoin|ethereum|litecoin|custom
                    'address'       => 'bc1qt7wc6jfth4t2szc2hp6340sqp3y0pa9r3ywgrr',
                    'custom_scheme' => 'bitcoin',  // when type=custom
                    'qr_mode'       => 'upload',   // upload|auto|none
                    'qr_url'        => '',
                    'copy_button'   => 1,
                )
            ),
            '__import_json'  => '',
        );
    }

    private static function load_options() {
        $opt = get_option(self::OPT, null);
        if (is_null($opt)) {
            $legacy = get_option(self::LEGACY_OPT, null);
            if (is_array($legacy)) {
                update_option(self::OPT, $legacy, false);
                $opt = $legacy;
            } else {
                $opt = array();
            }
        }
        return wp_parse_args($opt, self::defaults());
    }

    /* ---------------- Admin Settings ---------------- */
    public static function register_settings() {
        register_setting(self::OPT, self::OPT, array(
            'type'              => 'array',
            'sanitize_callback' => array(__CLASS__, 'sanitize'),
            'default'           => self::defaults()
        ));

        add_settings_section('kc_section_main',  'General',   '__return_false', self::OPT);
        add_settings_field('enabled',     'Enable plugin',               array(__CLASS__, 'field_checkbox'), self::OPT, 'kc_section_main', array('key'=>'enabled'));
        add_settings_field('on_singular', 'Show only on single posts',   array(__CLASS__, 'field_checkbox'), self::OPT, 'kc_section_main', array('key'=>'on_singular'));
        add_settings_field('title',       'Box title',                   array(__CLASS__, 'field_text'),     self::OPT, 'kc_section_main', array('key'=>'title'));
        add_settings_field('message',     'Message',                     array(__CLASS__, 'field_textarea'), self::OPT, 'kc_section_main', array('key'=>'message','id'=>'kc_msg','help'=>'Tip: You can paste emojis manually if you like.'));

        add_settings_section('kc_section_links', 'Links', '__return_false', self::OPT);
        add_settings_field('links', 'Custom links', array(__CLASS__, 'field_links_repeater'), self::OPT, 'kc_section_links');

        add_settings_section('kc_section_crypto', 'Cryptos', '__return_false', self::OPT);
        add_settings_field('cryptos', 'Crypto wallets', array(__CLASS__, 'field_crypto_repeater'), self::OPT, 'kc_section_crypto');

        add_settings_section('kc_section_io', 'Export / Import', '__return_false', self::OPT);
        add_settings_field('export_json',   'Export settings (JSON)', array(__CLASS__, 'field_export'),   self::OPT, 'kc_section_io');
        add_settings_field('__import_json', 'Import settings (paste JSON and Save)', array(__CLASS__, 'field_textarea'), self::OPT, 'kc_section_io', array('key'=>'__import_json','rows'=>6,'placeholder'=>'Paste settings JSON here and click Save.'));
    }

    public static function admin_menu() {
        add_options_page('KC Donate Box', 'KC Donate Box', 'manage_options', self::OPT, array(__CLASS__, 'render_page'));
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) { return; }
        $reset_url = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1>KC Donate Box</h1>

            <?php if (!empty($_GET['kc_reset'])): ?>
                <div class="notice notice-success is-dismissible"><p>Settings were reset to defaults.</p></div>
            <?php endif; ?>

            <form method="post" action="options.php" style="margin-bottom:16px;">
                <?php
                settings_fields(self::OPT);
                do_settings_sections(self::OPT);
                submit_button('Save Changes');
                ?>
            </form>

            <form method="post" action="<?php echo esc_url($reset_url); ?>">
                <?php wp_nonce_field('kc_donate_box_reset'); ?>
                <input type="hidden" name="action" value="kc_donate_box_reset">
                <?php submit_button('Reset to defaults', 'delete', 'submit', false, array('onclick'=>"return confirm('Reset all KC Donate Box settings to factory defaults?');")); ?>
            </form>
        </div>
        <?php
    }

    /* ---------------- Field helpers ---------------- */
    private static function get($key) {
        $opts = self::load_options();
        return isset($opts[$key]) ? $opts[$key] : null;
    }

    public static function field_checkbox($args) {
        $key = $args['key']; $val = self::get($key);
        echo '<label><input type="checkbox" name="'.esc_attr(self::OPT)."[$key]".'" value="1" '.checked($val,1,false).' /> </label>';
        if (!empty($args['help'])) echo '<p class="description">'.esc_html($args['help']).'</p>';
    }
    public static function field_text($args) {
        $key = $args['key']; $val = self::get($key);
        $id  = !empty($args['id']) ? $args['id'] : 'kc_txt_'.esc_attr($key);
        echo '<input type="text" id="'.esc_attr($id).'" class="regular-text" name="'.esc_attr(self::OPT)."[$key]".'" value="'.esc_attr($val).'" />';
        if (!empty($args['help'])) echo '<p class="description">'.esc_html($args['help']).'</p>';
    }
    public static function field_textarea($args) {
        $key = $args['key']; $val = self::get($key);
        $rows = !empty($args['rows']) ? intval($args['rows']) : 3;
        $ph   = !empty($args['placeholder']) ? $args['placeholder'] : '';
        $id   = !empty($args['id']) ? $args['id'] : 'kc_ta_'.esc_attr($key);
        echo '<textarea id="'.esc_attr($id).'" class="large-text" rows="'.esc_attr($rows).'" name="'.esc_attr(self::OPT)."[$key]".'" placeholder="'.esc_attr($ph).'">'.esc_textarea($val).'</textarea>';
        if (!empty($args['help'])) echo '<p class="description">'.esc_html($args['help']).'</p>';
    }

    /* -------- Links repeater (admin) -------- */
    public static function field_links_repeater() {
        $links = self::get('links');
        if (!is_array($links)) { $links = array(); }
        echo '<div id="kc-links-repeater">';
        foreach ($links as $i => $row) {
            $label = isset($row['label']) ? $row['label'] : '';
            $url   = isset($row['url'])   ? $row['url']   : '';
            $en    = !empty($row['enabled']) ? 'checked' : '';
            echo '<div class="kc-link-row">';
            echo '<label style="display:inline-block;margin-right:8px;"><input type="checkbox" name="'.esc_attr(self::OPT).'[links]['.$i.'][enabled]" value="1" '.$en.'> Enabled</label>';
            echo '<input type="text" name="'.esc_attr(self::OPT).'[links]['.$i.'][label]" value="'.esc_attr($label).'" placeholder="Label (e.g., Buy me a coffee)" style="width:36%;margin-right:6px;">';
            echo '<input type="url"  name="'.esc_attr(self::OPT).'[links]['.$i.'][url]"   value="'.esc_attr($url).'"   placeholder="https://..." style="width:48%;margin-right:6px;">';
            echo '<button type="button" class="button kc-remove-link">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<p><button type="button" class="button button-secondary" id="kc-add-link">+ Add link</button></p>';
        ?>
        <script>
        (function($){
            var $rep = $('#kc-links-repeater');
            $('#kc-add-link').on('click', function(){
                var i = $rep.children('.kc-link-row').length;
                var html =
                  '<div class="kc-link-row">' +
                  '<label style="display:inline-block;margin-right:8px;">' +
                  '<input type="checkbox" name="<?php echo esc_js(self::OPT); ?>[links]['+i+'][enabled]" value="1" checked> Enabled</label>' +
                  '<input type="text" name="<?php echo esc_js(self::OPT); ?>[links]['+i+'][label]" value="" placeholder="Label" style="width:36%;margin-right:6px;">' +
                  '<input type="url"  name="<?php echo esc_js(self::OPT); ?>[links]['+i+'][url]"   value="" placeholder="https://..." style="width:48%;margin-right:6px;">' +
                  '<button type="button" class="button kc-remove-link">Remove</button>' +
                  '</div>';
                $rep.append($(html));
            });
            $rep.on('click','.kc-remove-link', function(){
                $(this).closest('.kc-link-row').remove();
            });
        })(jQuery);
        </script>
        <?php
    }

    /* -------- Crypto repeater (admin) -------- */
    public static function field_crypto_repeater() {
        $list = self::get('cryptos');
        if (!is_array($list)) { $list = array(); }

        echo '<div id="kc-crypto-repeater">';
        foreach ($list as $i => $row) {
            self::crypto_row_html($i, $row);
        }
        echo '</div>';
        echo '<p><button type="button" class="button button-secondary" id="kc-add-crypto">+ Add crypto</button></p>';

        $tmpl = array(
            'enabled'       => 1,
            'type'          => 'bitcoin',
            'address'       => '',
            'custom_scheme' => 'bitcoin',
            'qr_mode'       => 'upload',
            'qr_url'        => '',
            'copy_button'   => 1,
        );
        $template_index = '__INDEX__';
        ob_start(); self::crypto_row_html($template_index, $tmpl); $row_template = ob_get_clean();
        $row_template = str_replace(array("\n","\r"), '', $row_template);
        ?>
        <script>
        (function($){
            var $rep = $('#kc-crypto-repeater');
            var rowTmpl = <?php echo wp_json_encode($row_template); ?>;

            $('#kc-add-crypto').on('click', function(){
                var i = $rep.children('.kc-crypto-row').length;
                var html = rowTmpl.replace(/<?php echo preg_quote($template_index, '/'); ?>/g, String(i));
                $rep.append($(html));
            });

            $rep.on('click','.kc-remove-crypto', function(){
                $(this).closest('.kc-crypto-row').remove();
            });

            // Media picker (delegated)
            $(document).on('click','.kc-media-btn', function(e){
                e.preventDefault();
                var target = $('#'+$(this).data('target'));
                var frame = wp.media({ title:'Choose image', multiple:false, library:{type:'image'} });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    target.val(att.url);
                });
                frame.open();
            });
        })(jQuery);
        </script>
        <?php
    }

    private static function crypto_row_html($i, $row) {
        $enabled   = !empty($row['enabled']) ? 'checked' : '';
        $type      = isset($row['type']) ? $row['type'] : 'bitcoin';
        $address   = isset($row['address']) ? $row['address'] : '';
        $scheme    = isset($row['custom_scheme']) ? $row['custom_scheme'] : 'bitcoin';
        $qr_mode   = isset($row['qr_mode']) ? $row['qr_mode'] : 'upload';
        $qr_url    = isset($row['qr_url']) ? $row['qr_url'] : '';
        $copy_btn  = !empty($row['copy_button']) ? 'checked' : '';

        $namebase  = esc_attr(self::OPT).'[cryptos]['.$i.']';
        $media_id  = 'kc_media_qr_'.md5($namebase.'[qr_url]');
        echo '<div class="kc-crypto-row">';

        echo '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">';
        echo '<label><input type="checkbox" name="'.$namebase.'[enabled]" value="1" '.$enabled.'> Enabled</label>';

        echo '<label> Type ';
        echo '<select name="'.$namebase.'[type]">';
        $types = array('bitcoin'=>'Bitcoin','ethereum'=>'Ethereum','litecoin'=>'Litecoin','custom'=>'Custom');
        foreach ($types as $k=>$lbl) {
            echo '<option value="'.esc_attr($k).'" '.selected($type,$k,false).'>'.esc_html($lbl).'</option>';
        }
        echo '</select></label>';

        echo '<label> Address ';
        echo '<input type="text" name="'.$namebase.'[address]" value="'.esc_attr($address).'" placeholder="Wallet address" style="min-width:260px;"></label>';

        echo '<label> Custom scheme ';
        echo '<input type="text" name="'.$namebase.'[custom_scheme]" value="'.esc_attr($scheme).'" placeholder="mycoin (only when type = Custom)"></label>';

        echo '<label> QR mode ';
        echo '<select name="'.$namebase.'[qr_mode]">';
        $modes = array('upload'=>'Uploaded image','auto'=>'Auto (qrserver.com)','none'=>'Do not show');
        foreach ($modes as $k=>$lbl) {
            echo '<option value="'.esc_attr($k).'" '.selected($qr_mode,$k,false).'>'.esc_html($lbl).'</option>';
        }
        echo '</select></label>';

        echo '<label> QR image URL ';
        echo '<input type="text" class="regular-text" id="'.$media_id.'" name="'.$namebase.'[qr_url]" value="'.esc_attr($qr_url).'" placeholder="https://...">';
        echo ' <button type="button" class="button kc-media-btn" data-target="'.$media_id.'">Choose image</button>';
        echo '</label>';

        echo '<label><input type="checkbox" name="'.$namebase.'[copy_button]" value="1" '.$copy_btn.'> Show copy button</label>';

        echo '<button type="button" class="button kc-remove-crypto" style="margin-left:auto;">Remove</button>';
        echo '</div>';

        echo '</div>';
    }

    /* -------- Export JSON (admin) -------- */
    public static function field_export() {
        $opts = self::load_options();
        unset($opts['__import_json']);
        echo '<textarea class="large-text code" rows="6" readonly onclick="this.select()">'.esc_textarea(wp_json_encode($opts, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)).'</textarea>';
        echo '<p class="description">Copy the JSON above to back up your settings.</p>';
    }

    /* -------- Admin assets (enqueue file-based) -------- */
    public static function admin_assets($hook) {
        if ($hook !== 'settings_page_'.self::OPT) return;
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('kc-donate-admin', plugins_url('assets/css/admin.css', __FILE__), array(), self::VER);
        wp_enqueue_script('kc-donate-admin', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), self::VER, true);
    }

    /* ---------------- Sanitize ---------------- */
    public static function sanitize($input) {
        $d = self::defaults();

        // Import first
        if (!empty($input['__import_json'])) {
            $json = json_decode(stripslashes($input['__import_json']), true);
            if (is_array($json)) {
                $merged = wp_parse_args($json, $d);
                $merged['__import_json'] = '';
                return $merged;
            }
        }

        $out = array();
        $out['enabled']     = !empty($input['enabled']) ? 1 : 0;
        $out['on_singular'] = !empty($input['on_singular']) ? 1 : 0;
        $out['title']       = isset($input['title'])   ? sanitize_text_field($input['title'])   : $d['title'];
        $out['message']     = isset($input['message']) ? wp_kses_post($input['message'])        : $d['message'];

        // Links
        $out['links'] = array();
        if (!empty($input['links']) && is_array($input['links'])) {
            foreach ($input['links'] as $row) {
                $lbl = isset($row['label'])   ? wp_kses_post($row['label']) : '';
                $url = isset($row['url'])     ? esc_url_raw($row['url'])    : '';
                $en  = !empty($row['enabled']) ? 1 : 0;
                if ($lbl === '' && $url === '') continue;
                $out['links'][] = array('label'=>$lbl,'url'=>$url,'enabled'=>$en);
            }
        }
        if (empty($out['links'])) { $out['links'] = $d['links']; }

        // Cryptos
        $out['cryptos'] = array();
        if (!empty($input['cryptos']) && is_array($input['cryptos'])) {
            foreach ($input['cryptos'] as $row) {
                $en   = !empty($row['enabled']) ? 1 : 0;
                $type = isset($row['type']) ? sanitize_text_field($row['type']) : 'bitcoin';
                if (!in_array($type, array('bitcoin','ethereum','litecoin','custom'), true)) $type = 'bitcoin';

                $addr   = isset($row['address'])        ? sanitize_text_field($row['address'])        : '';
                $scheme = isset($row['custom_scheme'])  ? sanitize_text_field($row['custom_scheme'])  : 'bitcoin';

                $qrmode = isset($row['qr_mode']) ? sanitize_text_field($row['qr_mode']) : 'upload';
                if (!in_array($qrmode, array('upload','auto','none'), true)) $qrmode = 'upload';
                $qrurl  = isset($row['qr_url'])  ? esc_url_raw($row['qr_url']) : '';

                $copy   = !empty($row['copy_button']) ? 1 : 0;

                if (!$en && $addr === '' && $qrurl === '') continue;

                $out['cryptos'][] = array(
                    'enabled'       => $en,
                    'type'          => $type,
                    'address'       => $addr,
                    'custom_scheme' => $scheme,
                    'qr_mode'       => $qrmode,
                    'qr_url'        => $qrurl,
                    'copy_button'   => $copy,
                );
            }
        }
        if (empty($out['cryptos'])) { $out['cryptos'] = $d['cryptos']; }

        $out['__import_json'] = '';

        return $out;
    }

    /* ---------------- Frontend ---------------- */
    public static function inject_box($content) {
        $o = self::load_options();
        if (!$o['enabled']) return $content;
        if ($o['on_singular'] && !is_singular('post')) return $content;

        return $content . self::render_box($o);
    }

    public static function shortcode($atts=array()) {
        $o = self::load_options();
        if (!$o['enabled']) return '';
        return self::render_box($o);
    }

    private static function coin_display_name($c) {
        $map = array('bitcoin'=>'Bitcoin', 'ethereum'=>'Ethereum', 'litecoin'=>'Litecoin');
        if (isset($c['type']) && $c['type'] === 'custom') {
            $s = isset($c['custom_scheme']) ? trim((string)$c['custom_scheme']) : '';
            return $s !== '' ? ucwords($s) : 'Custom';
        }
        return isset($map[$c['type']]) ? $map[$c['type']] : 'Crypto';
    }

    private static function build_crypto_uri($c) {
        $type   = isset($c['type']) ? $c['type'] : '';
        $scheme = ($type === 'custom' && !empty($c['custom_scheme'])) ? $c['custom_scheme'] : $type;
        $addr   = isset($c['address']) ? trim((string)$c['address']) : '';
        if ($addr === '' || $scheme === '') return '';
        return sprintf('%s:%s', $scheme, $addr);
    }

    private static function render_box($o) {
        $html  = '<div class="kc-donate-box kc-support-box">';
        $html .= '<p><strong>'.esc_html($o['title']).':</strong> '.wp_kses_post($o['message']).'</p>';

        // Links
        if (!empty($o['links']) && is_array($o['links'])) {
            foreach ($o['links'] as $row) {
                if (empty($row['enabled']) || empty($row['url'])) continue;
                $html .= '<p><a href="'.esc_url($row['url']).'" target="_blank" rel="noopener nofollow">'.wp_kses_post($row['label']).'</a></p>';
            }
        }

        // Cryptos
        if (!empty($o['cryptos']) && is_array($o['cryptos'])) {
            foreach ($o['cryptos'] as $c) {
                if (empty($c['enabled']) || empty($c['address'])) continue;

                $uri   = self::build_crypto_uri($c);
                $coin  = self::coin_display_name($c);
                $label = 'Donate with '.$coin;

                $html .= '<div class="kc-crypto-item" style="margin-top:8px;">';
                $html .= '<p><strong>₿/Ξ:</strong> ';
                if ($uri) {
                    $html .= '<a href="'.esc_url($uri).'" rel="nofollow noopener">'.esc_html($label).'</a><br>';
                } else {
                    $html .= esc_html($label).'<br>';
                }
                $html .= '<small>Address: <code class="kc-addr">'.esc_html($c['address']).'</code>';
                if (!empty($c['copy_button'])) {
                    $html .= ' <button type="button" class="kc-copy" data-copy="'.esc_attr($c['address']).'" style="margin-left:8px;padding:2px 8px;font-size:0.85em;">Copy</button>';
                }
                $html .= '</small></p>';

                // QR
                if (!empty($c['qr_mode']) && $c['qr_mode'] !== 'none') {
                    $qr = '';
                    if ($c['qr_mode']==='upload' && !empty($c['qr_url'])) {
                        $qr = esc_url($c['qr_url']);
                    } elseif ($c['qr_mode']==='auto' && $uri) {
                        $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data='.rawurlencode($uri);
                    }
                    if ($qr) {
                        $html .= '<details style="margin-top:6px;"><summary>Show QR code</summary>';
                        $html .= '<img src="'.$qr.'" alt="Crypto QR code" width="160" height="160" loading="lazy"></details>';
                    }
                }
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /* ---------------- Front assets (CSS+JS) ---------------- */
    public static function enqueue_front_assets() {
        $o = self::load_options();
        if (!$o['enabled']) return;

        wp_enqueue_style('kc-donate-front', plugins_url('assets/css/front.css', __FILE__), array(), self::VER);
        wp_enqueue_script('kc-donate-front', plugins_url('assets/js/front.js', __FILE__), array(), self::VER, true);
    }

    /* ---------------- Reset ---------------- */
    public static function handle_reset() {
        if (!current_user_can('manage_options')) { wp_die('Insufficient permissions'); }
        check_admin_referer('kc_donate_box_reset');
        update_option(self::OPT, self::defaults(), false);
        delete_option(self::LEGACY_OPT);
        wp_redirect(add_query_arg(array('page'=>self::OPT,'kc_reset'=>1), admin_url('options-general.php')));
        exit;
    }
}
// i18n: load translations from /languages
add_action('plugins_loaded', function(){
    load_plugin_textdomain('kc-donate-box', false, dirname(plugin_basename(__FILE__)).'/languages');
});
KC_Donate_Box::init();
