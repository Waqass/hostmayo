<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'library/CE/XmlFunctions.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'library/CE/NE_Network.php';

class PluginQuantumvault extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
            lang('Plugin Name') => array (
                'type'        => 'hidden',
                'description' => lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                'value'       => lang('Quantum Vault Gateway')
            ),
            lang('Use Maxmind') => array(
                'type'        => 'yesno',
                'description' => lang('Use Quantum Vault Gateway\'s Maxmind fraud screening services.'),
                'value'       => 0,
            ),
            lang('Quantum Vault Gateway Username') => array (
                'type'        => 'text',
                'description' => lang('Please enter your Quantum Vault Gateway Username Here.'),
                'value'       => ''
            ),
            lang('Quantum Vault Gateway VaultKey') => array (
                'type'        => 'password',
                'description' => lang('Please enter your Quantum Vault Gateway VaultKey Here.'),
                'value'       => ''
            ),
            lang('Quantum Vault Gateway RestrictKey') => array (
                'type'        => 'password',
                'description' => lang('Please enter your Quantum Vault Gateway RestrictKey Here.'),
                'value'       => ''
            ),
            lang('Quantum Vault Gateway Inline Frame API Username') => array (
                'type'        => 'text',
                'description' => lang('Please enter your Quantum Vault Gateway Inline Frame API Username Here.'),
                'value'       => ''
            ),
            lang('Quantum Vault Gateway Inline Frame API Key') => array (
                'type'        => 'password',
                'description' => lang('Please enter your Quantum Vault Gateway Inline Frame API Key Here.'),
                'value'       => ''
            ),
            lang('Invoice After Signup') => array (
                'type'        => 'yesno',
                'description' => lang('Select YES if you want an invoice sent to the customer after signup is complete.'),
                'value'       => '1'
            ),
            lang('Signup Name') => array (
                'type'        => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'       => 'Credit Card'
            ),
            lang('Payment Form Title') => array(
                'type'        => 'text',
                'description' => lang('The Payment Form will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify.'),
                'value'       => ''
            ),
            lang('Payment Form Header') => array(
                'type'        => 'textarea',
                'description' => lang('The Payment Form will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify. HTML is accepted.'),
                'value'       => ''
            ),
            lang('Receipt Page Header') => array(
                'type'        => 'textarea',
                'description' => lang('The Receipt Page will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify. HTML is accepted.'),
                'value'       => ''
            ),
            lang('Receipt Page Footer') => array(
                'type'        => 'textarea',
                'description' => lang('The Receipt Footer will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify. HTML is accepted.'),
                'value'       => ''
            ),
            lang('Dummy Plugin') => array (
                'type'        => 'hidden',
                'description' => lang('1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions'),
                'value'       => '0'
            ),
            lang('Auto Payment') => array (
                'type'        => 'hidden',
                'description' => lang('No description'),
                'value'       => '1'
            ),
            lang('CC Stored Outside') => array (
                'type'        => 'hidden',
                'description' => lang('Is Credit Card stored outside of Clientexec? 1 = YES, 0 = NO'),
                'value'       => '1'
            ),
            lang('Iframe Configuration') => array (
                'type'        => 'hidden',
                'description' => lang('Parameters to be used in the iframe when loaded, like: width, height, scrolling, frameborder'),
                'value'       => 'width="100%" height="300" scrolling="auto" frameborder="0"'
            ),
            lang('30 Day Billing') => array (
                'type'        => 'hidden',
                'description' => lang('Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals.'),
                'value'       => '0'
            ),
            lang('Check CVV2') => array (
                'type'        => 'hidden',
                'description' => lang('Select YES if you want to accept CVV2 for this plugin.'),
                'value'       => '0'
            ),
            lang('Update Gateway') => array (
                'type'        => 'hidden',
                'description' => lang('1 = Create, update or remove Gateway customer information through the function UpdateGateway when customer choose to use this gateway, customer profile is updated, customer is deleted or customer status is changed. 0 = Do nothing.'),
                'value'       => '1'
            )
        );

        return $variables;
    }

    function singlepayment($params)
    {
        return $this->autopayment($params);
    }

    function autopayment($params)
    {

        $cPlugin = new Plugin($params['invoiceNumber'], "quantumvault", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);

        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $cPlugin->setAction('refund');
        } else {
            $isRefund = false;
            $cPlugin->setAction('charge');
        }

        $response = $this->ShowVaultDetails($params);

        if ($response && isset($response['QGWRequest']['#']['ResponseSummary'][0]['#']['Status'][0]['#'])) {
            if (!strcasecmp($response['QGWRequest']['#']['ResponseSummary'][0]['#']['Status'][0]['#'], 'Success')) {
                if ($response['QGWRequest']['#']['ResponseSummary'][0]['#']['ResultCount'][0]['#'] != 0) {
                    // CUSTOMER EXIST
                    return $this->useVault($params, $isRefund);
                } else {
                    // CUSTOMER DOES NOT EXIST. CREATE IT
                    return $this->useForm($params);
                }
            } else {
                $cPlugin->PaymentRejected($response['QGWRequest']['#']['ResponseSummary'][0]['#']['StatusDescription'][0]['#']);
                return 'There was an error in the gateway provider';
            }
        } else {
              $cPlugin->PaymentRejected($this->user->lang("There was not response from Quantum Vault. Please double check your information"));
              return $this->user->lang("There was not response from Quantum Vault. Please double check your information");
        }
    }

    function useVault($params, $isRefund)
    {
        if ($isRefund) {
            //  Process Single Credit/Debit Transactions
            //      Used to create an individual Debit/Credit Transaction.

            //      REQUEST PARAMETERS
            //          PARAMETER NAME        REQUIRED    TYPE        NOTES
            //          TransactionID         Special     (integer)   Required if ProcessType = VOID/PREVIOUS_SALE
            //          ProcessType           No          (string)    RETURN
            //                                                        AUTH_CAPTURE        Auth Then Sales
            //                                                        AUTH_ONLY           Auth only
            //                                                        SALES (default)     Skips AVS and CVV2
            //                                                        VOID                Requires TransactionID
            //                                                        PREVIOUS_SALE       Force An AUTH
            //          PaymentType           Yes         (string)    CC/EFT
            //          CreditCardNumber      No          (string)    Only required for PaymentType CC

            $GatewayKey = $params['plugin_quantumvault_Quantum Vault Gateway RestrictKey'];

            $RequestType = "ProcessSingleTransaction";
            $paramsArray = array(
                "TransactionID"     => $params['invoiceRefundTransactionId'],
                "ProcessType"       => (!isset($params['void']) || $params['void'] != true)? 'RETURN' : 'VOID',
                "PaymentType"       => 'CC',
                "CreditCardNumber"  => $params['userCCNumber'],
            );
        } else {
            //  Create Vault Debit/Credit Transaction
            //      Used to create an individual Debit or Credit Transaction from Vault Customers

            //      REQUEST PARAMETERS
            //          PARAMETER NAME        REQUIRED    TYPE        NOTES
            //          TransactionType       Yes         (string)    CREDIT for CC, DEBIT for EFT
            //          CustomerID            Yes         (string)    Identifies the vault customer
            //          Memo                  No          (string)    Customer Defined field
            //          Amount                Yes         (currency)
            //          TransactionDate       No          (datetime)  Defaults to transaction creation Date

            $GatewayKey = $params['plugin_quantumvault_Quantum Vault Gateway VaultKey'];

            $RequestType = "CreateTransaction";
            $paramsArray = array(
                "TransactionType" => 'CREDIT',
                "CustomerID"      => $params['userID'],
                "Memo"            => $this->user->lang("Invoice")." ".$params["invoiceNumber"],
                "Amount"          => $params['invoiceTotal'],
            );
        }

        $response = $this->XMLrequest($params, $GatewayKey, $RequestType, $paramsArray);

        $cPlugin = new Plugin($params["invoiceNumber"], "quantumvault", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);

        if ($isRefund) {
            $cPlugin->setAction('refund');
        } else {
            $cPlugin->setAction('charge');
        }

        if ($response && isset($response['QGWRequest']['#']['ResponseSummary'][0]['#']['Status'][0]['#'])) {
            if (!strcasecmp($response['QGWRequest']['#']['ResponseSummary'][0]['#']['Status'][0]['#'], 'Success')) {
                if (!strcasecmp($response['QGWRequest']['#']['Result'][0]['#']['Status'][0]['#'], 'APPROVED')) {
                    $cPlugin->setTransactionID($response['QGWRequest']['#']['Result'][0]['#']['TransactionID'][0]['#']);
                    $cPlugin->setAmount($response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']);
                    $cPlugin->setLast4($response['QGWRequest']['#']['Result'][0]['#']['CreditCardNumber'][0]['#']);

                    if ($response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'] == 0) {
                        if ($isRefund) {
                            $cPlugin->PaymentRejected("There was a Quantum Vault Gateway refund of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." processed.");
                            return 'There was a refund of '.$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'];
                        } else {
                            $cPlugin->PaymentRejected("There was a Quantum Vault Gateway payment of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." processed.");
                            return 'There was a payment of '.$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'];
                        }
                    } else {
                        if ($isRefund) {
                            $cPlugin->PaymentAccepted(
                                $response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'],
                                "Quantum Vault Gateway refund of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." was successfully processed.",
                                $response['QGWRequest']['#']['Result'][0]['#']['TransactionID'][0]['#']
                            );
                        } else {
                            $cPlugin->PaymentAccepted(
                                $response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'],
                                "Quantum Vault Gateway payment of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." was accepted.",
                                $response['QGWRequest']['#']['Result'][0]['#']['TransactionID'][0]['#']
                            );
                        }
                        return array('AMOUNT' => $response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']);
                    }
                } elseif (!strcasecmp($response['QGWRequest']['#']['Result'][0]['#']['Status'][0]['#'], 'DECLINED')) {
                    if ($isRefund) {
                        $cPlugin->PaymentRejected("Quantum Vault Gateway refund of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." was rejected.");
                        return 'Refund rejected by credit card gateway provider';
                    } else {
                        $cPlugin->PaymentRejected("Quantum Vault Gateway payment of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." was rejected.");
                        return 'Payment rejected by credit card gateway provider';
                    }
                } elseif (!strcasecmp($response['QGWRequest']['#']['Result'][0]['#']['Status'][0]['#'], 'VOIDED')) {
                    $cPlugin->setTransactionID($response['QGWRequest']['#']['Result'][0]['#']['TransactionID'][0]['#']);
                    $cPlugin->setAmount($response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']);
                    $cPlugin->setLast4($params['userCCNumber']);

                    if ($response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'] == 0) {
                        $cPlugin->PaymentRejected("There was a Quantum Vault Gateway refund of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." processed.");
                        return 'There was a refund of '.$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'];
                    } else {
                        $cPlugin->PaymentAccepted(
                            $response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#'],
                            "Quantum Vault Gateway refund of ".$response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']." was successfully processed.",
                            $response['QGWRequest']['#']['Result'][0]['#']['TransactionID'][0]['#']
                        );
                        return array('AMOUNT' => $response['QGWRequest']['#']['Result'][0]['#']['Amount'][0]['#']);
                    }
                } else {
                    $cPlugin->PaymentRejected($response['QGWRequest']['#']['Result'][0]['#']['StatusDescription'][0]['#']);
                    return 'There was an error in the gateway provider';
                }
            } else {
                if ($response['QGWRequest']['#']['ResponseSummary'][0]['#']['StatusDescription'][0]['#'] == 'Transaction has not settled.  Please void transaction.') {
                    $params['void'] = true;
                    return $this->autopayment($params);
                }
                $cPlugin->PaymentRejected($response['QGWRequest']['#']['ResponseSummary'][0]['#']['StatusDescription'][0]['#']);
                return 'There was an error in the gateway provider';
            }
        } else {
              $cPlugin->PaymentRejected($this->user->lang("There was not response from Quantum Vault. Please double check your information"));
              return $this->user->lang("There was not response from Quantum Vault. Please double check your information");
        }
    }

    function useForm($params)
    {
        $params = CE_Lib::_array_map_recursive(array('CE_Lib', 'escapeHTMLAttributeValue'), $params);

        $callbackURL = mb_substr($params['clientExecURL'], -1, 1) == '//' ? $params['clientExecURL']."plugins/gateways/quantumvault/callback.php" : $params['clientExecURL']."/plugins/gateways/quantumvault/callback.php";
        $useMaxmind = $params['plugin_quantumvault_Use Maxmind']? 1 : 2;

        $html = "<html>\n<head>\n</head>\n<body>\n"
            ."<form name=\"frmQuantumVaultGateway\" action=\"https://secure.quantumgateway.com/cgi/clientexec.php\" method=\"post\">\n"
            ."<input type=\"hidden\" name=\"gwlogin\" value=\"{$params['plugin_quantumvault_Quantum Vault Gateway Username']}\">\n"
            ."<input type=\"hidden\" name=\"RestrictKey\" value=\"{$params['plugin_quantumvault_Quantum Vault Gateway RestrictKey']}\">\n"
            ."<input type=\"hidden\" name=\"post_return_url\" value=\"$callbackURL\">\n"
            ."<input type=\"hidden\" name=\"ResponseMethod\" value=\"GET\" />\n"
            ."<input type=\"hidden\" name=\"ID\" value=\"{$params['userID']}\">\n"
            ."<input type=\"hidden\" name=\"cust_id\" value=\"{$params['userID']}\">\n"
            ."<input type=\"hidden\" name=\"customer_ip\" value=\"".CE_Lib::getRemoteAddr()."\">\n"
            ."<input type=\"hidden\" name=\"amount\" value=\"{$params['invoiceTotal']}\">\n"
            ."<input type=\"hidden\" name=\"FNAME\" value=\"{$params['userFirstName']}\">\n"
            ."<input type=\"hidden\" name=\"LNAME\" value=\"{$params['userLastName']}\">\n"
            ."<input type=\"hidden\" name=\"BADDR1\" value=\"{$params['userAddress']}\">\n"
            ."<input type=\"hidden\" name=\"BZIP1\" value=\"{$params['userZipcode']}\">\n"
            ."<input type=\"hidden\" name=\"BCITY\" value=\"{$params['userCity']}\">\n"
            ."<input type=\"hidden\" name=\"BCUST_EMAIL\" value=\"{$params['userEmail']}\">\n"
            ."<input type=\"hidden\" name=\"BSTATE\" value=\"{$params['userState']}\">\n"
            ."<input type=\"hidden\" name=\"BCOUNTRY\" value=\"{$params['userCountry']}\">\n"
            ."<input type=\"hidden\" name=\"phone\" value=\"{$params['userPhone']}\">\n"
            ."<input type=\"hidden\" name=\"invoice_num\" value=\"{$params['invoiceNumber']}\">\n"
            ."<input type=\"hidden\" name=\"invoice_description\" value=\"{$params['invoiceDescription']}\">\n"
            ."<input type=\"hidden\" name=\"MAXMIND\" value=\"$useMaxmind\">\n"
            ."<input type=\"hidden\" name=\"AddToVault\" value=\"Y\">\n"
            ."<input type=\"hidden\" name=\"skip_shipping_info\" value=\"Y\">\n"
            ."<input type=\"hidden\" name=\"page_heading\" value=\"{$params['plugin_quantumvault_Payment Form Title']}\">\n"
            ."<input type=\"hidden\" name=\"payment_heading\" value=\"{$params['plugin_quantumvault_Payment Form Header']}\">\n"
            ."<input type=\"hidden\" name=\"header_receipt\" value=\"{$params['plugin_quantumvault_Receipt Page Header']}\">\n"
            ."<input type=\"hidden\" name=\"footer_receipt\" value=\"{$params['plugin_quantumvault_Receipt Page Footer']}\">\n"
            ."<script language=\"JavaScript\">\n"
            ."   document.forms['frmQuantumVaultGateway'].submit();\n"
            ."</script>\n"
            ."</form>\n</body>\n</html>\n";

        echo $html;
        exit;
    }

    function credit($params)
    {
        $params['refund'] = true;
        return $this->autopayment($params);
    }

    function XMLrequest($params, $GatewayKey, $RequestType, $paramsArray)
    {
        $url = "https://secure.quantumgateway.com/cgi/xml_requester.php";

        $xml  = "<QGWRequest>\n";
        $xml .= "    <Authentication>\n";
        $xml .= "        <GatewayLogin>".$params['plugin_quantumvault_Quantum Vault Gateway Username']."</GatewayLogin>\n";
        $xml .= "        <GatewayKey>".$GatewayKey."</GatewayKey>\n";
        $xml .= "    </Authentication>\n";
        $xml .= "    <Request>\n";
        $xml .= "        <RequestType>".$RequestType."</RequestType>\n";
        foreach ($paramsArray as $paramName => $paramValue) {
            $xml .= "        <".$paramName;
            if ($paramValue != '') {
                $xml .= ">".$paramValue."</".$paramName.">\n";
            } else {
                $xml .= "/>\n";
            }
        }
        $xml .= "    </Request>\n";
        $xml .= "</QGWRequest>\n";

        /*$header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
        );*/

        $mask = array(
            '<GatewayLogin>XXX MASKED XXX</GatewayLogin>' => '/<GatewayLogin>(.*)<\/GatewayLogin>/',
            '<GatewayKey>XXX MASKED XXX</GatewayKey>'     => '/<GatewayKey>(.*)<\/GatewayKey>/'
        );
        $response = NE_Network::curlRequest($this->settings, $url, $xml, false, true, false, 'POST', false, $mask);

        if ($response) {
            $response = XmlFunctions::xmlize(
                str_replace(
                    array(
                        '&',
                        'á','é','í','ó','ú',
                        'Á','É','Í','Ó','Ú'
                    ),
                    array(
                        '&amp;',
                        'a','e','i','o','u',
                        'A','E','I','O','U'
                    ),
                    $response
                )
            );
        }

        return $response;
    }

    function ShowVaultDetails($params)
    {
        $GatewayKey = $params['plugin_quantumvault_Quantum Vault Gateway VaultKey'];

        $RequestType = "ShowVaultDetails";
        $paramsArray = array(
            "CustomerID" => $params['userID'],
        );

        return $this->XMLrequest($params, $GatewayKey, $RequestType, $paramsArray);
    }

    function ShowTransactionDetails($params)
    {
        $GatewayKey = $params['plugin_quantumvault_Quantum Vault Gateway RestrictKey'];

        $RequestType = "ShowTransactionDetails";
        $paramsArray = array(
            "TransactionID" => $params['TransactionID'],
        );

        return $this->XMLrequest($params, $GatewayKey, $RequestType, $paramsArray);
    }

    function ShowURL($params)
    {
        $CustomerID = $params['CustomerID'];
        $API_Username = $this->settings->get('plugin_quantumvault_Quantum Vault Gateway Inline Frame API Username');
        $API_Key = $this->settings->get('plugin_quantumvault_Quantum Vault Gateway Inline Frame API Key');
        $API_CustomerID = 'CE'.$CustomerID;

        include_once "PluginQuantumvaultIframe.php";

        $quantum = quantumilf_getCode($API_Username, $API_Key, '100%', '200', '0', '0', $API_CustomerID, 'CustomerEditPayment');

        return <<<IFRAME
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <title>Untitled Document</title>
                    {$quantum['script']}
                </head>
                <body>
                    {$quantum['iframe']}
                </body>
            </html>
IFRAME;
    }

    function UpdateGateway($params)
    {
        $params['plugin_quantumvault_Quantum Vault Gateway Username'] = $this->settings->get('plugin_quantumvault_Quantum Vault Gateway Username');
        switch ($params['Action']) {
            case 'update':  // When updating customer profile or changing to use this gateway
                $statusAliasGateway = StatusAliasGateway::getInstance($this->user);
                if (in_array($params['Status'], $statusAliasGateway->getUserStatusIdsFor(array(USER_STATUS_INACTIVE, USER_STATUS_CANCELLED, USER_STATUS_FRAUD)))) {
                    $this->CustomerRemove($params);
                } else {
                    $this->AddUpdateCustomer($params);
                }
                break;
            case 'delete':  // When deleting the customer or changing to use another gateway
                $this->CustomerRemove($params);
                break;
        }
    }

    function AddUpdateCustomer($params)
    {
        $GatewayKey = $this->settings->get('plugin_quantumvault_Quantum Vault Gateway VaultKey');

        $RequestType = "AddUpdateCustomer";
        $paramsArray = array(
            "CustomerID"   => 'CE'.$params['User ID'],
            "FirstName"    => $params['First Name'],
            "LastName"     => $params['Last Name'],
            "Address"      => $params['Address'],
            "City"         => $params['City'],
            "State"        => $params['State'],
            "ZipCode"      => $params['Zipcode'],
            "PhoneNumber"  => $params['Phone'],
            "EmailAddress" => $params['Email'],
        );

        return $this->XMLrequest($params, $GatewayKey, $RequestType, $paramsArray);
    }

    function CustomerRemove($params)
    {
        $GatewayKey = $this->settings->get('plugin_quantumvault_Quantum Vault Gateway VaultKey');

        $RequestType = "CustomerRemove";
        $paramsArray = array(
            "CustomerID" => 'CE'.$params['User ID'],
        );

        return $this->XMLrequest($params, $GatewayKey, $RequestType, $paramsArray);
    }
}