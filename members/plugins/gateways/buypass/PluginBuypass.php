<?php
require_once('Buypass.class.php');
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

/**
* @package Plugins
*/
class PluginBuypass extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
            lang('Plugin Name') => array (
                'type'          => 'hidden',
                'description'   => lang('How CE sees this plugin ( not to be confused with the Signup Name )'),
                'value'         => 'Buypass'
            ),
            lang('Buypass User ID') => array (
                'type'          => 'password',
                'description'   => lang('Please enter your Buypass User ID here.'),
                'value'         => ''
            ),
            lang('Buypass Gateway ID') => array (
                'type'          => 'password',
                'description'   => lang('Please enter your Buypass Gateway ID here.'),
                'value'         => ''
            ),
            lang('Buypass Terminal ID') => array (
                'type'          => 'text',
                'description'   => lang('Please enter your Buypass Terminal ID here.</br>Max Size: 11'),
                'value'         => ''
            ),
            lang('Buypass Platform') => array (
                'type'          => 'text',
                'description'   => lang('Please enter your Buypass Platform here.</br>Identifies the platform to perform transaction processing.</br>Max Size: 11'),
                'value'         => ''
            ),
            lang('Buypass Application ID') => array (
                'type'          => 'text',
                'description'   => lang('Please enter your Buypass Application ID here.</br>Application identifier for the application used in sending/receiving transaction request.</br>The value of this field is assigned/authorized by the gateway and must be used in all transactions used by the certified application.</br>Max Size: 20'),
                'value'         => ''
            ),
            lang('Buypass Live URL') => array (
                'type'          => 'text',
                'description'   => lang('Please enter your Buypass Live URL here.'),
                'value'         => ''
            ),
            lang('Buypass Test URL') => array (
                'type'          => 'text',
                'description'   => lang('Please enter your Buypass Test URL here.'),
                'value'         => ''
            ),
            lang('Buypass Test Mode') => array (
                'type'          => 'yesno',
                'description'   => lang('Select YES if you want to use Buypass testing server, so no actual monetary transactions are made.'),
                'value'         => '0'
            ),
            lang('Invoice After Signup') => array (
                'type'          => 'yesno',
                'description'   => lang('Select YES if you want an invoice sent to the customer after signup is complete.'),
                'value'         => '1'
            ),
            lang('Signup Name') => array (
                'type'          => 'text',
                'description'   => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'         => 'Buypass'
            ),
            lang('Auto Payment') => array (
                'type'          => 'hidden',
                'description'   => lang('No description'),
                'value'         => '1'
            ),
            lang('Dummy Plugin') => array (
                'type'          => 'hidden',
                'description'   => lang('1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions'),
                'value'         => '0'
            ),
            lang('Update Gateway') => array (
                'type'        => 'hidden',
                'description' => lang('1 = Create, update or remove Gateway customer information through the function UpdateGateway when customer choose to use this gateway, customer profile is updated, customer is deleted or customer status is changed. 0 = Do nothing.'),
                'value'       => '1'
            ),
            lang('Accept CC Number') => array (
                'type'          => 'hidden',
                'description'   => lang('Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information'),
                'value'         => '1'
            ),
            lang('Visa') => array (
                'type'          => 'yesno',
                'description'   => lang('Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type.'),
                'value'         => '1'
            ),
            lang('MasterCard') => array (
                'type'          => 'yesno',
                'description'   => lang('Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type.'),
                'value'         => '1'
            ),
            lang('AmericanExpress') => array (
                'type'          => 'yesno',
                'description'   => lang('Select YES to allow American Express card acceptance with this plugin. No will prevent this card type.'),
                'value'         => '1'
            ),
            lang('Discover') => array (
                'type'          => 'yesno',
                'description'   => lang('Select YES to allow Discover card acceptance with this plugin. No will prevent this card type.'),
                'value'         => '1'
            )
        );
        return $variables;
    }

    function credit($params)
    {
        $params['refund'] = true;
        return $this->autopayment($params);
    }

    function singlepayment($params)
    {
        return $this->autopayment($params);
    }

    function autopayment($params)
    {
        $cPlugin = new Plugin($params['invoiceNumber'], "buypass", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);

        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $cPlugin->setAction('refund');
        }else{
            $isRefund = false;
            $cPlugin->setAction('charge');
        }

        //Create customer Buypass profile transaction
        $customerProfile = $this->createCustomerProfileTransaction($params, $isRefund);
        if($customerProfile['error']){
            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.$customerProfile['detail']);
            return $this->user->lang("There was an error performing this operation.").' '.$customerProfile['detail'];
        }else{
            // 00 - Approved or completed successfully
            // 11 - Approved (VIP)
            // 10 - Approved, partial amount approved
            if(in_array($customerProfile['ResponseCode'], array('00', '11', '10'))){
                if(in_array($customerProfile['ResponseCode'], array('00', '11'))){
                    // 00 - Approved or completed successfully
                    // 11 - Approved (VIP)
                    $amount = $customerProfile['amount'];
                }elseif($customerProfile['ResponseCode'] === '10'){
                    // 10 - Approved, partial amount approved
                    //Transaction's total amount in US dollars. Format assumes 2 decimal points.
                    $amount = number_format((intval($customerProfile['TransactionAmount'])/100),2);
                }

                if($isRefund){
                    $cPlugin->PaymentAccepted($amount, "Buypass refund of {$amount} was successfully processed.", $customerProfile['ReferenceNumber']);
                    return array('AMOUNT' => $amount);
                }else{
                    $cPlugin->setTransactionID($customerProfile['ReferenceNumber']);
                    $cPlugin->PaymentAccepted($amount, "Buypass payment of {$amount} was accepted. Auth Identification Response: {$customerProfile['AuthIdentificationResponse']}", $customerProfile['ReferenceNumber']);
                    return '';
                }
            }else{
                $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' *Response Code: '.$customerProfile['ResponseCode']);
                return $this->user->lang("There was an error performing this operation.").' *Response Code: '.$customerProfile['ResponseCode'];
            }
        }
    }

    // Create customer Buypass profile
    function createFullCustomerProfile($params)
    {
        //Buypass Credentials from CE plugin
        $UserID = $this->settings->get('plugin_buypass_Buypass User ID');
        $GatewayID = $this->settings->get('plugin_buypass_Buypass Gateway ID');
        $LiveURL = $this->settings->get('plugin_buypass_Buypass Live URL');
        $TestURL = $this->settings->get('plugin_buypass_Buypass Test URL');
        $sandbox = $this->settings->get('plugin_buypass_Buypass Test Mode');
        $USE_DEVELOPMENT_SERVER = ($sandbox)? Buypass::USE_DEVELOPMENT_SERVER : Buypass::USE_PRODUCTION_SERVER;
        $TerminalID = $this->settings->get('plugin_buypass_Buypass Terminal ID');
        $Platform = $this->settings->get('plugin_buypass_Buypass Platform');
        $ApplicationID = $this->settings->get('plugin_buypass_Buypass Application ID');

        try{
            // Process the transaction
            $buypass = new Buypass($UserID, $GatewayID, $LiveURL, $TestURL, $USE_DEVELOPMENT_SERVER);

            //Max Size: 11
            //Terminal identifier.
            $buypass->setParameter('Tid', $TerminalID, 11);

            //Max Size: 11
            //Identifies the platform to perform transaction processing.
            $buypass->setParameter('Platform', $Platform, 11);

            //Max Size: 19
            //Credit card number.
            //Only numeric characters are allowed; spaces and hyphens are not allowed; for example; 41111111111111111 is allowed
            $buypass->setParameter('AccountNumber', $params['userCCNumber'], 19);

            //Max Size: 2
            //Credit card expiration month in MM format.
            //Exactly two characters required; for example, 02
            $buypass->setParameter('ExpirationMonth', $params['cc_exp_month'], 2);

            //Max Size: 2
            //Credit card expiration year in YY format.
            //Exactly two characters required; for example, 09.
            $buypass->setParameter('ExpirationYear', substr(trim($params['cc_exp_year']), 2 , 2), 2);

            //Max Size: 40
            //Card holder first name.
            //This field is optional and used for transaction reporting.
            $buypass->setParameter('CardHolderFirstName', $params['userFirstName'], 40);

            //Max Size: 40
            //Card holder last name.
            //This field is optional and used for transaction reporting.
            $buypass->setParameter('CardHolderLastName', $params['userLastName'], 40);

            //Max Size: 10 (It is failing and looks like Max Size: 5)
            //Billing ZIP or postal code used for AVS.
            $buypass->setParameter('AvsZip', $params['userZipcode'], 5);

            //Max Size: 80
            //Billing street address used for AVS.
            $buypass->setParameter('AvsStreet', $params['userAddress'], 80);

            //Max Size: 20
            //Application identifier for the application used in sending/receiving transaction request.
            //The value of this field is assigned/authorized by the gateway and must be used in all transactions used by the certified application.
            $buypass->setParameter('ApplicationId', $ApplicationID, 20);

            //Max Size: 50
            //Custom field used to record transaction details.
            //This field is optional and used for transaction reporting.
            $buypass->setParameter('Cf1', 'customerid: '.$params['CustomerID'], 50);

            $buypass->createToken();

            if($buypass->isSuccessful()){
                //Token identifying the card number to process
                $profile_id = $buypass->getToken();
                $Billing_Profile_ID = '';
                $profile_id_array = array();
                $user = new User($params['CustomerID']);
                if($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != ''){
                    $profile_id_array = unserialize($Billing_Profile_ID);
                }
                if(!is_array($profile_id_array)){
                    $profile_id_array = array();
                }
                $profile_id_array['buypass'] = $profile_id;
                $user->updateCustomTag('Billing-Profile-ID', serialize($profile_id_array));
                $user->save();
                
                return array(
                    'error'               => false,
                    'profile_id'          => $profile_id
                );
            }else{
                return array(
                    'error'  => true,

                    //Result Message. Can have details of the error.
                    'detail' => $buypass->getResultMessage()
                );
            }
        }catch(BuypassException $e){
            return array(
                'error'  => true,
                'detail' => $e
            );
        }
    }

    function UpdateGateway($params){
        switch($params['Action']){
            case 'update':  // When updating customer profile or changing to use this gateway
                $statusAliasGateway = StatusAliasGateway::getInstance($this->user);
                if(in_array($params['Status'], $statusAliasGateway->getUserStatusIdsFor(array(USER_STATUS_INACTIVE, USER_STATUS_CANCELLED, USER_STATUS_FRAUD)))){
                  $this->CustomerRemove($params);
                }
                break;
            case 'delete':  // When deleting the customer, changing to use another gateway, or updating the Credit Card
                $this->CustomerRemove($params);
                break;
        }
    }

    function CustomerRemove($params){
        $profile_id = '';
        $Billing_Profile_ID = '';
        $profile_id_array = array();
        $user = new User($params['User ID']);
        if($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != ''){
            $profile_id_array = unserialize($Billing_Profile_ID);
            if(is_array($profile_id_array) && isset($profile_id_array['buypass'])){
                $profile_id = $profile_id_array['buypass'];
            }
        }

        if($profile_id != ''){
            //Buypass Credentials from CE plugin
            $UserID = $this->settings->get('plugin_buypass_Buypass User ID');
            $GatewayID = $this->settings->get('plugin_buypass_Buypass Gateway ID');
            $LiveURL = $this->settings->get('plugin_buypass_Buypass Live URL');
            $TestURL = $this->settings->get('plugin_buypass_Buypass Test URL');
            $sandbox = $this->settings->get('plugin_buypass_Buypass Test Mode');
            $USE_DEVELOPMENT_SERVER = ($sandbox)? Buypass::USE_DEVELOPMENT_SERVER : Buypass::USE_PRODUCTION_SERVER;
            $TerminalID = $this->settings->get('plugin_buypass_Buypass Terminal ID');
            $Platform = $this->settings->get('plugin_buypass_Buypass Platform');
            $ApplicationID = $this->settings->get('plugin_buypass_Buypass Application ID');

            try{
                // Process the transaction
                $buypass = new Buypass($UserID, $GatewayID, $LiveURL, $TestURL, $USE_DEVELOPMENT_SERVER);

                //Max Size: 11
                //Terminal identifier.
                $buypass->setParameter('Tid', $TerminalID, 11);

                //Max Size: 11
                //Identifies the platform to perform transaction processing.
                $buypass->setParameter('Platform', $Platform, 11);

                //Max Size: 16
                //Token identifying the card number to process
                $buypass->setParameter('Token', $profile_id, 16);

                $buypass->deleteToken();

                if(is_array($profile_id_array)){
                    unset($profile_id_array['buypass']);
                }else{
                    $profile_id_array = array();
                }
                $user->updateCustomTag('Billing-Profile-ID', serialize($profile_id_array));
                $user->save();

                if($buypass->isSuccessful()){
                    return array(
                        'error'      => false,
                        'profile_id' => $profile_id
                    );
                }else{
                    return array(
                        'error'  => true,

                        //Result Message. Can have details of the error.
                        'detail' => $buypass->getResultMessage()
                    );
                }
            }catch(BuypassException $e){
                return array(
                    'error'  => true,
                    'detail' => $e
                );
            }
        }else{
            return array(
                'error'  => true,
                'detail' => $this->user->lang("There was an error performing this operation.").' '.$this->user->lang("profile_id is empty.")
            );
        }
    }

    //Get customer Buypass profile
    function getCustomerProfile($params)
    {
        $profile_id == '';
        $Billing_Profile_ID = '';
        $profile_id_array = array();
        $user = new User($params['CustomerID']);
        if($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != ''){
            $profile_id_array = unserialize($Billing_Profile_ID);
            if(is_array($profile_id_array) && isset($profile_id_array['buypass'])){
                $profile_id = $profile_id_array['buypass'];
            }
        }

        if($profile_id == ''){
            $params['cc_exp_month'] = sprintf("%02d", $user->getCCMonth());
            $params['cc_exp_year'] = $user->getCCYEAR();
            return $this->createFullCustomerProfile($params);
        }else{
            return array(
                'error'               => false,
                'profile_id'          => $profile_id
            );
        }
    }

    //Create customer Buypass profile transaction
    function createCustomerProfileTransaction($params, $isRefund)
    {
        //Get customer Buypass payment profile
        $customerProfile = $this->getCustomerProfile($params);
        if($customerProfile['error']){
            return $customerProfile;
        }else{
            $profile_id = $customerProfile['profile_id'];
        }

        //Invoice Information from CE
        $amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));

        //Buypass Credentials from CE plugin
        $UserID = $this->settings->get('plugin_buypass_Buypass User ID');
        $GatewayID = $this->settings->get('plugin_buypass_Buypass Gateway ID');
        $LiveURL = $this->settings->get('plugin_buypass_Buypass Live URL');
        $TestURL = $this->settings->get('plugin_buypass_Buypass Test URL');
        $sandbox = $this->settings->get('plugin_buypass_Buypass Test Mode');
        $USE_DEVELOPMENT_SERVER = ($sandbox)? Buypass::USE_DEVELOPMENT_SERVER : Buypass::USE_PRODUCTION_SERVER;
        $TerminalID = $this->settings->get('plugin_buypass_Buypass Terminal ID');
        $Platform = $this->settings->get('plugin_buypass_Buypass Platform');
        $ApplicationID = $this->settings->get('plugin_buypass_Buypass Application ID');

        try{
            // Process the transaction
            $buypass = new Buypass($UserID, $GatewayID, $LiveURL, $TestURL, $USE_DEVELOPMENT_SERVER);

            //Max Size: 11
            //Terminal identifier.
            $buypass->setParameter('Tid', $TerminalID, 11);

            //Max Size: 11
            //Identifies the platform to perform transaction processing.
            $buypass->setParameter('Platform', $Platform, 11);

            //Max Size: 12
            //Full amount of transaction including cents.
            // Only numeric characters and a decimal point are allowed; for example, 1000.00
            $buypass->setParameter('Amount', $amount, 12);

            //Max Size: 16
            //Token identifying the card number to process
            $buypass->setParameter('Token', $profile_id, 16);

            //Max Size: 50
            //Custom field used to record transaction details.
            //This field is optional and used for transaction reporting.
            $buypass->setParameter('Cf1', 'customerid: '.$params['CustomerID'], 50);
            $buypass->setParameter('Cf2', 'invoiceid: '.$params['invoiceNumber'], 50);

            //Max Size: 20
            //Application identifier for the application used in sending/receiving transaction request.
            //The value of this field is assigned/authorized by the gateway and must be used in all transactions used by the certified application.
            $buypass->setParameter('ApplicationId', $ApplicationID, 20);

            //Max Size: 1
            //Recurring payment indicator. 1 – ON; 0 – OFF;
            //Buypass currently only supports the recurring payment indicator for Visa and MasterCard transactions.
            $buypass->setParameter('Recurring', '0', 1);

            if($isRefund){
                $buypass->processRefund();
            }else{
                $buypass->processPayment();
            }

            // Get the payment or refund profile ID returned from the request
            if($buypass->isSuccessful()){
                return array(
                    'error'                      => false,

                    //Identifies the response identification assigned by the authorizing institution. Present only for approvals.
                    'AuthIdentificationResponse' => $buypass->getAuthIdentificationResponse(),

                    //Unique value identifying the transaction. Used to identify the transaction for void/reversals
                    'ReferenceNumber'            => $buypass->getReferenceNumber(),

                    //Identifies the disposition of a message. Refer to Appendix F in https://drive.google.com/file/d/0B-NTHmk-nv8FRVZSelBERnh2Y0U/view
                    // 00 - Approved or completed successfully
                    // 11 - Approved (VIP)
                    // 10 - Approved, partial amount approved
                    'ResponseCode'               => $buypass->getResponseCode(),

                    //Identifies the transaction's total amount in US dollars. Format assumes 2 decimal points.
                    'TransactionAmount'          => $buypass->getTransactionAmount(),

                    'amount'                     => $amount
                );
            }else{
                //Result Code. Can have details of the error.
                $ResultCode = $buypass->getResultCode();

                //Identifies the disposition of a message. Refer to Appendix F in https://drive.google.com/file/d/0B-NTHmk-nv8FRVZSelBERnh2Y0U/view
                $ResponseCode = $buypass->getResponseCode();

                //Identifies a decline message, failed bit number, or auth telephone number. Present only for declines.
                $AdditionalResponseData = $buypass->getAdditionalResponseData();
                return array(
                    'error'  => true,
                    'detail' => '*Result Code: '.$ResultCode.' *Response Code: '.$ResponseCode.' *Additional Response Data: '.$AdditionalResponseData
                );
            }
        }catch(BuypassException $e){
            return array(
                'error'  => true,
                'detail' => $e
            );
        }
    }
}
?>