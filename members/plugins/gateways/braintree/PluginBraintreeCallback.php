<?php
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/admin/models/PluginCallback.php';

class PluginBraintreeCallback extends PluginCallback
{
    function processCallback()
    {
        if (isset($_POST['braintree_action']) && !empty($_POST['braintree_action'])) {
            $braintree_action = $_POST['braintree_action'];

            switch ($braintree_action) {
                case 'DefaultPaymentMethod':
                case 'PayInvoice':
                    try {
                        Braintree_Configuration::publicKey($this->settings->get("plugin_braintree_Public Key"));
                        Braintree_Configuration::privateKey($this->settings->get("plugin_braintree_Private Key"));
                        Braintree_Configuration::merchantId($this->settings->get("plugin_braintree_Merchant ID"));
                        Braintree_Configuration::environment($this->settings->get("plugin_braintree_Environment"));

                        $PaymentMethodCreate = Braintree_PaymentMethod::create([
                            'customerId'         => $_POST['profile_id'],
                            'paymentMethodNonce' => $_POST['payload_nonce'],
                            'options'            => [
                                'makeDefault'                  => true,
                                'failOnDuplicatePaymentMethod' => true
                            ]
                        ]);

                        if ($PaymentMethodCreate->success) {
                            if ($braintree_action == 'PayInvoice') {
                                try {
                                    $invoice_id = $_POST['invoice_id'];
                                    $cPlugin = new Plugin($invoice_id, 'braintree', $this->user);
                                    $cPlugin->setAmount($_POST['invoice_total']);
                                    $cPlugin->setAction('charge');

                                    $sale = array(
                                        'customerId' => $_POST['profile_id'],
                                        'amount'     => sprintf("%01.2f", round($_POST['invoice_total'], 2)),
                                        'orderId'    => $invoice_id,  // This field is get back in responce to track this transaction
                                        'options'    => array(
                                            'submitForSettlement' => true
                                        )
                                    );
                                    $result = Braintree_Transaction::sale($sale);

                                    if ($result->success) {
                                        $cPlugin->m_TransactionID = $result->transaction->id;
                                        $transaction = "Braintree Payment of {$_POST['invoice_total']} was accepted";
                                        $cPlugin->PaymentAccepted($_POST['invoice_total'], $transaction);
                                        echo json_encode(
                                            array(
                                                "success" => true,
                                                "message" => $this->user->lang('Braintree Payment was accepted')
                                            )
                                        );
                                        return;
                                    } else {
                                        $transaction = "Payment rejected - Reason: ".$result->message;
                                        $cPlugin->PaymentRejected($transaction, false);
                                        echo json_encode(
                                            array(
                                                "success" => false,
                                                "message" => $this->user->lang('There was an error performing this operation.')
                                            )
                                        );
                                        return;
                                    }
                                } catch (Exception $e) {
                                    echo json_encode(
                                        array(
                                            "success" => false,
                                            "message" => $e->getMessage()
                                        )
                                    );
                                    return;
                                }
                            } else {
                                echo json_encode(
                                    array(
                                        "success" => true,
                                        "message" => $this->user->lang('Payment method has been set as default')
                                    )
                                );
                                return;
                            }
                        } else {
                            echo json_encode(
                                array(
                                    "success" => false,
                                    "message" => $PaymentMethodCreate->message
                                )
                            );
                            return;
                        }
                    } catch (Exception $e) {
                        echo json_encode(
                            array(
                                "success" => false,
                                "message" => $e->getMessage()
                            )
                        );
                        return;
                    }
                    break;
                case 'DeletePaymentMethod':
                    try {
                        Braintree_Configuration::publicKey($this->settings->get("plugin_braintree_Public Key"));
                        Braintree_Configuration::privateKey($this->settings->get("plugin_braintree_Private Key"));
                        Braintree_Configuration::merchantId($this->settings->get("plugin_braintree_Merchant ID"));
                        Braintree_Configuration::environment($this->settings->get("plugin_braintree_Environment"));

                        $paymentMethodToken = '';
                        $customer = Braintree_Customer::find($_POST['profile_id']);
                        foreach ($customer->paymentMethods as $PaymentMethod) {
                            $result = Braintree_PaymentMethodNonce::create($PaymentMethod->token);
                            $nonce = $result->paymentMethodNonce->nonce;

                            if ($nonce === $_POST['payload_nonce']) {
                                $paymentMethodToken = $PaymentMethod->token;
                                break;
                            }
                        }

                        if ($paymentMethodToken !== '') {
                            $PaymentMethodDelete = Braintree_PaymentMethod::delete($paymentMethodToken);

                            if ($PaymentMethodDelete->success) {
                                echo json_encode(
                                    array(
                                        "success" => true,
                                        "message" => $this->user->lang('Payment method has been deleted')
                                    )
                                );
                                return;
                            } else {
                                echo json_encode(
                                    array(
                                        "success" => false,
                                        "message" => $PaymentMethodCreate->message
                                    )
                                );
                                return;
                            }
                        } else {
                            echo json_encode(
                                array(
                                    "success" => false,
                                    "message" => $this->user->lang('Payment method not found')
                                )
                            );
                            return;
                        }

                    } catch (Exception $e) {
                        echo json_encode(
                            array(
                                "success" => false,
                                "message" => $e->getMessage()
                            )
                        );
                        return;
                    }
                    break;
            }
        }
    }
}
?>
