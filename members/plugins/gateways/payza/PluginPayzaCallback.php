<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
  
class PluginPayzaCallback extends PluginCallback
{
    function processCallback()
    {
        if (!isset($GLOBALS['testing'])) {
            $testing = false;
        } else {
            $testing = $GLOBALS['testing'];
        }
  
        CE_Lib::log(4, 'Payza callback invoked');
  
        if ($this->settings->get('plugin_payza_Use Test Mode') == '0' && isset($_POST['ap_test']) && $_POST['ap_test'] == '1')
        {
            CE_Lib::log(4, "** Payza test mode callback but account is in production mode => callback discarded");
            return;
        }
  
        if (!isset($_POST['ap_purchasetype'])) {
            CE_Lib::log(4, 'Payza callback ignored: ap_purchasetype is not defined');
            return;
        }
  
        if (!isset($_POST['ap_securitycode'])) {
            CE_Lib::log(4, 'Payza callback ignored: ap_securitycode is not defined');
            return;
        }
  
        $securitycode = $_POST['ap_securitycode'];
  
        if ($this->settings->get('plugin_payza_Security Code') != $securitycode)
        {
            CE_Lib::log(4, "** This is a fraud transaction and you must cancel this =>  callback discarded");
            return;
        }
  
        $apTransType = $_POST['ap_purchasetype'];
        $apTransID = @$_POST['ap_referencenumber']; // Transaction ID (Unique) (not defined for subscription cancellations)
        $apPayStatus = @$_POST['ap_status']; // Payment Status (not defined for subscription cancellations)
        $apPayAmount = @$_POST['ap_totalamount']; // Total paid for this transaction (not defined for subscription cancellations)
        $tInvoiceID = @$_POST['apc_1']; // This custom field corresponds to the invoiceID
        $tGenerateInvoice = @$_POST['apc_2']; // This custom field corresponds to the Generate Invoices After Callback Notification
  
        CE_Lib::log(4, "\$PayzaTransType: $apTransType; \$PayzaTransID: $apTransID; \$PayzaPayStatus: $apPayStatus;");
        CE_Lib::log(4, "\$PayzaPayAmount: $apPayAmount; \$tInvoiceID: $tInvoiceID; \$tIsRecurring: $tIsRecurring; \$tGenerateInvoice: $tGenerateInvoice");
  
         // Create Plugin class object to interact with CE.
        $cPlugin = new Plugin($tInvoiceID, 'payza', $this->user);
  
        //Add plugin details
        $cPlugin->setAmount($apPayAmount);
        $cPlugin->m_TransactionID = $apTransID;
        $cPlugin->m_Action = "charge";
        $cPlugin->m_Last4 = "NA";
  
        // Add Code for Normal Payment
        switch($apPayStatus) {
            case "Success":
                // Payment was succesful/completed.
                $transaction = "Payza payment of $apPayAmount was accepted. Original Signup Invoice: $tInvoiceID (OrderID:".$apTransID.")";
                $cPlugin->PaymentAccepted($apPayAmount,$transaction,$apTransID, $testing);
                break;
            default:
                $transaction = "Payza payment of $apPayAmount was rejected. Original Signup Invoice: $tInvoiceID (OrderID:".$apTransID.")";
                $cPlugin->PaymentRejected($transaction);
                break;
        }
    }
}
?>