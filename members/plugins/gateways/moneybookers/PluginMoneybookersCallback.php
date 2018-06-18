<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginMoneybookersCallback extends PluginCallback
{
    
    function processCallback()
    {
        // ignore get requests
        if (!isset($_POST['transaction_id']) && !isset($_POST['status'])) {
            return;
        }
        
        //Create plug in class to interact with CE
        $cPlugin = new Plugin($_POST['transaction_id'], 'moneybookers', $this->user);
        
        $hash = $_POST['merchant_id'].$_POST['transaction_id'].strtoupper(md5(trim($this->settings->get("plugin_moneybookers_Secret Word")))).$_POST['mb_amount'].$_POST['mb_currency'].$_POST['status'];
        $hash = strtoupper(trim(md5(trim($hash))));
        
        $cPlugin->m_TransactionID = $_POST['mb_transaction_id'];
        $cPlugin->m_Action = "charge";
        $cPlugin->setAmount((isset($_POST['amount']))? $_POST['amount'] : "0.0");
        
        if (strcasecmp(trim($_POST['md5sig']), $hash) == 0) {
            switch($_POST['status']){
                case 2: // PROCESSED
                    $responsetext = "This transaction has been approved.";
                    $cPlugin->PaymentAccepted($_POST['amount'], $responsetext." MB Trans ID(".$_POST['mb_transaction_id'].")", $_POST['mb_transaction_id']);
                    break;
                case 0: // PENDING
                    $responsetext = "This transaction is pending.";
                    $cPlugin->PaymentPending($responsetext." MB Trans ID(".$_POST['mb_transaction_id'].")", $_POST['mb_transaction_id']);
                    break;
                case -1: // CANCELLED
                    $responsetext = "This transaction has been cancelled.";
                    $cPlugin->PaymentRejected("Error: ".$responsetext." MB Trans ID(".$_POST['mb_transaction_id'].")");
                    break;
                case -2: // FAILED
                    $responsetext = "This transaction has failed.";
                    $cPlugin->PaymentRejected("Error: ".$responsetext." MB Trans ID(".$_POST['mb_transaction_id'].")");
                    break;
                case -3: // CHARGEBACK
                    // THIS CASE DOES NOT APPLY
                default:
                    $responsetext = "This transaction has been declined.";
                    $cPlugin->PaymentRejected("Error: ".$responsetext." MB Trans ID(".$_POST['mb_transaction_id'].")");
                    break;
            }
        } else {
            $responsetext = "This transaction has been declined.";
            $cPlugin->PaymentRejected("Error: ".$responsetext." MB Trans ID(".$_POST['mb_transaction_id'].")");
        }
    }
    
}

?>
