<?php
/**
 * WPBakery Visual Composer Plugin
 *
 * @package VPBakeryVisualComposer
 *
 */

if (!class_exists('WPBakeryVisualComposerSettings')) {
class WPBakeryVisualComposerSettings {

    protected $option_group = 'wpb_js_composer_settings';
    protected $page = "wpb_vc_settings";
    protected static $field_prefix = 'wpb_js_';
    protected static $notification_name= 'wpb_js_notify_user_about_element_class_names';
    protected static $color_settings, $defaults;
    protected $composer;

    public function __construct($composer) {
        $this->composer = WPBakeryVisualComposer::getInstance();
        $this->setTabs();

        $this->tabs = apply_filters('vc_settings_tabs', $this->tabs);
        if(!empty($_COOKIE['wpb_js_composer_settings_active_tab']) && isset($this->tabs[str_replace('#vc-settings-', '', $_COOKIE['wpb_js_composer_settings_active_tab'])])) {
            $this->active_tab = str_replace('#vc-settings-', '', $_COOKIE['wpb_js_composer_settings_active_tab']);
        } else if(!empty($_GET['tab']) && isset($this->tabs[$_GET['tab']])) {
            $this->active_tab = $_GET['tab'];
        } elseif(!$this->showConfigurationTabs()) {
            $this->active_tab = 'updater';
        } else {
            $this->active_tab = 'general';
        }
        self::$color_settings = array(
            array('vc_color' => array('title' => __('Main accent color', LANGUAGE_ZONE))),
            array('vc_color_hover' => array('title' =>__('Hover color', LANGUAGE_ZONE))),
            array('vc_color_call_to_action_bg' => array('title' =>__('Call to action background color', LANGUAGE_ZONE))),
            //array('vc_color_call_to_action_border' => array('title' =>__('Call to action border color', LANGUAGE_ZONE))),
            array('vc_color_google_maps_bg' => array('title' =>__('Google maps background color', LANGUAGE_ZONE))),
            array('vc_color_post_slider_caption_bg' => array('title' =>__('Post slider caption background color', LANGUAGE_ZONE))),
            array('vc_color_progress_bar_bg' => array('title' =>__('Progress bar background color', LANGUAGE_ZONE))),
            array('vc_color_separator_border' => array('title' =>__('Separator border color', LANGUAGE_ZONE))),
            array('vc_color_tab_bg' => array('title' =>__('Tabs navigation background color', LANGUAGE_ZONE))),
            array('vc_color_tab_bg_active' => array('title' =>__('Active tab background color', LANGUAGE_ZONE)))
        );
        self::$defaults = array(
            'vc_color' => '#f7f7f7',
            'vc_color_hover' => '#F0F0F0',
            'margin' => '35px',
            'gutter' => '15',
            'responsive_max' => '768'
        );
        $vc_action = !empty($_POST['vc_action']) ? $_POST['vc_action'] : (!empty($_GET['vc_action']) ? $_GET['vc_action'] : '');
        if($vc_action=='restore_color') {
            $this->restoreColor();
        } elseif($vc_action=='remove_all_css_classes') {
            $this->removeAllCssClasses();
        }
    }
    public function setTabs() {
        $this->tabs = array();

        if($this->showConfigurationTabs()) {
            $this->tabs['general'] = __('General Settings', LANGUAGE_ZONE);
        }

        if($this->composer->isPlugin() && !$this->composer->settingsAsTheme() && $this->showConfigurationTabs()) {
            $this->tabs['color'] = __('Design Options', LANGUAGE_ZONE);
            // $this->tabs['element_css'] = __('Element Class Names', LANGUAGE_ZONE);
            $this->tabs['custom_css'] = __('Custom CSS', LANGUAGE_ZONE);
        }
        if(!$this->composer->isNetworkPlugin() || ($this->composer->isNetworkPlugin() && is_network_admin())) {
            if(!$this->composer->updaterDisabled()) $this->tabs['updater'] = __('Product License', LANGUAGE_ZONE);
        }
        // TODO: may allow to disable automapper
        if(!vc_automapper_is_disabled()) {
            $this->tabs['automapper'] = vc_automapper()->title();
        }
    }
    public function showConfigurationTabs() {
        return !$this->composer->isNetworkPlugin() || !is_network_admin();
    }
    /**
     * Init settings page && menu item
     */
    public function init() {
        if($this->composer->isNetworkPlugin() && is_network_admin()) {
            $page = add_menu_page(__("Visual Composer Settings", LANGUAGE_ZONE),
                __("Visual Composer", LANGUAGE_ZONE),
                'manage_options',
                $this->page,
                array(&$this, 'output'));
        } else {
            $page = add_options_page(__("Visual Composer Settings", LANGUAGE_ZONE),
                __("Visual Composer", LANGUAGE_ZONE),
                'manage_options',
                $this->page,
                array(&$this, 'output'));
        }


        add_action("load-$page", array(&$this, 'admin_load'));
        $this->deactivate = $this->composer->getDeactivationLicense();
        /**
         * General Settings
         */
        $tab_prefix = '_general';
        if( WPBakeryVisualComposer::getInstance()->isPlugin() ) {
            register_setting($this->option_group.$tab_prefix, self::$field_prefix.'content_types', array($this, 'sanitize_post_types_callback'));
        } else {
            register_setting($this->option_group.$tab_prefix, self::$field_prefix.'theme_content_types', array($this, 'sanitize_post_types_callback'));
        }

        register_setting($this->option_group.$tab_prefix, self::$field_prefix.'groups_access_rules', array($this, 'sanitize_group_access_rules_callback'));
        register_setting($this->option_group.$tab_prefix, self::$field_prefix.'not_responsive_css', array($this, 'sanitize_not_responsive_css_callback'));
        add_settings_section($this->option_group.$tab_prefix,
            null,
            array(&$this, 'setting_section_callback_function'),
            $this->page.$tab_prefix);

        if( WPBakeryVisualComposer::getInstance()->isPlugin() ) {
            add_settings_field(self::$field_prefix.'content_types', __("Content types", LANGUAGE_ZONE), array(&$this, 'content_types_field_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix);
        } else {
            add_settings_field(self::$field_prefix.'theme_content_types', __("Content types", LANGUAGE_ZONE), array(&$this, 'theme_content_types_field_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix);
        }
        add_settings_field(self::$field_prefix.'groups_access_rules', __("User groups access rules", LANGUAGE_ZONE), array(&$this, 'groups_access_rules_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix);
        add_settings_field(self::$field_prefix.'not_responsive_css', __("Disable responsive content elements", LANGUAGE_ZONE), array(&$this, 'not_responsive_css_field_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix);
        /**
         * Color Options
         */
        $tab_prefix = '_color';

        register_setting($this->option_group.$tab_prefix, self::$field_prefix.'use_custom', array($this, 'sanitize_use_custom_callback'));
        add_settings_field(self::$field_prefix.'use_custom', __('Use custom design options', LANGUAGE_ZONE), array(&$this, 'use_custom_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix, array('id' => 'use_custom'));
        // add_action('update_option_'.self::$field_prefix.'use_custom', array(&$this, 'buildCustomColorCss'));
        // add_action('add_option_'.self::$field_prefix.'use_custom', array(&$this, 'buildCustomColorCss'));
        foreach(self::$color_settings as $color_set) {
            foreach($color_set as $key => $data) {
                register_setting($this->option_group.$tab_prefix, self::$field_prefix.$key, array($this, 'sanitize_color_callback'));
                add_settings_field(self::$field_prefix.$key, $data['title'], array(&$this, 'color_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix, array('id' => $key));
                // add_action('update_option_'.self::$field_prefix.$key, array(&$this, 'buildCustomColorCss'));
                // add_action('add_option_'.self::$field_prefix.$key, array(&$this, 'buildCustomColorCss'));
            }
        }
        // Margin
        register_setting($this->option_group.$tab_prefix, self::$field_prefix.'margin', array($this, 'sanitize_margin_callback'));
        add_settings_field(self::$field_prefix.'margin', __('Elements bottom margin', LANGUAGE_ZONE), array(&$this, 'margin_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix, array('id' => 'margin'));
        // add_action('update_option_'.self::$field_prefix.'margin', array(&$this, 'buildCustomColorCss'));
        // Gutter
        register_setting($this->option_group.$tab_prefix, self::$field_prefix.'gutter', array($this, 'sanitize_gutter_callback'));
        add_settings_field(self::$field_prefix.'gutter', __('Grid gutter width', LANGUAGE_ZONE), array(&$this, 'gutter_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix, array('id' => 'gutter'));
        /// add_action('update_option_'.self::$field_prefix.'gutter', array(&$this, 'buildCustomColorCss'));
        // Responsive max width
        register_setting($this->option_group.$tab_prefix, self::$field_prefix.'responsive_max', array($this, 'sanitize_responsive_max_callback'));
        add_settings_field(self::$field_prefix.'responsive_max', __('Mobile screen width', LANGUAGE_ZONE), array(&$this, 'responsive_max_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix, array('id' => 'responsive_max'));
        // add_action('update_option_'.self::$field_prefix.'responsive_max', array(&$this, 'buildCustomColorCss'));
        add_settings_section($this->option_group.$tab_prefix,
            null,
            array(&$this, 'setting_section_callback_function'),
            $this->page.$tab_prefix);
        /**
         * Element Class names
         */
        $tab_prefix = '_element_css';

         register_setting($this->option_group.$tab_prefix, self::$field_prefix.'row_css_class', array($this, 'sanitize_row_css_class_callback'));
         register_setting($this->option_group.$tab_prefix, self::$field_prefix.'column_css_classes', array($this, 'sanitize_column_css_classes_callback'));
         add_settings_section($this->option_group.$tab_prefix,
            null, array(&$this, 'setting_section_callback_function'),
            $this->page.$tab_prefix);
        add_settings_field(self::$field_prefix.'row_css_class', "Row CSS class name", array(&$this, 'row_css_class_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix);
        add_settings_field(self::$field_prefix.'column_css_classes', "Columns CSS class names", array(&$this, 'column_css_classes_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix);
         /**
         * Custom CSS
         */
        $tab_prefix = '_custom_css';
        register_setting($this->option_group.$tab_prefix, self::$field_prefix.'custom_css', array($this, 'sanitize_custom_css_callback'));
        // add_action('update_option_'.self::$field_prefix.'custom_css', array(&$this, 'buildCustomCss'));
        add_settings_section($this->option_group.$tab_prefix,
            null,
            array(&$this, 'setting_section_callback_function'),
            $this->page.$tab_prefix);
        add_settings_field(self::$field_prefix.'custom_css', __("Paste your CSS code", LANGUAGE_ZONE), array(&$this, 'custom_css_field_callback'), $this->page.$tab_prefix, $this->option_group.$tab_prefix);
        foreach($this->tabs as $tab => $title) {
            do_action('vc_settings_tab-'.$tab, $this);
        }
        $tab = 'updater';
        $this->addSection($tab, null, array(&$this, 'setting_section_callback_function'));
        $this->addField($tab, __('Envato Username', LANGUAGE_ZONE), 'envato_username', array(&$this, 'sanitize_envato_username'), array(&$this, 'envato_username_callback'));
        $this->addField($tab, __('Secret API Key', LANGUAGE_ZONE), 'envato_api_key', array(&$this, 'sanitize_envato_api_key'), array(&$this, 'envato_api_key_callback'));
        $this->addField($tab, __('Visual Composer License Key', LANGUAGE_ZONE), 'js_composer_purchase_code', array(&$this, 'sanitize_js_composer_purchase_code'), array(&$this, 'js_composer_purchase_code_callback'));

    }
    /**
     * Creates new section.
* @param $tab - tab key name as tab section
* @param $title - Human title
* @param $callback - function to build section header.
     */
    public function addSection($tab, $title = null, $callback = null) {
        add_settings_section($this->option_group.'_'.$tab, $title, ($callback!==null ? $callback : array(&$this, 'setting_section_callback_function')), $this->page.'_'.$tab);
    }
    /**
     * Create field in section.
     * @param $tab
     * @param $title
     * @param $field_name
     * @param $sanitize_callback
     * @param $field_callback
     * @param array $args
     */
    public function addField($tab, $title, $field_name, $sanitize_callback, $field_callback, $args = array()) {
        register_setting($this->option_group.'_'.$tab, self::$field_prefix.$field_name,  $sanitize_callback);
        add_settings_field(self::$field_prefix.$field_name, $title, $field_callback, $this->page.'_'.$tab, $this->option_group.'_'.$tab, $args);
        return $this; // chaining
    }
    public function restoreColor() {
        foreach(self::$color_settings as $color_sett) {
            foreach($color_sett as $key => $value) {
                delete_option(self::$field_prefix.$key);
            }
        }
        delete_option(self::$field_prefix.'margin');
        delete_option(self::$field_prefix.'gutter');
        delete_option(self::$field_prefix.'responsive_max');
        delete_option(self::$field_prefix.'use_custom');
        // $this->buildCustomColorCss();

        wp_redirect(!empty($_POST['_wp_http_referer']) ?  preg_replace('/tab\=/', 'tab_old=',$_POST['_wp_http_referer']).'&tab=color' :  '/options-general.php?page=wpb_vc_settings&tab=color');
    }
    public function removeAllCssClasses() {
        delete_option(self::$field_prefix.'row_css_class');
        delete_option(self::$field_prefix.'column_css_classes');
        wp_redirect(!empty($_POST['_wp_http_referer']) ? preg_replace('/tab\=/', 'tab_old=',$_POST['_wp_http_referer']).'&tab=element_css' :  '/options-general.php?page=wpb_vc_settings&tab=element_css');
    }
    public static function get($option_name) {
       return get_option(self::$field_prefix.$option_name);
    }


    /**
     * Set up the enqueue for the CSS & JavaScript files.
     *
     */

    function admin_load() {
        /*
        get_current_screen()->add_help_tab( array(
            'id'      => 'overview',
            'title'   => __('Overview'),
            'content' =>
            ''
        ) );
        get_current_screen()->set_help_sidebar(
            '<p><strong>' . __( 'For more information:' ) . '</strong></p>'
        );
        */
        wp_register_script('wpb_js_composer_settings', $this->composer->assetURL( 'js/backend/composer-settings-page.js' ), array('jquery', 'wpb_js_composer_js_tools'), WPB_VC_VERSION, true);
        wp_register_script('wpb_js_composer_automapper', $this->composer->assetURL( 'js/backend/composer-automapper.js' ), array('wpb_js_composer_settings', 'backbone', 'shortcode'), WPB_VC_VERSION, true);

        wp_enqueue_style('js_composer_settings');
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script('wpb_js_composer_settings');
        wp_enqueue_script('wpb_js_composer_automapper');
        wp_localize_script( 'wpb_js_composer_automapper', 'i18nLocaleVcAutomapper', array(
            'are_you_sure_delete' => __('Are you sure you want to delete this shortcode?', LANGUAGE_ZONE),
            'are_you_sure_delete_param' => __("Are you sure you want to delete the shortcode's param?", LANGUAGE_ZONE),
            'my_shortcodes_category' => __('My shortcodes', LANGUAGE_ZONE),
            'error_shortcode_name_is_required' => __("Shortcode name is required.", LANGUAGE_ZONE),
            'error_enter_valid_shortcode_tag' => __("Please enter valid shortcode tag.", LANGUAGE_ZONE),
            'error_enter_required_fields' => __("Please enter all required fields for params.", LANGUAGE_ZONE),
            'new_shortcode_mapped' => __('New shortcode mapped from string!', LANGUAGE_ZONE),
            'shortcode_updated' => __('Shortcode updated!', LANGUAGE_ZONE),
            'error_content_param_not_manually' => __('Content param can not be added manually, please use checkbox.', LANGUAGE_ZONE),
            'error_param_already_exists' => __('Param %s already exists. Param names must be unique.', LANGUAGE_ZONE),
            'error_wrong_param_name' => __('Please use only letters, numbers and underscore for param name', LANGUAGE_ZONE),
            'error_enter_valid_shortcode' => __('Please enter valid shortcode to parse!', LANGUAGE_ZONE)
        ));
        setcookie('wpb_js_composer_settings_active_tab');
        $this->locale = array(
            'are_you_sure_reset_css_classes' => __('Are you sure you want to reset to defaults?', LANGUAGE_ZONE),
            'are_you_sure_reset_color' => __('Are you sure you want to reset to defaults?', LANGUAGE_ZONE),
            'vc_updater_error' => __('Something went wrong! Please try again later.', LANGUAGE_ZONE),
            'vc_updater_license_activation_success' => __('License successfully activated. Thank you!', LANGUAGE_ZONE),
            'vc_updater_license_deactivation_success' =>__('Your license key is deactivated.', LANGUAGE_ZONE),
            'vc_updater_empty_data' => __('Envato username and license key are required.', LANGUAGE_ZONE),
            'vc_updater_wrong_license_key' => __('Invalid liense key. Please check information in your envato profile.', LANGUAGE_ZONE),
            'vc_updater_wrong_data' => __('Wrong data. Please check your information or ask support for help.', LANGUAGE_ZONE),
            'vc_updater_already_activated' => __('License successfully activated. Thank you! (401)', LANGUAGE_ZONE),
            'vc_updater_already_activated_another_url' => sprintf(__('Your license key is already activated on another website ({site}), you should deactivate it first or <a href="%s" target="_blank">obtain new license key</a>.', LANGUAGE_ZONE), esc_url( "http://bit.ly/vcomposer" )),
            'vc_updater_activate_license' => __('Activate license', LANGUAGE_ZONE),
            'vc_updater_deactivate_license' => __('Deactivate license', LANGUAGE_ZONE)
        );
        wp_localize_script( 'wpb_js_composer_settings', 'i18nLocaleSettings', $this->locale );
    }

    /**
     * Access groups
     *
     */
    public function groups_access_rules_callback() {
      global $wp_roles;
      $groups = is_object($wp_roles) ? $wp_roles->roles : array(); // get_editable_roles();

        $settings = ( $settings = get_option(self::$field_prefix.'groups_access_rules')) ?  $settings : array();
        $show_types = array(
            'all' => __('Show Visual Composer & default editor', LANGUAGE_ZONE),
            'only' => __('Show only Visual Composer', LANGUAGE_ZONE),
            'no' => __("Don't allow to use Visual Composer", LANGUAGE_ZONE)
        );
        $shortcodes = WPBMap::getShortCodes();
        $size_line = ceil(count(array_keys($shortcodes))/3);
        ?>
        <div class="wpb_settings_accordion" id="wpb_js_settings_access_groups" xmlns="http://www.w3.org/1999/html">
        <?php
        if(is_array($groups)):
            foreach($groups as $key => $params):
                if(isset($params['capabilities']['edit_posts']) && $params['capabilities']['edit_posts']===true):
                $allowed_setting = isset($settings[$key]['show']) ? $settings[$key]['show'] : 'all';
                $shortcode_settings =  isset($settings[$key]['shortcodes']) ? $settings[$key]['shortcodes'] : array();
                ?>
                    <h3 id="wpb-settings-group-<?php echo $key ?>-header">
                        <a href="#wpb-settings-group-<?php echo $key ?>">
                            <?php echo $params['name'] ?>
                        </a>
                    </h3>
                    <div id="wpb-settings-group-<?php echo $key ?>" class="accordion-body">
                        <div class="visibility settings-block">
                            <label for="wpb_composer_access_<?php echo $key ?>"><b><?php _e('Visual Composer access', LANGUAGE_ZONE) ?></b></label>
                            <select id="wpb_composer_access_<?php echo $key ?>" name="<?php echo self::$field_prefix.'groups_access_rules['.$key.'][show]' ?>">
                                <?php foreach($show_types as $i_key => $name): ?>
                                <option value="<?php echo $i_key ?>"<?php echo $allowed_setting==$i_key ? ' selected="true"' : '' ?>><?php echo $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="shortcodes settings-block">
                            <div class="title"><b><?php echo _e('Enabled shortcodes', LANGUAGE_ZONE) ?></b> </div>
                            <?php $z=1;foreach ($shortcodes as $sc_base => $el): ?>
                            <?php if (!isset($el['content_element']) || $el['content_element']==true): ?>
                            <?php if($z==1): ?><div class="pull-left"><?php endif; ?>
                            <label>
                                <input type="checkbox" <?php if(isset($shortcode_settings[$sc_base]) && (int)$shortcode_settings[$sc_base]==1): ?>checked="true" <?php endif; ?>name="<?php echo self::$field_prefix.'groups_access_rules['.$key.'][shortcodes]['.$sc_base.']' ?>" value="1" />
                                <?php _e($el["name"], LANGUAGE_ZONE) ?>
                            </label>
                            <?php if($z==$size_line): ?></div><?php $z=0; endif; $z+=1; ?>
                            <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if($z!=1): ?></div><?php endif; ?>
                            <div class="clearfix"></div>
                            <div class="select-all">
                                <a href="#" class="wpb-settings-select-all-shortcodes"><?php echo __('Select All', LANGUAGE_ZONE) ?></a> | <a href="#" class="wpb-settings-select-none-shortcodes"><?php echo __('Select none', LANGUAGE_ZONE) ?></a>
                            </div>
                        </div>
                    </div>
                <?php
                endif;
            endforeach;
        endif;
        ?>
        </div>
        <?php
    }

    /**
     * Content types checkboxes list callback function
     */
    public function content_types_field_callback() {
        $pt_array = ($pt_array = get_option('wpb_js_content_types')) ? ($pt_array) : WPBakeryVisualComposer::getInstance()->config('default_post_types');
        foreach ($this->getPostTypes() as $pt) {
            if (!in_array($pt, $this->getExcluded())) {
                $checked = (in_array($pt, $pt_array)) ? ' checked="checked"' : '';
                ?>
                <label>
                    <input type="checkbox"<?php echo $checked; ?> value="<?php echo $pt; ?>" id="wpb_js_post_types_<?php echo $pt; ?>" name="<?php echo self::$field_prefix.'content_types' ?>[]">
                    <?php echo $pt; ?>
                </label><br>
                <?php }
        }
        ?>
        <p class="description indicator-hint"><?php _e("Select for which content types Visual Composer should be available during post creation/editing.", LANGUAGE_ZONE); ?></p>
        <?php
    }


    /**
     * Themes Content types checkboxes list callback function
     */
    public function theme_content_types_field_callback() {
        $pt_array = ($pt_array = get_option('wpb_js_theme_content_types')) ? $pt_array : WPBakeryVisualComposer::getInstance()->config('default_post_types');
        foreach ($this->getPostTypes() as $pt) {
            if (!in_array($pt, $this->getExcluded())) {
                $checked = (in_array($pt, $pt_array)) ? ' checked="checked"' : '';
                ?>
            <label>
                <input type="checkbox"<?php echo $checked; ?> value="<?php echo $pt; ?>" id="wpb_js_post_types_<?php echo $pt; ?>" name="<?php echo self::$field_prefix.'theme_content_types' ?>[]">
                <?php echo $pt; ?>
            </label><br>
            <?php }
        }
        ?>
    <p class="description indicator-hint"><?php _e("Select for which content types Visual Composer should be available during post creation/editing.", LANGUAGE_ZONE); ?></p>
    <?php
    }
    public function custom_css_field_callback() {
        $value = ($value = get_option(self::$field_prefix.'custom_css')) ? $value : '';
        echo '<textarea name="'.self::$field_prefix.'custom_css'.'" class="custom_css">'.$value.'</textarea>';
        echo '<p class="description indicator-hint">'.__("If you want to add some custom CSS code to the plugin and don't want to modify any files, then it's a good place to enter your code at this field.", LANGUAGE_ZONE).'</p>'; // TODO: Rewrite it
    }
    /**
     * Not responsive checkbox callback function
     */
    public function not_responsive_css_field_callback() {
        $checked = ($checked = get_option(self::$field_prefix.'not_responsive_css')) ? $checked : false;
        ?>
            <label>
                <input type="checkbox"<?php echo ($checked ? ' checked="checked";' : '') ?> value="1" id="wpb_js_not_responsive_css" name="<?php echo self::$field_prefix.'not_responsive_css' ?>">
                <?php _e('Disable', LANGUAGE_ZONE) ?>
            </label><br/>
    <p class="description indicator-hint"><?php _e('Check this checkbox to prevent content elements from "stacking" one on top other (on small media screens, eg. mobile).', LANGUAGE_ZONE); ?></p>
    <?php
    }
    /**
     * Row css class callback
     */
    public function row_css_class_callback() {
        $value = ($value = get_option(self::$field_prefix.'row_css_class')) ? $value : '';
        echo !empty($value) ? $value : '<i>'.__('Empty value', LANGUAGE_ZONE).'</i>';
        // echo '<input type="text" name="'.self::$field_prefix.'row_css_class'.'" value="'.$value.'">';
        // echo '<p class="description indicator-hint">'.__('To change class name for the row element, enter it here. By default vc_row is used.', LANGUAGE_ZONE).'</p>';

    }

    /**
     * Content types checkboxes list callback function
     */
    public function column_css_classes_callback() {
        $classes = ($classes = get_option(self::$field_prefix.'column_css_classes')) ? $classes : array();
        for($i=1;$i<=12;$i++) {
            if( !empty($classes['span'.$i]) ) {
                $v= $classes['span'.$i];
            } else {
                $v = '<i>'.__('Empty value', LANGUAGE_ZONE).'</i>';
            }
            $id = self::$field_prefix.'column_css_classes_span_'.$i;
            echo '<div class="column_css_class">';
            echo '<label for="'.$id.'">'.sprintf('Span %d:', $i).'</label>';
            // echo '<input type="text" name="'.self::$field_prefix.'column_css_classes'.'[span'.$i.']" id="'.$id.'" value="'.(!empty($classes['span'.$i]) ? $classes['span'.$i]: '').'">';
            echo $v;
            echo '</div>';
        }
        ?>
    <?php // <p class="description indicator-hint"> _e("To change class names for the columns elements, enter them here. By default vc_spanX are used, where X number from 1 to 12.", LANGUAGE_ZONE);</p> ?>
    <?php
    }
    /**
     * Not responsive checkbox callback function
     */
    public function use_custom_callback($args) {
        $checked = ($checked = get_option(self::$field_prefix.$args['id'])) ? $checked : false;
        ?>
    <label>
        <input type="checkbox"<?php echo ($checked ? ' checked="checked";' : '') ?> value="1" id="wpb_js_<?php echo $args['id'] ?>" name="<?php echo self::$field_prefix.$args['id'] ?>">
        <?php _e('Enable', LANGUAGE_ZONE) ?>
    </label><br/>
    <p class="description indicator-hint"><?php _e('Enable the use of custom design options for your site. When checked, custom css file will be used.', LANGUAGE_ZONE); ?></p>
    <?php
    }
    public function color_callback($args) {
        $value = ($value = get_option(self::$field_prefix.$args['id'])) ? $value : $this->getDefault($args['id']);
        echo '<input type="text" name="'.self::$field_prefix.$args['id'].'" value="'.$value.'" class="color-control css-control">';
        //echo '<p class="description indicator-hint">'.__('', LANGUAGE_ZONE).'</p>';
    }

    public function margin_callback($args) {
        $value = ($value = get_option(self::$field_prefix.$args['id'])) ? $value : $this->getDefault($args['id']);
        echo '<input type="text" name="'.self::$field_prefix.$args['id'].'" value="'.$value.'" class="css-control">';
        echo '<p class="description indicator-hint css-control">'.__('To change default vertical spacing between content elements, enter new value here. Example: 20px', LANGUAGE_ZONE).'</p>';
    }
    public function gutter_callback($args) {
        $value = ($value = get_option(self::$field_prefix.$args['id'])) ? $value : $this->getDefault($args['id']);
        echo '<input type="text" name="'.self::$field_prefix.$args['id'].'" value="'.$value.'" class="css-control"> px';
        echo '<p class="description indicator-hint css-control">'.__('To change default horizontal spacing between columns, enter new value in pixels here.', LANGUAGE_ZONE).'</p>';
    }

    public function responsive_max_callback($args) {
        $value = ($value = get_option(self::$field_prefix.$args['id'])) ? $value : $this->getDefault($args['id']);
        echo '<input type="text" name="'.self::$field_prefix.$args['id'].'" value="'.$value.'" class="css-control"> px';
        echo '<p class="description indicator-hint css-control">'.__('By default content elements "stack" one on top other when screen size is smaller then 768px. Here you can change that value if needed.', LANGUAGE_ZONE).'</p>';
    }

    public function envato_username_callback() {
        $field = 'envato_username';
        $value = ($value = get_option(self::$field_prefix.$field)) ? $value : '';
        echo '<input type="text" name="'.self::$field_prefix.$field.'" value="'.$value.'"'.$this->disableIfActivated().'>';
        echo '<p class="description indicator-hint">'.__('Your Envato username.', LANGUAGE_ZONE).'</p>';
    }
    public function js_composer_purchase_code_callback() {
        $field = 'js_composer_purchase_code';
        $value = ($value = get_option(self::$field_prefix.$field)) ? $value : '';
        echo '<input type="text" name="'.self::$field_prefix.$field.'" value="'.$value.'"'.$this->disableIfActivated().'>';
        //echo '<p class="description indicator-hint">'.__('Your Item Purchase Code contained within the License Certificate which is accessible in your Envato account. o view your License Certificate: Login to your Envato account and visit Downloads section, then click "Download" button to reveal "License Certificate" link.', LANGUAGE_ZONE).'</p>';
        echo '<p class="description indicator-hint">'. sprintf( __( 'Please enter your CodeCanyon Visual Composer license key, you can find your key by following the instructions on <a href="%s" target="_blank">this page</a>. License key looks similar to this: bjg759fk-kvta-6584-94h6-75jg8vblatftq.', LANGUAGE_ZONE ), esc_url( "http://kb.wpbakery.com/index.php?title=Item_Verification_Code" ) ) . '</p>';
    }
    public function envato_api_key_callback() {
        $field = 'envato_api_key';
        $value = ($value = get_option(self::$field_prefix.$field)) ? $value : '';
        echo '<input type="password" name="'.self::$field_prefix.$field.'" value="'.$value.'"'.$this->disableIfActivated().'>';
        echo '<p class="description indicator-hint">'. sprintf( __( "You can find API key by visiting your Envato Account page, then clicking the My Settings tab. At the bottom of the page you'll find your account's API key. <a href='%s' target='_blank'>Need help?</a>", LANGUAGE_ZONE ), esc_url( "http://kb.wpbakery.com/index.php?title=Envato_API_key" ) ) . '</p>';
    }
    public function getDefault($key) {
        return !empty(self::$defaults[$key]) ? self::$defaults[$key] : '';
    }
    public function disableIfActivated() {
        if(!isset($this->deactivate_license)) $this->deactivate_license = $this->composer->getDeactivationLicense();
        return empty($this->deactivate_license) ? '' : ' disabled="true" class="vc-updater-passive"';
    }
    /**
     * Callback function for settings section
     *
     *
     */
    public function setting_section_callback_function($tab) {
        if ($tab["id"]=='wpb_js_composer_settings_color'): ?>
        <div class="tab_intro">
          <p class="description">
          <?php _e('Here you can tweak default Visual Composer content elements visual appearance. By default Visual Composer is using neutral light-grey theme. Changing "Main accent color" will affect all content elements if no specific "content block" related color is set.', LANGUAGE_ZONE) ?>
          </p>
        </div>
       <?php elseif($tab["id"]=='wpb_js_composer_settings_updater'): ?>
       <div class="tab_intro">
           <p>
               <?php //_e('Add your Envato credentials, to enable auto updater. With correct login credentials Visual Composer will be updated automatically (same as other plugins do).', LANGUAGE_ZONE) ?>
               <?php echo sprintf(__('A valid license key qualifies you for support and enables automatic updates. <strong>A license key may only be used for one Visual Composer installation on one WordPress site at a time.</strong> If you previosly activated your license key on another site, then you should deactivate it first or <a href="%s" target="_blank">obtain new license key</a>.', LANGUAGE_ZONE), esc_url( "http://bit.ly/vcomposer" )); ?>
           </p>
       </div>
        <?php endif;
    }

    protected function getExcluded() {
        return array('attachment', 'revision', 'nav_menu_item', 'mediapage');
    }

    protected function getPostTypes() {
        return get_post_types(array('public' => true));
    }

    /**
     * Sanitize functions
     *
     */

    // {{

    /**
     * Access rules for user's groups
     *
     * @param $rules - Array of selected rules for each user's group
     */

    public function sanitize_group_access_rules_callback($rules) {
        $sanitize_rules= array();
        $groups = get_editable_roles();
        foreach($groups as $key => $params) {
            if(isset($rules[$key])) $sanitize_rules[$key] = $rules[$key];
        }
        return $sanitize_rules;
    }

    public function sanitize_not_responsive_css_callback($rules) {
        return $rules;
    }

    public function sanitize_row_css_class_callback($value) {
        return $value; // return preg_match('/^[a-z_]\w+$/i', $value) ? $value : '';
    }
    public function sanitize_column_css_classes_callback($classes) {
        $sanitize_rules = array();
        for($i=1; $i<=12; $i++) {
            if(isset($classes['span'.$i])) {
                $sanitize_rules['span'.$i] = $classes['span'.$i];
            }
        }
        return $sanitize_rules;
    }
    /**
     * Post types fields sanitize
     *
     * @param $post_types - Post types array selected by user
     */

    public function sanitize_post_types_callback($post_types) {
        $pt_array = array();
        if(isset($post_types) && is_array($post_types)) {
            foreach ( $post_types as $pt ) {
                if ( !in_array($pt, $this->getExcluded()) && in_array($pt, $this->getPostTypes()) ) {
                    $pt_array[] = $pt;
                }
            }
        }

        return $pt_array;
    }
    public function sanitize_use_custom_callback($rules) {
        return $rules;
    }

    public function sanitize_custom_css_callback($css) {
        return $css;
    }
    public function sanitize_color_callback($color) {
        return $color;
    }
    public function sanitize_margin_callback($margin) {
        $margin = preg_replace('/\s/', '', $margin);
        if(!preg_match('/^\d+(px|%|em|pt){0,1}$/', $margin))
            add_settings_error(self::$field_prefix.'margin', 1, __('Invalid Margin value.', LANGUAGE_ZONE), 'error');
        return $margin;
    }
    public function sanitize_gutter_callback($gutter) {
        $gutter = preg_replace('/[^\d]/', '', $gutter);
        if(!$this->_isGutterValid($gutter))
            add_settings_error(self::$field_prefix.'gutter', 1, __('Invalid Gutter value.', LANGUAGE_ZONE), 'error');
        // $gutter = preg_replace('/[^\d\.]/', '', $gutter);
        return $gutter;
    }
    public function sanitize_responsive_max_callback($responsive_max) {
        if(!$this->_isNumberValid($responsive_max))
            add_settings_error(self::$field_prefix.'responsive_max', 1, __('Invalid "Responsive max" value.', LANGUAGE_ZONE), 'error');
        // $gutter = preg_replace('/[^\d\.]/', '', $gutter);
        return $responsive_max;
    }
    public function sanitize_envato_username($username) {
        return $username;
    }
    public function sanitize_envato_api_key($api_key) {
        return $api_key;
    }
    public function sanitize_js_composer_purchase_code($code) {
        return $code;
    }
    // }}
    public static function _isNumberValid($number) {
        return preg_match('/^[\d]+(\.\d+){0,1}$/', $number);

    }
    public static function _isGutterValid($gutter) {
        return self::_isNumberValid($gutter);
    }
    /**
     * Process options data from form and add to js_composer option parameters
     *
     *
     */
    public function take_action() {
        // if this fails, check_admin_referer() will automatically print a "failed" page and die.
        if ( !empty($_POST) && check_admin_referer('wpb_js_settings_save_action', 'wpb_js_nonce_field') ) {

            if ( isset($_POST['post_types']) && is_array($_POST['post_types']) ) {
                update_option('wpb_js_content_types', $_POST['post_types']);
            } else {
                delete_option('wpb_js_content_types');
            }

            wp_redirect(admin_url('options-general.php?page=wpb_vc_settings')); exit();
        }
    }
    public function showNotification() {
        echo '<div class="error"><p>'.sprintf(__('Visual Composer: Your css class names settings are deprecated. <a href="%s">Click here to resolve</a>.', LANGUAGE_ZONE), menu_page_url($this->page, false).'&tab=element_css').'</p></div>';
    }

    public static function removeNotification() {
        update_option(self::$notification_name, 'false');
    }
    public static function requireNotification() {
        $row_css_class = ($value = get_option(self::$field_prefix.'row_css_class')) ? $value : '';
        $column_css_classes = ($value = get_option(self::$field_prefix.'column_css_classes')) ? $value : '';

        $notification = get_option(self::$notification_name);
        if($notification!=='false' && (!empty($row_css_class) || strlen(implode('', array_values($column_css_classes))) > 0)) {
            update_option(self::$notification_name, 'true');
            return true;
        }
        return false;
    }
    /**
     *  HTML template
     */
    public function output() {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        if(isset($_GET['vc_action']) && $_GET['vc_action']==='upgrade') {
            $this->upgradeFromEnvato();
        }
        if(
            (isset($_GET['build_css']) && ($_GET['build_css'] == '1' || $_GET['build_css'] == 'true'))
            ||
            (isset($_GET['settings-updated']) && ($_GET['settings-updated'] === '1' || $_GET['settings-updated'] === 'true'))
          ) {
            $this->buildCustomColorCss();
            $this->buildCustomCss();
        }
        $use_custom = get_option(self::$field_prefix.'use_custom');
        ?>
<div class="wrap vc-settings" id="wpb-js-composer-settings">
    <h2><?php _e('Visual Composer Settings', LANGUAGE_ZONE); ?></h2>
    <?php
    ?>
    <h2 class="nav-tab-wrapper vc-settings-tabs">
        <?php foreach($this->tabs as $tab => $title): ?>
        <a href="#vc-settings-<?php echo $tab ?>" class="vc-settings-tab-control nav-tab<?php echo ($this->active_tab == $tab ? ' nav-tab-active' : '') ?>"><?php echo $title?></a>
        <?php endforeach; ?>
    </h2>
    <?php foreach($this->tabs as $tab => $title): ?>
    <?php if($tab=='element_css'): ?>
        <form action="options.php" method="post" id="vc-settings-<?php echo $tab ?>" class="vc-settings-tab-content<?php echo ($this->active_tab == $tab ? ' vc-settings-tab-content-active' : '') ?>">
        <?php settings_fields( $this->option_group.'_'.$tab ) ?>
        <div class="deprecated">
            <p>
            <?php _e("<strong>Deprecated:</strong> To override class names that are applied to Visual Composer content elements you should use WordPress add_filter('vc_shortcodes_css_class') function. <a class='vc_show_example'>See Example</a>.", LANGUAGE_ZONE) ?>
            </p>
        </div>
        <div class="vc_helper">
        <?php
        $row_css_class = ($value = get_option(self::$field_prefix.'row_css_class')) ? $value : '';
        $column_css_classes = ($value = get_option(self::$field_prefix.'column_css_classes')) ? (array)$value : array();
        if(!empty($row_css_class) || strlen(implode('', array_values($column_css_classes))) > 0) {
            echo '<p>'.__('You have used element class names settings to replace row and column css classes.').'</p>';
            echo '<p>'.__('Below is code snippet which you should add to your functions.php file in your theme, to replace row and column classes with custom classes saved by you earlier.').'</p>';
            $function = <<<EOF
            <?php
            function custom_css_classes_for_vc_row_and_vc_column(\$class_string, \$tag) {
EOF;
            if(!empty($row_css_class)) {
                $function .= <<<EOF

                if(\$tag=='vc_row' || \$tag=='vc_row_inner') {
                    \$class_string = str_replace('vc_row-fluid', '{$row_css_class}', \$class_string);
                }
EOF;
            }
            $started_column_replace = false;
            for($i=1; $i<=12; $i++) {
                if(!empty($column_css_classes['span'.$i])) {
                    if(!$started_column_replace) {
                        $started_column_replace = true;
                        $function .= <<<EOF

                if(\$tag=='vc_column' || \$tag=='vc_column_inner') {

EOF;
                    }
                    $function .=<<<EOF
                    \$class_string = str_replace('vc_span{$i}', '{$column_css_classes['span'.$i]}', \$class_string);

EOF;
                }
            }
            if($started_column_replace) {
            $function .= <<<EOF
                }
EOF;
            }
            $function .= <<<EOF

                return \$class_string;
            }
            // Filter to Replace default css class for vc_row shortcode and vc_column
            add_filter('vc_shortcodes_css_class', 'custom_css_classes_for_vc_row_and_vc_column', 10, 2);
            ?>
EOF;
            echo '<div class="vc_filter_function"><pre>'.htmlentities2($function).'</pre></div>';
            /*
            $show_notification_button = get_option(self::$notification_name);
            if($show_notification_button === 'true') {
                echo '<button class="button button-primary" id="vc-settings-disable-notification-button">'.__("Don't notify me about this", LANGUAGE_ZONE).'</button>';
            }
            */
        } else {
            $function = <<<EOF
            <?php
            function custom_css_classes_for_vc_row_and_vc_column(\$class_string, \$tag) {
                if(\$tag=='vc_row' || \$tag=='vc_row_inner') {
                    \$class_string = str_replace('vc_row-fluid', 'my_row-fluid', \$class_string);
                }
                if(\$tag=='vc_column' || \$tag=='vc_column_inner') {
                    \$class_string = preg_replace('/vc_span(\d{1,2})/', 'my_span$1', \$class_string);
                }
                return \$class_string;
            }
            // Filter to Replace default css class for vc_row shortcode and vc_column
            add_filter('vc_shortcodes_css_class', 'custom_css_classes_for_vc_row_and_vc_column', 10, 2);
            ?>
EOF;
            echo '<div class="vc_filter_function"><pre>'.htmlentities2($function).'</pre></div>';
        }
            ?>
            </div>
            <?php settings_fields( $this->option_group.'_'.$tab ) ?>
            <?php do_settings_sections($this->page.'_'.$tab) ?>
            <?php wp_nonce_field('wpb_js_settings_save_action', 'wpb_js_nonce_field'); ?>
            <input type="hidden" name="vc_action" value="" id="vc-settings-<?php echo $tab?>-action"/>
            <a href="#" class="button vc-restore-button" id="vc-settings-custom-css-reset-data"><?php _e('Remove all saved', LANGUAGE_ZONE) ?></a>
        </form>
        <?php elseif($tab=='automapper'): ?>
        <form action="options.php" method="post" id="vc-settings-<?php echo $tab ?>" class="vc-settings-tab-content<?php echo ($this->active_tab == $tab ? ' vc-settings-tab-content-active' : '') ?>"<?php echo apply_filters('vc_setting-tab-form-'.$tab, '') ?>>
            <?php vc_automapper()->renderHtml(); ?>
        </form>
        <?php else: ?>
        <?php $css = $tab == 'color' && $use_custom ? ' color_enabled' : ''; ?>
        <form action="options.php" method="post" id="vc-settings-<?php echo $tab ?>" class="vc-settings-tab-content<?php echo ($this->active_tab == $tab ? ' vc-settings-tab-content-active' : '').$css ?>"<?php echo apply_filters('vc_setting-tab-form-'.$tab, '') ?>>
            <?php settings_fields( $this->option_group.'_'.$tab ) ?>
            <?php do_settings_sections($this->page.'_'.$tab) ?>
            <?php wp_nonce_field('wpb_js_settings_save_action', 'wpb_js_nonce_field'); ?>
            <?php
            $submit_button_attributes = array();
            $license_activation_key = $this->composer->getDeactivationLicense();
            if($tab === 'updater' && !empty($license_activation_key)) $submit_button_attributes['disabled'] = 'true'
            ?>
            <?php submit_button( __( 'Save Changes', LANGUAGE_ZONE ), 'primary', 'submit', true, $submit_button_attributes); ?>
            <input type="hidden" name="vc_action" value="" id="vc-settings-<?php echo $tab?>-action"/>
            <?php if($tab=='color'): ?>
            <a href="#" class="button vc-restore-button" id="vc-settings-color-restore-default"><?php _e('Restore to defaults', LANGUAGE_ZONE) ?></a>
            <?php endif; ?>
            <?php if($tab === 'updater'): ?>
            <input type="hidden" id="vc-settings-license-status" name="vc_license_status" value="<?php echo empty($license_activation_key) ? 'not_activated' : 'activated' ?>" />
            <a href="#" class="button vc-activate-license-button" id="vc-settings-activate-license"><?php empty($license_activation_key) ? _e('Activate license', LANGUAGE_ZONE) : _e('Deactivate license', LANGUAGE_ZONE) ?></a> <span class="vc-updater-spinner-wrapper" style="display: none;" id="vc-updater-spinner"><img src="<?php echo get_site_url() ?>/wp-admin/images/wpspin_light.gif"  /></span>
            <?php endif; ?>
        </form>
        <?php endif; ?>

    <?php endforeach; ?>
</div>
<?php
    }
    public static function buildCustomColorCss() {
        /**
         * Filesystem API init.
         * */
        $url = wp_nonce_url('options-general.php?page=wpb_vc_settings&build_css=1','wpb_js_settings_save_action');
        self::getFileSystem($url);
        global $wp_filesystem;
        /**
         *
         * Building css file.
         *
         */
        if(($js_composer_upload_dir = self::checkCreateUploadDir($wp_filesystem, 'use_custom', 'js_composer_front_custom.css')) === false) return;

        $filename = $js_composer_upload_dir.'/js_composer_front_custom.css';
        $use_custom = get_option(self::$field_prefix.'use_custom');
        if(!$use_custom) {
            $wp_filesystem->put_contents( $filename, '', FS_CHMOD_FILE);
            return;
        }
        $css_string = file_get_contents( WPBakeryVisualComposer::getInstance()->assetPath('css/tpl_js_composer_front.css'));
        $pattern  = array();
        $replace = array();
        foreach(array_reverse(self::$color_settings) as $color_set) {
            foreach($color_set as $key => $title) {
                $value = get_option(self::$field_prefix.$key);
                if(!empty($value)) {
                    $pattern[]= '/\"\"\s*.'.$key.'[\w\_]*.\s*\"\"/';
                    $replace[] = $value;
                } elseif(!empty(self::$defaults[$key])) {
                    $pattern[]= '/\"\"\s*.'.$key.'[\w\_]*.\s*\"\"/';
                    $replace[] = self::$defaults[$key];
                }
            }
        }
        $margin = ($margin = get_option(self::$field_prefix.'margin')) ? $margin : self::$defaults['margin'];
        $split_margin = preg_split('/([\d\.]+)/', $margin, 2, PREG_SPLIT_DELIM_CAPTURE);
        $margin = !empty($split_margin[1]) ? $split_margin[1] : 0;
        $units = !empty($split_margin[2]) ? $split_margin[2] : 'px';
        $pattern[] = '/\"\"\s*vc_element_margin_bottom\s*\"\"/';
        $replace[] = $margin.$units;
        $pattern[] = '/\"\"\s*vc_margin_bottom_third\s*\"\"/';
        $replace[] = ((float)$margin/3).$units;
        $pattern[] = '/\"\"\s*vc_margin_bottom_gold\s*\"\"/';
        $replace[] = ((float)$margin/1.61).$units;

        $gutter = ($gutter = get_option(self::$field_prefix.'gutter')) ? $gutter : '';
        if(!self::_isGutterValid($gutter)) $gutter = self::$defaults['gutter'];
        $columns = 12.0;
        $tour_nav_spanX = 4.0;
        $fluidGridGutterWidth = (float)$gutter; // this comes from Design Options tab
        $fluidGridColumnWidth = (100-(($columns-1)*$fluidGridGutterWidth))/$columns;
        $spans_sizes = array();
        for ($span_size=1; $span_size<=12; $span_size++) {
            $w = ($fluidGridColumnWidth * $span_size) + ($fluidGridGutterWidth * ($span_size - 1));
            $pattern[] = '/\"\"\s*vc_span'.$span_size.'\s*\"\"/';
            $replace[] = $w.'px';
            $spans_sizes['vc_span'.$span_size] = $w;
        }
        $pattern[] = '/\"\"\s*vc_margin_left\s*\"\"/';
        $replace[] = $fluidGridGutterWidth.'%';
        // @fluidGridGutterWidth;
        $pattern[] = '/\"\"\s*vc_negative_margin_left\s*\"\"/';
        $replace[] = (-1*$fluidGridGutterWidth).'%';
        // @vc_teaser_grid_w:100% + @fluidGridGutterWidth
        $pattern[] = '/\"\"\s*vc_teaser_grid_w\s*\"\"/';
        $replace[] = (100+$fluidGridGutterWidth).'%';
        // @vc_teaser_grid_span2: 100% / @gridColumns * 2 - @fluidGridGutterWidth - 0.15%
        $pattern[] = '/\"\"\s*vc_teaser_grid_span2\s*\"\"/';
        $replace[] = (100.0 / $columns * 2.0 - $fluidGridGutterWidth - 0.15).'%';
        // @vc_teaser_grid_span3: 100% / @gridColumns * 3 - @fluidGridGutterWidth - 0.08%
        $pattern[] = '/\"\"\s*vc_teaser_grid_span3\s*\"\"/';
        $replace[] = (100.0 / $columns * 3.0 - $fluidGridGutterWidth - 0.08).'%';
        // @vc_teaser_grid_span3: 100% / @gridColumns * 4 - @fluidGridGutterWidth - 0.08%
        $pattern[] = '/\"\"\s*vc_teaser_grid_span4\s*\"\"/';
        $replace[] = (100.0 / $columns * 4.0 - $fluidGridGutterWidth - 0.08).'%';
        // @vc_teaser_grid_span3: 100% / @gridColumns * 6 - @fluidGridGutterWidth - 0.05%
        $pattern[] = '/\"\"\s*vc_teaser_grid_span6\s*\"\"/';
        $replace[] = (100.0 / $columns * 6.0 - $fluidGridGutterWidth - 0.05).'%';
        //  @vc_teaser_grid_span12: 100% - @fluidGridGutterWidth
        $pattern[] = '/\"\"\s*vc_teaser_grid_span12\s*\"\"/';
        $replace[] = (100-$fluidGridGutterWidth).'%';
        // @vc_cta_button_w: 100% - 70% - @fluidGridGutterWidth
        $pattern[] = '/\"\"\s*vc_cta_button_w\s*\"\"/';
        $replace[] = (100.0 - 70.0 -$fluidGridGutterWidth).'%';
        // @tour_nav_width: @vc_span1 * @tour_nav_spanX + @fluidGridGutterWidth * (@tour_nav_spanX - 1)
        $pattern[] = '/\"\"\s*vc_tour_nav_width\s*\"\"/';
        $replace[] = $tour_nav_width = ($spans_sizes['vc_span1'] * $tour_nav_spanX + $fluidGridGutterWidth * ($tour_nav_spanX -1)).'%';
        // @tour_slides_width: 100% - @tour_nav_width
        $pattern[] = '/\"\"\s*vc_tour_slides_width\s*\"\"/';
        $replace[] = (100.0 - $tour_nav_width).'%';

        $responsive_max = ($responsive_max = get_option(self::$field_prefix.'responsive_max')) ? $responsive_max : '';
        if(!self::_isNumberValid($responsive_max)) $responsive_max = self::$defaults['responsive_max'];
        // $pattern[] = '/\"\"\s*vc_responsive_max_w\s*\"\"/';
        // $replace[] = $responsive_max.'px';
        $css_string = preg_replace('/((?<=min\-width\:\s)\d+px(?=\)\s\{\s+\.vc_responsive))/m', $responsive_max.'px', $css_string);

        $main_accent_color = ($main_accent_color = get_option(self::$field_prefix.'vc_color')) ? $main_accent_color : self::$defaults['vc_color'];
        //Call to action border color
        $cta_bg = ($cta_bg = get_option(self::$field_prefix.'vc_color_call_to_action_bg')) ? $cta_bg : $main_accent_color;
        $pattern[] = '/\"\"\s*vc_call_to_action_border\s*\"\"/';
        $replace[] = vc_colorCreator($cta_bg, -5);

        $pattern[] = '/(url\(\.\.\/(?!\.))/';
        $replace[] = 'url('.WPBakeryVisualComposer::getInstance()->assetURL('');
        $css_string = preg_replace($pattern, $replace, $css_string);
        $array_span_css = array();
        $i = 1; while($i<=12) {$array_span_css[] = '.vc_responsive .wpb_row .vc_span'.$i.', .vc_non_responsive .wpb_row .vc_span'.$i++;}
        // @fluidGridGutterWidth;
        $css_string .= ''.implode(','."\n", $array_span_css).', .vc_container {
            padding-left: '.($fluidGridGutterWidth/2).'px;
            padding-right: '.($fluidGridGutterWidth/2).'px;
        }
        .wpb_row {
          margin-left: -'.($fluidGridGutterWidth/2).'px;
          margin-right: -'.($fluidGridGutterWidth/2).'px;
        }';
        // HERE goes the magic
        if ( ! $wp_filesystem->put_contents( $filename, $css_string, FS_CHMOD_FILE) ) {
            if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
                add_settings_error(self::$field_prefix.'main_color', $wp_filesystem->errors->get_error_code(), __('Something went wrong: js_composer_front_custom.css could not be created.', LANGUAGE_ZONE).' '.$wp_filesystem->errors->get_error_message(), 'error');
            elseif ( !$wp_filesystem->connect() )
                add_settings_error(self::$field_prefix.'main_color', $wp_filesystem->errors->get_error_code(), __('js_composer_front_custom.css could not be created. Connection error.', LANGUAGE_ZONE), 'error');
            elseif(!$wp_filesystem->is_writable($filename) )
                add_settings_error(self::$field_prefix.'main_color', $wp_filesystem->errors->get_error_code(), sprintf(__('js_composer_front_custom.css could not be created. Cannot write custom css to "%s".', LANGUAGE_ZONE), $filename), 'error');
            else
                add_settings_error(self::$field_prefix.'main_color', $wp_filesystem->errors->get_error_code(), __('js_composer_front_custom.css could not be created. Problem with access.', LANGUAGE_ZONE), 'error');
            delete_option(self::$field_prefix.'use_custom');
        }
    }
    /**
     * Builds custom css file using css options from vc settings.
     * @return unknown
     */
    public static function buildCustomCss() {
        /**
         * Filesystem API init.
         * */
        $url = wp_nonce_url('options-general.php?page=wpb_vc_settings&build_css=1','wpb_js_settings_save_action');
        self::getFileSystem($url);
        global $wp_filesystem;
        /**
         * Building css file.
         */
        if(( $js_composer_upload_dir = self::checkCreateUploadDir($wp_filesystem, 'custom_css', 'custom.css')) ===false) return;

        $filename = $js_composer_upload_dir.'/custom.css';
        $css_string = '';
        $custom_css_string = get_option(self::$field_prefix.'custom_css');
        if(!empty($custom_css_string)) {
            $assets_url = WPBakeryVisualComposer::getInstance()->assetURL('');
            $css_string .= preg_replace('/(url\(\.\.\/(?!\.))/', 'url('.$assets_url, $custom_css_string);
        }

        if ( ! $wp_filesystem->put_contents( $filename, $css_string, FS_CHMOD_FILE) ) {
            if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
                add_settings_error(self::$field_prefix.'custom_css', $wp_filesystem->errors->get_error_code(), __('Something went wrong: custom.css could not be created.', LANGUAGE_ZONE).$wp_filesystem->errors->get_error_message(), 'error');
            elseif ( !$wp_filesystem->connect() )
                add_settings_error(self::$field_prefix.'custom_css', $wp_filesystem->errors->get_error_code(), __('custom.css could not be created. Connection error.', LANGUAGE_ZONE), 'error');
            elseif(!$wp_filesystem->is_writable($filename) )
                add_settings_error(self::$field_prefix.'custom_css', $wp_filesystem->errors->get_error_code(), __('custom.css could not be created. Cannot write custom css to "'.$filename.'".', LANGUAGE_ZONE), 'error');
            else
                add_settings_error(self::$field_prefix.'custom_css', $wp_filesystem->errors->get_error_code(), __('custom.css could not be created. Problem with access.', LANGUAGE_ZONE), 'error');
        }
    }

    public static function checkCreateUploadDir($wp_filesystem, $option, $filename) {
        $js_composer_upload_dir = self::uploadDir();
        if(!$wp_filesystem->is_dir($js_composer_upload_dir)) {
            if(!$wp_filesystem->mkdir($js_composer_upload_dir, 0777)) {
                add_settings_error(self::$field_prefix.$option, $wp_filesystem->errors->get_error_code(), __(sprintf('%s could not be created. Not available to create js_composer directory in uploads directory ('.$js_composer_upload_dir.').', $filename), LANGUAGE_ZONE), 'error');
                return false;
            }
        }
        return $js_composer_upload_dir;
    }
    public static function uploadDir() {
        $upload_dir = wp_upload_dir();
        global $wp_filesystem;
        return $wp_filesystem->find_folder($upload_dir['basedir']).WPBakeryVisualComposer::uploadDir();
    }
    public static function uploadURL() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'].WPBakeryVisualComposer::uploadDir();
    }
    public static function getFieldPrefix() {
        return self::$field_prefix;
    }
    /**
     * Upgrade plugin from the Envato marketplace.
     */
    public function upgradeFromEnvato() {
        if ( ! current_user_can('update_plugins') )
            wp_die(__('You do not have sufficient permissions to update plugins for this site.'));
        $title = __('Update Visual Composer Plugin', LANGUAGE_ZONE);
        $parent_file = 'options-general.php';
        $submenu_file = 'options-general.php';
        require_once ABSPATH . 'wp-admin/admin-header.php';
        require_once WPBakeryVisualComposer::$config['COMPOSER_LIB'].'wpb_automatic_updater.php';
        $upgrader = new WpbAutomaticUpdater( new Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
        $upgrader->upgradeComposer();
        include ABSPATH . 'wp-admin/admin-footer.php';
        exit();
    }
    protected  static function getFileSystem($url = ''){
        if(empty($url)) $url = wp_nonce_url('options-general.php?page=wpb_vc_settings','wpb_js_settings_save_action');
        if (false === ($creds = request_filesystem_credentials($url, '', false, false, null) ) ) {
            _e('This is required to enable file writing for js_composer', LANGUAGE_ZONE);
            exit(); // stop processing here
        }
        $upload_dir = wp_upload_dir();
        if ( ! WP_Filesystem($creds, $upload_dir['basedir']) ) {
            request_filesystem_credentials($url, '', true, false, null);
            _e('This is required to enable file writing for js_composer', LANGUAGE_ZONE);
            exit();
        }
    }
}
}
?>