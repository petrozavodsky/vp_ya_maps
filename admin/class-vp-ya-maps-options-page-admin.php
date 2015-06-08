<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WordPress_Plugin_Template_Settings {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $settings_base;
	private $settings;
	private $plugin_textdomain;

	public function __construct( $file ) {
		$this->file          = $file;
		$this->dir           = dirname( $this->file );
		$this->assets_dir    = trailingslashit( $this->dir ) . 'admin';
		$this->assets_url    = esc_url( trailingslashit( plugins_url( '/admin/', $this->file ) ) );
		$this->settings_base = 'vp_yandex_maps';
		$this->plugin_textdomain = 'vp_yandex_maps';

		add_action( 'admin_init', array( $this, 'init' ) );

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array(
			$this,
			'add_settings_link'
		) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item() {
		$page = add_options_page( __( 'Plugin Settings', $this->plugin_textdomain ), __( 'Plugin Settings', $this->plugin_textdomain ), 'manage_options', 'plugin_settings', array(
			$this,
			'settings_page'
		) );
		add_action( 'admin_print_styles-' . $page, array(
			$this,
			'settings_assets'
		) );
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets() {
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );
		wp_enqueue_media();
		wp_register_script( 'wpt-admin-js', $this->assets_url . 'js/class-vp-ya-maps-options-page-admin.js', array(
			'farbtastic',
			'jquery'
		), '1.0.0' );
		wp_enqueue_script( 'wpt-admin-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links
	 *
	 * @return array        Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=plugin_settings">' . __( 'Settings', $this->plugin_textdomain ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['post_types'] = array(
			'title'       => __( 'Post types', $this->plugin_textdomain ),
			'description' => __( 'Settings behavior maps for certain types of posts.', $this->plugin_textdomain ),
			'fields'      => array(
				array(
					'id'          => 'multiple_checkboxes',
					'label'       => __( 'Some Items', $this->plugin_textdomain ),
					'description' => __( 'You can select multiple items and they will be stored as an array.', $this->plugin_textdomain ),
					'type'        => 'checkbox_multi',
					'options'     => array(
						'square'    => 'Square',
						'circle'    => 'Circle',
						'rectangle' => 'Rectangle',
						'triangle'  => 'Triangle'
					),
					'default'     => array( 'circle', 'triangle' )
				)
			)
		);

//		$settings['extra'] = array(
//			'title'       => __( 'Extra', $this->plugin_textdomain ),
//			'description' => __( 'These are some extra input fields that maybe aren\'t as common as the others.', $this->plugin_textdomain ),
//			'fields'      => array(
//				array(
//					'id'          => 'number_field',
//					'label'       => __( 'A Number', $this->plugin_textdomain ),
//					'description' => __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', $this->plugin_textdomain ),
//					'type'        => 'number',
//					'default'     => '',
//					'placeholder' => __( '42', $this->plugin_textdomain )
//				),
//				array(
//					'id'          => 'colour_picker',
//					'label'       => __( 'Pick a colour', $this->plugin_textdomain ),
//					'description' => __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', $this->plugin_textdomain ),
//					'type'        => 'color',
//					'default'     => '#21759B'
//				),
//				array(
//					'id'          => 'an_image',
//					'label'       => __( 'An Image', $this->plugin_textdomain ),
//					'description' => __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', $this->plugin_textdomain ),
//					'type'        => 'image',
//					'default'     => '',
//					'placeholder' => ''
//				),
//				array(
//					'id'          => 'multi_select_box',
//					'label'       => __( 'A Multi-Select Box', $this->plugin_textdomain ),
//					'description' => __( 'A standard multi-select box - the saved data is stored as an array.', $this->plugin_textdomain ),
//					'type'        => 'select_multi',
//					'options'     => array(
//						'linux'   => 'Linux',
//						'mac'     => 'Mac',
//						'windows' => 'Windows'
//					),
//					'default'     => array( 'linux' )
//				)
//			)
//		);

		$settings = apply_filters( 'plugin_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {

				// Add section to page
				add_settings_section( $section, $data['title'], array(
					$this,
					'settings_section'
				), 'plugin_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->settings_base . $field['id'];
					register_setting( 'plugin_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array(
						$this,
						'display_field'
					), 'plugin_settings', $section, array( 'field' => $field ) );
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Generate HTML for displaying fields
	 *
	 * @param  array $args Field data
	 *
	 * @return void
	 */
	public function display_field( $args ) {

		$field = $args['field'];

		$html = '';

		$option_name = $this->settings_base . $field['id'];
		$option      = get_option( $option_name );

		$data = '';
		if ( isset( $field['default'] ) ) {
			$data = $field['default'];
			if ( $option ) {
				$data = $option;
			}
		}

		switch ( $field['type'] ) {

			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/>' . "\n";
				break;

			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value=""/>' . "\n";
				break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . $data . '</textarea><br/>' . "\n";
				break;

			case 'checkbox':
				$checked = '';
				if ( $option && 'on' == $option ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
				break;

			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = FALSE;
					if ( in_array( $k, $data ) ) {
						$checked = TRUE;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, TRUE, FALSE ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = FALSE;
					if ( $k == $data ) {
						$checked = TRUE;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, TRUE, FALSE ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = FALSE;
					if ( $k == $data ) {
						$selected = TRUE;
					}
					$html .= '<option ' . selected( $selected, TRUE, FALSE ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
				break;

			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = FALSE;
					if ( in_array( $k, $data ) ) {
						$selected = TRUE;
					}
					$html .= '<option ' . selected( $selected, TRUE, FALSE ) . ' value="' . esc_attr( $k ) . '" />' . $v . '</label> ';
				}
				$html .= '</select> ';
				break;

			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}else{
					$image_thumb = $this->assets_url.'img/no-img.png';
				}

				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image', $this->plugin_textdomain ) . '" data-uploader_button_text="' . __( 'Use image', $this->plugin_textdomain ) . '" class="image_upload_button button" value="' . __( 'Upload new image', $this->plugin_textdomain ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="' . __( 'Remove image', $this->plugin_textdomain ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
				break;

			case 'color':
				?>
				<div class="color-picker" style="position:relative;">
					<input type="text"
					       name="<?php esc_attr_e( $option_name ); ?>"
					       class="color" value="<?php esc_attr_e( $data ); ?>"/>

					<div
						style="position:absolute;background:#FFF;z-index:99;border-radius:100%;"
						class="colorpicker"></div>
				</div>
				<?php
				break;

		}

		switch ( $field['type'] ) {

			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				$html .= '<br/><span class="description">' . $field['description'] . '</span>';
				break;

			default:
				$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . $field['description'] . '</span></label>' . "\n";
				break;
		}

		echo $html;
	}

	/**
	 * Validate individual settings field
	 *
	 * @param  string $data Inputted value
	 *
	 * @return string       Validated value
	 */
	public function validate_field( $data ) {
		if ( $data && strlen( $data ) > 0 && $data != '' ) {
			$data = urlencode( strtolower( str_replace( ' ', '-', $data ) ) );
		}

		return $data;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML
		$html = '<div class="wrap" id="plugin_settings">' . "\n";
		$html .= '<h2>' . __( 'Plugin Settings', $this->plugin_textdomain ) . '</h2>' . "\n";
		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		// Setup navigation
		$html .= '<ul id="settings-sections" class="subsubsub hide-if-no-js">' . "\n";
		$html .= '<li><a class="tab all current" href="#all">' . __( 'All', $this->plugin_textdomain ) . '</a></li>' . "\n";

		foreach ( $this->settings as $section => $data ) {
			$html .= '<li>| <a class="tab" href="#' . $section . '">' . $data['title'] . '</a></li>' . "\n";
		}

		$html .= '</ul>' . "\n";

		$html .= '<div class="clear"></div>' . "\n";

		// Get settings fields
		ob_start();
		settings_fields( 'plugin_settings' );
		do_settings_sections( 'plugin_settings' );
		$html .= ob_get_clean();

		$html .= '<p class="submit">' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', $this->plugin_textdomain ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

}