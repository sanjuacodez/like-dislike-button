jQuery(document).ready(function($) {
    $('.ld-buttons').on('click', '.ld-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const postId = $btn.data('post');
        const voteType = $btn.data('type');

        $.ajax({
            url: ldbData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ld_vote',
                post_id: postId,
                vote_type: voteType,
                nonce: ldbData.nonce
            },
            beforeSend: () => {
                $btn.addClass('loading').prop('disabled', true);
            },
            success: (response) => {
                if (response.success) {
                    $btn.find('.ld-count').text(response.data.count);
                } else {
                    alert(response.data);
                }
            },
            complete: () => {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });
});
