<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';

class Plugin2checkoutCallback extends PluginCallback
{
    var $response = array();

    function processCallback()
    {
        if (isset($_REQUEST['ce_invoice_num'])) {
            $this->response = $_REQUEST;
        } else {
            // ignore blank browser requests
            return;
        }

        //Get vars from post/get
        $lInvoiceID = $this->response['ce_invoice_num'];
        $lInvoicePaid = $this->response['x_2checked'];
        $lSignUp = $this->response['signup'];
        $lErrorCode = "";
        $lPricePaid = $this->response['x_amount'];
        $lOrderID = $this->response['order_number'];

        //Create plug in class to interact with CE
        $cPlugin = new Plugin($lInvoiceID, $_GET['plugin'], $this->user);
        $cPlugin->m_TransactionID = $lOrderID;
        $cPlugin->setAmount($lPricePaid);
        $cPlugin->setAction('charge');

        //Just in case require to notify the admins
        $notifyAdmin = false;

        //Determine if payment was made & make sure that it was not made in demo mode
        if (($lInvoicePaid == 'Y' || $lInvoicePaid == 'K') && $this->response['demo'] != 'Y') {
            if ($this->isFraudTransaction($notifyAdmin)) {
                $transaction = "Payment rejected - Reason: FRAUD";
                $cPlugin->PaymentRejected($transaction, false);
            } else {
                $transaction = " 2checkout Payment of $lPricePaid was accepted (OrderID:".$lOrderID.")";
                $cPlugin->PaymentAccepted($lPricePaid, $transaction);
            }
        } else {
            //if the payment was made in demo mode let's log it
            if ($this->response['demo']=='Y') {
                if ($this->isFraudTransaction($notifyAdmin)) {
                    $transaction = "Payment rejected - Reason: FRAUD (DEMO MODE)";
                    $cPlugin->PaymentRejected($transaction, false);
                } else {
                    $transaction = " 2checkout Payment of $lPricePaid was accepted (OrderID:".$lOrderID.") (DEMO MODE)";
                    $cPlugin->PaymentAccepted($lPricePaid, $transaction);
                }
            } else {
                //if not in demo mode we will log the reason_response_code from 2co
                if (!isset($this->response['ce_invoice_num'])) {
                    $this->_log2CheckoutCallback(0, true);
                } else {
                    $this->_log2CheckoutCallback($this->response['ce_invoice_num'], true);
                }

                $lErrorCode = $this->response['x_response_reason_code'];
                $transaction = "Payment rejected - Reason: ".$lErrorCode;
                $cPlugin->PaymentRejected($transaction, false);
            }
        }

        if ($notifyAdmin) {
            // Send an email to admin and let him know about the invoice payment,
            // and that he must set a Secret Word in the plugin configuration.
            if ($recipients = $this->settings->get('Application Error Notification')) {
                include_once 'library/CE/NE_MailGateway.php';
                $mailGateway = new NE_MailGateway();
                $body = "A 2Checkout payment for invoice ".$this->response['ce_invoice_num']." has been received.\n"
                    ."The payment amount was ".$this->response['x_amount']." and the 2Checkout Transaction Id was ".$this->response['order_number'].".\n"
                    ."The payment will be applied to the invoice.\n\n"
                    ."In order to avoid frauds with this kind of payments, please take a look on your 2Checkout plugin configuration and set a proper 'Secret Word'.\n"
                    ."Please take in count, you will also need to set the 'Secret Word' on the 2Checkout Site Management page.\n\n"
                    ."You will continue getting this kind of emails, until you set a proper 'Secret Word' for 2Checkout.\n\n"
                    ."Thank you.\n";
                $recipients = explode("\r\n", $recipients);

                foreach ($recipients as $recipient) {
                    $mailSend = $mailGateway->mailMessageEmail(
                        array('HTML' => null, 'plainText' => $body),
                        $this->settings->get('Support E-mail'),
                        $this->settings->get('Support E-mail'),
                        $recipient,
                        '',
                        'ClientExec 2Checkout Security Risk Notification',
                        1
                    );
                }
            }
        }

        //Need to check to see if user is coming from signup
        if ($lSignUp == 1) {
            if ($this->settings->get('Signup Completion URL') != '') {
                $returnURL = $this->settings->get('Signup Completion URL'). '?success=1';
            } else {
                $returnURL = CE_Lib::getSoftwareURL()."/order.php?step=complete&pass=1";
            }
        } else {
            $returnURL = CE_Lib::getSoftwareURL()."/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $lInvoiceID;
        }

        header("Location: " . $returnURL);
    }

    function isFraudTransaction(&$notifyAdmin)
    {
        // IT WILL CONSIDER FRAUD, IN ANY OF THIS CASES:
        // - The ce_invoice_hash was not properly generated
        // - The ce_invoice_hash can not be decoded
        // - The invoice id taken from decoding the ce_invoice_hash do not match with the ce_invoice_num
        $tInvoiceId = 0;
        if (isset($this->response['ce_invoice_hash']) && $this->response['ce_invoice_hash'] != "WRONGHASH") {
            $tInvoiceId = Invoice::decodeInvoiceHash($this->response['ce_invoice_hash']);
        }
        if (!isset($this->response['ce_invoice_num'])) {
            $this->response['FRAUD REASON'] = 'INVALID INVOICE NUMBER';
            $this->_log2CheckoutCallback(0, true);
            return true;
        } elseif (is_a($tInvoiceId, 'CE_Error') || $tInvoiceId == 0 || $tInvoiceId != $this->response['ce_invoice_num']) {
            $this->response['FRAUD REASON'] = 'INVALID INVOICE NUMBER'.(is_a($tInvoiceId, 'CE_Error'))? '. '.$tInvoiceId->getMessage() : '';
            $this->_log2CheckoutCallback($this->response['ce_invoice_num'], true);
            return true;
        }

        // IT WILL CONSIDER FRAUD, WHEN:
        // - There is no order_number (empty or NA)
        if (!isset($this->response['order_number']) || $this->response['order_number'] == '' || $this->response['order_number'] == 'NA') {
            $this->response['FRAUD REASON'] = 'INVALID ORDER NUMBER';
            $this->_log2CheckoutCallback($tInvoiceId, true);
            return true;
        }

        // IT WILL CONSIDER FRAUD, WHEN:
        // - Seller ID do not match
        $VendorNumber = $this->settings->get('plugin_2checkout_Seller ID');
        if (!isset($this->response['x_login']) || $this->response['x_login'] != $VendorNumber) {
            $this->response['FRAUD REASON'] = 'INVALID SELLER ID';
            $this->_log2CheckoutCallback($tInvoiceId, true);
            return true;
        }

        // IT WILL CONSIDER FRAUD, WHEN:
        // - There are duplicated 2checkout transactions with the same transactionid and amount
        //   (They are used to generate the 2checkout MD5 hash, so there should not be 2 equal combinations)
        $selectQuery = "SELECT COUNT(*) "
            ."FROM `invoicetransaction` "
            ."WHERE `accepted` = 1 "
            ."AND `transactionid` = ? "
            ."AND `response` LIKE '%2checkout%' "
            ."AND `action` = 'charge' "
            ."AND `amount` = ".$this->response['x_amount']." ";
        $result = $this->db->query($selectQuery, $this->response['order_number']);
        list($numTransactions) = $result->fetch();
        if ($numTransactions > 0) {
            $this->response['FRAUD REASON'] = 'DUPLICATED TRANSACTION';
            $this->_log2CheckoutCallback($tInvoiceId, true);
            return true;
        }

        $notifyAdmin = false;
        $SecretWord = $this->settings->get('plugin_2checkout_Secret Word');
        if ($SecretWord != '') {
            // IT WILL CONSIDER FRAUD, WHEN:
            // - The 2checkout MD5 hash is not valid
            //   The 2checkout MD5 hash structure is:               uppercase( md5( secret word + vendor number + order number + total ) )
            //   But, the 2checkout MD5 hash structure for demo is: uppercase( md5( secret word + vendor number +            1 + total ) )
            //  ( https://www.2checkout.com/documentation/checkout/passback/validation )
            if ($this->response['demo'] != 'Y') {
                $string_to_hash = $SecretWord.$VendorNumber.$this->response['order_number'].$this->response['x_amount'];
            } else {
                $string_to_hash = $SecretWord.$VendorNumber.'1'.$this->response['x_amount'];
            }

            $check_key = strtoupper(md5($string_to_hash));
            if ($check_key != $this->response['x_MD5_Hash']) {
                $this->response['FRAUD REASON'] = 'INVALID HASH';
                $this->_log2CheckoutCallback($tInvoiceId, true);
                return true;
            }
        } else {
            // Send an email to admin and let him know about the invoice payment,
            // and that he must set a Secret Word in the plugin configuration.
            $notifyAdmin = true;
        }

        // FRAUD VALIDATIONS PASSED
        $this->_log2CheckoutCallback($tInvoiceId);
        return false;
    }

    function _log2CheckoutCallback($InvoiceId, $Error = false)
    {
        // GET THE CUSTOMER ID
        $CustomerId = 0;
        if ($InvoiceId != 0) {
            $query = "SELECT `customerid` "
                ."FROM `invoice` "
                ."WHERE `id` = ? ";
            $result = $this->db->query($query, $InvoiceId);
            list($tCustomerId) = $result->fetch();

            if (isset($tCustomerId)) {
                $CustomerId = $tCustomerId;
            }
        }

        if ($Error || $InvoiceId == 0 || $CustomerId == 0) {
            require_once 'modules/admin/models/Error_EventLog.php';
            $Log = Error_EventLog::newInstance(
                false,
                $CustomerId,
                $InvoiceId,
                ERROR_EVENTLOG_2CHECKOUT_CALLBACK,
                NE_EVENTLOG_USER_SYSTEM,
                serialize($this->response)
            );
        } else {
            require_once 'modules/billing/models/Invoice_EventLog.php';
            $Log = Invoice_EventLog::newInstance(
                false,
                $CustomerId,
                $InvoiceId,
                INVOICE_EVENTLOG_2CHECKOUT_CALLBACK,
                NE_EVENTLOG_USER_SYSTEM,
                serialize($this->response)
            );
        }
        $Log->save();
    }
}
?>
