<?php

require_once 'modules/billing/models/class.gateway.plugin.php';

$cPlugin = new Plugin($params["invoiceNumber"],"bluepay", $this->user);

//create transaction code
if ($bluePay->getStatus()=="1") $responsetext = "This transaction has been approved";
elseif ($bluePay->getStatus()=="0") $responsetext = "This transaction has been declined";
elseif ($bluePay->getStatus()=="E") $responsetext = "There has been an error processing this transaction";
elseif ($bluePay->getStatus()=="") $responsetext = "Server Configuration Error";

if (($bluePay->getStatus() == "1" || $bluePay->getStatus() == "0") && $bluePay->getTransId()) {
    $transaction = str_replace('transaction', $transType, $responsetext).". (BP Trans:".$bluePay->getTransId().")";
} else {
    $transaction = str_replace('transaction', $transType, $responsetext).". BP Message: ".$bluePay->getMessage()." (BP Trans: 0)";
}
$transactioncode=$transaction;

$cPlugin->setAmount($params['invoiceTotal']);
$cPlugin->m_Action = ($transType == 'credit')? 'refund' : $transType;
$cPlugin->m_Last4 = mb_substr($params["userCCNumber"],-4);

//check to see if transaction was accepted
if ($bluePay->getStatus()=="1"){
    $cPlugin->m_TransactionID = $bluePay->getTransId();
    $cPlugin->PaymentAccepted($params['invoiceTotal'],$transactioncode);
}else if(!$bolInSignup){
    //Only add transaction if we are NOT coming from signup
    $cPlugin->m_TransactionID = (is_null($bluePay->getTransId()))? '0' : $bluePay->getTransId();
    $cPlugin->PaymentRejected($transactioncode);
}
