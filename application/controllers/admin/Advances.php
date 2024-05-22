<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Invoices_model $invoices_model
 * @property Payments_model $payments_model
 */
class Advances extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('advances_model');
    }

    
    /* In case if user go only on /payments */
    public function index()
    {
        $this->list_payments();
    }

    public function list_payments()
    {
        if (!has_permission('advances', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('advances');
        }
       
        $data['items_groups'] = $this->advances_model->get_receipts();

        $data['title'] = 'advances';
        $this->load->view('admin/advances/manage', $data);
    }

    public function table($clientid = '')
    {
        if (!has_permission('advances', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            ajax_access_denied();
        }

        $this->app->get_table_data('advances', [
            'clientid' => $clientid,
        ]);
    }
    
    
     /* Delete payment */
    public function delete($id)
    {
        if (!has_permission('advances', '', 'delete')) {
            access_denied('Delete Advance');
        }
        if (!$id) {
            redirect(admin_url('advances'));
        }
        $response = $this->advances_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', 'Advance'));
        } else {
            set_alert('warning', _l('problem_deleting', 'Advance'));
        }
        redirect(admin_url('advances'));
    }
    

    /* Update payment data */
    public function receipt($id = '')
    {
       
        $data['title'] = 'Advance Receipt';
        $this->load->model('payment_modes_model');
        $data['staffs'] = $this->advances_model->get_staffs();
        $data['payment_modes'] = $this->payment_modes_model->get();
        $this->load->view('admin/advances/receipt_template', $data);
    }
    
    public function edit($id = ''){
		$get_data = $this->advances_model->get_data($id);
		$data['advance'] = $get_data;
		$this->load->model('payment_modes_model');
	    $data['staffs'] = $this->advances_model->get_staffs();
        $data['payment_modes'] = $this->payment_modes_model->get();
        $this->load->view('admin/advances/edit_template', $data);
	}
	
	public function update($id=''){
	    $success = $this->advances_model->update($this->input->post(), $id);
        if ($success == true) {
            set_alert('success', _l('updated_successfully', _l('client')));
        }
        redirect(admin_url('advances'));
	}
    
    
    public function record_payment()
    {
        
        
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['date'] = date("Y-m-d", strtotime($data['date']));
            $id = $this->advances_model->process_payment($data);
            if ($id) {
                set_alert('success', 'Advance Amount Created');
                redirect(admin_url('advances'));
            } else {
                set_alert('danger', 'Advance Amount Failed');
            }
            redirect(admin_url('advances'));
        }
    }

    /**
     * Generate payment pdf
     * @since  Version 1.0.1
     * @param  mixed $id Payment id
     */
    public function pdf($id)
    {
        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('View Payment');
        }

        $payment = $this->advances_model->get($id);

        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && !user_can_view_invoice($payment->invoiceid)) {
            access_denied('View Payment');
        }

        $this->load->model('invoices_model');
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);

        try {
            $paymentpdf = payment_pdf($payment);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $paymentpdf->Output(mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid)) . '.pdf', $type);
    }

    /**
     * Send payment manually to customer contacts
     * @since  2.3.2
     * @param  mixed $id payment id
     * @return mixed
     */
    public function send_to_email($id)
    {
        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('Send Payment');
        }

        $payment = $this->advances_model->get($id);

        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && !user_can_view_invoice($payment->invoiceid)) {
            access_denied('Send Payment');
        }

        $this->load->model('invoices_model');
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);
        set_mailing_constant();

        $paymentpdf = payment_pdf($payment);
        $filename   = mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid), 'UTF-8') . '.pdf';

        $attach = $paymentpdf->Output($filename, 'S');

        $sent    = false;
        $sent_to = $this->input->post('sent_to');

        if (is_array($sent_to) && count($sent_to) > 0) {
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {
                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template('invoice_payment_recorded_to_customer', (array) $contact, $payment->invoice_data, false, $payment->paymentid);

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

                    if ($template->send()) {
                        $sent = true;
                    }
                }
            }
        }

        // In case client use another language
        load_admin_language();
        set_alert($sent ? 'success' : 'danger', _l($sent ? 'payment_sent_successfully' : 'payment_sent_failed'));

        redirect(admin_url('payments/payment/' . $id));
    }

   
}
