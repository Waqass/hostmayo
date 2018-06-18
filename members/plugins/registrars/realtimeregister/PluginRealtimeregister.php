<?php
require_once 'modules/admin/models/RegistrarPlugin.php';
require_once 'library/CE/NE_MailGateway.php';

class PluginRealtimeregister extends RegistrarPlugin
{
    var $api_url = "https://http.api.yoursrs.com/v1/";
    var $api_url_test = "https://http.api.yoursrs-ote.com/v1/";
    var $country_codes = array( 'US' => '1', 'CA' => '1', 'EG' => '20', 'MA' => '212', 'EH' => '212', 'DZ' => '213', 'TN' => '216', 'LY' => '218', 'GM' => '220', 'SN' => '221', 'MR' => '222', 'ML' => '223', 'GN' => '224', 'CI' => '225', 'BF' => '226', 'NE' => '227', 'TG' => '228', 'BJ' => '229', 'MU' => '230', 'LR' => '231', 'SL' => '232', 'GH' => '233', 'NG' => '234', 'TD' => '235', 'CF' => '236', 'CM' => '237', 'CV' => '238', 'ST' => '239', 'GQ' => '240', 'GA' => '241', 'CG' => '242', 'CD' => '243', 'AO' => '244', 'GW' => '245', 'IO' => '246', 'AC' => '247', 'SC' => '248', 'SD' => '249', 'RW' => '250', 'ET' => '251', 'SO' => '252', 'QS' => '252', 'DJ' => '253', 'KE' => '254', 'TZ' => '255', 'UG' => '256', 'BI' => '257', 'MZ' => '258', 'ZM' => '260', 'MG' => '261', 'RE' => '262', 'YT' => '262', 'ZW' => '263', 'NA' => '264', 'MW' => '265', 'LS' => '266', 'BW' => '267', 'SZ' => '268', 'KM' => '269', 'ZA' => '27', 'SH' => '290', 'TA' => '290', 'ER' => '291', 'AW' => '297', 'FO' => '298', 'GL' => '299', 'GR' => '30', 'NL' => '31', 'BE' => '32', 'FR' => '33', 'ES' => '34', 'GI' => '350', 'PT' => '351', 'LU' => '352', 'IE' => '353', 'IS' => '354', 'AL' => '355', 'MT' => '356', 'CY' => '357', 'FI' => '358', 'AX' => '358', 'BG' => '359', 'HU' => '36', 'LT' => '370', 'LV' => '371', 'EE' => '372', 'MD' => '373', 'AM' => '374', 'QN' => '374', 'BY' => '375', 'AD' => '376', 'MC' => '377', 'SM' => '378', 'VA' => '379', 'UA' => '380', 'RS' => '381', 'ME' => '382', 'HR' => '385', 'SI' => '386', 'BA' => '387', 'EU' => '388', 'MK' => '389', 'IT' => '39', 'VA' => '39', 'RO' => '40', 'CH' => '41', 'CZ' => '420', 'SK' => '421', 'LI' => '423', 'AT' => '43', 'GB' => '44', 'GG' => '44', 'IM' => '44', 'JE' => '44', 'DK' => '45', 'SE' => '46', 'NO' => '47', 'SJ' => '47', 'PL' => '48', 'DE' => '49', 'FK' => '500', 'BZ' => '501', 'GT' => '502', 'SV' => '503', 'HN' => '504', 'NI' => '505', 'CR' => '506', 'PA' => '507', 'PM' => '508', 'HT' => '509', 'PE' => '51', 'MX' => '52', 'CU' => '53', 'AR' => '54', 'BR' => '55', 'CL' => '56', 'CO' => '57', 'VE' => '58', 'GP' => '590', 'BL' => '590', 'MF' => '590', 'BO' => '591', 'GY' => '592', 'EC' => '593', 'GF' => '594', 'PY' => '595', 'MQ' => '596', 'SR' => '597', 'UY' => '598', 'AN' => '599', 'MY' => '60', 'AU' => '61', 'CX' => '61', 'CC' => '61', 'ID' => '62', 'PH' => '63', 'NZ' => '64', 'SG' => '65', 'TH' => '66', 'TL' => '670', 'NF' => '672', 'AQ' => '672', 'BN' => '673', 'NR' => '674', 'PG' => '675', 'TO' => '676', 'SB' => '677', 'VU' => '678', 'FJ' => '679', 'PW' => '680', 'WF' => '681', 'CK' => '682', 'NU' => '683', 'WS' => '685', 'KI' => '686', 'NC' => '687', 'TV' => '688', 'PF' => '689', 'TK' => '690', 'FM' => '691', 'MH' => '692', 'RU' => '7', 'KZ' => '7', 'XT' => '800', 'XS' => '808', 'JP' => '81', 'KR' => '82', 'VN' => '84', 'KP' => '850', 'HK' => '852', 'MO' => '853', 'KH' => '855', 'LA' => '856', 'CN' => '86', 'XN' => '870', 'PN' => '872', 'XP' => '878', 'BD' => '880', 'XG' => '881', 'XV' => '882', 'XL' => '883', 'TW' => '886', 'XD' => '888', 'TR' => '90', 'QY' => '90', 'IN' => '91', 'PK' => '92', 'AF' => '93', 'LK' => '94', 'MM' => '95', 'MV' => '960', 'LB' => '961', 'JO' => '962', 'SY' => '963', 'IQ' => '964', 'KW' => '965', 'SA' => '966', 'YE' => '967', 'OM' => '968', 'PS' => '970', 'AE' => '971', 'IL' => '972', 'PS' => '972', 'BH' => '973', 'QA' => '974', 'BT' => '975', 'MN' => '976', 'NP' => '977', 'XR' => '979', 'IR' => '98', 'XC' => '991', 'TJ' => '992', 'TM' => '993', 'AZ' => '994', 'QN' => '994', 'GE' => '995', 'KG' => '996', 'UZ' => '998' );

    var $supportsNamesuggest = false;

    function _getURL()
    {
        if ($this->settings->get("plugin_realtimeregister_TestMode") == 1) {
            return $this->api_url_test;
        }
        return $this->api_url;
    }

    function _sendRequest($url, $params)
    {
        $params['login_handle'] = $this->settings->get("plugin_realtimeregister_Dealer");
        $params['login_pass'] = $this->settings->get("plugin_realtimeregister_Password");

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        $cafile = __DIR__ . '/../../../library/cacert.pem';
        if (file_exists($cafile)) {
            curl_setopt($curl, CURLOPT_CAINFO, $cafile);
        }

        if ($this->settings->get("plugin_realtimeregister_TestMode") == 1) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
        }

        $result = curl_exec($curl);
        /* Could not connect to API, curl returned false */
        if ($result === false) {
            $curl_error = "Curl errno " . curl_errno($curl) . ": " . curl_error($curl);
            $msg = $this->_debug($url, $params, array("Could not connect to RealtimeRegister API.", $curl_error));
            curl_close($curl);
            return new CE_Error($msg, 132);
        }

        /* Try to decode the response */
        $response = json_decode($result);

        curl_close($curl);

        /* Response could not be decoded */
        if (!$response) {
            $msg = $this->_debug($url, $params, "Received invalid response. Please try again.");
            return new CE_Error($msg, 132);
        }

        /* An error occurred */
        if ($response->code >= 2000) {
            $error = $response->error;
            array_unshift($error, $response->msg);
            $msg = $this->_debug($url, $params, $error, $response);
            throw new Exception($msg);
        }

        /* Uncomment next line to debug ALL requests (even the successful ones) */
        //this->_debug($url, $params, str_replace($this->_getURL(), "", $url), $response);
        return $response;
    }

    function _he(&$value, $key) {
        $value = htmlentities($value);
    }

    function _debug($url, $params, $msg, $response = null) {
        if (!is_array($msg)) {
            $msg = array($msg);
        }

        if (isset($response->svTRID)) {
            $msg[] = "svTRID: " . $response->svTRID;
        }

        $subject = current($msg);
        array_walk($msg, array("PluginRealtimeregister", "_he"));
        $msg = implode("<br />\n", $msg);

        $message  = '<div style="background: #EFEAE8; border: 1px solid #DE510B; padding: 5px;">' . "\n";
        $message .= '<p style="font-size: 1.1em; color: #95310B; margin: 0px;">' . $msg . "</p>\n";

        if ($this->settings->get("plugin_realtimeregister_DebugMode") == 1) {
            if (isset($params['login_pass'])) {
                $params['login_pass'] = str_repeat("*", strlen($params['login_pass']));
            }

            $message .= "<p>Date/Time: " . date("d-m-Y H:i:s") . "</p>\n";
            $message .= "URL: " . htmlentities($url) . "<br />\n";
            $message .= "Params:<br />\n";
            $message     .= "<pre>" . htmlentities(print_r($params, true)) . "</pre>\n";

            if ($response) {
                $message .= "Response-code: " . htmlentities($response->code) . "<br />\n";
                $message .= "Response-msg: " . htmlentities($response->msg) . "<br />\n";
                if (count($response->error)) {
                    $message .= "Response-error(s):<br />\n";
                    $response_errors = $response->error;
                    array_walk($response_errors, array("PluginRealtimeregister", "_he"));
                    $message .= implode("<br />\n", $response_errors) . "<br />\n";
                }
                $message .= "<pre>" . htmlentities(print_r($response, true)) . "</pre>\n";
            }
        }
        $message .= "</div>\n";

        if ($this->settings->get("plugin_realtimeregister_DebugMode") == 1 && $this->settings->get("plugin_realtimeregister_DebugMail")) {
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            mail($this->settings->get("plugin_realtimeregister_DebugMail"), "[DEBUG] ClientExec " . $subject, $message, $headers);
        }
        return $message;
    }

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')=> array (
                'type'          =>'hidden',
                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                'value'         =>lang('Realtime Register'),
                ),
            lang('Dealer')     => array(
                'type'          => 'text',
                'description'   => lang('Enter your username for your Realtime Register dealer account.'),
                'value'         => '',
                ),
            lang('Password')   => array(
                'type'          => 'password',
                'description'   => lang('Enter your Realtime Register dealer password.'),
                'value'         => '',
                ),
            lang('Handle')     => array(
                'type'          => 'text',
                'description'   => lang('Enter your default contact handle name for Admin, Billing, Tech Contact.'),
                'value'         => '',
                ),
            lang('NS 1')     => array(
                'type'          => 'text',
                'description'   => lang('Enter Name Server #1, used in stand alone domains.'),
                'value'         => '',
                ),
            lang('NS 2')     => array(
                'type'          => 'text',
                'description'   => lang('Enter Name Server #1, used in stand alone domains.'),
                'value'         => '',
                ),
            lang('PhoneFormat')     => array(
                'type'          => 'text',
                'description'   => lang("The format of stored phone numbers.<br /><br />\n%c = country code (eg. 31 for NL)<br />\n%a = area code (eg. 38 for Zwolle)<br />\n%s = subscriber number (eg. 4530759)<br /><br />\nExample<br />\n+31 (0) 38 4530759 --> +%c (0) %a %s<br />"),
                'value'         => '',
                ),
            lang('TestMode')   => array(
                'type'          => 'yesno',
                'description'   => lang('Enable this to use the testing environment of Realtime Register.'),
                'value'         => '',
                ),
            lang('DebugMode')   => array(
                'type'          => 'yesno',
                'description'   => lang('Debug mode provides extensive information when an error occurs. You should only enable this for debugging purposes!'),
                'value'         => '',
                ),
            lang('DebugMail')   => array(
                'type'          => 'text',
                'description'   => lang('E-mail debug messages to this address.'),
                'value'         => '',
                ),
            lang('Supported Features')  => array(
                    'type'          => 'label',
                    'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>* '.lang('Get / Set Auto Renew Status').' <br>* '.lang('Get / Set Contact Information').' <br>* '.lang('Get Registrar Lock').' <br>* '.lang('Send Transfer Key'),
                    'value'         => ''
                    ),
            lang('Actions') => array (
                'type'          => 'hidden',
                'description'   => lang('Current actions that are active for this plugin (when a domain isn\'t registered)'),
                'value'         => 'Register'
                ),
            lang('Registered Actions') => array (
                'type'          => 'hidden',
                'description'   => lang('Current actions that are active for this plugin (when a domain is registered)'),
                'value'         => 'SendTransferKey (Send Auth Info),Cancel',
                )
            );
        return $variables;
    }

    /**
    * Method that communicates with the registrar API to find out if the domain name is available
    *
    * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld.
    * @return array (code [,message]), where code is:
    *			                       0:       Domain available
    *						           1:       Domain already registered
    *		                	       2:       Registrar Error, domain extension not recognized or supported
    *							       3:       Domain invalid
    *							       5:       Could not contact registry to lookup domain
    */
    function checkDomain( $params )
    {
        $domain = $params['sld'] . "." . $params['tld'];

        $curl_params = array();
        $curl_url = $this->_getURL() . "domains/" . urlencode($domain) . "/check";

        $response = $this->_sendRequest($curl_url, $curl_params);
        $status = '';

          if (is_a($response, "CE_Error")) {
            throw new Exception($response->errMessage);

            switch ($response->errCode) {
                case 132:
                case 1001:
                    CE_Lib::log(4, 'Error: ' . $response->errMessage);
                    $status = 5;
                default:
                    CE_Lib::log(4, 'Error: ' . $response->errMessage);
                    $status = 3;
            }
        }

        /* Check domain availability */
        if ($response->response->$domain->avail == 1) {
            $status = 0;
        } else if ($response->response->$domain->avail == 0) {
            $status = 1;
        }

        if (isset($response->response->$domain->reason)) {
            switch ($response->response->$domain->reason) {
                default:
                case "TLD not supported":
                    $status = 2;
                case "The URI does not meet the conditions of the tld" :
                    $status = 2;
            }
        }

        $domains[] = array("tld"=>$params['tld'],"domain"=>$params['sld'],"status"=>$status);\
        CE_Lib::log(4, $domains);
        return array("result"=>$domains);

    }



    /**
 * Communicates with the registrar API to retrieve the contact information for a given domain.
 *
 * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
 * @return array('type' => array(contactField => contactValue))
 */
function getContactInformation( $params )
{
    $curl_params = array();
    $domain = $params['sld'] . "." . $params['tld'];
    $curl_url_domain = $this->_getURL() . "domains/" . urlencode($domain) . "/info";

    $response = $this->_sendRequest($curl_url_domain, $curl_params);

    if (is_a($response, "CE_Error")) {
        return $response;
    }

    $types = array(
        'Registrant' => 'registrant',
        'AuxBilling' => 'billing',
        'Admin' => 'admin',
        'Tech' 	=> 'tech'
    );

    $info = array();

    foreach ($types as $type_ce => $type_srs) {
        $curl_url_contact = $this->_getURL() . "contacts/" . urlencode($response->response->$type_srs) . "/info";
        $response_contact = $this->_sendRequest($curl_url_contact, $curl_params);
        if (is_a($response_contact, "CE_Error")) {
            return $response;
        }

        $data = $response_contact->response;
        $info[$type_ce]['OrganizationName'] = array($this->user->lang('Organization'), $data->org);
        $info[$type_ce]['Name']	= array($this->user->lang('Name'), $data->name);
        $info[$type_ce]['Address1'] = array($this->user->lang('Address').' 1', array_shift($data->street));
        $info[$type_ce]['Address2'] = array($this->user->lang('Address').' 2',array_shift($data->street));
        $info[$type_ce]['Address3'] = array($this->user->lang('Address').' 3',array_shift($data->street));
        $info[$type_ce]['City']	 = array($this->user->lang('City'), $data->city);
        $info[$type_ce]['StateProv'] = array($this->user->lang('State') . ' / ' . $this->user->lang('Province'), $data->sp);
        $info[$type_ce]['Country'] = array($this->user->lang('Country'), $data->cc);
        $info[$type_ce]['PostalCode'] = array($this->user->lang('Postal Code'),	$data->pc);
        $info[$type_ce]['EmailAddress'] = array($this->user->lang('E-mail'), $data->email);
        $info[$type_ce]['Phone'] = array($this->user->lang('Phone'), $data->voice);
        $info[$type_ce]['Fax']	 = array($this->user->lang('Fax'), $data->fax);
    }
    return $info;
}

    /**
     * Communicates with the registrar API to retrieve general information for a given domain.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return array(id,domain,expiration,registrationstatus,purchasestatus,autorenew)
     */
    function getGeneralInfo( $params )
    {
        $domain = $params['sld'] . "." . $params['tld'];
        $curl_params = array();
        $curl_url = $this->_getURL() . "domains/" . urlencode($domain) . "/info";

        $response = $this->_sendRequest($curl_url, $curl_params);

        if (is_a($response, "CE_Error")) {
            return $response;
        }

        // TODO: return correct purchasestatus
        return array(
            'id' => $response->response->roid,
            'domain' => $domain,
            'expiration' => date('Y-m-d', $response->response->exDate),
            'registrationstatus' => $response->response->status,
            'purchasestatus' => "",
            'autorenew' => $response->response->renew
        );
    }

    /**
     * Communicates with the registrar API to retrieve the dns information for a given domain.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return array('type' => array(contactField => contactValue))
     */
    function getNameServers( $params )
    {
        $domain = $params['sld'] . "." . $params['tld'];
        $curl_params = array();
        $curl_url = $this->_getURL() . "domains/" . urlencode($domain) . "/info";

        $response = $this->_sendRequest($curl_url, $curl_params);

        if (is_a($response, "CE_Error")) {
            return $response;
        }

        $info = array();
        $info['usesDefault'] = false;
        $info['hasDefault'] = false;

        foreach ($response->response->ns as $key => $ns) {
            $info[$key+1] = $ns->host;
        }
        return $info;
    }

    /**
     * Communicates with the registrar API to retrieve the registrar lock information for a given domain.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return boolean
     */
    function getRegistrarLock( $params )
    {
        $domain = $params['sld'] . "." . $params['tld'];
        $curl_params = array();
        $curl_url = $this->_getURL() . "domains/" . urlencode($domain) . "/info";

        $response = $this->_sendRequest($curl_url, $curl_params);

        if (is_a($response, "CE_Error")) {
            return $response;
        }

        if (isset($response->response->lock)) {
            return true;
        } else {
            return false;
        }
    }

    function _convertPhoneNumber($phone, $country, $format = null) {
        if (!$format) {
            return $phone;
        }

        $regex          = $format;
        $escape_chars   = '\()[]{|.?^$+*';

        for ($pos = 0; $pos < strlen($escape_chars); $pos++) {
            $regex = str_replace($escape_chars[$pos], "\\" . $escape_chars[$pos], $regex);
        }

        $country_code = $this->country_codes[strtoupper($country)];

        if ($country_code) {
            if (mb_substr_count($regex, "%c")) {
                $regex = str_replace("%c", "(?<country_code>" . $country_code . ")", $regex);
            }
        } else {
            $regex = str_replace("%c", "(?<country_code>[1-9][0-9]{0,2})", $regex);
        }

        $regex = str_replace("%a", "(?<area_code>[1-9][0-9]{0,6})", $regex);
        $regex = str_replace("%s", "(?<subscriber_number>[1-9][0-9 ]{2,15})", $regex);
        $regex = "/" . $regex . "/";

        preg_match($regex, $phone, $m);

        if (!isset($m['country_code']) && $country_code) {
            $m['country_code'] = $country_code;
        }

        if (!isset($m['area_code'])) {
            $m['area_code'] = "";
        }

        $strip_chars = " ";

        for ($pos = 0; $pos < strlen($strip_chars); $pos++) {
            $m['subscriber_number'] = str_replace($strip_chars[$pos], "", $m['subscriber_number']);
        }

        if (!$m['country_code'] || !$m['subscriber_number']) {
            return $phone;
        }

        $phone = "+" . $m['country_code'] . "." . $m['area_code'] . $m['subscriber_number'];

        return $phone;
    }


    /**
    * Register domain name
    *
    * @param array $params
    */
    function doRegister($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
        $userPackage->setCustomField("Registrar Order Id",$userPackage->getCustomField("Registrar").'-'.$orderid);
        return $userPackage->getCustomField('Domain Name') . ' has been registered.';
    }

    /**
     * Communicates with the registrar API to carry out the domain name registration.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld, NumYears, RegistrantOrganizationName, RegistrantFirstName, RegistrantLastName, RegistrantEmailAddress, RegistrantPhone, RegistrantAddress1, RegistrantCity, RegistrantProvince, RegistrantPostalCode, RegistrantCountry, DomainPassword, ExtendedAttributes, NSx (list of nameservers if set, and usedns
     * @return array(code [,message]) -1: error trying to purchase domain 0: domain not available >0: Operation successfull, returns orderid
     */
    function registerDomain( $params )
    {
        $domain         = $params['sld'] . "." . $params['tld'];
        $curl_url       = $this->_getURL() . "domains/" . urlencode($domain) . "/create";
        $curl_params = array();

        for ($i = 1; $i <= 6; $i++) {
            if (!empty($params['NS'.$i])) {
                $curl_params['ns'][] = array("host" => $params['NS'.$i]['hostname'], "addr" => $params['NS'.$i]['ip']);
            }
        }

        // stand alone doamins, use default provided in the registrar plugin settings.
        if ( !isset($curl_params['ns']) ) {
            $curl_params['ns'][] = array("host" => $this->settings->get("plugin_realtimeregister_NS 1"));
            $curl_params['ns'][] = array("host" => $this->settings->get("plugin_realtimeregister_NS 2"));
        }

        $country = ($params['RegistrantCountry'] == "UK" ? "GB" : $params['RegistrantCountry']);
        $phone = $this->_convertPhoneNumber($params['RegistrantPhone'], $country, $params['PhoneFormat']);

        $curl_params['contact_data']['registrant']  = array(
            'email' => utf8_encode($params['RegistrantEmailAddress']),
            'name' => utf8_encode($params['RegistrantFirstName'] . " " . $params['RegistrantLastName']),
            'org' => utf8_encode($params['RegistrantOrganizationName']),
            'street' => array(utf8_encode($params['RegistrantAddress1'])),
            'city' => utf8_encode($params['RegistrantCity']),
            'sp' => utf8_encode($params['RegistrantStateProvince']),
            'pc' => utf8_encode($params['RegistrantPostalCode']),
            'cc' => utf8_encode($country),
            'voice' => utf8_encode($phone)
        );

        $curl_params['admin'] = $params['Handle'];
        $curl_params['billing'] = $params['Handle'];
        $curl_params['tech'] = $params['Handle'];

        $response = $this->_sendRequest($curl_url, $curl_params);

        if (is_a($response, "CE_Error")) {
            throw new Exception($response->errMessage);
        }
        return array(1);
    }

    function doSendTransferKey($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->sendTransferKey($this->buildRegisterParams($userPackage,$params));
        return 'Successfully sent auth info for ' . $userPackage->getCustomField('Domain Name');
    }

    /**
     * Communicates with the registrar API to send the transfer key to registrant.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return CE_Error on failure
     */
    function sendTransferKey( $params )
    {
        $domain = $params['sld'] . "." . $params['tld'];
        $curl_params = array();
        $curl_url = $this->_getURL() . "domains/" . urlencode($domain) . "/info";

        $response = $this->_sendRequest($curl_url, $curl_params);

        if (is_a($response, "CE_Error")) {
            throw new Exception($response->errMessage);
        }

        $curl_url_contact = $this->_getURL() . "contacts/" . urlencode($response->response->registrant) . "/info";
        $response_contact = $this->_sendRequest($curl_url_contact, $curl_params);

        if (is_a($response_contact, "CE_Error")) {
            throw new Exception($response->errMessage);
        }

        $message = "Transfer key for '" . htmlentities($domain) . "': " . htmlentities($response->response->pw);

        $from = $this->settings->get('Support Email');
        $mailGateway = new NE_MailGateway();
        $mailGateway->mailMessageEmail(
            $message,
            $from,
            $from,
            $response_contact->response->email,
            '',
            "Transfer key for " . $domain
        );
    }


    /**
     * Communicates with the registrar API to set autorenew for a given domain.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return CE_Error on failure
     */
    function setAutorenew( $params )
    {
        $curl_params	= array();
        $domain			= $params['sld'] . "." . $params['tld'];
        $curl_url_domain = $this->_getURL() . "domains/" . urlencode($domain) . "/update";

        $curl_params['autorenew'] = ($params['autorenew'] ? true : false);

        $response = $this->_sendRequest($curl_url_domain, $curl_params);

        if (is_a($response, "CE_Error")) {
            throw new Exception($response->errMessage);
        }
    }

    /**
     * Communicates with the registrar API to retrieve the contact information for a given domain.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return array('type' => array(contactField => contactValue))
     */
    function setContactInformation( $params )
    {
        $curl_params = array();
        $domain = $params['sld'] . "." . $params['tld'];
        $curl_url_domain = $this->_getURL() . "domains/" . urlencode($domain) . "/info";
        $response = $this->_sendRequest($curl_url_domain, $curl_params);

        if (is_a($response, "CE_Error")) {
            throw new Exception($response->errMessage);
        }

        $types = array(
            'Registrant' => 'registrant',
            'AuxBilling' => 'billing',
            'Admin' => 'admin',
            'Tech' => 'tech'
        );

        $type = $types[$params['type']];

        $curl_url_contact = $this->_getURL() . "contacts/" . urlencode($response->response->$type) . "/update";

        $curl_params['org'] = utf8_encode($params[$params['type'] . "_OrganizationName"]);
        $curl_params['name'] = utf8_encode($params[$params['type'] . "_Name"]);
        $curl_params['street'] = array(	utf8_encode($params[$params['type'] . "_Address1"]) );
        $curl_params['city'] = utf8_encode($params[$params['type'] . "_City"]);
        $curl_params['sp'] = utf8_encode($params[$params['type'] . "_StateProv"]);
        $curl_params['cc'] = utf8_encode($params[$params['type'] . "_Country"]);
        $curl_params['pc'] = utf8_encode($params[$params['type'] . "_PostalCode"]);
        $curl_params['email'] = utf8_encode($params[$params['type'] . "_EmailAddress"]);
        $curl_params['voice'] = utf8_encode($params[$params['type'] . "_Phone"]);
        $curl_params['fax'] = utf8_encode($params[$params['type'] . "_Fax"]);

        $response_contact = $this->_sendRequest($curl_url_contact, $curl_params);

        if (is_a($response_contact, "CE_Error")) {
            throw new Exception($response_contact->errMessage);
        }
    }

    /**
     * Communicates with the registrar API to retrieve the dns information for a given domain.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return array('type' => array(contactField => contactValue))
     */
    function setNameServers( $params )
    {
        $domain = $params['sld'] . "." . $params['tld'];
        $curl_params = array();
        $curl_url = $this->_getURL() . "domains/" . urlencode($domain) . "/update";

        foreach ($params['ns'] as $ns) {
            $curl_params['ns'][] = array("host" => $ns);
        }
        $response = $this->_sendRequest($curl_url, $curl_params);

        if (is_a($response, "CE_Error")) {
            throw new Exception($response->errMessage);
        }
    }

    /**
     * Communicates with the registrar API to retrieve the dns information for a given domain.
     *
     * @param array $params: Contains the values for the variables defined in getVariables(), plus: tld, sld
     * @return CE_Error on failure
     */
    function setRegistrarLock( $params )
    {
        $domain = $params['sld'] . "." . $params['tld'];
        $curl_params = array();
        $curl_url = $this->_getURL() . "domains/" . urlencode($domain) . "/update";
        $curl_params['lock'] = ($params['lock'] ? true : false);

        $response = $this->_sendRequest($curl_url, $curl_params);

        if (is_a($response, "CE_Error")) {
            throw new Exception($response->errMessage);
        }
    }

    function disablePrivateRegistration($parmas) { throw new MethodNotImplemented('Method disablePrivateRegistration has not been implemented yet.'); }
    function getTransferStatus($params) { throw new MethodNotImplemented('Method getTransferStatus has not been implemented yet.'); }
    function checkNSStatus( $params ) { throw new Exception('This function is not supported'); }
    function deleteNS( $params ) { throw new Exception('This function is not supported'); }
    function editNS( $params ) { throw new Exception('This function is not supported'); }
    function fetchDomains( $params ) { throw new Exception('This function is not supported'); }
    function registerNS( $params ) { throw new Exception('This function is not supported'); }
    function getDNS($params) { throw new Exception('This function is not supported', EXCEPTION_CODE_NO_EMAIL); }
    function setDNS($params) { throw new Exception('This function is not supported'); }

}
if (!function_exists("json_encode")) {

    function json_encode($data) {
        require_once 'library/CE/Services/JSON.php';

        $json = new Services_JSON();
        return $json->encode($data);
    }
}

if (!function_exists("json_decode")) {

    function json_decode($data) {
        require_once 'library/CE/Services/JSON.php';

        $json = new Services_JSON();
        return $json->decode($data);
    }
}
