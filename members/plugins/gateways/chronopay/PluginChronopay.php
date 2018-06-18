<?php
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginChronopay extends GatewayPlugin
{
    function getVariables()
    {
    	/* Specification
    		  itemkey     - used to identify variable in your other functions
    		  type 		  - text,textarea,yesno,password
    		  description - description of the variable, displayed in ClientExec
    	*/

        $variables = array (
                   lang("Plugin Name") => array (
    			   						"type"          =>"hidden",
    									"description"   =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
    									"value"         =>lang("ChronoPay")
    								   ),
                   lang("Product ID") => array (
    			   						"type"          =>"text",
    									"description"   =>lang("Product ID configured in your ChronoPay Account.<br>NOTE: This ID is required if you have selected ChronoPay as a payment gateway for any of your clients."),
    									"value"         =>""
    								   ),
                   lang("Product Name") => array (
    			   						"type"          =>"text",
    									"description"   =>lang("Product Name to be displayed on the ChronoPay hosted payment page."),
    									"value"         =>""
    								   ),
                   lang("ChronoPay Language") => array (
    			   						"type"          =>"text",
    									"description"   =>lang("Language in which the ChronoPay page will be displayed in. <br/>NL = Dutch, ES = Spanish, <br/>RU = Russian, EN = English (Default)"),
    									"value"         =>"EN"
    								   ),
                   lang("Invoice After Signup") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                                        "value"         =>"1"
                                       ),
                   lang("Signup Name") => array (
    			   						"type"          =>"text",
    									"description"   =>lang("Select the name to display in the signup process for this payment type. Example: ChronoPay or Credit Card."),
    									"value"         =>"Credit Card"
    								   ),
                   lang("Dummy Plugin") => array (
    			   						"type"          =>"hidden",
    									"description"   =>lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
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

    function singlepayment($params)
    {
    	//Function needs to build the url to the payment processor, then redirect
    	//Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_2checkout_SellerID"])

    	$return_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/chronopay/callback.php?ipn=1" : $params['clientExecURL']."/plugins/gateways/chronopay/callback.php?ipn=1";

    	$postURL = "https://secure.chronopay.com/index_shop.cgi";


    	if ($params["userCountry"]=="US") $params["userCountry"]="USA";

        //generate post to submit to chronopay
        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form name=\"frmChronoPay\" action=\"$postURL\" method=\"post\">\n";
        $strRet .= "<input type=\"hidden\" name=\"product_id\" value=\"".$params['plugin_chronopay_Product ID']."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"product_name\" value=\"".$params['plugin_chronopay_Product Name']."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"product_price\" value=\"".sprintf("%01.2f", round($params["invoiceTotal"], 2))."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"product_price_currency\" value=\"".$params['currencytype']."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"language\" value=\"".$params['plugin_chronopay_ChronoPay Language']."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"cs1\" value=\"".$params["invoiceNumber"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"cs2\" value=\"".$params['isSignup']."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"cs3\" value=\"".''."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"cb_url\" value=\"".$return_url."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"cb_type\" value=\"P\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"decline_url\" value=\"".$return_url."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"f_name\" value=\"".$params["userFirstName"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"s_name\" value=\"".$params["userLastName"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"street\" value=\"".$params["userAddress"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"city\" value=\"".$params["userCity"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"state\" value=\"".$params["userState"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"zip\" value=\"".$params["userZipcode"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"country\" value=\"".$params["userCountry"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"email\" value=\"".$params["userEmail"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"phone\" value=\"".$params["userPhone"]."\" />\n";
        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms[0].submit();\n";
        $strRet .= "</script>\n";
    //	$strRet .= "<input type=\"submit\" name=\"go\" value=\"Go to ChronoPay\" />\n";
        $strRet .= "</form>\n";
        $strRet .= "</body></html>";
        echo $strRet;
     	exit;
     }
}
?>
