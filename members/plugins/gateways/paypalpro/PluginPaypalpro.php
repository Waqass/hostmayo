<?php

require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'plugins/gateways/paypalpro/functions.php';
require_once 'modules/billing/models/Currency.php';

class PluginPaypalpro extends GatewayPlugin
{
    /**
    * @package Plugins
    */
    function getVariables()
    {
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password,hidden ( hiddens are not visible to the user )
              description - description of the variable, displayed in ClientExec
              value       - default value
        */
        $variables = array (
		lang("Plugin Name") => array (
				"type"          =>"hidden",
				"description"   =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
				"value"         =>lang("Paypal Pro")
			       ),
		lang("Use PayPal Sandbox") => array(
				"type"          =>"yesno",
				"description"   =>lang("Select YES if you want to use the PayPal testing server, so no actual monetary transactions are made. You need to have a developer account with Paypal. Enter the API credentials from your Sandbox account below.  To test it, you must be logged into the developer panel (developer.paypal.com) in another browser window for the transaction to be successful."),
				"value"   	=>"0"
				),
		lang("API Username") => array (
				"type"          =>"text",
				"description"   =>lang("Enter your PayPal API Username Here."),
				"value"         =>""
			       ),
		lang("API Password") => array (
				"type"          =>"text",
				"description"   =>lang("Enter your PayPal API Password Here."),
				"value"         =>""
			       ),
		lang("API Signature") => array (
				"type"          =>"text",
				"description"   =>lang("Enter your PayPal API Signature Here."),
				"value"         =>""
			       ),
		lang("Accept CC Number") => array (
				"type"          =>"hidden",
				"description"   =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information."),
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
				"value"         =>"1"
			       ),
		lang("Discover") => array (
				"type"          =>"yesno",
				"description"   =>lang("Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."),
				"value"         =>"1"
			       ),
		lang("Invoice After Signup") => array (
				"type"          =>"yesno",
				"description"   =>lang("Select YES if you want an invoice sent to the customer after signup is complete.  NOTE: PayPal Website Payments Pro does not send a receipt for this payment type."),
				"value"		=>"0"
			       ),
		lang("Signup Name") => array (
				"type"          =>"text",
				"description"   =>lang("Select the name to display in the signup process for this payment type. Example: Credit Card."),
				"value"         =>"Credit Card"
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
				"type"          =>"yesno",
				"description"   =>lang("Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."),
				"value"         =>"0"
			       ),
		lang("Check CVV2") => array (
				"type"          =>"hidden",
				"description"   =>lang("PayPal Pro requires CVV2 checking."),
				"value"         =>"1"
			       )
        );
        return $variables;
    }

    function singlepayment($params)
    { return $this->autopayment($params); }

    function autopayment($params)
    {
    	require_once 'library/CE/NE_Network.php';

        // used for callback
        $transType = 'charge';

        // figure out customer IP
        $paypalpro['customer_ip'] = urlencode(CE_Lib::getRemoteAddr());

        // Pull the credentials from the database
        $paypalpro['API_Username']=urlencode($params['plugin_paypalpro_API Username']);
        $paypalpro['API_Password']=urlencode($params['plugin_paypalpro_API Password']);
        $paypalpro['API_Signature']=urlencode($params['plugin_paypalpro_API Signature']);

        // Determine the url
        $paypalpro['API_Endpoint'] = "https://api-3t.paypal.com/nvp";
	if($params['plugin_paypalpro_Use PayPal Sandbox']=="1") {
		$paypalpro['API_Endpoint'] = "https://api-3t.sandbox.paypal.com/nvp";
	}

        //Current User Information
        $paypalpro['firstname']=urlencode($params["userFirstName"]);
        $paypalpro['lastname']=urlencode($params["userLastName"]);
        $paypalpro['phone']=urlencode($params["userPhone"]);
        $paypalpro['address']=urlencode($params["userAddress"]);
        $paypalpro['city']=urlencode($params["userCity"]);
        $paypalpro['state']=urlencode($params["userState"]);
        $paypalpro['zip']=urlencode($params["userZipcode"]);
        $paypalpro['country']=urlencode($params["userCountry"]);
        $paypalpro['email']=urlencode($params["userEmail"]);

        //Transaction Information
        $paypalpro['currencycode']=urlencode($params['currencytype']);
        $paypalpro['paymentaction']=urlencode("Sale");
        $paypalpro['creditCardNumber']=urlencode($params["userCCNumber"]);
        $paypalpro['trans_id']=urlencode($params["invoiceRefundTransactionId"]);
        $paypalpro['cvv2']=urlencode($params["userCCCVV2"]);

        $currency = new Currency($this->user);
        $amount = $currency->format($params['currencytype'], $params['invoiceTotal'], false);
        $paypalpro['invoiceTotal']= urlencode($amount);

        $paypalpro['description']=urlencode($params["invoiceDescription"]);

        // Remove the slash from the expiration date
        $paypalpro['expmonth']=mb_substr($params["userCCExp"],0,2);
        $paypalpro['expyear']=mb_substr($params["userCCExp"],3,4);
        $paypalpro['expDate']=$paypalpro['expmonth'].$paypalpro['expyear'];


	// Find out what kind of card was entered
	$paypalpro['prefix'] = mb_substr($params["userCCNumber"],0,6);

	switch ($paypalpro['prefix']) {
	case (mb_substr($paypalpro['prefix'],0,1)=='4'):
		$paypalpro['creditCardType']=urlencode("Visa");
		break;
	case ((mb_substr($paypalpro['prefix'],0,2)=='34') || (mb_substr($paypalpro['prefix'],0,2)=='37')):
		$paypalpro['creditCardType']=urlencode("Amex");
		break;
	case ((mb_substr($paypalpro['prefix'],0,4)>='6011') ||
		(($paypalpro['prefix']>=622126) && ($paypalpro['prefix']<=622925)) ||
		((mb_substr($paypalpro['prefix'],0,3)>=644) && (mb_substr($paypalpro['prefix'],0,3)<=659)) ||
		(($params['plugin_paypalpro_Use PayPal Sandbox']=="1") && (mb_substr($paypalpro['prefix'],0,2)=='66'))):
		$paypalpro['creditCardType']=urlencode("Discover");
		break;
	case ((mb_substr($paypalpro['prefix'],0,2)>=51) && (mb_substr($paypalpro['prefix'],0,2)<=55)):
		$paypalpro['creditCardType']=urlencode("MasterCard");
		break;
	}

        // Construct the NVP string
        $nvpstr = "&AMT=" . $paypalpro['invoiceTotal'] . "&CURRENCYCODE=" . $paypalpro['currencycode'] . "&PAYMENTACTION=" . $paypalpro['paymentaction'] . "&CREDITCARDTYPE=" . $paypalpro['creditCardType'] . "&ACCT=" . $paypalpro['creditCardNumber'] . "&EXPDATE=" . $paypalpro['expDate'] . "&CVV2=" . $paypalpro['cvv2'] . "&EMAIL=" . $paypalpro['email'] . "&FIRSTNAME=" . $paypalpro['firstname'] . "&LASTNAME=" . $paypalpro['lastname'] . "&STREET=" . $paypalpro['address'] . "&CITY=" . $paypalpro['city'] . "&STATE=" . $paypalpro['state'] . "&ZIP=" . $paypalpro['zip'] . "&COUNTRYCODE=" . $paypalpro['country'] .  "&INVNUM=" . $paypalpro['trans_id'] . "&IPADDRESS=" . $paypalpro['customer_ip'] . "&DESC=" . $paypalpro['description'];
		$nvpstr .= "&BUTTONSOURCE=Clientexec_SP";
        // Call the function
	$resArray = DirectPayment("DoDirectPayment",$paypalpro['API_Username'], $paypalpro['API_Password'], $paypalpro['API_Signature'], $paypalpro['API_Endpoint'], $nvpstr);

	if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }

        $paypalpro["ack"] = urldecode(strtoupper($resArray["ACK"]));

        include('plugins/gateways/paypalpro/callback.php');

        $tReturnValue = "";

        if ($paypalpro["ack"]=="SUCCESS" || $paypalpro["ack"]=="SUCCESSWITHWARNING") { $tReturnValue = ""; }
        elseif ($paypalpro["ack"]=="FAILURE" || $paypalpro["ack"]=="FAILUREWITHWARNING" || $paypalpro["ack"]=="WARNING") {
        	$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
		$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
		$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
		$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

		$tReturnValue = "<span style='color: #FF0000;'>Error Message [$ErrorCode]: " . $ErrorShortMsg . "<br>" .
		$ErrorLongMsg . "</span>";
        }
        else { $tReturnValue = "<span style='color: #FF0000;'>An unspecified error has occurred.</span>"; }
        return $tReturnValue;
    }

    // Refund function (to send for a full refund of last charge on invoice)
    function credit($params)
    {
    	// Only used for callback
    	$transType = 'refund';

	// Pull the credentials from the database
	$paypalpro['API_Username']=urlencode($params['plugin_paypalpro_API Username']);
	$paypalpro['API_Password']=urlencode($params['plugin_paypalpro_API Password']);
	$paypalpro['API_Signature']=urlencode($params['plugin_paypalpro_API Signature']);

        // Determine the url
        $paypalpro['API_Endpoint'] = "https://api-3t.paypal.com/nvp";
	if($params['plugin_paypalpro_Use PayPal Sandbox']=="1") {
		$paypalpro['API_Endpoint'] = "https://api-3t.sandbox.paypal.com/nvp";
	}

        // Transaction Information
	$pwppreftype = urlencode("FULL");
	$pwpptransid = urlencode($params["invoiceRefundTransactionId"]);

	// Add request-specific fields to the request string.
	// No need to set the refund amount as per the operation of Paypal's RefundTransaction script
	// when doing a full refund.  Amount is not to be set for a full refund.  Additionally, the
	// currency code is also not to be set for a full refund.
	$nvpStr = "&TRANSACTIONID=" . $pwpptransid . "&REFUNDTYPE=" . $pwppreftype;

	$resArray = DirectPayment("RefundTransaction", $paypalpro['API_Username'], $paypalpro['API_Password'], $paypalpro['API_Signature'], $paypalpro['API_Endpoint'], $nvpStr);

	$bolInSignup = false;

        $pwppack = urldecode(strtoupper($resArray["ACK"]));
	$pwpprefundtransid = urldecode($resArray["REFUNDTRANSACTIONID"]);
        $pwpprefundedamt = urldecode($resArray["GROSSREFUNDAMT"]);

        include('plugins/gateways/paypalpro/callback.php');

        $tReturnValue = "";

        if ($pwppack=="SUCCESS" || $pwppack=="SUCCESSWITHWARNING") {
        	$tReturnValue = "";
        	return array('AMOUNT' => $pwpprefundedamt);
        }
        elseif ($pwppack=="FAILURE" || $pwppack=="FAILUREWITHWARNING" || $pwppack=="WARNING") {
        	$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
		$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
		$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
		$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

		$tReturnValue = "<span style='color: #FF0000;'>Error Message [$ErrorCode]: " . $ErrorShortMsg . "<br>" .
		$ErrorLongMsg . "</span>";
        }
        else { $tReturnValue = "<span style='color: #FF0000;'>An unspecified error has occurred.</span>"; }
        return $tReturnValue;
    }
}
?>
