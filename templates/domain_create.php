<hr class="full-width">
<div class="large-12 column class">
    <h3>Ajouter un domaine</h3>
</div>
<hr class="full-width">
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
    <input type="hidden" name="action" value="stats_crawler_add_domain">
    <div>
        <?php echo __('Nom du domaine', 'stats-crawler'); ?>
        <input type="text" required="required" name="domain_name" id="domain_name" placeholder="Domaine"/>
    </div>

    <div>
        <?php echo __('Url', 'stats-crawler'); ?>
        <input type="text" required="required" name="url_logic" id="url_logic" placeholder="http://www.domaine.com"/>
    </div>

    <div>
        <?php echo __('Categorie', 'stats-crawler'); ?>
        <input type="text" required="required" name="category" id="category" placeholder="Domaine"/>
    </div>
    <div>
        <input type="submit" value="Ajouter le domaine">
    </div>
</form>