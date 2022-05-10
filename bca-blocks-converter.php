<?php
/**
 *
 * @link https://organicthemes.com
 * @since 1.0.0
 * @package Organic_Widgets
 *
 * @wordpress-plugin
 * Plugin Name: Blk Canvas - Block Converter
 * Plugin URI: https://publisherdesk.com/
 * Description: Convert all classic content to blocks. An extremely useful tool when upgrading to the WordPress 5 Gutenberg editor.
 * Version: 1.0.0
 * Author: Blk Canvas
 * Author URI: https://publisherdesk.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: bca
 * Domain Path: /languages/
 */

define('BCA_BLOCK_CONVERTER_PATH', plugin_dir_path( __FILE__ ) );
define('BCA_BLOCK_CONVERTER_URI', plugin_dir_url( __FILE__ ) );
// post types and statuses plugin work with
define( 'BCA_BLOCK_CONVERTER_TYPES', serialize( array( 'post' ) ) );
define( 'BCA_BLOCK_CONVERTER_STATUSES', serialize( array( 'publish', 'future', 'draft', 'private' ) ) );
define( 'BCA_BLOCK_CONVERTER_PER_PAGE', 2 );

if ( ! function_exists( 'bca_register_block_converter_taxonomy' ) ) {

	// Register Custom Taxonomy
	function bca_register_block_converter_taxonomy() {
	
		$labels = array(
			'name'                       => _x( 'Classic to Gutenberg Posts', 'Taxonomy General Name', 'bca' ),
			'singular_name'              => _x( 'Classic to Gutenberg Post', 'Taxonomy Singular Name', 'bca' ),
			'menu_name'                  => __( 'Classic to Gutenberg Posts', 'bca' ),
			'all_items'                  => __( 'All Posts', 'bca' ),
			'parent_item'                => __( 'Parent Post', 'bca' ),
			'parent_item_colon'          => __( 'Parent Post:', 'bca' ),
			'new_item_name'              => __( 'New Post Name', 'bca' ),
			'add_new_item'               => __( 'Add New Post', 'bca' ),
			'edit_item'                  => __( 'Edit Post', 'bca' ),
			'update_item'                => __( 'Update Post', 'bca' ),
			'view_item'                  => __( 'View Post', 'bca' ),
			'separate_items_with_commas' => __( 'Separate post with commas', 'bca' ),
			'add_or_remove_items'        => __( 'Add or remove posts', 'bca' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'bca' ),
			'popular_items'              => __( 'Popular Items', 'bca' ),
			'search_items'               => __( 'Search Items', 'bca' ),
			'not_found'                  => __( 'Not Found', 'bca' ),
			'no_terms'                   => __( 'No items', 'bca' ),
			'items_list'                 => __( 'Items list', 'bca' ),
			'items_list_navigation'      => __( 'Items list navigation', 'bca' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => false,
			'show_ui'                    => true,
			'show_admin_column'          => false,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'show_in_rest'               => true,
		);
		register_taxonomy( 'bca_blocks_converter', array( 'post' ), $args );

		wp_insert_term(
			'Classic Editor',   // the term 
			'bca_blocks_converter', // the taxonomy
			array(
				'description' => 'Post using classic editor.',
				'slug'        => 'classic',
			)
		);
		wp_cache_flush();
	}
	add_action( 'init', 'bca_register_block_converter_taxonomy', 0 );
	
}
/**
 * Register a custom menu page.
 */
function bca_add_block_converter_admin_page() {
    add_submenu_page( 
        'tools.php',
        'Classic to Gutenberg Conversion',
        'Classic to Gutenberg',
        'manage_options',
        'bca-block-converter',
        'bca_block_converter_render_index',
    );
}
add_action( 'admin_menu', 'bca_add_block_converter_admin_page' );

/**
 * Enqueue admin styles and scripts.
 */
function bca_block_converter_enqueue_scripts() {
	$asset = include_once BCA_BLOCK_CONVERTER_PATH . '/build/index.asset.php';
	wp_register_script( 'bca-block-converter-script', BCA_BLOCK_CONVERTER_URI . 'build/index.js', array( 'jquery', 'wp-blocks', 'wp-edit-post' ), $asset['version'], true );
	$jsObj = array(
		'ajaxUrl'                      => admin_url( 'admin-ajax.php' ),
		'serverErrorMessage'           => '<div class="error"><p>' . __( 'Server error occured!', 'bca' ) . '</p></div>',
		'scanningMessage'              => '<p>' . sprintf( __( 'Scanning... %s%%', 'bca' ), 0 ) . '</p>',
		'bulkConvertingMessage'        => '<p>' . sprintf( __( 'Converting... %s%%', 'bca' ), 0 ) . '</p>',
		'bulkConvertingSuccessMessage' => '<div class="updated"><p>' . __( 'All posts successfully converted!', 'bca' ) . '</p></div>',
		'confirmConvertAllMessage'     => __( 'You are about to convert all classic posts to blocks. These changes are irreversible. Convert all classic posts to blocks?', 'bca' ),
		'convertingSingleMessage'      => __( 'Converting...', 'bca' ),
		'convertedSingleMessage'       => __( 'Converted', 'bca' ),
        'failedMessage'                => __( 'Failed', 'bca' ),
        'hookSuffix' => $GLOBALS['hook_suffix'],
        'requestURI' => $_SERVER['REQUEST_URI'],
	);
	wp_localize_script( 'bca-block-converter-script', 'bcaConvert', $jsObj );
    wp_enqueue_script( 'bca-block-converter-script' );
    
    wp_enqueue_style( 'bca-block-converter-style', BCA_BLOCK_CONVERTER_URI . 'admin/assets/style.css' );
}
add_action( 'admin_enqueue_scripts', 'bca_block_converter_enqueue_scripts' );
/**
 * Render admin page.
 */
function bca_block_converter_render_index()
{
	require_once BCA_BLOCK_CONVERTER_PATH . 'admin/admin-list.php';
    require_once BCA_BLOCK_CONVERTER_PATH . 'admin/index.php'; 
}

/**
 * Display table with indexed posts.
 */
function bca_render_table() {
    require_once BCA_BLOCK_CONVERTER_PATH . 'admin/admin-list.php';
	?>
	<div class="meta-box-sortables ui-sortable">
	<?php
	$table = new BCA_CONVERTER_LIST();
	
	?>
		<form method="post" id="bca-converter-list">
        <?php
            $table->views();
			$table->prepare_items();
			$table->search_box( __( 'Search', 'bca' ), 'bbconv-search' );
			$table->display();
		?>
		</form>
	</div>
	<?php
}

/**
 * Get translated status label by slug.
 *
 * @param string $status status slug
 *
 * @return string
 */
function bca_status_label( $status ) {
	$status_labels = array(
		'any'     => __( 'All', 'bca' ),
		'publish' => __( 'Published', 'bca' ),
		'future'  => __( 'Future', 'bca' ),
		'draft'   => __( 'Drafts', 'bca' ),
		'private' => __( 'Private', 'bca' ),
	);

	if ( array_key_exists( $status, $status_labels ) ) {
		return $status_labels[ $status ];
	}
	return $status;
}


add_action( 'rest_api_init', function () {
    register_rest_route( 'bca-blocks-converter/v1', '/single/convert/', array(
        'methods' => 'GET',
        'callback' => 'bca_convert_single',
    ) );
} );

add_action( 'rest_api_init', function () {
    register_rest_route( 'bca-blocks-converter/v1', '/single/update/', array(
        'methods' => 'POST',
        'callback' => 'bca_save_single',
    ) );
} );
add_action( 'rest_api_init', function () {
    register_rest_route( 'bca-blocks-converter/v1', '/bulk/convert/', array(
        'methods' => 'GET',
        'callback' => 'bca_convert_bulk',
    ) );
} );

add_action( 'rest_api_init', function () {
    register_rest_route( 'bca-blocks-converter/v1', '/bulk/update/', array(
        'methods' => 'POST',
        'callback' => 'bca_save_bulk',
    ) );
} );
add_action( 'rest_api_init', function () {
    register_rest_route( 'bca-blocks-converter/v1', '/scan/', array(
        'methods' => 'GET',
        'callback' => 'bca_scan_posts',
    ) );
} );

/**
 * Find content created in Classic editor
 *
 * @param string $content the content of a post
 *
 * @return bool
 */
function bca_is_classic_editor( $content )
{
	if ( ! empty( $content ) && strpos( $content, '<!-- wp:' ) === false ) {
		return true;
	}
	return false;
}

function bca_scan_posts() {

    if(!isset($GLOBALS['hook_suffix'])){
        //$GLOBALS['hook_suffix'] = $_GET['hook_suffix']; //admin_head-{$hook_suffix}
    }

    /** Load WordPress Administration APIs */
    // require_once( ABSPATH . 'wp-admin/includes/admin.php' );


    // require_once BCA_BLOCK_CONVERTER_PATH . 'admin/admin-list.php';
    
	
	$offset         = intval( $_GET['offset'] );
	$total_expected = intval( $_GET['total'] );

	$total_actual  = 0;
    $post_types    = unserialize( BCA_BLOCK_CONVERTER_TYPES );
    $post_statuses = unserialize( BCA_BLOCK_CONVERTER_STATUSES );
    
	foreach ( $post_types as $type ) {
		$post_statuses = wp_count_posts( $type );
		foreach ( $post_statuses as $status ) {
			$total_actual += (int) $status;
        }
    }

	$json = array(
		'error'   => false,
		'offset'  => $total_actual,
		'total'   => $total_actual,
        'message' => '',
	);

	if ( $total_expected != -1 && $total_expected != $total_actual ) {
		$json['error']   = true;
		$json['message'] = '<div class="error"><p>' . __( 'An error occurred while scanning! Someone added or deleted one or more posts during the scanning process. Try again.', 'bca' ) . '</p></div>';
		return $json;
    }

	$args = array(
		'post_type'      => $post_types,
		'post_status'    => array_keys(get_object_vars($post_statuses)),
		'posts_per_page' => BCA_BLOCK_CONVERTER_PER_PAGE,
		'offset'         => $offset,
	);
	$posts_array = get_posts( $args );

	foreach ( $posts_array as $post ) {

		if ( bca_is_classic_editor( $post->post_content ) ) {
			// update_post_meta( $post->ID, 'bca_is_classic_editor', 1 );
			wp_set_object_terms( $post->ID, 'classic', 'bca_blocks_converter');
            // UPDATE `1Wnat4_postmeta` SET meta_value = '' WHERE meta_key = 'bca_is_classic_editor'
		}
		$offset++;
    }
    
    // $table = new BCA_CONVERTER_LIST();
    // $json['list'] = $table->ajax_response();
    
    $percentage       = (int) ( $offset / $total_actual * 100 );
    $json['offset']   = $offset;
	$json['percentage']   = $percentage;
	$message = ($percentage == 100) ? 'Complete' : 'Scanning';
	$json['message'] .= '<p>' . sprintf( __( '%s... %s%%', 'bca' ), $message, $percentage ) . '</p>';

	return $json;
}

function bca_convert_bulk( $data )
{

	$json  = array();

	if ( ! empty( $_GET['total'] ) ) {
		$offset         = intval( $_GET['offset'] );
		$total_expected = intval( $_GET['total'] );

		$post_types    = unserialize( BCA_BLOCK_CONVERTER_TYPES );
		$post_statuses = unserialize( BCA_BLOCK_CONVERTER_STATUSES );

		// $total_actual = (int) bca_block_converter_count_posts();  //bca_get_count( $post_types );
		$total_actual = 0;
		$bca_block_converter_count_posts = bca_block_converter_count_posts();
		foreach ( $bca_block_converter_count_posts as $status ) {
			$total_actual += (int) $status;
        }
		if ( $total_expected == -1 ) {
			$total_expected = $total_actual;
		}

		$json = array(
			'error'     => false,
			'offset'    => $total_expected,
			'total'     => $total_expected,
			'message'   => '',
			'postsData' => array(),
		);

		if ( $total_expected != ( $total_actual + $offset ) ) {
			$json['error']   = true;
			$json['message'] = '<div class="error"><p>' . __( 'An error occurred while bulk converting! Someone added or deleted one or more posts during the converting process. Try again.', 'bca' ) . '</p></div>';
			return $json;
		}

		$args        = array(
			'post_type'      => $post_types,
			'post_status'    => $post_statuses,
			'posts_per_page' => BCA_BLOCK_CONVERTER_PER_PAGE,
			'tax_query'		 => array(
				array(
					'taxonomy' => 'bca_blocks_converter',
					'field'	   => 'slug',
					'terms'    => 'classic'
				)
			),
			// 'meta_key'       => 'bca_is_classic_editor',
			// 'meta_value'     => 1,
		);
		$posts_array = get_posts( $args );

		$posts_data = array();
		foreach ( $posts_array as $key => $post ) {
			$posts_data[$key] = array(
				'id'      => $post->ID,
				'content' => wpautop( $post->post_content ),
			);
			
			if(wpdocs_detect_shortcode( $post->post_content, 'gallery' ) !== false){
				$posts_data[$key]['gallery'] = wpdocs_detect_shortcode( $post->post_content, 'gallery' );
			}
			$offset++;
		}
		$json['postsData'] = $posts_data;

		$json['offset']  = $offset;
		$percentage      = (int) ( $offset / $total_expected * 100 );
		$json['percentage']  = $percentage;
		$json['message'] = '<p>' . sprintf( __( 'Converting... %s%%', 'bca' ), $percentage ) . '</p>';

		return $json;
	}

}
/**
 * Detect shortcodes in the global $post.
 */
function wpdocs_detect_shortcode( $post_content, $search ) {
    global $post;
    $pattern = get_shortcode_regex();
	$gallery_src = array();
    if (   preg_match_all( '/'. $pattern .'/s', $post_content, $matches )
        && array_key_exists( 2, $matches )
        && in_array( $search, $matches[2] )
    ) {
		// shortcode is being used
		
		foreach ($matches[0] as $key => $match) {
			preg_match('/ids=\"(.*?)\"/',$match,$ids);
			if (isset($ids[1])) {
				$ids = explode(',',$ids[1]);
				foreach ($ids as $key => $id) {
					$gallery_src[$id]['url'] = wp_get_attachment_url( $id );
					$gallery_src[$id]['alt'] = get_post_meta($id, '_wp_attachment_image_alt', TRUE);
					$gallery_src[$id]['caption'] = wp_get_attachment_caption( $id );
				}
			}
		}

		return $gallery_src;
    }else{
		return false;
	}
}
/**
 * Sort the number of posts by type and create labeled array.
 *
 * @return array
 */
function bca_count_indexed() {
	$post_types = unserialize( BCA_BLOCK_CONVERTER_TYPES );

	$indexed = array();
	foreach ( $post_types as $type ) {
		$post_type_obj     = get_post_type_object( $type );
		$label             = $post_type_obj->labels->name;
		$indexed[ $label ] = wp_count_posts( $type );
	}

	return $indexed;
}
function bca_block_converter_count_posts( $status = '' )
{
	global $wpdb;

	$term = get_term_by( 'slug', 'classic', 'bca_blocks_converter');
	$status = ( $status == 'any' || $status == 'all' ) ? '' : $status;
	$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts}\n";
	$query .= "LEFT JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)\n";
	$query .= " WHERE ( {$wpdb->term_relationships}.term_taxonomy_id IN ($term->term_id) ) AND {$wpdb->posts}.post_type = %s";
	$query .= ($status !== '' ) ? " AND {$wpdb->posts}.post_status = '$status'\n" : "\n";
	$query .= " GROUP BY {$wpdb->posts}.post_status";
	
	$results = (array) $wpdb->get_results( $wpdb->prepare( $query, 'post' ), ARRAY_A );
    $counts  = array_fill_keys( get_post_stati(), 0 );
 
    foreach ( $results as $row ) {
        $counts[ $row['post_status'] ] = $row['num_posts'];
    }
 
	return $counts;
}
/**
 * Count indexed posts by type.
 *
 * @param string/array $type post type/types
 *
 * @return int
 */
function bca_get_count( $type ) {
	$args = array(
		'posts_per_page' => -1,
		'post_type'      => $type,
		'meta_key'       => 'bca_is_classic_editor',
		'meta_value'     => 1,
	);

	$posts_query = new WP_Query( $args );
	return $posts_query->post_count;
}
function bca_save_bulk( WP_REST_Request $request )
{
	$json  = array();
	
	if ( ! empty( $request['total'] ) ) {
		$json = array(
			'error'  => false,
			'offset' => intval( $request['offset'] ),
			'total'  => intval( $request['total'] ),
		);
		foreach ( $request['postsData'] as $post ) {
			$post_data = array(
				'ID'           => $post['id'],
				'post_content' => $post['content'],
			);
			if ( ! wp_update_post( $post_data ) ) {
				$json['error'] = true;
				return $json;
			}
		}
		return $json;
	}
}

function bca_convert_single( $data )
{

	if ( ! empty( $data['id'] ) ) {
		$post_id = intval( $data['id'] );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			$json['error'] = true;
			return json_encode( $json );
		} else {
			$json['id'] = $data['id'];
			$json['content'] = wpautop( $post->post_content );
			if(wpdocs_detect_shortcode( $post->post_content, 'gallery' ) !== false){
				$json['gallery'] = wpdocs_detect_shortcode( $post->post_content, 'gallery' );
			}
            return $json;
		}
	}
}

function bca_save_single( WP_REST_Request $request )
{
    $post_id = $request['post_id'];
    $post_content = $request['post_content'];

    if(!$post_id){
        $json['error'] = 'Post ID is missing, invalid Post.';
        return $json;
    }

    $post_id   = intval( $post_id );
    $post_data = array(
        'ID'           => $post_id,
        'post_content' => $post_content,
    );

    $json['message'] = $post_id;

    if ( ! wp_update_post( $post_data ) ) {
        $json['error'] = true;
        return $json;
    } else {
        $json['message'] = $post_id;
        return $json;
    }    

}

/**
 * Automatically index posts on creation or updating.
 */

add_action( 'post_updated', 'bca_index_after_save', 10, 2 );
function bca_index_after_save( $post_ID, $post_after ) {
	if ( bca_is_classic_editor( $post_after->post_content ) ) {
		//error_log(print_r('IS classic',true));
		wp_set_object_terms( $post_ID, 'classic', 'bca_blocks_converter');
		// update_post_meta( $post_ID, 'bca_is_classic_editor', 1 );
	} else {
		//error_log(print_r('IS Block',true));
		wp_remove_object_terms( $post_ID, 'classic', 'bca_blocks_converter');
		//delete_post_meta( $post_ID, 'bca_is_classic_editor' );
	}
}