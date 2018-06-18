<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'plugins/gateways/protxform/functions.php';

class PluginProtxformCallback extends PluginCallback
{
    
    function processCallback()
    {
        // ignore the request
        if (!isset($_REQUEST['crypt'])) {
            return;
        }
        
        $crypt = $_REQUEST['crypt'];
        $cPlugin = new Plugin("0", 'protxform', $this->user);
        $Decrypt_Password = $cPlugin->GetPluginVariable("plugin_protxform_Crypt Password");
        
        $Decoded = SimpleXor(base64Decode($crypt),$Decrypt_Password);
        $values = getToken($Decoded);
        
        $lInvoiceID = $values['VendorTxCode'];
        $lInvoiceID = mb_substr($lInvoiceID, 0, strpos($lInvoiceID, "D"));
        $lErrorCode = "";  //For Future Use
        $lPricePaid = $values['Amount'];  //For Future Use
        
        $cPlugin = new Plugin($lInvoiceID, 'protxform', $this->user);
        $cPlugin->setAmount($lPricePaid);
        $cPlugin->setAction('charge');
        
        if(isset($_GET['fail']) && $_GET['fail'] == 1){
            // Failed payment
            $cPlugin->PaymentRejected($lErrorCode);
        }elseif(isset($_GET['success']) && $_GET['success'] == 1){
            // Passed payment
            $cPlugin->PaymentAccepted($lPricePaid);
        }
        
        //users coming from protx need to be redirected to invoicing
        $cPlugin->ForwardUser();
    }
}

?>
