<?php
require_once('AuthnetCIM.class.php');
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/admin/models/PluginCallback.php';

class PluginAuthnetcimCallback extends PluginCallback
{
    function processCallback()
    {
        if (isset($_GET['authnetcim_action']) && !empty($_GET['authnetcim_action'])) {
            // Actually handle the signup URL setting
            if ($this->settings->get('Signup Completion URL') != '') {
                $returnURL = $this->settings->get('Signup Completion URL').'?success=1';
                $returnURL_Cancel = $this->settings->get('Signup Completion URL');
            } else {
                $clientExecURL = CE_Lib::getSoftwareURL();
                if (mb_substr($clientExecURL,-1,1) != '//') {
                    $clientExecURL .= "/";
                }
                $returnURL = $clientExecURL."order.php?step=complete&pass=1";
                $returnURL_Cancel = $clientExecURL."order.php?step=3";
            }

            $authnetcim_action = $_GET['authnetcim_action'];

            switch ($authnetcim_action) {
                case 'PayInvoice':
                    $tInvoiceId = 0;
                    if (isset($_GET['ce_invoice_hash']) && $_GET['ce_invoice_hash'] != "WRONGHASH") {
                        $tInvoiceId = Invoice::decodeInvoiceHash($_GET['ce_invoice_hash']);
                    }
                    if (!is_a($tInvoiceId, 'CE_Error') && $tInvoiceId != 0) {
                        $errorMessage = '';
                        $tempInvoice = new Invoice($tInvoiceId);
                        $tInvoiceTotal = $tempInvoice->getBalanceDue();

                        $cPlugin = new Plugin($tInvoiceId, "authnetcim", $this->user);
                        $cPlugin->setAmount($tInvoiceTotal);
                        $cPlugin->setAction('charge');

                        //Authorize.net CIM Credentials from CE plugin
                        $myapilogin = $this->settings->get('plugin_authnetcim_Authorize.Net CIM API Login ID');
                        $transactionKey = $this->settings->get('plugin_authnetcim_Authorize.Net CIM Transaction Key');
                        $sandbox = $this->settings->get('plugin_authnetcim_Authorize.Net CIM Test Mode');
                        $serverToUse = ($sandbox)? AuthnetCIM::USE_DEVELOPMENT_SERVER : AuthnetCIM::USE_PRODUCTION_SERVER;

                        $profile_id == '';
                        $Billing_Profile_ID = '';
                        $profile_id_array = array();
                        $user = new User($tempInvoice->getUserID());
                        if ($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != '') {
                            $profile_id_array = unserialize($Billing_Profile_ID);
                            if (is_array($profile_id_array) && isset($profile_id_array['authnetcim'])) {
                                $profile_id = $profile_id_array['authnetcim'];
                            }
                        }

                        if ($profile_id != '') {
                            try {
                                $cim = new AuthnetCIM($myapilogin, $transactionKey, $serverToUse);
                                $cim->setParameter('customerProfileId', $profile_id);
                                $cim->getCustomerProfile();
                                if ($cim->isSuccessful()) {
                                    $customerProfile =  array(
                                        'profile_id'          => $profile_id,
                                        'payment_profile_id'  => $cim->getPaymentProfileId(),
                                        'shipping_profile_id' => $cim->getCustomerAddressId()
                                    );

                                    $validationMode = $this->settings->get('plugin_authnetcim_Authorize.Net CIM Validation Mode');
                                    if ($validationMode == '') {
                                        $validationMode = 'liveMode';
                                    }

                                    try {
                                        //Validate customer payment profile
                                        if ($customerProfile['profile_id'] != '' && $customerProfile['payment_profile_id'] != '' && $customerProfile['shipping_profile_id'] != '') {
                                            $cim = new AuthnetCIM($myapilogin, $transactionKey, $serverToUse);
                                            $cim->setParameter('customerProfileId', $customerProfile['profile_id']);
                                            $cim->setParameter('customerPaymentProfileId', $customerProfile['payment_profile_id']);
                                            $cim->setParameter('customerShippingAddressId', $customerProfile['shipping_profile_id']);
                                            $cim->setParameter('validationMode', $validationMode);
                                            $cim->validateCustomerPaymentProfile();
                                            if ($cim->isSuccessful()) {
                                                //Invoice Information from CE
                                                $amount = sprintf("%01.2f", round($tInvoiceTotal, 2));
                                                $purchase_invoice_id = $tInvoiceId;

                                                try {
                                                    // Process the transaction
                                                    $cim = new AuthnetCIM($myapilogin, $transactionKey, $serverToUse);
                                                    $cim->setParameter('customerProfileId', $customerProfile['profile_id']);
                                                    $cim->setParameter('customerPaymentProfileId', $customerProfile['payment_profile_id']);
                                                    $cim->setParameter('customerShippingAddressId', $customerProfile['shipping_profile_id']);
                                                    $cim->setParameter('amount', $amount);

                                                    $cim->setParameter('orderInvoiceNumber', true);
                                                    $cim->setParameter('invoiceNumber', $purchase_invoice_id);
                                                    $cim->setParameter('description', 'Invoice '.$purchase_invoice_id);
                                                    $cim->createCustomerProfileTransaction('profileTransAuthCapture');

                                                    // Get the payment profile ID returned from the request
                                                    $approval_code = '';
                                                    $transaction_ID = '';
                                                    if ($cim->isSuccessful()) {
                                                        $customerProfile = array(
                                                            'approval_code'  => $cim->getAuthCode(),
                                                            'transaction_ID' => $cim->getTransactionID(),
                                                            'amount'         => $amount
                                                        );

                                                        $cPlugin->setTransactionID($customerProfile['transaction_ID']);
                                                        $cPlugin->PaymentAccepted($customerProfile['amount'], "Authorize.Net CIM payment of {$customerProfile['amount']} was accepted. Approval code: {$customerProfile['approval_code']}", $customerProfile['transaction_ID']);
                                                    } else {
                                                        $errorMessage = $cim->getResponseSummary();
                                                    }
                                                } catch (AuthnetCIMException $e) {
                                                    $errorMessage = $e->getMessage();
                                                }
                                            } else {
                                                $errorMessage = $cim->getResponseSummary();
                                            }
                                        } else {
                                            $errorMessage = 'The customer do not have a customer Authnet CIM payment profile or shipping profile';
                                        }
                                    } catch (AuthnetCIMException $e) {
                                        $errorMessage = $e->getMessage();
                                    }
                                } else {
                                    $errorMessage = $cim->getResponseSummary();
                                }
                            } catch (AuthnetCIMException $e) {
                                $errorMessage = $e->getMessage();
                            }
                        } else {
                            $errorMessage = 'The customer do not have a customer Authnet CIM profile';
                        }

                        if ($errorMessage == '') {
                            CE_Lib::redirectPage($returnURL);
                        } else {
                            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.$errorMessage);
                            CE_Lib::redirectPage($returnURL_Cancel);
                        }
                    }
                    break;
            }
        }
    }
}
?>
