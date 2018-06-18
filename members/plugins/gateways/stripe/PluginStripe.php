<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'plugins/gateways/stripe/stripe-php/lib/Stripe.php';

/**
* @package Plugins
*/
class PluginStripe extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
            lang("Plugin Name") => array (
                                "type"          =>"hidden",
                                "description"   =>lang("How CE sees this plugin ( not to be confused with the Signup Name )"),
                                "value"         =>"Stripe"
                                ),
            lang("Api Key") => array (
                                "type"          =>"text",
                                "description"   =>lang("Api Secret Key of your Stripe account."),
                                "value"         =>""
                                ),
            lang("Invoice After Signup") => array (
                                "type"          =>"yesno",
                                "description"   =>lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                                "value"         =>"1"
                                ),
            lang("Signup Name") => array (
                                "type"          =>"text",
                                "description"   =>lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                                "value"         =>"Stripe"
                                ),
            lang("Dummy Plugin") => array (
                                "type"          =>"hidden",
                                "description"   =>lang("1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"),
                                "value"         =>"0"
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
                                "value"         =>"1"
                               ),
            lang("DinersClub") => array (
                                "type"          =>"yesno",
                                "description"   =>lang("Select YES to allow Diners Club card acceptance with this plugin. No will prevent this card type."),
                                "value"         =>"1"
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
                                "description"   =>lang("Select YES if you want to accept CVV2 for this plugin."),
                                "value"         =>"1"
                                )
        );
        return $variables;
    }

    function credit($params)
    {
        $params['refund'] = true;
        return $this->autopayment($params);
    }

    function singlePayment($params)
    {
        return $this->autopayment($params);
    }

    function autopayment($params)
    {
        $cPlugin = new Plugin($params['invoiceNumber'], "stripe", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);

        $CCMo   = mb_substr($params['userCCExp'], 0, 2);
        $CCYear = mb_substr($params['userCCExp'], 3);
        $CCcvc  = (is_numeric($params["userCCCVV2"]) ? $params["userCCCVV2"] : null);

        Stripe::setApiKey($params["plugin_stripe_Api Key"]);

        // TEST CREDIT CARD NUMBERS
        //  Number            Card type
        //  4242424242424242  Visa
        //  5555555555554444  MasterCard
        //  378282246310005   American Express
        //  6011111111111117  Discover
        //  30569309025904    Diner's Club
        //
        //  In addition, these cards will produce specific responses that are useful for testing different scenarios:
        //    Number            Description
        //    4000000000000341  Attaching this card to a Customer object will succeed, but attempts to charge the customer will fail.
        //    4000000000000002  Charges with this card will always be declined with a card_declined code.
        //    4000000000000069  Will be declined with an expired_card code.
        //    4000000000000119  Will be declined with a processing_error code.
        $myCard = array(
            'number'          => $params['userCCNumber'],
            'exp_month'       => $CCMo,
            'exp_year'        => $CCYear,
            'cvc'             => $CCcvc,
            'name'            => $params["userFirstName"] . ' ' . $params["userLastName"],
            'address_line1'   => $params["userAddress"],
            'address_city'    => $params["userCity"],
            'address_zip'     => $params["userZipcode"],
            'address_state'   => $params["userState"],
            'address_country' => $params["userCountry"]
        );

        $currency = $params['currencytype'];

        $totalAmount = sprintf("%01.2f", round($params["invoiceTotal"], 2)) * 100;

        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $cPlugin->setAction('refund');
        }else{
            $isRefund = false;
            $cPlugin->setAction('charge');
        }

        try{
            if ($isRefund){
                $charge = Stripe_Charge::retrieve($params['invoiceRefundTransactionId']);
                $charge->refund();
            }else{
                $charge = Stripe_Charge::create(
                    array(
                        'card'        => $myCard,

                        // Amount charged in cents. The minimum amount is 50 cents
                        'amount'      => $totalAmount,

                        // https://support.stripe.com/questions/which-currencies-does-stripe-support
                        // 3-letter ISO code for currency.
                        // At present, if your business is in the United States you can only create charges in U.S. Dollars.
                        // If your business is in Canada, you can create charges in U.S. and Canadian Dollars.
                        // To be clear, it's not a problem if your customers' payment cards are based in other currencies.
                        // For example, if a customer has a Euro-based credit card and you're charging in U.S. dollars,
                        // the customer will see an uneven Euro charge on their credit card and you'll see
                        // the exact same deposit and fees as if you were charging a customer with a U.S. dollar-based card.
                        'currency'    => $currency,

                        // optional, default is null
                        // An arbitrary string which you can attach to a charge object.
                        // It is displayed when in the web interface alongside the charge.
                        // It's often a good idea to use an email address as a description for tracking later.
                        'description' => "user: ".$params['userEmail'].", Invoice: ".$params['invoiceNumber']
                    )
                );
            }
        }catch(Exception $e){
            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.")." ".$e->getMessage());
            return $this->user->lang("There was an error performing this operation.")." ".$e->getMessage();
        }

        if($charge->__get('failure_message') == ''){
            if($charge->__get('object') == 'charge'){
                $cPlugin->setTransactionID($charge->__get('id'));

                if ($isRefund){
                    if($charge->__get('refunded') == true){
                        $chargeAmount = sprintf("%01.2f", round(($charge->__get('amount_refunded') / 100), 2));
                        $cPlugin->PaymentAccepted($chargeAmount, "Stripe refund of {$chargeAmount} was successfully processed.", $charge->__get('id'));
                        return array('AMOUNT' => $chargeAmount);
                    }else{
                        $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation."));
                        return $this->user->lang("There was an error performing this operation.");
                    }
                }else{
                    if($charge->__get('paid') == true){
                        $chargeAmount = sprintf("%01.2f", round(($charge->__get('amount') / 100), 2));
                        $cPlugin->PaymentAccepted($chargeAmount, "Stripe payment of {$chargeAmount} was accepted.", $charge->__get('id'));
                        return '';
                    }else{
                        $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation."));
                        return $this->user->lang("There was an error performing this operation.");
                    }
                }
            }else{
                $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation."));
                return $this->user->lang("There was an error performing this operation.");
            }
        }else{
            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.")." ".$charge->__get('failure_message'));
            return $this->user->lang("There was an error performing this operation.")." ".$charge->__get('failure_message');
        }
    }
}