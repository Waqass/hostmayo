<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice_EventLog.php';
require_once 'modules/admin/models/Error_EventLog.php';

class PluginPaypalCallback extends PluginCallback
{

    function processCallback()
    {
        if (!isset($GLOBALS['testing'])) {
            $testing = false;
        } else {
            $testing = $GLOBALS['testing'];
        }

        CE_Lib::log(4, 'Paypal callback invoked');

        $logOK = $this->_logPaypalCallback();

        if ($this->settings->get('plugin_paypal_Use PayPal Sandbox') == '0' && isset($_POST['test_ipn']) && $_POST['test_ipn'] == '1') {
            CE_Lib::log(4, "** Paypal sandbox callback but account is in production mode => callback discarded");
            return;
        }

        $ppTransType = '';
        if (!isset($_POST['txn_type']) && isset($_POST['payment_status']) && $_POST['payment_status'] == 'Refunded') {
            $ppTransType = 'refund';
        }

        if (!isset($_POST['txn_type']) && $ppTransType == '') {
            CE_Lib::log(4, 'Paypal callback ignored: txn_type is not defined');
            return;
        }

        // Assign IPN Variables
        // Different possible transaction types (txn_type) for subscriptions:
        // 1. 'subscr_signup': issued just after the client has paid the inicial amount. We ignore this.
        // 2. 'subscr_payment': issued just after the previous one and for every subsequent payment.
        if ($ppTransType == '') {
            $ppTransType = $_POST['txn_type'];
        }

        $ppTransID = @$_POST['txn_id']; // Transaction ID (Unique) (not defined for subscription cancellations)
        $ppPayStatus = @$_POST['payment_status']; // Payment Status (not defined for subscription cancellations)
        $ppPayAmount = @$_POST['mc_gross']; // Total paid for this transaction (not defined for subscription cancellations)

        $customValues = explode("_", $_POST["custom"]);
        $tInvoiceID         = $customValues[0];
        $tIsRecurring       = $customValues[1];
        $tGenerateInvoice   = $customValues[2];
        $tRecurringExclude  = '';
        if (isset($customValues[3])) {
            $tRecurringExclude = $customValues[3];
        }

        CE_Lib::log(4, "\$ppTransType: $ppTransType; \$ppTransID: $ppTransID; \$ppPayStatus: $ppPayStatus;");
        CE_Lib::log(4, "\$ppPayAmount: $ppPayAmount; \$tInvoiceID: $tInvoiceID; \$tIsRecurring: $tIsRecurring; \$tGenerateInvoice: $tGenerateInvoice \$tRecurringExclude: $tRecurringExclude");

        if (!$logOK) {
            return;
        }

        // Create Plugin class object to interact with CE.
        $cPlugin = new Plugin($tInvoiceID, 'paypal', $this->user);

        // Comfirm the callback before assuming anything
        $exit = false;
        $createTicket = false;
        $maxRetries = 3;
        for ($retry = 1; $retry <= $maxRetries; $retry++) {
            $exit = false;
            $createTicket = false;
            CE_Lib::log(4, "Requesting callback confirmation (attempt $retry of $maxRetries); sending request: $paypal_url?$req");
            try{
                $res = $this->_requestConfirmation($testing);
            }catch (Exception $e){
                $customerid = $cPlugin->m_Invoice->getUserID();
                $errorLog = Error_EventLog::newInstance(false,
                    (isset($customerid))? $customerid : 0,
                    $tInvoiceID,
                    ERROR_EVENTLOG_PAYPAL_REQUEST_CONFIRMATION,
                    NE_EVENTLOG_USER_SYSTEM,
                    serialize($this->_utf8EncodePaypalCallback($_POST))
                );
                $errorLog->save();

                //exit;
                $exit = true;
                continue;
            }

            CE_Lib::log(4, "Request Confirmation Returned (attempt $retry of $maxRetries): ".$res);
            if (strpos ($res, "VERIFIED") !== false) {
                CE_Lib::log(4, "Callback has been verified successfully");
                break;
            } elseif (strpos ($res, "INVALID") !== false) {
                CE_Lib::log(4, "Callback verification returned 'INVALID'");
                $transaction = "Paypal IPN returned INVALID to Confirmation.";
                $cPlugin->PaymentRejected($transaction);

                //return;
                $exit = true;
                break;
            } else {
                CE_Lib::log(1, "Callback not returning verification code of 'VERIFIED' or 'INVALID' (attempt $retry of $maxRetries). Original Paypal callback details: ".print_r($_POST, true));

                //return;
                $createTicket = true;
                $exit = true;
            }
        }
        if ($exit) {
            if ($createTicket) {
                $customerid = $cPlugin->m_Invoice->getUserID();
                $message = "There was a PayPal Callback not returning verification code of 'VERIFIED' or 'INVALID'. Attempted verification $maxRetries time(s).\n"
                          ."\n"
                          ."Original Paypal callback details:\n"
                          .print_r($_POST, true)."\n"
                          ."\n"
                          ."When trying to verify, Paypal returned:\n"
                          .$res."\n"
                          ."\n"
                          ."Please make sure to login to your PayPal account and verify the transaction by yourself. Also, you probably will need to take some manual actions over an invoice.\n"
                          ."\n"
                          ."Thanks.";
                if (isset($customerid)) {
                    // GENERATE TICKET
                    $tUser = new User($customerid);
                    $subject = 'Issue when verifying paypal callback';
                    $cPlugin->createTicket(false, $subject, $message, $tUser);
                } else {
                    CE_Lib::log(1, $message);
                }
            }
            exit;
        }

        // Comfirm the callback before assuming anything
        if ($ppTransType == 'subscr_signup') {  // Subscription started
            // Here we should update a field in the first invoice with the subscription date:
            //     $_POST['subscr_date']
            // Time/Date stamp generated by PayPal , in the following format: HH:MM:SS DD Mmm YY, YYYY PST
            //                                                                22:21:09 Oct 20, 2009 PDT
            // However, I am not believing in this param now, because seems that the documentacion says
            // one thing different than what I am really getting in this parameter.

            //Lets mark the recurring fees as active subscription.
            $cPlugin->setRecurring($tRecurringExclude);

            //Lets avoid updating invoice instance on this callback because there is another callback very close that is also updating the invoice, causing to lost some data.
            //The subscription will be instead updated when getting the payment callback.
            //$cPlugin->setSubscriptionId($_POST['subscr_id'], $tRecurringExclude);

            $transaction = "Started paypal subscription. Subscription ID: ".$_POST['subscr_id'];
            $cPlugin->logSubscriptionStarted($transaction, $_POST['subscr_id'].' '.$_POST['subscr_date']);

            CE_Lib::log(4, 'Paypal subscr_signup callback ignored');
            return;
        }

        $newInvoice = false;

        // Check to see if this Invoice is not unpaid
        if ($cPlugin->IsUnpaid() == 0 && $ppTransType == 'subscr_payment') {
            $cPlugin->setSubscriptionId($_POST['subscr_id'], $tRecurringExclude);

            CE_Lib::log(4, 'Previous subscription invoice is already paid');
            // If it is, then check to see if the GenerateInvoice variable was passed to the script
            if ($tGenerateInvoice) {
                // If it was and is TRUE (1), set internal Plugin object variables to the existing invoice's information.
                if ($cPlugin->TransExists($ppTransID)) {
                    $newInvoice = $cPlugin->retrieveInvoiceForTransaction($ppTransID);

                    if ($newInvoice && $cPlugin->m_Invoice->isPending()) {
                        CE_Lib::log(4, 'Invoice already exists, and is marked as pending');
                    }
                } else {
                    //Search for existing invoice, unpaid and with same subscription id
                    $newInvoice = $cPlugin->retrieveLastInvoiceForSubscription($_POST['subscr_id'], $ppTransID);

                    if ($newInvoice === false) {
                        $customerid = $cPlugin->m_Invoice->getUserID();

                        //try to generate the customer invoices and search again
                        include_once 'modules/billing/models/BillingGateway.php';
                        $billingGateway = new BillingGateway($this->user);
                        $billingGateway->processCustomerBilling($customerid, $_POST['subscr_id']);

                        //Search for existing invoice, unpaid and with same subscription id
                        $newInvoice = $cPlugin->retrieveLastInvoiceForSubscription($_POST['subscr_id'], $ppTransID);

                        if ($newInvoice === false) {
                            $message = "There was a PayPal subscription payment for subscription ".$_POST['subscr_id'].".\n"
                                      ."However, the system could not find any pending invoice for this subscription. The PayPal transaction id for the payment is ".$ppTransID.".\n"
                                      ."Please take a look at the customer to confirm if this payment was required.\n"
                                      ."If the payment was not required, please make sure to login to your PayPal account and refund the payment. If the subscription should no longer apply, please cancel the subscription in your PayPal account.\n"
                                      ."\n"
                                      ."Thanks.";
                            if (isset($customerid)) {
                                // GENERATE TICKET
                                $tUser = new User($customerid);
                                $subject = 'Issue with paypal subscription payment';
                                $cPlugin->createTicket($_POST['subscr_id'], $subject, $message, $tUser);
                            } else {
                                CE_Lib::log(1, $message);
                            }

                            $errorLog = Error_EventLog::newInstance(false,
                                (isset($customerid))? $customerid : 0,
                                $tInvoiceID,
                                ERROR_EVENTLOG_PAYPAL_CALLBACK,
                                NE_EVENTLOG_USER_SYSTEM,
                                serialize($this->_utf8EncodePaypalCallback($_POST))
                            );
                            $errorLog->save();
                            exit;
                        }
                    }
                }
            } else {
                CE_Lib::log(1, 'Error: exiting Paypal callback invocation');
                exit;
            }
        } elseif ($cPlugin->IsUnpaid() == 0 && $tIsRecurring && $ppTransType != 'refund' && $ppPayStatus != 'Refunded') {
            //LETS SEARCH THE LATEST SUBSCRIPTION INVOICE, NO MATTER THE STATUS
            $newInvoice = $cPlugin->retrieveLastInvoiceForSubscription($_POST['subscr_id'], $ppTransID, false);
            if ($newInvoice === false) {
                $errorLog = Error_EventLog::newInstance(false,
                    (isset($customerid))? $customerid : 0,
                    $tInvoiceID,
                    ERROR_EVENTLOG_PAYPAL_CALLBACK,
                    NE_EVENTLOG_USER_SYSTEM,
                    serialize($this->_utf8EncodePaypalCallback($_POST))
                );
                $errorLog->save();
                exit;
            }
        }

        //Add plugin details
        $cPlugin->setAmount($ppPayAmount);
        $cPlugin->m_TransactionID = $ppTransID;
        $cPlugin->m_Action = "charge";
        $cPlugin->m_Last4 = "NA";

        // Manage the payment
        // TODO check that receiver_email is an email address in your PayPal account
        if ($tIsRecurring && $ppTransType != 'refund' && $ppPayStatus != 'Refunded') {
            // Uncomment to test payment failures through subscription cancellations
            // if ($ppTransType == 'subscr_cancel') $ppTransType = 'subscr_failed';
            switch($ppTransType) {
                case 'subscr_payment':  // Subscription payment received
                    switch($ppPayStatus) {
                        case "Completed": // The payment has been completed, and the funds have been added successfully to your account balance.
                            //The subscription will be updated when getting the payment callback, to avoid a conflcik with the subscr_signup callback
                            $cPlugin->setSubscriptionId($_POST['subscr_id'], $tRecurringExclude);
                            $transaction = "Paypal payment of $ppPayAmount was accepted. Original Signup Invoice: $tInvoiceID (OrderID:".$ppTransID.")";
                            $cPlugin->PaymentAccepted($ppPayAmount, $transaction, $ppTransID, $testing);
                            break;
                        case "Pending": // The payment is pending. See pending_reason for more information.
                            //The subscription will be updated when getting the payment callback, to avoid a conflcik with the subscr_signup callback
                            $cPlugin->setSubscriptionId($_POST['subscr_id'], $tRecurringExclude);
                            $transaction = "Paypal payment of $ppPayAmount was marked 'pending' by Paypal. Reason: ".$_POST['pending_reason'].". Original Signup Invoice: $tInvoiceID (OrderID:".$ppTransID.")";
                            $cPlugin->PaymentPending($transaction, $ppTransID);
                            break;
                        case "Failed":  // The payment has failed. This happens only if the payment was made from your customer�s bank account.
                            $transaction = "Paypal payment of $ppPayAmount was rejected. Original Signup Invoice: $tInvoiceID (OrderID:".$ppTransID.")";
                            $cPlugin->PaymentRejected($transaction);
                            break;
                    }
                    break;
                case 'subscr_eot':    // Subscription expired
                    $transaction = "Paypal subscription has expired. Subscription ID: ".$_POST['subscr_id'];
                    $cPlugin->logSubscriptionExpired($transaction, $_POST['subscr_id'].' '.$_POST['subscr_date']);
                    break;
                case 'subscr_cancel': // Subscription canceled
                    CE_Lib::log(4, "Subscription has been cancelled on Paypal's side.");
                    $tUser = new User($cPlugin->m_Invoice->m_UserID);
                    if (in_array($tUser->getStatus(), StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_CANCELLED))) {
                        CE_Lib::log(4, 'User is already cancelled. Ignore callback.');
                    } else {
                        $subject = 'Gateway recurring payment cancelled';
                        $message = "Recurring payment for invoice $tInvoiceID, corresponding to package \"{$_POST['item_name']}\" has been cancelled by customer.";
                        // If createTicket returns false it's because this transaction has already been done
                        // Use subscr_id because txn_id is not sent on cancellation IPNs from Paypal
                        if (!$cPlugin->createTicket($_POST['subscr_id'], $subject, $message, $tUser)) {
                            exit;
                        }
                    }
                    $transaction = "Paypal subscription cancelled by customer. Original Signup Invoice: $tInvoiceID";
                    $cPlugin->resetRecurring($transaction, $_POST['subscr_id'], $tRecurringExclude, $tInvoiceID);
                    break;
                case 'subscr_failed': // Subscription signup failed
                    // this is caused by lack of funds for example
                    $subject = 'Gateway recurring payment failed';
                    $reason = isset($_POST['pending_reason'])? $_POST['pending_reason'] : 'unknown';
                    $message = "Recurring payment for invoice $tInvoiceID, corresponding to package \"{$_POST['item_name']}\" has failed.\n";
                    $message .= "Reason: $reason.";
                    $tUser = new User($cPlugin->m_Invoice->m_UserID);
                    // if createTicket returns false it's because this transaction has already been done
                    if (!$cPlugin->createTicket($_POST['subscr_id'], $subject, $message, $tUser)) {
                        exit;
                    }
                    // log failed transaction
                    $transaction = "Recurring subscription payment failed. Reason: $reason.";
                    $cPlugin->logFailedSubscriptionPayment($transaction, $_POST['subscr_id'].' '.$_POST['subscr_date']);
                    break;
            }
        } elseif ($ppTransType == 'refund' && $ppPayStatus == 'Refunded') {
            $cPlugin->m_Action = "refund";
            $ppPayAmount = str_replace("-", "", $ppPayAmount);

            if (isset($_POST['parent_txn_id'])) {
                $ppParentTransID = $_POST['parent_txn_id'];

                if ($cPlugin->TransExists($ppParentTransID)) {
                    $newInvoice = $cPlugin->retrieveInvoiceForTransaction($ppParentTransID);

                    if ($newInvoice && ($cPlugin->m_Invoice->isPaid() || $cPlugin->m_Invoice->isPartiallyPaid())) {
                        $transaction = "Paypal payment of $ppPayAmount was refunded. Original Signup Invoice: $tInvoiceID (OrderID:".$ppTransID.")";
                        $cPlugin->PaymentRefunded($ppPayAmount, $transaction, $ppTransID);
                    } elseif (!$cPlugin->m_Invoice->isRefunded()) {
                        CE_Lib::log(1, 'Related invoice not found or not set as paid on the application, when doing the refund');
                    }
                } else {
                    CE_Lib::log(1, 'Parent transaction id not matching any existing invoice on the application, when doing the refund');
                }
            } else {
                CE_Lib::log(1, 'Callback not returning parent_txn_id when refunding');
            }
        } else {
            // Add Code for Normal Payment
            switch($ppPayStatus) {
                case "Completed":
                    $transaction = "Paypal payment of $ppPayAmount was accepted. Original Signup Invoice: $tInvoiceID (OrderID:".$ppTransID.")";
                    $cPlugin->PaymentAccepted($ppPayAmount, $transaction, $ppTransID, $testing);
                    break;
                case "Pending":
                    $transaction = "Paypal payment of $ppPayAmount was marked 'pending' by Paypal. Original Signup Invoice: $tInvoiceID (OrderID:".$ppTransID.")";
                    $cPlugin->PaymentPending($transaction, $ppTransID);
                    break;
                case "Failed":
                    $transaction = "Paypal payment of $ppPayAmount was rejected. Original Signup Invoice: $tInvoiceID (OrderID:".$ppTransID.")";
                    $cPlugin->PaymentRejected($transaction);
                    break;
            }
        }
    }

    function _requestConfirmation($testing)
    {
        if ($testing) {
            return 'VERIFIED';
        } else {
            $raw_post_data = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);
            $myPost = array();
            foreach ($raw_post_array as $keyval) {
                $keyval = explode ('=', $keyval);
                if (count($keyval) == 2) {
                    $myPost[$keyval[0]] = urldecode($keyval[1]);
                }
            }
            // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
            $req = 'cmd=_notify-validate';
            foreach ($myPost as $key => $value) {
                $value = urlencode($value);
                $req .= "&$key=$value";
            }

            // Step 2: POST IPN data back to PayPal to validate
            $ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/../../../library/cacert.pem');
            if ( !($res = curl_exec($ch)) ) {
                CE_Lib::log(1, "PayPal Callback Verification failed: " . curl_error($ch));
                curl_close($ch);
                exit;
            }
            curl_close($ch);
            CE_Lib::log(4, 'PayPal Callback Response: ' . $res);
            return $res;
        }
    }

    //return true if can add the event log
    //return false if can not add the event log
    function _logPaypalCallback()
    {
        if (!isset($_POST["custom"])) {
            if (!isset($_POST["txn_type"]) || $_POST["txn_type"] != "new_case") {
                $errorLog = Error_EventLog::newInstance(false,
                    0,
                    0,
                    ERROR_EVENTLOG_PAYPAL_CALLBACK,
                    NE_EVENTLOG_USER_SYSTEM,
                    serialize($this->_utf8EncodePaypalCallback($_POST))
                );
                $errorLog->save();
            }

            return false;
        }

        // search the customer id based on the invoice id
        $customValues = explode("_", $_POST["custom"]);
        $tInvoiceID         = $customValues[0];

        $query = "SELECT `customerid` "
                ."FROM `invoice` "
                ."WHERE `id` = ? ";
        $result = $this->db->query($query, $tInvoiceID);
        list($customerid) = $result->fetch();

        $invoiceNotFound = false;

        if (!isset($customerid)) {
            $invoiceNotFound = true;

            // search the customer id based on the email address
            $query = "SELECT `id` "
                    ."FROM `users` "
                    ."WHERE `email` = ? ";
            $result = $this->db->query($query, $_POST["payer_email"]);
            list($customerid) = $result->fetch();
        }

        if (!isset($customerid) || $invoiceNotFound) {

            $errorLog = Error_EventLog::newInstance(false,
                (isset($customerid))? $customerid : 0,
                $tInvoiceID,
                ERROR_EVENTLOG_PAYPAL_CALLBACK,
                NE_EVENTLOG_USER_SYSTEM,
                serialize($this->_utf8EncodePaypalCallback($_POST))
            );
            $errorLog->save();
            return false;
        } else {

            $invoiceLog = Invoice_EventLog::newInstance(false,
                $customerid,
                $tInvoiceID,
                INVOICE_EVENTLOG_PAYPAL_CALLBACK,
                NE_EVENTLOG_USER_SYSTEM,
                serialize($this->_utf8EncodePaypalCallback($_POST))
            );
            $invoiceLog->save();
            return true;
        }
    }

    //return array with the utf8 encoded values of the original array
    function _utf8EncodePaypalCallback($callbackPost)
    {
        if (is_array($callbackPost)) {
            foreach ($callbackPost as $postKey => $postValue) {
                $callbackPost[$postKey] = utf8_encode($postValue);
            }
        } else {
            $callbackPost = array();
        }

        return $callbackPost;
    }
}