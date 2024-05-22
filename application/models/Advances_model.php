<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Advances_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('invoices_model');
    }

    /**
     * Get payment by ID
     * @param  mixed $id payment id
     * @return object
     */
    public function get($id)
    {
        $this->db->select('*,' . db_prefix() . 'invoicepaymentrecords.id as paymentid');
        $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'invoicepaymentrecords.paymentmode', 'left');
        $this->db->order_by(db_prefix() . 'invoicepaymentrecords.id', 'asc');
        $this->db->where(db_prefix() . 'invoicepaymentrecords.id', $id);
        $payment = $this->db->get(db_prefix() . 'invoicepaymentrecords')->row();
        if (!$payment) {
            return false;
        }
        // Since version 1.0.1
        $this->load->model('payment_modes_model');
        $payment_gateways = $this->payment_modes_model->get_payment_gateways(true);
        if (is_null($payment->id)) {
            foreach ($payment_gateways as $gateway) {
                if ($payment->paymentmode == $gateway['id']) {
                    $payment->name = $gateway['name'];
                }
            }
        }

        return $payment;
    }
    
    public function get_staffs(){
        $receipts = $this->db->get(db_prefix() . 'staff')->result_array();
        
        return $receipts;
    }
    
    public function get_receipts(){
        
       
        $this->db->select(db_prefix() . 'receiptrecords.*,' . db_prefix() . 'staff.firstname,' . db_prefix() . 'staff.lastname,' . db_prefix().'payment_modes.name');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'receiptrecords.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'receiptrecords.paymentmode', 'left');
        $receipts = $this->db->get(db_prefix() . 'receiptrecords')->result_array();
        
        return $receipts;
    
    
    }
    
    public function update($data, $id){
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'receiptrecords', $data);

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }
    }
    
    public function get_data($id){
        $this->db->where(db_prefix() . 'receiptrecords.id', $id);
        $payment = $this->db->get(db_prefix() . 'receiptrecords')->row();
        if (!$payment) {
            return false;
        }
		return $payment;
	}
    
    public function process_payment($data)
    {
        // Offline payment mode from the admin side
		$this->db->insert(db_prefix() . 'receiptrecords', $data);
		$insert_id = $this->db->insert_id();
        return $id;
    }

    /**
     * Get all invoice payments
     * @param  mixed $invoiceid invoiceid
     * @return array
     */
    public function get_invoice_payments($invoiceid)
    {
        $this->db->select('*,' . db_prefix() . 'invoicepaymentrecords.id as paymentid');
        $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'invoicepaymentrecords.paymentmode', 'left');
        $this->db->order_by(db_prefix() . 'invoicepaymentrecords.id', 'asc');
        $this->db->where('invoiceid', $invoiceid);
        $payments = $this->db->get(db_prefix() . 'invoicepaymentrecords')->result_array();
        // Since version 1.0.1
        $this->load->model('payment_modes_model');
        $payment_gateways = $this->payment_modes_model->get_payment_gateways(true);
        $i                = 0;
        foreach ($payments as $payment) {
            if (is_null($payment['id'])) {
                foreach ($payment_gateways as $gateway) {
                    if ($payment['paymentmode'] == $gateway['id']) {
                        $payments[$i]['id']   = $gateway['id'];
                        $payments[$i]['name'] = $gateway['name'];
                    }
                }
            }
            $i++;
        }

        return $payments;
    }

   

    /**
     * Check whether payment exist by transaction id for the given invoice
     *
     * @param  int $transactionId
     * @param  int|null $invoiceId
     *
     * @return bool
     */
    public function transaction_exists($transactionId, $invoiceId = null)
    {
        return total_rows('invoicepaymentrecords', array_filter([
            'transactionid' => $transactionId,
            'invoiceid'     => $invoiceId,
        ])) > 0;
    }

    /**
     * Record new payment
     * @param array $data payment data
     * @return boolean
     */
    public function add($data, $subscription = false)
    {
        // Check if field do not redirect to payment processor is set so we can unset from the database
        if (isset($data['do_not_redirect'])) {
            unset($data['do_not_redirect']);
        }

        if ($subscription != false) {
            $after_success = get_option('after_subscription_payment_captured');

            if ($after_success == 'nothing' || $after_success == 'send_invoice') {
                $data['do_not_send_email_template'] = true;
            }
        }

        if (isset($data['do_not_send_email_template'])) {
            unset($data['do_not_send_email_template']);
            $do_not_send_email_template = true;
        } elseif ($this->session->has_userdata('do_not_send_email_template')) {
            $do_not_send_email_template = true;
            $this->session->unset_userdata('do_not_send_email_template');
        }

        if (is_staff_logged_in()) {
            if (isset($data['date'])) {
                $data['date'] = to_sql_date($data['date']);
            } else {
                $data['date'] = date('Y-m-d H:i:s');
            }
            if (isset($data['note'])) {
                $data['note'] = nl2br($data['note']);
            } elseif ($this->session->has_userdata('payment_admin_note')) {
                $data['note'] = nl2br($this->session->userdata('payment_admin_note'));
                $this->session->unset_userdata('payment_admin_note');
            }
        } else {
            $data['date'] = date('Y-m-d H:i:s');
        }

        $data['daterecorded'] = date('Y-m-d H:i:s');
        $data                 = hooks()->apply_filters('before_payment_recorded', $data);

        $this->db->insert(db_prefix() . 'invoicepaymentrecords', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $invoice      = $this->invoices_model->get($data['invoiceid']);
            $force_update = false;

            if (!class_exists('Invoices_model', false)) {
                $this->load->model('invoices_model');
            }

            if ($invoice->status == Invoices_model::STATUS_DRAFT) {
                $force_update = true;
                // update invoice number for invoice with draft - V2.7.2
                $this->invoices_model->change_invoice_number_when_status_draft($invoice->id);
            }

            update_invoice_status($data['invoiceid'], $force_update);

            $activity_lang_key = 'invoice_activity_payment_made_by_staff';
            if (!is_staff_logged_in()) {
                $activity_lang_key = 'invoice_activity_payment_made_by_client';
            }

            $this->invoices_model->log_invoice_activity($data['invoiceid'], $activity_lang_key, !is_staff_logged_in() ? true : false, serialize([
                app_format_money($data['amount'], $invoice->currency_name),
                '<a href="' . admin_url('payments/payment/' . $insert_id) . '" target="_blank">#' . $insert_id . '</a>',
            ]));

            log_activity('Payment Recorded [ID:' . $insert_id . ', Invoice Number: ' . format_invoice_number($invoice->id) . ', Total: ' . app_format_money($data['amount'], $invoice->currency_name) . ']');

            // Send email to the client that the payment is recorded
            $payment               = $this->get($insert_id);
            $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);
            set_mailing_constant();
            $paymentpdf           = payment_pdf($payment);
            $payment_pdf_filename = mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid), 'UTF-8') . '.pdf';
            $attach               = $paymentpdf->Output($payment_pdf_filename, 'S');

            if (!isset($do_not_send_email_template)
                || ($subscription != false && $after_success == 'send_invoice_and_receipt')
                || ($subscription != false && $after_success == 'send_invoice')
            ) {
                $template_name        = 'invoice_payment_recorded_to_customer';
                $pdfInvoiceAttachment = false;
                $attachPaymentReceipt = true;
                $emails_sent          = [];

                $where = ['active' => 1, 'invoice_emails' => 1];

                if ($subscription != false) {
                    $where['is_primary'] = 1;
                    $template_name       = 'subscription_payment_succeeded';

                    if ($after_success == 'send_invoice_and_receipt' || $after_success == 'send_invoice') {
                        $invoice_number = format_invoice_number($payment->invoiceid);
                        set_mailing_constant();
                        $pdfInvoice           = invoice_pdf($payment->invoice_data);
                        $pdfInvoiceAttachment = $pdfInvoice->Output($invoice_number . '.pdf', 'S');

                        if ($after_success == 'send_invoice') {
                            $attachPaymentReceipt = false;
                        }
                    }
                    // Is from settings: Send Payment Receipt
                } else {
                    if (get_option('attach_invoice_to_payment_receipt_email') == 1) {
                        $invoice_number = format_invoice_number($payment->invoiceid);
                        set_mailing_constant();
                        $pdfInvoice           = invoice_pdf($payment->invoice_data);
                        $pdfInvoiceAttachment = $pdfInvoice->Output($invoice_number . '.pdf', 'S');
                    }
                }

                $contacts = $this->clients_model->get_contacts($invoice->clientid, $where);

                foreach ($contacts as $contact) {
                    $template = mail_template(
                        $template_name,
                        $contact,
                        $invoice,
                        $subscription,
                        $payment->paymentid
                    );

                    if ($attachPaymentReceipt) {
                        $template->add_attachment([
                                'attachment' => $attach,
                                'filename'   => $payment_pdf_filename,
                                'type'       => 'application/pdf',
                            ]);
                    }

                    if ($pdfInvoiceAttachment) {
                        $template->add_attachment([
                            'attachment' => $pdfInvoiceAttachment,
                            'filename'   => str_replace('/', '-', $invoice_number) . '.pdf',
                            'type'       => 'application/pdf',
                        ]);
                    }
                    $merge_fields = $template->get_merge_fields();

                    if ($template->send()) {
                        array_push($emails_sent, $contact['email']);
                    }

                    $this->app_sms->trigger(SMS_TRIGGER_PAYMENT_RECORDED, $contact['phonenumber'], $merge_fields);
                }

                if (count($emails_sent) > 0) {
                    $additional_activity_data = serialize([
                       implode(', ', $emails_sent),
                     ]);
                    $activity_lang_key = 'invoice_activity_record_payment_email_to_customer';
                    if ($subscription != false) {
                        $activity_lang_key = 'invoice_activity_subscription_payment_succeeded';
                    }
                    $this->invoices_model->log_invoice_activity($invoice->id, $activity_lang_key, false, $additional_activity_data);
                }
            }

            $this->db->where('staffid', $invoice->addedfrom);
            $this->db->or_where('staffid', $invoice->sale_agent);
            $staff_invoice = $this->db->get(db_prefix() . 'staff')->result_array();

            $notifiedUsers = [];
            foreach ($staff_invoice as $member) {
                if (get_option('notification_when_customer_pay_invoice') == 1) {
                    if (is_staff_logged_in() && $member['staffid'] == get_staff_user_id()) {
                        continue;
                    }
                    // E.q. had permissions create not don't have, so we must re-check this
                    if (user_can_view_invoice($invoice->id, $member['staffid'])) {
                        $notified = add_notification([
                        'fromcompany'     => true,
                        'touserid'        => $member['staffid'],
                        'description'     => 'not_invoice_payment_recorded',
                        'link'            => 'payments/payment/' . $insert_id,
                        'additional_data' => serialize([
                            format_invoice_number($invoice->id),
                        ]),
                    ]);
                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        send_mail_template(
                            'invoice_payment_recorded_to_staff',
                            $member['email'],
                            $member['staffid'],
                            $invoice,
                            $attach,
                            $payment->id
                        );
                    }
                }
            }

            pusher_trigger_notification($notifiedUsers);

            hooks()->do_action('after_payment_added', $insert_id);

            return $insert_id;
        }

        return false;
    }

    /**
     * Update payment
     * @param  array $data payment data
     * @param  mixed $id   paymentid
     * @return boolean
     */
   

    /**
     * Delete payment from database
     * @param  mixed $id paymentid
     * @return boolean
     */
    public function delete($id)
    {
        
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'receiptrecords');
        if ($this->db->affected_rows() > 0) {
            
            return true;
        }

        return false;
    }

    public function add_batch_payment($paymentsData)
    {
        $sendBatchPaymentEmail = true;
        if (isset($paymentsData['do_not_send_invoice_payment_recorded'])) {
            $sendBatchPaymentEmail = false;
        }

        $paymentIds = [];
        foreach ($paymentsData['invoice'] as $data) {
            if (empty($data['invoiceid']) || empty($data['amount']) || empty($data['date']) || empty('paymentmode')) {
                continue;
            }

            $data['date']         = to_sql_date($data['date']);
            $data['daterecorded'] = date('Y-m-d H:i:s');
            $data                 = hooks()->apply_filters('before_payment_recorded', $data);

            $this->db->insert(db_prefix() . 'invoicepaymentrecords', $data);
            $insert_id = $this->db->insert_id();

            if ($insert_id) {
                $paymentIds[] = $insert_id;
                $invoice      = $this->invoices_model->get($data['invoiceid']);
                $force_update = false;

                if (!class_exists('Invoices_model', false)) {
                    $this->load->model('invoices_model');
                }

                if ($invoice->status == Invoices_model::STATUS_DRAFT) {
                    $force_update = true;
                    // update invoice number for invoice with draft - V2.7.2
                    $this->invoices_model->change_invoice_number_when_status_draft($invoice->id);
                }
                update_invoice_status($data['invoiceid'], $force_update);

                $this->invoices_model->log_invoice_activity(
                    $data['invoiceid'],
                    'invoice_activity_payment_made_by_staff',
                    false,
                    serialize([
                        app_format_money($data['amount'], $invoice->currency_name),
                        '<a href="' . admin_url('payments/payment/' . $insert_id) . '" target="_blank">#' . $insert_id . '</a>',
                    ])
                );
                log_activity('Payment Recorded [ID:' . $insert_id . ', Invoice Number: ' . format_invoice_number($invoice->id) . ', Total: ' . app_format_money(
                    $data['amount'],
                    $invoice->currency_name
                ) . ']');
            }
            hooks()->do_action('after_payment_added', $insert_id);
        }

        if (count($paymentIds) > 0 && $sendBatchPaymentEmail) {
            $this->send_batch_payment_notification_to_customers($paymentIds);
        }

        return count($paymentIds);
    }

    private function send_batch_payment_notification_to_customers($paymentIds)
    {
        $paymentData = $this->db
            ->select(db_prefix() . 'invoicepaymentrecords.*,' . db_prefix() . 'invoices.currency,' . db_prefix() . 'invoices.clientId,' . db_prefix() . 'invoices.hash')
            ->join(db_prefix() . 'invoices', 'invoicepaymentrecords.invoiceid=invoices.id')
            ->where_in('invoicepaymentrecords.id', $paymentIds)
            ->get(db_prefix() . 'invoicepaymentrecords')
            ->result();

        // used collection groupBy as a workaround for mysql8.0 only full group mode
        $paymentData = collect($paymentData)->groupBy('clientId');

        foreach ($paymentData as $clientId => $payments) {
            $contacts = $this->get_contacts_for_payment_emails($clientId);
            foreach ($contacts as $contact) {
                if (count($payments) === 1) {
                    $this->send_invoice_payment_recorded($payments[0]->id, $contact);
                } else {
                    $template = mail_template('invoice_batch_payments', $payments, $contact);
                    foreach ($payments as $payment) {
                        $payment               = $this->get($payment->id);
                        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);
                        $template              = $this->_add_payment_mail_attachments_to_template($template, $payment);
                    }
                    $template->send();
                }
            }
        }
    }

    public function send_invoice_payment_recorded($id, $contact)
    {
        if (!class_exists('Invoices_model', false)) {
            $this->load->model('invoices_model');
        }

        // to get structure matching payment_pdf()
        $payment               = $this->get($id);
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);
        $template              = mail_template('invoice_payment_recorded_to_customer', (array) $contact, $payment->invoice_data, false, $id);
        $template              = $this->_add_payment_mail_attachments_to_template($template, $payment);

        return $template->send();
    }

    private function _add_payment_mail_attachments_to_template($template, $payment)
    {
        set_mailing_constant();

        $paymentPDF = payment_pdf($payment);
        $filename   = mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid), 'UTF-8') . '.pdf';
        $attach     = $paymentPDF->Output($filename, 'S');
        $template->add_attachment([
            'attachment' => $attach,
            'filename'   => $filename,
            'type'       => 'application/pdf',
        ]);

        if (get_option('attach_invoice_to_payment_receipt_email') == 1) {
            $invoice_number = format_invoice_number($payment->invoiceid);
            set_mailing_constant();
            $pdfInvoice           = invoice_pdf($payment->invoice_data);
            $pdfInvoiceAttachment = $pdfInvoice->Output($invoice_number . '.pdf', 'S');

            $template->add_attachment([
                'attachment' => $pdfInvoiceAttachment,
                'filename'   => str_replace('/', '-', $invoice_number) . '.pdf',
                'type'       => 'application/pdf',
            ]);
        }

        return $template;
    }

    private function get_contacts_for_payment_emails($client_id)
    {
        if (!class_exists('Clients_model', false)) {
            $this->load->model('clients_model');
        }

        return $this->clients_model->get_contacts($client_id, [
            'active' => 1, 'invoice_emails' => 1,
        ]);
    }
}
