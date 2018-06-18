<?php
/*****************************************************************/
// function plugin_worldpay_variables($params) - required function
/*****************************************************************/
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginWorldpay extends GatewayPlugin
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
                                        "value"         =>lang("World Pay")
                                       ),
                   lang("Installation ID") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("ID used to identify you to WorldPay.<br>NOTE: This ID is required if you have selected WorldPay as a payment gateway for any of your clients."),
                                        "value"         =>""
                                       ),
                   lang("Callback Password") => array (
                                        "type"          =>"password",
                                        "description"   =>lang("Password used to verify valid transactions FROM WorldPay Callbacks.<br>NOTE: This password has to match the value set in the WorldPay Customer Management System."),
                                        "value"         =>""
                                       ),
                   lang("MD5 Secret") => array (
                                        "type"          =>"password",
                                        "description"   =>lang("MD5 Secret used to verify valid transactions FROM WorldPay.<br>NOTE: This secret has to match the value set in the WorldPay Customer Management System."),
                                        "value"         =>""
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
                                        "type"          =>"hidden",
                                        "description"   =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
                                        "value"         =>"0"
                                       ),
                   lang("Dummy Plugin") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                                        "value"         =>"0"
                                       ),
                   lang("Auto Payment") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("No description"),
                                        "value"         =>"0"
                                       ),
                   lang("30 Day Billing") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."),
                                        "value"         =>"0"
                                       ),
                   lang("Check CVV2") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Select YES if you want to accept CVV2 for this plugin."),
                                        "value"         =>"0"
                                       )
        );
        return $variables;
    }

    /*****************************************************************/
    // function plugin_worldpay_singlepayment($params) - required function
    /*****************************************************************/
    function singlepayment($params)
    {
        //Function needs to build the url to the payment processor
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])

        if ($params["plugin_worldpay_MD5 Secret"]!="") {
            $this->buildmd5($params);
        } else {
            $strURL = $this->buildnormal($params);
        }

    }

    /**********************************************************************************/
    // function plugin_worldpay_buildnormal($params) - plugin function, used internally
    /**********************************************************************************/
    function buildnormal($params)
    {

            $strRet = "<html>\n";
            $strRet .= "<head></head>\n";
            $strRet .= "<body>\n";
            $strRet .= "<form name=\"frmWorldpay\" action=\"https://select.worldpay.com/wcc/purchase\" method=\"post\">\n";
            $strRet .= "<input type=\"hidden\" name=\"instId\" value=\"".$params["plugin_worldpay_Installation ID"]."\">\n";
            $strRet .= "<input type=\"hidden\" name=\"cartId\" value=\"".$params['invoiceNumber']."\">\n";
            $strRet .= "<input type=\"hidden\" name=\"name\" value=\"".$params['userFirstName']." ".$params['userLastName']."\">\n";
            $strRet .= "<input type=\"hidden\" name=\"amount\" value=\"".sprintf("%01.2f", round($params["invoiceTotal"], 2))."\">\n";
            $strRet .= "<input type=\"hidden\" name=\"currency\" value=\"".$params["currencytype"]."\">\n";
            $strRet .= "<input type=\"hidden\" name=\"email\" value=\"".$params["userEmail"]."\">\n";
            if (DEMO) $strRet .= "<input type=\"hidden\" name=\"testMode\" value=\"100\">\n";
            $strRet .= "</form>\n";

            //submi script
            $strRet .= "<script language=\"JavaScript\">\n";
            $strRet .= "document.forms[0].submit();\n";
            $strRet .= "</script>\n";
            $strRet .= "</body></html>";
            echo $strRet;
            exit;


    }

    function credit($params)
    {}

    /*******************************************************************************/
    // function plugin_worldpay_buildmd5($params) - plugin function, used internally
    /*******************************************************************************/
    function buildmd5($params)
    {

        //generate md5
        $strMD5 = $params["plugin_worldpay_MD5 Secret"];
        $strMD5 .= ":".sprintf("%01.2f", round($params["invoiceTotal"], 2));
        $strMD5 .= ":".$params["currencytype"];
        $strMD5 .= ":".$params['invoiceNumber'];
        $strMD5 .= ":".$params["plugin_worldpay_Installation ID"];
        $strMD5 = md5($strMD5);

        //generate post to submit to worldpay
        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form name=\"frmWorldpay\" action=\"https://select.worldpay.com/wcc/purchase\" method=\"post\">\n";
        $strRet .= "<input type=\"hidden\" name=\"instId\" value=\"".$params["plugin_worldpay_Installation ID"]."\">\n";
        $strRet .= "<input type=\"hidden\" name=\"signatureFields\" value=\"amount:currency:cartId:instId\">\n";
        $strRet .= "<input type=\"hidden\" name=\"signature\" value=\"".$strMD5."\">\n";
        $strRet .= "<input type=\"hidden\" name=\"cartId\" value=\"".$params['invoiceNumber']."\">\n";
        $strRet .= "<input type=\"hidden\" name=\"name\" value=\"".$params['userFirstName']." ".$params['userLastName']."\">\n";
        $strRet .= "<input type=\"hidden\" name=\"amount\" value=\"".sprintf("%01.2f", round($params["invoiceTotal"], 2))."\">\n";
        $strRet .= "<input type=\"hidden\" name=\"currency\" value=\"".$params["currencytype"]."\">\n";
        $strRet .= "<input type=\"hidden\" name=\"email\" value\"".$params["userEmail"]."\">\n";
        if (DEMO) $strRet .= "<input type=\"hidden\" name=\"testMode\" value=\"100\">\n";
        $strRet .= "</form>\n";
        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms[0].submit();\n";
        $strRet .= "</script>\n";
        $strRet .= "</body></html>";
        echo $strRet;
        exit;
    }
}
?>
