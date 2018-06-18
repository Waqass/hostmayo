<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

use Razorpay\Api\Api;

/**
* @package Plugins
*/
class PluginRazorpay extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
            lang("Plugin Name") => array(
                "type"        => "hidden",
                "description" => lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                "value"       => lang("Razorpay")
            ),
            lang("Key Id") => array(
                "type"        => "text",
                "description" => lang("Razorpay Key Id.<br><b>NOTE:</b> This Key Id is required if you have selected Razorpay as a payment gateway for any of your clients.<br>You can get it from <a href='https://dashboard.razorpay.com/#/app/keys' target='_blank'>here</a>"),
                "value"       => ""
            ),
            lang("Key Secret") => array(
                "type"        => "password",
                "description" => lang("Razorpay Key Secret shared during activation API Key.<br><b>NOTE:</b> This Key Secret is required if you have selected Razorpay as a payment gateway for any of your clients.<br>You can get it from <a href='https://dashboard.razorpay.com/#/app/keys' target='_blank'>here</a>"),
                "value"       => ""
            ),
            lang("Invoice After Signup") => array(
                "type"        => "yesno",
                "description" => lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                "value"       => "1"
            ),
            lang("Signup Name") => array(
                "type"        => "text",
                "description" => lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                "value"       => "Razorpay"
            ),
            lang('Razorpay Logo Image URL') => array (
                'type'        => 'text',
                'description' => lang('A relative or absolute URL pointing to a square image of your brand or product.</br>The recommended minimum size is 128x128px.</br>The recommended image types are .gif, .jpeg, and .png.</br>Leave this field empty to use the default image.'),
                'value'       => ''
            ),
            lang("Dummy Plugin") => array(
                "type"        => "hidden",
                "description" => lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                "value"       => "0"
            ),
            lang("Auto Payment") => array(
                "type"        => "hidden",
                "description" => lang("No description"),
                "value"       => "0"
            ),
            lang('Form') => array (
                'type'        => 'hidden',
                'description' => lang('Has a form to be loaded?  1 = YES, 0 = NO'),
                'value'       => '1'
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
        return $this->singlepayment($params);
    }

    function singlepayment($params)
    {
        $keyId = $params["plugin_razorpay_Key Id"];
        $keySecret = $params["plugin_razorpay_Key Secret"];
        $invoice_id = $params['invoiceNumber'];
        try {
            $api = new Api($keyId, $keySecret);
        } catch (Exception $e) {
            return $this->user->lang("There was an error with Razorpay.")." ".$e->getMessage();
        }

        if (isset($params['refund']) && $params['refund']) {
            $cPlugin = new Plugin($params['invoiceNumber'], 'razorpay', $this->user);
            $cPlugin->setAction('refund');

            try {
                $refundData = array(
                    'payment_id' => $params["invoiceRefundTransactionId"]
                );
                $razorpayRefund = $api->refund->create($refundData); // Creates refund for a payment
                $razorpayRefundId = $razorpayRefund['id'];

                $refund = $api->refund->fetch($razorpayRefundId); // Returns a particular refund
                $priceRefunded = sprintf("%01.2f", round(($refund->amount / 100), 2)); // paise in rupees
            } catch (Exception $e) {
                $error = 'Razorpay Error : ' . $e->getMessage();
                $transaction = "Refund rejected - Reason: ".$error;
                $cPlugin->PaymentRejected($transaction, false);
                return $transaction;
            }

            $cPlugin->m_TransactionID = $razorpayRefundId;
            $cPlugin->setAmount($priceRefunded);
            $cPlugin->PaymentAccepted($priceRefunded, "Razorpay refund of {$priceRefunded} was successfully processed.", $razorpayRefundId);
            return array('AMOUNT' => $priceRefunded);
        } else {
            $success = true;

            $error = "Payment Failed";
            $razorpay_payment_id = $params['plugincustomfields']['razorpay_payment_id'];
            $razorpay_signature = $params['plugincustomfields']['razorpay_signature'];

            if (empty($razorpay_payment_id) === false) {
                $keyId = $this->settings->get("plugin_razorpay_Key Id");
                $keySecret = $this->settings->get("plugin_razorpay_Key Secret");
                try {
                    $api = new Api($keyId, $keySecret);
                } catch (Exception $e) {
                    return $this->user->lang("There was an error with Razorpay.")." ".$e->getMessage();
                }

                //Recover the razorpay_order_id so that we can verify the callback
                $invoice = new Invoice($invoice_id);
                if ($params['isSignup'] == 1) {
                    $razorpayOrderId = $invoice->m_ProcessorID;

                    if ($razorpayOrderId != '') {
                        try {
                            // Please note that the razorpay order ID must come from a trusted source
                            // (could be database or something else)
                            $attributes = array(
                                'razorpay_order_id'   => $razorpayOrderId,
                                'razorpay_payment_id' => $razorpay_payment_id,
                                'razorpay_signature'  => $razorpay_signature
                            );

                            $api->utility->verifyPaymentSignature($attributes);
                        } catch (SignatureVerificationError $e) {
                            $success = false;
                            $error = 'Razorpay Error : ' . $e->getMessage();
                        }
                    }
                }
            } else {
                $success = false;
            }

            $cPlugin = new Plugin($invoice_id, 'razorpay', $this->user);
            $cPlugin->m_TransactionID = $razorpay_payment_id;
            $cPlugin->setAction('charge');

            if ($success === true) {
                try {
                    $payment = $api->payment->fetch($razorpay_payment_id); // Returns a particular payment
                    $pricePaid = sprintf("%01.2f", round(($payment->amount / 100), 2)); // paise in rupees
                } catch (Exception $e) {
                    $pricePaid = $params['invoiceTotal'];
                }

                $cPlugin->setAmount($pricePaid);

                $transaction = "Razorpay Payment of {$pricePaid} was accepted";
                $cPlugin->PaymentAccepted($pricePaid, $transaction);
            } else {
                $transaction = "Payment rejected - Reason: ".$error;
                $cPlugin->PaymentRejected($transaction, false);
            }

            //Need to check to see if user is coming from signup
            if ($params['isSignup'] == 1) {
                if ($this->settings->get('Signup Completion URL') != '') {
                    if ($success === true) {
                        $returnURL = $this->settings->get('Signup Completion URL').'?success=1';
                    } else {
                        $returnURL = $this->settings->get('Signup Completion URL');
                    }
                } else {
                    if ($success === true) {
                        $returnURL = CE_Lib::getSoftwareURL()."/order.php?step=complete&pass=1";
                    } else {
                        $returnURL = CE_Lib::getSoftwareURL()."/order.php?step=3";
                    }
                }
            } else {
                if ($success === true) {
                    $returnURL = CE_Lib::getSoftwareURL()."/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=".$invoice_id;
                } else {
                    $returnURL = CE_Lib::getSoftwareURL()."/index.php?fuse=billing&cancel=1&controller=invoice&view=invoice&id=".$invoice_id;
                }
            }

            header("Location: " . $returnURL);
        }
    }

    public function getForm($args)
    {
        if ( $args['from'] == 'paymentmethod' ) {
            return '';
        }
        $keyId = $this->getVariable('Key Id');
        $keySecret = $this->getVariable('Key Secret');
        try {
            $api = new Api($keyId, $keySecret);

            if ($args['from'] != 'signup') {
                // We create a razorpay order using orders api
                // Docs: https://docs.razorpay.com/docs/orders
                $orderData = array(
                    'receipt'         => $args['invoiceId'],
                    'amount'          => sprintf("%01.2f", round($args['invoiceBalanceDue'], 2)) * 100, // rupees in paise
                    'currency'        => 'INR',
                    'payment_capture' => 1 // auto capture
                );
                $razorpayOrder = $api->order->create($orderData);
                $razorpayOrderId = $razorpayOrder['id'];

                //Save the razorpay_order_id so that we can verify the callback
                $invoice = new Invoice($args['invoiceId']);
                $invoice->SetProcessorID($razorpayOrderId);

                $amount = $orderData['amount'];
            }
        } catch (Exception $e) {
            return $this->user->lang("There was an error with Razorpay.")." ".$e->getMessage();
        }

        $this->view->invoiceId = $args['invoiceId'];
        $this->view->keyId = $keyId;
        if ($args['from'] != 'signup') {
            $this->view->amount = $amount;
        }
        $this->view->logoImage = $this->getVariable('Razorpay Logo Image URL');
        if ($this->view->logoImage == '') {
            $SoftwareURL = mb_substr(CE_Lib::getSoftwareURL(), -1, 1) == "//" ? CE_Lib::getSoftwareURL() : CE_Lib::getSoftwareURL()."/";
            $this->view->logoImage = $SoftwareURL.'plugins/gateways/razorpay/logo.png';
        }
        $this->view->companyName = $this->settings->get("Company Name");
        if ($args['from'] != 'signup') {
            $this->view->razorpayOrderId = $razorpayOrderId;
        }

        $this->view->from = $args['from'];
        $this->view->termsConditions = $args['termsConditions'];

        return $this->view->render('form.phtml');
    }
}
