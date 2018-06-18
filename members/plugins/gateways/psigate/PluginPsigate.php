<?php
/*****************************************************************/
// function plugin_psigate_variables($params) - required function
/*****************************************************************/
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginPsigate extends GatewayPlugin
{
    function getVariables()
    {
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password
              description - description of the variable, displayed in ClientExec
        */

        $variables = array (
                   lang("Plugin Name") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                                        "value"         =>lang("PSiGate")
                                       ),
                   lang("Store Name") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("ID used to identify you to PSiGate.<br>NOTE: This ID is required if you have selected PSiGate as a payment gateway for any of your clients."),
                                        "value"         =>""
                                       ),
                   lang("Passphrase") => array (
                                        "type"          =>"password",
                                        "description"   =>lang("Your PSiGate passphrase used to authenticate a valid transaction."),
                                        "value"         =>""
                                       ),
                   lang("Demo Mode") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you want to set this plugin in Demo mode for testing purposes."),
                                        "value"         =>"1"
                                       ),
                   lang("Visa") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."),
                                        "value"         =>"1"
                                       ),
                   lang("MasterCard") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."),
                                        "value"         =>"1"
                                       ),
                   lang("AmericanExpress") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."),
                                        "value"         =>"0"
                                       ),
                   lang("Discover") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."),
                                        "value"         =>"0"
                                       ),
                   lang("Invoice After Signup") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                                        "value"         =>"1"
                                       ),
                   lang("Signup Name") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                                        "value"         =>"Credit Card"
                                       ),
                   lang("Accept CC Number") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
                                        "value"         =>"1"
                                       ),
                   lang("Dummy Plugin") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                                        "value"         =>"0"
                                       ),
                   lang("Auto Payment") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("No description"),
                                        "value"         =>"1"
                                        ),
                   lang("30 Day Billing") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."),
                                        "value"         =>"0"
                                       ),
                   lang("Check CVV2") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Select YES if you want to accept CVV2 for this plugin."),
                                        "value"         =>"1"
                                       )
        );
        return $variables;
    }

    /*****************************************************************/
    // function plugin_psigate_singlepayment($params) - required function
    /*****************************************************************/
    function singlepayment($params) {
        //Function needs to build the url to the payment processor
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])

        return $this->autopayment($params);

    }

    /**********************************************************************************/
    // function plugin_psigate_autopayment($params) - plugin function, used internally
    /**********************************************************************************/
    function autopayment($params)
    {
        require_once 'class.psigate_xml.php';
        require_once 'library/CE/NE_Network.php';

        $psi = new PsiGatePayment();

        // used for callback
        $transType = 'charge';

        $ip = CE_Lib::getRemoteAddr();
        $host = @gethostbyaddr($ip);

        if ($params["plugin_psigate_Demo Mode"]==0){
            $psi->setGatewayURL('https://secure.psigate.com:27934/Messenger/XMLMessenger');
        } else {
            $psi->setGatewayURL('https://dev.psigate.com:27934/Messenger/XMLMessenger');
        }

        $psi->setStoreID($params["plugin_psigate_Store Name"]);
        $psi->setPassPhrase($params["plugin_psigate_Passphrase"]); // Assures authenticity
        $psi->setOrderID(""); // Order ID.  Leave blank to have PSiGate assign
        $psi->setPaymentType('CC');
        $psi->setCardAction('0'); // 1 for Authorize, 0 for Immediate Charge
        $psi->setSubTotal(sprintf("%01.2f", round($params["invoiceTotal"], 2))); // Amount
        $psi->setCardNumber($params["userCCNumber"]); // Card Number
        $psi->setCardExpMonth(mb_substr($params["userCCExp"],0,2)); // Month in 2-digit format
        $psi->setCardExpYear(mb_substr($params["userCCExp"],strpos($params["userCCExp"],"/")+3));
        $psi->setCardIDNumber($params["userCCCVV2"]);
        $psi->setUserID($params['userID']); // Unique customer identifier set by merchant.
        $psi->setBname($params['userFirstName']." ".$params['userLastName']); // Billing Name
        $psi->setBcompany($params["userOrganization"]); // Company Name
        $psi->setBaddress1($params["userAddress"]); // Billing Address 1
        $psi->setBcity($params["userCity"]); // Billing City
        $psi->setBprovince($params["userState"]); // Billing state or province
        $psi->setBpostalCode($params["userZipcode"]); // Billing Zip
        $psi->setBcountry($params["userCountry"]);
        $psi->setPhone($params["userPhone"]); // Customer Phone
        $psi->setEmail($params["userEmail"]); // Customer Email
        $psi->setCustomerIP($ip); // Customer IP address, for fraud
        $psi->setComments($params["invoiceDescription"]);

        // doPayment is not safe for used with E_NOTICE
        $errorReporting = error_reporting();
        error_reporting(0);

        // Send transaction data to the gateway
        $psi->doPayment();

        error_reporting($errorReporting);

        if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }
        include 'plugins/gateways/psigate/callback.php';

        //Return error code
        $tReturnValue = "";
        if ($psi->getTrxnApproved() == 'APPROVED'){ $tReturnValue = ""; }
        else { $tReturnValue = $psi->getTrxnApproved()." Error: ".$psi->getErrorMessage();}
        return $tReturnValue;
    }

    function credit($params)
    {
        require_once 'class.psigate_xml.php';
        require_once 'library/CE/NE_Network.php';

        $psi = new PsiGatePayment();

        // used for callback
        $transType = 'void';

        $ip = CE_Lib::getRemoteAddr();
        $host = @gethostbyaddr($ip);

        if ($params["plugin_psigate_Demo Mode"]==0){
            $psi->setGatewayURL('https://secure.psigate.com:27934/Messenger/XMLMessenger');
        } else {
            $psi->setGatewayURL('https://dev.psigate.com:27934/Messenger/XMLMessenger');
        }

        $psi->setStoreID($params["plugin_psigate_Store Name"]);
        $psi->setPassPhrase($params["plugin_psigate_Passphrase"]); // Assures authenticity

        //$psi->setOrderID($params["invoiceRefundTransactionId"]); // Order ID.  Leave blank to have PSiGate assign
        $OrderArray = explode(" | ", $params["invoiceRefundTransactionId"]);
        $psi->setTrxnTransRefNumber($OrderArray[0]);
        $psi->setOrderID((isset($OrderArray[1]))? $OrderArray[1] : ""); // Order ID.  Leave blank to have PSiGate assign

        $psi->setPaymentType('CC');
        $psi->setCardAction('9'); // 1 for Authorize, 0 for Immediate Charge, 9 for void, 3 for credit
        $psi->setSubTotal(sprintf("%01.2f", round($params["invoiceTotal"], 2))); // Amount
        $psi->setCardNumber($params["userCCNumber"]); // Card Number
        $psi->setCardExpMonth(mb_substr($params["userCCExp"],0,2)); // Month in 2-digit format
        $psi->setCardExpYear(mb_substr($params["userCCExp"],strpos($params["userCCExp"],"/")+3));
        $psi->setUserID($params['userID']); // Unique customer identifier set by merchant.
        $psi->setBname($params['userFirstName']." ".$params['userLastName']); // Billing Name
        $psi->setBcompany($params["userOrganization"]); // Company Name
        $psi->setBaddress1($params["userAddress"]); // Billing Address 1
        $psi->setBcity($params["userCity"]); // Billing City
        $psi->setBprovince($params["userState"]); // Billing state or province
        $psi->setBpostalCode($params["userZipcode"]); // Billing Zip
        $psi->setBcountry($params["userCountry"]);
        $psi->setPhone($params["userPhone"]); // Customer Phone
        $psi->setEmail($params["userEmail"]); // Customer Email
        $psi->setCustomerIP($ip); // Customer IP address, for fraud
        $psi->setComments($params["invoiceDescription"]);

        // doPayment is not safe for used with E_NOTICE
        $errorReporting = error_reporting();
        error_reporting(0);

        // Send transaction data to the gateway
        $psi->doPayment();

        // if void failed, try refunding it
        if ($psi->getTrxnApproved() != 'APPROVED') {
            $psi->setCardAction(3); // credit
            $psi->doPayment();
            $transType = 'refund';
        }

        error_reporting($errorReporting);

        if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }
        include 'plugins/gateways/psigate/callback.php';

        //Return error code

        if($psi->getTrxnApproved() == 'APPROVED'){
            return array('AMOUNT' => $psi->getTrxnSubTotal());
        }else{
            return $psi->getTrxnApproved()." Error: ".$psi->getErrorMessage();
        }
    }
}
?>
