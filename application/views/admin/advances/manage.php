<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="_buttons tw-mb-3 sm:tw-mb-5">
            <div class="row">
                <div class="col-md-4">
                    <a href="<?php echo admin_url('advances/receipt'); ?>" class="btn btn-primary pull-left new"><i class="fa-regular fa-plus tw-mr-1"></i>Create New Staff Advance </a>
                </div>
            </div>
        </div>
        
        <div class="panel_s">
            <div class="panel-body">
                <div class="panel-table-full">
                    <?php $this->load->view('admin/advances/table_html'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    initDataTable('.table-payments', admin_url + 'payments/table', undefined, undefined, 'undefined',
        <?php echo hooks()->apply_filters('payments_table_default_order', json_encode([0, 'desc'])); ?>);
});
</script>
</body>

</html>