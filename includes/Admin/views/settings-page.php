<div class="wrap">
    <h1><?php esc_html_e('Like/Dislike Button Settings', 'like-dislike-button'); ?></h1>
    
    <form method="post" action="options.php">
        <?php 
        settings_fields('ld_settings_group');
        do_settings_sections('ld-settings');
        submit_button();
        ?>
    </form>
</div>
