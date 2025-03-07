<?php
class LD_Vote_Manager {
    public static function get_votes($post_id) {
        return [
            'like' => get_post_meta($post_id, '_ld_like_count', true) ?: 0,
            'dislike' => get_post_meta($post_id, '_ld_dislike_count', true) ?: 0
        ];
    }

    public static function increment_vote($post_id, $type) {
        $meta_key = "_ld_{$type}_count";
        $count = (int) get_post_meta($post_id, $meta_key, true);
        update_post_meta($post_id, $meta_key, $count + 1);
        return $count + 1;
    }
}
