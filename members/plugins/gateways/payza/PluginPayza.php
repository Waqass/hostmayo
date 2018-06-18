<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Currency.php';

class PluginPayza extends GatewayPlugin{

    function getVariables()
    {
        /* Specification
            itemkey     - used to identify variable in your other functions
            type          - text,textarea,yesno,password
            description - description of the variable, displayed in ClientExec
        */

        $variables = array (
            lang("Plugin Name") => array (
                                "type"          =>"hidden",
                                "description"   =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                                "value"         =>lang("Payza")
            ),
            lang("User ID") => array (
                                "type"          =>"text",
                                "description"   =>lang("ID used to identify you to Payza.<br>NOTE: This ID is required if you have selected Payza as a payment gateway for any of your clients."),
                                "value"         =>""
            ),
            lang("Security Code") => array (
                                "type"          =>"text",
                                "description"   =>lang("Security Code in your Payza IPN setup."),
                                "value"         =>""
            ),
            lang("Signup Name") => array (
                                "type"          =>"text",
                                "description"   =>lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                                "value"         =>"Payza"
            ),
            lang("Invoice After Signup") => array (
                                "type"          =>"yesno",
                                "description"   =>lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                                "value"         =>"1"
            ),
             lang("Use Test Mode") => array(
                                "type"          =>"yesno",
                                "description"   =>lang("Select YES if you want to use Payza in test mode. You need to make sure in your IPN settings of Payza you have enabled Test mode"),
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

    function singlepayment($params, $test = false){
        CE_Lib::log(4,print_r("single payment payza",true) );
        CE_Lib::log(4,print_r($params,true) );
        $currency = new Currency($this->user);
        $userid=$params['plugin_payza_User ID'];
        $itemName=$params["companyName"]." - Subscription";
        $currencyType=$params["currencytype"];
        if ($params['isSignup']==1) {
            $returnURL=$params["clientExecURL"]."/signup.php?step=6&pass=1";
            $returnURL_Cancel=$params["clientExecURL"]."/signup.php?step=6&pass=0";
        }else {
            $returnURL=$params["clientExecURL"];
            $returnURL_Cancel=$params["clientExecURL"];
        }
        $amount = $currency->format($params['currencytype'], $params['invoiceTotal'] , false);
        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form method=\"post\" name=\"frmPayza\" action=\"https://secure.payza.com/checkout\" > ";
        $strRet .= "<input type=\"hidden\" name=\"ap_purchasetype\" value=\"item\"/> ";
        $strRet .= "<input type=\"hidden\" name=\"ap_merchant\" value=\"".$userid."\"/> ";
        $strRet .= "<input type=\"hidden\" name=\"ap_itemname\" value=\"".$itemName."\"/> ";
        $strRet .= "<input type=\"hidden\" name=\"ap_currency\" value=\"".$currencyType."\"/> ";

        //apc_1 stands for the invoice id
        $strRet .= "<input type=\"hidden\" name=\"apc_1\" value=\"".$params['invoiceNumber']."\"/> ";

        $strRet .= "<input type=\"hidden\" name=\"ap_returnurl\" value=\"".$returnURL."\"/> ";
        $strRet .= "<input type=\"hidden\" name=\"ap_quantity\" value=\"1\"/> ";
        $strRet .= "<input type=\"hidden\" name=\"ap_amount\" value=\"".$amount."\"/> ";
        $strRet .= "<input type=\"hidden\" name=\"ap_cancelurl\" value=\"".$returnURL_Cancel."\"/> ";
        $strRet .= "<input type=\"image\" name=\"ap_image\" src=\"https://www.payza.com/images/payza-buy-now.png\"/> ";
        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms['frmPayza'].submit();\n";
        $strRet .= "</script>\n";

        $strRet .= "</form> ";
        $strRet .= "</form>\n";
        $strRet .= "</body>\n</html>\n";
        echo $strRet;
        exit;
    }

    function credit($params){
    }

}

?>
