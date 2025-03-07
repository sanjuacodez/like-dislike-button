<?php
namespace Like_Dislike_Button\Utilities;
trait LD_Security {
    protected function verify_nonce($nonce) {
        if (!wp_verify_nonce($nonce, LD_BUTTON_NONCE_KEY)) {
            throw new Exception(__('Security verification failed', 'like-dislike-button'));
        }
    }

    protected function sanitize_post_id($post_id) {
        $clean_id = absint($post_id);
        if (!get_post($clean_id)) {
            throw new Exception(__('Invalid post ID', 'like-dislike-button'));
        }
        return $clean_id;
    }

    protected function check_permissions() {
        if (!current_user_can('edit_posts')) {
            throw new Exception(__('Permission denied', 'like-dislike-button'));
        }
    }
}
