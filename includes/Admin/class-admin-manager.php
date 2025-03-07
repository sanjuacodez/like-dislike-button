<?php
class LD_Admin_Manager {
    use LD_Security;

    public function create_admin_menu() {
        add_options_page(
            __('Like/Dislike Settings', 'like-dislike-button'),
            __('Like/Dislike', 'like-dislike-button'),
            'manage_options',
            'ld-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('ld_settings_group', 'ld_settings');

        add_settings_section(
            'ld_general_section',
            __('General Settings', 'like-dislike-button'),
            null,
            'ld-settings'
        );

        $this->add_setting_field('post_types', __('Enable Post Types', 'like-dislike-button'), 'post_types_callback');
        $this->add_setting_field('button_position', __('Button Position', 'like-dislike-button'), 'position_callback');
        $this->add_setting_field('icon_style', __('Icon Style', 'like-dislike-button'), 'icon_style_callback');
    }

    private function add_setting_field($id, $title, $callback) {
        add_settings_field(
            "ld_{$id}",
            $title,
            [$this, $callback],
            'ld-settings',
            'ld_general_section'
        );
    }

    public function post_types_callback() {
        $settings = get_option('ld_settings');
        $post_types = get_post_types(['public' => true], 'objects');
        
        foreach ($post_types as $type) {
            $checked = isset($settings['post_types'][$type->name]) ? 'checked' : '';
            printf(
                '<label><input type="checkbox" name="ld_settings[post_types][%s]" %s> %s</label><br>',
                esc_attr($type->name),
                $checked,
                esc_html($type->label)
            );
        }
    }

    public function render_settings_page() {
        require_once LD_BUTTON_PATH . 'includes/Admin/views/settings-page.php';
    }
}
