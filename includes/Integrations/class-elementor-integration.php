<?php
class LD_Elementor_Integration {
    public function __construct() {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }

    public function register_widgets($widgets_manager) {
        require_once LD_BUTTON_PATH . 'includes/Integrations/class-elementor-widget.php';
        $widgets_manager->register(new LD_Elementor_Widget());
    }
}

class LD_Elementor_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'ld_button';
    }

    public function get_title() {
        return esc_html__('Like/Dislike Button', 'like-dislike-button');
    }

    public function get_icon() {
        return 'eicon-rating';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => esc_html__('Settings', 'like-dislike-button'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('post_id', [
            'label' => esc_html__('Post ID', 'like-dislike-button'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => get_the_ID(),
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $display = new LD_Display_Service();
        echo $display->get_buttons_html($settings['post_id']);
    }
}
