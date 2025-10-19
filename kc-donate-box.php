<?php
/**
 * Plugin Name: KC Donate Box
 * Plugin URI:  https://github.com/kerimcandan/kc-donate-box
 * Description: Adds a customizable donate/support box under posts (repeatable links + multiple crypto wallets with QR). Shortcodes: [kcdobo_donate_box] (new), [kc_donate_box] (legacy).
 * Version:     1.6.3-rc1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Kerim Candan
 * Author URI:  https://kerimcandan.com/
 * License:     GPLv2 or later
 * Text Domain: kc-donate-box
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCDOBO_Plugin {

	/**
	 * Options & version
	 *
	 * OPT         = new canonical option name (all new installs write here)
	 * LEGACY_OPT1 = previous option name (auto-migrated on first save/load)
	 * LEGACY_OPT2 = older legacy option name (auto-migrated if found)
	 */
	const OPT         = 'kcdobo_options';
	const LEGACY_OPT1 = 'kc_donate_box_opts';
	const LEGACY_OPT2 = 'kc_support_box_opts';
	const VER         = '1.6.3';

	/** Bootstrap hooks */
	public static function init() {
		add_action( 'admin_init',            array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu',            array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );

		add_filter( 'the_content',           array( __CLASS__, 'inject_box' ) );

		// New long-prefix shortcodes (recommended)
		add_shortcode( 'kcdobo_donate_box',  array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'kcdobo_support_box', array( __CLASS__, 'shortcode' ) );

		// Legacy shortcodes for backward-compatibility
		add_shortcode( 'kc_donate_box',      array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'kc_support_box',     array( __CLASS__, 'shortcode' ) );

		add_action( 'wp_enqueue_scripts',    array( __CLASS__, 'enqueue_front_assets' ) );

		// Reset action: new + legacy alias (accept both)
		add_action( 'admin_post_kcdobo_reset',        array( __CLASS__, 'handle_reset' ) );
		add_action( 'admin_post_kc_donate_box_reset', array( __CLASS__, 'handle_reset' ) );
	}

	/* ---------------- Defaults ---------------- */
	public static function defaults() {
		return array(
			'enabled'     => 1,
			'on_singular' => 1,
			'title'       => 'Heads up',
			'message'     => 'If you enjoyed this post or found it helpful, you can support my work 😊',
			'links'       => array(
				array( 'label' => '☕ Buy me a coffee',    'url' => 'https://buymeacoffee.com/kerimcandan', 'enabled' => 1 ),
				array( 'label' => '💙 Support via PayPal', 'url' => 'https://www.paypal.me/kerimcandan',   'enabled' => 1 ),
			),
			'cryptos'     => array(
				array(
					'enabled'       => 1,
					'type'          => 'bitcoin',  // bitcoin|ethereum|litecoin|custom
					'address'       => 'bc1qt7wc6jfth4t2szc2hp6340sqp3y0pa9r3ywgrr',
					'custom_scheme' => 'bitcoin',  // used only when type=custom
					'qr_mode'       => 'upload',   // upload|auto|none
					'qr_url'        => '',
					'copy_button'   => 1,
				),
			),
			'__import_json' => '',
		);
	}

	/**
	 * Load options with migration from legacy keys when needed.
	 */
	private static function load_options() {
		// 1) Try the new canonical option first.
		$opt = get_option( self::OPT, null );

		// 2) If missing, try migrating from the newer legacy key.
		if ( is_null( $opt ) ) {
			$legacy_newer = get_option( self::LEGACY_OPT1, null ); // 'kc_donate_box_opts'
			if ( is_array( $legacy_newer ) ) {
				update_option( self::OPT, $legacy_newer, false );
				$opt = $legacy_newer;
			}
		}

		// 3) If still missing, try migrating from the older legacy key.
		if ( is_null( $opt ) ) {
			$legacy_old = get_option( self::LEGACY_OPT2, null ); // 'kc_support_box_opts'
			if ( is_array( $legacy_old ) ) {
				update_option( self::OPT, $legacy_old, false );
				$opt = $legacy_old;
			}
		}

		if ( is_null( $opt ) ) {
			$opt = array();
		}

		return wp_parse_args( $opt, self::defaults() );
	}

	/* ---------------- Admin Settings ---------------- */
	public static function register_settings() {
		register_setting(
			self::OPT,
			self::OPT,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section( 'kc_section_main', 'General', '__return_false', self::OPT );
		add_settings_field( 'enabled', 'Enable plugin', array( __CLASS__, 'field_checkbox' ), self::OPT, 'kc_section_main', array( 'key' => 'enabled' ) );
		add_settings_field( 'on_singular', 'Show only on single posts', array( __CLASS__, 'field_checkbox' ), self::OPT, 'kc_section_main', array( 'key' => 'on_singular' ) );
		add_settings_field( 'title', 'Box title', array( __CLASS__, 'field_text' ), self::OPT, 'kc_section_main', array( 'key' => 'title' ) );
		add_settings_field( 'message', 'Message', array( __CLASS__, 'field_textarea' ), self::OPT, 'kc_section_main', array( 'key' => 'message', 'id' => 'kc_msg', 'help' => 'Tip: You can paste emojis manually if you like.' ) );

		add_settings_section( 'kc_section_links', 'Links', '__return_false', self::OPT );
		add_settings_field( 'links', 'Custom links', array( __CLASS__, 'field_links_repeater' ), self::OPT, 'kc_section_links' );

		add_settings_section( 'kc_section_crypto', 'Cryptos', '__return_false', self::OPT );
		add_settings_field( 'cryptos', 'Crypto wallets', array( __CLASS__, 'field_crypto_repeater' ), self::OPT, 'kc_section_crypto' );

		add_settings_section( 'kc_section_io', 'Export / Import', '__return_false', self::OPT );
		add_settings_field( 'export_json', 'Export settings (JSON)', array( __CLASS__, 'field_export' ), self::OPT, 'kc_section_io' );
		add_settings_field( '__import_json', 'Import settings (paste JSON and Save)', array( __CLASS__, 'field_textarea' ), self::OPT, 'kc_section_io', array( 'key' => '__import_json', 'rows' => 6, 'placeholder' => 'Paste settings JSON here and click Save.' ) );
	}

	public static function admin_menu() {
		add_options_page( 'KC Donate Box', 'KC Donate Box', 'manage_options', self::OPT, array( __CLASS__, 'render_page' ) );
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$reset_url  = admin_url( 'admin-post.php' );
		// Read-only notice flag set by our safe redirect after nonce-checked reset action.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$show_reset = (bool) filter_input( INPUT_GET, 'kc_reset', FILTER_VALIDATE_BOOLEAN );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'KC Donate Box', 'kc-donate-box' ); ?></h1>

			<?php if ( $show_reset ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings were reset to defaults.', 'kc-donate-box' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php" style="margin-bottom:16px;">
				<?php
				settings_fields( self::OPT );
				do_settings_sections( self::OPT );
				submit_button( __( 'Save Changes', 'kc-donate-box' ) );
				?>
			</form>

			<form method="post" action="<?php echo esc_url( $reset_url ); ?>">
				<?php wp_nonce_field( 'kcdobo_reset' ); ?>
				<input type="hidden" name="action" value="kcdobo_reset" />
				<?php submit_button( __( 'Reset to defaults', 'kc-donate-box' ), 'delete', 'submit', false, array( 'onclick' => "return confirm('Reset all KC Donate Box settings to factory defaults?');" ) ); ?>
			</form>
		</div>
		<?php
	}

	/* ---------------- Field helpers ---------------- */
	private static function get( $key ) {
		$opts = self::load_options();
		return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	}

	public static function field_checkbox( $args ) {
		$key  = $args['key'];
		$val  = self::get( $key );
		$name = sprintf( '%s[%s]', self::OPT, $key );

		printf(
			'<label><input type="checkbox" name="%1$s" value="1" %2$s /> </label>',
			esc_attr( $name ),
			checked( $val, 1, false )
		);

		if ( ! empty( $args['help'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
		}
	}

	public static function field_text( $args ) {
		$key  = $args['key'];
		$val  = self::get( $key );
		$id   = ! empty( $args['id'] ) ? $args['id'] : 'kc_txt_' . $key;
		$name = sprintf( '%s[%s]', self::OPT, $key );

		printf(
			'<input type="text" id="%1$s" class="regular-text" name="%2$s" value="%3$s" />',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $val )
		);

		if ( ! empty( $args['help'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
		}
	}

	public static function field_textarea( $args ) {
		$key  = $args['key'];
		$val  = self::get( $key );
		$rows = ! empty( $args['rows'] ) ? (int) $args['rows'] : 3;
		$ph   = ! empty( $args['placeholder'] ) ? $args['placeholder'] : '';
		$id   = ! empty( $args['id'] ) ? $args['id'] : 'kc_ta_' . $key;
		$name = sprintf( '%s[%s]', self::OPT, $key );

		printf(
			'<textarea id="%1$s" class="large-text" rows="%2$s" name="%3$s" placeholder="%4$s">%5$s</textarea>',
			esc_attr( $id ),
			esc_attr( (string) $rows ),
			esc_attr( $name ),
			esc_attr( $ph ),
			esc_textarea( $val )
		);

		if ( ! empty( $args['help'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
		}
	}

	/* -------- Row templates for JS (no inline <script>) -------- */
	private static function link_row_html( $i, $row ) {
		$checked = ! empty( $row['enabled'] );
		$label   = isset( $row['label'] ) ? $row['label'] : '';
		$url     = isset( $row['url'] ) ? $row['url'] : '';

		echo '<div class="kc-link-row">';

		printf(
			'<label style="display:inline-block;margin-right:8px;"><input type="checkbox" name="%1$s[links][%2$s][enabled]" value="1" %3$s> %4$s</label>',
			esc_attr( self::OPT ),
			esc_attr( (string) $i ),
			checked( $checked, true, false ),
			esc_html__( 'Enabled', 'kc-donate-box' )
		);

		printf(
			'<input type="text" name="%1$s[links][%2$s][label]" value="%3$s" placeholder="%4$s" style="width:36%%;margin-right:6px;">',
			esc_attr( self::OPT ),
			esc_attr( (string) $i ),
			esc_attr( $label ),
			esc_attr__( 'Label (e.g., Buy me a coffee)', 'kc-donate-box' )
		);

		printf(
			'<input type="url" name="%1$s[links][%2$s][url]" value="%3$s" placeholder="https://..." style="width:48%%;margin-right:6px;">',
			esc_attr( self::OPT ),
			esc_attr( (string) $i ),
			esc_attr( $url )
		);

		echo '<button type="button" class="button kc-remove-link">' . esc_html__( 'Remove', 'kc-donate-box' ) . '</button>';
		echo '</div>';
	}

	private static function link_row_template() {
		$tmpl = array( 'enabled' => 1, 'label' => '', 'url' => '' );
		ob_start();
		self::link_row_html( '__INDEX__', $tmpl ); // __INDEX__ → replaced in JS
		$html = ob_get_clean();
		return str_replace( array( "\n", "\r" ), '', $html );
	}

	private static function crypto_row_template() {
		$tmpl = array(
			'enabled'       => 1,
			'type'          => 'bitcoin',
			'address'       => '',
			'custom_scheme' => 'bitcoin',
			'qr_mode'       => 'upload',
			'qr_url'        => '',
			'copy_button'   => 1,
		);
		ob_start();
		self::crypto_row_html( '__INDEX__', $tmpl );
		$html = ob_get_clean();
		return str_replace( array( "\n", "\r" ), '', $html );
	}

	/* -------- Links repeater (admin) -------- */
	public static function field_links_repeater() {
		$links = self::get( 'links' );
		if ( ! is_array( $links ) ) {
			$links = array();
		}

		echo '<div id="kc-links-repeater">';
		foreach ( $links as $i => $row ) {
			$label   = isset( $row['label'] ) ? $row['label'] : '';
			$url     = isset( $row['url'] ) ? $row['url'] : '';
			$checked = ! empty( $row['enabled'] );

			echo '<div class="kc-link-row">';

			printf(
				'<label style="display:inline-block;margin-right:8px;"><input type="checkbox" name="%1$s[links][%2$s][enabled]" value="1" %3$s> %4$s</label>',
				esc_attr( self::OPT ),
				esc_attr( (string) $i ),
				checked( $checked, true, false ),
				esc_html__( 'Enabled', 'kc-donate-box' )
			);

			printf(
				'<input type="text" name="%1$s[links][%2$s][label]" value="%3$s" placeholder="%4$s" style="width:36%%;margin-right:6px;">',
				esc_attr( self::OPT ),
				esc_attr( (string) $i ),
				esc_attr( $label ),
				esc_attr__( 'Label (e.g., Buy me a coffee)', 'kc-donate-box' )
			);

			printf(
				'<input type="url" name="%1$s[links][%2$s][url]" value="%3$s" placeholder="https://..." style="width:48%%;margin-right:6px;">',
				esc_attr( self::OPT ),
				esc_attr( (string) $i ),
				esc_attr( $url )
			);

			echo '<button type="button" class="button kc-remove-link">' . esc_html__( 'Remove', 'kc-donate-box' ) . '</button>';
			echo '</div>';
		}
		echo '</div>';

		// Hidden marker so sanitize() knows this section was present (even if empty).
		echo '<input type="hidden" name="' . esc_attr( self::OPT ) . '[__links_present]" value="1">';
		echo '<p><button type="button" class="button button-secondary" id="kc-add-link">+ ' . esc_html__( 'Add link', 'kc-donate-box' ) . '</button></p>';
	}

	/* -------- Crypto repeater (admin) -------- */
	public static function field_crypto_repeater() {
		$list = self::get( 'cryptos' );
		if ( ! is_array( $list ) ) {
			$list = array();
		}

		echo '<div id="kc-crypto-repeater">';
		foreach ( $list as $i => $row ) {
			self::crypto_row_html( $i, $row );
		}
		echo '</div>';

		// Hidden marker so sanitize() knows this section was present (even if empty).
		echo '<input type="hidden" name="' . esc_attr( self::OPT ) . '[__cryptos_present]" value="1">';
		echo '<p><button type="button" class="button button-secondary" id="kc-add-crypto">+ ' . esc_html__( 'Add crypto', 'kc-donate-box' ) . '</button></p>';
	}

	private static function crypto_row_html( $i, $row ) {
		$enabled  = ! empty( $row['enabled'] );
		$type     = isset( $row['type'] ) ? $row['type'] : 'bitcoin';
		$address  = isset( $row['address'] ) ? $row['address'] : '';
		$scheme   = isset( $row['custom_scheme'] ) ? $row['custom_scheme'] : 'bitcoin';
		$qr_mode  = isset( $row['qr_mode'] ) ? $row['qr_mode'] : 'upload';
		$qr_url   = isset( $row['qr_url'] ) ? $row['qr_url'] : '';
		$copy_btn = ! empty( $row['copy_button'] );

		$namebase = sprintf( '%s[cryptos][%s]', self::OPT, (string) $i );
		$media_id = 'kc_media_qr_' . md5( $namebase . '[qr_url]' );

		echo '<div class="kc-crypto-row">';
		echo '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">';

		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="1" %2$s> %3$s</label>',
			esc_attr( $namebase ),
			checked( $enabled, true, false ),
			esc_html__( 'Enabled', 'kc-donate-box' )
		);

		echo '<label> ' . esc_html__( 'Type', 'kc-donate-box' ) . ' ';
		printf( '<select name="%s[type]">', esc_attr( $namebase ) );
		$types = array(
			'bitcoin'  => 'Bitcoin',
			'ethereum' => 'Ethereum',
			'litecoin' => 'Litecoin',
			'custom'   => 'Custom',
		);
		foreach ( $types as $k => $lbl ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $k ),
				selected( $type, $k, false ),
				esc_html( $lbl )
			);
		}
		echo '</select></label>';

		printf(
			'<label> %1$s <input type="text" name="%2$s[address]" value="%3$s" placeholder="%4$s" style="min-width:260px;"></label>',
			esc_html__( 'Address', 'kc-donate-box' ),
			esc_attr( $namebase ),
			esc_attr( $address ),
			esc_attr__( 'Wallet address', 'kc-donate-box' )
		);

		printf(
			'<label> %1$s <input type="text" name="%2$s[custom_scheme]" value="%3$s" placeholder="%4$s"></label>',
			esc_html__( 'Custom scheme', 'kc-donate-box' ),
			esc_attr( $namebase ),
			esc_attr( $scheme ),
			esc_attr__( 'mycoin (only when type = Custom)', 'kc-donate-box' )
		);

		echo '<label> ' . esc_html__( 'QR mode', 'kc-donate-box' ) . ' ';
		printf( '<select name="%s[qr_mode]">', esc_attr( $namebase ) );
		$modes = array(
			'upload' => 'Uploaded image',
			'auto'   => 'Auto (qrserver.com)',
			'none'   => 'Do not show',
		);
		foreach ( $modes as $k => $lbl ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $k ),
				selected( $qr_mode, $k, false ),
				esc_html( $lbl )
			);
		}
		echo '</select></label>';

		printf(
			'<label> %1$s <input type="text" class="regular-text" id="%2$s" name="%3$s[qr_url]" value="%4$s" placeholder="https://..."> ' .
			'<button type="button" class="button kc-media-btn" data-target="%2$s">%5$s</button></label>',
			esc_html__( 'QR image URL', 'kc-donate-box' ),
			esc_attr( $media_id ),
			esc_attr( $namebase ),
			esc_attr( $qr_url ),
			esc_html__( 'Choose image', 'kc-donate-box' )
		);

		printf(
			'<label><input type="checkbox" name="%1$s[copy_button]" value="1" %2$s> %3$s</label>',
			esc_attr( $namebase ),
			checked( $copy_btn, true, false ),
			esc_html__( 'Show copy button', 'kc-donate-box' )
		);

		echo '<button type="button" class="button kc-remove-crypto" style="margin-left:auto;">' . esc_html__( 'Remove', 'kc-donate-box' ) . '</button>';

		echo '</div>';
		echo '</div>';
	}

	/* -------- Export JSON (admin) -------- */
	public static function field_export() {
		$opts = self::load_options();
		unset( $opts['__import_json'] );
		echo '<textarea class="large-text code" rows="6" readonly onclick="this.select()">' . esc_textarea( wp_json_encode( $opts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Copy the JSON above to back up your settings.', 'kc-donate-box' ) . '</p>';
	}

	/* -------- Admin assets (enqueue file-based) -------- */
	public static function admin_assets( $hook ) {
		// Only on our settings page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( $page !== self::OPT ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );

		wp_enqueue_style( 'kcdobo-admin', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), self::VER );
		wp_enqueue_script( 'kcdobo-admin', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), self::VER, true );

		// Pass small HTML templates/config to JS without inline <script> tags elsewhere.
		$config = array(
			'link_row_tmpl'   => self::link_row_template(),
			'crypto_row_tmpl' => self::crypto_row_template(),
		);
		wp_add_inline_script(
			'kcdobo-admin',
			'window.kcdonatebox_admin = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}

	/* ---------------- Sanitize ---------------- */
	public static function sanitize( $input ) {
		$d    = self::defaults();
		$prev = get_option( self::OPT, $d ); // previous saved options (or defaults if none)

		// Import first
		if ( ! empty( $input['__import_json'] ) ) {
			$json = json_decode( stripslashes( $input['__import_json'] ), true );
			if ( is_array( $json ) ) {
				$merged                  = wp_parse_args( $json, $d );
				$merged['__import_json'] = '';
				return $merged;
			}
		}

		$out                 = array();
		$out['enabled']      = ! empty( $input['enabled'] ) ? 1 : 0;
		$out['on_singular']  = ! empty( $input['on_singular'] ) ? 1 : 0;
		$out['title']        = isset( $input['title'] )   ? sanitize_text_field( $input['title'] )   : ( isset( $prev['title'] ) ? $prev['title'] : $d['title'] );
		$out['message']      = isset( $input['message'] ) ? wp_kses_post( $input['message'] )        : ( isset( $prev['message'] ) ? $prev['message'] : $d['message'] );

		/* Links
		 * If the form posts the links section: parse it (empty array allowed).
		 * If not posted at all: keep previous (or defaults).
		 */
		$out['links'] = array();
		if ( array_key_exists( 'links', $input ) || ! empty( $input['__links_present'] ) ) {
			if ( ! empty( $input['links'] ) && is_array( $input['links'] ) ) {
				foreach ( $input['links'] as $row ) {
					$lbl = isset( $row['label'] ) ? wp_kses_post( $row['label'] ) : '';
					$url = isset( $row['url'] )   ? esc_url_raw( $row['url'] )   : '';
					$en  = ! empty( $row['enabled'] ) ? 1 : 0;
					if ( $lbl === '' && $url === '' ) {
						continue;
					}
					$out['links'][] = array(
						'label'   => $lbl,
						'url'     => $url,
						'enabled' => $en,
					);
				}
			}
			// If nothing valid remains, keep it as empty [] intentionally.
		} else {
			$out['links'] = isset( $prev['links'] ) ? $prev['links'] : $d['links'];
		}

		/* Cryptos
		 * If the form posts the cryptos section: parse it (empty array allowed).
		 * If not posted at all: keep previous (or defaults).
		 */
		$out['cryptos'] = array();
		if ( array_key_exists( 'cryptos', $input ) || ! empty( $input['__cryptos_present'] ) ) {
			if ( ! empty( $input['cryptos'] ) && is_array( $input['cryptos'] ) ) {
				foreach ( $input['cryptos'] as $row ) {
					$en   = ! empty( $row['enabled'] ) ? 1 : 0;
					$type = isset( $row['type'] ) ? sanitize_text_field( $row['type'] ) : 'bitcoin';
					if ( ! in_array( $type, array( 'bitcoin', 'ethereum', 'litecoin', 'custom' ), true ) ) {
						$type = 'bitcoin';
					}

					$addr   = isset( $row['address'] )       ? sanitize_text_field( $row['address'] )       : '';
					$scheme = isset( $row['custom_scheme'] ) ? sanitize_text_field( $row['custom_scheme'] ) : 'bitcoin';

					$qrmode = isset( $row['qr_mode'] ) ? sanitize_text_field( $row['qr_mode'] ) : 'upload';
					if ( ! in_array( $qrmode, array( 'upload', 'auto', 'none' ), true ) ) {
						$qrmode = 'upload';
					}
					$qrurl = isset( $row['qr_url'] ) ? esc_url_raw( $row['qr_url'] ) : '';

					$copy = ! empty( $row['copy_button'] ) ? 1 : 0;

					// Skip fully empty + disabled rows
					if ( ! $en && $addr === '' && $qrurl === '' ) {
						continue;
					}

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
		} else {
			$out['cryptos'] = isset( $prev['cryptos'] ) ? $prev['cryptos'] : $d['cryptos'];
		}

		$out['__import_json'] = '';

		return $out;
	}

	/* ---------------- Frontend ---------------- */
	public static function inject_box( $content ) {
		$o = self::load_options();
		if ( ! $o['enabled'] ) {
			return $content;
		}
		if ( $o['on_singular'] && ! is_singular( 'post' ) ) {
			return $content;
		}

		return $content . self::render_box( $o );
	}

	public static function shortcode( $atts = array() ) {
		$o = self::load_options();
		if ( ! $o['enabled'] ) {
			return '';
		}
		return self::render_box( $o );
	}

	private static function coin_display_name( $c ) {
		$map = array(
			'bitcoin'  => 'Bitcoin',
			'ethereum' => 'Ethereum',
			'litecoin' => 'Litecoin',
		);
		if ( isset( $c['type'] ) && 'custom' === $c['type'] ) {
			$s = isset( $c['custom_scheme'] ) ? trim( (string) $c['custom_scheme'] ) : '';
			return $s !== '' ? ucwords( $s ) : 'Custom';
		}
		return isset( $map[ $c['type'] ] ) ? $map[ $c['type'] ] : 'Crypto';
	}

	private static function build_crypto_uri( $c ) {
		$type   = isset( $c['type'] ) ? $c['type'] : '';
		$scheme = ( 'custom' === $type && ! empty( $c['custom_scheme'] ) ) ? $c['custom_scheme'] : $type;
		$addr   = isset( $c['address'] ) ? trim( (string) $c['address'] ) : '';
		if ( '' === $addr || '' === $scheme ) {
			return '';
		}
		return sprintf( '%s:%s', $scheme, $addr );
	}

	private static function render_box( $o ) {
		$html  = '<div class="kc-donate-box kc-support-box">';
		$html .= '<p><strong>' . esc_html( $o['title'] ) . ':</strong> ' . wp_kses_post( $o['message'] ) . '</p>';

		// Links
		if ( ! empty( $o['links'] ) && is_array( $o['links'] ) ) {
			foreach ( $o['links'] as $row ) {
				if ( empty( $row['enabled'] ) || empty( $row['url'] ) ) {
					continue;
				}
				$html .= '<p><a href="' . esc_url( $row['url'] ) . '" target="_blank" rel="noopener nofollow">' . wp_kses_post( $row['label'] ) . '</a></p>';
			}
		}

		// Cryptos
		if ( ! empty( $o['cryptos'] ) && is_array( $o['cryptos'] ) ) {
			foreach ( $o['cryptos'] as $c ) {
				if ( empty( $c['enabled'] ) || empty( $c['address'] ) ) {
					continue;
				}

				$uri   = self::build_crypto_uri( $c );
				$coin  = self::coin_display_name( $c );
				$label = 'Donate with ' . $coin;

				$html .= '<div class="kc-crypto-item" style="margin-top:8px;">';
				$html .= '<p><strong>₿/Ξ:</strong> ';
				if ( $uri ) {
					$html .= '<a href="' . esc_url( $uri ) . '" rel="nofollow noopener">' . esc_html( $label ) . '</a><br>';
				} else {
					$html .= esc_html( $label ) . '<br>';
				}
				$html .= '<small>' . esc_html__( 'Address:', 'kc-donate-box' ) . ' <code class="kc-addr">' . esc_html( $c['address'] ) . '</code>';
				if ( ! empty( $c['copy_button'] ) ) {
					$html .= ' <button type="button" class="kc-copy" data-copy="' . esc_attr( $c['address'] ) . '" style="margin-left:8px;padding:2px 8px;font-size:0.85em;">' . esc_html__( 'Copy', 'kc-donate-box' ) . '</button>';
				}
				$html .= '</small></p>';

				// QR
				if ( ! empty( $c['qr_mode'] ) && 'none' !== $c['qr_mode'] ) {
					$qr = '';
					if ( 'upload' === $c['qr_mode'] && ! empty( $c['qr_url'] ) ) {
						$qr = esc_url( $c['qr_url'] );
					} elseif ( 'auto' === $c['qr_mode'] && $uri ) {
						$qr = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . rawurlencode( $uri );
					}
					if ( $qr ) {
						$html .= '<details style="margin-top:6px;"><summary>' . esc_html__( 'Show QR code', 'kc-donate-box' ) . '</summary>';
						$html .= '<img src="' . $qr . '" alt="' . esc_attr__( 'Crypto QR code', 'kc-donate-box' ) . '" width="160" height="160" loading="lazy"></details>';
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
		if ( ! $o['enabled'] ) {
			return;
		}

		wp_enqueue_style( 'kcdobo-front', plugins_url( 'assets/css/front.css', __FILE__ ), array(), self::VER );
		wp_enqueue_script( 'kcdobo-front', plugins_url( 'assets/js/front.js', __FILE__ ), array(), self::VER, true );
	}

	/* ---------------- Reset ---------------- */
	public static function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		// Try new nonce first; if absent, accept legacy nonce for compatibility.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'kcdobo_reset' ) ) ) {
			check_admin_referer( 'kc_donate_box_reset' );
		}

		update_option( self::OPT, self::defaults(), false );

		// Optional: clear legacy options as well.
		delete_option( self::LEGACY_OPT1 );
		delete_option( self::LEGACY_OPT2 );

		wp_redirect( add_query_arg( array( 'page' => self::OPT, 'kc_reset' => 1 ), admin_url( 'options-general.php' ) ) );
		exit;
	}
}

KCDOBO_Plugin::init();
