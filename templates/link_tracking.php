
<hr class="full-width">
<script type='text/javascript'>
    function dayFilterChange(){
        jQuery("#filter_form").submit();
    }
</script>
<div class="large-12 column class">
    <h6 style="float: left;padding-top: 10px;padding-right: 20px;">Link Click Tracking</h6>
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="filter_form" method="post">
        <input type="hidden" name="action" value="add_count_filter_selection">
        <select name="day_id" style="float: left;width: 150px;" onchange="dayFilterChange()" id="day-filter-selection">
            <option value="-2" <?php if($filter_select_index=='-2'){ echo 'selected="true"';} ?>">All</option>
            <option value="1" <?php if($filter_select_index=='1'){ echo 'selected="true"';} ?>">Yesterday</option>
            <option value="0" <?php if($filter_select_index=='0'){ echo 'selected="true"';} ?>">Today</option>
            <option value="7" <?php if($filter_select_index=='7'){ echo 'selected="true"';} ?>">This Week</option>
            <option value="30" <?php if($filter_select_index=='30'){ echo 'selected="true"';} ?>">This Month</option>
        </select>
    </form>
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
<!--            <td>--><?php //echo $domain->name; ?><!--</td>-->
<!--            <td>--><?php //echo $domain->click_count; ?><!--</td>-->

            <td><?php echo $domain['name']; ?></td>
            <td><?php echo $domain['click_count']; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>