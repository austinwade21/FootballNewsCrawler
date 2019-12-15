<?php wp_nonce_field('post-on-social-medias-status-nonce', 'post-on-social-medias-status-nonce'); ?>
<p>
    <label for="post-on-social-medias-status"><?php _e('Status', 'post-on-social-medias'); ?></label>
    <select name="post-on-social-medias-status" id="post-on-social-medias-status">
        <option></option>
        <?php 
            foreach ($status as $status => $name) {
                $active = ($current_status == $status) ? 'selected' : '';
                echo '<option value="' . $status . '" ' . $active . '>' . $name . '</option>';
            }
        ?>
    </select>
</p>