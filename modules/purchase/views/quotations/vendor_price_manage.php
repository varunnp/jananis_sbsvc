<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			
			
			
			
			
			<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
            <div class="col-md-12">
              <div class="panel_s mbot10">
               <div class="panel-body _buttons">
                <input type="hidden" name="group_job" value="<?php echo $group; ?>">        
                <?php if(has_permission('purchase_quotations','','create')){ ?>
                 <a href="<?php echo admin_url('purchase/vendor_price'); ?>" class="btn btn-info pull-left new">Create New Vendor Price</a>
               <?php } ?>
               <div class="col-md-3">
                 <select name="pur_request[]" id="pur_request" class="selectpicker pull-right mright10" multiple data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('purchase_request'); ?>">
                   <?php foreach($pur_request as $s) { ?>
                    <option value="<?php echo html_entity_decode($s['id']); ?>" ><?php echo html_entity_decode($s['pur_rq_code'].' - '.$s['pur_rq_name']); ?></option>
                  <?php } ?>
                 </select>
               </div>
                <input type="hidden" name="v_type" value="vendor_price" id="v_type">
               <div class="col-md-3">
                 <select name="vendor[]" id="vendor" class="selectpicker pull-right mright10" multiple data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('vendor'); ?>">
                   <?php foreach($vendors as $s) { ?>
                    <option value="<?php echo html_entity_decode($s['userid']); ?>" ><?php echo html_entity_decode($s['company']); ?></option>
                  <?php } ?>
                 </select>
               </div>
            
              <div class="display-block text-right"> 
                <a href="#" class="btn btn-default btn-with-tooltip toggle-small-view hidden-xs" onclick="toggle_small_estimate_view('.table-pur_estimates','#estimate'); return false;" data-toggle="tooltip" title="<?php echo _l('estimates_toggle_table_tooltip'); ?>"><i class="fa fa-angle-double-left"></i></a>
              </div>
            
            </div>
            </div>
            <div class="row">
              <div class="col-md-12" id="small-table">
                <div class="panel_s">
                  <div class="panel-body">
                    <!-- if estimateid found in url -->
                    <?php echo form_hidden('estimateid',$estimateid); ?>
                     <?php $this->load->view('quotations/table_html'); ?>
                  </div>
                </div>
              </div>
              <div class="col-md-7 small-table-right-col">
                <div id="estimate" class="hide">
                </div>
              </div>
            </div>
            </div>

			
			
			
		</div>
	</div>
</div>

<?php init_tail(); ?>

</body>
</html>
