<?php
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginMoneybookers extends GatewayPlugin
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
                                        "value"         =>lang("Money Bookers")
                                       ),
                   lang("Merchant E-mail") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("E-mail address used to identify you to Moneybookers."),
                                        "value"         =>""
                                       ),
                   lang("Secret Word") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Secret word set in your Money Bookers account."),
                                        "value"         =>""
                                       ),
                   lang("Status E-mail") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("E-mail address to where you want Moneybookers to send a copy of the transaction details after the payment process is complete. (Optional)"),
                                        "value"         =>""
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
                                        "value"         =>lang("Money Bookers")
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

    function singlepayment($params) {
        //Function needs to build the url to the payment processor, then redirect
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])
        $stat_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/moneybookers/callback.php" : $params['clientExecURL']."/plugins/gateways/moneybookers/callback.php";
        $stat_url2 = $params["plugin_moneybookers_Status E-mail"];

        $strForm  = '<html><body>';
        $strForm .= '<form name="frmMoneyBookers" action="https://www.moneybookers.com/app/payment.pl" method="post">';
        $strForm .= '<input type="hidden" name="pay_to_email" value="'.$params["plugin_moneybookers_Merchant E-mail"].'">';
        $strForm .= '<input type="hidden" name="detail1_description" value="Payment '.$params["companyName"].'">';
        $strForm .= '<input type="hidden" name="detail1_text" value="Invoice '.$params['invoiceNumber'].'">';
        $strForm .= '<input type="hidden" name="amount" value="'.sprintf("%01.2f", round($params["invoiceTotal"], 2)).'">';
        $strForm .= '<input type="hidden" name="transaction_id" value="'.$params['invoiceNumber'].'">';
        $strForm .= '<input type="hidden" name="status_url" value="'.$stat_url.'">';
        if(trim($stat_url2) != ''){
            $strForm .= '<input type="hidden" name="status_url2" value="'.$stat_url2.'">';
        }
        $strForm .= '<input type="hidden" name="return_url" value="'.$params["clientExecURL"].'">';
        $strForm .= '<input type="hidden" name="cancel_url" value="'.$params["clientExecURL"].'">';
        $strForm .= '<input type="hidden" name="language" value="EN">';
        $strForm .= '<input type="hidden" name="currency" value="'.$params["currencytype"].'">';
        $strForm .= '<input type="hidden" name="firstname" value="'.$params["userFirstName"].'">';
        $strForm .= '<input type="hidden" name="lastname" value="'.$params["userLastName"].'">';
        $strForm .= '<input type="hidden" name="address" value="'.$params["userAddress"].'">';
        $strForm .= '<input type="hidden" name="city" value="'.$params["userCity"].'".';
        $strForm .= '<input type="hidden" name="state" value="'.$params["userState"].'">';
        $strForm .= '<input type="hidden" name="postal_code" value="'.$params["userZipcode"].'">';
        $strForm .= "<script language=\"JavaScript\">\n";
        $strForm .= "document.forms['frmMoneyBookers'].submit();\n";
        $strForm .= "</script>";
        $strForm .= "</form>";
        $strForm .= "</body></html>";
        echo $strForm;
        exit;
    }
}
?>
