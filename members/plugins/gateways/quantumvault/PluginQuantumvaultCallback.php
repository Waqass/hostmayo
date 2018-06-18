<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'library/CE/NE_Network.php';
require_once 'library/CE/NE_PluginCollection.php';

class PluginQuantumvaultCallback extends PluginCallback
{

    function processCallback()
    {
        // ignore blank browser requests
        if (!isset($_GET['invoice_num']) || !isset($_GET['transID'])) {
            return;
        }

        $cPlugin = new Plugin($_GET['invoice_num'], "quantumvault", $this->user);
        $cPlugin->setTransactionID($_GET['transID']);
        $cPlugin->setAmount($_GET['amount']);
        $cPlugin->setAction('charge');

        // Verification step to ensure transaction was approved.
        $params = array(
                          "TransactionID"                                           =>  $_GET['transID'],
                          "plugin_quantumvault_Quantum Vault Gateway RestrictKey"   =>  $this->settings->get('plugin_quantumvault_Quantum Vault Gateway RestrictKey'),
                          "plugin_quantumvault_Quantum Vault Gateway Username"      =>  $this->settings->get('plugin_quantumvault_Quantum Vault Gateway Username'),
                       );

        $pluginCollection = new NE_PluginCollection('gateways', $this->user);
        $response = $pluginCollection->callFunction('quantumvault', 'ShowTransactionDetails', $params);

        if($response && isset($response['QGWRequest']['#']['ResponseSummary'][0]['#']['Status'][0]['#'])){
            if(!strcasecmp($response['QGWRequest']['#']['ResponseSummary'][0]['#']['Status'][0]['#'], 'Success')){
                CE_Lib::log(4, "Callback has been verified successfully");

                if(!preg_match('/'.$_GET['invoice_num'].'/', $response['QGWRequest']['#']['Result'][0]['#']['InvoiceDescription'][0]['#'])) {
                    $cPlugin->PaymentRejected("The invoice number does not correspond to the one in the transaction.");
                }elseif($response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'] != $_GET['amount']) {
                    $cPlugin->PaymentRejected("The paid amount does not correspond to the one in the transaction.");
                }elseif(!strcasecmp($response['QGWRequest']['#']['Result'][0]['#']['Status'][0]['#'], 'APPROVED')){
                    $cPlugin->setLast4($response['QGWRequest']['#']['Result'][0]['#']['CreditCardNumber'][0]['#']);

                    $cPlugin->PaymentAccepted($_GET['amount'], "Quantum Vault Gateway payment of {$_GET['amount']} was accepted.", $_GET['transID']);
                }else{
                    $cPlugin->PaymentRejected("Quantum Vault Gateway payment of {$_GET['amount']} was rejected.");
                }
            }else{
                CE_Lib::log(4, $response['QGWRequest']['#']['ResponseSummary'][0]['#']['StatusDescription'][0]['#']);
                $cPlugin->PaymentRejected($response['QGWRequest']['#']['ResponseSummary'][0]['#']['StatusDescription'][0]['#']);
            }
        }else{
            CE_Lib::log(4, "Callback verification failed");
            $cPlugin->PaymentRejected('Callback verification failed');
        }
        $cPlugin->ForwardUser(1) ;
    }
}

?>
