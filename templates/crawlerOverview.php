<hr class="full-width">
<div class="large-12 column class">
    <h6>Liste des domaines</h6>
</div>
<hr class="full-width">
<table>
    <thead>
    <tr>
        <th><?php echo __('Nom du domaine', 'stats-crawler') ?></th>
        <th><?php echo __('Keywords', 'stats-crawler') ?></th>
        <th><?php echo __('Nombre de liens', 'stats-crawler') ?></th>
        <th><?php echo __('Actions', 'stats-crawler'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($crawledDomains as $crawledDomain) : ?>
        <tr>
            <td>
                <a href="<?php echo admin_url('admin.php?page=stats_crawler_overview&domainId=') . $crawledDomain->id . "&domainLocale=fr";?>"><?php echo $crawledDomain->name; ?></a>

                <?php if ($crawledDomain->obsolete): ?>
                    <span class="badge warning"><?php echo __('Inactif', 'stats-crawler') ?></span>
                <?php else: ?>
                    <span class="badge success"><?php echo __('Actif', 'stats-crawler') ?></span>
                <?php endif; ?>

            </td>
            <td>
                <?php echo $crawledDomain->keywords ?>
            </td>
            <td>
                <?php echo $crawledDomain->link_count ?>
            </td>
            <td>
                <?php if ($crawledDomain->obsolete): ?>
                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="stats_crawler_enable_domain">
                        <input type="hidden" name="domainId" value="<?php echo $crawledDomain->id; ?>">
                        <button><?php echo __('Activer le domaine', 'stats-crawler');?></button>
                    </form>
                <?php else: ?>
                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="stats_crawler_disable_domain">
                        <input type="hidden" name="domainId" value="<?php echo $crawledDomain->id; ?>">
                        <button><?php echo __('Désactiver le domaine', 'stats-crawler');?></button>
                    </form>
                <?php endif; ?>

            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>