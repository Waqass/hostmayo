<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';

class PluginOnebipCallback extends PluginCallback
{
    function processCallback()
    {
        if (!isset($GLOBALS['testing'])) {
            $testing = false;
        } else {
            $testing = $GLOBALS['testing'];
        }

        CE_Lib::log(4, 'Onebip callback invoked');

        $error = false;

        // Check MD5 hash � anti-fraud measure
        if(isset($_REQUEST['hash'])){
            $my_api_key = $this->settings->get('plugin_onebip_API Key'); // stored in Onebip account settings
            $basename = basename($_SERVER['REQUEST_URI']);
            $pos = strrpos($basename, "&hash");
            $basename_without_hash = substr($basename, 0, $pos);
            $my_hash = md5($my_api_key . $basename_without_hash);

            if($my_hash != $_REQUEST['hash']){
                $error = "Invalid hash code";
            }else{
                // Onebip parameters:
                $payment_id         = $_REQUEST['payment_id'];          // This is a unique ID identifying the payment at Onebip
                $country            = $_REQUEST['country'];             // Country code in ISO 3166 standard
                $currency           = $_REQUEST['currency'];            // Local currency code in ISO 4217 standard
                $price              = $_REQUEST['price'];               // End user price in cents/pence (local value added taxes included) (actual value * 100)
                $tax                = $_REQUEST['tax'];                 // Local value added taxes paid by the end user, if any (actual value * 100)
                $commission         = $_REQUEST['commission'];          // Onebip commission fees for handling the transaction (actual value * 100)
                $amount             = $_REQUEST['amount'];              // Merchant net revenue for the transaction after local taxes and onebip fees (actual value * 100)
                $original_price     = $_REQUEST['original_price'];      // The original price set by the merchant before the onebip conversion in the local price of the buyer
                $original_currency  = $_REQUEST['original_currency'];   // The original currency set by the merchant before the onebip conversion in the local currency of the buyer

                // Your internal transaction ID and item code, if you use them:
                $item_code          = $_REQUEST['item_code'];

                // Your custom parameters, if you use them:
                $invoice_number     = $_REQUEST['invoice_number'];      // The Invoice ID in ClientExec
                $original_ce_amount = $_REQUEST['original_ce_amount'];  // The original amount to pay for the invoice in ClientExec
                $onebip_payment_fee = $_REQUEST['onebip_payment_fee'];  // The percentage used to increase the amount to charge, due to the high Onebip payment fees

                /*** ADD SOME VALIDATIONS HERE WITH THE ONEBIP PARAMETERS ***/

                // Payment Amount
                // Using $original_ce_amount will make sure to set the invoice as paid.
                // However, the real amount you get after local taxes and onebip fees is $amount/100
                $paymentAmount = $original_ce_amount;

                // Create Plugin class object to interact with CE.
                $cPlugin = new Plugin($invoice_number, 'onebip', $this->user);

                //Add plugin details
                $cPlugin->setAmount($paymentAmount);
                $cPlugin->m_TransactionID = $payment_id;
                $cPlugin->m_Action = "charge";
                $cPlugin->m_Last4 = "NA";

                // NOT SURE IF THERE IS A WAY TO GET AN STATUS
                /*
                switch($ppPayStatus) {
                    case "Completed":
                        $transaction = "Onebip payment of $paymentAmount was accepted. (OrderID:".$payment_id.")";
                        $cPlugin->PaymentAccepted($paymentAmount,$transaction,$payment_id, $testing);
                    break;
                    case "Pending":
                        $transaction = "Onebip payment of $paymentAmount was marked 'pending' by Paypal. (OrderID:".$payment_id.")";
                        $cPlugin->PaymentPending($transaction,$payment_id);
                    break;
                    case "Failed":
                        $transaction = "Onebip payment of $paymentAmount was rejected. (OrderID:".$payment_id.")";
                        $cPlugin->PaymentRejected($transaction);
                    break;
                }
                */
                $transaction = "Onebip payment of $paymentAmount was accepted. (OrderID:".$payment_id.")";
                $cPlugin->PaymentAccepted($paymentAmount,$transaction,$payment_id, $testing);
            }
        }else{
            $error = "This isn't a valid Onebip notification!";
        }

        if($error !== false){
            CE_Lib::log(1, "ONEBIP CALLBACK ERROR: ".$error);
            echo 'ERROR: '.$error;
        }else{
            echo 'OK'; // it is important you print "OK" in uppercase
        }
    }
}
?>
