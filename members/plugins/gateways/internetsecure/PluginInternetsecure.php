<?php

/*****************************************************************/
// function plugin_internetsecure_variables($params) - required function
/*****************************************************************/
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginInternetsecure extends GatewayPlugin
{
    function getVariables()
    {
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password,hidden ( hiddens are not visable to the user )
              description - description of the variable, displayed in ClientExec
              value       - default value
        */

        $variables = array (
                   lang("Plugin Name") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                                        "value"         =>lang("Internet Secure")
                                       ),
                   lang("Company ID") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("ID used to identify you to Internet Secure.<br>NOTE: This ID is required if you have selected Internet Secure as a payment gateway for any of your clients."),
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
    // function plugin_internetsecure_singlepayment($params) - required function
    /*****************************************************************/
    function singlepayment($params)
    {
        //Function needs to build the url to the payment processor
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])

        //generate post to submit to internetsecure
        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form name=\"frmInternetSecure\" action=\"https://secure.internetsecure.com/process.cgi\" method=\"post\">\n";
        $strRet .= "<INPUT type=hidden name=\"products\" value=\"Price::Qty::Code::Desciption::Flags|".sprintf("%01.2f", round($params["invoiceTotal"], 2))."::1::".$params["invoiceNumber"]."::HostingInvoice::{".$params["currencytype"]."}\">";
        $strRet .= "<INPUT type=hidden name=\"MerchantNumber\" value=\"".$params["plugin_internetsecure_Company ID"]."\">";
        $strRet .= "<INPUT type=hidden name=\"language\" value=\"English\">";
        $strRet .= "<INPUT type=hidden name=\"ReturnURL\" value=\"".$params["companyURL"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxEmail\" value=\"".$params["userEmail"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxName\" value=\"".$params["userFirstName"]." ".$params["userLastName"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxCompany\" value=\"".$params["userCompany"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxCountry\" value=\"".$params["userCountry"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxAddress\" value=\"".$params["userAddress"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxCity\" value=\"".$params["userCity"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxProvince\" value=\"".$params["userState"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxPostal\" value=\"".$params["userZipcode"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxPhone\" value=\"".$params["userPhone"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxCurrency\" value=\"".$params["currencytype"]."\">";
        $strRet .= "<INPUT type=hidden name=\"xxxAmount\" value=\"".sprintf("%01.2f", round($params["invoiceTotal"], 2))."\">";
        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms['frmInternetSecure'].submit();\n";
        $strRet .= "</script>\n";
        $strRet .= "</form>\n";
        $strRet .= "</body></html>";
        echo $strRet;
        exit;
    }
}
?>
