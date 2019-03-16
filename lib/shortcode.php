<?php
function dbm_shortcode_search ($atts, $content) {
	$param = shortcode_atts( array(
		'post_type' => '',
		'posts_per_page' => '5',
		'pager' => '',
	), $atts );
	if ($param['pager'])  {
		$pager_nonce = wp_create_nonce('dbm_search_pager');
	}
	ob_start();
?>
<form role="search" method="post" class="ajax-search-form" >
        <?php echo do_shortcode($content); ?>
<?php
		if ($param['pager']) {
			echo "<input type='hidden' name='pager_nonce' value='$pager_nonce' />\n";
		}
?>
        <input type="hidden" name="post_type"  value="<?php echo $param['post_type']; ?>" />
        <input type="hidden" id="posts_per_page" name="posts_per_page"  value="<?php echo $param['posts_per_page']; ?>" />
	<div style="text-align:center;"><button type="submit" class="ajax search-submit" value="search">この条件で検索する</button></div>
</form>
<?php
	return ob_get_clean();
}

function dbm_shortcode_tax_checkbox($atts) {
	ob_start();
	$param = shortcode_atts( array(
		'name' => '',
	), $atts );
	if (isset($param['name'])) {
		$taxonomy_name = $param['name'];
		$args = array( 'orderby' => 'id', 'hide_empty' => false);
		$taxonomys = get_terms($taxonomy_name, $args);
		if (!is_wp_error($taxonomys) && count($taxonomys)) {
			foreach ($taxonomys as $taxonomy){
			?>
				<label class="search-label-<?php echo $taxonomy->slug; ?>"><input type="checkbox" name="<?php echo 'tax_' . $taxonomy->taxonomy;?>[]" value="<?php echo $taxonomy->slug; ?>"><?php echo $taxonomy->name; ?></label>
<?php
			}
		}
	}
	return ob_get_clean();
}
function dbm_shortcode_tax_lable ($atts) {
	ob_start();
	$param = shortcode_atts( array(
		'name' => '',
	), $atts );
	if (isset($param['name'])) {
		$taxonomy_var = get_taxonomy($param['name']);
		echo $taxonomy_var->label;
	}
	return ob_get_clean();
}

function dbm_shortcode_tax_select($atts) {
	ob_start();
	$param = shortcode_atts( array(
		'name' => '',
		'multiple' => '',
		'size' => ''
	), $atts );
	if (isset($param['name'])) {
		$taxonomy_name = $param['name'];
		$args = array( 'orderby' => 'id', 'hide_empty' => false);
		$taxonomys = get_terms($taxonomy_name, $args);
		$option = '';
		if ($param['multiple']) {
			$option .= 'multiple ';
		}
		if ($param['size']) {
			$option .= 'size=' . $param['size'];
		}
		if (!is_wp_error($taxonomys) && count($taxonomys)) {
			echo "<select name='tax_" . $param['name'] . "[]'" . $option .  ">";
			echo "<option value=''>" . __(All) . "</option>";
			foreach ($taxonomys as $taxonomy){
				echo "<option value='" . $taxonomy->slug . "'>" . $taxonomy->name . "</option>";
			}
			echo "</select>";
		}
	}
	return ob_get_clean();
}

function dbm_shortcode_textbox($atts) {
	ob_start();
	$param = shortcode_atts( array(
		'name' => '',
		'required' => 'false',
	), $atts );
	$required = '';
	if ($param['required'] == 'true') {
		$required = 'required';
	}
	if (!empty($param['name'])) {
			echo "<input type='text' name='". $param['name'] . "' ". $required . ">";
	}
	else {
		echo "<input type='text' name='s' ". $required . ">";
	}
	return ob_get_clean();
}

function dbm_shortcode_result_table($atts) {
	ob_start();
	$param = shortcode_atts( array(
		'label' => '',
		'data' => '',
	), $atts );
	if (isset($param['data'])) {
		echo "<table>";
		if (isset($param['label'])) {
			$label = explode(",", $param['label']);
			echo "<thead><tr>";
			foreach ($label as $col) {
				echo "<th>" . $col . "</th>";
			}
			echo "</tr></thead>";
		}
		$data = explode(",", $param['data']);
		echo "<tbody class='datalist' data-bind='foreach: object'><tr>";
		foreach ($data as $col) {
			if (substr($col, 0, 4) != 'tax_') {
				echo "<td data-bind='text: " . $col . "'></td>";
			}
			else {
				echo "<td data-bind='foreach: " . $col . "'>";
				echo "<span data-bind='text: name'></span> ";
				echo "</td>";
			}
		}
		echo "</tr></tbody>";

		echo "</table>";
	}
	return ob_get_clean();
}

function dbm_shortcode_result_pager($atts) {
	ob_start();
	$param = shortcode_atts( array(
		'label' => 'first, prev, next, last',
	), $atts );
	$label = explode(",", $param['label']);
?>
<div class="ko-pager" data-bind="with: pager, visible: pager.items().length != 0">
    <a class="btn btn-small" data-bind="click: goToFirst"> <?php echo $label[0]; ?> </a>
    <a class="btn btn-small" data-bind="click: goToPrev"> <?php echo $label[1]; ?> </a>
    <span data-bind="text: current"></span>/<span data-bind="text: pages"></span>
    <a class="btn btn-small" data-bind="click: goToNext"> <?php echo $label[2]; ?> </a>
    <a class="btn btn-small" data-bind="click: goToLast"> <?php echo $label[3]; ?> </a>
    <span class="pager-summary" data-bind="visible: !isLoading()">
        <!--ko if: count() > 0 -->
        Found <span data-bind="text: count"> </span> records.
        <span data-bind="text: offset() + 1"> </span> -
        <span data-bind="text: offset() + items().length"> </span>
        <!--/ko-->
        <!--ko if: count() == 0 -->
        No records.
        <!--/ko-->
    </span>
    <span class="indicator" data-bind="visible: isLoading">
        <!-- 実際はローディング画像を入れる -->
        now loading ...
    </span>
</div>
<?php
	return ob_get_clean();
}
add_shortcode( 'dbm_search', 'dbm_shortcode_search' );
add_shortcode( 'dbm_tax_checkbox', 'dbm_shortcode_tax_checkbox' );
add_shortcode( 'dbm_tax_select', 'dbm_shortcode_tax_select' );
add_shortcode( 'dbm_tax_label', 'dbm_shortcode_tax_label' );
add_shortcode( 'dbm_textbox', 'dbm_shortcode_textbox' );
add_shortcode( 'dbm_result_table', 'dbm_shortcode_result_table' );
add_shortcode( 'dbm_result_pager', 'dbm_shortcode_result_pager' );
