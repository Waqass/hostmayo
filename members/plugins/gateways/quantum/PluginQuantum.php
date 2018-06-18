<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'library/CE/XmlFunctions.php';

class PluginQuantum extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('Quantum Gateway')
                               ),
            lang('Use Verify By Visa and MasterCard SecureCode') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Be sure to have these services enabled in your gateway before attempting to use them.'),
                                'value'         =>0,
                               ),
            lang('Use DialVerify Service') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Be sure to have this service enabled in your gateway before attempting to use it.'),
                                'value'         =>0,
                               ),
            lang('Use Maxmind') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Use Quantum Gateway\'s Maxmind fraud screening services.'),
                                'value'         =>0,
                               ),
            lang('Quantum Gateway Username') => array (
                                'type'          =>'text',
                                'description'   =>lang('Please enter your Quantum Gateway Username Here.'),
                                'value'         =>''
                               ),
            lang('Quantum Gateway RestrictKey') => array (
                                'type'          =>'password',
                                'description'   =>lang('Please enter your Quantum Gateway RestrictKey Here.'),
                                'value'         =>''
                               ),
            lang('Accept CC Number') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information'),
                                'value'         =>'1'
                               ),
            lang('Visa') => array (
                                'type'          =>'yesno',
                                'description'   =>lang('Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type.'),
                                'value'         =>'1'
                               ),
            lang('MasterCard') => array (
                                'type'          =>'yesno',
                                'description'   =>lang('Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type.'),
                                'value'         =>'1'
                               ),
            lang('AmericanExpress') => array (
                                'type'          =>'yesno',
                                'description'   =>lang('Select YES to allow American Express card acceptance with this plugin. No will prevent this card type.'),
                                'value'         =>'1'
                               ),
            lang('Discover') => array (
                                'type'          =>'yesno',
                                'description'   =>lang('Select YES to allow Discover card acceptance with this plugin. No will prevent this card type.'),
                                'value'         =>'0'
                               ),
            lang('Invoice After Signup') => array (
                                'type'          =>'yesno',
                                'description'   =>lang('Select YES if you want an invoice sent to the customer after signup is complete.'),
                                'value'         =>'1'
                               ),
            lang('Signup Name') => array (
                                'type'          =>'text',
                                'description'   =>lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                                'value'         =>'Credit Card'
                               ),
            lang('Payment Form Title') => array(
                                'type'          =>'text',
                                'description'   =>lang('The Payment Form will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify.'),
                                'value'         =>''
                               ),
            lang('Payment Form Header') => array(
                                'type'          =>'textarea',
                                'description'   =>lang('The Payment Form will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify. HTML is accepted.'),
                                'value'         =>''
                               ),
            lang('Receipt Page Header') => array(
                                'type'          =>'textarea',
                                'description'   =>lang('The Receipt Page will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify. HTML is accepted.'),
                                'value'         =>''
                               ),
            lang('Receipt Page Footer') => array(
                                'type'          =>'textarea',
                                'description'   =>lang('The Receipt Footer will only be displayed when extra user input is required, i.e. when using Verified By Visa, MaterCard SecureCode or DialVerify. HTML is accepted.'),
                                'value'         =>''
                               ),
            lang('Dummy Plugin') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions'),
                                'value'         =>'0'
                               ),
            lang('Auto Payment') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('No description'),
                                'value'         =>'1'
                               ),
            lang('30 Day Billing') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals.'),
                                'value'         =>'0'
                               ),
           lang("Check CVV2") => array (
                                "type"          =>"yesno",
                                "description"   =>lang("Select YES if you want to accept CVV2 for this plugin."),
                                "value"         =>"1"
                               )
        );

        return $variables;
    }

    function singlepayment($params)
    {
        include_once 'library/CE/NE_Network.php';

        // VbV/MCSC and Dialverify (only used in signup) require some interaction from the user with the provider server,
        // so if those aren't set up then use autopayment which is silent.
        if (!$params['isSignup'] || (!$params['plugin_quantum_Use Verify By Visa and MasterCard SecureCode'] && !$params['plugin_quantum_Use DialVerify Service'])) {
            return $this->autopayment($params);
        }

        $params = CE_Lib::_array_map_recursive(array('CE_Lib', 'escapeHTMLAttributeValue'), $params);

        $callbackURL = mb_substr($params['clientExecURL'], -1, 1) == '//' ? $params['clientExecURL']."plugins/gateways/quantum/callback.php" : $params['clientExecURL']."/plugins/gateways/quantum/callback.php";
        $CCMo = mb_substr($params['userCCExp'], 0, 2);
        $CCYear = mb_substr($params['userCCExp'], 3);
        $useMaxmind = $params['plugin_quantum_Use Maxmind']? 1 : 2;

        $html = "<html>\n<head>\n</head>\n<body>\n"
               ."<form name=\"frmQuantumGateway\" action=\"https://secure.quantumgateway.com/cgi/clientexec.php\" method=\"post\">\n"
               ."<input type=\"hidden\" name=\"gwlogin\" value=\"{$params['plugin_quantum_Quantum Gateway Username']}\">\n"
               ."<input type=\"hidden\" name=\"RestrictKey\" value=\"{$params['plugin_quantum_Quantum Gateway RestrictKey']}\">\n"
               ."<input type=\"hidden\" name=\"post_return_url\" value=\"$callbackURL\">\n"
               ."<input type=\"hidden\" name=\"ResponseMethod\" value=\"GET\" />\n"
               ."<input type=\"hidden\" name=\"ID\" value=\"{$params['userID']}\">\n"
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
               ."<input type=\"hidden\" name=\"trans_method\" value=\"CC\">\n"
               ."<input type=\"hidden\" name=\"ccnum\" value=\"{$params['userCCNumber']}\">\n";

        // TAKEN FROM: http://www.quantumgateway.com/view_developer.php?Cat1=2
        if ($params['userCCCVV2'] != '') {
            // CVV - CVV2
            //     Security code on the back or front of the credit card
            $html .= "<input type=\"hidden\" name=\"CVV2\" value=\"{$params['userCCCVV2']}\">\n";

            // Type Of CVV2 being sent - CVVtype
            //     0 - Not Passing CVV2
            //     1 - CVV2 is being passed
            //     2 - CVV2 on card is unreadable
            //     9 - Card does not have CVV2 imprint
            $html .= "<input type=\"hidden\" name=\"CVVtype\" value=\"1\">\n";
        }

        $html .= "<input type=\"hidden\" name=\"ccmo\" value=\"$CCMo\">\n"
                ."<input type=\"hidden\" name=\"ccyr\" value=\"$CCYear\">\n"
                ."<input type=\"hidden\" name=\"invoice_num\" value=\"{$params['invoiceNumber']}\">\n"
                ."<input type=\"hidden\" name=\"invoice_description\" value=\"{$params['invoiceDescription']}\">\n"
                ."<input type=\"hidden\" name=\"MAXMIND\" value=\"$useMaxmind\">\n"
                ."<input type=\"hidden\" name=\"page_heading\" value=\"{$params['plugin_quantum_Payment Form Title']}\">\n"
                ."<input type=\"hidden\" name=\"payment_heading\" value=\"{$params['plugin_quantum_Payment Form Header']}\">\n"
                ."<input type=\"hidden\" name=\"header_receipt\" value=\"{$params['plugin_quantum_Receipt Page Header']}\">\n"
                ."<input type=\"hidden\" name=\"footer_receipt\" value=\"{$params['plugin_quantum_Receipt Page Footer']}\">\n"
                ."<script language=\"JavaScript\">\n"
                ."   document.forms['frmQuantumGateway'].submit();\n"
                ."</script>\n"
                ."</form>\n</body>\n</html>\n";

        echo $html;
        exit;
    }

    function autopayment($params)
    {
        include_once 'modules/billing/models/class.gateway.plugin.php';
        include_once 'library/CE/NE_Network.php';

        $requestArr = array(
            'gwlogin'               => $params['plugin_quantum_Quantum Gateway Username'],
            'trans_method'          => 'CC',
            'cust_id'               => $params['userID'],
            'customer_ip'           => CE_Lib::getRemoteAddr(),
            'invoice_num'           => $params['invoiceNumber'],
            'invoice_description'   => $params['invoiceDescription'],
            'ccnum'                 => $params['userCCNumber'],
            'ccmo'                  => mb_substr($params['userCCExp'], 0, 2),
            'ccyr'                  => mb_substr($params['userCCExp'], 3),
            'amount'                => $params['invoiceTotal'],
            'BADDR1'                => $params['userAddress'],
            'BCITY'                 => $params['userCity'],
            'BSTATE'                => $params['userState'],
            'BCOUNTRY'              => $params['userCountry'],
            'BZIP1'                 => $params['userZipcode'],
            'BCUST_EMAIL'           => $params['userEmail'],
            'phone'                 => $params['userPhone'],
            'RestrictKey'           => $params['plugin_quantum_Quantum Gateway RestrictKey'],
            'FNAME'                 => $params['userFirstName'],
            'LNAME'                 => $params['userLastName'],
            'MAXMIND'               => ($params['plugin_quantum_Use Maxmind'] && $params['isSignup'])? 1 : 2,
            'post_return_url'       => mb_substr($params['clientExecURL'], -1, 1) == '//' ? $params['clientExecURL']."plugins/gateways/quantum/callback.php" : $params['clientExecURL']."/plugins/gateways/quantum/callback.php",
            'ResponseMethod'        => 'GET',   // not sure this is needed here, but just in case...
        );

        $cPlugin = new Plugin($params['invoiceNumber'], "", $this->user);

        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $cPlugin->setAction('refund');
            $requestArr['trans_type'] = 'RETURN';
        } else {
            $isRefund = false;
            $cPlugin->setAction('charge');
        }

        $request = '';
        $firstIteration = true;
        foreach ($requestArr as $var => $value) {
            if (!$firstIteration) {
                $request .= '&';
            }
            $request .= "$var=".urlencode($value);
            $firstIteration = false;
        }

        $masks = array(
            'gwlogin=XXX_MASKED_XXX&'     => '/gwlogin=\w+&/',
            'RestrictKey=XXX_MASKED_XXX&' => '/RestrictKey=\w+&/',
            'ccnum=XXX_MASKED_XXX&'       => '/ccnum=\d+&/',
            'ccmo=XXX_MASKED_XXX&'        => '/ccmo=\d+&/',
            'ccyr=XXX_MASKED_XXX&'        => '/ccyr=\d+&/',
            'CVV2=XXX_MASKED_XXX&'        => '/CVV2=\d+&/'
        );

        $response = NE_Network::curlRequest($this->settings, 'https://secure.quantumgateway.com/cgi/clientexecT.php', $request, false, true, false, 'POST', false, $masks);

        // response is a "|"-separated string for payments, and just a string for refunds...
        if (!$isRefund) {
            $response = explode("|", trim($response));
            $transactionID = mb_substr($response[2], 1, -1);
        } else {
            $transactionID = $params['invoiceRefundTransactionId'];
        }

        $cPlugin->setAmount($params['invoiceTotal']);
        $cPlugin->setTransactionID($transactionID);

        if (!$isRefund && $response[0] != '"APPROVED"') {
            $cPlugin->setAction('charge');
            $cPlugin->PaymentRejected("Quantum Gateway payment of {$params['invoiceTotal']} was rejected.");
            return 'Payment rejected by credit card gateway provider';
        }

        if ($isRefund && strpos($response, 'APPROVED') === false) {
            $cPlugin->setAction('refund');
            $cPlugin->PaymentRejected("Quantum Gateway refund of {$params['invoiceTotal']} was rejected.");
            return 'Refund rejected by credit card gateway provider';
        }

        if ($isRefund) {
            $cPlugin->setAction('refund');
            $cPlugin->PaymentAccepted($params['invoiceTotal'], "Quantum Gateway refund of {$params['invoiceTotal']} was successfully processed.", $transactionID);
            return array('AMOUNT' => $params['invoiceTotal']);
        } else {
            $cPlugin->setAction('charge');
            $cPlugin->PaymentAccepted($params['invoiceTotal'], "Quantum Gateway payment of {$params['invoiceTotal']} was accepted.", $transactionID);
            return '';
        }
    }

    function credit($params)
    {
        $params['refund'] = true;
        return $this->autopayment($params);
    }

    function ShowTransactionDetails($params)
    {
        $GatewayKey = $params['plugin_quantum_Quantum Gateway RestrictKey'];

        $RequestType = "ShowTransactionDetails";
        $paramsArray = array(
                                "TransactionID"   => $params['TransactionID'],
                            );

        return $this->XMLrequest($params, $GatewayKey, $RequestType, $paramsArray);
    }

    function XMLrequest($params, $GatewayKey, $RequestType, $paramsArray)
    {
        $url = "https://secure.quantumgateway.com/cgi/xml_requester.php";

        $xml  = "<QGWRequest>\n";
        $xml .= "    <Authentication>\n";
        $xml .= "        <GatewayLogin>".$params['plugin_quantum_Quantum Gateway Username']."</GatewayLogin>\n";
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
}