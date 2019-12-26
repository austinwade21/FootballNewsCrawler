
<hr class="full-width">
<div class="large-12 column class">
    <h6>Link Click Tracking</h6>
</div>
<hr class="full-width">
<table>
    <thead>
        <tr>
            <th><?php echo __('domain', 'stats-crawler'); ?></th>
            <th><?php echo __('Link Clicked Count', 'stats-crawler'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($link_tracking as $domain) : ?>
        <tr>
            <td><?php echo $domain->name; ?></td>
            <td><?php echo $domain->click_count; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>