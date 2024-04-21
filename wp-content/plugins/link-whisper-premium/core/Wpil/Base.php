<?php

/**
 * Base controller
 */
class Wpil_Base
{
    public static $report_menu;
    public static $action_tracker = array();

    /**
     * Register services
     */
    public function register()
    {
        add_action('admin_init', [$this, 'init']);
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('admin_enqueue_scripts', [$this, 'addScripts']);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_scripts'));
        add_action('plugin_action_links_' . WPIL_PLUGIN_NAME, [$this, 'showSettingsLink']);
        add_action('upgrader_process_complete', [$this, 'upgrade_complete'], 10, 2);
        add_action('wp_ajax_get_post_suggestions', ['Wpil_Suggestion','ajax_get_post_suggestions']);
        add_action('wp_ajax_wpil_get_external_site_suggestions', ['Wpil_Suggestion', 'ajax_get_external_site_suggestions']);
        add_action('wp_ajax_update_suggestion_display', ['Wpil_Suggestion','ajax_update_suggestion_display']);
        add_action('wp_ajax_wpil_csv_export', ['Wpil_Export','ajax_csv']);
        add_action('wp_ajax_wpil_export_suggestion_data', ['Wpil_Export','ajax_export_suggestion_data']);
        add_action('wp_ajax_wpil_bulk_keyword_export', ['Wpil_Export','ajax_export_autolink_rule_data']);
        add_action('wp_ajax_wpil_clear_gsc_app_credentials', ['Wpil_SearchConsole','ajax_clear_custom_auth_config']);
        add_action('wp_ajax_wpil_gsc_deactivate_app', ['Wpil_SearchConsole','ajax_disconnect']);
        add_action('wp_ajax_wpil_save_animation_load_status', array('Wpil_Suggestion', 'ajax_save_animation_load_status'));
        add_action('wp_ajax_wpil_set_multi_link_in_sentence_editor', array('Wpil_Suggestion', 'ajax_set_allow_multiple_sentence_links'));
        add_action('wp_ajax_wpil_save_domain_attributes', array('Wpil_Settings', 'ajax_save_domain_attributes'));
        add_filter('the_content', array(__CLASS__, 'remove_link_whisper_attrs'));
        add_filter('the_content', array(__CLASS__, 'add_link_attrs'));
        add_filter('the_content', array(__CLASS__, 'add_link_icons'), 100, 1);
        foreach(Wpil_Settings::getPostTypes() as $post_type){
            add_filter("get_user_option_meta-box-order_{$post_type}", [$this, 'group_metaboxes'], 1000, 1 );
            add_filter($post_type . '_row_actions', array(__CLASS__, 'modify_list_row_actions'), 10, 2);
            add_filter( "manage_{$post_type}_posts_columns", array(__CLASS__, 'add_columns'), 11 );
            add_action( "manage_{$post_type}_posts_custom_column", array(__CLASS__, 'columns_contents'), 11, 2);
        }

        foreach(Wpil_Settings::getTermTypes() as $term_type){
            add_filter($term_type . '_row_actions', array(__CLASS__, 'modify_list_row_actions'), 10, 2); // we can only add the row actions. There's no modifying of the columns...
        }
    }

    /**
     * Initial function
     */
    function init()
    {
        $capability = apply_filters('wpil_filter_main_permission_check', 'manage_categories');
        if (!current_user_can($capability)) {
            return;
        }

        $post = self::getPost();

        if (!empty($_GET['csv_export'])) {
            Wpil_Export::csv();
        }

        if (!empty($_GET['type'])) { // if the current page has a "type" value
            $type = $_GET['type'];

            switch ($type) {
                case 'delete_link':
                    Wpil_Link::delete();
                    break;
                case 'inbound_suggestions_page_container':
                    include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/inbound_suggestions_page_container.php';
                    exit;
                    break;
            }
        }

        if (!empty($_GET['area'])) {
            switch ($_GET['area']) {
                case 'wpil_export':
                    Wpil_Export::getInstance()->export($post);
                    break;
                case 'wpil_excel_export':
                    $post = self::getPost();
                    if (!empty($post)) {
                        Wpil_Excel::exportPost($post);
                    }
                    break;
            }
        }

        if (!empty($_POST['hidden_action'])) {
            switch ($_POST['hidden_action']) {
                case 'wpil_save_settings':
                    Wpil_Settings::save();
                    break;
                case 'activate_license':
                    Wpil_License::activate();
                    break;
            }
        }

        // if we're on a link whisper page
        if(isset($_GET['page']) && ('link_whisper' === $_GET['page'] || 'link_whisper_settings' === $_GET['page'])){
            // do a version check
            $version = get_option('wpil_version_check_update', WPIL_PLUGIN_OLD_VERSION_NUMBER);
            // if the plugin update check hasn't run yet
            if($version < WPIL_PLUGIN_VERSION_NUMBER){
                // create any tables that need creating
                self::createDatabaseTables();
                // and make sure the existing tables are up to date
                self::updateTables();
                // note the updated status
                update_option('wpil_version_check_update', WPIL_PLUGIN_VERSION_NUMBER);
            }
        }


        //add screen options
        add_action("load-" . self::$report_menu, function () {
            add_screen_option( 'report_options', array(
                'option' => 'report_options',
            ) );
        });
    }

    /**
     * This function is used for adding menu and submenus
     *
     *
     * @return  void
     */
    public function addMenu()
    {
        $capability = apply_filters('wpil_filter_main_permission_check', 'manage_categories');
        if (!current_user_can($capability)) {
            return;
        }

        if (!Wpil_License::isValid()) {
            add_menu_page(
                __('Link Whisper', 'wpil'),
                __('Link Whisper', 'wpil'),
                'manage_categories',
                'link_whisper_license',
                [Wpil_License::class, 'init'],
                plugin_dir_url(__DIR__).'../images/lw-icon-16x16.png'
            );

            return;
        }

        add_menu_page(
            __('Link Whisper', 'wpil'),
            __('Link Whisper', 'wpil'),
            'edit_posts',
            'link_whisper',
            [Wpil_Report::class, 'init'],
            plugin_dir_url(__DIR__). '../images/lw-icon-16x16.png'
        );

        if(WPIL_STATUS_HAS_RUN_SCAN){
            $page_title = __('Internal Links Report', 'wpil');
            $menu_title = __('Reports', 'wpil');
        }else{
            $page_title = __('Internal Links Report', 'wpil');
            $menu_title = __('Complete Install', 'wpil');
        }

        self::$report_menu = add_submenu_page(
            'link_whisper',
            $page_title,
            $menu_title,
            'edit_posts',
            'link_whisper',
            [Wpil_Report::class, 'init']
        );

        // add the advanced functionality if the first scan has been run
        if(!empty(WPIL_STATUS_HAS_RUN_SCAN)){
            add_submenu_page(
                'link_whisper',
                __('Add Inbound Internal Links', 'wpil'),
                __('Add Inbound Internal Links', 'wpil'),
                'edit_posts',
                'admin.php?page=link_whisper&type=links'
            );

            $autolinks = add_submenu_page(
                'link_whisper',
                __('Auto-Linking', 'wpil'),
                __('Auto-Linking', 'wpil'),
                'manage_categories',
                'link_whisper_keywords',
                [Wpil_Keyword::class, 'init']
            );

            //add autolink screen options
            add_action("load-" . $autolinks, function () {
                add_screen_option( 'wpil_keyword_options', array( // todo possibly update 'keywords' to 'autolink' to avoid confusion
                    'option' => 'wpil_keyword_options',
                ) );
            });

            $target_keywords = add_submenu_page(
                'link_whisper',
                __('Target Keywords', 'wpil'),
                __('Target Keywords', 'wpil'),
                'manage_categories',
                'link_whisper_target_keywords',
                [Wpil_TargetKeyword::class, 'init']
            );

            //add target keyword screen options
            add_action("load-" . $target_keywords, function () {
                add_screen_option( 'target_keyword_options', array(
                    'option' => 'target_keyword_options',
                ) );
            });

            add_submenu_page(
                'link_whisper',
                __('URL Changer', 'wpil'),
                __('URL Changer', 'wpil'),
                'manage_categories',
                'link_whisper_url_changer',
                [Wpil_URLChanger::class, 'init']
            );
        }
        add_submenu_page(
            'link_whisper',
            __('Settings', 'wpil'),
            __('Settings', 'wpil'),
            'manage_categories',
            'link_whisper_settings',
            [Wpil_Settings::class, 'init']
        );
    }

    /**
     * Get post or term by ID from GET or POST request
     *
     * @return Wpil_Model_Post|null
     */
    public static function getPost()
    {
        if (!empty($_REQUEST['term_id'])) {
            $post = new Wpil_Model_Post((int)$_REQUEST['term_id'], 'term');
        } elseif (!empty($_REQUEST['post_id'])) {
            $post = new Wpil_Model_Post((int)$_REQUEST['post_id']);
        } else {
            $post = null;
        }

        return $post;
    }

    /**
     * Show plugin version
     *
     * @return string
     */
    public static function showVersion()
    {
        $plugin_data = get_plugin_data(WP_INTERNAL_LINKING_PLUGIN_DIR . 'link-whisper.php');

        return "<p style='float: right'>version <b>".$plugin_data['Version']."</b></p>";
    }

    /**
     * Show extended error message
     *
     * @param $errno
     * @param $errstr
     * @param $error_file
     * @param $error_line
     */
    public static function handleError($errno, $errstr, $error_file, $error_line)
    {
        if (stristr($errstr, "WordPress could not establish a secure connection to WordPress.org")) {
            return;
        }

        $file = 'n/a';
        $func = 'n/a';
        $line = 'n/a';
        $debugTrace = debug_backtrace();
        if (isset($debugTrace[1])) {
            $file = isset($debugTrace[1]['file']) ? $debugTrace[1]['file'] : 'n/a';
            $line = isset($debugTrace[1]['line']) ? $debugTrace[1]['line'] : 'n/a';
        }
        if (isset($debugTrace[2])) {
            $func = $debugTrace[2]['function'] ? $debugTrace[2]['function'] : 'n/a';
        }

        $out = "call from <b>$file</b>, $func, $line";

        $trace = '';
        $bt = debug_backtrace();
        $sp = 0;
        foreach($bt as $k=>$v) {
            extract($v);

            $args = '';
            if (isset($v['args'])) {
                $args2 = array();
                foreach($v['args'] as $k => $v) {
                    if (!is_scalar($v)) {
                        $args2[$k] = "Array";
                    }
                    else {
                        $args2[$k] = $v;
                    }
                }
                $args = implode(", ", $args2);
            }

            $file = substr($file,1+strrpos($file,"/"));
            $trace .= str_repeat("&nbsp;",++$sp);
            $trace .= "file=<b>$file</b>, line=$line,
									function=$function(".
                var_export($args, true).")<br>";
        }

        $out .= $trace;

        echo "<b>Error:</b> [$errno] $errstr - $error_file:$error_line<br><br><hr><br><br>$out";
    }

    /**
     * Add meta box to the post edit page
     */
    public static function addMetaBoxes()
    {
        $capability = apply_filters('wpil_filter_main_permission_check', 'manage_categories');
        if (!current_user_can($capability)) {
            return;
        }

        if (Wpil_License::isValid())
        {
            $post_id = isset($_REQUEST['post']) ? (int)$_REQUEST['post'] : '';
            if ($post_id) {
                // exit if the post has been ignored
                $completely_ignored = Wpil_Settings::get_completely_ignored_pages();
                if(!empty($completely_ignored) && in_array('post_' . $post_id, $completely_ignored, true)){
                    return;
                }
            }

            // only show the Target Keywords panel if there's a post id
            if(!empty($post_id)){
                add_meta_box('wpil_target-keywords', 'Link Whisper Target Keywords', [Wpil_Base::class, 'showTargetKeywordsBox'], Wpil_Settings::getPostTypes());
            }
            add_meta_box('wpil_link-articles', 'Link Whisper Suggested Links', [Wpil_Base::class, 'showSuggestionsBox'], Wpil_Settings::getPostTypes());
        }
    }

    /**
     * Show meta box on the post edit page
     */
    public static function showSuggestionsBox()
    {
        $post_id = isset($_REQUEST['post']) ? (int)$_REQUEST['post'] : '';
        $user = wp_get_current_user();
        $manually_trigger_suggestions = !empty(get_option('wpil_manually_trigger_suggestions', false));
        if ($post_id) {
            // clear any old links that may still be hiding in the meta
            delete_post_meta($post_id, 'wpil_links');
            include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/link_list_v2.php';
        }else{
            include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/link_list_please_save_post.php';
        }
    }

    /**
     * Show the target keyword metabox on the post edit screen
     */
    public static function showTargetKeywordsBox()
    {
        $post_id = isset($_REQUEST['post']) ? (int)$_REQUEST['post'] : '';
        $user = wp_get_current_user();
        if ($post_id) {
            $keyword_sources = Wpil_TargetKeyword::get_active_keyword_sources();
            $keywords = Wpil_TargetKeyword::get_keywords_by_post_ids($post_id);
            $post = new Wpil_Model_Post($post_id, 'post');
            $is_metabox = true;
            include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/target_keyword_list.php';
        }
    }

    /**
     * Makes sure the link suggestions and the target keyword metaboxes are in the same general grouping
     **/
    public static function group_metaboxes($option){
        // if there are no grouping settings, exit
        if(empty($option)){
            return $option;
        }

        $has_target_keyword = false;
        $suggestion_box = '';
        foreach($option as $position => $boxes){
            if(false !== strpos($boxes, 'wpil_target-keywords')){
                $has_target_keyword = true;
            }

            if(false !== strpos($boxes, 'wpil_link-articles')){
                $suggestion_box = $position;
            }
        }
        
        // if the target keyword box hasn't been set yet, but the suggestion box has
        if(empty($has_target_keyword) && !empty($suggestion_box)){
            // place the target keyword box above the suggestion box
            $option[$suggestion_box] = str_replace('wpil_link-articles', 'wpil_target-keywords,wpil_link-articles', $option[$suggestion_box]);
        }

        return $option;
    }

    /**
     * Add scripts to the admin panel
     *
     * @param $hook
     */
    public static function addScripts($hook)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/post.php') !== false || strpos($_SERVER['REQUEST_URI'], '/term.php') !== false || (!empty($_GET['page']) && $_GET['page'] == 'link_whisper')) {
            if(function_exists('wp_enqueue_editor')){
                wp_enqueue_editor();
            }
        }

        wp_register_script('wpil_base64', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/base64.js', array(), false, true);
        wp_enqueue_script('wpil_base64');

        wp_register_script('wpil_sweetalert_script_min', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/sweetalert.min.js', array('jquery'), $ver=false, true);
        wp_enqueue_script('wpil_sweetalert_script_min');

        $js_path = 'js/wpil_admin.js';
        $f_path = WP_INTERNAL_LINKING_PLUGIN_DIR.$js_path;
        $ver = filemtime($f_path);
        $current_screen = get_current_screen();

        wp_register_script('wpil_admin_script', WP_INTERNAL_LINKING_PLUGIN_URL.$js_path, array('jquery', 'wpil_base64'), $ver, true);
        wp_enqueue_script('wpil_admin_script');

        // IF
        if (isset($_GET['page']) && $_GET['page'] == 'link_whisper' && isset($_GET['type']) && ($_GET['type'] == 'inbound_suggestions_page' ||  // on the Inbound Suggestions page
            $_GET['type'] == 'click_details_page') ||                                                                                           // or the Detailed Click Report page
            (!empty($current_screen) && ('post' === $current_screen->base || 'page' === $current_screen->base))                                 // or a post edit screen
        ){
            wp_register_style('wpil_daterange_picker_css', WP_INTERNAL_LINKING_PLUGIN_URL . 'css/daterangepicker.css');
            wp_enqueue_style('wpil_daterange_picker_css');
            wp_register_style('wpil_select2_css', WP_INTERNAL_LINKING_PLUGIN_URL . 'css/select2.min.css');
            wp_enqueue_style('wpil_select2_css');
            wp_register_script('wpil_moment', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/moment.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_moment');
            wp_register_script('wpil_daterange_picker', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/daterangepicker.js', array('jquery', 'wpil_moment'), $ver, true);
            wp_enqueue_script('wpil_daterange_picker');
            wp_register_script('wpil_select2', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/select2.full.min.js', array('jquery'), $ver, true); // Todo: remove the select2.min.js file when we pass 2.2.0
            wp_enqueue_script('wpil_select2');
        }

        if (isset($_GET['page']) && $_GET['page'] == 'link_whisper' && isset($_GET['type']) && $_GET['type'] == 'links') {
            wp_register_script('wpil_report', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/wpil_report.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_report');
        }

        if (isset($_GET['page']) && $_GET['page'] == 'link_whisper' && isset($_GET['type']) && $_GET['type'] == 'error') {
            wp_register_script('wpil_error', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/wpil_error.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_error');
        }

        if (isset($_GET['page']) && $_GET['page'] == 'link_whisper' && isset($_GET['type']) && $_GET['type'] == 'domains') {
            wp_register_style('wpil_select2_css', WP_INTERNAL_LINKING_PLUGIN_URL . 'css/select2.min.css');
            wp_enqueue_style('wpil_select2_css');
            wp_register_script('wpil_select2', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/select2.full.min.js', array('jquery'), $ver, true); // Todo: remove the select2.min.js file when we pass 2.2.0
            wp_enqueue_script('wpil_select2');
            
            wp_register_script('wpil_domains', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/wpil_domains.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_domains');
        }

        if (isset($_GET['page']) && $_GET['page'] == 'link_whisper' && isset($_GET['type']) && ( $_GET['type'] == 'click_details_page' || $_GET['type'] == 'clicks')) {
            wp_register_script('wpil_click', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/wpil_click.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_click');
        }

        if (isset($_GET['page']) && $_GET['page'] == 'link_whisper_keywords') {
            wp_register_script('wpil_keyword', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/wpil_keyword.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_keyword');
            wp_register_script('wpil_papa_parse', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/papaparse.min.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_papa_parse');
        }

        if (isset($_GET['page']) && $_GET['page'] == 'link_whisper_url_changer') {
            wp_register_script('wpil_keyword', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/wpil_url_changer.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_keyword');
        }

        if (isset($_GET['page']) && ($_GET['page'] == 'link_whisper_target_keywords' || $_GET['page'] == 'link_whisper' && isset($_GET['type']) && $_GET['type'] === 'inbound_suggestions_page') || ('post' === $current_screen->base || 'term' === $current_screen->base) ) {
            wp_register_script('wpil_target_keyword', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/wpil_target_keyword.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_target_keyword');
        }

        if(isset($_GET['page']) && ($_GET['page'] == 'link_whisper_settings')){
            $js_path = 'js/wpil_admin_settings.js';
            $f_path = WP_INTERNAL_LINKING_PLUGIN_DIR.$js_path;
            $ver = filemtime($f_path);

            add_thickbox();

            wp_register_script('wpil_admin_settings_script', WP_INTERNAL_LINKING_PLUGIN_URL.$js_path, array('jquery', 'wpil_select2'), $ver, true);
            wp_enqueue_script('wpil_admin_settings_script');

            wp_register_style('wpil_select2_css', WP_INTERNAL_LINKING_PLUGIN_URL . 'css/select2.min.css');
            wp_enqueue_style('wpil_select2_css');
            wp_register_script('wpil_select2', WP_INTERNAL_LINKING_PLUGIN_URL . 'js/select2.full.min.js', array('jquery'), $ver, true);
            wp_enqueue_script('wpil_select2');
        }

        $style_path = 'css/wpil_admin.css';
        $f_path = WP_INTERNAL_LINKING_PLUGIN_DIR.$style_path;
        $ver = filemtime($f_path);

        wp_register_style('wpil_admin_style', WP_INTERNAL_LINKING_PLUGIN_URL.$style_path, $deps=[], $ver);
        wp_enqueue_style('wpil_admin_style');

        $disable_fonts = apply_filters('wpil_disable_fonts', false); // we've only got one font ATM
        if(empty($disable_fonts)){
            $style_path = 'css/wpil_fonts.css';
            $f_path = WP_INTERNAL_LINKING_PLUGIN_DIR.$style_path;
            $ver = filemtime($f_path);

            wp_register_style('wpil_admin_fonts', WP_INTERNAL_LINKING_PLUGIN_URL.$style_path, $deps=[], $ver);
            wp_enqueue_style('wpil_admin_fonts');
        }

        $ajax_url = admin_url('admin-ajax.php');

        $script_params = [];
        $script_params['ajax_url'] = $ajax_url;
        $script_params['completed'] = __('completed', 'wpil');
        $script_params['site_linking_enabled'] = (!empty(get_option('wpil_link_external_sites', false))) ? 1: 0;

        $script_params["WPIL_OPTION_REPORT_LAST_UPDATED"] = get_option(WPIL_OPTION_REPORT_LAST_UPDATED);

        wp_localize_script('wpil_admin_script', 'wpil_ajax', $script_params);
    }

    /**
     * Enqueues the scripts to use on the frontend.
     **/
    public static function enqueue_frontend_scripts(){
        global $wp_the_query, $post;

        // if we're doing a preview of the settings
        if( isset($_GET['wpil_related_post_preview_nonce']) &&
            isset($_GET['nonce']) && 
            wp_verify_nonce($_GET['nonce'], 'wpil-related-posts-preview-nonce'))
        {
            // inline a bit of JS to scroll the window to the RP widget
            wp_register_script('link-whisper-related-post-preview-inline', '');
            wp_enqueue_script('link-whisper-related-post-preview-inline');
            wp_add_inline_script('link-whisper-related-post-preview-inline', 'window.location.hash = "#link-whisper-related-posts-widget";');
        }

        // TODO: Add an option to disable the frontend scripts.
        if(empty($wp_the_query) || !Wpil_License::isValid()){
            return;
        }

        $posty = $wp_the_query->get_queried_object();

        // if we're on a post type archive
        if($wp_the_query->is_post_type_archive || is_a($posty, 'WP_Post_Type')){
            // exit since we can't accurately assign clicks to a post
            return;
        }
        
        if(empty($posty)){
            $posty = $post;
        }

        // get if the links are to be opened in new tabs
        $open_with_js       = (!empty(get_option('wpil_js_open_new_tabs', false))) ? 1: 0;
        $open_all_intrnl    = (!empty(get_option('wpil_open_all_internal_new_tab', false))) ? 1: 0;
        $open_all_extrnl    = (!empty(get_option('wpil_open_all_external_new_tab', false))) ? 1: 0;

        // and if the user has disabled click tracking or there isn't a valid post id
        $dont_track_clicks = (!empty(get_option('wpil_disable_click_tracking', false)) || empty($posty)) ? 1: 0;

        // if none of them are, exit
        if( ($open_with_js == 0 || $open_all_intrnl == 0 && $open_all_extrnl == 0) && $dont_track_clicks == 1){
            return;
        }

        // put together the ajax variables
        $ajax_url = get_site_url(null, 'wp-admin/admin-ajax.php', 'relative');
        $type = null; 
        $id = null;
        if(!empty($posty)){
            $type = (is_a($posty, 'WP_Term')) ? 'term': 'post';
            $id = ($type === 'post') ? $posty->ID: $posty->term_id;
        }
        $script_params = [];
        $script_params['ajaxUrl'] = $ajax_url;
        $script_params['postId'] = $id;
        $script_params['postType'] = $type;
        $script_params['openInternalInNewTab'] = $open_all_intrnl;
        $script_params['openExternalInNewTab'] = $open_all_extrnl;
        $script_params['disableClicks'] = $dont_track_clicks;
        $script_params['openLinksWithJS'] = $open_with_js;
        $script_params['trackAllElementClicks'] = !empty(get_option('wpil_track_all_element_clicks', 0)) ? 1: 0;


        // output some actual localizations
        $script_params['clicksI18n'] = array(
            'imageNoText'   => __('Image in link: No Text', 'wpil'),
            'imageText'     => __('Image Title: ', 'wpil'),
            'noText'        => __('No Anchor Text Found', 'wpil'),
        );

        // enqueue the frontend scripts
        $filename = (true) ? 'frontend.min.js': 'frontend.js';

        $file_path = WP_INTERNAL_LINKING_PLUGIN_DIR . 'js/' . $filename;
        $url_path  = WP_INTERNAL_LINKING_PLUGIN_URL . 'js/' . $filename;
        wp_enqueue_script('wpil-frontend-script', $url_path, array(), filemtime($file_path), true);

        // output the ajax variables
        wp_localize_script('wpil-frontend-script', 'wpilFrontend', $script_params);
    }

    /**
     * Show settings link on the plugins page
     *
     * @param $links
     * @return array
     */
    public static function showSettingsLink($links)
    {
        if(class_exists('Wpil_License') && !Wpil_License::isValid()){
            $links[] = '<a href="admin.php?page=link_whisper_license">Activate License</a>';
        }else{
            $links[] = '<a href="admin.php?page=link_whisper_settings">Settings</a>';
        }

        return $links;
    }

    /**
     * Loads default LinkWhisper settings in to database on plugin activation.
     */
    public static function activate()
    {
        // only set default option values if the options are empty
        if('' === get_option(WPIL_OPTION_LICENSE_STATUS, '')){
            update_option(WPIL_OPTION_LICENSE_STATUS, '');
        }
        if('' === get_option(WPIL_OPTION_LICENSE_KEY, '')){
            update_option(WPIL_OPTION_LICENSE_KEY, '');
        }
        if('' === get_option(WPIL_OPTION_IGNORE_NUMBERS, '')){
            update_option(WPIL_OPTION_IGNORE_NUMBERS, '1');
        }
        if('' === get_option(WPIL_OPTION_POST_TYPES, '')){
            update_option(WPIL_OPTION_POST_TYPES, ['post', 'page']);
        }
        if('' === get_option(WPIL_OPTION_LINKS_OPEN_NEW_TAB, '')){
            update_option(WPIL_OPTION_LINKS_OPEN_NEW_TAB, '0');
        }
        if('' === get_option(WPIL_OPTION_DEBUG_MODE, '')){
            update_option(WPIL_OPTION_DEBUG_MODE, '0');
        }
        if('' === get_option(WPIL_OPTION_UPDATE_REPORTING_DATA_ON_SAVE, '')){
            update_option(WPIL_OPTION_UPDATE_REPORTING_DATA_ON_SAVE, '0');
        }
        if('' === get_option(WPIL_OPTION_IGNORE_WORDS, '')){
            // if there's no ignore words, configure the language settings
            update_option('wpil_selected_language', Wpil_Settings::getSiteLanguage());
            $ignore = "-\r\n" . implode("\r\n", Wpil_Settings::getIgnoreWords()) . "\r\n-";
            update_option(WPIL_OPTION_IGNORE_WORDS, $ignore);
        }
        if('' === get_option(WPIL_LINK_TABLE_IS_CREATED, '')){
            Wpil_Report::setupWpilLinkTable(true);
            // if the plugin is activating and the link table isn't set up, assume this is a fresh install
            update_option('wpil_fresh_install', true); // the link table was created with ver 0.8.3 and was the first major table event, so it should be a safe test for new installs
        }
        if('' === get_option('wpil_install_date', '')){
            // set the install date since it may come in handy
            update_option('wpil_install_date', current_time('mysql', true));
        }

        Wpil_Link::removeLinkClass();

        self::createDatabaseTables();
        self::updateTables();
        // note the updated status
        update_option('wpil_version_check_update', WPIL_PLUGIN_VERSION_NUMBER);
    }

    /**
     * Runs any update routines after the plugin has been updated.
     */
    public static function upgrade_complete($upgrader_object, $options){
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
            // Go through each plugin to see if Link Whisper was updated
            foreach( $options['plugins'] as $plugin ) {
                if( $plugin == WPIL_PLUGIN_NAME ) {
                    // create any tables that need creating
                    self::createDatabaseTables();
                    // and make sure the existing tables are up to date
                    self::updateTables();
                    // note the updated status
                    update_option('wpil_version_check_update', WPIL_PLUGIN_VERSION_NUMBER);
                }
            }
        }
    }

    /**
     * Updates the existing LW data tables with changes as we add them.
     * Does a version check to see if any DB tables have been updated since the last time this was run.
     * 
     * @param bool $force_update Setting $force_update to true will ignore the version checks and run all update steps
     */
    public static function updateTables($force_update = false){
        global $wpdb;

        $autolink_tbl = $wpdb->prefix . 'wpil_keyword_links';
        $autolink_rule_tbl = $wpdb->prefix . 'wpil_keywords';
        $autolink_select_tbl = $wpdb->prefix . 'wpil_keyword_select_links';
        $broken_link_tbl = $wpdb->prefix . 'wpil_broken_links';
        $ignore_broken_link_tbl = $wpdb->prefix . 'wpil_ignore_links';
        $report_links_tbl = $wpdb->prefix . 'wpil_report_links';
        $target_keyword_tbl = $wpdb->prefix . 'wpil_target_keyword_data';
        $url_changer_tbl = $wpdb->prefix . 'wpil_urls';
        $url_links_tbl = $wpdb->prefix . 'wpil_url_links';
        $click_tracking_tbl = $wpdb->prefix . 'wpil_click_data';
        $related_posts_tbl = $wpdb->prefix . "wpil_related_posts";

        $fresh_install = get_option('wpil_fresh_install', false);

        // if the DB is up to date, exit
        if(WPIL_STATUS_SITE_DB_VERSION === WPIL_STATUS_PLUGIN_DB_VERSION && !$force_update){
            return;
        }

        // if this is a fresh install of the plugin and not a forced update
        if($fresh_install && empty(WPIL_STATUS_SITE_DB_VERSION) && !$force_update){
            // set the DB version as the latest since all the created tables will be up to date
            update_option('wpil_site_db_version', WPIL_STATUS_PLUGIN_DB_VERSION);
            update_option('wpil_fresh_install', false);
            // and exit
            return;
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 0.9 || $force_update){
            // Added in v1.0.0
            // if the error links table exists
            $error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$broken_link_tbl}'");
            if(!empty($error_tbl_exists)){
                // find out if the table has a last_checked col
                $col = $wpdb->query("SHOW COLUMNS FROM {$broken_link_tbl} LIKE 'last_checked'");
                if(empty($col)){
                    // if it doesn't, add it and a check_count col to the table
                    $update_table = "ALTER TABLE {$broken_link_tbl} ADD COLUMN check_count INT(2) DEFAULT 0 AFTER created, ADD COLUMN last_checked DATETIME NOT NULL DEFAULT NOW() AFTER created";
                    $wpdb->query($update_table);
                }
            }

            // update the state of the DB to this point
            update_option('wpil_site_db_version', '0.9');
        }

        // if the current DB version is less than 1.0, run the 1.0 update
        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.0 || $force_update){
            /** added in v1.0.1 **/
            // if the error links table exists
            $error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$broken_link_tbl}'");
            if(!empty($error_tbl_exists)){
                // find out if the table has a ignore_link col
                $col = $wpdb->query("SHOW COLUMNS FROM {$broken_link_tbl} LIKE 'ignore_link'");
                if(empty($col)){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$broken_link_tbl} ADD COLUMN ignore_link tinyint(1) DEFAULT 0 AFTER `check_count`";
                    $wpdb->query($update_table);
                }
            }

            // update the state of the DB to this point
            update_option('wpil_site_db_version', '1.0');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.16 || $force_update){
            $error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$broken_link_tbl}'");
            if(!empty($error_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$broken_link_tbl} LIKE 'sentence'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$broken_link_tbl} ADD COLUMN sentence varchar(1000) AFTER `ignore_link`";
                    $wpdb->query($update_table);
                }
            }

            $error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$report_links_tbl}'");
            if(!empty($error_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$report_links_tbl} LIKE 'location'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$report_links_tbl} ADD COLUMN location varchar(20) AFTER `post_type`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.16');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.17 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_tbl} LIKE 'anchor'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_tbl} ADD COLUMN anchor text AFTER `post_type`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.17');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.18 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'restrict_cats'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN restrict_cats tinyint(1) DEFAULT 0 AFTER `link_once`";
                    $wpdb->query($update_table);
                }
            }

            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'restricted_cats'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN restricted_cats text AFTER `restrict_cats`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.18');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.19 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'restrict_date'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN restrict_date tinyint(1) DEFAULT 0 AFTER `link_once`";
                    $wpdb->query($update_table);
                }

                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'restricted_date'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN restricted_date DATETIME AFTER `restrict_date`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.19');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.20 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'select_links'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN select_links tinyint(1) DEFAULT 0 AFTER `link_once`";
                    $wpdb->query($update_table);
                }
            }

            // make sure the possible links table is created too
            Wpil_Keyword::preparePossibleLinksTable();

            update_option('wpil_site_db_version', '1.20');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.21 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'set_priority'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN set_priority tinyint(1) DEFAULT 0 AFTER `select_links`";
                    $wpdb->query($update_table);
                }
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'priority_setting'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN priority_setting int DEFAULT 0 AFTER `set_priority`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.21');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.22 || $force_update){
            $changed_urls_exist = $wpdb->query("SHOW TABLES LIKE '{$url_links_tbl}'");
            if(!empty($changed_urls_exist)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$url_links_tbl} LIKE 'relative_link'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$url_links_tbl} ADD COLUMN relative_link tinyint(1) DEFAULT 0 AFTER `anchor`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.22');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.23 || $force_update){
            $error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$report_links_tbl}'");
            if(!empty($error_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$report_links_tbl} LIKE 'broken_link_scanned'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$report_links_tbl} ADD COLUMN broken_link_scanned tinyint(1) DEFAULT 0 AFTER `location`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.23');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.24 || $force_update){
            $trgt_kword_tbl_exists = $wpdb->query("SHOW TABLES LIKE '$target_keyword_tbl'");
            if(!empty($trgt_kword_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM $target_keyword_tbl LIKE 'auto_checked'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE $target_keyword_tbl ADD COLUMN auto_checked tinyint(1) DEFAULT 0 AFTER `save_date`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.24');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.25 || $force_update){
            $clk_tbl_exists = $wpdb->query("SHOW TABLES LIKE '$click_tracking_tbl'");
            if(!empty($clk_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM $click_tracking_tbl LIKE 'link_location'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE $click_tracking_tbl ADD COLUMN link_location varchar(64) DEFAULT 'Body Content' AFTER `link_anchor`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.25');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.26 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'case_sensitive'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN case_sensitive tinyint(1) DEFAULT 0 AFTER `restricted_cats`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.26');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.27 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'force_insert'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN force_insert tinyint(1) DEFAULT 0 AFTER `case_sensitive`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.27');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.28 || $force_update){
            $url_changer_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$url_changer_tbl}'");
            if(!empty($url_changer_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$url_changer_tbl} LIKE 'wildcard_match'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$url_changer_tbl} ADD COLUMN wildcard_match tinyint(1) DEFAULT 0 AFTER `new`";
                    $wpdb->query($update_table);
                }
            }

            $url_links_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$url_links_tbl}'");
            if(!empty($url_links_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$url_links_tbl} LIKE 'original_url'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$url_links_tbl} ADD COLUMN original_url text NOT NULL AFTER `anchor`";
                    $wpdb->query($update_table);
                }
            }

            $broken_link_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$broken_link_tbl}'");
            if(!empty($broken_link_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$broken_link_tbl} LIKE 'anchor'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$broken_link_tbl} ADD COLUMN anchor text NOT NULL AFTER `sentence`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.28');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.29 || $force_update){
            $autolink_rule_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($autolink_rule_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'link'"); // This time we have to make sure `link` _does_ exist
                if(!empty($col)){
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} CHANGE `link` `link` VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.29');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.30 || $force_update){
            $autolink_rule_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'");
            if(!empty($autolink_rule_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'limit_inserts'"); // since we're adding cols, make sure it doens't already exist
                if(empty($col)){
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN limit_inserts tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `link_once`";
                    $wpdb->query($update_table);
                }

                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'insert_limit'"); // since we're adding cols, make sure it doens't already exist
                if(empty($col)){
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN insert_limit INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `limit_inserts`";
                    $wpdb->query($update_table);
                }

                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'prioritize_longtail'"); // since we're adding cols, make sure it doens't already exist
                if(empty($col)){
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN prioritize_longtail tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `priority_setting`";
                    $wpdb->query($update_table);
                }

                $col = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'same_lang'"); // since we're adding cols, make sure it doens't already exist
                if(empty($col)){
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN same_lang tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `force_insert`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.30');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.31 || $force_update){
            $link_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$report_links_tbl}'");
            if(!empty($link_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$report_links_tbl} LIKE 'target_id'"); // since we're adding cols, make sure it doens't already exist
                if(empty($col)){
                    $update_table = "ALTER TABLE {$report_links_tbl} ADD COLUMN target_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER `post_id`";
                    $wpdb->query($update_table);
                }

                $col = $wpdb->query("SHOW COLUMNS FROM {$report_links_tbl} LIKE 'target_type'"); // since we're adding cols, make sure it doens't already exist
                if(empty($col)){
                    $update_table = "ALTER TABLE {$report_links_tbl} ADD COLUMN target_type TEXT AFTER `target_id`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.31');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.32 || $force_update){
            $keywrd_url_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$autolink_tbl}'");
            if(!empty($keywrd_url_tbl_exists)) {
                $index = $wpdb->query("SHOW INDEX FROM {$autolink_tbl} WHERE COLUMN_NAME = 'keyword_id'"); // since we're adding cols, make sure it doens't already exist
                if(empty($index)){
                    $update_table = "ALTER TABLE {$autolink_tbl} ADD INDEX(`keyword_id`)";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.32');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.33 || $force_update){
            $link_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$report_links_tbl}'");
            if(!empty($link_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$report_links_tbl} LIKE 'link_whisper_created'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$report_links_tbl} ADD COLUMN link_whisper_created tinyint(1) DEFAULT 0 AFTER `broken_link_scanned`";
                    $wpdb->query($update_table);
                }

                $col = $wpdb->query("SHOW COLUMNS FROM {$report_links_tbl} LIKE 'is_autolink'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$report_links_tbl} ADD COLUMN is_autolink tinyint(1) DEFAULT 0 AFTER `link_whisper_created`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.33');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.34 || $force_update){
            $error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$broken_link_tbl}'");
            if(!empty($error_tbl_exists)){
                // find out if the table has a ignore_link col
                $row = $wpdb->get_row("SHOW COLUMNS FROM {$broken_link_tbl} LIKE 'post_id'");
                if(!empty($row) && isset($row->Type) && is_string($row->Type) && false !== strpos($row->Type, 'int(10)')){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$broken_link_tbl} CHANGE `post_id` `post_id` BIGINT(20) UNSIGNED NOT NULL";
                    $wpdb->query($update_table);
                }
            }

            $ignore_error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$ignore_broken_link_tbl}'");
            if(!empty($ignore_error_tbl_exists)){
                // find out if the table has a ignore_link col
                $row = $wpdb->get_row("SHOW COLUMNS FROM {$ignore_broken_link_tbl} LIKE 'post_id'");
                if(!empty($row) && isset($row->Type) && is_string($row->Type) && false !== strpos($row->Type, 'int(10)')){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$ignore_broken_link_tbl} CHANGE `post_id` `post_id` BIGINT(20) UNSIGNED NOT NULL";
                    $wpdb->query($update_table);
                }
            }

            if(!empty($wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'"))){
                // find out if the table has a ignore_link col
                $row = $wpdb->get_row("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'post_id'");
                if(!empty($row) && isset($row->Type) && is_string($row->Type) && false !== strpos($row->Type, 'int(10)')){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} CHANGE `post_id` `post_id` BIGINT(20) UNSIGNED NOT NULL";
                    $wpdb->query($update_table);
                }
            }

            if(!empty($wpdb->query("SHOW TABLES LIKE '{$autolink_select_tbl}'"))){
                // find out if the table has a ignore_link col
                $row = $wpdb->get_row("SHOW COLUMNS FROM {$autolink_select_tbl} LIKE 'post_id'");
                if(!empty($row) && isset($row->Type) && is_string($row->Type) && false !== strpos($row->Type, 'int(10)')){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$autolink_select_tbl} CHANGE `post_id` `post_id` BIGINT(20) UNSIGNED NOT NULL";
                    $wpdb->query($update_table);
                }
            }

            if(!empty($wpdb->query("SHOW TABLES LIKE '{$url_links_tbl}'"))){
                // find out if the table has a ignore_link col
                $row = $wpdb->get_row("SHOW COLUMNS FROM {$url_links_tbl} LIKE 'post_id'");
                if(!empty($row) && isset($row->Type) && is_string($row->Type) && false !== strpos($row->Type, 'int(10)')){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$url_links_tbl} CHANGE `post_id` `post_id` BIGINT(20) UNSIGNED NOT NULL";
                    $wpdb->query($update_table);
                }
            }

            if(!empty($wpdb->query("SHOW TABLES LIKE '{$related_posts_tbl}'"))){
                // find out if the table has a ignore_link col
                $row = $wpdb->get_row("SHOW COLUMNS FROM {$related_posts_tbl} LIKE 'post_id'");
                if(!empty($row) && isset($row->Type) && is_string($row->Type) && false !== strpos($row->Type, 'int(10)')){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$related_posts_tbl} CHANGE `post_id` `post_id` BIGINT(20) UNSIGNED NOT NULL";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.34');
        }

        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.35 || $force_update){
            if(!empty($wpdb->query("SHOW TABLES LIKE '{$autolink_rule_tbl}'"))){
                // find out if the table has a ignore_link col
                $exists = $wpdb->query("SHOW COLUMNS FROM {$autolink_rule_tbl} LIKE 'restrict_to_live'");
                if(empty($exists)){
                    // if it doesn't, update it with the "ignore_link" column
                    $update_table = "ALTER TABLE {$autolink_rule_tbl} ADD COLUMN restrict_to_live tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `link_once`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.35');
        }


        // todo create a database index for click tracking's user_ip column if people find that it takes too long to load the user_ip view
/*
        if((float)WPIL_STATUS_SITE_DB_VERSION < 1.23 || $force_update){
            $error_tbl_exists = $wpdb->query("SHOW TABLES LIKE '{$report_links_tbl}'");
            if(!empty($error_tbl_exists)) {
                $col = $wpdb->query("SHOW COLUMNS FROM {$report_links_tbl} LIKE 'broken_link_scanned'");
                if (empty($col)) {
                    $update_table = "ALTER TABLE {$report_links_tbl} ADD COLUMN broken_link_scanned tinyint(1) DEFAULT 0 AFTER `location`";
                    $wpdb->query($update_table);
                }
            }

            update_option('wpil_site_db_version', '1.23');
        }*/
    }


    /**
     * Modifies the post's row actions to add an "Add Inbound Links" button to the row actions.
     * Only adds the link to post types that we create links for.
     * 
     * @param $actions
     * @param $object
     * @return $actions
     **/
    public static function modify_list_row_actions( $actions, $object ) {
        $type = is_a($object, 'WP_Post') ? $object->post_type: $object->taxonomy;

        if(!in_array($type, Wpil_Settings::getAllTypes())){
            return $actions;
        }

        $page = (isset($_GET['paged']) && !empty($_GET['paged'])) ? '&paged=' . (int)$_GET['paged']: '';

        if(is_a($object, 'WP_Post')){
            $actions['wpil-add-inbound-links'] = '<a target=_blank href="' . admin_url("admin.php?post_id={$object->ID}&page=link_whisper&type=inbound_suggestions_page&ret_url=" . base64_encode(admin_url("edit.php?post_type={$type}{$page}&direct_return=1"))) . '">Add Inbound Links</a>';
        }else{
            global $wp_taxonomies;
            $post_type = $wp_taxonomies[$type]->object_type[0];
            $actions['wpil-add-inbound-links'] = '<a target=_blank href="' . admin_url("admin.php?term_id={$object->term_id}&page=link_whisper&type=inbound_suggestions_page&ret_url=" . base64_encode(admin_url("edit-tags.php?taxonomy={$type}{$page}&post_type={$post_type}&direct_return=1"))) . '">Add Inbound Links</a>';
        }

        return $actions;
    }

	/**
	 * Add new columns for SEO title, description and focus keywords.
	 *
	 * @param array $columns Array of column names.
	 *
	 * @return array
	 */
	public static function add_columns($columns){
		global $post_type;

        if(!in_array($post_type, Wpil_Settings::getPostTypes())){
            return $columns;
        }
        
		$columns['wpil-link-stats'] = esc_html__('Link Stats', 'wpil');

		return $columns;
	}

    /**
	 * Add content for custom column.
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public static function columns_contents($column_name, $post_id){
        if('wpil-link-stats' === $column_name){
            $post_status = get_post_status($post_id);
            // exit if the current post is in a status we don't process
            if(!in_array($post_status, Wpil_Settings::getPostStatuses())){
                $status_obj = get_post_status_object($post_status);
                $status = (!empty($status_obj)) ? $status_obj->label: ucfirst($post_status);
                ?>
                <span class="wpil-link-stats-column-display wpil-link-stats-content">
                    <strong><?php _e('Links: ', 'wpil'); ?></strong>
                    <span><span><?php echo sprintf(__('%s post processing %s.', 'wpil'), $status, '<a href="' . admin_url("admin.php?page=link_whisper_settings") . '">' . __('not set', 'wpil') . '</a>'); ?></span></span>
                </span>
                <?php
                return;
            }

            $post = new Wpil_Model_Post($post_id);
            $post_scanned = !empty(get_post_meta($post_id, 'wpil_sync_report3', true));
            $inbound_internal = (int)get_post_meta($post_id, 'wpil_links_inbound_internal_count', true);
            $outbound_internal = (int)get_post_meta($post_id, 'wpil_links_outbound_internal_count', true);
            $outbound_external = (int)get_post_meta($post_id, 'wpil_links_outbound_external_count', true);
            $broken_links = Wpil_Error::getBrokenLinkCountByPostId($post_id);
            $ignored_pages = Wpil_Settings::get_completely_ignored_pages(); // todo create an is_ignored checker for posts
            $is_ignored = (!empty($ignored_pages) && in_array('post_' . $post_id, $ignored_pages)) ? true: false;

            $post_type = get_post_type($post_id);
            $page = (isset($_GET['paged']) && !empty($_GET['paged'])) ? '&paged=' . (int)$_GET['paged']: '';
            ?>
            <span class="wpil-link-stats-column-display wpil-link-stats-content">
                <?php if($post_scanned){ ?>
                <strong><?php _e('Links: ', 'wpil'); ?></strong>
                <span title="<?php _e('Inbound Internal Links', 'wpil'); ?>"><a target=_blank href="<?php echo admin_url("admin.php?post_id={$post_id}&page=link_whisper&type=inbound_suggestions_page&ret_url=" . base64_encode(admin_url("admin.php/edit.php?post_type={$post_type}{$page}"))); ?>"><span class="dashicons dashicons-arrow-down-alt"></span><span><?php echo $inbound_internal; ?></span></a></span>
                <span class="divider"></span>
                <span title="<?php _e('Outbound Internal Links', 'wpil'); ?>"><a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>"><span class="dashicons dashicons-external  <?php echo (!empty($outbound_internal)) ? 'wpil-has-outbound': ''; ?>"></span> <span><?php echo $outbound_internal; ?></span></a></span>
                <span class="divider"></span>
                <span title="<?php _e('Outbound External Links', 'wpil'); ?>"><span class="dashicons dashicons-admin-site-alt3 <?php echo (!empty($outbound_external)) ? 'wpil-has-outbound': ''; ?>"></span> <span><?php echo $outbound_external; ?></span></span>
                <span class="divider"></span>
                <?php if(!empty($broken_links)){ ?>
                <span title="<?php _e('Broken Links', 'wpil'); ?>"><a target=_blank href="<?php echo admin_url("admin.php?page=link_whisper&type=error&post_id={$post_id}"); ?>"><span class="dashicons dashicons-editor-unlink broken-links"></span> <span><?php echo $broken_links; ?></span></a></span>
                <?php }else{ ?>
                <span title="<?php _e('Broken Links', 'wpil'); ?>"><span class="dashicons dashicons-editor-unlink"></span> <span>0</span></span>
                <?php } ?>
                <?php }else{ 
                    if($is_ignored){
                        ?>
                    <strong><?php _e('Links: ', 'wpil'); ?></strong>
                        <span><?php echo sprintf(__('Post is being %s.', 'wpil'), '<a href="' . admin_url("admin.php?page=link_whisper_settings&tab=content-ignoring-settings") . '">' . __('ignored', 'wpil') . '</a>'); ?></span>
                        <?php
                    }else{
                    ?>
                    <?php $scan_link = (empty(get_option('wpil_has_run_initial_scan', false))) ? admin_url("admin.php?page=link_whisper"): $post->getLinks()->refresh; ?>
                    <strong><?php _e('Links: Not Scanned', 'wpil'); ?></strong>
                    <span title="<?php _e('Scan Links', 'wpil'); ?>"><a target=_blank href="<?php echo esc_url($scan_link); ?>"><span><?php _e('Scan Links', 'wpil'); ?></span> <span class="dashicons dashicons-update-alt wpil-refresh-links"></span></a></span>
                    <?php } ?>
                <?php } ?>
            </span>
        <?php
        }
	}

    /**
     * Filters the post content to make links open in new tabs if they don't already.
     * Differentiates between internal and external links.
     * @param string $content 
     * @return string $content 
     **/
    public static function open_links_in_new_tabs($content = ''){

        $open_all_intrnl = !empty(get_option('wpil_open_all_internal_new_tab', false));
        $open_all_extrnl = !empty(get_option('wpil_open_all_external_new_tab', false));

        if($open_all_intrnl || $open_all_extrnl){
            preg_match_all( '/<(a\s[^>]*?href=[\'"]([^\'"]*?)[\'"][^>]*?)>/', $content, $matches );

            foreach($matches[0] as $key => $link){
                // if the link already opens in a new tab, skip to the next link
                if(false !== strpos($link, 'target="_blank"')){
                    continue;
                }

                $internal = Wpil_Link::isInternal($matches[2][$key]);

                if($internal && $open_all_intrnl){
                    $new_link = str_replace($matches[1][$key], $matches[1][$key] . ' target="_blank"', $link);
                    $content = mb_ereg_replace(preg_quote($link), $new_link, $content);
                }elseif(!$internal && $open_all_extrnl){
                    $new_link = str_replace($matches[1][$key], $matches[1][$key] . ' target="_blank"', $link);
                    $content = mb_ereg_replace(preg_quote($link), $new_link, $content);
                }
            }
        }

        return $content;
    }

    /**
     * Filters the post content to make links open in new tabs if they don't already.
     * Differentiates between internal and external links.
     * @todo Remove if we have no trouble from the new system after version 2.2.0.
     * I'm currently just leaving this here in case I need a reference or an emergency rollback
     * @param string $content 
     * @return string $content 
     **/
    public static function add_link_attrs_old($content = ''){
        global $post;

        $open_all_intrnl    = !empty(get_option('wpil_open_all_internal_new_tab', false));
        $open_all_extrnl    = !empty(get_option('wpil_open_all_external_new_tab', false));
        $same_all_intrnl    = !empty(get_option('wpil_open_all_internal_same_tab', false));
        $same_all_extrnl    = !empty(get_option('wpil_open_all_external_same_tab', false));
        $no_follow          = !empty(get_option('wpil_add_nofollow', false));

        $ignore_nofollow_domains = ($no_follow) ? Wpil_Settings::getIgnoreNofollowDomains() : array();
        $nofollow_domains = array_diff(Wpil_Settings::getNofollowDomains(), $ignore_nofollow_domains); // skip the ignored nofollow domains
        $sponsored = Wpil_Settings::getSponsoredDomains();

        // don't apply link attributes to links with these classes
        $ignore_classes = array(
            'page-numbers',
            'navigation',
            'nav-link'
        );

        // allow users to filter the classes to ignore
        $ignore_classes = apply_filters('wpil_filter_link_attr_classes', $ignore_classes);

        // flip the classes for fast searching
        $ignore_classes = array_flip($ignore_classes);

        if( $open_all_intrnl || 
            $open_all_extrnl || 
            $no_follow || 
            $same_all_intrnl || 
            $same_all_extrnl || 
            !empty($sponsored) ||
            !empty($nofollow_domains))
        {
            $post_url = (!empty($post) && isset($post->ID)) ? get_the_permalink($post->ID): false;
            preg_match_all('/<(a\s[^>]*?href=[\'"]([^\'"]*?)[\'"][^>]*?)>/', $content, $matches);

            $external_site_links = array_map(function($url){ return wp_parse_url($url, PHP_URL_HOST); }, Wpil_SiteConnector::get_registered_sites());

            foreach($matches[0] as $key => $link){

                // if there are classes, check them to see if we should ignored the links
                $skip = false;
                if(false !== strpos($link, 'class=')){
                    preg_match('/class="([^"]*?)"/', $link, $classes);
                    if(!empty($classes)){
                        $classes = explode(' ', $classes[1]);
                        foreach($classes as $class){
                            if(isset($ignore_classes[$class])){
                                $skip = true;
                                break;
                            }
                        }
                    }
                }

                // if we found a class to skip
                if($skip){
                    // skip
                    continue;
                }

                $url = $matches[2][$key];

                // if this is a jump link
                if(Wpil_Report::isJumpLink($url, $post_url)){
                    // skip
                    continue;
                }

                $url_host = wp_parse_url($url, PHP_URL_HOST);
                $link_attrs = $matches[1][$key];
                $internal = Wpil_Link::isInternal($url);

                if( ( ($internal && $open_all_intrnl) || (!$internal && $open_all_extrnl) ) &&
                    false === strpos($link, 'target="_blank"'))
                {
                    $link_attrs .= ' target="_blank"';
                }

                if( $no_follow && !$internal &&                             // if we're supposed to add nofollow to external links and this is an external link
                    false === strpos($link_attrs, 'nofollow') &&            // and the link doesn't already have a nofollow attr
                    !in_array($url_host, $external_site_links, true) &&     // and if the link isn't pointing to a registered external site
                    !in_array($url_host, $ignore_nofollow_domains, true)    // and if the link isn't pointing to a domain that the user is ignoring
                ){
                    preg_match('/(rel="([^"]+)")/', $link_attrs, $rel);

                    // if there is a rel attr
                    if(!empty($rel)){
                        // insert the nofollow attr in the rel 
                        $updated = str_replace($rel[2], $rel[2] . ' nofollow', $rel[0]);
                        $link_attrs = str_replace($rel[0], $updated, $link_attrs);
                    }else{
                        $link_attrs .= ' rel="nofollow"';
                    }
                }

                if( !empty($nofollow_domains) &&                            // if we have domains to mark as nofollow
                    false === strpos($link_attrs, 'nofollow') &&            // and the link doesn't already have a nofollow attr
                    !in_array($url_host, $external_site_links, true) &&     // and if the link isn't pointing to a registered external site
                    in_array($url_host, $nofollow_domains, true)            // and if the link is pointing to a domain that the user is ignoring
                ){
                    preg_match('/(rel="([^"]+)")/', $link_attrs, $rel);

                    // if there is a rel attr
                    if(!empty($rel)){
                        // insert the nofollow attr in the rel 
                        $updated = str_replace($rel[2], $rel[2] . ' nofollow', $rel[0]);
                        $link_attrs = str_replace($rel[0], $updated, $link_attrs);
                    }else{
                        $link_attrs .= ' rel="nofollow"';
                    }
                }

                // if the user wants to set all internal or external links to open in the same tab
                if( ( ($internal && $same_all_intrnl) || (!$internal && $same_all_extrnl) ) && false !== strpos($link_attrs, 'target="_blank"')){
                    // remove _blank from the attr list
                    $link_attrs = str_replace('target="_blank"', '', $link_attrs);
                }

                if( !empty($sponsored) && !$internal &&                     // if we're supposed to add "sponsored" to external links and this is an external link
                    false === strpos($link_attrs, 'sponsored') &&           // and this link doesn't have a "sponsored" attr
                    in_array($url_host, $sponsored, true)                   // and the link's host is one of the sponsored ones
                ){
                    preg_match('/(rel="([^"]+)")/', $link_attrs, $rel);

                    // if there is a rel attr
                    if(!empty($rel)){
                        // insert the sponsored attr in the rel
                        $updated = str_replace($rel[2], $rel[2] . ' sponsored', $rel[0]);
                        $link_attrs = str_replace($rel[0], $updated, $link_attrs);
                    }else{
                        $link_attrs .= ' rel="sponsored"';
                    }
                }

                if($matches[1][$key] !== $link_attrs){
                    $new_link = str_replace($matches[1][$key], $link_attrs, $link);
                    $content = mb_ereg_replace(preg_quote($link), $new_link, $content);
                }
            }
        }

        return $content;
    }

    
    /**
     * Filters the post content to remove any attributes that Link Whisper has added to links
     * @param string $content 
     * @return string $content 
     **/
    public static function remove_link_whisper_attrs($content = ''){
        if(empty(Wpil_Settings::get_filter_out_frontend_attributes())){
            return $content;
        }

        $possible_attrs = array(
            'data-wpil-keyword-link="linked"',
            'data-wpil-monitor-id="[0-9]*"',
            'data-wpil="url"',
            'data-wpil-url-old=[\'"]([a-zA-Z0-9+=]*?)[\'"]',
            'class="wpil_keyword_link"',
            'wpil_keyword_link[\s]*?'
        );

        $content = preg_replace('/' . implode('|', $possible_attrs) . '/', '', $content);

        return $content;
    }

    /**
     * Filters the post content to make links open in new tabs if they don't already.
     * Differentiates between internal and external links.
     * @param string $content 
     * @return string $content 
     **/
    public static function add_link_attrs($content = ''){
        global $post;

        // if there are no active attrs
        if(empty(Wpil_Settings::get_active_link_attributes()) && !apply_filters('wpil_add_link_attr_allow_change_url', false)){
            // return the content
            return $content;
        }

        // don't apply link attributes to links with these classes
        $ignore_classes = array(
            'page-numbers',
            'navigation',
            'nav-link'
        );

        // allow users to filter the classes to ignore
        $ignore_classes = apply_filters('wpil_filter_link_attr_classes', $ignore_classes);

        // flip the classes for fast searching
        $ignore_classes = array_flip($ignore_classes);

        $post_url = (!empty($post) && isset($post->ID)) ? get_the_permalink($post->ID): false;
        preg_match_all('/<(a\s[^>]*?href=[\'"]([^\'"]*?)[\'"][^>]*?)>/', $content, $matches);

        foreach($matches[0] as $key => $link){

            // if there are classes, check them to see if we should ignored the links
            $skip = false;
            if(false !== strpos($link, 'class=')){
                preg_match('/class="([^"]*?)"/', $link, $classes);
                if(!empty($classes)){
                    $classes = explode(' ', $classes[1]);
                    foreach($classes as $class){
                        if(isset($ignore_classes[$class])){
                            $skip = true;
                            break;
                        }
                    }
                }
            }

            // if we found a class to skip
            if($skip){
                // skip
                continue;
            }

            $url = $matches[2][$key];

            // if this is a jump link
            if(Wpil_Report::isJumpLink($url, $post_url)){
                // skip
                continue;
            }

            $attrs = Wpil_Settings::get_active_link_attrs($url); // the attrs to be applied
            $link_attrs = $matches[1][$key]; // the attrs that already exist for the link
            $extracted_link_attrs = self::extract_link_attributes($link_attrs); // a neatly sorted array of the existing attrs, keyed by the attr name
            $rel_attrs = array();

            foreach($attrs as $attr){
                switch ($attr) {
                    case '_blank':
                        if(false === strpos($link, 'target="_blank"') && false === strpos($link, "target='_blank'")){
                            $link_attrs .= ' target="_blank"';
                        }
                        break;
                    case 'no_blank':
                        $link_attrs = str_replace(['target="_blank"', "target='_blank'"], '', $link_attrs);
                        break;
                    case 'dofollow':
                        if(isset($extracted_link_attrs['rel'])){
                            $s = array_search('nofollow', $extracted_link_attrs['rel']);
                            if(false !== $s){
                                unset($extracted_link_attrs['rel']);
                            }
                        }
                        $rel_attrs[] = $attr;
                        break;
                    case 'nofollow':
                        if(isset($extracted_link_attrs['rel'])){
                            $s = array_search('dofollow', $extracted_link_attrs['rel']);
                            if(false !== $s){
                                unset($extracted_link_attrs['rel']);
                            }
                        }
                        $rel_attrs[] = $attr;
                        break;
                    case 'sponsored':
                        $rel_attrs[] = $attr;
                        break;
                }
            }

            // if we have rel attrs to apply, and there were already some in the link
            if(!empty($rel_attrs)){
                // if the link already had some
                if(isset($extracted_link_attrs['rel']) && !empty($extracted_link_attrs['rel'])){
                    // find all the compatible ones and add them to the list
                    $rel_attrs = array_merge($rel_attrs, array_diff($extracted_link_attrs['rel'], $rel_attrs));
                    // and update the rel="" string from the link attrs
                    $link_attrs = mb_ereg_replace('rel="[^"]*?"', 'rel="' . implode(' ', $rel_attrs) . '"', $link_attrs);
                }else{
                    // if there aren't any rel attrs in the string, tack ours on the end of the string
                    $link_attrs .= ' rel="' . implode(' ', $rel_attrs) . '" ';
                }
            }

            // if the user reallllllyyyyyy wants to modify his link's URLs
            if(apply_filters('wpil_add_link_attr_allow_change_url', false)){
                $new_url = apply_filters('wpil_add_link_attr_change_url', $url, Wpil_Link::isInternal($url));
                if($url !== $new_url && !empty($url)){
                    $link_attrs = str_replace($url, $new_url, $link_attrs);
                }
            }

            if($matches[1][$key] !== $link_attrs){
                $new_link = str_replace($matches[1][$key], $link_attrs, $link);
                $content = mb_ereg_replace(preg_quote($link), $new_link, $content);
            }
        }

        return $content;
    }

    /**
     * Extracts known link attributes from supplied anchors
     **/
    public static function extract_link_attributes($link = ''){
        if(empty($link)){
            return array();
        }

        // keep track of the attrs LW supports so we can detect collisions
//        $supported_attrs = array('nofollow', 'dofollow', '_blank', 'sponsored');

        preg_match_all('/([a-zA-Z-]*)="([^"]+)"/', $link, $matches);

        $found_matches = array();
        foreach($matches[0] as $key => $match){
            $attr_name = $matches[1][$key];
            $attr_value = $matches[2][$key];

            // if this is a "rel" attr
            if($attr_name === 'rel'){
                // explode it on any spaces
                $bits = explode(' ', $attr_value);
                // check for collisions with supported attrs
//                $collisions = array_intersect($bits, $supported_attrs); // todo remove after version 2.2.5 if I can't find a use for this (collisions)
                // if there are collisions
//                if(!empty($collisions)){
                    // save a record of them
//                    $found_matches['collisions'] = $collisions;
//                }
                
                // and store the bits
                $found_matches[$attr_name] = $bits;
            }else{
                $found_matches[$attr_name] = $attr_value;
//                if(in_array($attr_value, $supported_attrs, true)){
//                    $found_matches['collisions'][] = $attr_value;
//                }
            }
        }

        return $found_matches;
    }

    /**
     * Adds icons to links in content to aid in navigation and accessibility.
     * 
     **/
    public static function add_link_icons($content){
        $add_to_external = Wpil_Settings::check_if_add_icon_to_link();
        $add_to_internal = Wpil_Settings::check_if_add_icon_to_link(true);

        if($add_to_external === 'never' && $add_to_internal === 'never'){
            return $content;
        }

        $ignore_html_external = Wpil_Settings::get_link_icon_html_exclude_tags();
        $ignore_html_internal = Wpil_Settings::get_link_icon_html_exclude_tags(true);

        preg_match_all('/<(a\s[^>]*?href=[\'"]([^\'"]*?)[\'"][^>]*?)>.*?<\/a>/', $content, $matches);

        if(!empty($matches)){
            // count the tag's ancestors so we can tell if we're supposed to skip adding an icon to it
            $tag_list = array();
            preg_match_all('/<a[ ]?[^>]*?>(?:.*?<\/a>)+?|<[a-zA-Z\/]+?[ ]?[^>]*?>/', $content, $tags);
            if(!empty($tags)){
                $link_ancestors = array();
                foreach($tags[0] as $key => $tag){
                    if(false === strpos($tag, '<a ')){
                        preg_match('/<[a-zA-Z0-9]*?\s|<[a-zA-Z0-9\/]*?>/', $tag, $id);
                        $id = trim(str_replace(['<', '/', '>'], '', $id[0]));

                        if(!isset($link_ancestors[$id])){
                            $link_ancestors[$id] = 0;
                        }
                
                        $link_ancestors[$id]++;
                    }else{
                        $tag_list[$tag] = $link_ancestors;
                    }
                }
            }

            $added_external = false;
            $added_internal = false;
            $offset = 0;
            foreach($matches[0] as $key => $link){
                $attrs = isset($matches[1][$key]) && !empty($matches[1][$key]) ? trim($matches[1][$key]): false;
                $url = isset($matches[2][$key]) && !empty($matches[2][$key]) ? trim($matches[2][$key]): false;
                $offset = Wpil_Word::mb_strpos($content, $link, $offset);

                // skip if there's no url
                if(empty($url)){
                    continue;
                }

                // first check if this is internal or external
                $internal = Wpil_Link::isInternal($url);

                if(!$internal){
                    if(!empty($ignore_html_external) && isset($tag_list[$link])){
                        foreach($ignore_html_external as $ignore){
                            if(isset($tag_list[$link][$ignore]) && $tag_list[$link][$ignore] % 2 !== 0){
                                continue 2;
                            }
                        }
                    }
                }else{
                    if(!empty($ignore_html_internal) && isset($tag_list[$link])){
                        foreach($ignore_html_internal as $ignore){
                            if(isset($tag_list[$link][$ignore]) && $tag_list[$link][$ignore] % 2 !== 0){
                                continue 2;
                            }
                        }
                    }
                }

                // if it's external and we're supposed to add icons to external links
                if(!$internal && $add_to_external !== 'never'){
                    if( $add_to_external === 'always' || // we're supposed to add the icon to all external
                        ($add_to_external === 'new_tab' && $attrs && false !== strpos($attrs, 'target="_blank"')) // Or we're supposed to add the icon to external links that open in new tabs
                    ){
                        $new_link = $link;
                        $link_close = Wpil_Word::mb_strpos($new_link, '</a>');
                        $new_start = mb_substr($new_link, 0, $link_close);
                        $new_end = mb_substr($new_link, $link_close);
                        $new_link = $new_start . self::get_link_icon(false, $added_external) . $new_end;
                        $content = mb_ereg_replace(preg_quote($link), $new_link, $content);
                        $added_external = true;
                    }
                }elseif($internal && $add_to_internal !== 'never'){
                    if( $add_to_internal === 'always' || // we're supposed to add the icon to all external
                        ($add_to_internal === 'new_tab' && $attrs && false !== strpos($attrs, 'target="_blank"')) // Or we're supposed to add the icon to external links that open in new tabs
                    ){
                        $new_link = $link;
                        $link_close = Wpil_Word::mb_strpos($new_link, '</a>');
                        $new_start = mb_substr($new_link, 0, $link_close);
                        $new_end = mb_substr($new_link, $link_close);
                        $new_link = $new_start . self::get_link_icon(true, $added_internal) . $new_end;
                        $content = mb_ereg_replace(preg_quote($link), $new_link, $content);
                        $added_internal = true;
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Gets the formatted icon that's inserted into a link
     **/
    public static function get_link_icon($internal = false, $return_reference = false){
        $icon_name = Wpil_Settings::get_link_icon($internal);
        $icon_title = Wpil_Settings::get_link_icon_title($internal);
        $icon_size = Wpil_Settings::get_link_icon_size($internal);
        $icon_color = Wpil_Settings::get_link_icon_color($internal);
        $icon_styles = array(
            'height' => $icon_size,
            'width' => $icon_size,
            'fill' => $icon_color,
            'stroke' => $icon_color,
            'display' => 'inline-block',
        );

        $icon = self::get_svg_icon($icon_name, $return_reference, $icon_styles);
        $formatted_icon = '<span class="wpil-link-icon" title="' . esc_attr($icon_title) . '" style="margin: 0 0 0 5px;">' . $icon . '</span>';

        return $formatted_icon;
    }

    /**
     * Gets SVG icon content so that we can use the HTML in PHP
     * 
     **/
    public static function get_svg_icon($name = '', $return_reference = false, $styles = array()){
        if(empty($name)){
            return '';
        }

        $path = '';
        $id = '';
        switch ($name){
            case 'new-tab-1':
                $path = '<g id="wpil-svg-new-tab-1-icon-path">
                            <g fill-rule="evenodd" stroke="none" stroke-width="1" transform="matrix(0.27272726,0,0,0.27272726,-1.6363636,-1.6363636)">
                                <g>
                                <path d="m 45.5,14 h 33 7.5 v 7.5 33 7.5 h -8 v 8 h 8 c 4.418278,0 8,-3.590712 8,-8 V 54.5 21.5 14 C 94,9.581722 90.409288,6 86,6 H 78.5 45.5 38 c -4.418278,0 -8,3.5907123 -8,8 v 8 h 8 V 14 Z M 6,38.008515 C 6,33.585535 9.578055,30 14.008515,30 h 47.98297 C 66.414466,30 70,33.578055 70,38.008515 v 47.98297 C 70,90.414466 66.421945,94 61.991485,94 H 14.008515 C 9.5855345,94 6,90.421945 6,85.991485 Z M 42,46 H 34 V 58 H 22 v 8 h 12 v 12 h 8 V 66 H 54 V 58 H 42 Z" />
                                </g>
                            </g>
                        </g>';
                $id = 'wpil-svg-new-tab-1-icon-path';
                break;
            case 'new-tab-2':
                $path = '<g id="wpil-svg-new-tab-2-icon-path" transform="translate(0,-4.8755901)">
                            <path d="M 23.707456,18.327668 V 15.405081 H 13.462555 c -0.807816,0 -1.463142,-0.654761 -1.463142,-1.461874 V 6.6300173 c 0,-0.8079967 -0.654445,-1.4618817 -1.461967,-1.4618817 H 3.2185296 c -0.8078122,0 -1.4631363,0.65476 -1.4631363,1.4618817 v 7.3123117 c 0,0.806825 -0.6541545,1.461584 -1.4628478,1.461584 v 2.923755 z" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="0.585091" />
                            <path d="m 23.707456,15.403913 c -0.807816,0 -1.463141,-0.653593 -1.463141,-1.461584 V 8.8232713 c 0,-0.80712 -0.655325,-1.461879 -1.464309,-1.461879 h -7.317451 c -0.808986,0 -1.463142,0.654759 -1.463142,1.461879 v 5.1190577 c 0,0.807991 0.655326,1.461584 1.464019,1.461584 z" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="0.585091" />
                            <path d="M 17.121717,9.5546463 V 13.20978 Z" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="0.585094" />
                            <path d="m 15.292428,11.382361 h 3.658577 z" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="0.585094" />
                        </g>';
                $id = 'wpil-svg-new-tab-2-icon-path';
                break;
            case 'outbound-1':
                $path = '<g id="wpil-svg-outbound-1-icon-path" transform="matrix(0.046875,0,0,0.046875,0.0234375,0.02343964)">
                            <path d="M 473.563,227.063 407.5,161 262.75,305.75 c -25,25 -49.563,41 -74.5,16 -25,-25 -9,-49.5 16,-74.5 L 349,102.5 283.937,37.406 c -14.188,-14.188 -2,-37.906 19,-37.906 h 170.625 c 20.938,0 37.938,16.969 37.938,37.906 v 170.688 c 0,20.937 -23.687,33.187 -37.937,18.969 z M 63.5,447.5 h 320 V 259.313 l 64,64 V 447.5 c 0,35.375 -28.625,64 -64,64 h -320 c -35.375,0 -64,-28.625 -64,-64 v -320 c 0,-35.344 28.625,-64 64,-64 h 124.188 l 64,64 H 63.5 Z" />
                        </g>';
                $id = 'wpil-svg-outbound-1-icon-path';
                break;
            case 'outbound-2':
                $path = '<g id="wpil-svg-outbound-2-icon-path" transform="matrix(1.2,0,0,1.2,-2.4,-2.4)">
                            <path d="m 20,18 c 0,1.103 -0.897,2 -2,2 H 6 C 4.897,20 4,19.103 4,18 V 6 C 4,4.897 4.897,4 6,4 h 7 V 2 H 6 C 3.794,2 2,3.794 2,6 v 12 c 0,2.206 1.794,4 4,4 h 12 c 2.206,0 4,-1.794 4,-4 v -7 h -2 z"/>
                            <polygon points="22,9 21.999,2 15,2 15,4 18.586,4 13.465,9.121 14.879,10.535 20,5.415 20,9 " />
                        </g>';
                $id = 'wpil-svg-outbound-2-icon-path';
                break;
            case 'outbound-3':
                $path = '<g id="wpil-svg-outbound-3-icon-path">
                            <g transform="matrix(0.92307697,0,0,0.92307697,-209.5794,-43.317149)">
                                <g fill-rule="evenodd" id="action" stroke="none" stroke-width="1" transform="translate(225.04432,44.926904)">
                                    <g transform="translate(-224.99998,-44.999995)">
                                        <g transform="translate(227,47)">
                                            <path d="m 21,12 c 0.552285,0 1,-0.447715 1,-1 V 5 C 22,4.4477152 21.552285,4 21,4 h -6 c -0.552285,0 -1,0.4477152 -1,1 0,0.5522847 0.447715,1 1,1 h 3.580002 l -6.287109,6.292893 c -0.390524,0.390524 -0.390524,1.02369 0,1.414214 0.390524,0.390524 1.02369,0.390524 1.414214,0 L 20,7.4190674 V 11 c 0,0.552285 0.447715,1 1,1 z" />
                                            <path d="m 20,18 v 6.008845 C 20,25.108529 19.110326,26 18.008845,26 H 1.991155 C 0.89147046,26 0,25.110326 0,24.008845 V 7.991155 C 0,6.8914705 0.88967395,6 1.991155,6 H 8 V 8 H 2 v 16 h 16 v -6 z" />
                                            <path d="M 24.008845,0 C 25.108529,0 26,0.88967395 26,1.991155 v 16.01769 C 26,19.108529 25.110326,20 24.008845,20 H 7.991155 C 6.8914705,20 6,19.110326 6,18.008845 V 1.991155 C 6,0.89147046 6.8896739,0 7.991155,0 Z M 8,2 H 24 V 18 H 8 Z" />
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </g>';
                $id = 'wpil-svg-outbound-3-icon-path';
                break;
            case 'outbound-4':
                $path = '<g id="wpil-svg-outbound-4-icon-path">
                            <g fill-rule="evenodd" id="action" stroke="none" stroke-width="1" transform="matrix(0.92307696,0,0,0.92307696,-1.8461539,-1.8461539)">
                                <g transform="translate(-270,-45)">
                                    <g transform="translate(272,47)">
                                        <path d="m 20,22 v 2.008845 C 20,25.108529 19.110326,26 18.008845,26 H 16 v -2 h 2 v -2 z m 0,-2 v -2 h -2 v 2 z m -6,6 h -3 v -2 h 3 z M 9,26 H 6 V 24 H 9 Z M 4,26 H 1.991155 C 0.89147046,26 0,25.110326 0,24.008845 V 22 h 2 v 2 H 4 Z M 0,20 v -3 h 2 v 3 z m 0,-5 v -3 h 2 v 3 z M 0,10 V 7.991155 C 0,6.8914705 0.88967395,6 1.991155,6 H 4 V 8 H 2 v 2 z M 6,6 H 8 V 8 H 6 Z" />
                                        <path d="M 24.008845,0 C 25.108529,0 26,0.88967395 26,1.991155 v 16.01769 C 26,19.108529 25.110326,20 24.008845,20 H 7.991155 C 6.8914705,20 6,19.110326 6,18.008845 V 1.991155 C 6,0.89147046 6.8896739,0 7.991155,0 Z M 8,2 H 24 V 18 H 8 Z" />
                                        <path d="m 21,12 c 0.552285,0 1,-0.447715 1,-1 V 5 C 22,4.4477152 21.552285,4 21,4 h -6 c -0.552285,0 -1,0.4477152 -1,1 0,0.5522847 0.447715,1 1,1 h 3.580002 l -6.287109,6.292893 c -0.390524,0.390524 -0.390524,1.02369 0,1.414214 0.390524,0.390524 1.02369,0.390524 1.414214,0 L 20,7.4190674 V 11 c 0,0.552285 0.447715,1 1,1 z" />
                                    </g>
                                </g>
                            </g>
                        </g>';
                $id = 'wpil-svg-outbound-4-icon-path';
                break;
            case 'outbound-5':
                $path = '<g id="wpil-svg-outbound-5-icon-path">
                            <g transform="matrix(0.3,0,0,0.3,-2.4,-2.4)">
                                <path d="M 73.788323,16 44.56401,45.224313 c -1.715534,1.715534 -1.718018,4.503944 2.89e-4,6.222251 1.71481,1.71481 4.504103,1.718436 6.222251,2.89e-4 L 80,22.233402 v 9.769759 C 80,34.20588 81.790861,36 84,36 c 2.204644,0 4,-1.789446 4,-3.996839 V 11.996839 C 88,10.896005 87.552712,9.8972231 86.829463,9.173436 86.105113,8.4484102 85.10633,8 84.003161,8 H 63.996839 C 61.79412,8 60,9.790861 60,12 c 0,2.204644 1.789446,4 3.996839,4 z M 88,56 V 36.985151 78.029699 C 88,83.536144 84.032788,88 79.132936,88 H 16.867063 C 11.96992,88 8,83.527431 8,78.029699 V 17.970301 C 8,12.463856 11.967212,8 16.867063,8 H 59.566468 40 c 2.209139,0 4,1.790861 4,4 0,2.209139 -1.790861,4 -4,4 H 18.277794 C 17.005287,16 16,17.194737 16,18.668519 V 77.331481 C 16,78.778664 17.019803,80 18.277794,80 H 77.722206 C 78.994713,80 80,78.805263 80,77.331481 V 56 c 0,-2.209139 1.790861,-4 4,-4 2.209139,0 4,1.790861 4,4 z" />
                            </g>
                        </g>';
                $id = 'wpil-svg-outbound-5-icon-path';
                break;
            case 'outbound-6':
                $path = '<g id="wpil-svg-outbound-6-icon-path">
                            <g fill-rule="evenodd" transform="matrix(0.5959368,0,0,0.5959368,-2.3837472,-2.2212188)">
                                <path d="m 40,24.965598 c 0,-0.552285 0.447715,-1 1,-1 0.552285,0 1,0.447715 1,1 0,3.469059 -0.129275,6.918922 -0.387834,10.349539 -0.334407,4.436897 -3.860867,7.963515 -8.297748,8.298115 C 29.895466,43.871087 26.457309,44 23,44 19.540669,44 16.100512,43.870937 12.679584,43.6128 8.2429399,43.278024 4.7167289,39.751579 4.3822446,35.314912 4.127407,31.934695 4,28.519214 4,25.068519 4,21.588185 4.1296049,18.127086 4.3888246,14.685273 4.723001,10.248145 8.2495818,6.7212457 12.68668,6.3866649 16.10527,6.128885 19.543061,6 23,6 23.552285,6 24,6.4477152 24,7 24,7.5522847 23.552285,8 23,8 19.593137,8 16.205509,8.1270043 12.837064,8.381003 9.3859872,8.6412326 6.6430914,11.384376 6.3831764,14.835476 6.1277288,18.227205 6,21.638202 6,25.068519 6,28.469267 6.1255362,31.834597 6.3765849,35.164557 6.6367394,38.615298 9.3793476,41.358088 12.830071,41.61847 16.200821,41.87282 19.590779,42 23,42 c 3.407228,0 6.795216,-0.127032 10.164018,-0.381085 3.450907,-0.260245 6.19371,-3.00317 6.453804,-6.454089 C 39.872604,31.784331 40,28.384605 40,24.965598 Z M 40.834267,5.7513115 c -0.09391,-0.015809 -0.190403,-0.024039 -0.288813,-0.024039 H 30.543365 c -0.552285,0 -1,-0.4477152 -1,-1 0,-0.5522847 0.447715,-1 1,-1 h 10.002089 c 2.058516,0 3.727273,1.6687569 3.727273,3.7272728 v 9.9999997 c 0,0.552285 -0.447715,1 -1,1 -0.552285,0 -1,-0.447715 -1,-1 V 7.4545455 c 0,-0.098534 -0.0083,-0.1951415 -0.0241,-0.2891685 L 30.340158,19.070833 C 29.397229,20.013522 27.983195,18.59913 28.926123,17.65644 Z" fill-rule="nonzero" />
                            </g>
                        </g>';
                $id = 'wpil-svg-outbound-6-icon-path';
                break;
            case 'outbound-7':
                $path = '<g id="wpil-svg-outbound-7-icon-path" fill="none" clip-path="url(#clip0_31_188)">
                            <path d="M9.16724 14.8891L20.1672 3.88908" stroke-linecap="round"/>
                            <path d="M13.4497 3.53554L20.5208 3.53554L20.5208 10.6066" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17.5 13.5L17.5 16.26C17.5 17.4179 17.5 17.9968 17.2675 18.4359C17.0799 18.7902 16.7902 19.0799 16.4359 19.2675C15.9968 19.5 15.4179 19.5 14.26 19.5L7.74 19.5C6.58213 19.5 6.0032 19.5 5.56414 19.2675C5.20983 19.0799 4.92007 18.7902 4.73247 18.4359C4.5 17.9968 4.5 17.4179 4.5 16.26L4.5 9.74C4.5 8.58213 4.5 8.0032 4.73247 7.56414C4.92007 7.20983 5.20982 6.92007 5.56414 6.73247C6.0032 6.5 6.58213 6.5 7.74 6.5L11 6.5" stroke-linecap="round"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_31_188">
                                <rect fill="white" height="24" width="24"/>
                            </clipPath>
                        </defs>';
                $id = 'wpil-svg-outbound-7-icon-path';
                break;
            case 'outbound-8':
                $path = '<g id="wpil-svg-outbound-8-icon-path">
                            <path d="M 19.318245,10.90244 H 17.12283 V 8.429854 L 13.552391,12.000586 12.000586,10.44761 15.569855,6.87922 H 13.097562 V 4.6840981 h 5.488683 c 0.402439,0 0.732,0.3286829 0.732,0.7308291 z" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="0.585367" />
                            <path d="M 21.512196,0.29268351 H 9.8054636 c -1.211999,0 -2.195122,0.98312199 -2.195122,2.19512209 V 14.195708 c 0,1.212 0.983123,2.195122 2.195122,2.195122 H 21.512196 c 1.212293,0 2.195122,-0.983122 2.195122,-2.195122 V 2.4878056 c 0,-1.2120001 -0.982829,-2.19512209 -2.195122,-2.19512209 z m 0,13.53687849 c 0,0.201073 -0.164488,0.366146 -0.364976,0.366146 H 10.171611 c -0.2016594,0 -0.3661474,-0.165073 -0.3661474,-0.366146 V 2.8539517 c 0,-0.2007804 0.164488,-0.3661461 0.3661474,-0.3661461 H 21.14722 c 0.200781,0 0.364976,0.1653657 0.364976,0.3661461 z" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="0.585367" />
                            <path d="m 14.195708,16.39083 v 4.75639 c 0,0.200781 -0.164487,0.364976 -0.365853,0.364976 H 2.8539517 c -0.2007804,0 -0.3661461,-0.164488 -0.3661461,-0.364976 V 10.17161 c 0,-0.2007804 0.1653657,-0.3661464 0.3661461,-0.3661464 H 7.6100496 V 7.610342 h -5.122244 c -1.2120001,0 -2.19512209,0.9831219 -2.19512209,2.1951216 V 21.512196 c 0,1.212293 0.98312199,2.195122 2.19512209,2.195122 H 14.195708 c 1.212,0 2.195122,-0.982829 2.195122,-2.195122 V 16.39083 Z" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="0.585367" />
                        </g>';
                $id = 'wpil-svg-outbound-8-icon-path';
                break;
        }

        if(!empty($path)){
            $custom_style = '';
            if(!empty($styles)){
                $custom_style = Wpil_Toolbox::validate_inline_styles($styles, true);
            }

            $svg = '<svg width="24" height="24" ' . $custom_style . ' viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">';
            if($return_reference){
                $svg .= '<use href="#' . $id . '"></use>';
            }else{
                $svg .= $path;
            }
            $svg .= '</svg>';
        }

        return $svg;
    }

    /**
     * Gets a list of the possible SVG icons
     * 
     **/
    public static function get_svg_icon_names(){
        return array(
            'new-tab-1',
            'new-tab-2',
            'outbound-1',
            'outbound-2',
            'outbound-3',
            'outbound-4',
            'outbound-5',
            'outbound-6',
            'outbound-7',
            'outbound-8');
    }

    public static function fixCollation($table)
    {
        global $wpdb;
        $table_status = $wpdb->get_results("SHOW TABLE STATUS where name like '$table'");
        if (empty($table_status[0]->Collation) || $table_status[0]->Collation != 'utf8mb4_unicode_ci') {
            $wpdb->query("alter table $table convert to character set utf8mb4 collate utf8mb4_unicode_ci");
        }
    }

    public static function verify_nonce($key)
    {
        $user = wp_get_current_user();
        if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $user->ID . $key)){
            wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('There was an error in processing the data, please reload the page and try again.', 'wpil'),
                )
            ));
        }
    }

    /**
     * Checks if there's a function hooked to a particular action or filter.
     * We have to flip through the hooked functions because a lot of the methods use instantiated objects.
     * Currently only checks for object methods since has_action is all we need for inline functions
     *
     * @param string $tag The hook/filter name that the function is hooked to
     * @param string $object The object who's method we're removing from the hook/filter
     * @param string $function The object method that we're removing from the hook/filter
     * @param int|bool $hook_priority The priority of the function that we're removing
     **/
    public static function has_hooked_function($tag, $object, $function, $hook_priority = false){
        global $wp_filter;
        $priority = intval($hook_priority);

        // if the hook that we're looking for does exist and at the priority we're looking for
        if( isset($wp_filter[$tag]) &&
            isset($wp_filter[$tag]->callbacks) &&
            !empty($wp_filter[$tag]->callbacks))
        {
            if( isset($wp_filter[$tag]->callbacks[$priority]) &&
                !empty($wp_filter[$tag]->callbacks[$priority]))
            {
                // look over all the callbacks in the priority we're looking in
                foreach($wp_filter[$tag]->callbacks[$priority] as $key => $data)
                {
                    // if the current item is the callback we're looking for
                    if(isset($data['function']) && (is_a($data['function'][0], $object) || $data['function'][0] === $object) && $data['function'][1] === $function){
                        // return that we've found it
                        return true;
                    }
                }
            }elseif(false === $hook_priority){
                // if there's no priority, flip through all the posssible priorities
                foreach($wp_filter[$tag]->callbacks as $priority => $dat)
                {
                    foreach($wp_filter[$tag]->callbacks[$priority] as $key => $data)
                    {
                        // if the current item is the callback we're looking for
                        if(isset($data['function']) && (is_a($data['function'][0], $object) || $data['function'][0] === $object) && $data['function'][1] === $function){
                            // return that we've found it
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Removes a hooked function from the wp hook or filter.
     * We have to flip through the hooked functions because a lot of the methods use instantiated objects
     *
     * @param string $tag The hook/filter name that the function is hooked to
     * @param string $object The object who's method we're removing from the hook/filter
     * @param string $function The object method that we're removing from the hook/filter
     * @param int $priority The priority of the function that we're removing
     **/
    public static function remove_hooked_function($tag, $object, $function, $priority){
        global $wp_filter;
        $priority = intval($priority);

        // if the hook that we're looking for does exist and at the priority we're looking for
        if( isset($wp_filter[$tag]) &&
            isset($wp_filter[$tag]->callbacks) &&
            !empty($wp_filter[$tag]->callbacks) &&
            isset($wp_filter[$tag]->callbacks[$priority]) &&
            !empty($wp_filter[$tag]->callbacks[$priority]))
        {
            // look over all the callbacks in the priority we're looking in
            foreach($wp_filter[$tag]->callbacks[$priority] as $key => $data)
            {
                // if the current item is the callback we're looking for
                if(isset($data['function']) && (is_a($data['function'][0], $object) || $data['function'][0] === $object) && $data['function'][1] === $function){
                    // remove the callback
                    unset($wp_filter[$tag]->callbacks[$priority][$key]);
                }
            }
        }
    }

    /**
     * Removes all functions that are of a lower priority than the one we supply.
     * If we're working on a main stack process, and we need to loop back for something,
     * there shouldn't be a need to re-call all the other functions that have gone before
     *
     * @param string $tag The hook/filter name that the functions are hooked to
     * @param int $priority_limit The limit on how high of priority hooks we should remove
     **/
    public static function remove_lower_priority_hooked_functions($tag, $priority_limit = 0){
        global $wp_filter;
        $priority_limit = intval($priority_limit);

        // if the hook that we're looking for does exist and at the priority we're looking for
        if( isset($wp_filter[$tag]) &&
            isset($wp_filter[$tag]->callbacks) &&
            !empty($wp_filter[$tag]->callbacks))
        {
            foreach($wp_filter[$tag]->callbacks as $priority => $callbacks){
                if($priority < $priority_limit){
                    unset($wp_filter[$tag]->callbacks[$priority]);
                }
            }
        }
    }

    /**
     * Checks to see if one of the calling ancestors of the current function is what we're looking for
     **/
    public static function has_ancestor_function($function_name = '', $class_name = ''){
        if(empty($function_name)){
            return false;
        }

        $call_stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        if(!empty($call_stack)){
            foreach($call_stack as $call){
                if( isset($call['function']) && $call['function'] === $function_name &&
                    (empty($class_name) || isset($call['class']) && $call['class'] === $class_name)
                ){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Updates the WP option cache independently of the update_options functionality.
     * I've found that for some users the cache won't update and that keeps some option based processing from working.
     * The code is mostly pulled from the update_option function
     *
     * @param string $option The name of the option that we're saving.
     * @param mixed $value The option value that we're saving.
     **/
    public static function update_option_cache($option = '', $value = ''){
        $option = trim( $option );
        if ( empty( $option ) ) {
            return false;
        }

        $serialized_value = maybe_serialize( $value );
        $alloptions = wp_load_alloptions( true );
        if ( isset( $alloptions[ $option ] ) ) {
            $alloptions[ $option ] = $serialized_value;
            wp_cache_set( 'alloptions', $alloptions, 'options' );
        } else {
            wp_cache_set( $option, $serialized_value, 'options' );
        }
    }

    /**
     * Makes sure that the transients are set and that the option cache is updated when data is saved.
     * There are some cases of the transients not sticking, even though they are supposed to be active.
     * I believe the issue is object caching catching the update information, and then not passing it back when we ask for it.
     * 
     * Uses the same arguments as the WP transient function
     **/
    public static function set_transient($transient, $value, $expiration = 0) {

        $expiration         = (int) $expiration;
        $transient_timeout  = '_transient_timeout_' . $transient;
        $transient_option   = '_transient_' . $transient;

        if(false === get_option($transient_option)){
            $autoload = 'yes';
            if($expiration){
                $autoload = 'no';
                add_option($transient_timeout, time() + $expiration, '', 'no');
            }
            $result = add_option($transient_option, $value, '', $autoload);
        }else{
            /*
            * If expiration is requested, but the transient has no timeout option,
            * delete, then re-create transient rather than update.
            */
            $update = true;

            if($expiration){
                if(false === get_option($transient_timeout)){
                    delete_option($transient_option);
                    add_option($transient_timeout, time() + $expiration, '', 'no');
                    $result = add_option($transient_option, $value, '', 'no');
                    $update = false;
                }else{
                    update_option($transient_timeout, time() + $expiration);
                    self::update_option_cache($transient_timeout, time() + $expiration);
                }
            }

            if($update){
                $result = update_option($transient_option, $value);
                self::update_option_cache($transient_option, $value);
            }
        }

        return $result;
    }

    /**
     * Deletes all Link Whisper related data on plugin deletion
     **/
    public static function delete_link_whisper_data(){
        global $wpdb;

        // if we're not really sure that the user wants to delete all data, exit
        if('1' !== get_option('wpil_delete_all_data', false)){
            return;
        }

        // create a list of all possible tables
        $tables = self::getDatabaseTableList();

        // go over the list of tables and delete all tables that exist
        foreach($tables as $table){
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if($table_exists === $table){
                $wpdb->query("DROP TABLE {$table}");
            }
        }

        // delete all of the settings from the options table
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE `option_name` LIKE 'wpil_%'");

        // clear all of the transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_wpil_%' OR `option_name` LIKE '_transient_timeout_wpil_%'");

        // delete all of the link metafields
        Wpil_Report::clearMeta();
    }

    /**
     * Checks to see if we're over the time limit.
     * 
     * @param int $time_pad The amount of time in advance of the PHP time limit that is considered over the time limit
     * @param int $max_time The absolute time limit that we'll wait for the current process to complete
     * @return bool
     **/
    public static function overTimeLimit($time_pad = 0, $max_time = null){
        $limit = ini_get( 'max_execution_time' );

        // if there is no limit or the limit is larger than 90 seconds
        if(empty($limit) || $limit === '-1' || $limit > 90){
            // create a self imposed limit so the user know LW is still working on looped actions
            $limit = 90;
        }

        // filter the limit so users with special constraints can make adjustments
        $limit = apply_filters('wpil_filter_processing_time_limit', $limit);

        // if the exit time pad is less than the limit
        if($limit < $time_pad){
            // default to a 5 second pad
            $time_pad = 5;
        }

        // get the current time
        $current_time = microtime(true);

        // if we've been running for longer than the PHP time limit minus the time pad, OR
        // a max time has been set and we've passed it
        if( ($current_time - WPIL_STATUS_PROCESSING_START) > ($limit - $time_pad) || 
            $max_time !== null && ($current_time - WPIL_STATUS_PROCESSING_START) > $max_time)
        {
            // signal that we're over the time limit
            return true;
        }else{
            return false;
        }
    }

    /**
     * Creates the database tables so we're sure that they're all set.
     * I'll still use the old method of creation for a while as a fallback.
     * But this will make LW more plug-n-play
     **/
    public static function createDatabaseTables(){
        Wpil_ClickTracker::prepare_table();
        Wpil_Error::prepareTable(false);
        Wpil_Error::prepareIgnoreTable();
        Wpil_Keyword::prepareTable(); // also prepares the possible links table
        Wpil_TargetKeyword::prepareTable();
        Wpil_URLChanger::prepareTable();
        Wpil_Widgets::prepare_table();
        Wpil_Report::prepare_link_tracking_table();

        // search console table not included because it's explicitly activated by the user
        // linked site data table also not included because it's explicitly activated by the user
    }

    /**
     * Returns an array of all the tables created by Link Whisper.
     * @param bool $should_prefix Should the returned tables have the site's database prefix attached?
     * @return array
     **/
    public static function getDatabaseTableList($should_prefix = true){
        global $wpdb;

        if($should_prefix){
            $prefix = $wpdb->prefix;
        }else{
            $prefix = '';
        }

        return array(
            "{$prefix}wpil_broken_links",
            "{$prefix}wpil_ignore_links",
            "{$prefix}wpil_click_data",
            "{$prefix}wpil_keywords",
            "{$prefix}wpil_keyword_links",
            "{$prefix}wpil_keyword_select_links",
            "{$prefix}wpil_report_links",
            "{$prefix}wpil_tracked_link_ids",
            "{$prefix}wpil_search_console_data",
            "{$prefix}wpil_site_linking_data",
            "{$prefix}wpil_target_keyword_data",
            "{$prefix}wpil_urls",
            "{$prefix}wpil_url_links",
            "{$prefix}wpil_related_posts",
        );
    }

    /**
     * Helper function to set WP to not use external object caches when doing AJAX
     **/
    public static function ignore_external_object_cache($ignore_ajax = false){
        if( (defined('DOING_AJAX') && DOING_AJAX || $ignore_ajax) &&
            function_exists('wp_using_ext_object_cache') &&
            file_exists( WP_CONTENT_DIR . '/object-cache.php') &&
            wp_using_ext_object_cache())
        {
            if(!defined('WP_REDIS_DISABLED') && defined('WP_REDIS_FILE')){
                define('WP_REDIS_DISABLED', true);
            }
            wp_using_ext_object_cache(false);
        }
    }

    /**
     *  Helper function to remove any problem hooks interfering with our AJAX requests
     * 
     * @param bool $ignore_ajax True allows the removing of hooks when ajax is not running
     **/
    public static function remove_problem_hooks($ignore_ajax = false){
        $admin_ajax = is_admin() && defined('DOING_AJAX') && DOING_AJAX;

        if( ($admin_ajax || $ignore_ajax) && defined('TOC_VERSION')){
            remove_all_actions('wp_enqueue_scripts');
        }
    }

    /**
     * Tracks actions that have taken place so we can tell if something in a distantly connected part of Link Whisper happened
     * 
     * @param string $action The name we've given to the action that's happened
     * @param mixed $value The value of the action that we're watching
     * @param bool $overwrite_true Should we overwrite TRUE results with whatever we currently have? By default, we don't so we can track if a result happened somewhere
     **/
    public static function track_action($action = '', $value = null, $overwrite_true = false){
        if(empty($action) || !is_string($action)){
            return;
        }

        // if the action has happened AND we should overwrite that status with the most recent one
        if(isset(self::$action_tracker[$action]) && !empty(self::$action_tracker[$action]) && $overwrite_true){
            self::$action_tracker[$action] = $value;
        }elseif(!array_key_exists($action, self::$action_tracker)){ // if the event has not happened yet
            self::$action_tracker[$action] = $value;
        }elseif(array_key_exists($action, self::$action_tracker) && empty(self::$action_tracker[$action]) && !empty($value)){ // if the event has been attempted and hasn't succeeded yet, but we now have a record of it happening!
            self::$action_tracker[$action] = $value;
        }
    }

    public static function action_happened($action = '', $return_result = true){
        if(empty($action) || !is_string($action)){
            return false;
        }

        $logged = array_key_exists($action, self::$action_tracker);

        if(!$logged){
            return false;
        }

        return ($return_result) ? self::$action_tracker[$action]: $logged;
    }

    public static function clear_tracked_action($action = ''){
        if(empty($action) || !is_string($action)){
            return;
        }

        if(array_key_exists($action, self::$action_tracker)){
            unset(self::$action_tracker[$action]);
        }
    }
}