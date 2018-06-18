<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginCcavenueoldCallback extends PluginCallback
{
    
    function processCallback()
    {
        // ignore blank browser requests
        if (!isset($_POST['Order_Id'])) {
            return;
        }
        
        $Order_Id = $_POST['Order_Id'];
        
	/** 
	 * The OderId here will contain both invoiceid and the timestamp we appended. So remove the timestamp to get the InvoiceId of CE
         * We must keep the OrderId we got as we need to verify the checksum
	 */
        list($Invoice_Id,$timestamp) = explode("_", $Order_Id);

        //$cPlugin = new Plugin($Order_Id, "ccavenueold", $this->user);
        
        /**
         * We must pass the invoiceId as the Order_Id to CE to process the invoice. 
         */
        $cPlugin = new Plugin($Invoice_Id, "ccavenueold", $this->user);

        $WorkingKey = $cPlugin->GetPluginVariable("plugin_ccavenueold_Working Key"); //put in the 32 bit working key in the quotes provided here
        $Amount = $_POST['Amount'];
        $AuthDesc = $_POST['AuthDesc'];
        $Checksum  = $_POST['Checksum'];
        $Merchant_Id = $_POST['Merchant_Id'];
        $signup = $_POST['Merchant_Param'];
        
        include_once("libfuncs.php");
        $Checksum = verifyChecksum($Merchant_Id, $Order_Id , $Amount,$AuthDesc,$Checksum,$WorkingKey);

        $cPlugin->m_TransactionID = $Order_Id;
        $cPlugin->setAmount($Amount);
        $cPlugin->setAction('charge');
        
        if ($Checksum=="true" && $AuthDesc=="Y") {
            $transaction = " CCAvenue Payment of $Amount was accepted";
            $cPlugin->PaymentAccepted($Amount);
            //Here you need to put in the routines for a successful
            //transaction such as sending an email to customer,
            //setting database status, informing logistics etc etc
            if ($signup) {
                $cPlugin->ForwardUser(1);
                exit;
            } else {
                $cPlugin->ForwardUser(0);
                exit;
            }
        } else if($Checksum=="true" && $AuthDesc=="B") {
             $transaction = " CCAvenue Payment of $Amount was accepted.";
             $cPlugin->PaymentAccepted($Amount, $transaction);
            //echo "<br>Thank you for shopping with us.We will keep you posted regarding the status of your order through e-mail";
            //Here you need to put in the routines/e-mail for a  "Batch Processing" order
            //This is only if payment for this transaction has been made by an American Express Card
            //since American Express authorisation status is available only after 5-6 hours by mail from ccavenue and at the "View Pending Orders"
            if ($signup) {
                $cPlugin->ForwardUser(1);
                exit;
            } else {
                $cPlugin->ForwardUser(0);
                exit;
            }
        } else if($Checksum=="true" && $AuthDesc=="N") {
            $transaction = " CCAvenue Payment of $Amount was rejected.";
            $cPlugin->PaymentRejected($transaction);
            //Here you need to put in the routines for a failed
            //transaction such as sending an email to customer
            //setting database status etc etc
            if ($signup) {
                $cPlugin->ForwardUser(1);
                exit;
            } else {
                $cPlugin->ForwardUser(0);
                exit;
            }
        } else {
            echo "here: you shouldn't be here";
            //echo "<br>Security Error. Illegal access detected";
            //Here you need to simply ignore this and dont need
            //to perform any operation in this condition
        }
    }
}

?>
