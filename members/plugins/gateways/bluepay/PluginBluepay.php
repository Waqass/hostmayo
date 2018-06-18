<?php
/*****************************************************************/
// function plugin_bluepay_variables($params) - required function
/*****************************************************************/
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginBluepay extends GatewayPlugin
{
    function getVariables() {
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password,hidden ( hiddens are not visable to the user )
              description - description of the variable, displayed in ClientExec
              value       - default value
        */
        $variables = array (
                   lang("Plugin Name") => array (
                                        "type"        =>"hidden",
                                        "description" =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                                        "value"       =>lang("BluePay")
                                       ),
                   lang("BluePay Account ID") => array (
                                        "type"        =>"text",
                                        "description" =>lang("Please enter your BluePay Account ID Here."),
                                        "value"       =>""
                                       ),
                   lang("BluePay Secret Key") => array (
                                        "type"        =>"password",
                                        "description" =>lang("Please enter your BluePay Secret Key Here."),
                                        "value"       =>""
                                       ),
                   lang("Demo Mode") => array (
                                        "type"        =>"yesno",
                                        "description" =>lang("Select YES if you want to set this plugin in Demo mode for testing purposes."),
                                        "value"       =>"1"
                                       ),
                   lang("Accept CC Number") => array (
                                        "type"        =>"hidden",
                                        "description" =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
                                        "value"       =>"1"
                                       ),
                   lang("Visa") => array (
                                        "type"        =>"yesno",
                                        "description" =>lang("Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."),
                                        "value"       =>"1"
                                       ),
                   lang("MasterCard") => array (
                                        "type"        =>"yesno",
                                        "description" =>lang("Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."),
                                        "value"       =>"1"
                                       ),
                   lang("AmericanExpress") => array (
                                        "type"        =>"yesno",
                                        "description" =>lang("Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."),
                                        "value"       =>"1"
                                       ),
                   lang("Discover") => array (
                                        "type"        =>"yesno",
                                        "description" =>lang("Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."),
                                        "value"       =>"0"
                                       ),
                   lang("Invoice After Signup") => array (
                                        "type"        =>"yesno",
                                        "description" =>lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                                        "value"       =>"1"
                                       ),
                   lang("Signup Name") => array (
                                        "type"        =>"text",
                                        "description" =>lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                                        "value"       =>"Credit Card"
                                       ),
                   lang("Dummy Plugin") => array (
                                        "type"        =>"hidden",
                                        "description" =>lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                                        "value"       =>"0"
                                       ),
                   lang("Auto Payment") => array (
                                        "type"        =>"hidden",
                                        "description" =>lang("No description"),
                                        "value"       =>"1"
                                       ),
                   lang("30 Day Billing") => array (
                                        "type"        =>"hidden",
                                        "description" =>lang("Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."),
                                        "value"       =>"0"
                                       ),
                   lang("Check CVV2") => array (
                                        "type"          =>"hidden", // not implemented yet
                                        "description"   =>lang("Select YES if you want to accept CVV2 for this plugin."),
                                        "value"         =>"1"
                                       )
        );
        return $variables;
    }

    /*****************************************************************/
    // function plugin_bluepay_singlepayment($params) - required function
    /*****************************************************************/
    function singlepayment($params)
    { // when set to non recurring
        //Function used to provide users with the ability
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])
        return $this->autopayment($params);
    }

    /*****************************************************************/
    // function plugin_bluepay_autopayment($userid) - required function if plugin is autopayment capable
    /*****************************************************************/
    function autopayment($params)
    {
        //used in callback
        $transType = 'charge';

        require_once 'class.BluePay.php';
        $mode = "TEST";
        if (!$params['plugin_bluepay_Demo Mode']) {
            $mode = "LIVE";
        }
        $bluePay = new BluePayment($params['plugin_bluepay_BluePay Account ID'], $params['plugin_bluepay_BluePay Secret Key'], $mode);
        $bluePay->sale($params['invoiceTotal']);
        $bluePay->setCustInfo($params["userCCNumber"],$params["userCCCVV2"],$params["userCCExp"],$params['userFirstName'],$params['userLastName'],
            $params['userAddress'],$params['userCity'],$params['userState'],$params['userZipcode'],$params['userCountry'],
            $params['userPhone'], $params['userEmail'], null, $params['invoiceDescription']);
        $bluePay->process();

        if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }
        include('plugins/gateways/bluepay/callback.php');
        //Return error code
        $tReturnValue = "";
        if (($bluePay->getStatus()==1)||($bluePay->getStatus()=='*1*')){ $tReturnValue = ""; }
        else { $tReturnValue = $bluePay->getMessage()." Code:".$bluePay->getStatus(); }
        return $tReturnValue;
    }

    function credit($params)
    {
        // used in callback
        $transType = 'refund';

        require_once 'class.BluePay.php';
        $mode = "TEST";
        if (!$params['plugin_bluepay_Demo Mode']) {
            $mode = "LIVE";
        }
        $bluePay = new BluePayment($params['plugin_bluepay_BluePay Account ID'], $params['plugin_bluepay_BluePay Secret Key'], $mode);
        $bluePay->refund($params['invoiceRefundTransactionId']);
        $bluePay->setCustInfo($params["userCCNumber"],"",$params["userCCExp"],$params['userFirstName'],$params['userLastName'],
            $params['userAddress'],$params['userCity'],$params['userState'],$params['userZipcode'],$params['userCountry'],
            $params['userPhone'], $params['userEmail'], null, $params['invoiceDescription']);
        $bluePay->process();

        if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }
        include('plugins/gateways/bluepay/callback.php');

        //Return error code

        if($bluePay->getStatus() == 1
          || $bluePay->getStatus() == '*1*'){
            return array('AMOUNT' => $params['invoiceTotal']);
        }else{
            return  $bluePay->getMessage()." Code:".$bluePay->getStatus();
        }
    }
}
?>
