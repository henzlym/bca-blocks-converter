<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BCA_CONVERTER_LIST extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Post', 'bca' ), //singular name of the listed records
			'plural'   => __( 'Posts', 'bca' ), //plural name of the listed records
			'ajax'     => true //should this table support ajax?

        ] );
    }

    public function ajax_response() {
 
		$this->prepare_items();
		ob_start();
		$this->views();
		$this->search_box( __( 'Search', 'bca' ), 'bca-search' );
		$this->display();
		$table = ob_get_clean();
     
        return $table;
    }
	/**
	 * Set common arguments for table rendering query.
	 *
	 * @return array
	 */
	public static function set_args_for_query() {
		$post_types    = unserialize( BCA_BLOCK_CONVERTER_TYPES );
		$post_statuses = unserialize( BCA_BLOCK_CONVERTER_STATUSES );

		$args = array(
			'post_type'   => $post_types,
			'post_status' => $post_statuses,
			'tax_query'		 => array(
				array(
					'taxonomy' => 'bca_blocks_converter',
					'field'	   => 'slug',
					'terms'    => 'classic'
				)
			),
			// 'no_found_rows' => true,
			// 'update_post_meta_cache' => false,
			// 'update_post_term_cache' => false,
		);

		if ( ! empty( $_REQUEST['bca_post_type'] ) ) {
			$args['post_type'] = $_REQUEST['bca_post_type'];
		}

		if ( ! empty( $_REQUEST['bca_post_status'] ) ) {
			$args['post_status'] = $_REQUEST['bca_post_status'];
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}

		if ( ! empty( $_REQUEST['orderby'] ) && $_REQUEST['orderby'] == 'post_title' ) {
			$args['orderby'] = 'title';
			if ( ! empty( $_REQUEST['order'] ) && $_REQUEST['order'] == 'asc' ) {
				$args['order'] = 'ASC';
			}
			if ( ! empty( $_REQUEST['order'] ) && $_REQUEST['order'] == 'desc' ) {
				$args['order'] = 'DESC';
			}
		}

		return $args;
	}

	/**
	 * Get posts with 'bblock_not_converted' meta field
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_posts( $per_page = 20, $page_number = 1 ) {

		$args = self::set_args_for_query();

		$args['posts_per_page'] = $per_page;

		$offset         = $per_page * $page_number - $per_page;
		$args['offset'] = $offset;

		$posts_array = get_posts( $args );

		$results = array();
		foreach ( $posts_array as $post ) {
			$results[] = array(
				'ID'         => $post->ID,
				'post_title' => $post->post_title,
				'post_type'  => $post->post_type,
				'action'     => '',
			);
		}

		return $results;
    }
    
	/**
	 * Return the count of posts that need to be converted.
	 *
	 * @return int
	 */
	public static function count_items() {

		$total_actual = 0;
		$post_statuses = bca_block_converter_count_posts();
		foreach ( $post_statuses as $status ) {
			$total_actual += (int) $status;
        }
		return $total_actual;
	}

	/**
	 * Returns the count of posts with a specific status.
	 *
	 * @return int
	 */
	public static function count_with_status( $post_status ) {
		$total_actual = 0;
		$post_statuses = bca_block_converter_count_posts($post_status);
		foreach ( $post_statuses as $status ) {
			$total_actual += (int) $status;
        }
		return $total_actual;
	}

	/** Text displayed when no data is available */
	public function no_items() {
		_e( 'No items available.', 'bca' );
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'         => '<input type="checkbox" />',
			'post_title' => __( 'Title', 'bca' ),
			'post_type'  => __( 'Post Type', 'bca' ),
			'action'     => __( 'Action', 'bca' ),
		];

		return $columns;
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		$post_id = absint( $item['ID'] );
		return sprintf(
			'<input type="checkbox" id="bca-convert-checkbox-%s" name="bulk-convert[]" value="%s" />',
			$post_id,
			$post_id
		);
	}

	/**
	 * Method for post title column
	 *
	 * @param array $item an array of data
	 *
	 * @return string
	 */
	public function column_post_title( $item ) {

		$title = '<strong><a href="' . get_permalink( $item['ID'] ) . '" target="_blank">' . $item['post_title'] . '</a></strong>';

		return $title;
	}

	/**
	 * Method for post type column
	 *
	 * @param array $item an array of data
	 *
	 * @return string
	 */
	public function column_post_type( $item ) {

		$url = esc_url( add_query_arg( array( 'bca_post_type' => $item['post_type'] ) ) );

		$post_type_obj = get_post_type_object( $item['post_type'] );
		$label         = $post_type_obj->labels->singular_name;

		$type = '<a href="' . $url . '">' . $label . '</a>';

		return $type;
	}

	/**
	 * Method for action column
	 *
	 * @param array $item an array of data
	 *
	 * @return string
	 */
	public function column_action( $item ) {

		$convert_nonce = wp_create_nonce( 'bca_convert_post_' . $item['ID'] );

		$action = '<a href="#" id="' . absint( $item['ID'] ) . '" class="bca-single-convert bca-single-convert-'.absint( $item['ID'] ).'">' . __( 'Convert', 'bca' ) . '</a>';

		return $action;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'post_title' => array( 'post_title', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-convert' => __( 'Convert', 'bca' ),
		);

		return $actions;
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @return array
	 */
	protected function get_views() {
		$status_links  = array();
		$post_statuses = unserialize( BCA_BLOCK_CONVERTER_STATUSES );
		array_unshift( $post_statuses, 'any' );
		foreach ( $post_statuses as $status ) {
			$status_count = self::count_with_status( $status );
			if ( $status_count > 0 ) {
				$label = bca_status_label( $status );
				if ( ( empty( $_REQUEST['bca_post_status'] ) && $status == 'any' )
					|| ( ! empty( $_REQUEST['bca_post_status'] ) && $_REQUEST['bca_post_status'] == $status ) ) {
					$status_links[ $status ] = '<strong>' . $label . '</strong> (' . number_format($status_count) . ')';
				} else {
					if ( $status == 'any' ) {
						$url = '?page=' . $_REQUEST['page'];
					} else {
						$url = '?page=' . $_REQUEST['page'] . '&bca_post_status=' . $status;
					}
					$status_links[ $status ] = '<a href="' . $url . '">' . $label . '</a> (' . number_format($status_count) . ')';
				}
			}
		}

		return $status_links;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string $which
	 */
	function extra_tablenav( $which ) {
		$post_types = unserialize( BCA_BLOCK_CONVERTER_TYPES );
		if ( $which == 'top' ) {
			if ( $this->has_items() ) {
				?>
			<div class="alignleft actions bulkactions">
				<select name="bca_post_type">
					<option value="">All Post Types</option>
					<?php
					foreach ( $post_types as $post_type ) {
						$selected = '';
						if ( ! empty( $_REQUEST['bca_post_type'] ) && $_REQUEST['bca_post_type'] == $post_type ) {
							$selected = ' selected = "selected"';
						}
						$post_type_obj = get_post_type_object( $post_type );
						$label         = $post_type_obj->labels->name;
						?>
					<option value="<?php echo $post_type; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
						<?php
					}
					?>
				</select>
				<?php submit_button( __( 'Filter', 'bca' ), 'action', 'bca_filter_btn', false ); ?>
			</div>
				<?php
			}
		}
		if ( $which == 'bottom' ) {
			// The code that goes after the table is there

		}
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page    = $this->get_items_per_page( 'posts_per_page', 20 );
		$total_items = self::count_items();

		$this->set_pagination_args(
			[
				'total_items' => $total_items, // WE have to calculate the total number of items.
				'per_page'    => $per_page, // WE have to determine how many items to show on a page.
			]
		);

		$current_page = $this->get_pagenum();

		$this->items = self::get_posts( $per_page, $current_page );
	}

}