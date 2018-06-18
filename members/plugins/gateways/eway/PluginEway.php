<?php
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginEway extends GatewayPlugin
{

    function getVariables()
    {
        /* Specification
               itemkey     - used to identify variable in your other functions
               type        - text,textarea,yesno,password
               description - description of the variable, displayed in ClientExec
        */

        $variables = array(
            lang("Plugin Name") => array(
                "type"        => "hidden",
                "description" => lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                "value"       => lang("eWay")
            ),
            lang("eWay Sandbox") => array(
                "type"        => "yesno",
                "description" => lang("Select YES if you want to set eWay into Test mode for testing. Even for testing you will need an eWay ID, that you can find at eWay's website."),
                "value"       => "0"
            ),
            lang("eWay ID") => array(
                "type"        => "text",
                "description" => lang("Please enter your eWay Customer ID here"),
                "value"       => ""
            ),
            lang("Refund Password") => array(
                "type"        => "password",
                "description" => lang("Password required for refunds."),
                "value"       => ""
            ),
            lang("Accept CC Number") => array(
                "type"        => "hidden",
                "description" => lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
                "value"       => "1"
            ),
            lang("Visa") => array(
                "type"        => "yesno",
                "description" => lang("Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."),
                "value"       => "1"
            ),
            lang("MasterCard") => array(
                "type"        => "yesno",
                "description" => lang("Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."),
                "value"       => "1"
            ),
            lang("AmericanExpress") => array(
                "type"        => "yesno",
                "description" => lang("Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."),
                "value"       => "1"
            ),
            lang("Discover") => array(
                "type"        => "yesno",
                "description" => lang("Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."),
                "value"       => "0"
            ),
            lang("Invoice After Signup") => array(
                "type"        => "yesno",
                "description" => lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                "value"       => "1"
            ),
            lang("Signup Name") => array(
                "type"        => "text",
                "description" => lang("Select the name to display in the signup process for this payment type. Example: eWay or Credit Card."),
                "value"       => "Credit Card"
            ),
            lang("Dummy Plugin") => array(
                "type"        => "hidden",
                "description" => lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                "value"       => "0"
            ),
            lang("Auto Payment") => array(
                "type"        => "hidden",
                "description" => lang("No description"),
                "value"       => "1"
            ),
            lang("30 Day Billing") => array(
                "type"        => "hidden",
                "description" => lang("Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."),
                "value"       => "0"
            ),
            lang("Check CVV2") => array(
                "type"        => "hidden",
                "description" => lang("Select YES if you want to accept CVV2 for this plugin."),
                "value"       => "1"
            )
        );
        return $variables;
    }

    function credit($params)
    {
        $params['refund'] = true;
        return $this->singlepayment($params);
    }

    function singlepayment($params)
    {
        require_once 'library/CE/NE_Network.php';

        $ewayId = trim($params["plugin_eway_eWay ID"]);
        $tInvoice = new Invoice($params["invoiceNumber"]);

        //Transaction Information
        $cardHoldersname = $params["userFirstName"]." ".$params["userLastName"];
        $ccMonth = mb_substr($params["userCCExp"], 0, 2);
        $ccYear = mb_substr($params["userCCExp"], strpos($params["userCCExp"], "/") + 3);
        $invoiceDescription = $tInvoice->getDescription();

        if (isset($params['refund']) && $params['refund']) {
            if ($params["plugin_eway_eWay Sandbox"] == '1') {
                $priceWithoutCents = explode(".", $params["invoiceTotal"]);
                $totalAmount = $priceWithoutCents[0] * 100;
            } else {
                $totalAmount = sprintf("%01.2f", round($params["invoiceTotal"], 2)) * 100;
            }

            //There is no Sandbox URL for refunds this way. Lets try the normal URL
            $requestUrl = "https://www.eway.com.au/gateway/xmlpaymentrefund.asp";

            $xmlCart = "<ewaygateway>";
            $xmlCart .= $this->CreateNode("ewayCustomerID", $ewayId);
            $xmlCart .= $this->CreateNode("ewayTotalAmount", $totalAmount);
            $xmlCart .= $this->CreateNode("ewayCardExpiryMonth", $ccMonth);
            $xmlCart .= $this->CreateNode("ewayCardExpiryYear", $ccYear);
            $xmlCart .= $this->CreateNode("ewayOriginalTrxnNumber", $params["invoiceRefundTransactionId"]);
            $xmlCart .= $this->CreateNode("ewayOption1", "");
            $xmlCart .= $this->CreateNode("ewayOption2", "");
            $xmlCart .= $this->CreateNode("ewayOption3", "");
            $xmlCart .= $this->CreateNode("ewayRefundPassword", $params["plugin_eway_Refund Password"]);
            $xmlCart .= "</ewaygateway>";
        } else {
            if ($params["plugin_eway_eWay Sandbox"] == '1') {
                $priceWithoutCents = explode(".", $tInvoice->getBalanceDue());
                $totalAmount = $priceWithoutCents[0] * 100;
                $requestUrl = "https://www.eway.com.au/gateway/xmltest/testpage.asp";
            } else {
                $totalAmount = sprintf("%01.2f", round($params["invoiceTotal"], 2)) * 100;
                $requestUrl = "https://www.eway.com.au/gateway/xmlpayment.asp";
            }

            $xmlCart = "<ewaygateway>";
            $xmlCart .= $this->CreateNode("ewayCustomerID", $ewayId);
            $xmlCart .= $this->CreateNode("ewayTotalAmount", $totalAmount);
            $xmlCart .= $this->CreateNode("ewayCardHoldersName", $cardHoldersname, true);
            $xmlCart .= $this->CreateNode("ewayCardNumber", $params["userCCNumber"]);
            $xmlCart .= $this->CreateNode("ewayCardExpiryMonth", $ccMonth);
            $xmlCart .= $this->CreateNode("ewayCardExpiryYear", $ccYear);
            $xmlCart .= $this->CreateNode("ewayTrxnNumber", "");
            $xmlCart .= $this->CreateNode("ewayCustomerInvoiceDescription", $invoiceDescription);
            $xmlCart .= $this->CreateNode("ewayCustomerFirstName", $params["userFirstName"], true);
            $xmlCart .= $this->CreateNode("ewayCustomerLastName", $params["userLastName"], true);
            $xmlCart .= $this->CreateNode("ewayCustomerEmail", $params["userEmail"]);
            $xmlCart .= $this->CreateNode("ewayCustomerAddress", $params["userAddress"], true);
            $xmlCart .= $this->CreateNode("ewayCustomerPostcode", $params["userZipcode"]);
            $xmlCart .= $this->CreateNode("ewayCustomerInvoiceRef", $params["invoiceNumber"]);
            $xmlCart .= $this->CreateNode("ewayOption1", "");
            $xmlCart .= $this->CreateNode("ewayOption2", "");
            $xmlCart .= $this->CreateNode("ewayOption3", "");
            if ($params["plugin_eway_Check CVV2"]) {
                $xmlCart .= $this->CreateNode("ewayCVN", $params["userCCCVV2"]);
            }
            $xmlCart .= "</ewaygateway>";
        }

        $transmit_response = NE_Network::curlRequest($this->settings, $requestUrl, $xmlCart, false, false, false);

        if (!$transmit_response) {
            $cPlugin->PaymentRejected($this->user->lang("There was not response from eWay. Please double check your information"));
            return $this->user->lang("There was not response from eWay. Please double check your information");
        }

        if (is_a($transmit_response, 'CE_Error')) {
            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.")." ".$transmit_response->getMessage());
            return $this->user->lang("There was an error performing this operation.")." ".$transmit_response->getMessage();
        }

        require_once 'library/CE/XmlFunctions.php';
        if ($transmit_response && !is_a($transmit_response, 'CE_Error')) {
            $xmlresponse = XmlFunctions::xmlize($transmit_response);
        }

        if (!$xmlresponse) {
            $cPlugin->PaymentRejected($this->user->lang("There was not response from eWay. Please double check your information"));
            return $this->user->lang("There was not response from eWay. Please double check your information");
        }

        if (is_a($xmlresponse, 'CE_Error')) {
            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.")." ".$xmlresponse->getMessage());
            return $this->user->lang("There was an error performing this operation.")." ".$xmlresponse->getMessage();
        }

        require_once 'modules/billing/models/class.gateway.plugin.php';
        $cPlugin = new Plugin($params["invoiceNumber"], "eway", $this->user);
        $chargeAmount = $params["invoiceTotal"];
        $cPlugin->setAmount($chargeAmount);
        if (isset($params['refund']) && $params['refund']) {
            $cPlugin->setAction('refund');
        } else {
            $cPlugin->setAction('charge');
        }

        require_once 'modules/billing/models/BillingGateway.php';
        $billingGateway = new BillingGateway($this->user);

        if (isset($xmlresponse['ewayResponse']['#']['ewayTrxnStatus'][0]['#'])) {
            $transactionID = '';
            if (isset($xmlresponse['ewayResponse']['#']['ewayTrxnNumber'][0]['#'])) {
                $transactionID = $xmlresponse['ewayResponse']['#']['ewayTrxnNumber'][0]['#'];
            } elseif (isset($xmlresponse['ewayResponse']['#']['ewayTrxnReference'][0]['#'])) {
                $transactionID = $xmlresponse['ewayResponse']['#']['ewayTrxnReference'][0]['#'];
            }
            $cPlugin->m_TransactionID = $transactionID;

            if ($xmlresponse['ewayResponse']['#']['ewayTrxnStatus'][0]['#'] == "True") {
                if (isset($params['refund']) && $params['refund']) {
                    $cPlugin->PaymentAccepted($chargeAmount, "eWay refund of {$chargeAmount} was successfully processed.", $transactionID);
                    return array('AMOUNT' => $chargeAmount);
                } else {
                    $cPlugin->PaymentAccepted($chargeAmount, "eWay Payment of {$chargeAmount} was accepted (OrderID:".$transactionID.")", $transactionID);
                }
            } else {
                $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.")." (".$xmlresponse['ewayResponse']['#']['ewayTrxnError'][0]['#'].").");
                return $this->user->lang("There was an error performing this operation.")." (".$xmlresponse['ewayResponse']['#']['ewayTrxnError'][0]['#'].").";
            }
        } else {
            $cPlugin->PaymentRejected($this->user->lang("There was not response from eWay. Please double check your information"));
            return $this->user->lang("There was not response from eWay. Please double check your information");
        }
    }

    function CreateNode($NodeName, $NodeValue, $CharacterData = false)
    {
        $NewNodeValue = htmlentities($NodeValue);
        if ($CharacterData) {
            $NewNodeValue = "<![CDATA[".$NewNodeValue."]]>";
        }
        $node = "<".$NodeName.">".$NewNodeValue."</".$NodeName.">";
        return $node;
    }
}
?>
