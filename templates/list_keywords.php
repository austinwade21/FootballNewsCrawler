<hr class="full-width">
<div class="large-12 column class">
    <h6>Liste des domaines</h6>
</div>
<hr class="full-width">
<table>
    <thead>
    <tr>
        <th><?php echo __('Keyword', 'stats-crawler') ?></th>
        <th><?php echo __('Domain', 'stats-crawler') ?></th>
        <th><?php echo __('Actions', 'stats-crawler'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($keywords as $keyword) : ?>
        <tr>
            <td>
                <?php echo $keyword->keyword ?>
            </td>
            <td>
                <?php echo $keyword->name ?>
            </td>
            <td>
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <input type="hidden" name="action" value="stats_crawler_remove_keyword">
                    <input type="hidden" name="keyword_id" value="<?php echo $keyword->id; ?>">
                    <button><?php echo __('Remove', 'stats-crawler');?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>