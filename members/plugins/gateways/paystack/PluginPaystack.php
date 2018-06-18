<?php

require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginPaystack extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array(
                'type'        => 'hidden',
                'description' => lang('How CE sees this plugin ( not to be confused with the Signup Name )'),
                'value'       => 'Paystack (Debit/Credit Cards)'
            ),
            lang('Test Mode') => array(
                'type'        => 'yesno',
                'description' => lang('Enable this option to enable Test Mode'),
                'value'       => ''
            ),
            lang('Live Secret Key') => array(
                'type'        => 'text',
                'description' => lang('Enter your Live Secret Key here'),
                'value'       => ''
            ),
            lang('Live Public Key') => array(
                'type'        => 'text',
                'description' => lang('Enter your Live Public Key here'),
                'value'       => ''
            ),
            lang('Test Secret Key') => array(
                'type'        => 'text',
                'description' => lang('Enter your Test Secret Key here'),
                'value'       => ''
            ),
            lang('Test Public Key') => array(
                'type'        => 'text',
                'description' => lang('Enter your Test Public Key here'),
                'value'       => ''
            ),
            lang('Invoice After Signup') => array(
                'type'        => 'yesno',
                'description' => lang('Select YES if you want an invoice sent to the customer after signup is complete.'),
                'value'       => '1'
            ),
            lang('Signup Name') => array(
                'type'        => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'       => 'Paystack (Debit/Credit Cards)'
            ),
            lang('Dummy Plugin') => array(
                'type'        => 'hidden',
                'description' => lang('1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions'),
                'value'       => '0'
            ),
            lang('Auto Payment') => array(
                'type'        => 'hidden',
                'description' => lang('No description'),
                'value'       => '0'
            ),
            lang('CC Stored Outside') => array(
                'type'        => 'hidden',
                'description' => lang('Is Credit Card stored outside of Clientexec? 1 = YES, 0 = NO'),
                'value'       => '1'
            ),
            lang('Form') => array(
                'type'        => 'hidden',
                'description' => lang('Has a form to be loaded?  1 = YES, 0 = NO'),
                'value'       => '1'
            )
        );
        return $variables;
    }

    function credit($params)
    {
    }

    function singlepayment($params)
    {
        $transactionId = $params['plugincustomfields']['paystack_transaction_id'];
        $invoiceId = $params['invoiceNumber'];
        $pricePaid = $params['invoiceTotal'];

        if ($this->getVariable('Test Mode') == '1') {
            $privateKey = $this->getVariable('Test Secret Key');
        } else {
            $privateKey = $this->getVariable('Live Secret Key');
        }

        try {
            $paystack = new Yabacon\Paystack($privateKey);
            $response = $paystack->transaction->verify(['reference' => $transactionId]);
        } catch(Exception $e) {
            CE_Lib::log(1, $e->getMessage());
            return $this->user->lang("There was an error with PayStack.")." ".$e->getMessage();
        }

        $cPlugin = new Plugin($invoiceId, 'paystack', $this->user);
        $cPlugin->m_TransactionID = $transactionId;
        $cPlugin->setAction('charge');
        $cPlugin->setAmount($pricePaid);
        if ($response->data->status == 'success') {
            $success = true;
            $transaction = "Paystack Payment of {$pricePaid} was accepted";
            $cPlugin->PaymentAccepted($pricePaid, $transaction);
        } else {
            $success = false;
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

    public function getForm($args)
    {
        $this->view->amount = sprintf("%01.2f", round($args['invoiceBalanceDue'], 2)) * 100;
        $this->view->from = $args['from'];

        if ($this->getVariable('Test Mode') == '1') {
            $this->view->publicKey = $this->getVariable('Test Public Key');
        } else {
            $this->view->publicKey = $this->getVariable('Live Public Key');
        }
        return $this->view->render('form.phtml');
    }
}
