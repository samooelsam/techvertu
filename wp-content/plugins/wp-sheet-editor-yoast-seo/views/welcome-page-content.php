<?php
$instance = WP_Sheet_Editor_YOAST_SEO_Lite_Obj();
?>
<p><?php _e('Thank you for installing our plugin.', $instance->textname); ?></p>

<?php
$steps = array();
if (!$instance->is_yoast_seo_plugin_active()) {
	$steps['install_dependencies_yoast'] = '<p>' . sprintf(__('Install the free plugin: YOAST SEO. <a href="%s" target="_blank" class="button install-plugin-trigger">Click here</a>. <br>This plugin is for editing SEO fields and it will not work without Yoast.', $instance->textname), $this->get_plugin_install_url('yoast seo')) . '</p>';
}
if (!function_exists('VGSE')) {
	$steps['install_dependencies'] = '<p>' . sprintf(__('Install the free plugin: WP Sheet Editor. <a href="%s" target="_blank" class="button install-plugin-trigger">Click here</a>. <br>WP Sheet Editor provides a spreadsheet editor inside WordPress where you can edit posts and pages<br>And this plugin is an extension of WP Sheet Editor that adds columns for editing the YOAST SEO fields in the spreadsheet editor.', $instance->textname), $this->get_plugin_install_url('wp-sheet-editor-bulk-spreadsheet-editor-for-posts-and-pages')) . '</p>';
} else {
	$steps['open_editor'] = '<p>' . sprintf(__('You can start editing: <a href="%s" class="button">Edit posts</a> <a href="%s" class="button">Edit pages</a>', $instance->textname), VGSE()->helpers->get_editor_url('post'), VGSE()->helpers->get_editor_url('page')) . '</p>';
}

$steps = apply_filters('vg_sheet_editor/yoast_free/welcome_steps', $steps);

if (!empty($steps)) {
	echo '<ol class="steps">';
	foreach ($steps as $key => $step_content) {
		if (empty($step_content)) {
			continue;
		}
		?>
		<li><?php echo $step_content; ?></li>		
		<?php
	}

	echo '</ol>';
}
?>
<hr>
<div class="tutorials" style="text-align: left;">
	<h3><?php _e('SEO Optimizations that you can make with our plugins', $instance->textname); ?></h3>
	<p><?php _e('WooCommerce SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/woocommerce-edit-hundreds-products-short-long-seo-descriptions/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast"  rel="bookmark"><?php _e('Edit Hundreds of Products with Short or Long SEO Descriptions', $instance->textname); ?></a></p>

	<p><?php _e('WooCommerce SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/woocommerce-quickly-find-edit-products-short-long-seo-title/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast" rel="bookmark"><?php _e('Quickly Find and Edit Products with Short or Long SEO title', $instance->textname); ?></a></p>

	<p><?php _e('WooCommerce SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/woocommerce-find-products-missing-seo-title-description/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast" rel="bookmark"><?php _e('How to Find Products Missing SEO Title or Description', $instance->textname); ?></a></p>

	<p><?php _e('Images SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/wordpress-how-bulk-edit-image-seo-alt-text/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast" rel="bookmark"><?php _e('How to Bulk Edit Image SEO Alt Text', $instance->textname); ?></a></p>

	<p><?php _e('Posts SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/wordpress-import-seo-title-description-from-excel-google-sheets/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast"  rel="bookmark"><?php _e('Import SEO Title and Description from Excel or Google Sheets', $instance->textname); ?></a></p>

	<p><?php _e('Posts SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/wordpress-search-posts-without-h2-h3-tags-content/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast" rel="bookmark"><?php _e('Search Posts WITHOUT H2 or H3 Tags in the Content', $instance->textname); ?></a></p>

	<p><?php _e('Posts SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/wordpress-fix-seo-errors-thousands-posts/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast" rel="bookmark"><?php _e('How to Fix SEO Errors on Thousands of Posts', $instance->textname); ?></a></p>

	<p><?php _e('Category Pages SEO:', $instance->textname); ?> <a  target="_blank"  href="https://wpsheeteditor.com/woocommere-how-to-edit-category-seo-title-description-spreadsheet/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast" rel="bookmark"><?php _e('How to Edit Category SEO Title and Description in a Spreadsheet', $instance->textname); ?></a></p>

</div>
<a  target="_blank"  href="https://wpsheeteditor.com/tag/seo/?utm_source=wp-admin&utm_medium=welcome-tutorials&utm_campaign=yoast" class="button" rel="bookmark"><?php _e('Read more SEO tutorials', $instance->textname); ?></a>

<?php
_e('<h3>Do you provide SEO services? are you a developer?</h3>
		<p>You can get more seo leads with WP Site Auditor plugin by Green Jay Media.<br>It lets your visitors add a url, keyword and email to generate an seo report of their website and you get the lead contact info with a copy of the report. The report shows info from moz, google page speed score, security, html issues and more.<br><a class="button" href="https://wpsheeteditor.com/go/seo-site-auditor" target="_blank">Grow your seo agency with WP Site Auditor</a></p>', $instance->textname);
