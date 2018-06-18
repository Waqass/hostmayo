<?php
/*****************************************************************************************************/
/*  CALL BACK CODE FROM AUTHORIZE.NET : This code needs to be called from your processor upon completion */
/*****************************************************************************************************/
// $cPlugin->GetPluginVariable takes queries in the form of plugin_[pluginname]_[variable]

require_once 'modules/billing/models/class.gateway.plugin.php';
//Create plug in class to interact with CE
$cPlugin = new Plugin($params["invoiceNumber"], "eprocessingnetwork", $this->user);

//create transaction code
if ($authnet_results["x_response_code"]==1) $responsetext = "This $transType has been approved";
elseif ($authnet_results["x_response_code"]==2) $responsetext = "This $transType has been declined";
elseif ($authnet_results["x_response_code"]==3) $responsetext = "There has been an error processing this $transType";
if ($authnet_results["x_response_reason_text"]==""){ $transaction = "Transaction (0): No response string returned"; }
else{    $transaction = " ".str_replace('transaction', $transType, $authnet_results["x_response_reason_text"])." (AN Trans:".$authnet_results["x_trans_id"].")"; }
$transactioncode=$transaction;

$cPlugin->setAmount($authnet_results["x_amount"]);
$cPlugin->m_TransactionID = $authnet_results["x_trans_id"];
$cPlugin->m_Action = ($transType == 'credit')? 'refund' : $transType;
$cPlugin->m_Last4 = mb_substr($authnet["cardnum"],-4);

//check to see if transaction was accepted
if ($authnet_results["x_response_code"]==1){
    $cPlugin->PaymentAccepted($authnet_results["x_amount"],$transactioncode,0);
}else if(!$bolInSignup){
    //Only add transaction if we are NOT coming from signup
    $cPlugin->PaymentRejected($transactioncode);
}
?>
