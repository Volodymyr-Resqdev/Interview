<?php

/**
     * Send Reminder email
     *
     * @param integer $id
     * @param string $type
     * @return mixed
     * @uses InvoiceController::getRedirectUrl()
     *
     * @since 1.0.1
     *
     */
    public function sendReminderMail($id, $type)
    {

        $user = Auth::user();
        /**
         * @var $invoice Invoice
         */
        $invoice = Invoice::where(['id'=> $id, 'user_id' => $user->id])->first();

        Invoice::where('id', $id)->update(['view_status' => '0']);

        if (!empty($invoice)) {

            $item = InvoiceItem::where('invoice_id', '=', $id)->get();

            $dataToEmail['invoice_data'] = $invoice;
            $dataToEmail['profile_info'] = $user;

            if ($invoice->paid_note == 'paypal') {
                $paypal_conf = \Config::get('paypal');
                $dataToEmail['paypalurl'] = $paypal_conf['pay_pal_config']['paypal_invoice_link'] . $invoice->invoice_url;
            } else {
                $dataToEmail['paypalurl'] = url('') . '/pay/' . $invoice->invoice_url;
            }

            $dataToEmail['invoice_data'] = $invoice->invoice_url;
            $dataToEmail['to']           = $invoice->email;
            $dataToEmail['subject']      = 'Pending Invoice Reminder from ' . $user->company;
            $dataToEmail['body']         = view('emails.invoice', compact('data', 'dataToEmail','invoice_data','item'));
            $dataToEmail['sender']       = env('NO_REPLY_EMAIL', 'noreply@invoyce.me');
            $dataToEmail['cc']           = $invoice->additional_email;

            $data['profile_info']   = $user;
            $data['invoice_data']   = $invoice_data = $invoice;
            $data['to']             = $invoice->email;
            $data['subject']        = $dataToEmail['subject'] = 'Pending Invoice Reminder';
            $data['body']           = View::make('emails.invoice', compact('data', 'dataToEmail','invoice_data','item'))->render();
            $data['sender']         = env('NO_REPLY_EMAIL', 'noreply@invoyce.me');;
            $data['cc']             = $invoice->additional_email;

            _mail($data);

            $dataToEmailSecond['to']        = $user->email;
            $dataToEmailSecond['subject']   = 'Invoice Sent to ' . $data['invoice_data']->company_name;
            $dataToEmailSecond['body']      = view('emails.reminder_user', compact('data'));
            $dataToEmailSecond['sender']    = env('NO_REPLY_EMAIL', 'noreply@invoyce.me');

            _mail($dataToEmailSecond);

            Session::flash('valid', 'block');

            $redirectTo = $this->getRedirectRoute($type);

            return Redirect::to(route($redirectTo));

        } else {

            return Redirect::to(route('login'));

        }

    }
