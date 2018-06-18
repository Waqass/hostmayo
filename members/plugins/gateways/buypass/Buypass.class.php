<?php

class BuypassException extends Exception {}

class Buypass
{
    const USE_PRODUCTION_SERVER  = 0;
    const USE_DEVELOPMENT_SERVER = 1;

    const EXCEPTION_CURL = 10;

    private $params  = array();
    private $success = false;
    private $error   = true;

    private $UserID = '';
    private $GatewayID = '';
    private $xml = '';
    private $ch = '';
    private $response = '';
    private $url = '';
    private $requesturl = '';
    private $Status = '';
    private $Token = '';
    private $ResultMessage = '';
    private $ResultCode = '';
    private $ResponseCode = '';
    private $AuthIdentificationResponse = '';
    private $AdditionalResponseData = '';
    private $ReferenceNumber = '';
    private $TransactionAmount = '';
    private $TransactionDate = '';
    private $AvsResponse = '';
    private $CvvResponse = '';
    private $Receipt = '';
    private $XmlResults = '';

    public function __construct($UserID, $GatewayID, $LiveURL, $TestURL, $test = self::USE_PRODUCTION_SERVER)
    {
        $this->UserID    = trim($UserID);
        $this->GatewayID = trim($GatewayID);
        if(empty($this->UserID) || empty($this->GatewayID)){
            trigger_error('You have not configured your ' . __CLASS__ . '() login credentials properly.', E_USER_ERROR);
        }

        $this->test = (bool) $test;
        if($this->test){
            $this->url = $TestURL;
        }else{
            $this->url = $LiveURL;
        }

        // Application to Servlet Mapping
        /*
          * Card Charge Transactions        processpayment
            Void Transactions               processvoid
          * Refund Transactions             processrefund
            Auth Only Transactions          processauth
            Auth Cancel Transactions        processauthcancel
            Capture Only Transactions       processcaptureonly
          * Create Token                    createtoken
          * Delete Token                    deletetoken
            QueryToken                      querytoken
            Update Exp Date                 updateexpiration
            Update Token                    updatetoken
        */
    }

    public function __destruct()
    {
        if(isset($this->ch)){
            curl_close($this->ch);
        }
    }

    public function __toString()
    {
        if(!$this->params){
            return (string) $this;
        }
        $output  = '<table summary="Buypass Results" id="buypass" border="1" style="border-collapse: collapse">' . "\n";
        $output .= '<tr>' . "\n\t\t" . '<th colspan="2"><b>Outgoing Parameters</b></th>' . "\n" . '</tr>' . "\n";

        if(!empty($this->xml)){
            $output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>UserID</b></td>';
            $output .= '<td>' . $this->UserID . '</td>' . "\n" . '</tr>' . "\n";
        }

        if(!empty($this->GatewayID)){
            $output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>GatewayID</b></td>';
            $output .= '<td>' . $this->GatewayID . '</td>' . "\n" . '</tr>' . "\n";
        }

        foreach($this->params as $key => $value){
            $output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>' . $key . '</b></td>';
            $output .= '<td>' . $value . '</td>' . "\n" . '</tr>' . "\n";
        }

        if(!empty($this->url) || !empty($this->requesturl)){
            $output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>URL</b></td>';
            $output .= '<td>' . htmlentities($this->url.$this->requesturl) . '</td>' . "\n" . '</tr>' . "\n";
        }

        if(!empty($this->xml)){
            $output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>XML REQUEST</b></td>';
            $output .= '<td>' . nl2br(str_replace(array(' ', '&lt;', '&gt;'), array('&nbsp;', '</b>&lt;', '&gt;<b>'), htmlentities($this->xml))) . '</td>' . "\n" . '</tr>' . "\n";
        }

        if(!empty($this->response)){
            $output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>XML RESPONSE</b></td>';
            $output .= '<td>' . nl2br(str_replace(array(' ', '&gt;&lt;', '&lt;', '&gt;'), array('&nbsp;', '&gt;</br>&lt;', '</b>&lt;', '&gt;<b>'), htmlentities($this->response))) . '</td>' . "\n" . '</tr>' . "\n";
        }

        $output .= '</table>' . "\n";
        return $output;
    }

    private function process()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->url.$this->requesturl);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);

        $post = array(
            "param" => $this->xml
        );
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Content-type: multipart/form-data'
        ));

        $this->response = curl_exec($this->ch);
        if($this->response){
            $this->parseResults();
            if($this->Status === '0'){
                $this->success = true;
                $this->error   = false;
            }else{
                $this->success = false;
                $this->error   = true;
            }
            curl_close($this->ch);
            unset($this->ch);
        }else{
            throw new BuypassException('Connection error: ' . curl_error($this->ch) . ' (' . curl_errno($this->ch) . ')', self::EXCEPTION_CURL);
        }
    }

    public function createToken()
    {
        $this->requesturl = 'createtoken';
        $this->xml = '<Request>'."\n"
                    .'    <MerchantData>'."\n"
                    .'        <Platform>' . $this->params['Platform'] . '</Platform>'."\n"
                    .'        <UserId>' . $this->UserID . '</UserId>'."\n"
                    .'        <GID>' . $this->GatewayID . '</GID>'."\n"
                    .'        <Tid>' . @$this->params['Tid'] . '</Tid>'."\n"
                    .'    </MerchantData>'."\n"
                    .'    <CreateToken>'."\n"
                    .'        <AccountNumber>' . $this->params['AccountNumber'] . '</AccountNumber>'."\n"
                    .'        <ExpirationMonth>' . $this->params['ExpirationMonth'] . '</ExpirationMonth>'."\n"
                    .'        <ExpirationYear>' . $this->params['ExpirationYear'] . '</ExpirationYear>'."\n"
                    .'        <CardHolderFirstName>' . $this->params['CardHolderFirstName'] . '</CardHolderFirstName>'."\n"
                    .'        <CardHolderLastName>' . $this->params['CardHolderLastName'] . '</CardHolderLastName>'."\n"
                    .'        <AvsZip>' . $this->params['AvsZip'] . '</AvsZip>'."\n"
                    .'        <AvsStreet>' . $this->params['AvsStreet'] . '</AvsStreet>'."\n"
                    //.'        <CustomId>' . $this->params['CustomId'] . '</CustomId>'."\n"
                    .'        <ApplicationId>' . $this->params['ApplicationId'] . '</ApplicationId>'."\n"
                    .'        <Cf1>' . @$this->params['Cf1'] . '</Cf1>'."\n"
                    .'        <Cf2>' . @$this->params['Cf2'] . '</Cf2>'."\n"
                    .'        <Cf3>' . @$this->params['Cf3'] . '</Cf3>'."\n"
                    .'    </CreateToken>'."\n"
                    .'</Request>';
        $this->process();
    }

    public function deleteToken()
    {
        $this->requesturl = 'deletetoken';
        $this->xml = '<Request>'."\n"
                    .'    <MerchantData>'."\n"
                    .'        <Platform>' . $this->params['Platform'] . '</Platform>'."\n"
                    .'        <UserId>' . $this->UserID . '</UserId>'."\n"
                    .'        <GID>' . $this->GatewayID . '</GID>'."\n"
                    .'        <Tid>' . @$this->params['Tid'] . '</Tid>'."\n"
                    .'    </MerchantData>'."\n"
                    .'    <DeleteToken>'."\n"
                    .'        <Token>' . $this->params['Token'] . '</Token>'."\n"
                    .'    </DeleteToken>'."\n"
                    .'</Request>';
        $this->process();
    }

    public function processPayment()
    {
        $this->requesturl = 'processpayment';
        $this->xml = '<Request>'."\n"
                    .'    <MerchantData>'."\n"
                    .'        <Platform>' . $this->params['Platform'] . '</Platform>'."\n"
                    .'        <UserId>' . $this->UserID . '</UserId>'."\n"
                    .'        <GID>' . $this->GatewayID . '</GID>'."\n"
                    .'        <Tid>' . @$this->params['Tid'] . '</Tid>'."\n"
                    .'    </MerchantData>'."\n"
                    .'    <ProcessPayment>'."\n"
                    .'        <Amount>' . $this->params['Amount'] . '</Amount>'."\n"
                    .'        <Token>' . $this->params['Token'] . '</Token>'."\n"

                    //String identifying type of transaction being processed. This field is optional and used for transaction reporting. Max Size: 20
                    //.'        <TypeOfSale>' . $this->params['TypeOfSale'] . '</TypeOfSale>'."\n"

                    .'        <Cf1>' . @$this->params['Cf1'] . '</Cf1>'."\n"
                    .'        <Cf2>' . @$this->params['Cf2'] . '</Cf2>'."\n"
                    .'        <Cf3>' . @$this->params['Cf3'] . '</Cf3>'."\n"

                    //Required when specifying eCommerce and MOTO industry transactions. See section 3.2 for details.
                    //.'        <IndustryType>' . $this->params['IndustryType'] . '</IndustryType>'."\n"

                    .'        <ApplicationId>' . $this->params['ApplicationId'] . '</ApplicationId>'."\n"
                    .'        <Recurring>' . $this->params['Recurring'] . '</Recurring>'."\n"
                    .'    </ProcessPayment>'."\n"

                    //Required when processing Level II transactions. See section 3.3 for details.
                    //.'    <Level2PurchaseInfo>' . $this->params['Level2PurchaseInfo'] . '</Level2PurchaseInfo>'."\n"

                    //Required when processing Level III transactions. See section 3.4 for details.
                    //.'    <Level3PurchaseInfo>' . $this->params['Level3PurchaseInfo'] . '</Level3PurchaseInfo>'."\n"

                    .'</Request>';
        $this->process();
    }

    public function processRefund()
    {
        $this->requesturl = 'processrefund';
        $this->xml = '<Request>'."\n"
                    .'    <MerchantData>'."\n"
                    .'        <Platform>' . $this->params['Platform'] . '</Platform>'."\n"
                    .'        <UserId>' . $this->UserID . '</UserId>'."\n"
                    .'        <GID>' . $this->GatewayID . '</GID>'."\n"
                    .'        <Tid>' . @$this->params['Tid'] . '</Tid>'."\n"
                    .'    </MerchantData>'."\n"
                    .'    <ProcessRefund>'."\n"
                    .'        <Amount>' . $this->params['Amount'] . '</Amount>'."\n"
                    .'        <Token>' . $this->params['Token'] . '</Token>'."\n"
                    .'        <ApplicationId>' . $this->params['ApplicationId'] . '</ApplicationId>'."\n"
                    .'    </ProcessRefund>'."\n"
                    .'</Request>';
        $this->process();
    }

    public function setParameter($field = '', $value = null, $length = null)
    {
        $field = (is_string($field)) ? trim($field) : $field;
        if(is_string($value)){
            $value = trim($value);
            if(!is_null($length)){
                $value = substr($value, 0 , $length);
            }
        }
        if(!is_string($field)){
            trigger_error(__METHOD__ . '() arg 1 must be a string: ' . gettype($field) . ' given.', E_USER_ERROR);
        }
        if(empty($field)){
            trigger_error(__METHOD__ . '() requires a parameter field to be named.', E_USER_ERROR);
        }
        if(!is_string($value) && !is_numeric($value) && !is_bool($value)){
            trigger_error(__METHOD__ . '() arg 2 (' . $field . ') must be a string, integer, or boolean value: ' . gettype($value) . ' given.', E_USER_ERROR);
        }
        if($value === '' || is_null($value)){
            trigger_error(__METHOD__ . '() parameter "value" is empty or missing (parameter: ' . $field . ').', E_USER_NOTICE);
        }
        $this->params[$field] = $value;
    }

    private function parseResults()
    {
        $response = $this->response;
        $this->XmlResults = new SimpleXMLElement($response);

        $this->Status                     = (string) $this->XmlResults->Status;
        $this->Token                      = (string) $this->XmlResults->Token;
        $this->ResultMessage              = (string) $this->XmlResults->ResultMessage;
        $this->ResultCode                 = (string) $this->XmlResults->ResultCode;
        $this->ResponseCode               = (string) $this->XmlResults->ResponseCode;
        $this->AuthIdentificationResponse = (string) $this->XmlResults->AuthIdentificationResponse;
        $this->AdditionalResponseData     = (string) $this->XmlResults->AdditionalResponseData;
        $this->ReferenceNumber            = (string) $this->XmlResults->ReferenceNumber;
        $this->TransactionAmount          = (string) $this->XmlResults->TransactionAmount;
        $this->TransactionDate            = (string) $this->XmlResults->TransactionDate;
        $this->AvsResponse                = (string) $this->XmlResults->AvsResponse;
        $this->CvvResponse                = (string) $this->XmlResults->CvvResponse;
        $this->Receipt                    = (string) $this->XmlResults->Receipt;
    }

    public function isSuccessful()
    {
        return $this->success;
    }

    public function isError()
    {
        return $this->error;
    }

    public function getXmlResults()
    {
        return $this->XmlResults;
    }

    public function getStatus()
    {
        return $this->Status;
    }

    public function getToken()
    {
        return $this->Token;
    }

    public function getResultMessage()
    {
        return $this->ResultMessage;
    }

    public function getResultCode()
    {
        return $this->ResultCode;
    }

    public function getResponseCode()
    {
        return $this->ResponseCode;
    }

    public function getAuthIdentificationResponse()
    {
        return $this->AuthIdentificationResponse;
    }

    public function getAdditionalResponseData()
    {
        return $this->AdditionalResponseData;
    }

    public function getReferenceNumber()
    {
        return $this->ReferenceNumber;
    }

    public function getTransactionAmount()
    {
        return $this->TransactionAmount;
    }

    public function getTransactionDate()
    {
        return $this->TransactionDate;
    }

    public function getAvsResponse()
    {
        return $this->AvsResponse;
    }

    public function getCvvResponse()
    {
        return $this->CvvResponse;
    }

    public function getReceipt()
    {
        return $this->Receipt;
    }
}
?>