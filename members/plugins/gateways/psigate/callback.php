<?php

require_once 'modules/billing/models/class.gateway.plugin.php';

$cPlugin = new Plugin($params["invoiceNumber"],"psigate", $this->user);

//create transaction code
if ($psi->getTrxnApproved()=="APPROVED") $responsetext = "This $transType has been approved";
elseif ($psi->getTrxnApproved()=="DECLINED") $responsetext = "This $transType has been declined";
else $responsetext = "There has been an error processing this $transType";

if (($psi->getTrxnApproved()=="APPROVED" || $psi->getTrxnApproved()=="DECLINED")) {
    $transaction = $responsetext.". ".$psi->getErrorMessage()." - (PSI Trans:".$psi->getTrxnTransRefNumber()." | Order ID:".$psi->getTrxnOrderID().")";
} else {
    $transaction = $responsetext.". PSI Message: ".$psi->getErrorMessage()." (PSI Trans: 0)";
}
$transactioncode=$transaction;

$cPlugin->setAmount($params['invoiceTotal']);

if($psi->getTrxnTransRefNumber()){
    $OrderString = $psi->getTrxnTransRefNumber();
}else{
    $OrderString = "0";
}
$OrderString .= " | ";
if($psi->getTrxnOrderID()){
    $OrderString .= $psi->getTrxnOrderID();
}else{
    $OrderString .= "0";
}


$cPlugin->m_TransactionID = $OrderString;
$cPlugin->m_Action = ($transType == 'credit')? 'refund' : $transType;
$cPlugin->m_Last4 = mb_substr($params["userCCNumber"],-4);

//check to see if transaction was accepted
if ($psi->getTrxnApproved()=="APPROVED"){
    $cPlugin->PaymentAccepted($params['invoiceTotal'],$transactioncode);
}else if(!$bolInSignup){
    //Only add transaction if we are NOT coming from signup
    $cPlugin->PaymentRejected($transactioncode);
}
