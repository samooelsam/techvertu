<?php
    global $shortcode_tags;

    // get the license status data
    $license    = get_option(WPIL_OPTION_LICENSE_KEY, '');
    $status     = get_option(WPIL_OPTION_LICENSE_STATUS);
    $last_error = get_option(WPIL_OPTION_LICENSE_LAST_ERROR, '');

    // get the current licensing state
    $licensing_state;
    if(empty($license) && empty($last_error) || ('invalid' === $status && 'Deactivated manually' === $last_error)){
        $licensing_state = 'not_activated';
    }elseif(!empty($license) && 'valid' === $status){
        $licensing_state = 'activated';
    }else{
        $licensing_state = 'error';
    }

    // create titles for the license statuses
    $status_titles   = array(
        'not_activated' => __('License Not Active', 'wpil'),
        'activated'     => __('License Active', 'wpil'),
        'error'         => __('License Error', 'wpil')
    );

    // create some helpful text to tell the user what's going on
    $status_messages = array(
        'not_activated' => __('Please enter your Link Whisper License Key to activate Link Whisper.', 'wpil'),
        'activated'     => __('Congratulations! Your Link Whisper License Key has been confirmed and Link Whisper is now active!', 'wpil'),
        'error'         => $last_error
    );

    // get if the user has enabled site interlinking
    $site_linking_enabled = get_option('wpil_link_external_sites', false);

    // get if the user has limited the number of links per post
    $max_links_per_post = get_option('wpil_max_links_per_post', 0);

    // get if the user has limited the number of inbound links per post
    $max_inbound_links_per_post = get_option('wpil_max_inbound_links_per_post', 0);

    // get the max age of posts that links will be inserted in
    $max_linking_age = get_option('wpil_max_linking_age', 0);

    // get the max age of posts that links will be inserted in
    $max_suggestion_count = get_option('wpil_max_suggestion_count', 0);

    // get if we're not tracking user ips with the click tracking
    $disable_ip_tracking = get_option('wpil_disable_click_tracking_info_gathering', false);

    // get the section skip type
    $skip_type = Wpil_Settings::getSkipSectionType();

    // get if we're filtering staging
    $filter_staging_url = !empty(get_option('wpil_filter_staging_url', false));

    // get the content formatting level
    $formatting_level = Wpil_Settings::getContentFormattingLevel();

    // get if the user is ignoring any tags from linking
    $ignored_linking_tags = Wpil_Settings::getIgnoreLinkingTags();

    // get the max suggestion anchor length
    $max_suggestion_length = Wpil_Settings::getSuggestionMaxAnchorSize();

    // get the min suggestion anchor length
    $min_suggestion_length = Wpil_Settings::getSuggestionMinAnchorSize();

    // if WP Recipes is active, get the selected field list
    $wp_recipe_fields = (defined('WPRM_POST_TYPE')) ? Wpil_Editor_WPRecipe::get_selected_fields(): array();

    $external_link_icon = Wpil_Settings::check_if_add_icon_to_link();
    $selected_external_icon = Wpil_Settings::get_link_icon();
    $external_link_icon_size = Wpil_Settings::get_link_icon_size();
    $external_link_icon_color = Wpil_Settings::get_link_icon_color();
    $external_link_icon_title = Wpil_Settings::get_link_icon_title();
    $external_link_icon_html_ignore = Wpil_Settings::get_link_icon_html_exclude_tags();
    $external_link_styles = array('height' => $external_link_icon_size, 'width' => $external_link_icon_size, 'fill' => $external_link_icon_color, 'stroke' => $external_link_icon_color, 'display' => 'inline-block');
    $external_settings_visible = ($external_link_icon === 'never') ? 'style="display:none"': '';
    
    $internal_link_icon = Wpil_Settings::check_if_add_icon_to_link(true);
    $selected_internal_icon = Wpil_Settings::get_link_icon(true);
    $internal_link_icon_size = Wpil_Settings::get_link_icon_size(true);
    $internal_link_icon_color = Wpil_Settings::get_link_icon_color(true);
    $internal_link_icon_title = Wpil_Settings::get_link_icon_title(true);
    $internal_link_icon_html_ignore = Wpil_Settings::get_link_icon_html_exclude_tags(true);
    $internal_link_styles = array('height' => $internal_link_icon_size, 'width' => $internal_link_icon_size, 'fill' => $internal_link_icon_color, 'stroke' => $internal_link_icon_color, 'display' => 'inline-block');
    $internal_settings_visible = ($internal_link_icon === 'never') ? 'style="display:none"': '';

    $related_posts_settings = Wpil_Settings::get_related_post_settings();
    $related_posts_types = Wpil_Settings::get_related_posts_active_post_types();
    $related_active = $related_posts_settings['active'];
    $thumbnail_active = $related_posts_settings['use_thumbnail'];
    $rp_layout_display = $related_posts_settings['widget_layout']['display'];
    $rp_layout_count = (int)$related_posts_settings['widget_layout']['count'];
    $rp_full_style = $related_posts_settings['styling']['full'];
    $rp_mobile_style = $related_posts_settings['styling']['mobile'];

    // get the current roles that are available on the site
    $active_roles = Wpil_Settings::get_available_roles(true);
    // and the roles that the user doesn't want to suggest links to
    $ignore_linking_roles = Wpil_Settings::get_ignore_linking_roles();

    // get the currently open setting tab. Default to "General Settings" if no tab is selected
    $current_tab = (isset($_GET['tab']) && !empty($_GET['tab'])) ? $_GET['tab']: 'general-settings';
?>
<style type="text/css">
    #frmSaveSettings .wpil-<?php echo $current_tab; ?>{
        display: table-row;
    }
</style>
<div class="wrap wpil_styles" id="settings_page">
    <?=Wpil_Base::showVersion()?>
    <h1 class="wp-heading-inline"><?php _e('Link Whisper Settings', 'wpil'); ?></h1>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <h2 class="nav-tab-wrapper" style="margin-bottom:1em;">
                <a class="nav-tab <?php echo ('general-settings' === $current_tab) ? 'nav-tab-active': ''; ?>" id="wpil-general-settings" href="#"><?php _e('General Settings', 'wpil'); ?></a>
                <a class="nav-tab <?php echo ('content-ignoring-settings' === $current_tab) ? 'nav-tab-active': ''; ?>" id="wpil-content-ignoring-settings" href="#"><?php _e('Content Ignoring', 'wpil'); ?></a>
                <a class="nav-tab <?php echo ('domain-settings' === $current_tab) ? 'nav-tab-active': ''; ?>" id="wpil-domain-settings" href="#"><?php _e('Domain Settings', 'wpil'); ?></a>
                <a class="nav-tab <?php echo ('advanced-settings' === $current_tab) ? 'nav-tab-active': ''; ?>" id="wpil-advanced-settings" href="#"><?php _e('Advanced Settings', 'wpil'); ?></a>
                <a class="nav-tab <?php echo ('related-posts-settings' === $current_tab) ? 'nav-tab-active': ''; ?>" id="wpil-related-posts-settings" href="#"><?php _e('Related Post Settings', 'wpil'); ?></a>
                <a class="nav-tab <?php echo ('licensing-settings' === $current_tab) ? 'nav-tab-active': ''; ?>" id="wpil-licensing-settings" href="#"><?php _e('Licensing', 'wpil'); ?></a>
            </h2>
            <div id="post-body-content" style="position: relative;">
                <?php
                    // if the user has authed GSC, check the status
                    if(Wpil_Settings::HasGSCCredentials()){
                        Wpil_SearchConsole::refresh_auth_token();
                        $authenticated = Wpil_SearchConsole::is_authenticated();
                        $gsc_profiles = Wpil_SearchConsole::get_set_profiles();
                        if(empty($gsc_profiles)){
                            $gsc_profile = Wpil_SearchConsole::get_site_profile();
                            if(!empty($gsc_profile)){
                                $gsc_profiles[] = $gsc_profile;
                            }
                        }
                        $profile_not_found = get_option('wpil_gsc_profile_not_easily_found', false);
                    }else{
                        $authenticated = false;
                        $gsc_profiles = array();
                        $profile_not_found = false;
                    }
                ?>
                <?php if (isset($_REQUEST['success']) && !isset($_REQUEST['access_valid'])) : ?>
                    <div class="notice update notice-success" id="wpil_message" >
                        <p><?php _e('The Link Whisper Settings have been updated successfully!', 'wpil'); ?></p>
                    </div>
                <?php endif; ?>
                <?php if($message = get_transient('wpil_gsc_access_status_message')){
                    if($message['status']){
                        if(!empty($gsc_profiles)){?>
                            <div class="notice update notice-success" id="wpil_message" >
                                <p><?php echo esc_html($message['text']); ?></p>
                            </div><?php
                        }
                    }else{?>
                        <div class="notice update notice-error" id="wpil_message" >
                        <p><?php echo esc_html($message['text']); ?></p>
                    </div>
                    <?php
                    }
                    ?>
                <?php } ?>
                <?php if(isset($_REQUEST['broken_link_scan_cancelled']) && $message = get_transient('wpil_clear_error_checker_message')){ ?>
                    <div class="notice update notice-success" id="wpil_message" >
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                <?php } ?>
                <?php if(isset($_REQUEST['database_creation_activated']) && $message = get_transient('wpil_database_creation_message')){ ?>
                    <div class="notice update notice-success" id="wpil_message" >
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                <?php } ?>
                <?php if(isset($_REQUEST['database_update_activated']) && $message = get_transient('wpil_database_update_message')){ ?>
                    <div class="notice update notice-success" id="wpil_message" >
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                <?php } ?>
                <?php if(isset($_REQUEST['table_item_count_reset']) && $message = get_transient('wpil_table_item_count_reset_message')){ ?>
                    <div class="notice update notice-success" id="wpil_message" >
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                <?php } ?>
                <?php if(array_key_exists('user_data_deleted', $_REQUEST) && $message = get_transient('wpil_user_data_delete_message')){ ?>
                    <?php if(!empty($_REQUEST['user_data_deleted'])){ ?>
                    <div class="notice update notice-success" id="wpil_message" >
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                    <?php }else{ ?>
                    <div class="notice update notice-error" id="wpil_message" >
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                    <?php } ?>
                <?php } ?>
                <?php if(!empty($authenticated) && empty($gsc_profiles)){?>
                    <div class="notice update notice-error" id="wpil_message" >
                        <p><?php _e('Connection Error: Either the selected Google account doesn\'t have Search Console access for this site, or Link Whisper is having trouble selecting this site. If you\'re sure the selected account has access to this site\'s GSC data, please select this site\'s profile from the "Currently Selected GSC Profile" option.', 'wpil'); ?></p>
                    </div>
                <?php } ?>
                <?php if(!extension_loaded('mbstring')){?>
                    <div class="notice update notice-error" id="wpil_message" >
                        <p><?php _e('Dependency Missing: Multibyte String.', 'wpil'); ?></p>
                        <p><?php _e('The Multibyte String PHP extension is not active on your site. Link Whisper uses this extension to process text when making suggestions. Without this extension, Link Whisper will not be able to make suggestions.', 'wpil'); ?></p>
                        <p><?php _e('Please contact your hosting provider about enabling the Multibyte String PHP extension.', 'wpil'); ?></p>
                    </div>
                <?php } ?>
                <?php if(!extension_loaded('zlib') && !extension_loaded('Bz2')){?>
                    <div class="notice update notice-error" id="wpil_message" >
                        <p><?php _e('Dependency Missing: Data Compression Library.', 'wpil'); ?></p>
                        <p><?php _e('Link Whisper hasn\'t detected a useable compression library on this site. Link Whisper uses compression libraries to reduce how much memory is used when generating suggestions.', 'wpil'); ?></p>
                        <p><?php _e('It will try to generate suggestions without compressing the suggestion data. If Link Whisper runs out of memory, the suggestion loading will hang in place indefinitely.', 'wpil'); ?></p>
                        <p><?php _e('If you experience this, please contact your hosting provider about enabling either the "Zlib" compression library, or the "Bzip2" compression library.', 'wpil'); ?></p>
                    </div>
                <?php } ?>
                <?php if(!function_exists('base64_decode') || !function_exists('base64_encode')){?>
                    <div class="notice update notice-error" id="wpil_message" >
                        <p><?php _e('Dependency Missing: Base64 String Processing.', 'wpil'); ?></p>
                        <p><?php _e('It appears that the "base64_decode" or the "base64_encode" functions aren\'t available. Link Whisper uses these functions to store and process text data in a way that prevents formatting mistakes.', 'wpil'); ?></p>
                        <p><?php _e('Without these functions, Link Whisper won\'t be able to preform many of it\'s operations, including Suggestion Generation, Link Deleting, and Autolink Creating.', 'wpil'); ?></p>
                        <p><?php _e('Please contact your hosting provider or developer about enabling these functions.', 'wpil'); ?></p>
                    </div>
                <?php } ?>
                <form name="frmSaveSettings" id="frmSaveSettings" action='' method='post'>
                    <?php wp_nonce_field('wpil_save_settings','wpil_save_settings_nonce'); ?>
                    <input type="hidden" name="wpil_setting_selected_tab" value="<?php echo esc_attr($current_tab); ?>">
                    <input type="hidden" name="hidden_action" value="wpil_save_settings" />
                    <input type="hidden" name="wpil_related_post_preview_nonce" value="<?php echo wp_create_nonce('wpil-related-posts-preview-nonce');?>" />
                    <table class="form-table">
                        <tbody>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Link Whisper created internal links open in new tab', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_2_links_open_new_tab" value="0" />
                                    <input type="checkbox" name="wpil_2_links_open_new_tab" <?=get_option('wpil_2_links_open_new_tab')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to set all links that it creates pointing to pages on this site to open in a new tab.', 'wpil');
                                            echo '<br /><br />';
                                            _e('Changing this setting will not update existing links.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php
                            $open_external = get_option('wpil_external_links_open_new_tab', null);
                            // if open external isn't set, use the other link option
                            $open_external = ($open_external === null) ? get_option('wpil_2_links_open_new_tab'): $open_external;
                        ?>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Link Whisper created external links open in new tab', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_external_links_open_new_tab" value="0" />
                                    <input type="checkbox" name="wpil_external_links_open_new_tab" <?=$open_external==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to set all links that it creates pointing to external sites to open in a new tab.', 'wpil');
                                            echo '<br /><br />';
                                            _e('Changing this setting will not update existing links.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Ignore Numbers', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_2_ignore_numbers" value="0" />
                                <input type="checkbox" name="wpil_2_ignore_numbers" <?=get_option('wpil_2_ignore_numbers')==1?'checked':''?> value="1" />
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Selected Language', 'wpil'); ?></td>
                            <td>
                                <select id="wpil-selected-language" name="wpil_selected_language">
                                    <?php
                                        $languages = Wpil_Settings::getSupportedLanguages();
                                        $selected_language = Wpil_Settings::getSelectedLanguage();
                                    ?>
                                    <?php foreach($languages as $language_key => $language_name) : ?>
                                        <option value="<?php echo $language_key; ?>" <?php selected($language_key, $selected_language); ?>><?php echo $language_name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="wpil-currently-selected-language" value="<?php echo $selected_language; ?>">
                                <input type="hidden" id="wpil-currently-selected-language-confirm-text-1" value="<?php echo esc_attr__('Changing Link Whisper\'s language will replace the current Words to be Ignored with a new list of words.', 'wpil') ?>">
                                <input type="hidden" id="wpil-currently-selected-language-confirm-text-2" value="<?php echo esc_attr__('If you\'ve added any words to the Words to be Ignored area, this will erase them.', 'wpil') ?>">
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Words to be Ignored', 'wpil'); ?></td>
                            <td>
                                <?php
                                    $lang_data = array();
                                    foreach(Wpil_Settings::getAllIgnoreWordLists() as $lang_id => $words){
                                        $lang_data[$lang_id] = $words;
                                    }
                                ?>
                                <textarea id='ignore_words_textarea' class='regular-text' style="float:left;" rows=10><?php echo esc_textarea(implode("\n", $lang_data[$selected_language])); ?></textarea>
                                <input type="hidden" name='ignore_words' id='ignore_words' value="<?php echo base64_encode(implode("\n", $lang_data[$selected_language])); ?>">
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will ignore these words when making linking suggestions. Please enter each word on a new line', 'wpil'); ?></div>
                                </div>
                                <input type="hidden" id="wpil-available-language-word-lists" value="<?php echo esc_attr( wp_json_encode($lang_data, JSON_UNESCAPED_UNICODE) ); ?>">
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Pages to Completely Ignore from Link Whisper.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_pages_completely' id='wpil_ignore_pages_completely' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_pages_completely', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -160px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('Link Whisper will completely ignore posts and category pages listed in this field.', 'wpil');
                                        echo '<br /><br />';
                                        _e('No suggestions will be made TO or FROM the pages listed, no links will be scanned from them, and no autolinks created in them.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a page, enter its URL in this field on it\'s own line.', 'wpil');
                                        echo '<br /><br />';
                                        _e('After entering a URL, you may want to run a link scan to refresh the link data.', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Don\'t Show Suggestion Ignored Posts in the Reports', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_dont_show_ignored_posts" value="0" />
                                    <input type="checkbox" name="wpil_dont_show_ignored_posts" <?=get_option('wpil_dont_show_ignored_posts')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php _e('Checking this will tell Link Whisper to hide pages that have been ignored so they don\'t show up in the Reports.', 'wpil');?>
                                            <br />
                                            <br />
                                            <?php _e('This will apply to pages that have been listed in the "Posts to be Ignored" and "Categories of posts to be Ignored" fields.', 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e('Pages listed in other ignoring fields will not be affected.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Posts to be Ignored for Suggestions', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_links' id='wpil_ignore_links' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_links')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Link Whisper will not use posts listed here in the suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Outbound linking suggestions will not be made TO these posts. And Inbound linking suggestions will not be made FROM these posts', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('To ignore a post, enter the post\'s full url on it\'s own line in the text area', 'wpil'); ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Categories of posts to be Ignored for Suggestions', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_categories' id='wpil_ignore_categories' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_categories')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Link Whisper will not suggest posts from categories listed in this field.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Outbound linking suggestions will not be made TO posts in the listed categories. And Inbound linking suggestions will not be made FROM posts in the listed categories.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('To ignore an entire category, enter the category\'s full url on it\'s own line in the text area', 'wpil'); ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Posts to be Ignored for Auto-Linking and URL Changer', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_keywords_posts' id='wpil_ignore_keywords_posts' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_keywords_posts')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will not insert auto-links or change URLs on posts entered in this field. To ignore a post, enter the post\'s full url on it\'s own line in the text area', 'wpil'); ?></div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Categories of Posts to be Ignored for Auto-Linking and URL Changer', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_keywords_posts_by_category' id='wpil_ignore_keywords_posts_by_category' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_keywords_posts_by_category')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will not insert auto-links or change URLs on posts that are in categories entered in this field. To ignore a whole category of posts, enter the category\'s full url on it\'s own line in the text area', 'wpil'); ?></div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Posts to be Ignored from Orphaned Posts Report', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_orphaned_posts' id='wpil_ignore_orphaned_posts' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_orphaned_posts', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will not show the listed posts on the Orphaned Posts report. To ignore a post, enter a post\'s full url on it\'s own line in the text area', 'wpil'); ?></div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Categories of Posts to be Ignored from Orphaned Posts Report', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_orphaned_posts_by_category' id='wpil_ignore_orphaned_posts_by_category' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_orphaned_posts_by_category', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will not show posts in the listed categories on the Orphaned Posts report. To ignore a category of post, enter a category\'s full url on it\'s own line in the text area', 'wpil'); ?></div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php if(class_exists('ACF')){ ?>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('ACF Fields to be Ignored', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_acf_fields' id='wpil_ignore_acf_fields' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_acf_fields', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Link Whisper will not process content in the ACF fields listed here. To ignore a field, enter each field\'s name on it\'s own line in the text area', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This will entirely ignore the field, so it won\'t show up in reports, be processed for autolinks, or be scanned during the suggestion process.', 'wpil'); ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Links to Ignore Clicks on', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_click_links' id='wpil_ignore_click_links' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_click_links', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -150px 0px 0px 30px;">
                                        <?php 
                                        _e('Link Whisper will not track clicks on links listed here.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a link by URL, enter it\'s URL. The effects apply across the site, so all links with matching URLs will be ignored. Each URL must go on it\'s own line.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a link by anchor text, enter it\'s anchor text. The effects apply across the site, so all links with matching anchor texts will be ignored. Each anchor text must go on it\'s own line.', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Links to be Ignored From The Reports.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_links_to_ignore' id='wpil_links_to_ignore' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_links_to_ignore', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -150px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('Link Whisper will ignore the links listed in this field and won\'t show them in the Links Report or other linking stat areas.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a link, enter it in this field on it\'s own line.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Wildcard matching can be performed by using the * character on the end of the link that you want to match. So for example, entering "https://example.com/*" would match links like "https://example.com/example-page-1", "https://example.com/category/examples" and "https://example.com/example-pages/example-page-2"', 'wpil');
                                        echo '<br /><br />';
                                        _e('After entering a link, you will need to run a link scan to refresh the stored data.', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Links to be Ignored From The Broken Link Scan.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_broken_links_to_ignore' id='wpil_broken_links_to_ignore' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_broken_links_to_ignore', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -150px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('Link Whisper will ignore the links listed in this field and won\'t scan them during the Broken Link Scans.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a link, enter it in this field on it\'s own line.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Wildcard matching can be performed by using the * character on the end of the link that you want to match. So for example, entering "https://example.com/*" would match links like "https://example.com/example-page-1", "https://example.com/category/examples" and "https://example.com/example-pages/example-page-2"', 'wpil');
                                        echo '<br /><br />';
                                        _e('After entering a link, you may wish to run a new Broken Link Scan to refresh the stored data.', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Elements to Ignore by CSS Class.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_elements_by_class' id='wpil_ignore_elements_by_class' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_elements_by_class', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -160px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('Link Whisper will ignore HTML tags that contain CSS classes listed in this field. It won\'t extract links, or make linking suggestions, from elements that have the listed CSS classes.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a class, enter it in this field on it\'s own line.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Wildcard matching can be performed by using the * character on the end of the class that you want to match. So for example, entering "exam*" would match classes like "example", "examples", and "examination"', 'wpil');
                                        echo '<br /><br />';
                                        _e('After entering a class, you may want to run a link scan to refresh the link data.', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('HTML Tags to Ignore from Linking.', 'wpil'); ?></td>
                            <td>
                                <select multiple name='wpil_ignore_tags_from_linking[]' class="wpil-setting-multiselect" id='wpil_ignore_tags_from_linking' style="width: 800px;float:left;">
                                <?php
                                    foreach(Wpil_Settings::getPossibleIgnoreLinkingTags() as $possible_ignore_tag){
                                        echo '<option value="' . $possible_ignore_tag . '" ' . (in_array($possible_ignore_tag, $ignored_linking_tags, true) ? 'selected="selected"': '') . '>' . $possible_ignore_tag . '</option>';
                                    } 
                                ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -160px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('Link Whisper will not create links in any HTML tag selected in this dropdown', 'wpil');
                                        echo '<br /><br />';
                                        _e('This will apply to both the Suggestions and the Autolinking.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php
                        if(defined('ELEMENTOR_VERSION')){ 
                            $supported_modules = Wpil_Settings::getPossibleIgnoreElementorModules();
                            $selected_modules = Wpil_Settings::getIgnoreLinkingElementorModules();
                        ?>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Elementor Elements to Ignore from Linking.', 'wpil'); ?></td>
                            <td>
                                <select multiple name='wpil_ignore_elementor_from_linking[]' class="wpil-setting-multiselect" id='wpil_ignore_elementor_from_linking' style="width: 800px;float:left;">
                                <?php
                                    foreach($supported_modules as $module_name => $possible_module){
                                        echo '<option value="' . $module_name . '" ' . (in_array($module_name, $selected_modules, true) ? 'selected="selected"': '') . '>' . $possible_module . '</option>';
                                    } 
                                ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -160px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('Link Whisper will not create links in any Elementor module selected in this dropdown', 'wpil');
                                        echo '<br /><br />';
                                        _e('This will apply to both the Suggestions and the Autolinking.', 'wpil');
                                        echo '<br /><br />';
                                        _e('NOTE: All active Elementor modules are listed here, even though only one\'s with text content can be linked. This is to allow you more control in ignoring modules.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Shortcodes to Ignore by Name.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_ignore_shortcodes_by_name' id='wpil_ignore_shortcodes_by_name' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_ignore_shortcodes_by_name', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-info"></i>    
                                    <div style="margin: 0px 0px 0px -500px; width: 500px; overflow: auto; max-height: 200px;">
                                        <?php 
                                        echo '<h3 style="color:#fff; margin-top: 0px;">';
                                        _e('The known shortcode names are:', 'wpil');
                                        echo '</h3>';
                                        echo '<thing style="display:flex; flex-wrap: wrap;">'; // not div since that gets hidden in wpil_helps
                                        foreach($shortcode_tags as $tag_name => $dat){
                                            echo '<span style="padding: 0 10px 0 0;">' . $tag_name . '</span>';
                                        }
                                        echo '</thing>';
                                        echo '<h3 style="color:#fff;">';
                                        echo '(' . __('There may be other shortcodes active, but this is what we could find.', 'wpil') . ')';
                                        echo '</h3>';
                                        ?>
                                    </div>
                                </div>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -160px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('Link Whisper will ignore any shortcodes listed in this field. It won\'t extract links from the listed shortcodes, or create links in any text content of the shortcode.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a shortcode, enter it\'s name (without square brackets) in this field on it\'s own line.', 'wpil');
                                        echo '<br /><br />';
                                        _e('So for example, to ignore the WordPress [caption][/caption] shortcode, enter "caption" (without quotes) on it\'s own line in the field', 'wpil');
                                        echo '<br /><br />';
                                        _e('After entering a shortcode, you may want to run a link scan to refresh any stored link data based on shortcodes.', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-content-ignoring-settings wpil-setting-row">
                            <td scope='row'><?php _e('Don\'t Link in Posts From Authors in the Selected Roles.', 'wpil'); ?></td>
                            <td>
                                <select multiple name='wpil_ignore_linking_roles[]' class="wpil-setting-multiselect" id='wpil_ignore_linking_roles' style="width: 800px;float:left;">
                                <?php
                                    foreach ($active_roles as $role => $label){ ?>
                                        <option value="<?= $role ?>" <?= (isset($ignore_linking_roles[$role])) ? 'selected="selected"' : ''; ?>>
                                            <?= $label ?>
                                        </option>
                                <?php   }?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -300px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('This setting allows you to tell Link Whisper suggest links TO or FROM posts that were created by authors with the specified roles. Autolinks will also not be created in the posts from these users.', 'wpil');
                                        echo '<br /><br />';
                                        _e('If for example, you wanted to not suggest links to posts that were created by "Subscribers", selecting the "Subscriber" role here will have Link Whisper not create link suggestions or autolinks in any post that was written by a "subscriber".', 'wpil');
                                        echo '<br /><br />';
                                        _e('This setting applies to both the Suggestions and the Autolinking.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Only suggest outbound links to these posts', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_suggest_to_outbound_posts' id='wpil_suggest_to_outbound_posts' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_suggest_to_outbound_posts', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will only suggest outbound links to the listed posts. Please enter each link on it\'s own line in the text area. If you do not want to limit suggestions to specific posts, leave this empty', 'wpil'); ?></div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('All internal links open in new tab', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_open_all_internal_new_tab" value="0" />
                                    <input type="checkbox" name="wpil_open_all_internal_new_tab" <?=get_option('wpil_open_all_internal_new_tab')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to filter post content before displaying to make the links to other pages on this site open in new tabs.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will cause existing links, and those not created with Link Whisper to open in new tabs.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This works best with the default WordPress content editors and may not work with some page builders', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('All external links open in new tab', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_open_all_external_new_tab" value="0" />
                                    <input type="checkbox" name="wpil_open_all_external_new_tab" <?=get_option('wpil_open_all_external_new_tab')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to filter post content before displaying to make the links to external sites open in new tabs.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will cause existing links, and those not created with Link Whisper to open in new tabs.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This works best with the default WordPress content editors and may not work with some page builders', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row js-force-open-new-tabs">
                            <td scope='row'><?php _e('Use JS to force opening in new tabs', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_js_open_new_tabs" value="0" />
                                    <input type="checkbox" name="wpil_js_open_new_tabs" <?=get_option('wpil_js_open_new_tabs')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to use frontend scripting to set links to open in new tabs.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This is mainly intended for cases where the options for setting links to open in new tabs aren\'t working. (This can happen with some page builders.)', 'wpil');
                                            echo '<br /><br />';
                                            _e('Only links in the content areas will open in new tabs, navigation links will not be affected', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will cause the Link Whisper Frontend script to be added to most pages if it isn\'t already there.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('All internal links open in the same tab', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_open_all_internal_same_tab" value="0" />
                                    <input type="checkbox" name="wpil_open_all_internal_same_tab" <?=get_option('wpil_open_all_internal_same_tab')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to filter post content before displaying to make the links to other pages on this site open in same tab that the user is on.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will cause existing links, and those not created with Link Whisper to open in the current tab.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This works best with the default WordPress content editors and may not work with some page builders', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('All external links open in the same tab', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_open_all_external_same_tab" value="0" />
                                    <input type="checkbox" name="wpil_open_all_external_same_tab" <?=get_option('wpil_open_all_external_same_tab')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to filter post content before displaying to make the links to external sites open in same tab that the user is on.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will cause existing links, and those not created with Link Whisper to open in the current tab.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This works best with the default WordPress content editors and may not work with some page builders', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Set external links to nofollow', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_add_nofollow" value="0" />
                                <input type="checkbox" name="wpil_add_nofollow" <?=!empty(get_option('wpil_add_nofollow', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to add the "nofollow" attribute to all external links it creates and the external links created with the WordPress editors.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('However, this does not apply to links to sites you\'ve interlinked via Link Whisper\'s "Interlink External Sites" settings.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Links to those sites won\'t have "nofollow" added.', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Domains Marked as Sponsored', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_sponsored_domains' id='wpil_sponsored_domains' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_sponsored_domains', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php 
                                        _e('Link Whisper will add the rel="sponsored" attribute to all links from domains listed in this field.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Please enter each domain on it\'s own line in the field.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Domains Marked as NoFollow', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_nofollow_domains' id='wpil_nofollow_domains' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(implode("\n", Wpil_Settings::getNofollowDomains())); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php 
                                        _e('Link Whisper will add the rel="nofollow" attribute to all links that point to domains listed in this field.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Please enter each domain on it\'s own line in the field.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Domains Marked as DoFollow', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_dofollow_domains' id='wpil_dofollow_domains' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(implode("\n", Wpil_Settings::getDofollowDomains())); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php 
                                        _e('Link Whisper will add the rel="dofollow" attribute to all links that point to domains listed in this field.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Please enter each domain on it\'s own line in the field.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Mark Links as External', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_marked_as_external' id='wpil_marked_as_external' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_marked_as_external')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will recognize these links as external on the Report page. Please enter each link on it\'s own line in the text area', 'wpil'); ?></div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Mark Domains as Internal', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_domains_marked_as_internal' id='wpil_domains_marked_as_internal' style="width: 800px;float:left;" class='regular-text' rows=5><?php echo esc_textarea(get_option('wpil_domains_marked_as_internal')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Link Whisper will recognize links with these domains as internal on the Report page. Please enter each domain on it\'s own line in the text area as it appears in your browser', 'wpil'); ?></div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Don\'t add "nofollow" to links with these domains.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_nofollow_ignore_domains' id='wpil_nofollow_ignore_domains' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_nofollow_ignore_domains', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -150px 0px 0px 30px;">
                                        <?php 
                                        _e('Link Whisper will not add the "nofollow" attributes to links pointing to domains listed in this field.', 'wpil');
                                        echo '<br /><br />';
                                        _e('To ignore a domain, enter it in this field. The effects apply across the site, so all links with matching domain will not have "nofollow" added.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Ignoring a domain will not remove "nofollow" from links that have had it manually added. This setting is only used when "Set external links to nofollow" is activated', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Don\'t have links to these domains open in new tabs.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_new_tab_ignore_domains' id='wpil_new_tab_ignore_domains' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_nofollow_ignore_domains', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -150px 0px 0px 30px;">
                                        <?php 
                                        _e('Link Whisper will not add the \'target="_blank"\' attribute to links pointing to domains listed in this field.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Ignoring a domain will not remove existing \'target="_blank"\' attributes from links that have had them manually added.', 'wpil');
                                        echo '<br /><br />';
                                        _e('This setting is only used when "All internal links open in new tab" or "All external links open in new tab" is activated', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-domain-settings wpil-setting-row">
                            <td scope='row'><?php _e('Don\'t force links to these domains open in the same tab.', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_same_tab_ignore_domains' id='wpil_same_tab_ignore_domains' style="width: 800px;float:left;" class='regular-text' rows=10><?php echo esc_textarea(get_option('wpil_same_tab_ignore_domains', '')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -150px 0px 0px 30px;">
                                        <?php 
                                        _e('Link Whisper will not remove the \'target="_blank"\' attribute from links pointing to domains listed in this field.', 'wpil');
                                        echo '<br /><br />';
                                        _e('Ignoring a domain will not add \'target="_blank"\' attributes to links if it\'s not already there.', 'wpil');
                                        echo '<br /><br />';
                                        _e('This setting is only used when "All internal links open in same tab" or "All external links open in same tab" is activated', 'wpil');
                                        echo '<br /><br />';
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php if(class_exists('ACF')){ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Only Process These ACF Fields', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_process_these_acf_fields' id='wpil_process_these_acf_fields' style="width: 800px;float:left;" class='regular-text' rows=5><?php echo esc_textarea(get_option('wpil_process_these_acf_fields')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('By default, Link Whisper tries to scan all ACF fields automatically, but since some sites can have thousands of ACF fields, it can be more practical to specify the ones to scan and ignore the rest.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This setting allows you to specify the fields to scan, so the rest can be ignored.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Wildcard matching is supported with the percent (%) character, so you can tell Link Whisper to scan fields like "example_content" and "testing_content" with "%_content"', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Please enter each field name on it\'s own line in the text area.', 'wpil'); ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('ACF Post-Referencing Fields', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_acf_post_reference_fields' id='wpil_acf_post_reference_fields' style="width: 800px;float:left;" class='regular-text' rows=5><?php echo esc_textarea(get_option('wpil_acf_post_reference_fields')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 300px;">
                                        <?php _e('Link Whisper will scan ACF fields listed here to see if they refer to a post containing ACF content meant to be displayed flexibly across the site.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Frequently, reusable ACF "modules" are created by making a post/custom post with ACF content, and then storing the post\'s ID in a data field on the post(s) that its meant to be used.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This setting allows you to specify what fields contain IDs for the "module" posts, so Link Whisper can pull in the "module" content and scan it for links.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Please enter each field name on it\'s own line in the text area. For wildcard matching, please use the "%" character.', 'wpil'); ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Custom Fields to Process', 'wpil'); ?></td>
                            <td>
                                <textarea name='wpil_custom_fields_to_process' id='wpil_custom_fields_to_process' style="width: 800px;float:left;" class='regular-text' rows=5><?php echo esc_textarea(get_option('wpil_custom_fields_to_process')); ?></textarea>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Link Whisper will scan custom-content fields listed here for links and to see if it can create links in the content fields.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Advanced Custom Fields are automatically scanned, so there\'s no need to list them here.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Please enter each field name on it\'s own line in the text area.', 'wpil'); ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Add Icon to External Links', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <input type="radio" name="wpil_add_icon_to_external_link" id="wpil_add_icon_to_external_link_never" data-linking-icon-type="external" value="never" <?php checked($external_link_icon, 'never'); ?>>
                                    <label for="wpil_add_icon_to_external_link_never" style="margin: 0 15px 0 0;"><?php _e('Never', 'wpil'); ?></label>
                                    <input type="radio" name="wpil_add_icon_to_external_link" id="wpil_add_icon_to_external_link_new_tab" data-linking-icon-type="external" value="new_tab" <?php checked($external_link_icon, 'new_tab'); ?>>
                                    <label for="wpil_add_icon_to_external_link_new_tab" style="margin: 0 15px 0 0;"><?php _e('If it Opens in a New Tab', 'wpil'); ?></label>
                                    <input type="radio" name="wpil_add_icon_to_external_link" id="wpil_add_icon_to_external_link_always" data-linking-icon-type="external" value="always" <?php checked($external_link_icon, 'always'); ?>>
                                    <label for="wpil_add_icon_to_external_link_always" style="margin: 0 15px 0 0;"><?php _e('Always', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This setting allows you to tell Link Whisper to add a notification icon to links when they point to external sites.', 'wpil');
                                            echo '<br /><br />';
                                            _e('The icon lets visitors know that they will be leaving your site, and is considered an important accessibility feature.', 'wpil');
                                            echo '<br /><br />';
                                            _e('You can choose if the icon should be added to all external links, or only to external links that open in new tabs.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-external-link-icon-settings" <?php echo $external_settings_visible; ?>>
                            <td scope='row'><?php _e('External Link Icon', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:280px;">
                                    <?php
                                        foreach(Wpil_Base::get_svg_icon_names() as $name){
                                            echo '<div class="wpil-external-link-icon-container" style="display: inline-block;">';
                                            echo '<input type="radio" name="wpil_external_link_icon" id="' . $name . '" value="' . $name . '"' . checked($selected_external_icon, $name, false) . '>';
                                            echo '<label for="' . $name . '" style="display: inline-block; margin: 5px 20px 5px 5px;">' . Wpil_Base::get_svg_icon($name, false, $external_link_styles) . '</label>';
                                            echo '</div>';
                                        } 
                                    ?>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This allows you to pick which icon Link Whisper will add to external links.', 'wpil');
                                            echo '<br /><br />';
                                            _e('The icon will be added to the right of the anchor text.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-external-link-icon-settings" <?php echo $external_settings_visible; ?>>
                            <td scope='row'><?php _e('External Link Icon Size', 'wpil'); ?></td>
                            <td>
                                <div style="display:inline-block;">
                                    <select name="wpil_external_link_icon_size" data-linking-icon-type="external" style="min-width:80px;">
                                        <?php
                                            $sizes = array( 5, 6, 7, 8, 9, 
                                                            10, 11, 12, 13, 14, 15, 
                                                            16, 17, 18, 19, 20, 21, 
                                                            22, 23, 24, 25, 26, 27,
                                                            28, 29, 30, 31, 32, 33, 
                                                            34, 35, 36, 37, 38, 39, 
                                                            40, 41, 42, 43, 44, 45);

                                        foreach($sizes as $size){
                                            echo '<option value="' . $size . '" ' . selected($size, $external_link_icon_size) . '>' . $size . 'px</option>';
                                        } ?>
                                    </select>

                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This setting controls the size of the icons that are added to external links.', 'wpil');
                                            echo '<br /><br />';
                                            _e('Changing this setting will adjust the icons shown in the Settings to give you a live preview of how they will look in your site\'s content.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-external-link-icon-settings" <?php echo $external_settings_visible; ?>>
                            <td scope='row'><?php _e('External Link Icon Color', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <input type="color" name="wpil_external_link_icon_color" style="width: 80px; height: 40px" data-linking-icon-type="external" value="<?php echo $external_link_icon_color; ?>" />

                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This setting controls the color of the icons that are added to external links.', 'wpil');
                                            echo '<br /><br />';
                                            _e('Changing this setting will adjust the icons shown in the Settings to give you a live preview of how they will look in your site\'s content.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-external-link-icon-settings" <?php echo $external_settings_visible; ?>>
                            <td scope='row'><?php _e('External Link Icon Title', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <input type="text" name="wpil_external_link_icon_title" style="width: 300px;" value="<?php echo esc_attr($external_link_icon_title); ?>" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This setting allows you to set the text that\'s shown when a visitor hovers the mouse over an external link icon.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-external-link-icon-settings" <?php echo $external_settings_visible; ?>>
                            <td scope='row'><?php _e('Don\'t Add Icons to External Links in These HTML Tags', 'wpil'); ?></td>
                            <td>
                                <select multiple name='wpil_external_link_icon_html_exclude[]' class="wpil-setting-multiselect" id='wpil_external_link_icon_html_exclude' style="width: 400px;float:left;">
                                    <?php
                                        foreach(Wpil_Settings::get_link_icon_html_ignore_tags() as $possible_ignore_tag){
                                            echo '<option value="' . $possible_ignore_tag . '" ' . (in_array($possible_ignore_tag, $external_link_icon_html_ignore, true) ? 'selected="selected"': '') . '>' . $possible_ignore_tag . '</option>';
                                        } 
                                    ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -160px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('This setting tells Link Whisper not to add icons to links inside of specific HTML elements.', 'wpil');
                                        echo '<br /><br />';
                                        _e('The setting applies to tags that are not the link\'s immediate ancestor, so highly nested links will be supported.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Add Icon to Internal Links', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <input type="radio" name="wpil_add_icon_to_internal_link" id="wpil_add_icon_to_internal_link_never" data-linking-icon-type="internal" value="never" <?php checked($internal_link_icon, 'never'); ?>>
                                    <label for="wpil_add_icon_to_internal_link_never" style="margin: 0 15px 0 0;"><?php _e('Never', 'wpil'); ?></label>
                                    <input type="radio" name="wpil_add_icon_to_internal_link" id="wpil_add_icon_to_internal_link_new_tab" data-linking-icon-type="internal" value="new_tab" <?php checked($internal_link_icon, 'new_tab'); ?>>
                                    <label for="wpil_add_icon_to_internal_link_new_tab" style="margin: 0 15px 0 0;"><?php _e('If it Opens in a New Tab', 'wpil'); ?></label>
                                    <input type="radio" name="wpil_add_icon_to_internal_link" id="wpil_add_icon_to_internal_link_always" data-linking-icon-type="internal" value="always" <?php checked($internal_link_icon, 'always'); ?>>
                                    <label for="wpil_add_icon_to_internal_link_always" style="margin: 0 15px 0 0;"><?php _e('Always', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                                _e('This setting allows you to tell Link Whisper to add a notification icon to links when they point to internal pages.', 'wpil');
                                                echo '<br /><br />';
                                                _e('The icon lets visitors know that they will be travelling to a page other than the current one.', 'wpil');
                                                echo '<br /><br />';
                                                _e('You can choose if the icon should be added to all internal links, or only to the internal links that open in new tabs.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-internal-link-icon-settings" <?php echo $internal_settings_visible; ?>>
                            <td scope='row'><?php _e('Internal Link Icon', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:280px;">
                                    <?php
                                        foreach(Wpil_Base::get_svg_icon_names() as $name){
                                            echo '<div class="wpil-internal-link-icon-container" style="display: inline-block;">';
                                            echo '<input type="radio" name="wpil_internal_link_icon" id="' . $name . '" value="' . $name . '"' . checked($selected_internal_icon, $name, false) . '>';
                                            echo '<label for="' . $name . '" style="display: inline-block; margin: 5px 20px 5px 5px;">' . Wpil_Base::get_svg_icon($name, false, $internal_link_styles) . '</label>';
                                            echo '</div>';
                                        } 
                                    ?>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This allows you to pick which icon Link Whisper will add to internal links.', 'wpil');
                                            echo '<br /><br />';
                                            _e('The icon will be added to the right of the anchor text.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-internal-link-icon-settings" <?php echo $internal_settings_visible; ?>>
                            <td scope='row'><?php _e('Internal Link Icon Size', 'wpil'); ?></td>
                            <td>
                                <div style="display:inline-block;">
                                    <select name="wpil_internal_link_icon_size" data-linking-icon-type="internal" style="min-width:80px;">
                                        <?php
                                            $sizes = array( 5, 6, 7, 8, 9, 
                                                            10, 11, 12, 13, 14, 15, 
                                                            16, 17, 18, 19, 20, 21, 
                                                            22, 23, 24, 25, 26, 27,
                                                            28, 29, 30, 31, 32, 33, 
                                                            34, 35, 36, 37, 38, 39, 
                                                            40, 41, 42, 43, 44, 45);

                                        foreach($sizes as $size){
                                            echo '<option value="' . $size . '" ' . selected($size, $internal_link_icon_size) . '>' . $size . 'px</option>';
                                        } ?>
                                    </select>

                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This setting controls the size of the icons that are added to internal links.', 'wpil');
                                            echo '<br /><br />';
                                            _e('Changing this setting will adjust the icons shown in the Settings to give you a live preview of how they will look in your site\'s content.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-internal-link-icon-settings" <?php echo $internal_settings_visible; ?>>
                            <td scope='row'><?php _e('Internal Link Icon Color', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <input type="color" name="wpil_internal_link_icon_color" style="width: 80px; height: 40px" data-linking-icon-type="internal" value="<?php echo $internal_link_icon_color; ?>" />

                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This setting controls the color of the icons that are added to internal links.', 'wpil');
                                            echo '<br /><br />';
                                            _e('Changing this setting will adjust the icons shown in the Settings to give you a live preview of how they will look in your site\'s content.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-internal-link-icon-settings" <?php echo $internal_settings_visible; ?>>
                            <td scope='row'><?php _e('Internal Link Icon Title', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <input type="text" name="wpil_internal_link_icon_title" style="width: 300px;" value="<?php echo esc_attr($internal_link_icon_title); ?>" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('This setting allows you to set the text that\'s shown when a visitor hovers the mouse over an internal link icon.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-internal-link-icon-settings" <?php echo $internal_settings_visible; ?>>
                            <td scope='row'><?php _e('Don\'t Add Icons to Internal Links in These HTML Tags', 'wpil'); ?></td>
                            <td>
                                <select multiple name='wpil_internal_link_icon_html_exclude[]' class="wpil-setting-multiselect" id='wpil_internal_link_icon_html_exclude' style="width: 400px;float:left;">
                                    <?php
                                        foreach(Wpil_Settings::get_link_icon_html_ignore_tags() as $possible_ignore_tag){
                                            echo '<option value="' . $possible_ignore_tag . '" ' . (in_array($possible_ignore_tag, $internal_link_icon_html_ignore, true) ? 'selected="selected"': '') . '>' . $possible_ignore_tag . '</option>';
                                        } 
                                    ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -160px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('This setting tells Link Whisper not to add icons to links inside of specific HTML elements.', 'wpil');
                                        echo '<br /><br />';
                                        _e('The setting applies to tags that are not the link\'s immediate ancestor, so highly nested links will be supported.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Relative Links Mode', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_insert_links_as_relative" value="0" />
                                    <input type="checkbox" name="wpil_insert_links_as_relative" <?=!empty(get_option('wpil_insert_links_as_relative', false))?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to insert all suggested links as relative links instead of absolute links.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will also allow the URL Changer to change links into relative ones if the "New URL" is relative.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Prevent Two-Way Linking', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_prevent_two_way_linking" value="0" />
                                    <input type="checkbox" name="wpil_prevent_two_way_linking" <?=!empty(get_option('wpil_prevent_two_way_linking', false))?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will keep Link Whisper from creating two-way linking relationships.', 'wpil');
                                            echo '<br /><br />';
                                            _e('If for example post "A" has a link to post "B", this setting will prevent Link Whisper from suggesting a link from post "B" to post "A".', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Disable Autolinking on Post Update', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_disable_autolinking_on_post_update" value="0" />
                                    <input type="checkbox" name="wpil_disable_autolinking_on_post_update" <?=!empty(Wpil_Settings::disable_autolink_on_post_save())?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="width: 300px;">
                                            <?php 
                                            _e('By default, when a post is saved/updated, Link Whisper searches it\'s content to see if there are any Autolinking keywords present.', 'wpil');
                                            echo '<br /><br />';
                                            _e('If there are, Link Whisper will create Autolinks on the available keywords.', 'wpil');
                                            echo '<br /><br />';
                                            _e('But this can add some time to the update process, especially if there are many Autolinking Rules.', 'wpil');
                                            echo '<br /><br />';
                                            _e('Checking this will disable the search and will keep Link Whisper from inserting Autolinks when a post is updated.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Post Types to Process', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <div class="wpil_help" style="float:right; position: relative; left: 30px;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php
                                                _e('This setting controls the post types that Link Whisper is active in.', 'wpil');
                                                echo '<br /><br />';
                                                _e('Link Whisper will create links in the selected post types, scan the post types for links, and will operate all of Link Whisper\'s Advanced Functionality in the post types.', 'wpil');
                                                echo '<br /><br />';
                                                _e('After changing the post type selection, please go to the Report page and click the "Run a Link Scan" button to clear the old link data.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <?php foreach ($types_available as $type => $label) : ?>
                                        <input type="checkbox" name="wpil_2_post_types[]" value="<?=$type?>" <?=in_array($type, $types_active)?'checked':''?>><label><?=ucfirst($label)?></label><br>
                                    <?php endforeach; ?>
                                    <input type="hidden" name="wpil_2_show_all_post_types" value="0">
                                    <input type="checkbox" name="wpil_2_show_all_post_types" value="1" <?=!empty(get_option('wpil_2_show_all_post_types', false))?'checked':''?>><label><?php _e('Show Non-Public Post Types', 'wpil'); ?></label><br>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Only Point Suggestions to Specific Post Types', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_limit_suggestions_to_post_types" value="0" />
                                    <input type="checkbox" name="wpil_limit_suggestions_to_post_types" <?=get_option('wpil_limit_suggestions_to_post_types')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php 
                                            _e('Checking this will tell Link Whisper to only suggest links that point to posts belonging to specific post types.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will only limit the suggestions in the Suggested Links panels. It won\'t affect the Autolinking or URL Changer', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php if(defined('WPRM_POST_TYPE')){ ?>
                        <tr class="wpil-general-settings wpil-setting-row wpil-wprm-content-field-setting <?php echo (!in_array('wprm_recipe', $types_active)) ? 'hide-setting': '';?>">
                            <td scope='row'><?php _e('WP Recipe Fields to Insert Links in', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <div class="wpil_help" style="float:right; position: relative; left: 30px;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php _e('Using these fields, you can choose what sections of a WP Recipe Link Whisper will insert links into.', 'wpil'); ?>
                                            <br /><br />
                                            <?php _e('By default, Link Whisper only insert links into the Recipe\'s "Notes" section at the bottom of the recipe.', 'wpil'); ?>
                                        </div>
                                    </div>
                                    <?php 
                                    foreach(Wpil_Editor_WPRecipe::get_insertable_fields() as $type => $data){
                                        if(is_array($data)){
                                            foreach($data as $dat => $label){
                                                ?>
                                                <input type="checkbox" name="<?php echo esc_attr("wpil_suggestion_wp_recipe_fields[$type][$dat]"); ?>" value="1" <?php echo (!empty($wp_recipe_fields[$type][$dat]) && isset($wp_recipe_fields[$type][$dat]))?'checked':''?>><label><?=$label?></label><br>
                                                <?php
                                            }
                                        }else{
                                            ?>
                                            <input type="checkbox" name="<?php echo esc_attr("wpil_suggestion_wp_recipe_fields[$type]");?>" value="1" <?php echo isset($wp_recipe_fields[$type])?'checked':''?>><label><?=$data?></label><br>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-general-settings wpil-setting-row wpil-suggestion-post-type-limit-setting <?php echo (empty(get_option('wpil_limit_suggestions_to_post_types', false))) ? 'hide-setting': '';?>">
                            <td scope='row'><?php _e('Post Types to Point Suggestions to', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <div class="wpil_help" style="float:right; position: relative; left: 30px;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php _e('Link Whisper will only offer suggestions that point to posts in the selected post types.', 'wpil'); ?>
                                            <br /><br />
                                            <?php _e('Only post types that Link Whisper is set to process will be listed here. If you don\'t see a post type listed here, please try selecting it in the "Post Types to Create Links For" setting.', 'wpil'); ?>
                                        </div>
                                    </div>
                                    <?php foreach ($types_available as $type => $label) : ?>
                                        <?php 
                                            $class = 'wpil-suggestion-limit-type-' . $type;
                                            $class .= !in_array($type, $types_active) ? ' hide-setting': ''; 
                                        ?>
                                        <input type="checkbox" name="wpil_suggestion_limited_post_types[]" value="<?=$type?>" <?php echo in_array($type, $suggestion_types_active)?'checked':''?> class="<?php echo $class; ?>"><label class="<?php echo $class; ?>"><?=ucfirst($label)?></label><br class="<?php echo $class; ?>">
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Term Types to Process', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <div class="wpil_help" style="float:right; position: relative; left: 30px;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php
                                                _e('This setting controls the term types that Link Whisper is active in.', 'wpil');
                                                echo '<br /><br />';
                                                _e('Link Whisper will create links in the selected term\'s archive pages, scan the term\'s archive pages for links, and will operate all of Link Whisper\'s Advanced Functionality in the term\'s archive pages.', 'wpil');
                                                echo '<br /><br />';
                                                _e('After changing the term type selection, please go to the Report page and click the "Run a Link Scan" button to clear the old link data.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <?php foreach ($term_types_available as $type) : ?>
                                        <input type="checkbox" name="wpil_2_term_types[]" value="<?=$type?>" <?=in_array($type, $term_types_active)?'checked':''?>><label><?=ucfirst($type)?></label><br>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Post Statuses to Create Links For', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <div class="wpil_help" style="float:right; position: relative; left: 30px;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div><?php _e('After changing the post status selection, please go to the Report page and click the "Run a Link Scan" button to clear the old link data.', 'wpil'); ?></div>
                                    </div>
                                    <?php foreach ($statuses_available as $status) : ?>
                                        <?php
                                            $status_obj = get_post_status_object($status);
                                            $stat = (!empty($status_obj)) ? $status_obj->label: ucfirst($post_status);
                                        ?>
                                        <input type="checkbox" name="wpil_2_post_statuses[]" value="<?=$status?>" <?=in_array($status, $statuses_active)?'checked':''?>><label><?=$stat?></label><br>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope="row"><span><?php _e('Number of', 'wpil'); ?></span>
                                <select name="wpil_skip_section_type" class="wpil-setting-inline-select">
                                    <option value="sentences"<?php selected($skip_type, 'sentences');?>><?php _e('Sentences', 'wpil'); ?></option>
                                    <option value="paragraphs"<?php selected($skip_type, 'paragraphs');?>><?php _e('Paragraphs', 'wpil'); ?></option>
                                </select>
                                <span><?php _e('to Skip', 'wpil');?></span>
                            </td>
                            <td>
                                <select name="wpil_skip_sentences" style="float:left; max-width:100px">
                                    <?php for($i = 0; $i <= 10; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i==Wpil_Settings::getSkipSentences() ? 'selected' : '' ?>><?=$i?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 4px;"></i>
                                    <div><?php _e('Link Whisper will not suggest links for this number of sentences or paragraphs appearing at the beginning of a post.', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope="row"><?php _e('Max Outbound Links Per Post', 'wpil'); ?></td>
                            <td>
                                <select name="wpil_max_links_per_post" style="float:left; max-width:100px">
                                    <option value="0" <?=0===(int)$max_links_per_post ? 'selected' : '' ?>><?php _e('No Limit', 'wpil'); ?></option>
                                    <?php for($i = 1; $i <= 100; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i===(int)$max_links_per_post ? 'selected' : '' ?>><?=$i?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 4px;"></i>
                                    <div style="margin: -130px 0px 0px 30px;">
                                        <?php 
                                        _e('This is the max number of links that you want your posts to have.', 'wpil'); 
                                        echo '<br /><br />';
                                        _e('When a post has reached the link limit, Link Whisper will not suggest adding more links to the post\'s content, and will not add more links to it via the Auto-Linking functionality.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope="row"><?php _e('Max Inbound Links Per Post', 'wpil'); ?></td>
                            <td>
                                <select name="wpil_max_inbound_links_per_post" style="float:left; max-width:100px">
                                    <option value="0" <?=0===(int)$max_inbound_links_per_post ? 'selected' : '' ?>><?php _e('No Limit', 'wpil'); ?></option>
                                    <?php for($i = 1; $i <= 100; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i===(int)$max_inbound_links_per_post ? 'selected' : '' ?>><?=$i?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 4px;"></i>
                                    <div style="margin: -130px 0px 0px 30px;">
                                        <?php 
                                        _e('This is the max number of inbound links that you want your posts to have.', 'wpil'); 
                                        echo '<br /><br />';
                                        _e('When a post has reached the link limit, Link Whisper will not suggest adding more inbound links pointing to the post.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope='row'><?php _e('Add Destination Post Title to Links', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_add_destination_title" value="0" />
                                    <input type="checkbox" name="wpil_add_destination_title" <?=!empty(get_option('wpil_add_destination_title', false))?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -250px 0 0 30px;">
                                            <?php 
                                            _e('Checking this will tell Link Whisper to insert the title of the post it\'s linking to in the link\'s title attribute.', 'wpil');
                                            echo '<br /><br />';
                                            _e('This will allow users to mouse over links to see what post is being linked to.', 'wpil');
                                            echo '<br /><br />';
                                            _e('The post title is added when links are created and changing this setting will not affect existing links.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope="row"><?php _e('Don\'t Suggest Links to Posts Older Than', 'wpil'); ?></td>
                            <td>
                                <select name="wpil_max_linking_age" style="float:left; max-width:100px">
                                    <option value="0" <?=0===(int)$max_linking_age ? 'selected' : '' ?>><?php _e('No Limit', 'wpil'); ?></option>
                                    <?php for($i = 1; $i <= 100; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i===(int)$max_linking_age ? 'selected' : '' ?>><?php printf( _n( '%s year', '%s years', $i, 'wpil' ), $i ); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 4px;"></i>
                                    <div style="margin: -130px 0px 0px 30px;">
                                        <?php 
                                        _e('Link Whisper won\'t suggest links from posts that were published before the date limit.', 'wpil');
                                        echo '<br /><br />';
                                        _e('This only applies to the suggestion-based links, the Auto Links have date limiting based on their creation rules.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-general-settings wpil-setting-row">
                            <td scope="row"><?php _e('Max Number of Suggestions to Display', 'wpil'); ?></td>
                            <td>
                                <select name="wpil_max_suggestion_count" style="float:left; max-width:100px">
                                    <option value="0" <?=0===(int)$max_suggestion_count ? 'selected' : '' ?>><?php _e('No Limit', 'wpil'); ?></option>
                                    <?php for($i = 1; $i <= 100; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i===(int)$max_suggestion_count ? 'selected' : '' ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 4px;"></i>
                                    <div style="margin: -130px 0px 0px 30px;">
                                        <?php 
                                        _e('This is the maximum number of suggestions that Link Whisper will show you at once in the Suggestion Panels.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php if(class_exists('ACF')){ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Disable Linking for Advanced Custom Fields', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_disable_acf" value="0" />
                                <div style="max-width: 80px;">
                                    <input type="checkbox" name="wpil_disable_acf" <?=get_option('wpil_disable_acf', false)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float: right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin-left: 30px; margin-top: -20px;">
                                            <p><i><?php _e('Checking this will tell Link Whisper to not process any data created by Advanced Custom Fields.', 'wpil'); ?></i></p>
                                            <p><i><?php _e('This will speed up the suggestion making and data saving, but will not update the ACF data.', 'wpil'); ?></i></p>
                                            <p><i><?php _e('If you don\'t see Advanced Custom Fields in your Installed Plugins list, it may be included as a component in a plugin or your theme.', 'wpil'); ?></i></p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Disable Broken Link Checker Cron Task', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_disable_broken_link_cron_check" value="0" />
                                <div style="max-width: 80px;">
                                    <input type="checkbox" name="wpil_disable_broken_link_cron_check" <?=get_option('wpil_disable_broken_link_cron_check', false)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float: right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin-left: 30px; margin-top: -20px;">
                                            <p><?php _e('Checking this will disable the cron task that broken link checker runs.', 'wpil'); ?></p>
                                            <p><?php _e('This will disable the scanning for new broken links and the re-checking of suspected broken links.', 'wpil'); ?></p>
                                            <p><?php _e('You can still manually activate the broken link scan by going to the Error Report and clicking "Scan for Broken Links" button.', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Count Non-Content Links', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_show_all_links" value="0" />
                                    <input type="checkbox" name="wpil_show_all_links" <?=get_option('wpil_show_all_links')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div><?php _e('Turning this on will cause menu links, footer links, sidebar links, comment links, and links from widgets to be displayed in the link reports.', 'wpil'); ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Count Related Post Links', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_count_related_post_links" value="0" />
                                    <input type="checkbox" name="wpil_count_related_post_links" <?=get_option('wpil_count_related_post_links')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php _e('Turning this on will tell Link Whisper to scan and process links in related post areas that are separate from the post content.', 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e('Currently supports links generated by Link Whisper\'s Related Posts feature and YARPP.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Include Comment Links In Links Report', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_show_comment_links" value="0" />
                                <input type="checkbox" name="wpil_show_comment_links" <?=!empty(get_option('wpil_show_comment_links', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to include links from comments in the Links Report.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('If you have "Count Non-Content Links" active, you won\'t need to activate this because comment links are already being included in the report.', 'wpil'); ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Ignore Links From Latest Post Widgets', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_ignore_latest_posts" value="0" />
                                <input type="checkbox" name="wpil_ignore_latest_posts" <?=!empty(get_option('wpil_ignore_latest_posts', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to ignore links from known Latest Post elements so the links aren\'t used in the Links Report.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Monitor Link Changes in Gutenberg Reusable Blocks', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_update_reusable_block_links" value="0" />
                                <input type="checkbox" name="wpil_update_reusable_block_links" <?=!empty(get_option('wpil_update_reusable_block_links', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this option will tell Link Whisper to monitor changes to Gutenberg reusable blocks and update the link stats of any posts that use the modified blocks.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Content Formatting Level in Link Scan', 'wpil'); ?></td>
                            <td>
                                <input type="range" name="wpil_content_formatting_level" class="wpil-thick-range" min="0" max="2" value="<?php echo $formatting_level; ?>">
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 340px;">
                                        <?php _e('The setting controls how much content formatting Link Whisper does with content when searching it for links.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('By default, Link Whisper fully formats the content with WordPress\'s "the_content" filter so it\'s closer to what a visitor would see.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('But for some themes and page builders, this causes issues with links. And the answer is to reduce how much Link Whisper formats the content.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting this to "Only Shortcodes" will render the shortcodes in post content, but otherwise leave the content unchanged. Setting it to "No Formatting" will disable the formatting entirely.', 'wpil'); ?>
                                    </div>
                                    </div>
                                </div>
                                <div>
                                    <span style="<?php echo ($formatting_level === 0) ? '': 'display:none';?>" class="wpil-content-formatting-text wpil-format-0"><?php _e('No Formatting', 'wpil'); ?></span>
                                    <span style="<?php echo ($formatting_level === 1) ? '': 'display:none';?>" class="wpil-content-formatting-text wpil-format-1"><?php _e('Only Shortcodes', 'wpil'); ?></span>
                                    <span style="<?php echo ($formatting_level === 2) ? '': 'display:none';?>" class="wpil-content-formatting-text wpil-format-2"><?php _e('Full Formatting', 'wpil'); ?></span>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Override Global Post During Link Scan', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_override_global_post_during_scan" value="0" />
                                <input type="checkbox" name="wpil_override_global_post_during_scan" <?=!empty(get_option('wpil_override_global_post_during_scan', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 340px;">
                                        <?php _e('This setting temporarily overrides global WordPress $post variable with one that matches the post currently being scanned.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This is a compatibility measure for shortcodes that rely on the global $post variable to get content information, or to conditionally display content.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('When the post scanning is completed, the $post variable is reset to its original value.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('One of the main indicators that this needs to be activated is if after the Link Scan completes, many posts are reporting that they have the same links. Especially if they\'re from "related post" sections.', 'wpil'); ?>
                                    </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Update "Post Modified" Date when Links Created', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_update_post_edit_date" value="0" />
                                <input type="checkbox" name="wpil_update_post_edit_date" <?=!empty(get_option('wpil_update_post_edit_date', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to update the "Post Modified" date when you insert create links in a post.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('By default, Link Whisper doesn\'t change the "Post Modified" date when creating links.', 'wpil'); ?>
                                    </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Remove "noindex" Posts from Suggestions', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_remove_noindex_post_suggestions" value="0" />
                                <input type="checkbox" name="wpil_remove_noindex_post_suggestions" <?=get_option('wpil_remove_noindex_post_suggestions')==1?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div><?php _e('Checking this option will tell Link Whisper to remove any suggestions pointing toward posts that have been marked as "noindex".', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Force Suggested Links to be HTTPS', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_force_https_links" value="0" />
                                <input type="checkbox" name="wpil_force_https_links" <?=!empty(get_option('wpil_force_https_links', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper that the links it suggests should always be HTTPS.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Link Whisper uses your site\'s "WordPress Address (URL)" setting to determine if the links it suggests should be HTTP or HTTPS.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('But sometimes, a site configuration setting will cause Link Whisper to use HTTP when it should be HTTPS.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This setting will force Link Whisper to always suggest HTTPS links.', 'wpil'); ?>
                                    </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Full HTML Suggestions', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_full_html_suggestions" value="0" />
                                    <input type="checkbox" name="wpil_full_html_suggestions" <?=get_option('wpil_full_html_suggestions')==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div><?php _e('Turning this on will tell Link Whisper to display the raw HTML version of the link suggestions under the suggestion box.', 'wpil'); ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Manually Trigger Suggestions', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_manually_trigger_suggestions" value="0" />
                                <input type="checkbox" name="wpil_manually_trigger_suggestions" <?=get_option('wpil_manually_trigger_suggestions')==1?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div><?php _e('Checking this option will stop Link Whisper from automatically generating suggestions when you open the post edit or Inbound Suggestion pages. Instead, Link Whisper will wait until you click the "Get Suggestions" button in the suggestion panel.', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Disable Outbound Suggestions', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_disable_outbound_suggestions" value="0" />
                                <input type="checkbox" name="wpil_disable_outbound_suggestions" <?=get_option('wpil_disable_outbound_suggestions')==1?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div><?php _e('Checking this option will prevent Link Whisper from doing suggestion scans inside post edit screens.', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Make Suggestion Filtering Persistent', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_make_suggestion_filtering_persistent" value="0" />
                                <input type="checkbox" name="wpil_make_suggestion_filtering_persistent" <?=get_option('wpil_make_suggestion_filtering_persistent')==1?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div>
                                        <?php _e('Checking this option will tell Link Whisper to make the Suggestion Filtering Options persistent between page loads.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('So if, for example, you set the suggestions to be limited to posts in the same categories as the current post. Link Whisper will remember that setting and will use it in future suggestion runs.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Limit Max Number of Posts to Search for Suggestions', 'wpil'); ?></td>
                            <td>
                                <input type="number" name="wpil_max_suggestion_post_count" value="<?=get_option('wpil_max_suggestion_post_count', 0)?>" step="1" min="0" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div>
                                        <?php _e('This setting tells Link Whisper the max number of posts that it should search through when generating suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Some sites have a huge number of posts, and trying to search through all of them for suggestions can take a very long time. Limiting the number of posts to search can help return suggestions in a timely manner.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('When set, Link Whisper will randomly select the posts it will search. Setting it to "0" means there is no limit to the number of posts to be searched.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Maximum Suggested Anchor Length', 'wpil'); ?></td>
                            <td>
                                <select name="wpil_suggestion_anchor_max_size" style="float:left; max-width:100px">
                                    <option value="1" <?=1===(int)$max_suggestion_length ? 'selected' : '' ?>><?php _e('1 Word', 'wpil'); ?></option>
                                    <?php for($i = 2; $i <= 15; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i===(int)$max_suggestion_length ? 'selected' : '' ?>><?php echo sprintf(__('%d Words', 'wpil'), $i);?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div style="width: 260px;">
                                        <?php _e('This option allows you set the maximum number of words that a suggested anchor can contain.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('If you are experiencing suggestions that are too long to be relevent, decreasing the maximum anchor length may help improve suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('The default max length for an anchor is 10 words.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Minimum Suggested Anchor Length', 'wpil'); ?></td>
                            <td>
                                <select name="wpil_suggestion_anchor_min_size" style="float:left; max-width:100px">
                                    <option value="1" <?=1===(int)$min_suggestion_length ? 'selected' : '' ?>><?php _e('1 Word', 'wpil'); ?></option>
                                    <?php for($i = 2; $i <= 15; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i===(int)$min_suggestion_length ? 'selected' : '' ?>><?php echo sprintf(__('%d Words', 'wpil'), $i);?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div style="width: 260px;">
                                        <?php _e('This option allows you set the minimum number of words that a suggested anchor must contain.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('If you are experiencing suggestions that are too short to be relevent, increasing the minimum anchor may help improve suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('If this setting is set to "1 Word", the setting filters the suggestions so exact matches of single keywords will be suggested, and suggestions based on all other criteria will required to be at least 2 words long.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php
                        if(current_user_can('activate_plugins')){
                        ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Connect to Google Search Console', 'wpil'); ?></td>
                            <td>
                                <?php
                                $authorized = get_option('wpil_gsc_app_authorized', false);
                                $has_custom = !empty(get_option('wpil_gsc_custom_config', false)) ? true : false;
                                $auth_message = (!$has_custom) ? __('Authorize Link Whisper', 'wpil'): __('Authorize Your App', 'wpil');
                                if(empty($authenticated) || empty($authorized)){ ?>
                                    <div class="wpil_gsc_app_inputs">
                                        <input style="width: 100%;max-width: 400px;margin: 0 0 10px 0;" id="wpil_gsc_access_code" class="wpil_gsc_get_authorize" type="text" name="wpil_gsc_access_code"/>
                                        <label for="wpil_gsc_access_code" class="wpil_gsc_get_authorize"><a class="wpil_gsc_enter_app_creds wpil_gsc_button button-primary"><?php _e('Authorize', 'wpil'); ?></a></label>
                                        <a style="margin-top:5px;" class="wpil-get-gsc-access-token button-primary" href="<?php echo Wpil_Settings::getGSCAuthUrl(); ?>"><?php echo $auth_message; ?></a>
                                        <?php /*
                                        <a <?php echo ($has_custom) ? 'style="display:none"': ''; ?> class="wpil_gsc_switch_app wpil_gsc_button enter-custom button-primary button-purple"><?php _e('Connect with Custom App', 'wpil'); ?></a>
                                        <a <?php echo ($has_custom) ? '': 'style="display:none"'; ?> class="wpil_gsc_clear_app_creds button-primary button-purple" data-nonce="<?php echo wp_create_nonce('clear-gsc-creds'); ?>"><?php _e('Clear Custom App Credentials', 'wpil'); ?></a>
                                        */ ?>
                                    </div>
                                    <?php /*
                                    <div style="display:none;" class="wpil_gsc_custom_app_inputs">
                                        <p><i><?php _e('To create a Google app to connect with, please follow this guide. TODO: Write article', 'wpil'); ?></i></p>
                                        <div>
                                            <input style="width: 100%;max-width: 400px;margin: 0 0 10px 0;" id="wpil_gsc_custom_app_name" class="connect-custom-app" type="text" name="wpil_gsc_custom_app_name"/>
                                            <label for="wpil_gsc_custom_app_name"><?php _e('App Name', 'wpil'); ?></label>
                                        </div>
                                        <div>
                                            <input style="width: 100%;max-width: 400px;margin: 0 0 10px 0;" id="wpil_gsc_custom_client_id" class="connect-custom-app" type="text" name="wpil_gsc_custom_client_id"/>
                                            <label for="wpil_gsc_custom_client_id"><?php _e('Client Id', 'wpil'); ?></label>
                                        </div>
                                        <div>
                                            <input style="width: 100%;max-width: 400px;margin: 0 0 10px 0;" id="wpil_gsc_custom_client_secret" class="connect-custom-app" type="text" name="wpil_gsc_custom_client_secret"/>
                                            <label for="wpil_gsc_custom_client_secret"><?php _e('Client Secret', 'wpil'); ?></label>
                                        </div>
                                        <a style="margin: 0 0 10px 0;" class="wpil_gsc_enter_app_creds wpil_gsc_button button-primary"><?php _e('Save App Credentials', 'wpil'); ?></a>
                                        <br />
                                        <a class="wpil_gsc_switch_app wpil_gsc_button enter-standard button-primary button-purple"><?php _e('Connect with Link Whisper App', 'wpil'); ?></a>
                                    </div>
                                    */ ?>
                                <?php }else{ ?>
                                    <a class="wpil-gsc-deactivate-app button-primary"  data-nonce="<?php echo wp_create_nonce('disconnect-gsc'); ?>"><?php _e('Deactivate', 'wpil'); ?></a>
                                <?php } ?>
                            </td>
                        </tr>
                            <?php if(!empty($authenticated)){ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Currently Selected GSC Profile', 'wpil'); ?></td>
                            <td>
                                <select multiple name="wpil_manually_select_gsc_profile[]" class="wpil-setting-multiselect" style="float:left; min-width:400px">
                                <?php foreach(Wpil_SearchConsole::get_profiles() as $key => $profile){ ?>
                                    <option value="<?=esc_attr($key)?>" <?=(in_array($profile, $gsc_profiles, true)) ? 'selected="selected"': '';?>><?=esc_html($profile)?></option>
                                <?php } ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 4px;"></i>
                                    <div>
                                        <?php _e('This is the manual GSC profile selector. It lists the currently selected GSC profile and allows you to pick a different one if Link Whisper\'s automatic selector wasn\'t able to find the right one.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php echo sprintf(__('Usually, the profile that matches your site\'s current URL or looks like "sc-domain:%s" is the correct one.', 'wpil'), wp_parse_url(get_home_url(), PHP_URL_HOST)); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                            <?php } ?>
                            <?php if($authorized){ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Disable Automatic Search Console Updates', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_disable_search_update" value="0" />
                                <input type="checkbox" name="wpil_disable_search_update" <?=get_option('wpil_disable_search_update', false)==1?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                    <div><?php _e('Link Whisper automatically scans for GSC updates via WordPress Cron. Turning this off will stop Link Whisper from performing the scan.', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Auto Select Top GSC Keywords', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_autotag_gsc_keywords" value="0" />
                                    <input type="checkbox" name="wpil_autotag_gsc_keywords" <?=Wpil_Settings::get_if_autotag_gsc_keywords()==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php _e('Turning this on will tell Link Whisper to automatically select the top GSC Keywords based on either impressions or clicks.', 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e('After changing this setting, please refresh the Target Keywords. The auto-selection process only activates during the Target Keyword Scan to save system resources.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row <?php echo (empty(Wpil_Settings::get_if_autotag_gsc_keywords())) ? 'hide-setting': '';?>">
                            <td scope='row'><?php _e('Auto Select GSC Keywords Basis', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:170px;">
                                    <?php $auto_select_basis = get_option('wpil_autotag_gsc_keyword_basis', 'impressions'); ?>
                                    <select name="wpil_autotag_gsc_keyword_basis" style="float:left; max-width:400px">
                                        <option value="impressions" <?php echo (('impressions' === $auto_select_basis) ? 'selected="selected"': ''); ?>><?php _e('Impressions', 'wpil'); ?>
                                        <option value="clicks" <?php echo (('clicks' === $auto_select_basis) ? 'selected="selected"': ''); ?>><?php _e('Clicks', 'wpil'); ?>
                                    </select>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php _e('How should Link Whisper decide which GSC keywords to auto select?', 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e('Should it pick the GSC keywords that have the most impressions, or the most clicks?', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row <?php echo (empty(Wpil_Settings::get_if_autotag_gsc_keywords())) ? 'hide-setting': '';?>">
                            <td scope='row'><?php _e('Number of GSC Keywords to Auto Select', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:170px;">
                                    <?php $auto_select_count = Wpil_Settings::get_autotag_gsc_keyword_count(); ?>
                                    <select name="wpil_autotag_gsc_keyword_count" style="float:left; max-width:400px">
                                        <option value="0" <?php echo ((0 === $auto_select_count) ? 'selected="selected"': ''); ?>><?php _e('Don\'t Auto Select', 'wpil'); ?>
                                    <?php for($i = 1; $i < 21; $i++){
                                        echo '<option value="' . $i . '" ' . (($i === $auto_select_count) ? 'selected="selected"': '') . '>' . $i . '</option>';
                                        } ?>
                                    </select>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div><?php _e('How many GSC Keywords should Link Whisper automatically set to active?', 'wpil'); ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                            <?php } ?>
                        <?php }else{ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Connect to Google Search Console', 'wpil'); ?></td>
                            <td>
                                <p><i><?php _e('Only admins can access the GSC settings.', 'wpil'); ?></i></p>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php if(defined('WPSEO_VERSION')){?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Only Create Outbound Links to Yoast Cornerstone Content', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_link_to_yoast_cornerstone" value="0" />
                                    <input type="checkbox" name="wpil_link_to_yoast_cornerstone" <?=get_option('wpil_link_to_yoast_cornerstone', false)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div><?php _e('Turning this on will tell Link Whisper to restrict the outbound link suggestions to posts marked as Yoast Cornerstone content.', 'wpil'); ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Only make suggestions based on Target Keywords', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_only_match_target_keywords" value="0" />
                                <input type="checkbox" name="wpil_only_match_target_keywords" <?=!empty(get_option('wpil_only_match_target_keywords', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Checking this will tell Link Whisper to only show suggestions that have matches based on the current post\'s Target Keywords.', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Target Keyword Sources', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block; position: relative;">
                                    <?php
                                        $target_keyword_sources = array_reverse(Wpil_TargetKeyword::get_available_keyword_sources());
                                        $active_keyword_sources = Wpil_Settings::getSelectedKeywordSources();
                                        $source_display_names   = Wpil_TargetKeyword::get_keyword_name_list();
                                    ?>
                                    <div class="wpil_help" style="position: absolute; right: -50px; top: -4px;">
                                        <i class="dashicons dashicons-editor-help"></i>
                                        <div>
                                            <?php _e('The toggle in this section allow you to select what Target Keyword sources it will extract data from.', 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e('The Custom Keywords are always enabled because they are manually entered.', 'wpil'); ?>
                                        </div>
                                    </div>
                                    <?php foreach ($target_keyword_sources as $source) : ?>
                                            <input type="checkbox" name="wpil_selected_target_keyword_sources[]" value="<?=$source?>" <?=in_array($source, $active_keyword_sources)?'checked':''?> <?php echo ($source === 'custom') ? 'disabled="disabled"': ''; ?>><label><?=$source_display_names[$source]?></label><br>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Add rel="noreferrer" to Created Links', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_add_noreferrer" value="0" />
                                <input type="checkbox" name="wpil_add_noreferrer" <?=!empty(get_option('wpil_add_noreferrer', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('Checking this will tell Link Whisper to add the noreferrer attribute to the links it creates. Adding this attribute will cause all clicks on inserted links to be counted as direct traffic on analytics systems.', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Point Suggestions From Staging to Live Site', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_filter_staging_url" value="0" />
                                <input type="checkbox" name="wpil_filter_staging_url" <?=$filter_staging_url?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 260px;">
                                        <?php _e('Checking this will tell Link Whisper that it\'s active on a staging site and that it should change the "home URL" portion of suggested links so they match the home URL of the live site.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This only applies to suggested links when they\'re created, existing links won\'t be changed. Also, this setting won\'t affect Autolinks or Custom Links.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Checking this will also set the link scanner to calculate inbound & outbound link stats based on the live site\'s domain, instead of the staging site\'s domain. So the stats on the staging site may change.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row <?php echo ($filter_staging_url) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Live Site Home URL', 'wpil'); ?></td>
                            <td>
                                <input type="text" name="wpil_live_site_url" placeholder="<?=esc_attr('https://example.com/')?>" value="<?=esc_attr(get_option('wpil_live_site_url', ''))?>" style="width: 600px" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 260px;">
                                        <?php _e('This is the home URL for the live site.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Link Whisper will replace the "home URL" portion of suggested links on the staging site with this home URL. That way, links created on the staging site will be pointing to pages on the live site\'s domain.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php echo sprintf(__('If this is the live site, then the URL should be: %s', 'wpil'), esc_attr(get_home_url())); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row <?php echo ($filter_staging_url) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Staging Site Home URL', 'wpil'); ?></td>
                            <td>
                                <input type="text" name="wpil_staging_site_url" placeholder="<?=esc_attr('https://staging.example.com/')?>" value="<?=esc_attr(get_option('wpil_staging_site_url', ''))?>" style="width: 600px" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('This is the home URL for the staging site.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Link Whisper will replace this "home URL" portion in suggested links on the staging site with the home URL for the live site.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php echo sprintf(__('If this is the staging site, then the URL should be: %s', 'wpil'), esc_attr(get_home_url())); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Ignore Image URLs', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_ignore_image_urls" value="0" />
                                <input type="checkbox" name="wpil_ignore_image_urls" <?=!empty(get_option('wpil_ignore_image_urls', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to ignore image URLs in the Links Report.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This will include image URLs inside anchor href attributes.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Include Image src URLs in Links Report', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_include_image_src" value="0" />
                                <input type="checkbox" name="wpil_include_image_src" <?=!empty(get_option('wpil_include_image_src', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to include image src URLs in the Links Report.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('The image URLs will be show up in the Outbound Internal link counts for the posts that they\'re in. They will not contribute to the Inbound Internal link counts for any posts.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        (<?php _e('This is the URL that\'s used in &lt;img&gt; tags. By default, Link Whisper scans image-related URls in &lt;a&gt; tags.', 'wpil'); ?>)
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Use "Ugly" Permalinks In Reports', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_use_ugly_permalinks" value="0" />
                                <input type="checkbox" name="wpil_use_ugly_permalinks" <?=!empty(get_option('wpil_use_ugly_permalinks', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 300px;">
                                        <?php _e('Checking this will tell Link Whisper to use WordPress\' "Ugly Permalinks" for the "View" links in the Link Whisper Reports.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Using the "Ugly" permalinks can save a surprising amount of time when loading the reports because we don\'t have to process all the rules required to calculate the correct URL for each post.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('One downside is that the Link Report\'s "Hidden by Redirect" icons may not be able to tell that the post is hidden, so the icons may fail to display on redirected posts.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        (<?php _e('This won\'t affect the inserted links or Suggestions, and it also won\'t change the links on the site itself. The "Ugly" permalinks will only be used for the Link Whisper "View" buttons in the Reports.', 'wpil'); ?>)
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Remove Inner HTML When Deleting Links', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_delete_link_inner_html" value="0" />
                                <input type="checkbox" name="wpil_delete_link_inner_html" <?=!empty(get_option('wpil_delete_link_inner_html', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 260px; display: none;">
                                        <?php _e('Checking this will tell Link Whisper to remove any HTML tags from link anchor text when links are deleted.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This is helpful when links use bold or italic tags for styling, and you don\'t want to leave these in the page when deleting the links.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        (<?php _e('One thing to be careful of is if the anchor has the opening tag but not the closing tag, deleting the link will leave behind the closing tag and this could mess up the page.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('EX: Deleting &lt;a href="example.com"&gt;&lt;strong&gt;testing&lt;/a&gt;&lt;/strong&gt; will leave behind the "&lt;/strong&gt;" tag, and this may change what content is bolded on the page', 'wpil'); ?>)
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Remove Inbound Internal Links When Their Target Post is Deleted', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_delete_links_to_post_on_delete" value="0" />
                                <input type="checkbox" name="wpil_delete_links_to_post_on_delete" <?=!empty(Wpil_Settings::delete_inbound_internal_on_post_delete())?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 260px; display: none;">
                                        <?php _e('Checking this will tell Link Whisper to remove any links pointing to a post when that post is deleted.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This is helpful because it removes 404 links automatically and reduces the need for URL redirections.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Since this is run when a post is deleted, you may find that it takes longer to delete a post than it used to.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Use SEO Titles Instead of Post Titles', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_use_seo_titles" value="0" />
                                <input type="checkbox" name="wpil_use_seo_titles" <?=!empty(get_option('wpil_use_seo_titles', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to use the post or term\'s SEO title instead of the post title in the reports and when making suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This will change how the post is titled in the reports and the suggestions you see, but it won\'t change the post title that\'s shown to visitors.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Make Suggestions Based on URL Slug Instead of Post Title', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_post_slug_for_suggestions" value="0" />
                                <input type="checkbox" name="wpil_post_slug_for_suggestions" <?=!empty(get_option('wpil_post_slug_for_suggestions', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will tell Link Whisper to use the post or term\'s SEO title instead of the post title in the reports and when making suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This will change how the post is titled in the reports and the suggestions you see, but it won\'t change the post title that\'s shown to visitors.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row wpil-partial-title-setting">
                            <td scope='row'><?php _e('Only Use Part of a Post Title When Making Suggestions', 'wpil'); ?></td>
                            <td>
                                <?php $partial_title = get_option('wpil_get_partial_titles', false); ?>
                                <select name="wpil_get_partial_titles" style="float:left; max-width:400px">
                                    <option value="0" <?php echo ((empty($partial_title)) ? 'selected="selected"': ''); ?>><?php _e('Use the Full Title', 'wpil'); ?>
                                    <option value="1" <?php echo (($partial_title === '1') ? 'selected="selected"': ''); ?>><?php _e('Use First Few Words', 'wpil'); ?>
                                    <option value="2" <?php echo (($partial_title === '2') ? 'selected="selected"': ''); ?>><?php _e('Use Last Few Words', 'wpil'); ?>
                                    <option value="3" <?php echo (($partial_title === '3') ? 'selected="selected"': ''); ?>><?php _e('Use Words Before Delimiter', 'wpil'); ?>
                                    <option value="4" <?php echo (($partial_title === '4') ? 'selected="selected"': ''); ?>><?php _e('Use Words After Delimiter', 'wpil'); ?>
                                </select>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('This option will tell Link Whisper to only use a section of the words in a post title when making suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This can improve suggestions if you have non post-specific words in the title.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('You can select either a number of words from the start or end of the post title for use in suggestions, or you can choose to split the title on a delimiting character and use the front or back end of the title for suggestions.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row <?php echo ($partial_title === '1' || $partial_title === '2') ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Number of Title Words to Use', 'wpil'); ?></td>
                            <td>
                                <?php $word_count = get_option('wpil_partial_title_word_count', 0); ?>
                                <select name="wpil_partial_title_word_count" style="float:left; max-width:100px">
                                    <option value="0" <?=0===(int)$word_count ? 'selected' : '' ?>><?php _e('No Limit', 'wpil'); ?></option>
                                    <?php for($i = 2; $i <= 25; $i++) : ?>
                                        <option value="<?=$i?>" <?=$i===(int)$word_count ? 'selected' : '' ?>><?=$i?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Link Whisper will use the number of words you set here when making suggestions and will ignore the rest in the title.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('If you\'ve selected "Use First Few Words", Link Whisper will use the words from the start of the title.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('If you\'ve selected "Use Last Few Words", Link Whisper will use the words from the end of the title.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row <?php echo ($partial_title === '3' || $partial_title === '4') ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Delimiter Character to Split the Title on', 'wpil'); ?></td>
                            <td>
                                <?php $split_char = get_option('wpil_partial_title_split_char', '')?>
                                <input type="text" name="wpil_partial_title_split_char" style="width:400px;" <?php echo ($split_char !== '') ? 'value="' . esc_attr($split_char) . '"': 'placeholder="' . __('Enter The Character To Split Titles on.', 'wpil') . '"';?> />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('The delimiting character is a consistent character that you use in post titles to separate the post\'s name/title from a static tagline that\'s applied to all posts.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('For example, if you have a post called "9 Great Things you Need to Have", and your site has a tagline of "Greatest Things Online", you could combine them into a full title of: "9 Great Things you Need to Have - Greatest Things Online"', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('In this case, the \'-\' character is the delimiter between the post\'s "name" and the site\'s static tagline.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                        <?php if(current_user_can('activate_plugins')){ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Interlink External Sites', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_link_external_sites" value="0" />
                                <input type="checkbox" name="wpil_link_external_sites" <?=$site_linking_enabled==1?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('Checking this will allow you to make links to external sites that you own via the outbound suggestions.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('All sites must have Link Whisper installed and be in the same licensing plan.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <a href="https://linkwhisper.com/knowledge-base/how-to-make-link-suggestions-between-sites/" target="_blank"><?php _e('Read more...', 'wpil'); ?></a>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php $access_code = get_option('wpil_link_external_sites_access_code', false); ?>
                        <tr class="wpil-site-linking-setting-row wpil-advanced-settings wpil-setting-row" <?php echo ($site_linking_enabled === '1') ? '': 'style="display:none;"'; ?>>
                            <td scope='row'><?php _e('Site Interlinking Access Code', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_link_external_sites_access_code" value="0" />
                                <input type="text" name="wpil_link_external_sites_access_code" style="width:400px;" <?php echo (!empty($access_code)) ? 'value="' . $access_code . '"': 'placeholder="' . __('Enter Access Code', 'wpil') . '"';?> />
                                <a href="#" class="wpil-generate-id-code button-primary" data-wpil-id-code="1" data-wpil-base-id-string="<?php echo Wpil_SiteConnector::generate_random_id_string(); ?>"><?php _e('Generate Code', 'wpil'); ?></a>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div><?php _e('This code is used to secure the connection between all linked sites. Use the same code on all sites you want to link', 'wpil'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <?php if(!empty($access_code)){ ?>
                        <tr class="wpil-linked-sites-row wpil-site-linking-setting-row wpil-advanced-settings wpil-setting-row" <?php echo ($site_linking_enabled === '1') ? '': 'style="display:none;"'; ?>>
                            <td scope='row'><?php _e('Home Urls of Linked Sites', 'wpil'); ?></td>
                            <td class="wpil-linked-sites-cell">
                                <?php
                                    $unregister_text = __('Unregister Site', 'wpil');
                                    $remove_text    = __('Remove Site', 'wpil');
                                    $import_text   = __('Import Post Data', 'wpil');
                                    $refresh_text = __('Refresh Post Data', 'wpil');
                                    $import_loadingbar = '<div class="progress_panel loader site-import-loader" style="display: none;"><div class="progress_count" style="width:100%">' . __('Importing Post Data', 'wpil') . '</div></div>';
                                    $link_site_text = __('Attempt Site Linking', 'wpil');
                                    $disable_external_linking = __('Disable Suggestions', 'wpil');
                                    $enable_external_linking = __('Enable Suggestions', 'wpil');
                                    $sites = Wpil_SiteConnector::get_registered_sites();
                                    $linked_sites = Wpil_SiteConnector::get_linked_sites();
                                    $disabled_suggestion_sites = get_option('wpil_disable_external_site_suggestions', array());

                                    foreach($sites as $site){
                                        // if the site has been linked
                                        if(in_array($site, $linked_sites, true)){
                                            $button_text = (Wpil_SiteConnector::check_for_stored_data($site)) ? $refresh_text: $import_text;
                                            $suggestions_disabled = isset($disabled_suggestion_sites[$site]);
                                            echo '<div class="wpil-linked-site-input">
                                                    <input type="text" name="wpil_linked_site_url[]" style="width:600px" value="' . $site . '" />
                                                    <label>
                                                        <a href="#" class="wpil-refresh-post-data button-primary site-linking-button" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'download-site-data-nonce') . '">' . $button_text . '</a>
                                                        <a href="#" class="wpil-external-site-suggestions-toggle button-primary site-linking-button" data-suggestions-enabled="' . ($suggestions_disabled ? 0: 1) . '" data-site-url="' . esc_url($site) . '" data-enable-text="' . $enable_external_linking . '" data-disable-text="' . $disable_external_linking . '" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'toggle-external-site-suggestions-nonce') . '">' . ($suggestions_disabled ? $enable_external_linking: $disable_external_linking) . '</a>
                                                        <a href="#" class="wpil-unlink-site-button button-primary button-purple site-linking-button" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'unlink-site-nonce') . '">' . $remove_text . '</a>
                                                        ' . $import_loadingbar . '
                                                    </label>
                                                </div>';
                                        }else{
                                            // if the site hasn't been linked, but only registered
                                            echo '<div class="wpil-linked-site-input">
                                                    <input type="text" name="wpil_linked_site_url[]" style="width:600px" value="' . $site . '" />
                                                    <label>
                                                        <a href="#" class="wpil-link-site-button button-primary" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'link-site-nonce') . '">' . $link_site_text . '</a>
                                                        <a href="#" class="wpil-unregister-site-button button-primary button-purple site-linking-button" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'unregister-site-nonce') . '">' . $unregister_text . '</a>
                                                    </label>
                                                </div>';
                                        }
                                    }
                                    echo '<div class="wpil-linked-site-add-button-container">
                                            <a href="#" class="button-primary wpil-linked-site-add-button">' . __('Add Site Row', 'wpil') . '</a>
                                        </div>';

                                    echo '<div class="wpil-linked-site-input template-input hidden">
                                            <input type="text" name="wpil_linked_site_url[]" style="width:600px;" />
                                            <label>
                                                <a href="#" class="wpil-register-site-button button-primary" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'register-site-nonce') . '">' . __('Register Site', 'wpil') . '</a>
                                            </label>
                                        </div>';
                                ?>
                                <input type="hidden" id="wpil-site-linking-initial-loading-message" value="<?php echo esc_attr__('Importing Post Data', 'wpil'); ?>">
                            </td>
                        </tr>
                        <tr class="wpil-linked-sites-row wpil-site-linking-setting-row wpil-advanced-settings wpil-setting-row" <?php echo ($site_linking_enabled === '1') ? '': 'style="display:none;"'; ?>>
                            <td scope='row'><?php _e('Disable Automatic Interlinked Site Updating', 'wpil'); ?></td>
                            <td class="wpil-linked-sites-cell">
                                <input type="hidden" name="wpil_disable_external_site_updating" value="0" />
                                <input type="checkbox" name="wpil_disable_external_site_updating" <?=!empty(get_option('wpil_disable_external_site_updating', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin-top: -195px;">
                                        <?php _e('Checking this will disable the notifications that Link Whisper sends to the interlinked sites when you update or delete a post.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('These notifications keep the linked sites up to date about content changes on this site, but they also slow down post updating/deleting.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Disabling the notifications will speed up post updating and deleting, but over time the data on linked sites will become out of date and need to be manually updated.', 'wpil'); ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php }else{ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Interlink External Sites', 'wpil'); ?></td>
                            <td>
                                <p><i><?php _e('Only admins can access the site linking settings.', 'wpil'); ?></i></p>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Delete Click Data Older Than', 'wpil'); ?></td>
                            <td>
                                <div style="display: flex;">
                                    <select name="wpil_delete_old_click_data" style="float:left;">
                                        <?php $day_count = get_option('wpil_delete_old_click_data', '0'); ?>
                                        <option value="0" <?php selected('0', $day_count) ?>><?php _e('Never Delete'); ?></option>
                                        <option value="1" <?php selected('1', $day_count) ?>><?php _e('1 Day'); ?></option>
                                        <option value="3" <?php selected('3', $day_count) ?>><?php _e('3 Days'); ?></option>
                                        <option value="7" <?php selected('7', $day_count) ?>><?php _e('7 Days'); ?></option>
                                        <option value="14" <?php selected('14', $day_count) ?>><?php _e('14 Days'); ?></option>
                                        <option value="30" <?php selected('30', $day_count) ?>><?php _e('30 Days'); ?></option>
                                        <option value="180" <?php selected('180', $day_count) ?>><?php _e('180 Days'); ?></option>
                                        <option value="365" <?php selected('365', $day_count) ?>><?php _e('1 Year'); ?></option>
                                    </select>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -50px 0 0 30px;">
                                            <?php _e("Link Whisper will delete tracked clicks that are older than this setting.", 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e("By default, Link Whisper doesn't delete tracked click data.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Disable Click Tracking', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_disable_click_tracking" value="0" />
                                    <input type="checkbox" name="wpil_disable_click_tracking" <?=get_option('wpil_disable_click_tracking', false)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -180px 0 0 30px;">
                                            <?php _e("Activating this will disable the Click Tracking and will remove the Click Report from the Dashboard", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("The Click Tracking uses the Link Whisper Frontend script to track visitor clicks. So disabling this and having the \"Use JS to force opening in new tabs\" off will remove the script.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Don\'t Collect User-Identifying Information with Click Tracking', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_disable_click_tracking_info_gathering" value="0" />
                                    <input type="checkbox" name="wpil_disable_click_tracking_info_gathering" <?=$disable_ip_tracking==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -230px 0 0 30px;">
                                            <?php _e("Activating this will set the Click Tracking to not collect information that could be used to identify a user", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("By default when a user clicks a link, Link Whisper collects the IP address of the visitor.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("If the visitor has an account on the site, then Link Whisper collects their user id too.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("With collection disabled, this data will not be saved.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Track Link Clicks on all Elements', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_track_all_element_clicks" value="0" />
                                    <input type="checkbox" name="wpil_track_all_element_clicks" <?=get_option('wpil_track_all_element_clicks', 0)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -280px 0 0 30px; width: 270px;">
                                            <?php _e("Activating this will set the Click Tracking to track link clicks on all parts of a page.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("By default, only clicks in the post content areas are tracked so you can easily see how your in-content links are performing.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("But when this setting is active, Link Whisper will track clicks in your page header, footer, sidebars & menus as well as widget areas.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("To help identify where in the page the link was clicked, the Detailed Click Report pages will show a 'location' stat for each click.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php if(Wpil_ClickTracker::check_for_stored_visitor_data()){ ?>
                        <tr class="wpil-advanced-settings wpil-setting-row <?php echo (empty($disable_ip_tracking)) ? 'hide-setting': '';?>">
                            <td scope='row'><?php _e('Delete all stored visitor data', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_delete_stored_visitor_data" value="0" />
                                    <input type="checkbox" name="wpil_delete_stored_visitor_data" value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -80px 0 0 30px;">
                                            <?php _e("Activating this will tell Link Whisper to delete all visitor data that it has stored.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("Currently, the only visitor data stored is used in the Click Report.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Attempt CDN Clearing after Link Delete', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_clear_cdn_link_delete" value="0" />
                                    <input type="checkbox" name="wpil_clear_cdn_link_delete" <?=get_option('wpil_clear_cdn_link_delete', 0)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -280px 0 0 30px; width: 270px;">
                                            <?php _e("Activating this will tell Link Whisper attempt to clear the CDN cache for posts that have links deleted from them.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("Sometimes when a post has a link deleted, the cached version of the post doesnt't get updated on the CDN.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("To force the CDN to update, this setting will try asking the CDN to clear the cached version of the post and pull up a fresh copy.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("This will slow down bulk deletes since it takes a moment for the CDN to respond, and it's possible that the CDN will deny the request.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Attempt Object Cache Flush After Link Delete', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_object_cache_flush" value="0" />
                                    <input type="checkbox" name="wpil_object_cache_flush" <?=get_option('wpil_object_cache_flush', 0)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -280px 0 0 30px; width: 270px;">
                                            <?php _e("Activating this will tell Link Whisper attempt to flush any active object caching after links or autolinking rules are deleted.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("Object caching works somewhat differently from normal page caching in that it caches the data that's used to generate pages and reports.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("Because of this, it's possible to delete a link from the site, but still have it show up in places like the Post Edit Screen since the editor content was cached.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("To minimize the impact on the server, Link Whisper will try to flush the cache at the end of bulk actions so the cache can be flushed as little as possible.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Trigger Post Update after Link Delete', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_update_post_after_action" value="0" />
                                    <input type="checkbox" name="wpil_update_post_after_action" <?=get_option('wpil_update_post_after_action', 0)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -280px 0 0 30px; width: 270px;">
                                            <?php _e("Activating this will tell Link Whisper trigger a WordPress 'post update' event for posts that have links deleted from them.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("This can be useful when the site's cache doesn't update the cached page after links are deleted.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("This will slow down bulk deletes since the 'post update' process is memory intensive.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Disable Link Creation Tracking', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_disable_link_insert_tracking" value="0" />
                                    <input type="checkbox" name="wpil_disable_link_insert_tracking" <?=get_option('wpil_disable_link_insert_tracking', false)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -100px 0 0 30px;">
                                            <?php _e("Activating this will prevent Link Whisper from adding a tracking id to the links it creates.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("The tracking id looks like this data-wpil-monitor-id=\"1\" and it's included in links created by Link Whisper so we can tell for sure that Link Whisper created them.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("If you don\'t want the id added to links, you can turn it off and Link Whisper won\'t add it. This will prevent Link Whisper from being able to target the links with the \"Delete All Link Whisper Links\" (coming soon!) feature since they will look like manually created links.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Hide Link Attributes Created by Link Whisper', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_filter_out_frontend_link_attributes" value="0" />
                                    <input type="checkbox" name="wpil_filter_out_frontend_link_attributes" <?=get_option('wpil_filter_out_frontend_link_attributes', false)==1?'checked':''?> value="1" />
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -100px 0 0 30px;">
                                            <?php _e("Activating this will have Link Whisper filter displayed posts to remove any attributes included in links it has created.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("Link Whisper uses attributes in links to track them and identify them for reports, and filtering them keeps them from being displayed in the page source on the frontend.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'><?php _e('Delete all Link Whisper Data', 'wpil'); ?></td>
                            <td>
                                <div style="max-width:80px;">
                                    <input type="hidden" name="wpil_delete_all_data" value="0" />
                                    <input type="checkbox" class="danger-zone" name="wpil_delete_all_data" <?=get_option('wpil_delete_all_data', false)==1?'checked':''?> value="1" />
                                    <input type="hidden" class="wpil-delete-all-data-message" value="<?php echo sprintf(__('Activating this will tell Link Whisper to delete ALL link Whisper related data when the plugin is deleted. %s This will remove all settings and stored data. Links inserted into content by Link Whisper will still exist. %s Undoing actions like URL changes will be impossible since the records of what the url used to be will be deleted as well. %s Please only activate this option if you\'re sure you want to delete all data.', 'wpil'), '&lt;br&gt;&lt;br&gt;', '&lt;br&gt;&lt;br&gt;', '&lt;br&gt;&lt;br&gt;'); ?>">
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -100px 0 0 30px;">
                                            <?php _e("Activating this will tell Link Whisper to delete ALL link Whisper related data when the plugin is deleted.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("This includes any Settings, Autolinking Rules, URL Changing Rules, and Report Data. This will not delete any links that have been created.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("Please only activate this option if you're sure you want to delete ALL link Whisper data.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-advanced-settings wpil-setting-row">
                            <td scope='row'>
                                <span class="settings-carrot">
                                    <?php _e('Debug Settings', 'wpil'); ?>
                                </span>
                            </td>
                            <td class="setting-control-container">
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_2_debug_mode" value="0" />
                                    <input type='checkbox' name="wpil_2_debug_mode" <?=get_option('wpil_2_debug_mode')==1?'checked':''?> value="1" />
                                    <label><?php _e('Enable Debug Mode?', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -220px 0 0 30px;">
                                            <p><?php _e('If you\'re having errors, or it seems that data is missing, activating Debug Mode may be useful in diagnosing the problem.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('Enabling Debug Mode will cause your site to display any errors or code problems it\'s expiriencing instead of hiding them from view.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('These error notices may be visible to your site\'s visitors, so it\'s recommended to only use this for limited periods of time.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('(If you are already debugging with WP_DEBUG, then there\'s no need to activate this.)', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_option_update_reporting_data_on_save" value="0" />
                                    <input type='checkbox' name="wpil_option_update_reporting_data_on_save" <?=get_option('wpil_option_update_reporting_data_on_save')==1?'checked':''?> value="1" />
                                    <label><?php _e('Run a check for un-indexed posts on each post save?', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -220px 0 0 30px;">
                                            <p><?php _e('Checking this will tell Link Whisper to look for any posts that haven\'t been indexed for the link reports every time a post is saved.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('In most cases this isn\'t necessary, but if you\'re finding that some of your posts aren\'t displaying in the reports screens, this may fix it.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('One word of caution: If you have many un-indexed posts on the site, this may cause memory / timeout errors.', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_include_post_meta_in_support_export" value="0" />
                                    <input type='checkbox' name="wpil_include_post_meta_in_support_export" <?=get_option('wpil_include_post_meta_in_support_export')==1?'checked':''?> value="1" />
                                    <label><?php _e('Include post meta in support data export?', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -220px 0 0 30px;">
                                            <p><?php _e('Checking this will tell Link Whisper to include additional post data in the data for support export.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('This isn\'t needed for most support cases. It\'s most commonly used for troubleshooting issues with page builders', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_clear_error_checker_process" value="0" />
                                    <input type='checkbox' name="wpil_clear_error_checker_process" <?=get_option('wpil_clear_error_checker_process')==1?'checked':''?> value="1" />
                                    <label><?php _e('Cancel active Broken Link scans?', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -220px 0 0 30px;">
                                            <p><?php _e('Checking this will tell Link Whisper to cancel any active Broken Link scans and allow you to access the Error Report table.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('This can be helpful when the Broken Link scan gets stuck, but it may not solve the underlying issue.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('Please close any tabs that have an active Broken Link scan running before activating this option.', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_optimize_option_table" value="0" />
                                    <input type="checkbox" name="wpil_optimize_option_table" <?=get_option('wpil_optimize_option_table', 0)==1?'checked':''?> value="1" />
                                    <label><?php _e('Attempt to Manage Option Table Overhead on Scans?', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -280px 0 0 30px; width: 270px;">
                                            <?php _e("Activating this will tell Link Whisper to try reducing the amount of database overhead that's generated when running a scan.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("This can be useful when running the scan creates so much overhead the database exceeds the normal data storage limits.", 'wpil'); ?>
                                            <br>
                                            <br>
                                            <?php _e("This is an advanced setting that may slow down the scan somewhat and could cause freezes on sites with exceptionally large option tables. The system only engages when the overhead exceeds 1 gigabyte.", 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_force_database_update" value="0" />
                                    <input type='checkbox' name="wpil_force_database_update" value="1" />
                                    <label><?php echo sprintf(__('Re-run the database table %s routine?', 'wpil'), '<strong>update</strong>'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -230px 0 0 30px;">
                                            <p><?php _e('Checking this will tell Link Whisper re-run the database table update process.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('This process is supposed to automatically run when the plugin is updated, but sometimes it gets interrupted.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('This can help when you have errors saying that certain columns do not exist in database tables.', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_force_create_database_tables" value="0" />
                                    <input type='checkbox' name="wpil_force_create_database_tables" value="1" />
                                    <label><?php echo sprintf(__('Re-run the database table %s routine?', 'wpil'), '<strong>creation</strong>'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -260px 0 0 30px;">
                                            <p><?php _e('Checking this will tell Link Whisper re-run the database table creation process.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('This process is supposed to automatically run when the plugin is updated, but sometimes it gets interrupted.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('This can help when you have errors saying that certain database tables do not exist.', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <div class="setting-control">
                                    <input type="hidden" name="wpil_reset_table_display_counts" value="0" />
                                    <input type='checkbox' name="wpil_reset_table_display_counts" value="1" />
                                    <label><?php _e('Reset the items to display in the Reports to 20?', 'wpil'); ?></label>
                                    <div class="wpil_help" style="float:right;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div style="margin: -260px 0 0 30px;">
                                            <p><?php _e('Checking this will tell Link Whisper reset the number if items to display in the Reports back to the default of 20 items.', 'wpil'); ?></p>
                                            <br>
                                            <p><?php _e('This is useful for cases where the Reports fail to load because there\'s too many results to display.', 'wpil'); ?></p>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row wpil-activate-related-posts-row">
                            <td scope='row'><?php _e('Activate Related Posts Widget', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_activate_related_posts" value="0" />
                                <input type="checkbox" name="wpil_activate_related_posts" <?=!empty(get_option('wpil_activate_related_posts', false))?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 300px;">
                                        <?php _e('Checking this will activate Link Whisper\'s Related Post Widget.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('This feature allows you to create a related posts section at the bottom of your posts that will link to other posts on the site.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        (<?php _e('For custom themes and unique layouts, the Related Post template that the widget uses can be overridden by creating a custom template in your theme\'s files.', 'wpil'); ?>)</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Add the Related Posts Widget By:', 'wpil'); ?></td>
                            <td>
                                <input type="radio" name="wpil_related_posts_insert_method" id="wpil_related_posts_insert_method_append" value="append" <?php checked($related_posts_settings['insert_method'], 'append'); ?>>
                                <label for="wpil_related_posts_insert_method_append" style="margin: 0 15px 0 0;"><?php _e('Automatically Adding to Posts', 'wpil'); ?></label>
                                <input type="radio" name="wpil_related_posts_insert_method" id="wpil_related_posts_insert_method_shortcode" value="shortcode" <?php checked($related_posts_settings['insert_method'], 'shortcode'); ?>>
                                <label for="wpil_related_posts_insert_method_shortcode" style="margin: 0 15px 0 0;"><?php _e('Using a Shortcode', 'wpil'); ?></label>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 300px;">
                                        <?php _e('How should Link Whisper add the Related Post section to your posts?', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Selecting the option to automatically add to posts will have Link Whisper add the widget to all the posts that are available to it.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Selecting the option to use the shortcode will require you to enter the Related Posts shortcode in all posts that you want to have the section show up in. This can be more flexible since you can choose where the related post section will be created, and may work better with complex themes and page builders.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Post Types to Include Related Posts Widget on', 'wpil'); ?></td>
                            <td>
                                <div class="expandable-area-container contracted">
                                    <div class="wpil_help" style="float:right; position: relative; left: 30px;">
                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                        <div>
                                            <?php
                                                _e('This setting controls the post types that Link Whisper will add the Related Posts widget to.', 'wpil');
                                                echo '<br /><br />';
                                                _e('If a post is not in one of the selected types, the Related Posts widget will not display.', 'wpil');
                                                echo '<br /><br />';
                                                _e('After changing the selected post types, there is no need to run a Link Scan.', 'wpil');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="wpil_row_expand contracted">
                                        <i class="dashicons dashicons-insert" style="margin-top: 6px;"></i>
                                        <i class="dashicons dashicons-remove" style="margin-top: 6px;"></i>
                                    </div>
                                    <div class="expandable-area contracted" style="display: inline-block; height:75px; overflow:hidden;" data-wpil-contract-size="75px">
                                        <?php foreach ($types_available as $type => $label) : ?>
                                            <input type="checkbox" name="wpil_related_posts_active_post_types[]" value="<?=$type?>" <?=in_array($type, $related_posts_types)?'checked':''?>><label><?php echo esc_html($label);?></label><br>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row wpil-related-posts-shortcode-row <?php echo ($related_active && $related_posts_settings['insert_method'] === 'shortcode') ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Link Whisper Related Post Shortcode', 'wpil'); ?></td>
                            <td>
                                <div>
                                    <pre style="max-width: 200px; display:inline-block">[link-whisper-related-posts]</pre>
                                    <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                        <i class="dashicons dashicons-editor-help"></i>
                                        <div>
                                            <?php _e('This is the Link Whisper Related Posts Shortcode.', 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e('To use it, copy the whole code including the square brackets and paste it into a content area where you want the Related Posts widget to be created.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Related Post Widget Title', 'wpil'); ?></td>
                            <td>
                                <input type="text" name="wpil_related_posts_widget_text[title]" id="wpil_related_posts_widget_text_title" value="<?php echo esc_attr($related_posts_settings['text']['title']) ?>">
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('This is the text that Link Whisper will use for the title of the Related Posts widget.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Related Post Widget Title Tag', 'wpil'); ?></td>
                            <td>
                                <select name='wpil_related_posts_widget_text[title_tag]' id='wpil_related_posts_widget_text_title_tag' style="width: 200px;float:left;">
                                    <?php
                                        foreach(array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div') as $possible_tag){
                                            echo '<option value="' . $possible_tag . '" ' . selected($possible_tag, trim($related_posts_settings['text']['title_tag'])) . '>' . $possible_tag . '</option>';
                                        } 
                                    ?>
                                </select>
                                <div class="wpil_help">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="margin: -80px 0px 0px 30px; width: 300px;">
                                        <?php 
                                        _e('This is the type of HTML tag that Link Whisper will use for the Related Posts title.', 'wpil');
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Related Post Widget Description', 'wpil'); ?></td>
                            <td>
                                <textarea name="wpil_related_posts_widget_text[description]" id="wpil_related_posts_widget_text_description" class="regular-text" rows=5><?php echo esc_textarea($related_posts_settings['text']['description']) ?></textarea>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('This is for adding a short description under the Related Posts title.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Its entirely optional, and empty by default.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Hide Related Posts Widget When Empty', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_related_posts_hide_empty_widget" value="0" />
                                <input type="checkbox" name="wpil_related_posts_hide_empty_widget" <?=!empty($related_posts_settings['hide_empty_widget'])?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 300px;">
                                        <?php _e('This setting tell Link Whisper if it should hide the Related Post widget when there are no related posts to show.', 'wpil'); ?>
                                        <br>
                                        <br>
                                        <?php _e('By default, this is "ON" so that the page is kept neat. Toggling it "OFF" will have Link Whisper display the empty Related Post widget with a message saying that "no posts were found".', 'wpil'); ?>
                                        <br>
                                        <br>
                                        <?php _e('The Related Post widget is always visible to Admins when empty.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row wpil-related-posts-hide-empty-widget-row <?php echo ($related_active && empty($related_posts_settings['hide_empty_widget'])) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Related Post Widget "No Posts" Message', 'wpil'); ?></td>
                            <td>
                                <textarea name="wpil_related_posts_widget_text[empty_message]" id="wpil_related_posts_widget_text_empty_message" class="regular-text" rows="3"><?php echo esc_textarea($related_posts_settings['text']['empty_message']) ?></textarea>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('This is the message that will be displayed to visitors when a Related Post widget is empty.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Use Thumbnail in Related Post Items', 'wpil'); ?></td>
                            <td>
                                <input type="hidden" name="wpil_related_posts_use_thumbnail" value="0" />
                                <input type="checkbox" name="wpil_related_posts_use_thumbnail" <?=!empty($related_posts_settings['use_thumbnail'])?'checked':''?> value="1" />
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 300px;">
                                        <?php _e('Checking this will tell Link Whisper to include the linked post\'s thumbnail along with the link.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row wpil-related-posts-thumbnail-row <?php echo ($related_active && $thumbnail_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Thumbnail Position', 'wpil'); ?></td>
                            <td>
                                <select name="wpil_related_posts_thumbnail_position" style="float:left;">
                                    <option value="above" <?php selected('above', $related_posts_settings['thumbnail_position']) ?>><?php _e('Put Thumbnail Above Link Text.', 'wpil'); ?></option>
                                    <option value="below" <?php selected('below', $related_posts_settings['thumbnail_position']) ?>><?php _e('Put Thumbnail Below Link Text.', 'wpil'); ?></option>
                                    <option value="inside" <?php selected('inside', $related_posts_settings['thumbnail_position']) ?>><?php _e('Use Thumbnail In Place Of Link Text.', 'wpil'); ?></option>
                                </select>
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 400px;">
                                        <?php _e('This setting controls where the thumbnail is positioned in relation to the related post link text.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to "Above" will put the post\'s thumbnail above the link text.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to "Below" will put the post\'s thumbnail under the link text.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to "In Place of the Link Text" will have Link Whisper remove the link text and replace it with the thumbnail as clickable link.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row wpil-related-posts-thumbnail-row <?php echo ($related_active && $thumbnail_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Thumbnail Size', 'wpil'); ?></td>
                            <td>
                                <input type="number" name="wpil_related_posts_thumbnail_size" step="1" min="0" max="512" value="<?php echo $related_posts_settings['thumbnail_size']; ?>">
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 300px;">
                                        <?php _e('This setting controls how wide in pixels the thumbnail image will be in the related post item.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Related Post Widget Styling', 'wpil'); ?></td>
                            <td>
                                <div class="expandable-area-container contracted no-shadow">
                                    <div class="wpil_row_expand contracted" style="display:flex; left:0px;">
                                        <span class="expandable-area-text contracted-text"><?php _e('Show Styling Settings', 'wpil'); ?></span>
                                        <span class="expandable-area-text expanded-text"><?php _e('Hide Styling Settings', 'wpil'); ?></span>
                                        <i class="dashicons dashicons-insert" style="margin-top: 6px;"></i>
                                        <i class="dashicons dashicons-remove" style="margin-top: 6px;"></i>
                                    </div>
                                    <div class="expandable-area contracted wpil-related-posts-styling-controls" style="display: inline-block; height:0px; overflow:hidden;" data-wpil-contract-size="0px">
                                        <div style="display: inline-block;">
                                            <label style="display: flex; justify-content: space-between; width: calc(100% + 50px); align-items: center;"><?php _e('Show Main Styling', 'wpil'); ?><input type="checkbox" id="wpil-related-posts-styling-type-toggle" class="always-active"><?php _e('Show Mobile Only Styling', 'wpil'); ?></label>
                                        </div><br><br>
                                        <div class="wpil-related-posts-full-styling-container" style="display:flex;">
                                            <div style="margin-right: 50px;">
                                                <div>
                                                    <strong><?php _e('Widget Section Margins:', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Widget Section Margins control the size of the margins around the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('These are useful for adjusting the positioning of the Related Post widget on the page.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('For example, if you increase the "Margin Top" number, the widget will move "down" the page and there will be an increased gap between the bottom of the post content and the top of the widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('The increments are measured in pixels, so choosing "100" for a margin is the same as 100px in the stylesheet.', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Margin Top', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['widget-margin-top'] === 'false' || $rp_full_style['widget-margin-top'] === false || $rp_full_style['widget-margin-top'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][widget-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-top']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][widget-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-top']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][widget-margin-top]" class="wpil-related-post-number-selector-empty" value="false" <?php echo (!$disabled) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Right', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['widget-margin-right'] === 'false' || $rp_full_style['widget-margin-right'] === false || $rp_full_style['widget-margin-right'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][widget-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-right']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][widget-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-right']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][widget-margin-right]" class="wpil-related-post-number-selector-empty" value="false" <?php echo (!$disabled) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Bottom', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['widget-margin-bottom'] === 'false' || $rp_full_style['widget-margin-bottom'] === false || $rp_full_style['widget-margin-bottom'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][widget-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-bottom']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][widget-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-bottom']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][widget-margin-bottom]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['widget-margin-bottom'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Left', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['widget-margin-left'] === 'false' || $rp_full_style['widget-margin-left'] === false || $rp_full_style['widget-margin-left'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][widget-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-left']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][widget-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-margin-left']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][widget-margin-left]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['widget-margin-left'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Widget Section Colors:', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Widget Section Colors control the main colors used in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set the widget\'s background color, the color of the widget\'s title text, and the color of the widget\'s description text.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a color, please click on the "clear" button next to the color selector.', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Background Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_full_style['widget-background-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[full][widget-background-color]" value="<?php echo esc_attr($rp_full_style['widget-background-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[full][widget-background-color]" value="0" <?php echo !empty($rp_full_style['widget-background-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_full_style['widget-background-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Title Text Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_full_style['widget-title-text-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[full][widget-title-text-color]" value="<?php echo esc_attr($rp_full_style['widget-title-text-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[full][widget-title-text-color]" value="0" <?php echo !empty($rp_full_style['widget-title-text-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_full_style['widget-title-text-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Description Text Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_full_style['widget-description-text-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[full][widget-description-text-color]" value="<?php echo esc_attr($rp_full_style['widget-description-text-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[full][widget-description-text-color]" value="0" <?php echo !empty($rp_full_style['widget-description-text-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_full_style['widget-description-text-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Widget Section Font Sizes:', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Widget Section Font Sizes control the sizes of the header text in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set the widget\'s title text size and the text size of the widget\'s description text.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a size selection, please click on the "X" button next to the size selector.', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Title', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['widget-title-font-size'] === 'false' || $rp_full_style['widget-title-font-size'] === false || $rp_full_style['widget-title-font-size'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][widget-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-title-font-size']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][widget-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-title-font-size']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][widget-title-font-size]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['widget-title-font-size'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Description', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['widget-description-font-size'] === 'false' || $rp_full_style['widget-description-font-size'] === false || $rp_full_style['widget-description-font-size'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][widget-description-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-description-font-size']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][widget-description-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['widget-description-font-size']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][widget-description-font-size]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['widget-description-font-size'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div>
                                                <div>
                                                    <strong><?php _e('Item Margins:', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Margins control the size of the margins around the post items inside the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('These are useful for adjusting the size and positioning of items inside the Related Post widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('For example, if you increase the "Margin Left" number, all of the posts in the widget will move left and compress as needed. Increasing the "Margin Left" and the "Margin Right" will increase the space between the post items by compressing them.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('The increments are measured in pixels, so choosing "100" for a margin is the same as 100px in the stylesheet.', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Margin Top', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['item-margin-top'] === 'false' || $rp_full_style['item-margin-top'] === false || $rp_full_style['item-margin-top'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][item-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-top']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][item-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-top']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][item-margin-top]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['item-margin-top'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Right', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['item-margin-right'] === 'false' || $rp_full_style['item-margin-right'] === false || $rp_full_style['item-margin-right'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][item-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-right']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][item-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-right']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][item-margin-right]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['item-margin-right'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Bottom', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['item-margin-bottom'] === 'false' || $rp_full_style['item-margin-bottom'] === false || $rp_full_style['item-margin-bottom'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][item-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-bottom']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][item-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-bottom']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][item-margin-bottom]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['item-margin-bottom'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Left', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['item-margin-left'] === 'false' || $rp_full_style['item-margin-left'] === false || $rp_full_style['item-margin-left'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][item-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-left']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][item-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-margin-left']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][item-margin-left]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['item-margin-left'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Item Colors:', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Colors settings control the colors used in the post items displayed in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set the item\'s background color and the color of the item\'s title text.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a color, please click on the "clear" button next to the color selector.', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Background Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_full_style['item-background-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[full][item-background-color]" value="<?php echo esc_attr($rp_full_style['item-background-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[full][item-background-color]" value="0" <?php echo !empty($rp_full_style['item-background-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_full_style['item-background-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Title Text Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_full_style['item-title-text-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[full][item-title-text-color]" value="<?php echo esc_attr($rp_full_style['item-title-text-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[full][item-title-text-color]" value="0" <?php echo !empty($rp_full_style['item-title-text-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_full_style['item-title-text-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label style="visibility:hidden">||</label></li><!-- spacer to keep the styling consistent -->
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Item Font Sizes:', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Font Sizes control the sizes of the title text used in the post items displayed in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a size selection, please click on the "X" button next to the size selector.', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Item Title', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_full_style['item-title-font-size'] === 'false' || $rp_full_style['item-title-font-size'] === false || $rp_full_style['item-title-font-size'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[full][item-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-title-font-size']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[full][item-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_full_style['item-title-font-size']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[full][item-title-font-size]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_full_style['item-title-font-size'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Item Decoration:', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Decoration sets what kind of "bullet" or list-item styling to use for post items when the Related Posts widget layout is set to use columns.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('By default, the Related Post items will use the default list-item styling rules from your site\'s theme.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set it to remove the styling, or use "bullets", "circles", numbers, or Roman Numerals for each list item.', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Item List Style (Column Layout)', 'wpil'); ?>
                                                            <div style="position: relative; top: -10px;">
                                                                <select name="wpil_related_posts_styling[full][item-list-style]" style="margin-top: 6px; min-width: 60px; font-weight: bold; padding: 0 5px; max-width: 110px;">
                                                                    <option value="site-default" <?php selected('site-default', $rp_full_style['item-list-style']); ?>><?php _e('Site Default', 'wpil'); ?></option>
                                                                    <option value="none" <?php selected('none', $rp_full_style['item-list-style']); ?>><?php _e('None', 'wpil'); ?></option>
                                                                    <option value="disc" <?php selected('disc', $rp_full_style['item-list-style']); ?>><?php _e('Disc', 'wpil'); ?></option>
                                                                    <option value="circle" <?php selected('circle', $rp_full_style['item-list-style']); ?>><?php _e('Circle', 'wpil'); ?></option>
                                                                    <option value="decimal" <?php selected('decimal', $rp_full_style['item-list-style']); ?>><?php _e('Decimal', 'wpil'); ?></option>
                                                                    <option value="upper-roman" <?php selected('upper-roman', $rp_full_style['item-list-style']); ?>><?php _e('Roman Numeral', 'wpil'); ?></option>
                                                                </select>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="wpil-related-posts-mobile-styling-container hide-setting">
                                            <div>
                                                <strong><?php _e('Mobile Style Breakpoint (Mobile Only):', 'wpil'); ?></strong>
                                                <div class="wpil_help" style="float:none; display:inline-block;">
                                                    <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                    <div style="width: 350px;">
                                                        <?php
                                                            _e('The Mobile Style Breakpoint controls how small the screen needs to be before Link Whisper will apply the mobile styling settings to the Related Posts widget.', 'wpil');
                                                            echo '<br /><br />';
                                                            _e('Most sites use a breakpoint of 480px, and that is what the Related Posts widget uses by default.', 'wpil');
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <ul>
                                                <li style="width: initial;">
                                                    <label>
                                                        <div style="width: 100%;">
                                                            <input type="range" name="wpil_related_posts_styling[mobile][mobile_breakpoint]" class="wpil-related-post-number-selector" style="width: calc(100% - 80px);" min="0" max="1960" value="<?php echo (!empty($rp_mobile_style['mobile_breakpoint'])) ? intval($rp_mobile_style['mobile_breakpoint']): 480; ?>">
                                                            <input type="number" name="wpil_related_posts_styling[mobile][mobile_breakpoint]" class="wpil-related-post-number-selector" style="width: 60px" min="0" max="1960" value="<?php echo (!empty($rp_mobile_style['mobile_breakpoint'])) ? intval($rp_mobile_style['mobile_breakpoint']): 480; ?>">
                                                        </div>
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                        <br>
                                        <div class="wpil-related-posts-mobile-styling-container hide-setting" style="display:flex;">
                                            <div style="margin-right: 50px;">
                                                <div>
                                                    <strong><?php _e('Widget Section Margins (Mobile Only):', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Widget Section Margins control the size of the margins around the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('These are useful for adjusting the positioning of the Related Post widget on the page.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('For example, if you increase the "Margin Top" number, the widget will move "down" the page and there will be an increased gap between the bottom of the post content and the top of the widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('The increments are measured in pixels, so choosing "100" for a margin is the same as 100px in the stylesheet.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('(This setting only applies to mobile-sized screens. To style the widget on larger screens, please click the "Show Main Styling" toggle)', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Margin Top', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['widget-margin-top'] === 'false' || $rp_mobile_style['widget-margin-top'] === false || $rp_mobile_style['widget-margin-top'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][widget-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-top']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][widget-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-top']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][widget-margin-top]" class="wpil-related-post-number-selector-empty" value="false" <?php echo (!$disabled) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Right', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['widget-margin-right'] === 'false' || $rp_mobile_style['widget-margin-right'] === false || $rp_mobile_style['widget-margin-right'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][widget-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-right']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][widget-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-right']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][widget-margin-right]" class="wpil-related-post-number-selector-empty" value="false" <?php echo (!$disabled) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Bottom', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['widget-margin-bottom'] === 'false' || $rp_mobile_style['widget-margin-bottom'] === false || $rp_mobile_style['widget-margin-bottom'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][widget-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-bottom']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][widget-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-bottom']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][widget-margin-bottom]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['widget-margin-bottom'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Left', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['widget-margin-left'] === 'false' || $rp_mobile_style['widget-margin-left'] === false || $rp_mobile_style['widget-margin-left'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][widget-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-left']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][widget-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-margin-left']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][widget-margin-left]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['widget-margin-left'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Widget Section Colors (Mobile Only):', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Widget Section Colors control the main colors used in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set the widget\'s background color, the color of the widget\'s title text, and the color of the widget\'s description text.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a color, please click on the "clear" button next to the color selector.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('(This setting only applies to mobile-sized screens. To style the widget on larger screens, please click the "Show Main Styling" toggle)', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Background Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_mobile_style['widget-background-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[mobile][widget-background-color]" value="<?php echo esc_attr($rp_mobile_style['widget-background-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[mobile][widget-background-color]" value="0" <?php echo !empty($rp_mobile_style['widget-background-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_mobile_style['widget-background-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Title Text Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_mobile_style['widget-title-text-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[mobile][widget-title-text-color]" value="<?php echo esc_attr($rp_mobile_style['widget-title-text-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[mobile][widget-title-text-color]" value="0" <?php echo !empty($rp_mobile_style['widget-title-text-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_mobile_style['widget-title-text-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Description Text Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_mobile_style['widget-description-text-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[mobile][widget-description-text-color]" value="<?php echo esc_attr($rp_mobile_style['widget-description-text-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[mobile][widget-description-text-color]" value="0" <?php echo !empty($rp_mobile_style['widget-description-text-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_mobile_style['widget-description-text-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Widget Section Font Sizes (Mobile Only):', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Widget Section Font Sizes control the sizes of the header text in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set the widget\'s title text size and the text size of the widget\'s description text.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a size selection, please click on the "X" button next to the size selector.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('(This setting only applies to mobile-sized screens. To style the widget on larger screens, please click the "Show Main Styling" toggle)', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Title', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['widget-title-font-size'] === 'false' || $rp_mobile_style['widget-title-font-size'] === false || $rp_mobile_style['widget-title-font-size'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][widget-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-title-font-size']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][widget-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-title-font-size']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][widget-title-font-size]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['widget-title-font-size'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Description', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['widget-description-font-size'] === 'false' || $rp_mobile_style['widget-description-font-size'] === false || $rp_mobile_style['widget-description-font-size'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][widget-description-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-description-font-size']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][widget-description-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['widget-description-font-size']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][widget-description-font-size]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['widget-description-font-size'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div>
                                                <div>
                                                    <strong><?php _e('Item Margins (Mobile Only):', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Margins control the size of the margins around the post items inside the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('These are useful for adjusting the size and positioning of items inside the Related Post widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('For example, if you increase the "Margin Left" number, all of the posts in the widget will move left and compress as needed. Increasing the "Margin Left" and the "Margin Right" will increase the space between the post items by compressing them.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('The increments are measured in pixels, so choosing "100" for a margin is the same as 100px in the stylesheet.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('(This setting only applies to mobile-sized screens. To style the widget on larger screens, please click the "Show Main Styling" toggle)', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Margin Top', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['item-margin-top'] === 'false' || $rp_mobile_style['item-margin-top'] === false || $rp_mobile_style['item-margin-top'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][item-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-top']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][item-margin-top]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-top']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][item-margin-top]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['item-margin-top'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Right', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['item-margin-right'] === 'false' || $rp_mobile_style['item-margin-right'] === false || $rp_mobile_style['item-margin-right'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][item-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-right']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][item-margin-right]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-right']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][item-margin-right]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['item-margin-right'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Bottom', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['item-margin-bottom'] === 'false' || $rp_mobile_style['item-margin-bottom'] === false || $rp_mobile_style['item-margin-bottom'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][item-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-bottom']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][item-margin-bottom]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-bottom']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][item-margin-bottom]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['item-margin-bottom'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Margin Left', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['item-margin-left'] === 'false' || $rp_mobile_style['item-margin-left'] === false || $rp_mobile_style['item-margin-left'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][item-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-left']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][item-margin-left]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="250" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-margin-left']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][item-margin-left]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['item-margin-left'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Item Colors (Mobile Only):', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Colors settings control the colors used in the post items displayed in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set the item\'s background color and the color of the item\'s title text.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a color, please click on the "clear" button next to the color selector.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('(This setting only applies to mobile-sized screens. To style the widget on larger screens, please click the "Show Main Styling" toggle)', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Background Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_mobile_style['item-background-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[mobile][item-background-color]" value="<?php echo esc_attr($rp_mobile_style['item-background-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[mobile][item-background-color]" value="0" <?php echo !empty($rp_mobile_style['item-background-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_mobile_style['item-background-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label><?php _e('Title Text Color', 'wpil'); ?>
                                                            <div>
                                                                <input type="color" class="wpil-related-posts-colorpicker <?php echo empty($rp_mobile_style['item-title-text-color']) ? 'clear': '';?>" name="wpil_related_posts_styling[mobile][item-title-text-color]" value="<?php echo esc_attr($rp_mobile_style['item-title-text-color']); ?>" />
                                                                <input type="hidden" class="wpil-related-posts-colorpicker-empty" name="wpil_related_posts_styling[mobile][item-title-text-color]" value="0" <?php echo !empty($rp_mobile_style['item-title-text-color']) ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-colorpicker" value="<?php esc_attr_e('clear', 'wpil'); ?>" <?php echo empty($rp_mobile_style['item-title-text-color']) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label style="visibility:hidden">||</label></li><!-- spacer to keep the styling consistent -->
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Item Font Sizes (Mobile Only):', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Font Sizes control the sizes of the title text used in the post items displayed in the Related Posts widget.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('To unset a size selection, please click on the "X" button next to the size selector.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('(This setting only applies to mobile-sized screens. To style the widget on larger screens, please click the "Show Main Styling" toggle)', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Item Title', 'wpil'); ?>
                                                            <div>
                                                                <?php $disabled = ($rp_mobile_style['item-title-font-size'] === 'false' || $rp_mobile_style['item-title-font-size'] === false || $rp_mobile_style['item-title-font-size'] === '')?>
                                                                <input type="range" name="wpil_related_posts_styling[mobile][item-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-title-font-size']): 0; ?>">
                                                                <input type="number" name="wpil_related_posts_styling[mobile][item-title-font-size]" class="wpil-related-post-number-selector <?php echo ($disabled) ? 'clear': ''; ?>" min="0" max="48" value="<?php echo (!$disabled) ? esc_attr($rp_mobile_style['item-title-font-size']): ''; ?>">
                                                                <input type="hidden" name="wpil_related_posts_styling[mobile][item-title-font-size]" class="wpil-related-post-number-selector-empty" value="false" <?php echo ($rp_mobile_style['item-title-font-size'] !== 'false') ? 'disabled': '';?>>
                                                                <input type="button" class="wpil-related-posts-clear-number" value="X" <?php echo ($disabled) ? 'disabled': '';?>>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <br>
                                                <div>
                                                    <strong><?php _e('Item Decoration (Mobile Only):', 'wpil'); ?></strong>
                                                    <div class="wpil_help" style="float:none; display:inline-block;">
                                                        <i class="dashicons dashicons-editor-help" style="margin-top: 6px;"></i>
                                                        <div style="width: 350px;">
                                                            <?php
                                                                _e('The Item Decoration sets what kind of "bullet" or list-item styling to use for post items when the Related Posts widget layout is set to use columns.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('By default, the Related Post items will use the default list-item styling rules from your site\'s theme.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('You can set it to remove the styling, or use "bullets", "circles", numbers, or Roman Numerals for each list item.', 'wpil');
                                                                echo '<br /><br />';
                                                                _e('(This setting only applies to mobile-sized screens. To style the widget on larger screens, please click the "Show Main Styling" toggle)', 'wpil');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul>
                                                    <li>
                                                        <label><?php _e('Item List Style (Column Layout)', 'wpil'); ?>
                                                            <div style="position: relative; top: -10px;">
                                                                <select name="wpil_related_posts_styling[mobile][item-list-style]" style="margin-top: 6px; min-width: 60px; font-weight: bold; padding: 0 5px; max-width: 110px;">
                                                                    <option value="site-default" <?php selected('site-default', $rp_mobile_style['item-list-style']); ?>><?php _e('Site Default', 'wpil'); ?></option>
                                                                    <option value="none" <?php selected('none', $rp_mobile_style['item-list-style']); ?>><?php _e('None', 'wpil'); ?></option>
                                                                    <option value="disc" <?php selected('disc', $rp_mobile_style['item-list-style']); ?>><?php _e('Disc', 'wpil'); ?></option>
                                                                    <option value="circle" <?php selected('circle', $rp_mobile_style['item-list-style']); ?>><?php _e('Circle', 'wpil'); ?></option>
                                                                    <option value="decimal" <?php selected('decimal', $rp_mobile_style['item-list-style']); ?>><?php _e('Decimal', 'wpil'); ?></option>
                                                                    <option value="upper-roman" <?php selected('upper-roman', $rp_mobile_style['item-list-style']); ?>><?php _e('Roman Numeral', 'wpil'); ?></option>
                                                                </select>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('How to Select Related Post Links', 'wpil'); ?></td>
                            <td>
                                <div style="position: relative; max-width: 400px;">
                                    <input type="radio" name="wpil_related_posts_select_method" id="wpil_related_posts_select_method_automatic" value="auto" <?php checked($related_posts_settings['select_method'], 'auto'); ?>>
                                    <label for="wpil_related_posts_select_method_automatic" style="margin: 0 15px 0 0;"><?php _e('Automatically Select Related Posts', 'wpil'); ?></label><br>
                                    <?php /*
                                    <input type="radio" name="wpil_related_posts_select_method" id="wpil_related_posts_select_method_auto-manual" value="auto-manual" <?php checked($related_posts_settings['select_method'], 'auto-manual'); ?>>
                                    <label for="wpil_related_posts_select_method_auto-manual" style="margin: 0 15px 0 0;"><?php _e('Automatically Select with Manual Exceptions', 'wpil'); ?></label><br>
                                    */ ?>
                                    <input type="radio" name="wpil_related_posts_select_method" id="wpil_related_posts_select_method_manual" value="manual" <?php checked($related_posts_settings['select_method'], 'manual'); ?>>
                                    <label for="wpil_related_posts_select_method_manual" style="margin: 0 15px 0 0;"><?php _e('Manually Select Related Posts', 'wpil'); ?></label><br>
                                    <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px; position:absolute;top: 0;right: 0;">
                                        <i class="dashicons dashicons-editor-help"></i>
                                        <div style="width: 400px;">
                                            <?php _e('This setting tells Link Whisper how the Related Post links are going to be selected.', 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e('Setting it to "Automatically Select Related Posts" will tell Link Whisper to do all the looking and automatically select the related posts. You can manually adjust any of the Related Post selections from inside the post editing area.', 'wpil'); ?>
                                            <br />
                                            <br />
                                            <?php _e('Setting it to "Manually Select Related Posts" tells Link Whisper that you will select all the related posts, and that it shouldn\'t automatically select any.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Link Within Page Terms', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <select name="wpil_related_post_term_search" style="float:left;">
                                        <option value="none" <?php selected('none', $related_posts_settings['term_search']) ?>><?php _e('Don\'t Consider Terms When Linking.', 'wpil'); ?></option>
                                        <option value="tags" <?php selected('tags', $related_posts_settings['term_search']) ?>><?php _e('Try to Link Between Posts With Matching "Tags" And Other Non-Hierarchical Terms.', 'wpil'); ?></option>
                                        <option value="cats" <?php selected('cats', $related_posts_settings['term_search']) ?>><?php _e('Try to Link Between Posts With Matching "Categories" And Other Hierarchical Terms.', 'wpil'); ?></option>
                                        <option value="both" <?php selected('both', $related_posts_settings['term_search']) ?>><?php _e('Try to Link Between Posts With Any Matching Terms.', 'wpil'); ?></option>
                                    </select>
                                    <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 400px;">
                                        <?php _e('This option tells Link Whisper if it should consider things like "tags" and "categories" when making related post links.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to try to link based on "tags" will have it search through all posts that are in the same "tag" (or non-hierarchical term) as the current post, and try to link to them.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to try to link based on "categories" will have it search through all posts that are in the same "category" (or hierarchical term) as the current post, and try to link to them.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to try to link based on any matching term will have it search for posts that are in either the same "tag" or the same "category" as the current post, and try to link to them.', 'wpil'); ?>
                                    </div>
                                </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Parent Page Policy', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <select name="wpil_related_post_parent_search" style="float:left;">
                                        <option value="none" <?php selected('none', $related_posts_settings['parent_search']) ?>><?php _e('Link Without Preference to Page Parents and Siblings.', 'wpil'); ?></option>
                                        <option value="prefer-both" <?php selected('prefer-both', $related_posts_settings['parent_search']) ?>><?php _e('Prefer Links Between Page Parents and Siblings.', 'wpil'); ?></option>
                                        <option value="only-both" <?php selected('only-both', $related_posts_settings['parent_search']) ?>><?php _e('Only Link Between Page Parents and Siblings.', 'wpil'); ?></option>
                                    </select>
                                    <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div style="width: 400px;">
                                        <?php _e('This option tells Link Whisper if it should create Related Post links to other posts withg the same "page parent" as it.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to "Prefer Links Between Page Parents and Siblings" will tell Link Whisper to try and find all posts with the same page parent as the current one, and try linking to them. If the post doesn\'t have a page parent, or there aren\'t enough "siblings" to fill up the Related Post Count, Link Whisper will select additional posts to fill out the widget.', 'wpil'); ?>
                                        <br />
                                        <br />
                                        <?php _e('Setting it to "Only Links Between Page Parents and Siblings" will tell Link Whisper to try and find all posts with the same page parent as the current one, and try linking to them. If the post doesn\'t have a page parent, or there aren\'t enough "siblings" to fill up the Related Post Count, Link Whisper will not select any additional posts to fill out the widget.', 'wpil'); ?>
                                    </div>
                                </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Existing Link Policy', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <select name="wpil_related_post_existing_link_handling" style="float:left;">
                                        <option value="none" <?php selected('none', $related_posts_settings['link_handling']) ?>><?php _e('Link Freely To All Posts.', 'wpil'); ?></option>
                                        <option value="no-outbound-internal" <?php selected('no-outbound-internal', $related_posts_settings['link_handling']) ?>><?php _e('Don\'t Link To Posts That The Current Post Already Links To.', 'wpil'); ?></option>
                                        <option value="prefer-outbound-internal" <?php selected('prefer-outbound-internal', $related_posts_settings['link_handling']) ?>><?php _e('Prefer Links To Posts That The Current Post Already Links To.', 'wpil'); ?></option>
                                        <option value="only-outbound-internal" <?php selected('only-outbound-internal', $related_posts_settings['link_handling']) ?>><?php _e('Only Link To Posts That The Current Post Already Links To.', 'wpil'); ?></option>
                                        <option value="no-inbound-internal" <?php selected('no-inbound-internal', $related_posts_settings['link_handling']) ?>><?php _e('Don\'t Link To Posts That Have Links Back To The Current Post.', 'wpil'); ?></option>
                                        <option value="prefer-inbound-internal" <?php selected('no-inbound-internal', $related_posts_settings['link_handling']) ?>><?php _e('Prefer Links To Posts That Have Links Back To The Current Post.', 'wpil'); ?></option>
                                        <option value="only-inbound-internal" <?php selected('only-inbound-internal', $related_posts_settings['link_handling']) ?>><?php _e('Only Link To Posts That Have Links Back To The Current Post.', 'wpil'); ?></option>
                                    </select>
                                    <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                        <i class="dashicons dashicons-editor-help"></i>
                                        <div>
                                            <?php _e('This setting tells Link Whisper how it should handle choosing related posts if they have a linking relationship with the current post.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Orphaned Post Linking Policy', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <select name="wpil_related_posts_orphaned_linking" style="float:left;">
                                        <option value="none" <?php selected('none', $related_posts_settings['orphaned_linking']) ?>><?php _e('Don\'t Prioritize Orphaned Posts.', 'wpil'); ?></option>
                                        <option value="prefer-orphaned" <?php selected('prefer-orphaned', $related_posts_settings['orphaned_linking']) ?>><?php _e('Prefer Links To Orphaned Posts.', 'wpil'); ?></option>
                                        <option value="only-orphaned" <?php selected('only-orphaned', $related_posts_settings['orphaned_linking']) ?>><?php _e('Only Link To Orphaned Posts.', 'wpil'); ?></option>
                                    </select>
                                    <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                        <i class="dashicons dashicons-editor-help"></i>
                                        <div style="width: 400px;">
                                            <?php _e('This setting tells Link Whisper if it should try to make Related Post links to orphaned posts.', 'wpil'); ?>
                                            <br><br>
                                            <?php _e('Setting it to "Prefer Links To Orphaned Posts" will have it try to link to orphaned posts, but if there aren\'t enough available to fill out the Related Posts widget, it will pull in non-orphaned posts to make up the difference.', 'wpil'); ?>
                                            <br><br>
                                            <?php _e('Setting it to "Only Link To Orphaned Posts" will have it only use orphaned posts in the Related Posts widget, and it will not include non-orphaned posts to fill out the widget.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Sort Related Posts', 'wpil'); ?></td>
                            <td>
                                <div style="display: inline-block;">
                                    <select name="wpil_related_posts_sort_order" style="float:left;">
                                        <option value="default" <?php selected('default', $related_posts_settings['sort_order']) ?>><?php _e('Site Default Sorting.', 'wpil'); ?></option>
                                        <option value="rand" <?php selected('rand', $related_posts_settings['sort_order']) ?>><?php _e('Randomly Sort.', 'wpil'); ?></option>
                                        <option value="newest" <?php selected('newest', $related_posts_settings['sort_order']) ?>><?php _e('Sort Newest to Oldest.', 'wpil'); ?></option>
                                        <option value="oldest" <?php selected('oldest', $related_posts_settings['sort_order']) ?>><?php _e('Sort Oldest to Newest.', 'wpil'); ?></option>
                                    </select>
                                    <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                        <i class="dashicons dashicons-editor-help"></i>
                                        <div style="width: 400px;">
                                            <?php _e('This setting tells Link Whisper how it should sort the related posts that it finds for each post.', 'wpil'); ?>
                                            <br><br>
                                            <?php _e('For example, setting it to "Sort Oldest to Newest" will have Link Whisper insert the oldest available post in the widget first, and then move to the next oldest post to insert, and then the next until all the places are filled in the Related Posts widget.', 'wpil'); ?>
                                            <br><br>
                                            <?php _e('Setting it to "Sort Randomly" will have Link Whisper create a list of potential posts to include in the widget, and then randomly select from that list the posts to add to the widget.', 'wpil'); ?>
                                            <br><br>
                                            <?php _e('After changing the sort order and saving the Settings, please refresh the Related Post Links to update the widgets.', 'wpil'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Number of Links to Display', 'wpil'); ?></td>
                            <td>
                                <input type="number" name="wpil_related_post_link_count" step="1" min="0" max="40" value="<?php echo $related_posts_settings['link_count']; ?>">
                                <div class="wpil_help" style="display: inline-block; float: none; margin: 0px 0 0 5px;">
                                    <i class="dashicons dashicons-editor-help"></i>
                                    <div>
                                        <?php _e('This setting controls the number of links that will be shown in the Related Post widget.', 'wpil'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Related Post Layout', 'wpil'); ?></td>
                            <td>
                                <div>
                                    <div>
                                        <input type="radio" name="wpil_related_post_widget_layout" id="wpil_related_posts_column_count_1" value="<?php echo esc_attr(json_encode(array('display' => 'column', 'count' => 1))); ?>" <?php checked(($rp_layout_display === 'column' && $rp_layout_count === 1)); ?>>
                                        <label for="wpil_related_posts_column_count_1" class="wpil-related-post-layout-label"><?php _e('Single Column', 'wpil'); ?></label>
                                        <input type="radio" name="wpil_related_post_widget_layout" id="wpil_related_posts_column_count_2" value="<?php echo esc_attr(json_encode(array('display' => 'column', 'count' => 2))); ?>" <?php checked(($rp_layout_display === 'column' && $rp_layout_count === 2)); ?>>
                                        <label for="wpil_related_posts_column_count_2" class="wpil-related-post-layout-label"><?php _e('Double Column', 'wpil'); ?></label>
                                        <input type="radio" name="wpil_related_post_widget_layout" id="wpil_related_posts_column_count_3" value="<?php echo esc_attr(json_encode(array('display' => 'column', 'count' => 3))); ?>" <?php checked(($rp_layout_display === 'column' && $rp_layout_count === 3)); ?>>
                                        <label for="wpil_related_posts_column_count_3" class="wpil-related-post-layout-label"><?php _e('Triple Column', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float: none; margin: -30px 0 0 5px;">
                                            <i class="dashicons dashicons-editor-help"></i>
                                            <div style="width: 300px; margin-top: -90px;">
                                                <?php _e('This setting controls how the links will be laid out in the Related Posts widget.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('Setting it to display in a "Single Column" will have the widget line up all the links in a single column.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('Setting it to display in a "Double Column" will have the widget list the links in two columns.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('Setting it to display in a "Triple Column" will have the widget list the links in three columns.', 'wpil'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <input type="radio" name="wpil_related_post_widget_layout" id="wpil_related_posts_row_count_1" value="<?php echo esc_attr(json_encode(array('display' => 'row', 'count' => 1))); ?>" <?php checked(($rp_layout_display === 'row' && $rp_layout_count === 1)); ?>>
                                        <label for="wpil_related_posts_row_count_1" class="wpil-related-post-layout-label"><?php _e('Single Row', 'wpil'); ?></label>
                                        <input type="radio" name="wpil_related_post_widget_layout" id="wpil_related_posts_row_count_2" value="<?php echo esc_attr(json_encode(array('display' => 'row', 'count' => 2))); ?>" <?php checked(($rp_layout_display === 'row' && $rp_layout_count === 2)); ?>>
                                        <label for="wpil_related_posts_row_count_2" class="wpil-related-post-layout-label"><?php _e('Double Row', 'wpil'); ?></label>
                                        <input type="radio" name="wpil_related_post_widget_layout" id="wpil_related_posts_row_count_3" value="<?php echo esc_attr(json_encode(array('display' => 'row', 'count' => 3))); ?>" <?php checked(($rp_layout_display === 'row' && $rp_layout_count === 3)); ?>>
                                        <label for="wpil_related_posts_row_count_3" class="wpil-related-post-layout-label"><?php _e('Triple Row', 'wpil'); ?></label>
                                        <div class="wpil_help" style="display: inline-block; float: none; margin: -30px 0 0 5px;">
                                            <i class="dashicons dashicons-editor-help"></i>
                                            <div style="width: 300px; margin-top: -90px;">
                                            <?php _e('This setting controls how the links will be laid out in the Related Posts widget.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('Setting it to display in a "Single Row" will have the widget line up all the links in a single row and will compress them to fit the space.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('Setting it to display in a "Double Row" will have the widget list the links in two rows and will compress them to fit the space.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('Setting it to display in a "Triple Row" will have the widget list the links in three rows and will compress them to fit the space as needed.', 'wpil'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="wpil-related-posts-settings wpil-setting-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Preview Widget', 'wpil'); ?></td>
                            <td>
                                <div>
                                    <div>
                                        <a id="wpil-related-posts-preview-button" class="thickbox open-plugin-details-modal button-primary" href="<?php echo esc_url(get_permalink(Wpil_Widgets::select_newest_related_post())); ?>?page=link_whisper_settings&success&tab=related-posts-settings&plugin=ai-engine&section=changelog&nonce=<?php echo wp_create_nonce('wpil-related-posts-preview-nonce');?>&TB_iframe=true&width=1200&height=800" data-wpil-preview-url="<?php echo esc_url(get_permalink(Wpil_Widgets::select_newest_related_post())); ?>"><?php _e('Preview Related Posts Widget', 'wpil'); ?></a>
                                        <div class="wpil_help" style="display: inline-block; float: none; margin: -30px 0 0 5px;">
                                            <i class="dashicons dashicons-editor-help"></i>
                                            <div style="width: 300px; margin-top: -90px;">
                                                <?php _e('This button allows you to preview any changes that you want to make to the Related Post widget before saving the Settings.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('Just click on the button, and Link Whisper will generate a preview of the Related Posts widget in a popup window.', 'wpil'); ?>
                                                <br />
                                                <br />
                                                <?php _e('When you like how the widget looks in the Preview, please save the Settings to apply the styling to the widget.', 'wpil'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php if($related_posts_settings['select_method'] !== 'manual'){ ?>
                        <tr class="wpil-related-posts-settings wpil-setting-row wpil-generate-auto-related-post-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Refresh Auto-Selected Related Post Links', 'wpil'); ?></td>
                            <td>
                                <a style="margin-top:5px;" class="wpil-generate-related-post-links button-primary" href="#" data-nonce="<?php echo wp_create_nonce(get_current_user_id() . 'wpil_refresh_related_post_nonce'); ?>"><?php _e('Refresh Auto Selected Links', 'wpil'); ?></a>
                                <div class="progress_panel loader related-post-loader" style="display: none; max-width: 400px"><div class="progress_count" style="width:100%; max-width: 400px"><?php _e('Refreshing Related Posts...', 'wpil') ?></div></div>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr class="wpil-related-posts-settings wpil-setting-row wpil-generate-all-related-post-row <?php echo ($related_active) ? '': 'hide-setting';?>">
                            <td scope='row'><?php _e('Refresh All Related Post Links', 'wpil'); ?></td>
                            <td>
                                <a style="margin-top:5px;" class="wpil-generate-all-related-post-links button-primary" href="#" data-nonce="<?php echo wp_create_nonce(get_current_user_id() . 'wpil_refresh_related_post_nonce'); ?>"><?php _e('Refresh All Related Post Links', 'wpil'); ?></a>
                                <div class="progress_panel loader related-post-loader" style="display: none; max-width: 400px"><div class="progress_count" style="width:100%; max-width: 400px"><?php _e('Refreshing Related Posts...', 'wpil') ?></div></div>
                            </td>
                        </tr>
                        <tr class="wpil-licensing-settings wpil-setting-row">
                            <td>
                                <div class="wrap wpil_licensing_wrap postbox">
                                    <div class="wpil_licensing_container">
                                        <div class="wpil_licensing" style="">
                                            <h2 class="wpil_licensing_header hndle ui-sortable-handle">
                                                <span>Link Whisper Licensing</span>
                                            </h2>
                                            <div class="wpil_licensing_content inside">
                                                <?php settings_fields('wpil_license'); ?>
                                                <input type="hidden" id="wpil_license_action_input" name="hidden_action" value="activate_license" disabled="disabled">
                                                <table class="form-table">
                                                    <tbody>
                                                        <tr>
                                                            <td class="wpil_license_table_title"><?php _e('License Key:', 'wpil');?></td>
                                                            <td><input id="wpil_license_key" name="wpil_license_key" type="text" class="regular-text" value="" /></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="wpil_license_table_title"><?php _e('License Status:', 'wpil');?></td>
                                                            <td><span class="wpil_licensing_status_text <?php echo esc_attr($licensing_state); ?>"><?php echo esc_attr($status_titles[$licensing_state]); ?></span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="wpil_license_table_title"><?php _e('License Message:', 'wpil');?></td>
                                                            <td><span class="wpil_licensing_status_text <?php echo esc_attr($licensing_state); ?>"><?php echo esc_attr($status_messages[$licensing_state]); ?></span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="wpil_license_table_title"><?php _e('Installed Version:', 'wpil');?></td>
                                                            <td><span class="wpil_licensing_status_text"><?php echo esc_html(Wpil_License::get_subscription_version_message()); ?></span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <?php wp_nonce_field( 'wpil_activate_license_nonce', 'wpil_activate_license_nonce' ); ?>
                                                <div class="wpil_licensing_version_number"><?php echo Wpil_Base::showVersion(); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <p class='submit wpil-setting-button save-settings'>
                        <input type='submit' name='btnsave' id='btnsave' value='Save Settings' class='button-primary' />
                    </p>
                    <p class='submit wpil-setting-button activate-license' style="display:none">
                        <button type="submit" class="button button-primary wpil_licensing_activation_button"><?php _e('Activate License', 'wpil'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>