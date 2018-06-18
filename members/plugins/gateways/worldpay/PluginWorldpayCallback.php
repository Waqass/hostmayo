<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginWorldpayCallback extends PluginCallback
{
    
    function processCallback()
    {
        // ignore browser GET requests
        if (!isset($_POST['cartId'])) {
            return;
        }
        //Get vars from post/get
        $tInvoiceID = $_POST['cartId'];
        $tTransStatus = $_POST['transStatus'];
        $tTransID = $_POST['transId'];
        $tCallBackPW = $_POST['callbackPW'];
        $lPricePaid = $_POST['cost'];  //For Future Use
        $testmode = "";
        //Create plug in class to interact with CE
        $cPlugin = new Plugin($tInvoiceID, 'worldpay', $this->user);
        $cPlugin->setAmount($lPricePaid);
        $cPlugin->setAction('charge');
        
        //Determine if payment was made
        if ($tTransStatus == "Y") {
            if ($_POST['testMode'] == 100) {
                $testmode = "Test ";
            }
            $transaction = " Worldpay $testPayment of $lPricePaid Successful (Worldpay ID:".$tTransID.")";
            if (trim($tCallBackPW) != trim($cPlugin->GetPluginVariable("plugin_worldpay_Callback Password"))){
                exit;
                //might want to send a warning email in the future for security warning
                //  $cPlugin->PaymentRejected($lErrorCode);
            } else {
                $cPlugin->PaymentAccepted($lPricePaid,$transaction);
            }
        } else {
            $transaction = " Worldpay $testPayment of $lPricePaid Failed (Worldpay ID:".$tTransID.")";
            $cPlugin->PaymentRejected($transaction);
        }
    }
}

?>
