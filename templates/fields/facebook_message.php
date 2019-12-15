<?php wp_nonce_field('post-on-social-medias-message-nonce', 'post-on-social-medias-message-nonce'); ?>
<p>
    <textarea name="post-on-social-medias-message" id="post-on-social-medias-message" style="width: 100%;min-height: 200px;"><?php echo $current_message; ?></textarea>
</p