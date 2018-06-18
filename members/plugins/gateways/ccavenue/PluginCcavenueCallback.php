<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginCcavenueCallback extends PluginCallback
{
    
    function processCallback()
    {
        include("plugins/gateways/ccavenue/Crypto.php");
        $WorkingKey = $this->settings->get('plugin_ccavenue_Encryption Key');
        $encResponse = $_POST["encResp"];   //This is the response sent by the CCAvenue Server
        $rcvdString = decrypt($encResponse, $WorkingKey);		//Crypto Decryption used as per the specified working key.
        $decryptValues = explode('&', $rcvdString);
        $dataSize = sizeof($decryptValues);
        $arrayResponse = array();

        for($i = 0; $i < $dataSize; $i++){
            $information = explode('=', $decryptValues[$i]);
            $arrayResponse[$information[0]] = $information[1];
        }


        // ignore blank browser requests
        if(!isset($arrayResponse['order_id'])){
            return;
        }

        $Order_Id = $arrayResponse['order_id'];

        /**
          * The OderId here will contain both invoiceid and the timestamp we appended. So remove the timestamp to get the InvoiceId of CE
          * We must keep the OrderId we got as we need to verify the checksum
        */
        list($Invoice_Id,$timestamp) = explode("_", $Order_Id);

        /**
         * We must pass the invoiceId as the Order_Id to CE to process the invoice. 
         */
        $cPlugin = new Plugin($Invoice_Id, "ccavenue", $this->user);

        $Amount = $arrayResponse['amount'];
        $OrderStatus = $arrayResponse['order_status'];
        $signup = $arrayResponse['merchant_param1'];

        $cPlugin->m_TransactionID = $Order_Id;
        $cPlugin->setAmount($Amount);
        $cPlugin->setAction('charge');
        
        if($OrderStatus == "Success"){
            $transaction = " CCAvenue Payment of $Amount was accepted";
            $cPlugin->PaymentAccepted($Amount,$transaction,$Order_Id);
            //Here you need to put in the routines for a successful
            //transaction such as sending an email to customer,
            //setting database status, informing logistics etc etc
            if($signup){
                $cPlugin->ForwardUser(1);
                exit;
            }else{
                $cPlugin->ForwardUser(0);
                exit;
            }
        }elseif(in_array($OrderStatus, array('Failure', 'Aborted'))){
            $transaction = " CCAvenue Payment of $Amount was rejected.";
            $cPlugin->PaymentRejected($transaction);
            //Here you need to put in the routines for a failed
            //transaction such as sending an email to customer
            //setting database status etc etc
            if($signup){
                $cPlugin->ForwardUser(1);
                exit;
            }else{
                $cPlugin->ForwardUser(0);
                exit;
            }
        }else{
            echo "here: you shouldn't be here";
            //echo "<br>Security Error. Illegal access detected";
            //Here you need to simply ignore this and dont need
            //to perform any operation in this condition
        }
    }
}

?>
