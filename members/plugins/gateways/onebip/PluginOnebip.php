<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Currency.php';

/**
* @package Plugins
*/
class PluginOnebip extends GatewayPlugin
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
                                        "value"         =>lang("Onebip")
                                       ),
                   lang("User ID") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("The email address associated to your Onebip account."),
                                        "value"         =>""
                                       ),
                   lang('API Key') => array (
                                        'type'          =>'password',
                                        'description'   =>lang('Please enter your API Key for your Onebip here. The API Key can be set under the "My Account" section of your Onebip account.'),
                                        'value'         =>''
                                       ),
                   lang("Onebip Payment Fee") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("A percentage to increase the amount to charge, due to the high Onebip payment fees. For example, a value of 20 (20%) will charge an amount of $120 for an invoice of $100"),
                                        "value"         =>"0"
                                       ),
                   lang("Signup Name") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                                        "value"         =>"Onebip"
                                       ),
                   lang("Invoice After Signup") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                                        "value"         =>"1"
                                       )
        );
        return $variables;
    }

    function credit($params)
    {}

    function singlepayment($params)
    {
        $stat_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/onebip/callback.php" : $params['clientExecURL']."/plugins/gateways/onebip/callback.php";

        //Need to check to see if user is coming from signup
        if ($params['isSignup']==1) {
            $returnURL=$params["clientExecURL"]."/order.php?step=5&pass=1";
            $returnURL_Cancel=$params["clientExecURL"]."/order.php?step=5&pass=0";
        }else {
            $returnURL=$params["clientExecURL"];
            $returnURL_Cancel=$params["clientExecURL"];
        }

        $currency = new Currency($this->user);
        $amount = $currency->format($params['currencytype'], $params['invoiceTotal'] , false);

        // Adding Onebip Payment Fee to the amount to pay
        $params["plugin_onebip_Onebip Payment Fee"] = trim($params["plugin_onebip_Onebip Payment Fee"]);
        if(!is_numeric($params["plugin_onebip_Onebip Payment Fee"])){
            $params["plugin_onebip_Onebip Payment Fee"] = 0;
        }
        $OnebipPaymentFeeDescription = '';
        if($params["plugin_onebip_Onebip Payment Fee"] > 0){
            $amountWithFees = $amount * (100 + $params["plugin_onebip_Onebip Payment Fee"]) / 100;
            $amountCents = sprintf("%01.2f", round($amountWithFees, 2)) * 100;
            $OnebipPaymentFeeDescription = " + Onebip Payment Fee (".$params["plugin_onebip_Onebip Payment Fee"]."%)";
        }else{
            $amountCents = sprintf("%01.2f", round($amount, 2)) * 100;
        }

        // Item Code. Limited to 64 characters by Onebip
        $item_code_company_part = $params["companyName"];
        $item_code_invoice_part = " Invoice ".$params['invoiceNumber'].$OnebipPaymentFeeDescription;
        $max_company_part_size = 64 - strlen($item_code_invoice_part);
        if(strlen($item_code_company_part) > $max_company_part_size){
            $item_code_company_part = substr($item_code_company_part, 0, $max_company_part_size - 3)."...";
        }
        $item_code = $item_code_company_part.$item_code_invoice_part;

        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";

        // Gateway Info
        $strRet .= "<form name=\"frmOnebip\" action=\"https://www.onebip.com/otms/\" method=\"post\">\n";
        $strRet .= "<input type=\"hidden\" name=\"command\" value=\"standard_pay\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"username\" value=\"".$params["plugin_onebip_User ID"]."\" />\n";

        // Item Info
        $strRet .= "<input type=\"hidden\" name=\"item_name\" value=\"".$params["companyName"]." Invoice ".$params['invoiceNumber'].$OnebipPaymentFeeDescription."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"item_code\" value=\"".$item_code."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"description\" value=\"".$params["companyName"]." Invoice ".$params['invoiceNumber'].$OnebipPaymentFeeDescription."\" />\n";

        // Payment Info
        $strRet .= "<input type=\"hidden\" name=\"price\" value=\"".$amountCents."\" />\n";   //cents
        $strRet .= "<input type=\"hidden\" name=\"currency\" value=\"".$params['currencytype']."\" />\n";

        // Custom Fields Info
        $strRet .= "<input type=\"hidden\" name=\"custom[invoice_number]\" value=\"".$params['invoiceNumber']."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"custom[original_ce_amount]\" value=\"".$amount."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"custom[onebip_payment_fee]\" value=\"".$params["plugin_onebip_Onebip Payment Fee"]."\" />\n";

        // Customer Info
        $strRet .= "<input type=\"hidden\" name=\"customer_email\" value=\"".$params["userEmail"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"customer_firstname\" value=\"".$params["userFirstName"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"customer_lastname\" value=\"".$params["userLastName"]."\" />\n";
        $strRet .= "<input type=\"hidden\" name=\"customer_country\" value=\"".$params["userCountry"]."\" />\n";

        // URLs Info
        $strRet .= "<input type=\"hidden\" name=\"return_url\" value=\"".$returnURL."\" />\n";        //sucess page
        $strRet .= "<input type=\"hidden\" name=\"cancel_url\" value=\"".$returnURL_Cancel."\" />\n"; //failed page
        $strRet .= "<input type=\"hidden\" name=\"notify_url\" value=\"".$stat_url."\" />\n";         //page of the gateway

        // Contact Info
        $strRet .= "<input type=\"hidden\" name=\"support_email\" value=\"".$params["companyBillingEmail"]."\" />\n";

        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms['frmOnebip'].submit();\n";
        $strRet .= "</script>\n";
        $strRet .= "</form>\n";
        $strRet .= "</body>\n</html>\n";

        echo $strRet;
        exit;
    }
}
?>
