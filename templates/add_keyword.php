<hr class="full-width">
<div class="large-12 column class">
    <h3>Add Keyword</h3>
</div>
<hr class="full-width">
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
    <input type="hidden" name="action" value="stats_crawler_add_keyword">
    <div>
        <?php echo __('Nom du domaine', 'stats-crawler'); ?>
        <select required="required" name="domain_id" id="domain_name" placeholder="Domaine">
            <?php foreach ($domains as $domain) : ?>
            <option value="<?=$domain->id?>"><?=$domain->name?></option>
            <?php endforeach;?>
        </select>
    </div>

    <div>
        <?php echo __('Keyword', 'stats-crawler'); ?>
        <input type="text" required="required" name="keyword" id="keyword" placeholder="Just one keyword"/>
    </div>

    <div>
        <input type="submit" value="Ajouter le domaine">
    </div>
</form>