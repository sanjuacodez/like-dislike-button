<?php
class LD_Gutenberg_Integration {
    public function __construct() {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_assets']);
    }

    public function register_block() {
        register_block_type(LD_BUTTON_PATH . 'assets/block', [
            'render_callback' => [$this, 'render_block'],
            'attributes' => [
                'postId' => ['type' => 'number', 'default' => 0]
            ]
        ]);
    }

    public function render_block($attributes) {
        $post_id = $attributes['postId'] ?: get_the_ID();
        $display = new LD_Display_Service();
        return $display->get_buttons_html($post_id);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'ld-block-editor',
            LD_BUTTON_URL . 'assets/block/block.css',
            [],
            LD_BUTTON_VERSION
        );
    }
}
