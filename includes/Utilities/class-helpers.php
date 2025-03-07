<?php
class LD_Helpers {
    public static function sanitize_settings($input) {
        $clean = [];
        $clean['post_types'] = isset($input['post_types']) ? 
            array_map('sanitize_key', (array)$input['post_types']) : 
            [];
        
        $clean['button_position'] = in_array($input['button_position'], ['before', 'after']) ? 
            $input['button_position'] : 
            'after';
            
        return $clean;
    }

    public static function is_enabled_for_post_type($post_type) {
        $settings = get_option('ld_settings');
        return isset($settings['post_types'][$post_type]);
    }

    public static function asset_url($path) {
        return LD_BUTTON_URL . ltrim($path, '/');
    }
}
