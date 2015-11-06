<?php
/**
 * Post type ckan-local-dataset
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Dataset
 */
class Ckan_Backend_Local_Dataset {

	// Be careful max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-dataset';
	const FIELD_PREFIX = '_ckan_local_dataset_';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_post_type();

		// add organisation column to admin list
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_organisation_column' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'add_organisation_column_data' ), 10, 2 );

		// create organisation filter dropdown to admin list
		add_action( 'restrict_manage_posts', array( $this, 'add_organisation_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_posts_by_organisation' ) );

		// define backend fields
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// render additional field after main cmb2 form is rendered
		add_action( 'cmb2_after_post_form_' . self::POST_TYPE . '-box', array( $this, 'render_addition_fields' ) );

		// add custom CMB2 field type dataset_identifier
		add_action( 'cmb2_render_dataset_identifier', array( $this, 'cmb2_render_callback_dataset_identifier' ), 10, 5 );
		// add custom CMB2 field type dataset_search
		add_action( 'cmb2_render_dataset_search', array( $this, 'cmb2_render_callback_dataset_search' ), 10, 5 );
		// add custom CMB2 field type mediatype_search
		add_action( 'cmb2_render_mediatype_search', array( $this, 'cmb2_render_callback_mediatype_search' ), 10, 5 );

		// initialize local dataset sync
		new Ckan_Backend_Sync_Local_Dataset( self::POST_TYPE, self::FIELD_PREFIX );
	}

	/**
	 * Renders CMB2 field of type dataset_identifier
	 *
	 * @param CMB2_Field $field The passed in `CMB2_Field` object.
	 * @param mixed      $escaped_value The value of this field escaped. It defaults to `sanitize_text_field`.
	 * @param int        $object_id The ID of the current object.
	 * @param string     $object_type The type of object you are working with.
	 * @param CMB2_Types $field_type_object This `CMB2_Types` object.
	 */
	public function cmb2_render_callback_dataset_identifier( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		$original_identifier = $escaped_value['original_identifier'];
		$organisation        = $escaped_value['organisation'];

		if ( empty( $organisation ) ) {
			$organisation = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
		}
		?>
		<div>
			<?php
			// @codingStandardsIgnoreStart
			echo $field_type_object->input( array(
				'name'  => $field_type_object->_name( '[original_identifier]' ),
				'id'    => $field_type_object->_id( '_original_identifier' ),
				'value' => $original_identifier,
				'desc'  => '',
			) ); ?>
			<span>@</span>
			<?php
			if ( current_user_can( 'edit_data_of_all_organisations' ) ) {
				echo $field_type_object->select( array(
					'name'    => $field_type_object->_name( '[organisation]' ),
					'id'      => $field_type_object->_id( '_organisation' ),
					'options' => $this->cmb2_get_organisation_options( $organisation ),
					'desc'    => '',
				) );
			} else {
				echo $field_type_object->input( array(
					'name'  => $field_type_object->_name( '[organisation]' ),
					'id'    => $field_type_object->_id( '_organisation' ),
					'value' => $organisation,
					'desc'  => '',
					'type'  => 'hidden',
					'class' => false,
				) );
				echo $organisation;
			}
			// @codingStandardsIgnoreEnd
			?>
		</div>
		<?php
		$field_type_object->_desc( true, true );
	}

	/**
	 * Renders CMB2 field of type dataset_search
	 *
	 * @param CMB2_Field $field The passed in `CMB2_Field` object.
	 * @param mixed      $escaped_value The value of this field escaped. It defaults to `sanitize_text_field`.
	 * @param int        $object_id The ID of the current object.
	 * @param string     $object_type The type of object you are working with.
	 * @param CMB2_Types $field_type_object This `CMB2_Types` object.
	 */
	public function cmb2_render_callback_dataset_search( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		?>
		<select class="dataset_search_box"
		        style="width: 50%"
		        name="<?php esc_attr_e( $field->args['_name'] ); ?>"
		        id="<?php esc_attr_e( $field->args['_id'] ); ?>">
			<?php if ( $escaped_value ) : ?>
				<?php $title = Ckan_Backend_Helper::get_dataset_title( $escaped_value ); ?>
				<option selected="selected" value="<?php esc_attr_e( $escaped_value ); ?>"><?php esc_attr_e( $title ); ?></option>
			<?php endif; ?>
		</select>
		<?php
		if ( is_object( $field->group ) ) {
			// Select2 library doesn't send field if it's empty but CMB2 won't save repeatable meta field if it's not in $_POST. So we have to add a dummy_value to the first repeatable item.
			// We just need this workaround if this field is the only field in the repeatable group.
			?>
			<input type="hidden" name="<?php esc_attr_e( $field->group->args['id'] ); ?>[0][dummy_value]" />
			<?php
		}
		?>
		<script type="text/javascript">
			(function($) {
				$("[name='<?php esc_attr_e( $field->args['_name'] ); ?>'").select2(datasetSearchOptions);
			})( jQuery );
		</script>
		<?php
	}

	/**
	 * Renders CMB2 field of type mediatype_search
	 *
	 * @param CMB2_Field $field The passed in `CMB2_Field` object.
	 * @param mixed      $escaped_value The value of this field escaped. It defaults to `sanitize_text_field`.
	 * @param int        $object_id The ID of the current object.
	 * @param string     $object_type The type of object you are working with.
	 * @param CMB2_Types $field_type_object This `CMB2_Types` object.
	 */
	public function cmb2_render_callback_mediatype_search( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		$media_types = get_terms( Ckan_Backend_MediaType::TAXONOMY, array( 'hide_empty' => 0 ) );
		?>
		<select class="mediatype_search_box"
		        style="width: 50%"
		        name="<?php esc_attr_e( $field->args['_name'] ); ?>"
		        id="<?php esc_attr_e( $field->args['_id'] ); ?>">
			<?php // add empty option to make placeholder work ?>
			<option value=""></option>
			<?php
			foreach( $media_types as $media_type ) {
				echo '<option value="' . $media_type->name . '"' . ( $media_type->name === $escaped_value ? ' selected="selected"' : '' ) . '>' . $media_type->name . '</option>';
			}
			?>
		</select>
		<script type="text/javascript">
			(function($) {
				$("[name='<?php esc_attr_e( $field->args['_name'] ); ?>'").select2(mediatypeSearchOptions);
			})( jQuery );
		</script>
		<?php
	}

	/**
	 * Creates organisation options for selectbox
	 *
	 * @param bool $value Current field value.
	 *
	 * @return string
	 */
	public function cmb2_get_organisation_options( $value = false ) {
		$organisation_list = Ckan_Backend_Helper::get_organisation_form_field_options();

		$organisation_options = '';
		$organisation_options .= '<option value="">' . esc_attr__( '- Please choose -', 'ogdch' ) . '</option>';
		foreach ( $organisation_list as $key => $title ) {
			$organisation_options .= '<option value="' . $key . '" ' . selected( $value, $key, false ) . '>' . $title . '</option>';
		}

		return $organisation_options;
	}

	/**
	 * Adds organisation column to admin list
	 *
	 * @param array $columns Array with all current columns.
	 *
	 * @return array
	 */
	public function add_organisation_column( $columns ) {
		$new_columns = array(
			self::FIELD_PREFIX . 'identifier' => __( 'Organization', 'ogdch' ),
		);

		return array_merge( $columns, $new_columns );
	}

	/**
	 * Prints data to organisation column
	 *
	 * @param string $column Name of custom column.
	 * @param int    $post_id Id of current post.
	 */
	public function add_organisation_column_data( $column, $post_id ) {
		if ( self::FIELD_PREFIX . 'identifier' === $column ) {
			$identifier = get_post_meta( $post_id, $column, true );
			echo esc_attr( Ckan_Backend_Helper::get_organization_title( $identifier['organisation'] ) );
		}
	}

	/**
	 * Adds organisation filter to admin list
	 */
	function add_organisation_filter() {
		global $post_type;

		if ( self::POST_TYPE === $post_type ) {
			Ckan_Backend_Helper::print_organisation_filter();
		}
	}

	/**
	 * Applies organisation filter
	 *
	 * @param WP_Query $query The current query.
	 */
	public function filter_posts_by_organisation( $query ) {
		global $post_type, $pagenow;

		if (
			// Only filter when were on the edit page of ckan-local-datasets
			self::POST_TYPE === $post_type &&
			'edit.php' === $pagenow &&
			is_admin() &&
			// Only filter when ckan-local-datasets are queried
			! empty( $query->query_vars['post_type'] ) &&
			$query->query_vars['post_type'] === self::POST_TYPE

		) {
			$organisation_filter   = '';
			if ( isset( $_GET['organisation_filter'] ) ) {
				$organisation_filter = sanitize_text_field( $_GET['organisation_filter'] );
			} elseif ( ! members_current_user_has_role( 'administrator' ) ) {
				// set filter on first page load if user is not an administrator
				$organisation_filter = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
			}

			if ( ! empty( $organisation_filter ) ) {
				// @codingStandardsIgnoreStart
				$query->query_vars['meta_query'] = array(
					array(
						'key'     => self::FIELD_PREFIX . 'identifier',
						'value'   => maybe_serialize( strval( $organisation_filter ) ),
						'compare' => 'LIKE',
					)
				);
				// @codingStandardsIgnoreEnd
			}
		}
	}

	/**
	 * Renders additional fields which aren't saved in database.
	 */
	public function render_addition_fields() {
		// Field shows that the metadata is not yet saved in database -> get values from $_POST array
		echo '<input type="hidden" id="metadata_not_in_db" name="metadata_not_in_db" value="1" />';
	}

	/**
	 * Shows an error message if the dataset is disbaled
	 *
	 * This function is a callback function for CMB2
	 *
	 * @param array  $field_args Array of field arguments.
	 * @param object $field CMB field.
	 *
	 * @return void
	 */
	public function show_message_if_disabled( $field_args, $field ) {
		$post_id = 0;
		if ( isset( $_GET['post'] ) ) {
			$post_id = $_GET['post'];
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$post_id = $_POST['post_ID'];
		}

		// see if dataset is disabled
		$value = get_post_meta( $post_id, self::FIELD_PREFIX . 'disabled', true );
		if ( 'on' === $value ) {
			// @codingStandardsIgnoreStart
			echo '<div class="error"><p>' . __( 'This dataset is disabled and will not be published on the website.', 'ogdch' ) . '</p></div>';
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Registers the post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Datasets', 'ogdch' ),
			'singular_name'      => __( 'Dataset', 'ogdch' ),
			'menu_name'          => __( 'Datasets', 'ogdch' ),
			'name_admin_bar'     => __( 'Datasets', 'ogdch' ),
			'all_items'          => __( 'All Datasets', 'ogdch' ),
			'add_new_item'       => __( 'Add New Dataset', 'ogdch' ),
			'add_new'            => __( 'Add New', 'ogdch' ),
			'new_item'           => __( 'New Dataset', 'ogdch' ),
			'edit_item'          => __( 'Edit Dataset', 'ogdch' ),
			'update_item'        => __( 'Update Dataset', 'ogdch' ),
			'view_item'          => __( 'View Dataset', 'ogdch' ),
			'search_items'       => __( 'Search Datasets', 'ogdch' ),
			'not_found'          => __( 'No Datasets found', 'ogdch' ),
			'not_found_in_trash' => __( 'No Datasets found in Trash', 'ogdch' ),
		);

		$taxonomies = array();
		foreach ( Ckan_Backend::$keywords_tax_mapping as $lang => $taxonomy ) {
			$taxonomies[] = $taxonomy;
		}
		$taxonomies[] = Ckan_Backend_MediaType::TAXONOMY;

		$args = array(
			'label'               => __( 'Datasets', 'ogdch' ),
			'description'         => __( 'Datasets which get synced with CKAN', 'ogdch' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'taxonomies'          => $taxonomies,
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-media-text',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => array( 'slug' => 'dataset' ),
			'map_meta_cap'        => true,
			'capability_type'     => array( 'dataset', 'datasets' ),
			'capabilities'        => array(
				'edit_posts'             => 'edit_datasets',
				'edit_others_posts'      => 'edit_others_datasets',
				'publish_posts'          => 'publish_datasets',
				'read_private_posts'     => 'read_private_datasets',
				'delete_posts'           => 'delete_datasets',
				'delete_private_posts'   => 'delete_private_datasets',
				'delete_published_posts' => 'delete_published_datasets',
				'delete_others_posts'    => 'delete_others_datasets',
				'edit_private_posts'     => 'edit_private_datasets',
				'edit_published_posts'   => 'edit_published_datasets',
				'create_posts'           => 'create_datasets',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post'              => 'edit_dataset',
				'read_post'              => 'read_dataset',
				'delete_post'            => 'delete_dataset',
			),
		);
		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Define the custom fields of this post type
	 *
	 * @return void
	 */
	public function define_fields() {
		global $language_priority;

		/* CMB Mainbox */
		$cmb = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-box',
			'title'        => __( 'Dataset', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* Identifier */
		$cmb->add_field( array(
			'name'       => __( 'Dataset Identifier', 'ogdch' ) . '*',
			'id'         => self::FIELD_PREFIX . 'identifier',
			'desc'       => __( 'Unique identifier of the dataset linked with the publisher. A good way to make sure this identifier is unique is to use the source system ID.', 'ogdch' ),
			'type'       => 'dataset_identifier',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		/* Dataset Information */
		$cmb->add_field( array(
			'name' => __( 'Dataset Information', 'ogdch' ),
			'type' => 'title',
			'id'   => 'dataset_information_title',
		) );

		foreach ( $language_priority as $lang ) {
			/* Title */
			$cmb->add_field( array(
				'name'       => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')*',
				'id'         => self::FIELD_PREFIX . 'title_' . $lang,
				'type'       => 'text',
			) );

			/* Description */
			$cmb->add_field( array(
				'name'       => __( 'Description', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')*',
				'id'         => self::FIELD_PREFIX . 'description_' . $lang,
				'type'       => 'textarea',
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		/* Dates */
		$cmb->add_field( array(
			'name' => __( 'Dates', 'ogdch' ),
			'type' => 'title',
			'id'   => 'dates_title',
		) );

		$cmb->add_field( array(
			'name' => __( 'Issued', 'ogdch' ) . '*',
			'id'   => self::FIELD_PREFIX . 'issued',
			'desc' => __( 'Date of the first publication of this dataset. If this date is unknown, the date of the first publication on this portal can be used.', 'ogdch' ),
			'type' => 'text_date_timestamp',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_field( array(
			'name' => __( 'Modified', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'modified',
			'desc' => __( 'Date when dataset was last modified (since the first publication on the portal).', 'ogdch' ),
			'type' => 'text_date_timestamp',
		) );

		$cmb->add_field( array(
			'name'             => __( 'Accrual Periodicity', 'ogdch' ),
			'id'               => self::FIELD_PREFIX . 'accrual_periodicity',
			'desc'             => __( 'The frequency in which this dataset is updated.', 'ogdch' ),
			'type'             => 'select',
			'show_option_none' => false,
			'options'          => Ckan_Backend_Frequency::get_frequencies(),
		) );

		$temporals_group = $cmb->add_field( array(
			'id'          => self::FIELD_PREFIX . 'temporals',
			'type'        => 'group',
			'name'        => __( 'Temporals', 'ogdch' ),
			'description' => __( 'One or more time period that this dataset covers.', 'ogdch' ),
			'options' => array(
				'group_title'   => __( 'Temporal {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Temporal', 'ogdch' ),
				'remove_button' => __( 'Remove Temporal', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $temporals_group, array(
			'name' => __( 'Start Date', 'ogdch' ),
			'id'   => 'start_date',
			'type' => 'text_date_timestamp',
		) );

		$cmb->add_group_field( $temporals_group, array(
			'name' => __( 'End Date', 'ogdch' ),
			'id'   => 'end_date',
			'type' => 'text_date_timestamp',
		) );

		/* Publisher */
		$cmb->add_field( array(
			'name' => __( 'Publisher Information', 'ogdch' ),
			'type' => 'title',
			'id'   => 'publisher_title',
		) );

		$publishers_group = $cmb->add_field( array(
			'id'          => self::FIELD_PREFIX . 'publishers',
			'type'        => 'group',
			'name'        => __( 'Publishers', 'ogdch' ),
			'description' => __( 'The actual publisher(s) of this dataset. This can be the same as the organisation which publishes this dataset. At least one publisher is required.', 'ogdch' ),
			'options' => array(
				'group_title'   => __( 'Publisher {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Publisher', 'ogdch' ),
				'remove_button' => __( 'Remove Publisher', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $publishers_group, array(
			'name' => __( 'Name', 'ogdch' ) . '*',
			'id'   => 'label',
			'type' => 'text',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$contact_points_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'contact_points',
			'type'        => 'group',
			'name'        => __( 'Contact points', 'ogdch' ),
			'description' => __( 'The contact point if there are questions about this dataset. At least one contact point is required.', 'ogdch' ),
			'options' => array(
				'group_title'   => __( 'Contact Point {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Contact Point', 'ogdch' ),
				'remove_button' => __( 'Remove Contact Point', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $contact_points_group, array(
			'name' => __( 'Name', 'ogdch' ) . '*',
			'id'   => 'name',
			'type' => 'text',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_group_field( $contact_points_group, array(
			'name' => __( 'Email', 'ogdch' ) . '*',
			'id'   => 'email',
			'type' => 'text_email',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_field( array(
			'name' => __( 'Further Information', 'ogdch' ),
			'type' => 'title',
			'id'   => 'further_information_title',
		) );

		/* Categories */
		$cmb->add_field( array(
			'name'              => __( 'Categories', 'ogdch' ),
			'id'                => self::FIELD_PREFIX . 'themes',
			'type'              => 'multicheck',
			'select_all_button' => false,
			'options'           => array( 'Ckan_Backend_Helper', 'get_group_form_field_options' ),
		) );

		/* Further Information */
		$cmb->add_field( array(
			'name' => __( 'Further Information', 'ogdch' ),
			'type' => 'title',
			'id'   => 'relation_title',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Landing Page', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'landing_page',
			'desc'       => __( 'Website with further information about the dataset.', 'ogdch' ),
			'type'       => 'text_url',
		) );

		$relations_group = $cmb->add_field( array(
			'id'          => self::FIELD_PREFIX . 'relations',
			'type'        => 'group',
			'name'        => __( 'Relations', 'ogdch' ),
			'description' => __( 'Further information related to this dataset.', 'ogdch' ),
			'options' => array(
				'group_title'   => __( 'Relation {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Relation', 'ogdch' ),
				'remove_button' => __( 'Remove Relation', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $relations_group, array(
			'name' => __( 'Title', 'ogdch' ),
			'id'   => 'label',
			'type' => 'text',
		) );

		$cmb->add_group_field( $relations_group, array(
			'name' => __( 'URL', 'ogdch' ),
			'id'   => 'url',
			'type' => 'text_url',
		) );

		$see_alsos_group = $cmb->add_field( array(
			'id'          => self::FIELD_PREFIX . 'see_alsos',
			'type'        => 'group',
			'name'        => __( 'Dataset Relations', 'ogdch' ),
			'description' => __( 'Relations to other datasets.', 'ogdch' ),
			'options' => array(
				'group_title'   => __( 'Dataset Relation {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Dataset Relation', 'ogdch' ),
				'remove_button' => __( 'Remove Dataset Relation', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $see_alsos_group, array(
			'name' => __( 'Dataset', 'ogdch' ),
			'id'   => 'dataset_identifier',
			'type' => 'dataset_search',
		) );

		/* Resources */
		$cmb->add_field( array(
			'name' => __( 'Distributions', 'ogdch' ),
			'type' => 'title',
			'id'   => 'distributions_title',
		) );

		$distributions_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'distributions',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Distribution {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Distribution', 'ogdch' ),
				'remove_button' => __( 'Remove Distribution', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Identifier', 'ogdch' ),
			'desc' => __( 'Identifier of the distribution in the source system.', 'ogdch' ),
			'id'   => 'identifier',
			'type' => 'text',
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_group_field( $distributions_group, array(
				'name' => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'   => 'title_' . $lang,
				'type' => 'text',
				'desc' => __( 'If the title is left empty, the title of the dataset is used instead.', 'ogdch' ),
			) );

			$cmb->add_group_field( $distributions_group, array(
				'name'       => __( 'Description', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => 'description_' . $lang,
				'type'       => 'textarea',
				'desc'       => __( 'If the description is left empty, the description of the dataset is used instead.', 'ogdch' ),
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		$cmb->add_group_field( $distributions_group, array(
			'name'              => __( 'Language', 'ogdch' ),
			'id'                => 'languages',
			'desc'              => __( 'Languages in which this distribution is available. If the distribution is language-independent do not check anything.', 'ogdch' ),
			'type'              => 'multicheck_inline',
			'select_all_button' => false,
			'options'           => array(
				'en' => __( 'English', 'ogdch' ),
				'de' => __( 'German', 'ogdch' ),
				'fr' => __( 'French', 'ogdch' ),
				'it' => __( 'Italian', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Issued', 'ogdch' ) . '*',
			'id'   => 'issued',
			'desc' => __( 'Date of the publication of this distribution.', 'ogdch' ),
			'type' => 'text_date_timestamp',
			'attributes' => array(
				'required' => 'required',
			),
			//'date_format' => 'd.m.Y', // TODO date_format is still buggy in the CMB2 plugin
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Modified', 'ogdch' ),
			'id'   => 'modified',
			'desc' => __( 'Date of the last change of the distribution.', 'ogdch' ),
			'type' => 'text_date_timestamp',
			//'date_format' => 'd.m.Y', // TODO date_format is still buggy in the CMB2 plugin
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name'             => __( 'Terms of use', 'ogdch' ) . '*',
			'id'               => 'rights',
			'desc'             => __( 'All terms of use which are not marked with an asterisk (*) are declared as close data.', 'ogdch' ),
			'type'             => 'select',
			'show_option_none' => false,
			'options'          => Ckan_Backend_Rights::get_rights(),
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name'       => __( 'Access URL', 'ogdch' ) . '*',
			'id'         => 'access_url',
			'desc'       => __( 'URL where the distribution can be found. This could be either a download url, an API url or a landing page url. If the distribution is only available through a landing page, this field must contain the url of the landing page. If a download url was given for this distribution, this field has to contain the same value.', 'ogdch' ),
			'type'       => 'text_url',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Download URL', 'ogdch' ),
			'id'   => 'download_url',
			'desc'       => __( 'URL of a file, if the distribution can be downloaded.', 'ogdch' ),
			'type' => 'text_url',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Size (in Bytes)', 'ogdch' ),
			'id'   => 'byte_size',
			'type' => 'text',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Mediatype', 'ogdch' ),
			'id'   => 'media_type',
			'type' => 'mediatype_search',
		) );

		/* CMB Sidebox to disable dataset */
		$cmb_side_disabled = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox-disabled',
			'title'        => __( 'Disable Dataset', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		$cmb_side_disabled->add_field( array(
			'desc'       => __( 'Disable Dataset', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'disabled',
			'type'       => 'checkbox',
			'before_row' => array( $this, 'show_message_if_disabled' ),
		) );

		/* CMB Sidebox for CKAN data */
		$cmb_side_ckan = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox-ckan',
			'title'        => __( 'CKAN Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		/* Ckan id (If Set -> update. Set on first save) */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'CKAN ID', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'ckan_id',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Ckan name */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'CKAN Name (Slug)', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'ckan_name',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

	}
}
