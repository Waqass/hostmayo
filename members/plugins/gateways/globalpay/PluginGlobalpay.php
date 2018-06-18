<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'library/CE/XmlFunctions.php';

/**
* @package Plugins
*/
class PluginGlobalpay extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
                   lang("Plugin Name") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                                        "value"         =>lang("Global Payments")
                                       ),
                   lang("Global User Name") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("User name used to identify you to globalpay.com.<br>NOTE: This user name is required if you have selected Global Payments as a payment gateway for any of your clients."),
                                        "value"         =>""
                                       ),
                   lang("Global Password") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Password used to identify you to globalpay.com.<br>NOTE: This password is required if you have selected Global Payments as a payment gateway for any of your clients."),
                                        "value"         =>""
                                       ),
                   lang("Global Environment Name") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("The Global Transport Certification environment's domain name is currently <b>https://certapia.globalpay.com</b></br>After certifying and when processing live transactions, the domain name must be changed in the integrated application to reflect the production environment's domain name.</br>After your certification is complete, contact Global Payments to receive the production domain name.</br>The value required in this field is to replace the <b><font color=blue>&lt;environment&gt;</font></b> tag on <b>https://<font color=blue>&lt;environment&gt;</font>.globalpay.com</b></br>Your certification system firewall must allow connection to both <b>172.31.31.59</b> and <b>172.31.12.81</b>"),
                                        "value"         =>"certapia"
                                       ),
                   lang("Global Term Type") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("The Term Type value (3 alphanumeric characters) identifies the application submitting the transaction.</br>The Global Payments Integration team will assign each integrated application a unique TermType during the certification process.</b>"),
                                        "value"         =>""
                                       ),
                    lang("Demo Mode") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Select YES if you want to set Global Payments into Demo Mode for testing. (<b>NOTE:</b> You must set to NO before accepting actual payments through this processor.)"),
                                        "value"         =>"0"
                                       ),
                   lang("Accept CC Number") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
                                        "value"         =>"1"
                                       ),
                   lang("Visa") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."),
                                        "value"         =>"0"
                                       ),
                   lang("MasterCard") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."),
                                        "value"         =>"0"
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

    function credit($params)
    {
        $params['refund'] = true;
        return $this->autopayment($params);
    }

    function singlepayment($params)
    {
        return $this->autopayment($params);
    }

    function autopayment($params)
    {
        //Function needs to build the url to the payment processor, then redirect
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_globalpay_SellerID"])
        $cPlugin = new Plugin($params['invoiceNumber'], "globalpay", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);
        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $TransType = 'Void';
            $cPlugin->setAction('refund');
        }else{
            $isRefund = false;
            $TransType = 'Sale';
            $cPlugin->setAction('charge');
        }

        $globalPay_url = "https://".trim($params["plugin_globalpay_Global Environment Name"]).".globalpay.com/GlobalPay/transact.asmx/ProcessCreditCard";
        $CCMonth       = mb_substr($params['userCCExp'], 0, 2);
        $CCYear        = mb_substr($params['userCCExp'], 5, 2);
        $invoiceAmount = $this->currencyFormat($this->settings->get('Default Currency'), $params['invoiceTotal']);
        if($invoiceAmount['error']){
            $cPlugin->PaymentRejected($invoiceAmount['value']);
            return $invoiceAmount['value'];
        }

        $TermType = trim($params["plugin_globalpay_Global Term Type"]);
        $CVNum = "";
        if($params["plugin_globalpay_Check CVV2"]){
            $CVNum = trim($params["userCCCVV2"]);
        }

        $arguments = array(
            "GlobalUserName" => trim($params["plugin_globalpay_Global User Name"]),
            "GlobalPassword" => trim($params["plugin_globalpay_Global Password"]),
            "TransType"      => trim($TransType),
            "CardNum"        => ($isRefund)? "" : trim($params['userCCNumber']),
            "ExpDate"        => ($isRefund)? "" : trim($CCMonth.$CCYear),
            "NameOnCard"     => ($isRefund)? "" : trim($params["userFirstName"]." ".$params["userLastName"]),
            "Amount"         => ($isRefund)? "" : trim($invoiceAmount['value']),
            "MagData"        => "",
            "InvNum"         => trim($params["invoiceNumber"]),
            "PNRef"          => ($isRefund)? trim($params['invoiceRefundTransactionId']) : "",
            "Zip"            => ($isRefund)? "" : trim($params["userZipcode"]),
            "Street"         => ($isRefund)? "" : trim($params["userAddress"]),
            "CVNum"          => ($isRefund)? "" : $CVNum,
            "ExtData"        => (($TermType == "")? "" : "<TermType>".$TermType."</TermType>")
                               .(($isRefund)? "" : "<CVPresence>".(($CVNum == "")? "NOTSUBMITTED" : "SUBMITTED")."</CVPresence>")
        );

        $postData = '';
        foreach ($arguments as $name => $value) {
            $name = urlencode($name);
            if($value === true){
                $value = 'true';
            }elseif($value === false){
                $value = 'false';
            }
            $postData .= $name . '=' . urlencode($value) . '&';
        }

        $masks = array(
            'GlobalUserName=XXX_MASKED_XXX&' => '/GlobalUserName=\w+&/',
            'GlobalPassword=XXX_MASKED_XXX&' => '/GlobalPassword=\w+&/',
            'CardNum=XXX_MASKED_XXX&'        => '/CardNum=\d+&/',
            'CVNum=XXX_MASKED_XXX&'          => '/CVNum=\d+&/',
            'ExtData=XXX_MASKED_XXX&'        => '/ExtData=.+&/'
        );

        CE_Lib::log(4, 'GlobalPay request: ' . $globalPay_url.'?'.preg_replace($masks, array_keys($masks), $postData));

        // certificate validation doesn't work well under windows
        $response = NE_Network::curlRequest($this->settings, $globalPay_url, $postData, false, true, false, 'POST', false, $masks);

        CE_Lib::log(4, 'GlobalPay response: ' . $response);

        if(is_a($response, 'CE_Error')){
            CE_Lib::log(4, 'Error communicating with GlobalPay: ' . $response->getMessage());
            throw new Exception('Error communicating with GlobalPay: ' . $response->getMessage());
        }elseif(!$response){
            CE_Lib::log(4, 'Error communicating with GlobalPay: No response found.');
            throw new Exception('Error communicating with GlobalPay: No response found.');
        }else{
            $response = XmlFunctions::xmlize($response);
        }

        if($response && isset($response['Response']['#']['Result'][0]['#'])){
            if($response['Response']['#']['Result'][0]['#'] == 0){
                $cPlugin->setTransactionID($response['Response']['#']['PNRef'][0]['#']);
                $cPlugin->setAmount($params['invoiceTotal']);
                $cPlugin->setLast4(mb_substr($params['userCCNumber'], -4));

                if($isRefund){
                    $cPlugin->PaymentAccepted($params['invoiceTotal'],
                                              "GlobalPay Gateway refund of ".$params['invoiceTotal']." was successfully processed.",
                                              $response['Response']['#']['PNRef'][0]['#']);
                }else{
                    $cPlugin->PaymentAccepted($params['invoiceTotal'],
                                              "GlobalPay Gateway payment of ".$params['invoiceTotal']." was accepted.",
                                              $response['Response']['#']['PNRef'][0]['#']);
                }
                return array('AMOUNT' => $params['invoiceTotal']);
            }else{
                $cPlugin->PaymentRejected($response['Response']['#']['RespMSG'][0]['#']);
                return 'There was an error in the gateway provider';
            }
        }else{
            $cPlugin->PaymentRejected($this->user->lang("There was not response from GlobalPay. Please double check your information"));
            return $this->user->lang("There was not response from GlobalPay. Please double check your information");
        }
    }

    function currencyFormat($currencyType, $value)
    {
        $supportedCurrencies = array(
            //'Currency_Code' => array(Decimal_Places, 'Decimal_Mark') //Country
            'USD' => array(2, '.'), //United States
            'AUD' => array(2, '.'), //Australia
            'CAD' => array(2, '.'), //Canada
            'CNY' => array(2, '.'), //China
            'GBP' => array(2, '.'), //Great Britain
            'HKD' => array(2, '.'), //Hong Kong
            'MYR' => array(2, '.'), //Malaysia
            'MXN' => array(2, '.'), //Mexico
            'NZD' => array(2, '.'), //New Zealand
            'PHP' => array(2, '.'), //Philippines
            'SAR' => array(2, '.'), //Saudi Arabia
            'SGD' => array(2, '.'), //Singapore
            'ZAR' => array(2, '.'), //South Africa
            'CHF' => array(2, '.'), //Switzerland
            'TWD' => array(2, '.'), //Taiwan
            'THB' => array(2, '.'), //Thailand
            'AED' => array(2, '.'), //United Arab Emirates
            'BRL' => array(2, ','), //Brazil
            'DKK' => array(2, ','), //Denmark
            'EUR' => array(2, ','), //France, Germany, Italy, Martinique
            'NOK' => array(2, ','), //Norway
            'SEK' => array(2, ','), //Sweden
            'VND' => array(2, ','), //Vietnam
            'IDR' => array(0,  ''), //Indonesia
            'JPY' => array(0,  ''), //Japan
            'KRW' => array(0,  '')  //South Korea
        );
        if(!isset($supportedCurrencies[$currencyType])){
            return array(
                'error' => true,
                'value' => 'Currency ('.$currencyType.') is not supported.'
            );
        }
        $formatedCurrency = sprintf("%01.".$supportedCurrencies[$currencyType][0]."f", round($value, $supportedCurrencies[$currencyType][0]));
        if($formatedCurrency == 0){
            return array(
                'error' => true,
                'value' => 'Amount must be a positive, non-zero value.'
            );
        }
        $formatedCurrency = number_format($formatedCurrency, $supportedCurrencies[$currencyType][0], $supportedCurrencies[$currencyType][1], '');
        if(strlen($formatedCurrency) > 11){
            return array(
                'error' => true,
                'value' => 'Amount ('.$formatedCurrency.') is not supported. Supported up to 11 alphanumeric characters.'
            );
        }
        return array(
            'error' => false,
            'value' => $formatedCurrency
        );
    }
}
?>
