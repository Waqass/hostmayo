<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginChronopayCallback extends PluginCallback
{
    
    function processCallback()
    {
        if (isset($_GET['ipn']) && $_GET['ipn'] == 1) {
            // IPN Callback
            
            //Get vars from post/get
            $lTransType     = trim( stripslashes($_POST['transaction_type']) );
            $lTransId       = trim( stripslashes($_POST['transaction_id']) );
            $lCustId        = trim( stripslashes($_POST['customer_id']) );
            $lSiteId        = trim( stripslashes($_POST['site_id']) );
            $iProductId     = trim( stripslashes($_POST['product_id']) );
            $lTransDate     = trim( stripslashes($_POST['date']) );
            $lTransTime     = trim( stripslashes($_POST['time']) );
            $lTransName     = trim( stripslashes($_POST['name']) );
            $lTransEmail    = trim( stripslashes($_POST['email']) );
            $lTotal		 	= trim( stripslashes($_POST['total']) );
            $lInvoiceID	    = trim( stripslashes($_POST['cs1']) );
            $lSignUp        = trim( stripslashes($_POST['cs2']) );
            $lThing         = trim( stripslashes($_POST['cs3']) );
            $lPricePaid	    = trim( stripslashes($_POST['total']) );
            
            CE_Lib::log(4, 'ChronoPay callback invoked');
            $str = "Request from: ".$_SERVER['REMOTE_ADDR']."\n";
            $str .= "POST Vars: \n";
            foreach( $_POST as $k=>$v )
            	$str .= "   $k => $v \n";
            CE_Lib::log(4, $str);
            
            $cPlugin = new Plugin($lInvoiceID, 'chronopay', $this->user);
            
            $transLog = '<br/>Transaction ID: '.$lTransId."<br/>".
            				'Customer ID: '.$lCustId."<br/>".
            				'Customer Email: '.$lTransEmail."<br/>".
            				'Chronopay Date: '.$lTransDate."<br/>".
            				'Chronopay Time: '.$lTransTime;		
            
            $cPlugin->m_TransactionID = $lTransId;
            $cPlugin->setAmount($lPricePaid);
            $cPlugin->setAction('charge');
            
            //Determine if payment was made
            if ( $lTransType == 'onetime' || $lTransType == 'initial' || $lTransType == 'rebill' ) {
            	$transaction = " ChronoPay Payment of $lPricePaid was accepted \n".$transLog;
            	$cPlugin->PaymentAccepted($lPricePaid, $transaction);
            } else {
            	//transaction failed
            	$transaction = "ChronoPay Payment rejected\n ".$transLog;
            	$cPlugin->PaymentRejected($transaction);
            }
        } else {
            // Customer Callback
            
            $lInvoiceID = trim( stripslashes($_POST['cs1']) );
            $lSignUp = trim( stripslashes($_POST['cs2']) );
            
            //Create plug in class to interact with CE
            $cPlugin = new Plugin($lInvoiceID, 'chronopay', $this->user);
            
            if ($cPlugin->IsPaid() == 1) {
            	$cPlugin->m_PaymentAccepted = 1;
            }
            
            $cPlugin->ForwardUser($lSignUp);
        }
        
    }
}

?>
