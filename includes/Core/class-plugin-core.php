<?php
class Like_Dislike_Core {
    use LD_Security;

    private static $instance;
    private $loader;

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_hooks();
    }

    private function load_dependencies() {
        require_once LD_BUTTON_PATH . 'includes/Entities/class-vote-manager.php';
        require_once LD_BUTTON_PATH . 'includes/Services/class-display-service.php';
        require_once LD_BUTTON_PATH . 'includes/Services/class-voting-service.php';
        require_once LD_BUTTON_PATH . 'includes/Integrations/class-elementor-integration.php';
        require_once LD_BUTTON_PATH . 'includes/Integrations/class-gutenberg-integration.php';
        require_once LD_BUTTON_PATH . 'includes/Admin/class-admin-manager.php';
        
        $this->loader = new Like_Dislike_Loader();
    }

    private function set_locale() {
        load_plugin_textdomain(
            'like-dislike-button',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    private function define_hooks() {
        // Admin hooks
        $admin = new LD_Admin_Manager();
        $this->loader->add_action('admin_menu', $admin, 'create_admin_menu');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        
        // Public hooks
        $public = new LD_Public_Manager();
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_assets');
        $this->loader->add_filter('the_content', $public, 'display_buttons');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_ld_vote', $this, 'handle_vote');
        $this->loader->add_action('wp_ajax_nopriv_ld_vote', $this, 'handle_vote');

        // Integrations
        new LD_Elementor_Integration();
        new LD_Gutenberg_Integration();
    }

    public function handle_vote() {
        try {
            $this->verify_nonce($_POST['nonce']);
            $post_id = $this->sanitize_post_id($_POST['post_id']);
            $vote_type = sanitize_key($_POST['vote_type']);

            $voting = new LD_Voting_Service();
            $new_count = $voting->process_vote($post_id, $vote_type);

            wp_send_json_success([
                'count' => $new_count,
                'message' => __('Vote recorded!', 'like-dislike-button')
            ]);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function run() {
        $this->loader->run();
    }
}
