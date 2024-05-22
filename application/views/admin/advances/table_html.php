<table class="table dt-table table-items-groups" data-order-col="1" data-order-type="asc">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Staff</th>
                                    <th>Receipt Mode</th>
                                    <th>Receipt Date</th>
                                    <th>Transaction ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items_groups as $group) { ?>
                                <tr class="row-has-options" data-group-row-id="<?php echo $group['id']; ?>">
                                    <td data-order="<?php echo $group['amount']; ?>"><?php echo $group['amount']; ?></td>
                                    <td data-order="<?php echo $group['firstname']; ?>">
                                        <span class="group_name_plain_text"><?php echo $group['firstname'].''. $group['lastname']; ?></span>
                                        <div class="group_edit hide">
                                            <div class="input-group">
                                                <input type="text" class="form-control">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary update-item-group"
                                                        type="button"><?php echo _l('submit'); ?></button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row-options">
                                            <?php if (has_permission('items', '', 'edit')) { ?>
                                            <a href="<?php echo admin_url('advances/edit/' . $group['id']); ?>" class="edit-item-group">
                                                <?php echo _l('edit'); ?>
                                            </a>
                                            <?php } ?>
                                            <?php if (has_permission('items', '', 'delete')) { ?>
                                            <a href="<?php echo admin_url('advances/delete/' . $group['id']); ?>"
                                                class="delete-item-group _delete text-danger">
                                                <?php echo _l('delete'); ?>
                                            </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                    
                                    <td data-order="<?php echo $group['name']; ?>"><?php echo $group['name']; ?></td>
                                    <td data-order="<?php echo $group['date']; ?>"><?php echo $group['date']; ?></td>
                                    <td data-order="<?php echo $group['transactionid']; ?>"><?php echo $group['transactionid']; ?></td>
                                    
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>