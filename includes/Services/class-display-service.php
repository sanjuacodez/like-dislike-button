<?php
class LD_Display_Service {
    private $settings;

    public function __construct() {
        $this->settings = new LD_Settings_Service();
    }

    public function get_buttons_html($post_id) {
        $votes = (new LD_Voting_Service())->get_vote_counts($post_id);
        $position = $this->settings->get_option('button_position', 'after');
        
        return sprintf(
            '<div class="ld-buttons %s">%s%s</div>',
            esc_attr($position),
            $this->generate_button('like', $post_id, $votes['like']),
            $this->generate_button('dislike', $post_id, $votes['dislike'])
        );
    }

    private function generate_button($type, $post_id, $count) {
        return sprintf(
            '<button class="ld-btn ld-%s" data-post="%d" data-type="%s">
                <span class="ld-icon">%s</span>
                <span class="ld-count">%d</span>
            </button>',
            esc_attr($type),
            absint($post_id),
            esc_attr($type),
            $this->get_icon($type),
            absint($count)
        );
    }

    private function get_icon($type) {
        $icon_path = LD_BUTTON_PATH . "assets/icons/{$type}.svg";
        return file_exists($icon_path) ? file_get_contents($icon_path) : '☆';
    }
}
