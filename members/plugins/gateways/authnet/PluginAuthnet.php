<?php
require_once 'modules/admin/models/GatewayPlugin.php';

class PluginAuthnet extends GatewayPlugin
{
    /**
    * @package Plugins
    */
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
                                        "value"         =>lang("Authorize.Net")
                                       ),
                   lang("Authorize.Net API Login ID") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Please enter your Authorize.Net API Login ID Here."),
                                        "value"         =>""
                                       ),
                   lang("Authorize.Net Transaction Key") => array (
                                        "type"          =>"password",
                                        "description"   =>lang("Please enter your Authorize.Net Transaction Key Here."),
                                        "value"         =>""
                                       ),
                   lang("MD5 Hash Value") => array (
                                        "type"          =>"password",
                                        "description"   =>lang("Used to verify valid transactions from Authorize.Net.<br>NOTE: This value has to match the value set in the Authorize.Net Merchant Interface - <i>Optional</i>"),
                                        "value"         =>""
                                       ),
                   lang("Demo Mode") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you want to set this plugin in Demo mode for testing purposes."),
                                        "value"         =>"1"
                                       ),
                   lang("Accept CC Number") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
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
                                        "description"   =>lang("Select YES if you want to accept CVV2 for this plugin.  Please set your Authorize.net account to reject transactions with CVV2 mismatches."),
                                        "value"         =>"1"
                                       )

        );
        return $variables;
    }

    function singlePayment($params)
    { // when set to non recurring
        //Function used to provide users with the ability
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])
        return $this->autopayment($params);
    }

    // when set to non recurring
    function credit($params)
    {
        // Used for callback
        $transType = 'credit';

        //Function used to provide users with the ability
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])

        $authnet['login'] = $params["plugin_authnet_Authorize.Net API Login ID"]; //Authorize.Net API Login ID
        $authnet['tran_key'] = $params["plugin_authnet_Authorize.Net Transaction Key"];     //authorize.net Password

        //Authnet Configuration
        $authnet['version']= "3.1";
        $authnet['method']="CC";
        $authnet['type']="CREDIT";
        if ($params["plugin_authnet_Demo Mode"]==1){ $authnet['test']="True"; }
        else { $authnet['test']="False"; }

        //Email Configuration
        $authnet['merchant_email']= $params["companyBillingEmail"];
        $authnet['email_customer']="False";

        //Environment Configuration
        $authnet['url']="https://secure.authorize.net/gateway/transact.dll"; //authorize.net url (post)

        //Transaction Information
        $authnet['cardnum']=$params["userCCNumber"];
        $authnet['amount']= $params["invoiceTotal"];
        $authnet['cust_id']=$params["userID"];
        $authnet['invoice_num']=$params["invoiceNumber"];
        $authnet['description']=$params["invoiceDescription"];
        $authnet['trans_id']=$params["invoiceRefundTransactionId"];

        // Authnet's library throws a lot of E_NOTICES...
        $errorReporting = error_reporting();
        error_reporting(0);
        include('plugins/gateways/authnet/functions.php');
        error_reporting($errorReporting);

        // if the transaction is not eligible for a credit (normally due to not settling yet)
        // try voiding the transaction.
        if ($authnet_results['x_response_code'] == 3 && $authnet_results['x_response_reason_code'] == 54) {
            $authnet['type'] = 'VOID';
            // Authnet's library throws a lot of E_NOTICES...
            $errorReporting = error_reporting();
            error_reporting(0);
            include('plugins/gateways/authnet/functions.php');
            error_reporting($errorReporting);
            $transType = 'void';
        }

        //Hash Value From Authorize.Net
        if ($params["plugin_authnet_MD5 Hash Value"] != ""){
            //      $tHash =  $params["plugin_authnet_MD5 Hash Value"].$params["plugin_authnet_Authorize.Net API Login ID"].$authnet_results["x_trans_id"].sprintf("%01.2f", round($params["invoiceTotal"], 2));
            //      if (strtoupper(md5($tHash))!=strtoupper($authnet_results["x_md5_hash"])) return; //do not approve
        }

        $bolInSignup = false;

        include('plugins/gateways/authnet/callback.php');

        //Return error code

        if($authnet_results["x_response_code"] == 1
          || $authnet_results["x_response_code"] == '*1*'){
            return array('AMOUNT' => $authnet_results["x_amount"]);
        }else{
            return $authnet_results["x_response_reason_text"]." Code:".$authnet_results["x_response_code"];
        }
    }

    function autopayment($params)
    {
        // used for callback
        $transType = 'charge';

        require_once 'library/CE/NE_Network.php';
        $authnet['login'] = $params["plugin_authnet_Authorize.Net API Login ID"]; //Authorize.Net API Login ID
        $authnet['tran_key'] = $params["plugin_authnet_Authorize.Net Transaction Key"];     //authorize.net Password

        //Authnet Configuration
        $authnet['version']= "3.1";
        $authnet['method']="CC";
        $authnet['type']="AUTH_CAPTURE";
        if ($params["plugin_authnet_Demo Mode"]==1){ $authnet['test']="True"; }
        else { $authnet['test']="False"; }

        //Email Configuration
        $authnet['merchant_email']= $params["companyBillingEmail"];
        $authnet['email_customer']="False";

        //Environment Configuration
        $authnet['url']="https://secure.authorize.net/gateway/transact.dll"; //authorize.net url (post)

        //Get information from current user
        //Contact Information
        $authnet['firstname']=$params["userFirstName"];
        $authnet['lastname']=$params["userLastName"];
        $authnet['phone']=$params["userPhone"];
        $authnet['address']=$params["userAddress"];
        $authnet['city']=$params["userCity"];
        $authnet['state']=$params["userState"];
        $authnet['zip']=$params["userZipcode"];
        $authnet['country']=$params["userCountry"];
        $authnet['email']=$params["userEmail"];

        // figure out customer IP
        $authnet['customer_ip'] = CE_Lib::getRemoteAddr();

        //If organization is populate then it is
        //used as the company name.  Wells Fargo
        //requires organization type. so we base it off
        //the organization name as well.
        if ($params["userOrganization"]==""){;
            $authnet['company']="NA";
            $authnet['organization_type'] = "I";
        }else{
            $authnet['company']=$params["userOrganization"];
            $authnet['organization_type'] = "B";
        }

        //Transaction Information
        $authnet['cardnum']=$params["userCCNumber"];
        $authnet['expdate']=$params["userCCExp"];  //MM/YYYYY
        $authnet['card_code']=$params["userCCCVV2"];

        $authnet['amount']= $params["invoiceTotal"];
        $authnet['cust_id']=$params["userID"];
        $authnet['invoice_num']=$params["invoiceNumber"];
        $authnet['description']=$params["invoiceDescription"];


        // Authnet's library throws a lot of E_NOTICES...
        $errorReporting = error_reporting();
        error_reporting(0);
        include('plugins/gateways/authnet/functions.php');
        error_reporting($errorReporting);

        //Hash Value From Authorize.Net
        if ($params["plugin_authnet_MD5 Hash Value"] != ""){
            //      $tHash =  $params["plugin_authnet_MD5 Hash Value"].$params["plugin_authnet_Authorize.Net API Login ID"].$authnet_results["x_trans_id"].sprintf("%01.2f", round($params["invoiceTotal"], 2));
            //      if (strtoupper(md5($tHash))!=strtoupper($authnet_results["x_md5_hash"])) return; //do not approve
        }
        if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }
        include('plugins/gateways/authnet/callback.php');
        //Return error code
        $tReturnValue = "";
        if (in_array($authnet_results["x_response_code"], array(1, '*1*', 4, '*4*'))) {
            $tReturnValue = "";
        } else {
            $tReturnValue = $authnet_results["x_response_reason_text"]." Code:".$authnet_results["x_response_code"];
        }
        return $tReturnValue;
    }
}
?>
