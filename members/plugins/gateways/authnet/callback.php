<?php
/*****************************************************************************************************/
/*  CALL BACK CODE FROM AUTHORIZE.NET : This code needs to be called from your processor upon completion */
/*****************************************************************************************************/
// $cPlugin->GetPluginVariable takes queries in the form of plugin_[pluginname]_[variable]

//Normal plugin would include gateway class via the line below
//include_once("../../../modules/billing/models/class.gateway.plugin.php");
//calling from the plugin directory so I do not need to go up in dir

require_once 'modules/billing/models/class.gateway.plugin.php';
//Create plug in class to interact with CE
//Pass Plugin Name..  will be same name as folder under plugins/gateways
$cPlugin = new Plugin($params["invoiceNumber"],"authnet", $this->user);

//create transaction code
if ($authnet_results["x_response_code"]==1) $responsetext = "This $transType has been approved";
elseif ($authnet_results["x_response_code"]==2) $responsetext = "This $transType has been declined";
elseif ($authnet_results["x_response_code"]==3) $responsetext = "There has been an error processing this $transType";
elseif ($authnet_results["x_response_code"]==4) $responsetext = "This $transType is currently under review";
elseif ($authnet_results["x_response_code"]=="") $responsetext = "Server Configuration Error: ";

if ($authnet_results["x_response_reason_text"]==""){ 
    //Could not determine what the error was
    //Check to see if we populate strAuthConfigError
    //With a possible reason for the error
    if($strAuthConfigError!=""){ 
        $transaction = $responsetext." ".$strAuthConfigError; 
    }else{
        $transaction = "Transaction (0): No response string returned"; 
    }
}else{    
    $transaction = " ".str_replace('transaction', $transType, $authnet_results["x_response_reason_text"])." (AN Trans:".$authnet_results["x_trans_id"].")"; 
}

$transactioncode=$transaction;

//Pass to plugin the variables needed to enter a transaction for this action

$cPlugin->setAmount($authnet_results["x_amount"]);
$cPlugin->m_TransactionID = $authnet_results["x_trans_id"];
$cPlugin->m_Action = ($transType == 'credit')? 'refund' : $transType;
$cPlugin->m_Last4 = mb_substr($authnet["cardnum"],-4);

//check to see if transaction was accepted
if ($authnet_results["x_response_code"]==1){
    $cPlugin->PaymentAccepted($authnet_results["x_amount"],$transactioncode);
}else if ($authnet_results["x_response_code"]==4){
    $cPlugin->PaymentPending($transactioncode);
}else if(!$bolInSignup){
    //Only add transaction if we are NOT coming from signup
    $cPlugin->PaymentRejected($transactioncode);
}
?>
