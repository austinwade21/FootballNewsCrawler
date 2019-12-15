
<hr class="full-width">
<div class="large-12 column class">
    <h6>Statistiques des liens du domaine</h6>
</div>
<hr class="full-width">
<table>
    <thead>
        <tr>
            <th><?php echo __('Titre de la page (lien vers original)', 'stats-crawler'); ?></th>
            <th><?php echo __('Article AllTrends', 'stats-crawler'); ?></th>
            <th><?php echo __('Partages FB', 'stats-crawler'); ?></th>
            <th><?php echo __('Réactions FB', 'stats-crawler'); ?></th>
            <th><?php echo __('Commentaires FB', 'stats-crawler'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($crawledUrls as $url) : ?>
        <tr>
            <td><a target="_blank" href="<?php echo $url->url; ?>"><?php echo $url->title ? $url->title : $url->url; ?></a></td>
            <td><a target="_blank" href="<?php echo get_edit_post_link($url->associated_post_id); ?>">Article</a> </td>
            <td><?php echo $url->facebook_share_count; ?></td>
            <td><?php echo $url->facebook_reaction_count; ?></td>
            <td><?php echo $url->facebook_comment_count; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>