<?php
/*****************************************************************/
// function plugin_ccavenue_variables($params) - required function
/*****************************************************************/
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginCcavenueold extends GatewayPlugin
{
    function getVariables() {
        /* Specification
        itemkey     - used to identify variable in your other functions
        type          - text,textarea,yesno,password,hidden ( hiddens are not visable to the user )
        description - description of the variable, displayed in ClientExec
        value     - default value
        */

        $variables = array (
            lang("Plugin Name") => array (
                                "type"          =>"hidden",
                                "description"   =>lang("How ClientExec sees this plugin (not to be confused with the Signup Name)"),
                                "value"         =>lang("CCAvenue (old version)")
                                ),
            lang("Merchant ID") => array (
                                "type"          =>"text",
                                "description"   =>lang("ID used to identify you to CCAvenue."),
                                "value"         =>""
                                ),
            lang("Working Key") => array (
                                "type"          =>"text",
                                "description"   =>lang("32 bit alphanumber CCAvenue key.<br>Note: This key is available at 'Generate Working Key' of the 'Settings & Options' section."),
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
            lang("Dummy Plugin") => array (
                                "type"          =>"hidden",
                                "description"   =>lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                                "value"         =>"0"
                                ),
            lang("Accept CC Number") => array (
                                "type"          =>"hidden",
                                "description"   =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
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

    function credit($params)
    {}

    /*****************************************************************/
    // function plugin_ccavenue_singlepayment($params) - required function
    /*****************************************************************/
    function singlepayment($params)
    {
        //Function needs to build the url to the payment processor
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])

        include("plugins/gateways/ccavenueold/libfuncs.php");

        $Merchant_Id = $params["plugin_ccavenueold_Merchant ID"];//This id(also User Id)  available at "Generate Working Key" of "Settings & Options"
        $Amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));//your script should substitute the amount in the quotes provided here
        $Order_Id = $params['invoiceNumber'];//your script should substitute the order description in the quotes provided here

        /**
         * OrderID should be unique for ccavenue gateway. So we need to append timestamp with the InvoiceID like invoiceid_timestamp
         */

        $Order_Id = $Order_Id."_".time();
        $Redirect_Url = $params['clientExecURL']."/plugins/gateways/ccavenueold/callback.php";//your redirect URL where your customer will be redirected after authorisation from CCAvenue
        $WorkingKey = $params["plugin_ccavenueold_Working Key"];//put in the 32 bit alphanumeric key in the quotes provided here.Please note that get this key ,login to your CCAvenue merchant account and visit the "Generate Working Key" section at the "Settings & Options" page.
        $Checksum = getCheckSum($Merchant_Id,$Amount,$Order_Id ,$Redirect_Url,$WorkingKey);

        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form name=ccavenue method=\"post\" action=\"https://www.ccavenue.com/shopzone/cc_details.jsp\">";
        $strRet .= "<input type=hidden name=Merchant_Id value=\"$Merchant_Id\">";
        $strRet .= "<input type=hidden name=Amount value=\"$Amount\">";
        $strRet .= "<input type=hidden name=Order_Id value=\"$Order_Id\">";
        $strRet .= "<input type=hidden name=Redirect_Url value=\"$Redirect_Url\">";
        $strRet .= "<input type=hidden name=Checksum value=\"$Checksum\">";

        $strRet .= "<input type=\"hidden\" name=\"billing_cust_name\" value=\"".$params["userFirstName"]." ".$params["userLastName"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_address\" value=\"".$params["userAddress"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_country\" value=\"".$params["userCountry"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_tel\" value=\"".$params["userPhone"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_email\" value=\"".$params["userEmail"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_name\" value=\"".$params["userFirstName"]." ".$params["userLastName"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_address\" value=\"".$params["userAddress"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_tel\" value=\"".$params["userPhone"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_notes\" value=\"Invoice #".$Order_Id."\">";
        $strRet .= "<input type=\"hidden\" name=\"Merchant_Param\" value=\"".$params['isSignup']."\">";

        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms['ccavenue'].submit();\n";
        $strRet .= "</script>\n";
        $strRet .= "</form>\n";
        $strRet .= "</body></html>";

        echo $strRet;
        exit;
    }
}
?>
