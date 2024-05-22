<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php echo form_open(admin_url('advances/update/'.$advance->id), ['id' => 'record_receipt']); ?>
<div class="col-md-12 no-padding animated fadeIn">
    <div class="panel_s">
        <div class="panel-body">
            <h4 class="tw-my-0 tw-font-semibold">
                Staff Advance
            </h4>
            <hr class="hr-panel-separator" />
            <div class="row">
                <div class="col-md-6">
                    <div class="f_client_id">
                        <div class="form-group select-placeholder">
                            <label for="clientid" class="control-label">Staff</label>
                            <select id="clientid" name="clientid"  data-width="100%" data-live-search="true" class="selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                
                                <?php foreach ($staffs as $staff) { ?>
                                    <option value="<?php echo $staff['staffid']; ?>" <?php if($advance->clientid == $staff['staffid']){ echo 'selected';} ?>><?php echo $staff['firstname'] . ' '.$staff['lastname']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="over_time_status" class="control-label">Over Time Status</label>
                        <select class="selectpicker" name="over_time_status" data-width="100%"
                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <option value=""></option>
                            <option value="yes" <?php if($advance->over_time_status == 'yes'){ echo 'selected'; }?> >Yes</option>
                            <option value="no" <?php if($advance->over_time_status == 'no'){ echo 'selected';} ?> >No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="salary_status" class="control-label">Salary Status</label>
                        <select class="selectpicker" name="salary_status" data-width="100%"
                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <option value=""></option>
                            <option value="yes" <?php if($advance->salary_status == 'yes'){ echo 'selected';} ?> >Yes</option>
                            <option value="no" <?php if($advance->salary_status == 'no'){ echo 'selected'; }?> >No</option>
                        </select>
                    </div>
                    <?php
                    $amount       = 0;
                    $totalAllowed = 0;
                    echo render_input('beni_name', 'Benificiary Name', $advance->beni_name, 'text');
                     ?>
                    
                    
                    
                    
                    <div class="form-group">
                        <label for="paymentmode" class="control-label"><?php echo _l('payment_mode'); ?></label>
                        <select class="selectpicker" name="paymentmode" data-width="100%"
                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <option value=""></option>
                            <?php foreach ($payment_modes as $mode) { ?>
                            <option value="<?php echo $mode['id']; ?>" <?php if($advance->paymentmode == $mode['id']){ echo 'selected';} ?>><?php echo $mode['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <?php echo render_input('transactionid', 'payment_transaction_id', $advance->transactionid, 'text');  ?>
                    
                    
                </div>
                
                <div class="col-md-6">
                    <?php 
                    echo render_input('amount', 'Advance Amount', $advance->amount, 'number');
                    echo render_date_input('date', 'Advance Date', $advance->date);
                    ?>
                    <div class="form-gruoup">
                        <label for="note" class="control-label">Advance Purpose</label>
                        <textarea name="note" class="form-control" rows="8"
                            placeholder="<?php echo _l('invoice_record_payment_note_placeholder'); ?>"
                            id="note"><?php echo $advance->note; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="#" class="btn btn-danger"
                onclick="init_invoice(); return false;"><?php echo _l('cancel'); ?></a>
            <button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>"
                data-form="#record_payment_form" class="btn btn-success"><?php echo _l('submit'); ?></button>
        </div>
    </div>
</div>
<?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    init_selectpicker();
    init_datepicker();
    appValidateForm($('#record_receipt'), {
        amount: 'required',
        date: 'required',
        paymentmode: 'required'
    });
    var $sMode = $('select[name="paymentmode"]');
    var total_available_payment_modes = $sMode.find('option').length - 1;
    if (total_available_payment_modes == 1) {
        $sMode.selectpicker('val', $sMode.find('option').eq(1).attr('value'));
        $sMode.trigger('change');
    }
});
</script>
</body>

</html>