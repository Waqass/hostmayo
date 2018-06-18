<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginInternetsecureCallback extends PluginCallback
{
    
    function processCallback()
    {
        // ignore browser requests
        if (!isset($_POST['DoubleColonProducts'])) {
            return;
        }
        //Get vars from post/get
        $tProduct = $_POST['DoubleColonProducts'];
        $aProducts = explode("::", $tProduct);
        $tInvoiceID = $aProducts[2];  // return of optional variables
        $tTransStatus = $_POST['Verbage'];
        $lPricePaid = $aProducts[0];  //For Future Use
        
        //Create plug in class to interact with CE
        $cPlugin = new Plugin($tInvoiceID, 'internetsecure', $this->user);
        
        //Determine if payment was made
        $cPlugin->setAmount($lPricePaid);
        $cPlugin->setAction('charge');
        $cPlugin->PaymentAccepted($lPricePaid);
    }
    
}

?>
