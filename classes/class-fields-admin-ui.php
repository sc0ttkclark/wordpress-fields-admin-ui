<?php
/**
 * Class Fields_Admin_UI
 */
class Fields_Admin_UI {

	/**
	 * Instance property
	 *
	 * @var Fields_Admin_UI
	 */
	private static $instance;

	/**
	 * Fields_Admin_UI constructor.
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'fields_register', array( $this, 'fields_register' ) );

	}

	/**
	 * Setup object
	 *
	 * @return Fields_Admin_UI
	 */
	public static function setup() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Register post types
	 */
	public function init() {

		$args = array(
			'labels'       => array(),
			'show_in_menu' => 'options-general.php',
		);

		// Sections
		$args['labels']['all_items'] = 'Manage Sections';

		$this->register_post_type( 'fields_api_section', array( 'Section', 'Sections' ), $args );

		// Fields
		$args['labels']['all_items'] = 'Manage Controls';

		$this->register_post_type( 'fields_api_control', array( 'Control', 'Controls' ), $args );

	}

	/**
	 * Register fields config
	 *
	 * @param WP_Fields_API $wp_fields
	 */
	public function fields_register( $wp_fields ) {

		// Get supported form/control type choices
		$form_choices         = wp_list_pluck( $this->get_fields_api_forms(), 'label' );
		$control_type_choices = $this->get_fields_api_control_types();

		// Fields Admin UI Section config
		$section_id     = 'fields-admin-ui-section';
		$section_config = array(
			'form'     => 'post-edit',
			'label'    => __( 'Section options', 'fields-admin-ui' ),
			'controls' => array(
				array(
					'id'          => 'fields_api_section_id',
					'label'       => __( 'Section ID', 'fields-admin-ui' ),
					'description' => __( '(optional) Used internally for identifying this section. Defaults to fields-admin-ui-{$section->ID}-{$section->post_name}', 'fields-admin-ui' ),
					'type'        => 'text',
					'field'       => array(), // Auto create associated field
				),
				array(
					'id'          => 'fields_api_form',
					'label'       => __( 'Form', 'fields-admin-ui' ),
					'description' => __( 'This is the form the section will appear in. The forms in this list are based on what the Fields API currently supports.', 'fields-admin-ui' ),
					'type'        => 'select',
					'choices'     => $form_choices,
					'field'       => array(), // Auto create associated field
				),
				array(
					'id'          => 'fields_api_object_subtype',
					'label'       => __( 'Object Subtype', 'fields-admin-ui' ),
					'description' => __( 'For post types or taxonomies, you can specify a specific post type or taxonomy name to target. Leave blank to target all post types or taxonomies.', 'fields-admin-ui' ),
					'type'        => 'text',
					'field'       => array(), // Auto create associated field
				),
				array(
					'id'          => 'fields_api_section_desc',
					'label'       => __( 'Section Description', 'fields-admin-ui' ),
					'description' => __( 'Some forms support a description to go along with the section, such as the Settings API.', 'fields-admin-ui' ),
					'type'        => 'textarea',
					'field'       => array(), // Auto create associated field
				),
			),
		);

		$wp_fields->add_section( 'post', $section_id, 'fields_api_section', $section_config );

		// Fields Admin UI Control config
		$section_id     = 'fields-admin-ui-control';
		$section_config = array(
			'form'     => 'post-edit',
			'label'    => __( 'Control options', 'fields-admin-ui' ),
			'controls' => array(
				array(
					'id'          => 'fields_api_control_id',
					'label'       => __( 'Control ID', 'fields-admin-ui' ),
					'description' => __( '(optional) Used internally for identifying this control. Defaults to {$section->post_name}_{$control->post_name}', 'fields-admin-ui' ),
					'type'        => 'text',
					'field'       => array(), // Auto create associated field
				),
				array(
					'id'          => 'fields_api_section',
					'label'       => __( 'Section', 'fields-admin-ui' ),
					'description' => __( 'This is the section the control will appear in.', 'fields-admin-ui' ),
					'type'        => 'dropdown-posts',
					'post_type'   => 'fields_api_section',
					'field'       => array(), // Auto create associated field
				),
				array(
					'id'          => 'fields_api_control_type',
					'label'       => __( 'Control Type', 'fields-admin-ui' ),
					'description' => __( 'There are a limited number of control types you can create with this plugin, but more exist in the Fields API and additional ones can be registered.', 'fields-admin-ui' ),
					'type'        => 'select',
					'choices'     => $control_type_choices,
					'field'       => array(), // Auto create associated field
				),
				array(
					'id'          => 'fields_api_control_desc',
					'label'       => __( 'Control Description', 'fields-admin-ui' ),
					'description' => __( 'This description shows next to the control, just like *this* description is.', 'fields-admin-ui' ),
					'type'        => 'textarea',
					'field'       => array(), // Auto create associated field
				),
			),
		);

		$wp_fields->add_section( 'post', $section_id, 'fields_api_control', $section_config );

		// Now register the config for any sections/fields created using this plugin
		$this->register_fields_config( $wp_fields );

	}

	/**
	 * Register fields config for sections/fields created using this plugin
	 *
	 * @param WP_Fields_API $wp_fields
	 */
	protected function register_fields_config( $wp_fields ) {

		// Get supported forms/control types
		$fields_api_forms         = $this->get_fields_api_forms();
		$fields_api_control_types = $this->get_fields_api_control_types();

		$args = array(
			'post_type'      => 'fields_api_section',
			'posts_per_page' => 50,
			'meta_query'     => array(
				array(
					'key'     => 'fields_api_form',
					'compare' => 'EXISTS',
				),
			),
		);

		$sections = get_posts( $args );

		foreach ( $sections as $section ) {
			$form = get_post_meta( $section->ID, 'fields_api_form', true );

			// Skip sections without forms or forms that aren't supported
			if ( empty( $form ) || empty( $fields_api_forms[ $form ] ) ) {
				continue;
			}

			$object_type = $fields_api_forms[ $form ]['object_type'];
			$object_subtype = get_post_meta( $section->ID, 'fields_api_object_subtype', true );

			// Set empty Object subtype to null
			if ( empty( $object_subtype ) ) {
				$object_subtype = null;
			}

			// Section config
			$section_id = get_post_meta( $section->ID, 'fields_api_section_id', true );

			if ( empty( $section_id ) ) {
				$section_id = sanitize_key( 'fields-admin-ui-' . $section->ID . '-' . $section->post_name );
			}

			$section_config = array(
				'form'     => $form,
				'label'    => $section->post_title,
				'controls' => array(),
			);

			$section_desc = get_post_meta( $section->ID, 'fields_api_section_desc', true );

			if ( ! empty( $section_desc ) ) {
				$section_config['description'] = $section_desc;
			}

			// Get controls for section
			$args = array(
				'post_type'      => 'fields_api_control',
				'posts_per_page' => 50,
				'meta_query'     => array(
					array(
						'key'   => 'fields_api_section',
						'value' => $section->ID,
					),
				),
			);

			$controls = get_posts( $args );

			if ( ! empty( $controls ) ) {
				foreach ( $controls as $control ) {
					// Control config
					$control_id = get_post_meta( $control->ID, 'fields_api_control_id', true );

					if ( empty( $control_id ) ) {
						$control_id = sanitize_key( $section->post_name . '_' . $control->post_name );
					}

					$section_config['controls'][ $control_id ] = array(
						'label' => $control->post_title,
					);

					$control_type = get_post_meta( $control->ID, 'fields_api_control_type', true );

					if ( ! empty( $control_type ) && isset( $fields_api_control_types[ $control_type ] ) ) {
						$section_config['controls'][ $control_id ]['type'] = $control_type;
					}

					$control_desc = get_post_meta( $control->ID, 'fields_api_control_desc', true );

					if ( ! empty( $control_desc ) ) {
						$section_config['controls'][ $control_id ]['description'] = $control_desc;
					}
				}

				$wp_fields->add_section( $object_type, $section_id, $object_subtype, $section_config );
			}
		}

	}

	/**
	 * Register post type helper method
	 *
	 * @param string $post_type Post type name
	 * @param array  $labels    Singular and plural labels
	 * @param array  $args      Post type args
	 */
	protected function register_post_type( $post_type, $labels, $args = array() ) {

		if ( empty( $labels ) ) {
			return;
		}

		$show_ui = false;

		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			$show_ui = true;
		}

		// Set defaults
		$defaults = array(
			'public'          => false,
			'show_ui'         => $show_ui,
			'show_in_menu'    => $show_ui,
			'capability_type' => 'post',
			'menu_position'   => 80,
			'supports'        => array(
				'title',
			),
			'rewrite'         => false,
		);

		// Merge defaults
		$args = array_merge( $defaults, $args );

		// Setup labels
		$label_singular = $labels[0];
		$label_plural   = $label_singular;

		if ( ! empty( $labels[1] ) ) {
			$label_plural = $labels[1];
		}

		$labels = array(
			'name'               => $label_plural,
			'singular_name'      => $label_singular,
			'menu_name'          => $label_plural,
			'name_admin_bar'     => $label_singular,
			'add_new'            => _x( 'Add New', 'Post type add_new', 'fields-admin-ui' ),
			'add_new_item'       => sprintf( _x( 'Add New %s', 'Post type add_new_item', 'fields-admin-ui' ), $label_singular ),
			'new_item'           => sprintf( _x( 'New %s', 'Post type new_item', 'fields-admin-ui' ), $label_singular ),
			'edit_item'          => sprintf( _x( 'Edit %s', 'Post type edit_item', 'fields-admin-ui' ), $label_singular ),
			'view_item'          => sprintf( _x( 'View %s', 'Post type view_item', 'fields-admin-ui' ), $label_singular ),
			'all_items'          => sprintf( _x( 'All %s', 'Post type all_items', 'fields-admin-ui' ), $label_plural ),
			'search_items'       => sprintf( _x( 'Search %s', 'Post type search_items', 'fields-admin-ui' ), $label_plural ),
			'parent_item_colon'  => sprintf( _x( 'Parent %s:', 'Post type parent_item_colon', 'fields-admin-ui' ), $label_singular ),
			'not_found'          => sprintf( _x( 'No %s found.', 'Post type not_found', 'fields-admin-ui' ), $label_plural ),
			'not_found_in_trash' => sprintf( _x( 'No %s found in Trash.', 'Post type not_found_in_trash', 'fields-admin-ui' ), $label_plural ),
		);

		if ( ! empty( $args['labels'] ) ) {
			$labels = array_merge( $labels, $args['labels'] );
		}

		$args['labels'] = $labels;

		// Register post type
		register_post_type( $post_type, $args );

	}

	/**
	 * Get the currently supported Fields API Forms
	 *
	 * @return array
	 */
	protected function get_fields_api_forms() {

		$forms = array(
			'post-edit'          => array(
				'label'       => __( 'Posts: Edit', 'fields-admin-ui' ),
				'object_type' => 'post',
			),
			'term-add'           => array(
				'label'       => __( 'Terms: Add new', 'fields-admin-ui' ),
				'object_type' => 'term',
			),
			'term-edit'          => array(
				'label'       => __( 'Terms: Edit', 'fields-admin-ui' ),
				'object_type' => 'term',
			),
			'comment-edit'       => array(
				'label'       => __( 'Comments: Edit', 'fields-admin-ui' ),
				'object_type' => 'comment',
			),
			/*'user-add'            => array(
				'label'       => __( 'Users: Add new', 'fields-admin-ui' ),
				'object_type' => 'user',
			),*/
			'user-edit'          => array(
				'label'       => __( 'Users: Profile edit', 'fields-admin-ui' ),
				'object_type' => 'user',
			),
			'settings-general'   => array(
				'label'       => __( 'Settings: General', 'fields-admin-ui' ),
				'object_type' => 'settings',
			),
			'settings-writing'   => array(
				'label'       => __( 'Settings: Writing', 'fields-admin-ui' ),
				'object_type' => 'settings',
			),
			'settings-reading'   => array(
				'label'       => __( 'Settings: Reading', 'fields-admin-ui' ),
				'object_type' => 'settings',
			),
			/*'settings-discussion' => array(
				'label'       => __( 'Settings: Discussion', 'fields-admin-ui' ),
				'object_type' => 'settings',
			),
			'settings-media'      => array(
				'label'       => __( 'Settings: Media', 'fields-admin-ui' ),
				'object_type' => 'settings',
			),*/
			'settings-permalink' => array(
				'label'       => __( 'Settings: Permalink', 'fields-admin-ui' ),
				'object_type' => 'settings',
			),
		);

		return $forms;

	}

	/**
	 * Get the currently supported Fields API Control Types
	 *
	 * @return array
	 */
	protected function get_fields_api_control_types() {

		$control_types = array(
			'text'     => __( 'Text', 'fields-admin-ui' ),
			'number'   => __( 'Number', 'fields-admin-ui' ),
			'email'    => __( 'E-mail', 'fields-admin-ui' ),
			'password' => __( 'Password', 'fields-admin-ui' ),
			'textarea' => __( 'Textarea', 'fields-admin-ui' ),
			'checkbox' => __( 'Checkbox', 'fields-admin-ui' ),
			'color'    => __( 'Color', 'fields-admin-ui' ),
		);

		return $control_types;

	}

}
