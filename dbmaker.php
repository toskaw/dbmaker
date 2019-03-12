<?php
/*
Plugin Name: DBMaker
Plugin URI: https://dstation.suehiro-kugahara.tokyo/product/dbmaker/
Description: Make data base from csv 
Version: 1.0
Author: Toshiyuki Kawashima
Author URI: https://dstation.suehiro-kugahara.tokyo/
License: GPL2
*/

/*  Copyright 2019 toshiyuki kawashima (email : d.s_kugahara@kf6.so-net.ne.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die("you do not have access to this page!");
require "class-option.php";
require "class-csvimport.php";
require "lib/shortcode.php";

add_action('init','DBM_init');
function DBM_init() {
	register_post_type('csv', array(
		'labels' => array(
			'name' => __('DataBase Maker'),
			'singular_name' => __('DataBase Setting'),
			'add_new_item' => __('新規DB追加')),
		'public' => true,
		'show_ui' => true,
		'supports' => array( 'title', 'editor' ),
		'menu_position' => 65,
	));

	$types = DBM_Csv_option::get_posttype_list();
	foreach ($types as $type) {
		$option = DBM_Csv_option::getInstance($type);
		// カスタム投稿タイプ登録
		register_post_type($type, array(
		'labels' => array(
			'name' => __(get_the_title($option->id())),
			'singular_name' => __(get_the_title($option->id()))),
			'public' => $option->public(),
			'show_ui' => true,
			'show_in_rest' => $option->public(),
			'supports' => array( 'title', 'custom-fields', 'editor' ),
			'menu_position' => 65,
		));
		// カスタム分類登録
		$taxonomys = $option->get_taxonomys();
		foreach ($taxonomys as $item) {
			register_taxonomy($item, array('post', $type), array(
				'label' => $item,
				'rewrite' => true,
				'public' => true,
				'hierarchical' => true,
			));
			register_taxonomy_for_object_type($item, $type);
		}
		// sortableカラム登録
		add_filter("manage_edit-{$type}_sortable_columns", "DBM_sortable_columns");
	}

	DBM_init_sessions();
}

function DBM_init_sessions() {
	if (!session_id()) {
		session_start();
	}
}
add_action('manage_posts_extra_tablenav', 'DBM_add_button' );
function DBM_add_button($which)
{
	global $post_type;
	$option = DBM_Csv_option::getInstance($post_type);

	if ( 'top' === $which && ($option->post_type())) {
?>
	<br class="clear">
	<div class="alignleft">
	<input type="file" id="csvfile" name="import_file" size="25">
	<button type="button"  class="button-primary ajax inline" name="action" value="import">csv読み込み</button>
	<button type="button"  class="button-primary ajax inline" name="action" value="delete_all">全消去</button>
	</div>
	<div id="inline" style="display:none;width:100%">
		<span id="progress_box"></span>
	</div>
<?php
	}
}

// フックする関数
function DBM_admin_enqueue_scripts($hook_suffix) {
	// edit.phpのみ
	if( 'edit.php' == $hook_suffix ) {
		// Register the script first.
		wp_register_script( 'DBM_ajax_js', plugins_url('js/dbm_ajax.js', __FILE__) );
		$screen = get_current_screen();
		// 引数作成
		wp_localize_script('DBM_ajax_js', 'PARAM', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'delete_all' => 'DBM_delete_all',
			'import_csv' => 'DBM_import_csv',
			'post_type' => $screen->post_type,
			'nonce' => wp_create_nonce('DBM_delete_all'),
		));
		// 読み込むスクリプトファイル(※依存関係:jquery)
		wp_enqueue_style('modaal_css','//cdn.jsdelivr.net/npm/modaal@0.4.4/dist/css/modaal.min.css');
		wp_enqueue_script('modaal_js', '//cdn.jsdelivr.net/npm/modaal@0.4.4/dist/js/modaal.min.js');
		wp_enqueue_script('DBM_ajax_js', plugins_url('js/dbm_ajax.js', __FILE__), array('jquery'), null, true);
	}
}
// "custom_enqueue" 関数を管理画面のキューアクションにフック
add_action( 'admin_enqueue_scripts', 'DBM_admin_enqueue_scripts' );

function DBM_enqueue_scripts() {

	if ('csv' == get_post_type()) {
		wp_register_script( 'DBM_ajax_search', plugins_url('js/dbm_ajax_search.js', __FILE__) );
		// 引数作成
		wp_localize_script('DBM_ajax_search', 'PARAM', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'search' => 'DBM_search',
			'nonce' => wp_create_nonce('DBM_search'),
		));

		wp_enqueue_script('knockout', plugins_url('js/knockout-min.js', __FILE__));
		wp_enqueue_script('DBM_ajax_search', plugins_url('js/dbm_ajax_search.js', __FILE__), array('jquery'), null, true);
	}
}
add_action( 'wp_enqueue_scripts', 'DBM_enqueue_scripts' );

function DBM_delete_all() {
	global $wpdb;
	if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'DBM_delete_all')) {
		$post_type = $_POST['post_type'];
		$counts = wp_count_posts($post_type);
		$remains = $counts->publish + $counts->draft + $counts->private + $counts->future + $counts->pending;
		if (isset($_REQUEST['first']) && $_REQUEST['first'] == 'true') {
			$_SESSION['total'] = $remains;
			if ($remains == 0) {
?>
				<span id='progress'>100</span>
<?php
				die();
			}
		}
		
		$options = array(
			'post_type' => $post_type,
			'cache_results'          => false, // don't cache results
			'update_post_meta_cache' => false, // No need to fetch post meta fields
			'update_post_term_cache' => false, // No need to fetch taxonomy fields
			'no_found_rows'          => true,  // No need for pagination
			'fields'                 => 'ids', // retrieve only ids
		);
		if ($remains > 1000) {
			$options['showposts'] = 1000;
		}
		else {
			$options['nopaging'] = 'true';
		}

		$wpdb->query( 'SET autocommit = 0;' );
		register_shutdown_function( function(){
			$GLOBALS['wpdb']->query( 'COMMIT;' );
		} );

		$wp_query = new WP_Query();
		$posts = $wp_query->query($options);
		foreach ($posts as $post_id) {
			wp_delete_post($post_id, true);
		}
		$progress = 100 - floor(($remains - count($posts)) / $_SESSION['total'] * 100);

		$wpdb->query( 'COMMIT;' );

		if ($progress == 100) {
			unset($_SESSION['total']);
		}
?>
<span id='progress'><?php echo $progress; ?></span>
<?php
	}
	else {
		status_header('403');
		echo 'Forbidden';
	}
	die();
}

add_action('wp_ajax_DBM_delete_all', 'DBM_delete_all');

function DBM_import_csv() {
	global $wpdb;
	if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'DBM_delete_all')) {
		// 設定値取得
		$options = DBM_Csv_option::getInstance($_POST['post_type']);
		if (isset($_REQUEST['first']) && $_REQUEST['first'] == 'true') {
			// 一時アップロード先ファイルパス
			if (is_uploaded_file($_FILES['import_file']['tmp_name'])){
				$upload_dir = wp_upload_dir();
				$_SESSION['file'] = $filePath = $upload_dir['path'] . '/' . $_FILES["import_file"]["name"];
				if (move_uploaded_file($_FILES['import_file']['tmp_name'], $filePath)) {
					$count = exec('wc -l ' . $filePath);
					$count = trim(str_replace($filePath, '', $count));
					$_SESSION['total'] = $count;
					$_SESSION['start'] = 0;
					if ($options->ignore_first_line()) {
						$_SESSION['start'] = 1;
					}
				} else {
					status_header('500');
					echo 'upload error';
					die();
				}
			} else {
				status_header('400');
				echo 'upload error';
				die();
			}
		}
		else {
			$filePath = $_SESSION['file'];
		}
		$file = new DBM_Csv_import($filePath);
		$file->set_input_charset($options->charcode());
		if ($_SESSION['start'] != 0) {
			$file->seek($_SESSION['start']);
		}
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		$wpdb->query( 'SET autocommit = 0;' );
		register_shutdown_function( function(){
			$GLOBALS['wpdb']->query( 'COMMIT;' );
		} );

		for ($i = 0; $i < 1000; $i++) {
			if (!$file->eof()) {
				$data = $file->readline();
				$post = array();
				$meta = array();
				$tax = array();
				$post['post_type'] = $_POST['post_type'];
				$post['post_status'] = $options->status();
				foreach ($data as $index => $item) {
					$key = $options->format($index);
					// (string) post slug
					if ($key == 'post_name') {
						if ($item) {
							$post['post_name'] = $item;
						}
					}
					// (login or ID) post_author
					else if ($key == 'post_author') {
						if (is_numeric($item)) {
							$user = get_user_by('id', $item);
						} else {
							$user = get_user_by('login', $item);
						}
						if (isset($user) && is_object($user)) {
							$post['post_author'] = $user->ID;
							unset($user);
						}
					}
					// (string) publish date
					else if ($key == 'post_date') {
						if ($item) {
							$post['post_date'] = date("Y-m-d H:i:s", strtotime($item));
						}
					}
					else if ($key == 'post_date_gmt') {
						if ($item) {
							$post['post_date_gmt'] = date("Y-m-d H:i:s", strtotime($item));
						}
					}
					// (string) post status
					else if ($key == 'post_status') {
						if ($item) {
							$post['post_status'] = $item;
						}
					}
					// (string) post password
					else if ($key == 'post_password') {
						if ($item) {
							$post['post_password'] = $item;
						}
					}
					// (string) post title
					else if ($key == 'post_title') {
						if ($item) {
							$post['post_title'] = $item;
						}
					}
					// (string) post content
					else if ($key == 'post_content') {
						if ($item) {
							$post['post_content'] = $item;
						}
					}
					// (string) post excerpt
					if ($key == 'post_excerpt') {
						if ($item) {
							$post['post_excerpt'] = $item;
						}
					}
					// (int) post parent
					else if ($key == 'post_parent') {
						if ($item) {
							$post['post_parent'] = $item;
						}
					}
					// (int) menu order
					else if ($key == 'menu_order') {
						if ($item) {
							$post['menu_order'] = $item;
						}
					}
					// (string) comment status
					else if ($key == 'comment_status') {
						if ($item) {
							$post['comment_status'] = $item;
						}
					}
					// (string, comma separated) slug of post categories
					else if ($key == 'post_category') {
						if ($item) {
							$categories = preg_split("/,+/", $item);
							if ($categories) {
								$post['post_category'] = wp_create_categories($item);
							}
						}
					}
					// (string, comma separated) name of post tags
					else if ($key == 'post_tags') {
						if ($item) {
							$post['post_tags'] = $item;
						}
					}
					// add any other data to post meta
					// check if meta is custom taxonomy
					else if (substr($key, 0, 4) == 'tax_') {
						// (string, comma divided) name of custom taxonomies 
						$customtaxes = preg_split("/,+/", $item);
						$taxname = substr($key, 4);
						$tax[$taxname] = array();
						foreach($customtaxes as $key => $value ) {
							$tax[$taxname][] = $value;
						}
					}
					else if ($key == '') {
					}
					else {
						$meta[$key] = $item;
					}
				}
				// Separate the post tags from $post array
				if (isset($post['post_tags']) && !empty($post['post_tags'])) {
					$post_tags = $post['post_tags'];
					unset($post['post_tags']);
				}
				// Add the post
				$post_id = wp_insert_post($post, true);
				// Set post tags
				if (isset($post_tags)) {
					wp_set_post_tags($post_id, $post_tags);
				}
				// Set meta data
				foreach ($meta as $key => $value) {
					update_post_meta($post_id, $key, $value);
				}
				// Set terms
				foreach ($tax as $key => $value) {
					wp_set_object_terms($post_id, $value, $key);
				}
			}
		}
		$wpdb->query( 'COMMIT;' );
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		$next = $file->key();
		if ($next >= $_SESSION['total']) {
			$progress = 100;
			// file delete
			unlink($filePath);
		}
		else {
			$progress = floor($next  * 100 / $_SESSION['total']);
		}
?>
<span id='progress'><?php echo $progress; ?></span>
<?php
		$_SESSION['start'] = $next;
	}
	else {
		status_header('403');
		echo 'Forbidden';
	}
	die();
}
add_action('wp_ajax_DBM_import_csv', 'DBM_import_csv');

add_action( 'wp_ajax_DBM_search', 'DBM_search');
add_action( 'wp_ajax_nopriv_DBM_search', 'DBM_search');
function DBM_search() {
	if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'DBM_search')) {
		if (isset($_REQUEST['post_type'])) {
			$post_type = $_REQUEST['post_type'];
			$types = DBM_Csv_option::get_posttype_list();
			if (in_array($post_type, $types)) {
				$options = DBM_Csv_option::getInstance($post_type);
				$param_list = array_filter($options->format_array(), 'strlen');
				$args = array();
				$args['post_type'] = $post_type;
				$args['posts_per_page'] = 5;
				$args['tax_query'] = array();
				$set_tax = false;
				$args['meta_query'] = array();
				$set_meta = false;
				if (isset($_REQUEST['s'])) {
					// keyword
					$args['s'] = $_REQUEST['s'];
				}
				foreach ($param_list as $key) {
					if (isset($_REQUEST[$key])) {
						$item = $_REQUEST[$key];
						if ($key == 'post_name') {
							$args['name'] = $item;
						}
						else if ($key == 'post_author') {
							if (is_numeric($item)) {
								$user = get_user_by('id', $item);
							} else {
								$user = get_user_by('login', $item);
							}
							if (isset($user) && is_object($user)) {
								$args['author'] = $user->ID;
								unset($user);
							}
						}
						else if ($key == 'post_date') {
							$args['year'] = date("Y", strtotime($item));
							$args['monthnum'] = date("m", strtotime($item));
							$args['day'] = date("d", strtotime($item));
							$args['hour'] = date("H", strtotime($item));
							$args['minute'] = date("i", strtotime($item));
							$args['second'] = date("s", strtotime($item));
						}
						else if ($key == 'post_status') {
							$args['post_status'] = $item;
						}
						else if ($key == 'post_title') {
							$args['s'] .= ' ' . $item;
						}
						else if ($key == 'post_parent') {
							$args['post_parent'] = $item;
						}
						else if ($key == 'post_category') {
							$args['cat'] = imploade(",", $item);
						}
						else if ($key == 'post_tags') {
							$args['tag__in'] = $item;
						}
						else if (substr($key, 0, 4) == 'tax_') {
							if (!empty($item[0])) {
								$taxname = substr($key, 4);
								$args['tax_query'][] = array(
									'taxonomy' => $taxname,
									'field'    => 'slug',
									'terms'    => $item,
								);
								$set_tax = true;
							}
						}
						else if ($key == '') {
						}
						else {
							// meta query
							$args['meta_query'][] = array(
								'key' => $key,
								'value' => $item,
								'compare' => 'LIKE',
							);
							$set_meta = true;
						}
					}
				}
				if ($set_tax == false) {
					unset($args['tax_query']);
				}
				if ($set_meta == false) {
					unset($args['meta_query']);
				}

				$query = new WP_Query($args);
				// The Loop
				if ( $query->have_posts() ) {
					$objects = array();
					while ( $query->have_posts() ) {
						$object = array();
						$query->the_post();
						$post = get_post();
						foreach ($param_list as $key) {
							if ($key == 'post_name') {
								$object[$key] = $post->post_name;
							}
							else if ($key == 'post_author') {
								$object[$key] = get_the_author();
							}
							else if ($key == 'post_date') {
								$object[$key] = the_date('Y-m-d', '', '', FALSE);
							}
							else if ($key == 'post_status') {
								$object[$key] = $post->post_status;
							}
							else if ($key == 'post_title') {
								$object[$key] = the_title('','',FALSE);
							}
							else if ($key == 'post_parent') {
								$object[$key] = $post->post_parent;
							}
							else if ($key == 'post_category') {
								$cat = get_the_category();
								$object[$key] = $cat;
							}
							else if ($key == 'post_tags') {
								$object[$key] = get_the_tags();
							}
							else if (substr($key, 0, 4) == 'tax_') {
								$taxname = substr($key, 4);
								$object[$key] = get_the_terms($post->ID, $taxname);
							}
							else if ($key == '') {
							}
							else {
								$object[$key] = get_post_meta($post->ID, $key, true);
							}
						}
						$objects[] = $object;
					}
				}
				$response = array(
					'success'   => true,
					'data'      => $objects,
				);
				wp_send_json( $response );
			}
		}
	}
	die();
}


// 固定カスタムフィールドボックス
function DBM_add_fields() {
	add_meta_box( 'csv_settings', 'csv設定', 'DBM_insert_csv_fields', 'csv', 'normal', 'high');
}
add_action('add_meta_boxes_csv', 'DBM_add_fields');
 
// csv設定入力エリア
function DBM_insert_csv_fields() {
	global $post;
	// nonceフィールドを追加して後でチェックする
	wp_nonce_field( 'DBM_save_csv_fields', 'DBM_meta_box_nonce' );

	echo '<strong>投稿タイプ</strong>:投稿タイプ名を指定する<br/> <input type="text" name="save_post_type" value="'.get_post_meta($post->ID, 'save_post_type', true).'" size="50" /><br>';
	echo '<strong>フォーマット</strong>：csvファイルの項目名をカンマ区切りで入力する </br> <input type="text" name="format" value="'.get_post_meta($post->ID, 'format', true).'" size="50" /><br>';
	echo '<strong>ステータス</strong>： インポート時の初期ステータス<br/><select name="status">';
	$list = array(
		'publish', 'draft', 'pending', 'private'
	);
	$status = get_post_meta($post->ID, 'status', true);
	foreach ($list as $item) {
		if ($item == $status) {
			echo "<option value='$item' selected>$item</option>";
		}
		else {
			echo "<option value='$item'>$item</option>";
		}
	}
	echo '</select><br/>';
	$list = mb_list_encodings();
	$char_code = get_post_meta($post->ID, 'char_code', true);
	echo '<strong>文字コード</strong>： csvファイルの文字コードを指定する<br/><select name="char_code">';
	foreach ($list as $code) {
		if ($code == $char_code) {
			echo "<option value='$code' selected>$code</option>";
		}
		else {
			echo "<option value='$code'>$code</option>";
		}
	}
	echo '</select><br/>';
	if( get_post_meta($post->ID,'ignore_firstline',true) == "1" ) {
		$csv_label_check = "checked";
	}//チェックされていたらcheckedを挿入
	echo '<strong>1行目をスキップ</strong>： <input type="checkbox" name="ignore_firstline" value="1" '.$csv_label_check.' ><br/>';
	if( get_post_meta($post->ID,'public_post_type',true) == "1" ) {
		$csv_label_check = "checked";
	}//チェックされていたらcheckedを挿入
	echo '<strong>データを公開する</strong>： <input type="checkbox" name="public_post_type" value="1" '.$csv_label_check.' ><br/>';
}
 

// カスタムフィールドの値を保存
function DBM_save_csv_fields( $post_id , $post, $update) {
	$csv_post_field = array(
		'save_post_type', 
		'format',
		'status',
		'char_code',
		'ignore_firstline',
		'public_post_type',
		'dbm_search_form');
	// nonceがセットされているかどうか確認
	if ( ! isset( $_POST['DBM_meta_box_nonce'] ) ) {
		return;
	}

	// nonceが正しいかどうか検証
	if ( ! wp_verify_nonce( $_POST['DBM_meta_box_nonce'], 'DBM_save_csv_fields' ) ) {
		return;
	}

	// 自動保存の場合はなにもしない
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	foreach ($csv_post_field as $field) {
		if(!empty($_POST[$field])){ 
			update_post_meta($post_id, $field, $_POST[$field] );
		} else { //未入力の場合
			delete_post_meta($post_id, $field);
		}
	}

	// contentが空の場合、デフォルトフォームで更新
	if (empty($post->post_content) && !empty($_POST['save_post_type'])) {
		$post->post_content = DBM_default_search_form($_POST['save_post_type']);
		// 無限ループ回避のため、フックを削除
		remove_action('save_post_csv', 'DBM_save_csv_fields');
		wp_update_post($post);
		// 改めてフック
		add_action('save_post_csv', 'DBM_save_csv_fields', 10, 3);
	}
}
add_action('save_post_csv', 'DBM_save_csv_fields', 10, 3);

$ignores = array(
	'post_name',
	'post_author',
	'post_date',
	'post_date_gmt',
	'post_status',
	'post_password',
	'post_title',
	'post_content',
	'post_excerpt',
	'post_parent',
	'menu_order',
	'comment_status',
	'post_category',
	'post_tags'
);

function DBM_add_columns($columns, $post_type) {
	global $ignores;
	$types = DBM_Csv_option::get_posttype_list();
	if (in_array($post_type, $types)) {
		$options = DBM_Csv_option::getInstance($post_type);
		$param_list = array_filter($options->format_array(), 'strlen');
		foreach ($param_list as $param) {
			if (!in_array($param, $ignores)) {
				$columns[$param] = $param;
			}
		}
	}
	return $columns;
}

add_filter( 'manage_posts_columns', 'DBM_add_columns', 10, 2);

function DBM_custom_column($column, $post_id) {
	global $ignores;
	$post = get_post($post_id);
	$types = DBM_Csv_option::get_posttype_list();
	$post_type = $post->post_type;

	if (in_array($post_type, $types)) {
		$options = DBM_Csv_option::getInstance($post_type);
		$param_list = array_filter($options->format_array(), 'strlen');
		if (in_array($column, $param_list) && !in_array($column, $ignores)) {
			if (substr($column, 0, 4) == 'tax_') {
				$terms = wp_get_object_terms($post_id, substr($column, 4));
				if (!empty($terms) && !is_wp_error($terms)) {
					$sp = '';
					foreach ($terms as $term) {
						echo $sp . $term->name;
						$sp = ',';
					}
				}
			}
			else {
				$value = get_post_meta($post_id, $column, true);
				echo $value;
			}
		}
	}
}
add_action( 'manage_posts_custom_column', 'DBM_custom_column', 10, 2);

function DBM_sortable_columns($columns) {
	global $ignores;
	$screen = get_current_screen();
	$post_type = $screen->post_type;
	$types = DBM_Csv_option::get_posttype_list();
	if (in_array($post_type, $types)) {
		$options = DBM_Csv_option::getInstance($post_type);
		$param_list = array_filter($options->format_array(), 'strlen');
		foreach ($param_list as $param) {
			if (!in_array($param, $ignores) && 'tax_' != substr($param, 0, 4)) {
				$columns[$param] = $param;
			}
		}
	}
	
	return $columns;
}

function DBM_posts_search_custom_fields( $orig_search, $query ) {
	$q = $query->query_vars;
	if (isset($q['post_type'])) {
		$post_type = $q['post_type'];
	}
	if ( $query->is_search() && is_main_query() && !empty($post_type) && in_array($post_type, DBM_Csv_option::get_posttype_list()) ) {
		global $wpdb;
		$q = $query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';
		$searchand = '';
		$options = DBM_Csv_option::getInstance($post_type);
		$taxonomys = $options->get_taxonomys();

		if ($q['search_terms']) {
		    foreach ( $q['search_terms'] as $term ) {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
				$like = $n . $wpdb->esc_like( $term ) . $n;
				// カスタムフィールド用の検索条件を追加
				if (empty($texonomys)) {
					$search .= $wpdb->prepare( "{$searchand}(($wpdb->posts.post_title $like_op %s) $andor_op ($wpdb->posts.post_content $like_op %s) $andor_op (custom.meta_value $like_op %s)) ", $like, $like, $like);
				} else {
					$search .= $wpdb->prepare( "{$searchand}(($wpdb->posts.post_title $like_op %s) $andor_op ($wpdb->posts.post_content $like_op %s) $andor_op (custom.meta_value $like_op %s) $andor_op (t.name $like_op %s)) ", $like, $like, $like, $like);
				}
				$searchand = ' AND ';
		    }
		}
		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() )
				$search .= " AND ($wpdb->posts.post_password = '') ";
		}
		return $search;
	}
	else {
		return $orig_search;
	}
}
add_filter( 'posts_search', 'DBM_posts_search_custom_fields', 10, 2 );

/**
 * カスタムフィールド検索用のJOIN
 */
function DBM_posts_join_custom_fields( $join, $query ) {
	$q = $query->query_vars;
	if (isset($q['post_type'])) {
		$post_type = $q['post_type'];
	}

	if ( $query->is_search() && is_main_query() && !empty($post_type) && in_array($post_type, DBM_Csv_option::get_posttype_list()) ) {
		global $wpdb;
		$join .= " INNER JOIN ( ";
		$join .= " SELECT post_id, group_concat( meta_value separator ' ') AS meta_value FROM $wpdb->postmeta ";
		$join .= " GROUP BY post_id ";
		$join .= " ) AS custom ON ($wpdb->posts.ID = custom.post_id) ";

		$options = DBM_Csv_option::getInstance($post_type);
		$taxonomys = $options->get_taxonomys();
		if (!empty($taxonomys)) {
			// term
			//$join .= "LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id";
		}
	}
	return $join;
}
add_filter( 'posts_join', 'DBM_posts_join_custom_fields', 10, 2 ); 

function DBM_custom_orderby_columns($vars) {
	global $ignores;
	if ( !is_admin() ) return $vars;
	$screen = get_current_screen();
	$post_type = $screen->post_type;
	$types = DBM_Csv_option::get_posttype_list();

	if (in_array($post_type, $types)) {
		$options = DBM_Csv_option::getInstance($post_type);
		$param_list = array_filter($options->format_array(), 'strlen');
		if (isset($vars['orderby'])) { 
			$column = $vars['orderby'];
			if (in_array($column, $param_list) && !in_array($column, $ignores)) {
				if (substr($column, 0, 4) == 'tax_') {
				}
				else {
					$vars = array_merge($vars, array(
							'meta_key' => $column, 
							'orderby' => 'meta_value'
					));
				}
			}
		}
	}
	return $vars;
}
add_filter('request', 'DBM_custom_orderby_columns');

add_action( 'restrict_manage_posts', 'DBM_custom_taxonomies_term_filter' );
function DBM_custom_taxonomies_term_filter() {
	$screen = get_current_screen();
	$post_type = $screen->post_type;
	$types = DBM_Csv_option::get_posttype_list();
	if (in_array($post_type, $types)) {
		$options = DBM_Csv_option::getInstance($post_type);
		$taxonomys = $options->get_taxonomys();
		foreach ($taxonomys as $taxonomy) {
			wp_dropdown_categories( array(
				'show_option_all' => $taxonomy,
				'orderby' => 'name',
				'selected' => get_query_var( $taxonomy ),
				'hide_empty' => 0,
				'name' => $taxonomy,
				'taxonomy' => $taxonomy,
				'value_field' => 'slug',));
		}
	}
}

function DBM_default_search_form($post_type) {
	ob_start();
	$types = DBM_Csv_option::get_posttype_list();
	if (in_array($post_type, $types)) {
		echo "[DBM_search post_type='" . $post_type . "']\n";
		echo "[DBM_textbox]\n";
		$options = DBM_Csv_option::getInstance($post_type);
		$taxonomys = $options->get_taxonomys();
		
		foreach ($taxonomys as $taxonomy) {
			echo "[DBM_tax_select name='" . $taxonomy ."' ]\n";
		}
		echo "[/DBM_search]\n";
		echo "<div>\n[DBM_result_table label='" . $options->rawformat() . "' data='" . $options->rawformat() . "']\n</div>\n";
	}
	return ob_get_clean();
}

?>