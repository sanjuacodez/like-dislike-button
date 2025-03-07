<?php
class LD_Voting_Service {
    use LD_Security;

    public function process_vote($post_id, $type) {
        $this->validate_vote($post_id, $type);
        $meta_key = $this->get_meta_key($type);
        
        $count = (int) get_post_meta($post_id, $meta_key, true);
        $count++;
        
        update_post_meta($post_id, $meta_key, $count);
        $this->track_vote($post_id);
        
        return $count;
    }

    private function validate_vote($post_id, $type) {
        if (!in_array($type, ['like', 'dislike'])) {
            throw new Exception(__('Invalid vote type', 'like-dislike-button'));
        }
        
        if ($this->has_voted($post_id)) {
            throw new Exception(__('Already voted', 'like-dislike-button'));
        }
    }

    private function get_meta_key($type) {
        return '_ld_' . sanitize_key($type) . '_count';
    }

    private function track_vote($post_id) {
        $votes = isset($_COOKIE['ld_votes']) ? json_decode($_COOKIE['ld_votes'], true) : [];
        $votes[] = $post_id;
        setcookie('ld_votes', json_encode($votes), time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }

    public function has_voted($post_id) {
        $votes = isset($_COOKIE['ld_votes']) ? json_decode($_COOKIE['ld_votes'], true) : [];
        return in_array($post_id, $votes);
    }
}
