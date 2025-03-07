<?php
class LD_Public_Manager
{
    public function enqueue_assets()
    {
        wp_enqueue_style(
            'ld-public',
            LD_BUTTON_URL . 'public/css/public.css',
            [],
            LD_BUTTON_VERSION
        );

        wp_enqueue_script(
            'ld-voting',
            LD_BUTTON_URL . 'public/js/voting.js',
            ['jquery'],
            LD_BUTTON_VERSION,
            true
        );
        // For block styles
        wp_enqueue_style(
            'ld-block',
            LD_BUTTON_URL . 'assets/block/block.css',
            [],
            LD_BUTTON_VERSION
        );
        wp_localize_script('ld-voting', 'ldbData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(LD_BUTTON_NONCE_KEY)
        ]);
    }

    public function display_buttons($content)
    {
        if ($this->should_display_buttons()) {
            $display = new LD_Display_Service();
            $buttons = $display->get_buttons_html(get_the_ID());
            return $this->insert_buttons($content, $buttons);
        }
        return $content;
    }

    private function should_display_buttons()
    {
        $settings = get_option('ld_settings');
        return is_singular() &&
            isset($settings['post_types'][get_post_type()]);
    }

    private function insert_buttons($content, $buttons)
    {
        $position = get_option('ld_settings')['button_position'] ?? 'after';
        return ('before' === $position) ? $buttons . $content : $content . $buttons;
    }
}
