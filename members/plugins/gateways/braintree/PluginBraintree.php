<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

/**
* @package Plugins
*/
class PluginBraintree extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
            lang("Plugin Name") => array(
                "type"        => "hidden",
                "description" => lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                "value"       => lang("Braintree")
            ),
            lang("Public Key") => array(
                "type"        => "text",
                "description" => lang("Braintree Public Key (user-specific public identifier).<br><b>NOTE:</b> This Public Key is required if you have selected Braintree as a payment gateway for any of your clients.<br>In order to get it, follow these steps in your Braintree account:<ol><li>Log into either the production Control Panel or the sandbox Control Panel, depending on which environment you are working in</li><li>Navigate to <b>Account</b> > <b>My User</b></li><li>Under <b>API Keys, Tokenization Keys, Encryption Keys</b>, click <b>View Authorizations</b>. If no API keys appear, click <b>Generate New API Key</b></li><li>Click <b>View</b> under the <b>Private Key</b> column to see your public and private keys, merchant ID, and environment</li></ol>"),
                "value"       => ""
            ),
            lang("Private Key") => array(
                "type"        => "password",
                "description" => lang("Braintree Private Key (user-specific secure identifier that should not be shared – even with Braintree).<br><b>NOTE:</b> This Private Key is required if you have selected Braintree as a payment gateway for any of your clients.<br>In order to get it, follow these steps in your Braintree account:<ol><li>Log into either the production Control Panel or the sandbox Control Panel, depending on which environment you are working in</li><li>Navigate to <b>Account</b> > <b>My User</b></li><li>Under <b>API Keys, Tokenization Keys, Encryption Keys</b>, click <b>View Authorizations</b>. If no API keys appear, click <b>Generate New API Key</b></li><li>Click <b>View</b> under the <b>Private Key</b> column to see your public and private keys, merchant ID, and environment</li></ol>"),
                "value"       => ""
            ),
            lang("Merchant ID") => array(
                "type"        => "text",
                "description" => lang("Braintree Merchant ID (a unique identifier for your gateway account, which is different than your merchant account ID).<br><b>NOTE:</b> This Merchant ID is required if you have selected Braintree as a payment gateway for any of your clients.<br>In order to get it, follow these steps in your Braintree account:<ol><li>Log into either the production Control Panel or the sandbox Control Panel, depending on which environment you are working in</li><li>Navigate to <b>Account</b> > <b>My User</b></li><li>Under <b>API Keys, Tokenization Keys, Encryption Keys</b>, click <b>View Authorizations</b>. If no API keys appear, click <b>Generate New API Key</b></li><li>Click <b>View</b> under the <b>Private Key</b> column to see your public and private keys, merchant ID, and environment</li></ol>"),
                "value"       => ""
            ),
            lang("Environment") => array(
                "type"        => "options",
                "description" => lang("Braintree Environment (value that specifies where requests should be directed – sandbox or production).<br><b>NOTE:</b> This Environment is required if you have selected Braintree as a payment gateway for any of your clients.<br>In order to get it, follow these steps in your Braintree account:<ol><li>Log into either the production Control Panel or the sandbox Control Panel, depending on which environment you are working in</li><li>Navigate to <b>Account</b> > <b>My User</b></li><li>Under <b>API Keys, Tokenization Keys, Encryption Keys</b>, click <b>View Authorizations</b>. If no API keys appear, click <b>Generate New API Key</b></li><li>Click <b>View</b> under the <b>Private Key</b> column to see your public and private keys, merchant ID, and environment</li></ol>"),
                "options"     => array(
                    'sandbox'    => lang( 'Sandbox' ),
                    'production' => lang('Production')
                ),
                "value"       => "sandbox"
            ),
            lang("Allow Paypal") => array (
                "type"        => "yesno",
                "description" =>lang("Select YES if you want to allow your customers to link a paypal account."),
                "value"       => "0"
            ),
            lang("Invoice After Signup") => array(
                "type"        => "yesno",
                "description" => lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                "value"       => "1"
            ),
            lang("Signup Name") => array(
                "type"        => "text",
                "description" => lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                "value"       => "Braintree"
            ),
            lang("Dummy Plugin") => array(
                "type"        => "hidden",
                "description" => lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                "value"       => "0"
            ),
            lang("Auto Payment") => array(
                "type"        => "hidden",
                "description" => lang("No description"),
                "value"       => "1"
            ),
            lang('CC Stored Outside') => array (
                'type'        => 'hidden',
                'description' => lang('Is Credit Card stored outside of Clientexec? 1 = YES, 0 = NO'),
                'value'       => '1'
            ),
            lang('Iframe Configuration') => array (
                'type'        => 'hidden',
                'description' => lang('Parameters to be used in the iframe when loaded, like: width, height, scrolling, frameborder'),
                'value'       => 'width="100%" height="400" scrolling="auto" frameborder="0"'
            ),
            lang("Check CVV2") => array(
                "type"        => "hidden",
                "description" => lang("Select YES if you want to accept CVV2 for this plugin."),
                "value"       => "0"
            )
        );
        return $variables;
    }

    function credit($params)
    {
        $params['refund'] = true;
        return $this->singlePayment($params);
    }

    function singlepayment($params)
    {
        return $this->autopayment($params);
    }

    function autopayment($params)
    {
        Braintree_Configuration::publicKey($this->settings->get("plugin_braintree_Public Key"));
        Braintree_Configuration::privateKey($this->settings->get("plugin_braintree_Private Key"));
        Braintree_Configuration::merchantId($this->settings->get("plugin_braintree_Merchant ID"));
        Braintree_Configuration::environment($this->settings->get("plugin_braintree_Environment"));

        //Create plug in class to interact with CE
        $invoice_id = $params['invoiceNumber'];
        $cPlugin = new Plugin($invoice_id, 'braintree', $this->user);
        $cPlugin->setAmount($params["invoiceTotal"]);

        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $cPlugin->setAction('refund');
        } else {
            $isRefund = false;
            $cPlugin->setAction('charge');
        }

        $profile_id = '';
        $customerProfile = $this->getCustomerProfile($params);
        if ($customerProfile['error']) {
            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.$customerProfile['detail']);
            return $this->user->lang("There was an error performing this operation.").' '.$customerProfile['detail'];
        } else {
            $profile_id = $customerProfile['profile_id'];

            $paymentMethodToken = '';
            $customer = Braintree_Customer::find($profile_id);

            if (count($customer->paymentMethods) > 0) {
                try {
                    if ($isRefund) {
                        $result = Braintree_Transaction::void($params['invoiceRefundTransactionId']);
                        if (!$result->success) {
                            $result = Braintree_Transaction::refund($params['invoiceRefundTransactionId']);
                        }
                        if ($result->success) {
                            $cPlugin->PaymentAccepted($params["invoiceTotal"], "Braintree refund of {$params["invoiceTotal"]} was successfully processed.", $result->transaction->id);
                            return array('AMOUNT' => $params["invoiceTotal"]);
                        } else {
                            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.@$result->message.' '.@$result->errors);
                            return $this->user->lang("There was an error performing this operation.");
                        }
                    } else {
                        $sale = array(
                            'customerId' => $profile_id,
                            'amount'     => sprintf("%01.2f", round($params["invoiceTotal"], 2)),
                            'orderId'    => $invoice_id,  // This field is get back in responce to track this transaction
                            'options'    => array(
                                'submitForSettlement' => true
                            )
                        );
                        $result = Braintree_Transaction::sale($sale);

                        if ($result->success) {
                            $cPlugin->m_TransactionID = $result->transaction->id;
                            $transaction = "Braintree Payment of {$params["invoiceTotal"]} was accepted";
                            $cPlugin->PaymentAccepted($params["invoiceTotal"], $transaction);
                            return '';
                        } else {
                            $transaction = "Payment rejected - Reason: ".$result->message;
                            $cPlugin->PaymentRejected($transaction, false);
                            return $this->user->lang("There was an error performing this operation.");
                        }
                    }
                } catch (Exception $e) {
                    $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.")." ".$e->getMessage());
                    return $this->user->lang("There was an error performing this operation.")." ".$e->getMessage();
                }
            } else {
                if ($params['isSignup']) {
                    return array(
                        'error' => false,
                        'FORM'  => $this->useForm($params)
                    );
                } else {
                    $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.$this->user->lang("The customer hasn't stored their credit card."));
                    return $this->user->lang("There was an error performing this operation.").' '.$this->user->lang("The customer hasn't stored their credit card.");
                }
            }
        }
    }

    // Create customer Braintree profile
    function createCustomerProfile($params)
    {
        Braintree_Configuration::publicKey($this->settings->get("plugin_braintree_Public Key"));
        Braintree_Configuration::privateKey($this->settings->get("plugin_braintree_Private Key"));
        Braintree_Configuration::merchantId($this->settings->get("plugin_braintree_Merchant ID"));
        Braintree_Configuration::environment($this->settings->get("plugin_braintree_Environment"));

        try {
            $result = Braintree_Customer::create([
                'firstName' => $params['userFirstName'],
                'lastName'  => $params['userLastName'],
                'company'   => $params['userOrganization'],
                'email'     => $params['userEmail'],
                'phone'     => $params['userPhone']
            ]);
            $profile_id = $result->customer->id;

            $Billing_Profile_ID = '';
            $profile_id_array = array();
            $user = new User($params['CustomerID']);
            if ($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != '') {
                $profile_id_array = unserialize($Billing_Profile_ID);
            }
            if (!is_array($profile_id_array)) {
                $profile_id_array = array();
            }
            $profile_id_array['braintree'] = $profile_id;
            $user->updateCustomTag('Billing-Profile-ID', serialize($profile_id_array));
            $user->save();

            return array(
                'error'      => false,
                'profile_id' => $profile_id
            );
        } catch (Exception $e) {
            return array(
                'error'  => true,
                'detail' => $this->user->lang("There was an error performing this operation.")." ".$e->getMessage()
            );
        }
    }

    //Get customer Braintree profile
    function getCustomerProfile($params)
    {
        if (!isset($params['CustomerID']) && isset($params['userID'])) {
            $params['CustomerID'] = $params['userID'];
        }
        $user = new User($params['CustomerID']);
        $profile_id = '';
        $Billing_Profile_ID = '';
        if ($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != '') {
            $profile_id_array = unserialize($Billing_Profile_ID);
            if (is_array($profile_id_array) && isset($profile_id_array['braintree'])) {
                $profile_id = $profile_id_array['braintree'];
            }
        }

        if ($profile_id == '') {
            // Create or get customer Authnet CIM profile
            return $this->createCustomerProfile($params);
        } else {
            return array(
                'error'      => false,
                'profile_id' => $profile_id
            );
        }
    }

    function useForm($params)
    {
        echo $this->ShowURL($params);
        exit;
    }

    function ShowURL($params)
    {
        Braintree_Configuration::publicKey($this->settings->get("plugin_braintree_Public Key"));
        Braintree_Configuration::privateKey($this->settings->get("plugin_braintree_Private Key"));
        Braintree_Configuration::merchantId($this->settings->get("plugin_braintree_Merchant ID"));
        Braintree_Configuration::environment($this->settings->get("plugin_braintree_Environment"));

        // Get customer Braintree profile
        $customerProfile = $this->getCustomerProfile($params);

        if ($customerProfile['error']) {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                        <title>Untitled Document</title>
                    </head>
                    <body>'.$customerProfile['detail'].'</body>
                </html>';
        }

        try {
            $clientToken = Braintree_ClientToken::generate([
                "customerId" => $customerProfile['profile_id']
            ]);

            $allowPaypal = '';
            if ($this->settings->get("plugin_braintree_Allow Paypal")) {
                $allowPaypal = 'paypal: {flow: "vault"},';
            }

            $clientExecURL = CE_Lib::getSoftwareURL();
            $callbackURL = mb_substr($clientExecURL,-1,1) == '//' ? $clientExecURL."plugins/gateways/braintree/callback.php" : $clientExecURL."/plugins/gateways/braintree/callback.php";

            // Actually handle the signup URL setting
            if ($this->settings->get('Signup Completion URL') != '') {
                $returnURL = $this->settings->get('Signup Completion URL').'?success=1';
                $returnURL_Cancel = $this->settings->get('Signup Completion URL');
            } else {
                $returnURL = $params["clientExecURL"]."/order.php?step=complete&pass=1";
                $returnURL_Cancel = $params["clientExecURL"]."/order.php?step=3";
            }

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                        <meta charset="utf-8">
                        <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.no-icons.min.css" rel="stylesheet">
                        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
                        <script src="//js.braintreegateway.com/web/dropin/1.1.0/js/dropin.min.js"></script>
                    </head>
                    <body>';

            if ($params['isSignup']) {
                $html .= '
                        <div style="max-width:500px; margin:auto;">
                            <label>'.$this->user->lang("We do not store credit card information for your selected payment type").'</label><br/>
                            <br/>
                            <div style="width:500px;" id="dropin-container"></div>
                            <button class="btn btn-success" id="pay-button" disabled>'.$this->user->lang("Pay Invoice").'</button>
                        </div>
                        <script>
                            var paybutton = document.querySelector("#pay-button");';
            } else {
                $html .= '
                        <div style="width:500px;" id="dropin-container"></div>
                        <button class="btn btn-success" id="submit-button" disabled>'.$this->user->lang("Save Default Payment Method").'</button>
                        <button class="btn btn-danger" id="delete-button" disabled>'.$this->user->lang("Delete Payment Method").'</button>
                        <script>
                            var button = document.querySelector("#submit-button");
                            var deletebutton = document.querySelector("#delete-button");';
            }

            $html .= '
                            function braintreeDropinCreate () {
                                braintree.dropin.create({
                                    authorization: "'.$clientToken.'",
                                    '.$allowPaypal.'
                                    container: "#dropin-container"
                                }, function (createErr, instance) {';

            if ($params['isSignup']) {
                $html .= '
                                    document.getElementById("pay-button").disabled = false;
                                    paybutton.addEventListener("click", function () {
                                        document.getElementById("pay-button").disabled = true;
                                        instance.requestPaymentMethod(function (err, payload) {
                                            if(err){
                                                alert("There was an error with the request");
                                                document.getElementById("pay-button").disabled = false;
                                                exit;
                                            }

                                            $.post(
                                                "'.$callbackURL.'",
                                                {
                                                    braintree_action: "PayInvoice",
                                                    profile_id: "'.$customerProfile['profile_id'].'",
                                                    payload_nonce: payload.nonce,
                                                    invoice_id: "'.$params['invoiceNumber'].'",
                                                    invoice_total: "'.$params["invoiceTotal"].'"
                                                },
                                                function(result) {
                                                    var response = JSON.parse(result); 
                                                    if (response.success) {
                                                        //reload
                                                        location.href = "'.$returnURL.'"
                                                    } else {
                                                        location.href = "'.$returnURL_Cancel.'"
                                                        //alert(response.message);
                                                    }
                                                }
                                            );
                                        });
                                    });';
            } else {
                $html .= '
                                    document.getElementById("submit-button").disabled = false;
                                    document.getElementById("delete-button").disabled = false;
                                    button.addEventListener("click", function () {
                                        document.getElementById("submit-button").disabled = true;
                                        document.getElementById("delete-button").disabled = true;
                                        instance.requestPaymentMethod(function (err, payload) {
                                            if(err){
                                                alert("There was an error with the request");
                                                document.getElementById("submit-button").disabled = false;
                                                document.getElementById("delete-button").disabled = false;
                                                exit;
                                            }

                                            $.post(
                                                "'.$callbackURL.'",
                                                {
                                                    braintree_action: "DefaultPaymentMethod",
                                                    profile_id: "'.$customerProfile['profile_id'].'",
                                                    payload_nonce: payload.nonce
                                                },
                                                function(result) {
                                                    var response = JSON.parse(result); 
                                                    if (response.success) {
                                                        //reload
                                                        var elem = document.getElementById("dropin-container");
                                                        elem.innerHTML = "";
                                                        braintreeDropinCreate();
                                                    } else {
                                                        //alert(response.message);
                                                        document.getElementById("submit-button").disabled = false;
                                                        document.getElementById("delete-button").disabled = false;
                                                    }
                                                }
                                            );
                                        });
                                    });

                                    deletebutton.addEventListener("click", function () {
                                        document.getElementById("submit-button").disabled = true;
                                        document.getElementById("delete-button").disabled = true;
                                        instance.requestPaymentMethod(function (err, payload) {
                                            if(err){
                                                alert("There was an error with the request");
                                                document.getElementById("submit-button").disabled = false;
                                                document.getElementById("delete-button").disabled = false;
                                                exit;
                                            }

                                            $.post(
                                                "'.$callbackURL.'",
                                                {
                                                    braintree_action: "DeletePaymentMethod",
                                                    profile_id: "'.$customerProfile['profile_id'].'",
                                                    payload_nonce: payload.nonce
                                                },
                                                function(result) {
                                                    var response = JSON.parse(result); 
                                                    if (response.success) {
                                                        //reload
                                                        var elem = document.getElementById("dropin-container");
                                                        elem.innerHTML = "";
                                                        braintreeDropinCreate();
                                                    } else {
                                                        //alert(response.message);
                                                        document.getElementById("submit-button").disabled = false;
                                                        document.getElementById("delete-button").disabled = false;
                                                    }
                                                }
                                            );
                                        });
                                    });';
            }

            $html .= '
                                });
                            }

                            braintreeDropinCreate();
                        </script>
                    </body>
                </html>';
            return $html;
        } catch (Exception $e) {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                        <title>Untitled Document</title>
                    </head>
                    <body>'.$e->getMessage().'</body>
                </html>';
        }
    }
}

?>
