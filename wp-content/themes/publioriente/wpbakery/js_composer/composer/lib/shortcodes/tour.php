<?php
/**
 */
define('SLIDE_TITLE', __("Slide", LANGUAGE_ZONE));

class WPBakeryShortCode_VC_Tour extends WPBakeryShortCode_VC_Tabs {
    protected $predefined_atts = array(
        'tab_id' => SLIDE_TITLE,
        'title' => ''
    );
    protected function getFileName() {
        return 'vc_tabs';
    }
    public function getTabTemplate() {
        return '<div class="wpb_template">'.do_shortcode('[vc_tab title="'.SLIDE_TITLE.'" tab_id=""][/vc_tab]').'</div>';
    }

}