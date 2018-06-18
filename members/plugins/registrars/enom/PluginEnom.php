<?php
require_once 'modules/admin/models/RegistrarPlugin.php';
require_once 'modules/domains/models/ICanImportDomains.php';
require_once dirname(__FILE__).'/../../../library/CE/NE_Observable_Loggers.php';

/**
* @package Plugins
* @todo PUNY encoding
*/
class PluginEnom extends RegistrarPlugin implements ICanImportDomains
{

    // insert credentials and uncomment to update registrars table
    //$gSettingsArray['plugin_enom_Use testing server'] = true;
    //_plugin_enom_updateRegistrarsTable(array('uid' => '', 'pw' => ''));

    // Set a var for support of name suggest
    var $supportsNamesuggest = true;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('eNom')
                               ),
            lang('Use testing server') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use Enom\'s testing environment, so that transactions are not actually made. For this to work, you must first register you server\'s ip in Enom\'s testing environment, and your server\'s name servers must be registered there as well.'),
                                'value'         =>0
                               ),
            lang('Login') => array(
                                'type'          =>'text',
                                'description'   =>lang('Enter your username for your Enom reseller account.'),
                                'value'         =>''
                               ),
            lang('Password')  => array(
                                'type'          =>'password',
                                'description'   =>lang('Enter the password for your Enom reseller account.'),
                                'value'         =>'',
                                ),
            lang('Supported Features')  => array(
                                'type'          => 'label',
                                'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>* '.lang('Domain Registration with ID Protect').' <br>* '.lang('Existing Domain Importing').' <br>* '.lang('Get / Set Auto Renew Status').' <br>* '.lang('Get / Set DNS Records').' <br>* '.lang('Get / Set Nameserver Records').' <br>* '.lang('Get / Set Contact Information').' <br>* '.lang('Get / Set Registrar Lock').' <br>* '.lang('Initiate Domain Transfer').' <br>* '.lang('Automatically Renew Domain').' <br>* '.lang('Send Transfer Key') . '<br>* '.lang('NameSpinner'),
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
                                'value'         => 'Renew (Renew Domain),DomainTransferWithPopup (Initiate Transfer),SendTransferKey (Send Auth Info),Cancel',
                                ),
            lang('Registered Actions For Customer') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin (when a domain is registered)'),
                                'value'         => 'SendTransferKey (Send Auth Info)',
            )
        );

        return $variables;
    }

    function _getSupportedTLDs($params)
    {
        $arguments = array(
            'command'       => 'gettldlist',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
        );
        $response = $this->_makeRequest($params, $arguments);
        $tlds = array();
        foreach ($response['interface-response']['#']['tldlist'][0]['#']['tld'] as $value) {
            $tlds[] = $value['#']['tld'][0]['#'];
        }

        return $tlds;
    }

    // returns array(code [,message]), where code is:
    // 0:       Domain available
    // 1:       Domain already registered
    // 2:       Registrar Error, domain extension not recognized or supported
    // 3:       Domain invalid
    // 5:       Could not contact registry to lookup domain
    function checkDomain($params)
    {
        // array of full domains that we're returning, used for easy searching.
        $fullDomains = array();

        // the domains array in the format that CE expects to be returned.
        $domains = array();

        $arguments = array(
            'command'       => 'check',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'tld'           => $params['tld'],
            'sld'           => $params['sld'],
        );
        ;

        if (isset($params['namesuggest'])) {
            foreach ($params['namesuggest'] as $key => $value) {
                if ( $value == $params['tld']) {
                    unset($params['namesuggest'][$key]);
                    break;
                }
            }
            array_unshift ($params['namesuggest'], $params['tld']);
            $arguments['TLDList'] = implode(",", $params['namesuggest']);
        }

        $response = $this->_makeRequest($params, $arguments, true);

        if (!$response) {
            return array(5);
        }

        $err = $response['interface-response']['#']['ErrCount'][0]['#'];
        if ($err > 0) {
            return array(5, $response['interface-response']['#']['errors'][0]['#']['Err1'][0]['#']);
        }

        //oddly enom decides to return signle domain matches in a different node so let's copy to domain
        if (isset($response['interface-response']['#']['DomainName'])) {
            $response['interface-response']['#']['Domain'] = $response['interface-response']['#']['DomainName'];
        }

        foreach ($response['interface-response']['#']['Domain'] as $key => $domain) {

            //available?
            if (isset($response['interface-response']['#']['RRPCode'][$key]) && isset($response['interface-response']['#']['RRPCode'][$key]['#']) ) {
                $RRPCode = $response['interface-response']['#']['RRPCode'][$key]['#'];
            } else {
                $RRPCode = "";
            }

            switch ($RRPCode)
            {
                case "210":
                    $status = 0;
                    break;
                case "211":
                    $status = 1;
                    break;
                case "723":
                    $status = 2;
                    break;
                default:
                    $status = 2;
                    break;

            }
            $fullDomains[] = strtolower($domain['#']);
            $aDomain = DomainNameGateway::splitDomain($domain['#']);
            $domains[] = array("tld"=>$aDomain[1],"domain"=>$aDomain[0],"status"=>$status);
        }

        if ( $params['enableNamespinner'] == true ) {
            // we need to see if the domain exists in $domains already and not add it, if it does.

            $arguments = array(
                'command'       => 'GetNameSuggestions',
                'uid'           => $params['Login'],
                'pw'            => $params['Password'],
                'OnlyTldList'   => implode(',', $params['allAvailableTLDs']),
                'SearchTerm'    => $params['sld'],
            );

            $response = $this->_makeRequest($params, $arguments, true);
            $err = $response['interface-response']['#']['ErrCount'][0]['#'];
            if ($err > 0) {
                // if there's an error from name spinner, just return domains we already have.
                return array('result'=>$domains);
            }
            foreach ( $response['interface-response']['#']['DomainSuggestions'][0]['#']['Domain'] as $domain ) {
                $tmpFullDomain = $domain['@']['sld'] . '.' . $domain['@']['tld'];
                // the domain is already been checked, so ignore it here.
                if ( in_array(strtolower($tmpFullDomain), $fullDomains) ) {
                    continue;
                }

                if (strtolower(trim($domain['@']['in_ga'])) == 'true' && strtolower(trim($domain['@']['premium'])) == 'false') {

                    $domains[] = array(
                        'tld' => $domain['@']['tld'],
                        'domain' => $domain['@']['sld'],
                        'status' => 0 );
                }
            }
        }

        return array("result"=>$domains);
    }

    /**
     * Initiate a domain transfer
     *
     * @param array $params
     */
    function doDomainTransferWithPopup($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $transferid = $this->initiateTransfer($this->buildTransferParams($userPackage, $params));
        $userPackage->setCustomField("Registrar Order Id", $userPackage->getCustomField("Registrar").'-'.$transferid);
        $userPackage->setCustomField('Transfer Status', $transferid);
        return "Transfer of has been initiated.";
    }

    /**
     * Register domain name
     *
     * @param array $params
     */
    function doRegister($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->registerDomain($this->buildRegisterParams($userPackage, $params));
        $userPackage->setCustomField("Registrar Order Id", $userPackage->getCustomField("Registrar").'-'.$orderid);
        return $userPackage->getCustomField('Domain Name') . ' has been registered.';
    }

    /**
     * Renew domain name
     *
     * @param array $params
     */
    function doRenew($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->renewDomain($this->buildRenewParams($userPackage, $params));
        $userPackage->setCustomField("Registrar Order Id", $userPackage->getCustomField("Registrar").'-'.$orderid);
        return $userPackage->getCustomField('Domain Name') . ' has been renewed.';
    }

    function getTransferStatus($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $arguments = array(
            'command'                       => 'TP_GetOrder',
            'uid'                           => $params['Login'],
            'pw'                            => $params['Password'],
            'TransferOrderID'               => $userPackage->getCustomField('Transfer Status')
        );

        $response = $this->_makeRequest($params, $arguments);

        $status = $response['interface-response']['#']['transferorder'][0]['#']['transferorderdetail'][0]['#']['statusdesc'][0]['#'];

        // We are completed, so update our internal status so we don't try to check everytime.
        if ( $status == 'Transferred and paid successfully' ) {
            $userPackage->setCustomField('Transfer Status', 'Completed');
        }

        return $status;
    }

    // possible return values: array(code [,message])
    // -1:  error trying to transfer domain
    // 0:   domain not available
    // >0:  Operation successfull, returns orderid
    function initiateTransfer($params)
    {

        $arguments = array(
            'command'                       => 'TP_CreateOrder',
            'uid'                           => $params['Login'],
            'pw'                            => $params['Password'],
            'DomainCount'                   => 1,
            'AuthInfo1'                     => $params['eppCode'],
            'OrderType'                     => 'Autoverification',
            'tld1'                           => $params['tld'],
            'sld1'                           => $params['sld']
        );

        $response = $this->_makeRequest($params, $arguments);

        $order_id = $response['interface-response']['#']['transferorder'][0]['#']['transferorderid'][0]['#'];

        return $order_id;
    }

    // possible return values: array(code [,message])
    // -1:  error trying to renew domain
    // 0:   domain not available
    // >0:  Operation successfull, returns orderid
    function renewDomain($params)
    {
        $arguments = array(
            'command'                       => 'extend',
            'uid'                           => $params['Login'],
            'pw'                            => $params['Password'],
            'tld'                           => $params['tld'],
            'sld'                           => $params['sld'],
            'NumYears'                      => $params['NumYears']
        );

        $response = $this->_makeRequest($params, $arguments);

        // RRPCode not set when there's an error (except for case 540), thus the @
        $RRPCode = @$response['interface-response']['#']['RRPCode'][0]['#'];

        if ($RRPCode == 200 || $RRPCode == 1300) {
            $order_id = $response['interface-response']['#']['OrderID'][0]['#'];

            // Check if we have ID protect enabled for this domain, and purchase (extend) it at eNom if so.
            if ( isset($params['package_addons']['IDPROTECT']) && $params['package_addons']['IDPROTECT'] == 1 ) {
                $arguments = array(
                    'command'   => 'RenewServices',
                    'uid'       => $params['Login'],
                    'pw'        => $params['Password'],
                    'tld'       => $params['tld'],
                    'sld'       => $params['sld'],
                    'Service'   => 'WPPS'
                );
                $response = $this->_makeRequest($params, $arguments);
            }

            return $order_id;
        }

    }

    // possible return values: array(code [,message])
    // -1:  error trying to purchase domain
    // 0:   domain not available
    // >0:  Operation successfull, returns orderid
    function registerDomain($params)
    {
        // Check if renewname was passed to avoid any errors
        // Things like isset don't work here as they class =0 as not being set, so we check if the value
        // is 0 and supress any errors using @ and set to 1 if its not to be safe
        if (!@$params['renewname'] == 0) {
            $params['renewname'] = 1;
        }
        // make the arguments
        $arguments = array(
            'command'                       => 'purchase',
            'uid'                           => $params['Login'],
            'pw'                            => $params['Password'],
            'tld'                           => $params['tld'],
            'sld'                           => $params['sld'],
            'NumYears'                      => $params['NumYears'],
            'Renewname'                     => $params['renewname'], // Stop auto renewing the domain
            'RegistrantOrganizationName'    => $params['RegistrantOrganizationName'],
            'RegistrantFirstName'           => $params['RegistrantFirstName'],
            'RegistrantLastName'            => $params['RegistrantLastName'],
            'RegistrantEmailAddress'        => $params['RegistrantEmailAddress'],
            'RegistrantPhone'               => $this->_validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
            'RegistrantAddress1'            => $params['RegistrantAddress1'],
            'RegistrantCity'                => $params['RegistrantCity'],
            //'RegistrantStateProvinceChoice' => $params['RegistrantStateProvinceChoice'],
            'RegistrantStateProvince'       => $params['RegistrantStateProvince'],
            'RegistrantPostalCode'          => $params['RegistrantPostalCode'],
            'RegistrantCountry'             => $params['RegistrantCountry'],
        );

        //for .ca domains we need to pass the registration information for the tech and admin
        if ($params['tld'] == "ca" || $params['tld'] == "eu") {
            $moreArgs = array(
                'TechOrganizationName'    => $params['RegistrantOrganizationName'],
                'TechFirstName'           => $params['RegistrantFirstName'],
                'TechLastName'            => $params['RegistrantLastName'],
                'TechEmailAddress'        => $params['RegistrantEmailAddress'],
                'TechPhone'               => $this->_validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
                'TechAddress1'            => $params['RegistrantAddress1'],
                'TechCity'                => $params['RegistrantCity'],
                'TechStateProvince'       => $params['RegistrantStateProvince'],
                'TechPostalCode'          => $params['RegistrantPostalCode'],
                'TechCountry'             => $params['RegistrantCountry'],
                'AdminOrganizationName'    => $params['RegistrantOrganizationName'],
                'AdminFirstName'           => $params['RegistrantFirstName'],
                'AdminLastName'            => $params['RegistrantLastName'],
                'AdminEmailAddress'        => $params['RegistrantEmailAddress'],
                'AdminPhone'               => $this->_validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
                'AdminAddress1'            => $params['RegistrantAddress1'],
                'AdminCity'                => $params['RegistrantCity'],
                'AdminStateProvince'       => $params['RegistrantStateProvince'],
                'AdminPostalCode'          => $params['RegistrantPostalCode'],
                'AdminCountry'             => $params['RegistrantCountry'],
                'AuxBillingOrganizationName'    => $params['RegistrantOrganizationName'],
                'AuxBillingFirstName'           => $params['RegistrantFirstName'],
                'AuxBillingLastName'            => $params['RegistrantLastName'],
                'AuxBillingEmailAddress'        => $params['RegistrantEmailAddress'],
                'AuxBillingPhone'               => $this->_validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
                'AuxBillingAddress1'            => $params['RegistrantAddress1'],
                'AuxBillingCity'                => $params['RegistrantCity'],
                'AuxBillingStateProvince'       => $params['RegistrantStateProvince'],
                'AuxBillingPostalCode'          => $params['RegistrantPostalCode'],
                'AuxBillingCountry'             => $params['RegistrantCountry']
            );
            $arguments = array_merge($arguments, $moreArgs);
        }

        if (is_array($params['ExtendedAttributes'])) {
            foreach ($params['ExtendedAttributes'] as $name => $value) {
                $arguments[$name] = $value;
            }
        }

        if (isset($params['NS1'])) {
            // maximum 12 name servers are allowed by Enom
            for ($i = 1; $i <= 12; $i++) {
                if (isset($params["NS$i"])) {
                    $arguments["NS$i"] = $params["NS$i"]['hostname'];
                } else {
                    break;
                }
            }
        } else {
            // Try to grab the default from eNom, "usedns" means use THEIR DNS, not the custom ones in the eNom account...
            $dnsArgs = array (
                'command'   => 'GetCusPreferences',
                'uid'       => $params['Login'],
                'pw'        => $params['Password']
            );
            $response = $this->_makeRequest($params, $dnsArgs);

            $done = $response['interface-response']['#']['Done'][0]['#'];

            if ( $done == 'true' ) {
                if (  $response['interface-response']['#']['CustomerPrefs'][0]['#']['UseOurDNS'][0]['#'] == 'False' ) {
                    $i = 1;
                    foreach ( $response['interface-response']['#']['CustomerPrefs'][0]['#']['NameServers'][0]['#'] as $dns ) {
                        if ( isset($dns[0]['#']) ) {
                            $arguments["NS$i"] = $dns[0]['#'];
                            $i++;
                        } else {
                            break;
                        }
                    }
                } else {
                    $arguments['usedns'] = 'default';
                }
            }
        }

        // make the request to register the domain now
        $response = $this->_makeRequest($params, $arguments);

        // RRPCode not set when there's an error (except for case 540), thus the @
        $RRPCode = @$response['interface-response']['#']['RRPCode'][0]['#'];

        if ($RRPCode == 200 || $RRPCode == 1300) {
            $order_id = $response['interface-response']['#']['OrderID'][0]['#'];

            // Check if we have ID protect enabled for this domain, and purchase it at eNom if so.
            if ( isset($params['package_addons']['IDPROTECT']) && $params['package_addons']['IDPROTECT'] == 1 ) {
                $arguments = array(
                    'command'   => 'PurchaseServices',
                    'uid'       => $params['Login'],
                    'pw'        => $params['Password'],
                    'tld'       => $params['tld'],
                    'sld'       => $params['sld'],
                    'Service'   => 'WPPS',
                    'NumYears'  => $params['NumYears'],
                    'RenewName' => 1
                );
                $response = $this->_makeRequest($params, $arguments);
            }
            return $order_id;
        }
    }

    // called from outside CE once in a while
    function _plugin_enom_updateRegistrarsTable($params)
    {
        $returnMessages = '';

        $supportedTLDs = $this->_getSupportedTLDs($params);
        $returnMessages .= "This plugin supports ".count($supportedTLDs)." tlds\n\n";
        foreach ($supportedTLDs as $tld) {
            $returnMessages .= "Checking $tld\n";
            $arguments = array(
                'command'   => 'getExtAttributes',
                'uid'       => $params['Login'],
                'pw'        => $params['Password'],
                'tld'       => $tld,
            );
            $response = $this->_makeRequest($params, $arguments);
            $err = $response['interface-response']['#']['ErrCount'][0]['#'];
            if ($err > 0) {
                return array(5, $response['interface-response']['#']['errors'][0]['#']['Err1'][0]['#']);
            }

            if (!isset($response['interface-response']['#']['Attributes'][0]['#']['Attribute'])) {
                continue;
            }

            $extraAttributes = array();
            foreach ($response['interface-response']['#']['Attributes'][0]['#']['Attribute'] as $attribute) {
                if ($attribute['#']['Required'][0]['#'] == '0') {
                    continue;
                }
                if ($attribute['#']['Description'][0]['#'] == 'Country') {
                    // We're already sending the country in another field
                    continue;
                }
                $attributeName = $attribute['#']['Name'][0]['#'];
                $extraAttributes[$attributeName]['ID']           = $attribute['#']['ID'][0]['#'];
                $extraAttributes[$attributeName]['description']  = $attribute['#']['Description'][0]['#'];

                $extraAttributes[$attributeName]['options'] = array();
                if (@is_array($attribute['#']['Options'][0]['#']['Option'])) {
                    foreach ($attribute['#']['Options'][0]['#']['Option'] as $option) {
                        $extraAttributes[$attributeName]['options'][$option['#']['Title'][0]['#']] = array(
                            'description'  => $option['#']['Description'][0]['#'],
                            'value'        => $option['#']['Value'][0]['#'],
                        );
                        if (isset($option['#']['Requires'])) {
                            $extraAttributes[$attributeName]['options'][$option['#']['Title'][0]['#']]['requires'] = array();
                            foreach ($option['#']['Requires'] as $requirement) {
                                $extraAttributes[$attributeName]['options'][$option['#']['Title'][0]['#']]['requires'][] = $requirement['#']['Attribute'][0]['#']['ID'][0]['#'];
                            }
                        }
                    }
                }
            }

            if (!$extraAttributes) {
                continue;
            }

            $extraAttributes = serialize($extraAttributes);
            $extraAttributes = base64_encode($extraAttributes);

            $query = "REPLACE INTO `tld_extra_attributes` SET tld='$tld', extra_attributes='$extraAttributes'";
            $this->db->query($query) or die("Error in query $query");
        }

        return $returnMessages;
    }

    function _makeRequest($params, $arguments, $skiperrorchecking = false)
    {

        include_once 'library/CE/XmlFunctions.php';
        include_once 'library/CE/XmlFunctions.php';
        include_once 'library/CE/NE_Network.php';

        // default paramters
        if (!isset($params['secure'])) {
            $params['secure'] = true;
        }
        if (!isset($params['test'])) {
            $params['test'] = false;
        }

        if (@$this->settings->get('plugin_enom_Use testing server') ) {
            $params['secure'] = false;
        }

        if ($params['secure']) {
            $request = 'https://';
        } else {
            $request= 'http://';
        }

        if (@$this->settings->get('plugin_enom_Use testing server')) {
            $request .= 'resellertest.enom.com/interface.asp';
        } else {
            $request .= 'reseller.enom.com/interface.asp';
        }

        $arguments['responsetype'] = 'XML';

        $i = 0;
        foreach ($arguments as $name => $value) {
            $value = urlencode($value);
            if (!$i) {
                $request .= "?$name=$value";
            } else {
                $request .= "&$name=$value";
            }
            $i++;
        }

        CE_Lib::log(4, 'Enom Params: '.$request);
        //echo "$request\n";
        // certificate validation doesn't work well under windows
        $response = NE_Network::curlRequest($this->settings, $request, false, false, true);
        //echo $response;

        if (is_a($response, 'CE_Error')) {

            throw new CE_Exception ($response);
        }
        if (!$response) {
            return false;   // don't want xmlize an empty array
        }

        $response = XmlFunctions::xmlize($response);

        //some acitons might have custom error checking for specific error responses
        //but this should work for the majority of actions
        if (!$skiperrorchecking) {

            if (is_a($response, 'CE_Error')) {
                throw new CE_Exception("eNom Plugin Error: ".$response);
            }
            if (!is_array($response)) {
                throw new CE_Exception('eNom Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
            }

            //we should check errors here
            $err = $response['interface-response']['#']['ErrCount'][0]['#'];
            // RRPCode not set when there's an error (except for case 540), thus the @
            $RRPCode = @$response['interface-response']['#']['RRPCode'][0]['#'];

            if ($err > 0) {

                switch ($RRPCode) {
                    case 540:
                        throw new CE_Exception("eNom Plugin Error: Error performing operation: Domain not available");
                        break;
                    default:
                        $messages = "";
                        for ($i = 1; $i <= $err; $i++) {
                            if ( substr($response['interface-response']['#']['errors'][0]['#']["Err$i"][0]['#'], 0, 17) == 'Invalid client IP' ||
                                 substr($response['interface-response']['#']['errors'][0]['#']["Err$i"][0]['#'], 0, 39) == 'User not permitted from this IP address') {
                                $messages .= "Invalid IP Address.  Be sure to submit your servers IP to eNom in a support ticket.";
                                throw new CE_Exception("eNom Plugin Error: ".$messages, EXCEPTION_CODE_CONNECTION_ISSUE);
                            } else if ( substr($response['interface-response']['#']['errors'][0]['#']["Err$i"][0]['#'], 0, 13) == 'Bad User name' ) {
                                $messages .= "Invalid Username or Password.";
                                throw new CE_Exception("eNom Plugin Error: ".$messages, EXCEPTION_CODE_CONNECTION_ISSUE);
                            } else {
                                $messages .= $response['interface-response']['#']['errors'][0]['#']["Err$i"][0]['#'] . ' ' ;

                            }
                        }

                        break;
                }
                throw new CE_Exception("eNom Plugin Error: ". $messages);
            }
        } else {
            $err = $response['interface-response']['#']['ErrCount'][0]['#'];
            if ($err > 0) {
                $messages = "";
                for ($i = 1; $i <= $err; $i++) {
                    if ( substr($response['interface-response']['#']['errors'][0]['#']["Err$i"][0]['#'], 0, 17) == 'Invalid client IP' ||
                        substr($response['interface-response']['#']['errors'][0]['#']["Err$i"][0]['#'], 0, 39) == 'User not permitted from this IP address') {
                        CE_Lib::log(1, "eNom Error: Your IP has not been whitelisted for eNom's API.");
                    }
                }
            }
        }

        return $response;
    }

    function _validatePhone($phone, $country)
    {
        // strip all non numerical values
        $phone = preg_replace('/[^\d]/', '', $phone);

        if ($phone == '') {
            return $phone;
        }

        $query = "SELECT phone_code FROM country WHERE iso=? AND phone_code != ''";
        $result = $this->db->query($query, $country);
        if (!$row = $result->fetch()) {
            return $phone;
        }

        // check if code is already there
        $code = $row['phone_code'];
        $phone = preg_replace("/^($code)(\\d+)/", '+\1.\2', $phone);
        if ($phone[0] == '+') {
            return $phone;
        }

        // if not, prepend it
        return "+$code.$phone";
    }

    function getContactInformation($params)
    {

        $arguments = array(
            'command'       => 'getcontacts',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld']
        );
        $response = $this->_makeRequest($params, $arguments);

        $info = array();
        foreach (array('Registrant', 'AuxBilling', 'Admin', 'Tech') as $type) {
            $data =  $response['interface-response']['#']['GetContacts'][0]['#'][$type][0]['#'];
            if (is_array($data)) {
                $info[$type]['OrganizationName']  = array($this->user->lang('Organization'), $data[$type.'OrganizationName'][0]['#']);
                $info[$type]['JobTitle']  = array($this->user->lang('Job Title'), $data[$type.'JobTitle'][0]['#']);
                $info[$type]['FirstName'] = array($this->user->lang('First Name'), $data[$type.'FirstName'][0]['#']);
                $info[$type]['LastName']  = array($this->user->lang('Last Name'), $data[$type.'LastName'][0]['#']);
                $info[$type]['Address1']  = array($this->user->lang('Address').' 1', $data[$type.'Address1'][0]['#']);
                $info[$type]['Address2']  = array($this->user->lang('Address').' 2', $data[$type.'Address2'][0]['#']);
                $info[$type]['City']      = array($this->user->lang('City'), $data[$type.'City'][0]['#']);
                $info[$type]['StateProvChoice']  = array($this->user->lang('State or Province'), $data[$type.'StateProvinceChoice'][0]['#']);
                $info[$type]['StateProvince']  = array($this->user->lang('Province').'/'.$this->user->lang('State'), $data[$type.'StateProvince'][0]['#']);
                $info[$type]['Country']   = array($this->user->lang('Country'), $data[$type.'Country'][0]['#']);
                $info[$type]['PostalCode']  = array($this->user->lang('Postal Code'), $data[$type.'PostalCode'][0]['#']);
                $info[$type]['EmailAddress']     = array($this->user->lang('E-mail'), $data[$type.'EmailAddress'][0]['#']);
                $info[$type]['Phone']  = array($this->user->lang('Phone'), $data[$type.'Phone'][0]['#']);
                $info[$type]['PhoneExt']  = array($this->user->lang('Phone Ext'), $data[$type.'PhoneExt'][0]['#']);
                $info[$type]['Fax']       = array($this->user->lang('Fax'), $data[$type.'Fax'][0]['#']);
            } else {
                $info[$type] = array(
                    'OrganizationName'  => array($this->user->lang('Organization'), ''),
                    'JobTitle'          => array($this->user->lang('Job Title'), ''),
                    'FirstName'         => array($this->user->lang('First Name'), ''),
                    'LastName'          => array($this->user->lang('Last Name'), ''),
                    'Address1'          => array($this->user->lang('Address').' 1', ''),
                    'Address2'          => array($this->user->lang('Address').' 2', ''),
                    'City'              => array($this->user->lang('City'), ''),
                    'StateProvChoice'   => array($this->user->lang('State or Province'), ''),
                    'StateProvince'         => array($this->user->lang('Province').'/'.$this->user->lang('State'), ''),
                    'Country'           => array($this->user->lang('Country'), ''),
                    'PostalCode'        => array($this->user->lang('Postal Code'), ''),
                    'EmailAddress'      => array($this->user->lang('E-mail'), ''),
                    'Phone'             => array($this->user->lang('Phone'), ''),
                    'PhoneExt'          => array($this->user->lang('Phone Ext'), ''),
                    'Fax'               => array($this->user->lang('Fax'), ''),
                );
            }
        }
        return $info;
    }

    function setContactInformation($params)
    {
        $arguments = array(
            'command'       => 'contacts',
            'ContactType'   => strtoupper($params['type']),
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld']
        );
        foreach ($params as $key => $value) {
            if (strpos($key, $params['type']) !== false) {
                $arguments[str_replace('_', '', $key)] = $value;
            }
        }
        $response = $this->_makeRequest($params, $arguments);
        return $this->user->lang('Contact Information updated successfully.');
    }

    function getNameServers($params)
    {

        $arguments = array(
            'command'       => 'getdns',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld']
        );
        $response = $this->_makeRequest($params, $arguments);

        $info = array();
        if ($response['interface-response']['#']['UseDNS'][0]['#'] == 'default') {
            $info['usesDefault'] = true;
        } else {
            $info['usesDefault'] = false;
        }
        $info['hasDefault'] = true;

        if (!isset($response['interface-response']['#']['dns'])) {
            return $info;
        }

        $data =  $response['interface-response']['#']['dns'];
        if (is_array($data)) {
            foreach ($data as $ns) {
                $info[] = $ns['#'];
            }
        }
        return $info;
    }

    function setNameServers($params)
    {
        $arguments = array(
            'command'       => 'modifyns',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld'],
        );

        if ($params['default'] == true) {
            $arguments['usedns'] = 'default';
        } else {
            $arguments['usedns'] = '';
            foreach ($params['ns'] as $key => $value) {
                $arguments['ns'.$key] = $value;
            }
        }

        $response = $this->_makeRequest($params, $arguments);
        $response = $response['interface-response']['#'];

        return $response['RRPText'][0]['#'];
    }

    function checkNSStatus($params)
    {
        $arguments = array(
            'command'       => 'checknsstatus',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'checknsname'   => $params['check_ns']
        );

        $response = $this->_makeRequest($params, $arguments);

        $response = $response['interface-response']['#'];

        if ($response['NsCheckSuccess'][0]['#'] == 1) {
            $data = $response['CheckNsStatus'][0]['#'];
            return $this->_traverseStatus($data);
        } else {
            // Couldn't find any information about this domain.
            return ($this->user->lang('No Results Found.'));
        }
    }

    function registerNS($params)
    {
        $arguments = array(
            'command'       => 'registernameserver',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'add'           => 'true',
            'nsname'        => $params['nsname'],
            'ip'            => $params['nsip']
        );

        $response = $this->_makeRequest($params, $arguments);

        $response = $response['interface-response']['#'];

        return $this->user->lang('Name Server registered successfully.');
    }

    function editNS($params)
    {
        $arguments = array(
            'command'       => 'updatenameserver',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'NS'            => $params['nsname'],
            'OldIP'         => $params['nsoldip'],
            'NewIP'         => $params['nsnewip']
        );

        $response = $this->_makeRequest($params, $arguments);

        $response = $response['interface-response']['#'];

        return $this->user->lang('Name Server edited successfully.');
    }

    function deleteNS($params)
    {
        $arguments = array(
            'command'       => 'deletenameserver',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'ns'            => $params['nsname']
        );

        $response = $this->_makeRequest($params, $arguments);

        $response = $response['interface-response']['#'];

        return $this->user->lang('Name Server deleted successfully.');
    }

    function getGeneralInfo($params)
    {
        $arguments = array(
            'command'       => 'GetDomainInfo',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld']
        );

        $response = $this->_makeRequest($params, $arguments);

        $response = $response['interface-response']['#'];

        $response = $response['GetDomainInfo'][0]['#'];
        $data = array();
        $data['id'] = $response['domainname'][0]['@']['domainnameid'];
        $data['domain'] = $response['domainname'][0]['#'];
        $data['expiration'] = $response['status'][0]['#']['expiration'][0]['#'];
        $data['registrationstatus'] = $response['status'][0]['#']['registrationstatus'][0]['#'];
        $data['purchasestatus'] = $response['status'][0]['#']['purchase-status'][0]['#'];
        $data['is_registered'] = ( $response['status'][0]['#']['registrationstatus'][0]['#'] == 'Registered') ? true : false;
        $data['is_expired'] = ( $response['status'][0]['#']['registrationstatus'][0]['#'] == 'Expired') ? true : false;

        try {
            $arguments['command'] = 'GetRenew';
            $response = $this->_makeRequest($params, $arguments);
            if (!is_array($response)) {
                $data['autorenew'] = 0;
            } else {
                $response = $response['interface-response']['#'];
                $data['autorenew'] = $response['auto-renew'][0]['#'];
            }
        } catch ( Exception $e ) {
            $data['autorenew'] = 0;
        }
        return $data;
    }

    function fetchDomains($params)
    {
        $arguments = array(
            'command'       => 'GetDomains',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'Display'       => '100',
            'Start'         => $params['next']
        );

        $response = $this->_makeRequest($params, $arguments);

        $domainsList = array();
        if ($response['interface-response']['#']['GetDomains'][0]['#']['DomainCount'][0]['#'] > 0) {
            foreach ($response['interface-response']['#']['GetDomains'][0]['#']['domain-list'][0]['#']['domain'] as $domain) {
                $domain = $domain['#'];

                $data['id'] = $domain['DomainNameID'][0]['#'];
                $data['sld'] = $domain['sld'][0]['#'];
                $data['tld'] = $domain['tld'][0]['#'];
                $data['exp'] = isset($domain['expiration-date'][0]['#'])? $domain['expiration-date'][0]['#']: 'n/a';
                $domainsList[] = $data;
            }
        }
        $metaData = array();
        $metaData['total'] = $response['interface-response']['#']['GetDomains'][0]['#']['DomainCount'][0]['#'];
        $metaData['next'] = $response['interface-response']['#']['GetDomains'][0]['#']['NextRecords'][0]['#'];
        $metaData['start'] = $response['interface-response']['#']['GetDomains'][0]['#']['StartPosition'][0]['#'];
        $metaData['end'] = $response['interface-response']['#']['GetDomains'][0]['#']['EndPosition'][0]['#'];
        $metaData['numPerPage'] = 25;
        return array($domainsList, $metaData);

    }

    function disablePrivateRegistration($params)
    {
        $arguments = array(
            'command'       => 'SetRenew',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld'],
            'RenewFlag'     => 0,
            'WPPSRenew'     => 0
        );

        $response = $this->_makeRequest($params, $arguments);
        return "Domain updated successfully";
    }

    function setAutorenew($params)
    {
        $arguments = array(
            'command'       => 'SetRenew',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld'],
            'RenewFlag'     => $params['autorenew']
        );

        $response = $this->_makeRequest($params, $arguments);
        return "Domain updated successfully";
    }

    function getRegistrarLock($params)
    {
        $arguments = array(
            'command'       => 'GetRegLock',
            'uid'           => $params['Login'],
            'pw'            => $params['Password'],
            'sld'           => $params['sld'],
            'tld'           => $params['tld']
        );

        $response = $this->_makeRequest($params, $arguments);

        return $response['interface-response']['#']['reg-lock'][0]['#'];
    }

    function doSetRegistrarLock($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->setRegistrarLock($this->buildLockParams($userPackage, $params));
        return "Updated Registrar Lock.";
    }

    function setRegistrarLock($params)
    {
        $arguments = array(
            'command'           => 'SetRegLock',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'sld'               => $params['sld'],
            'tld'               => $params['tld'],
            'UnlockRegistrar'   => $params['lock']? 0 : 1 // opposite of what we store
        );

        $response = $this->_makeRequest($params, $arguments);
    }

    function doSendTransferKey($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->sendTransferKey($this->buildRegisterParams($userPackage, $params));
        return 'Successfully sent auth info for ' . $userPackage->getCustomField('Domain Name');
    }

    function sendTransferKey($params)
    {
        $arguments = array(
            'command'           => 'SynchAuthInfo',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'sld'               => $params['sld'],
            'tld'               => $params['tld'],
            'EmailEPP'          => 'True',
            'RunSynchAutoInfo'  => 'True'
        );

        $response = $this->_makeRequest($params, $arguments);

    }

    function getDNS($params)
    {
        $arguments = array(
            'command'           => 'GetHosts',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'sld'               => $params['sld'],
            'tld'               => $params['tld'],
        );

        $response = $this->_makeRequest($params, $arguments);
        $response = $response['interface-response']['#'];

        $records = array();
        if ( isset($response['host']) && count($response['host']) > 0 ) {
            foreach ($response['host'] as $value) {
                // enom returns blank results (why?)
                if ($value['#']['hostid'][0]['#'] == '') {
                    continue;
                }
                $record = array(
                    'id'            =>  $value['#']['hostid'][0]['#'],
                    'hostname'      =>  trim(preg_replace('(\(.*\))', '', $value['#']['name'][0]['#'])), // enom adds (all) and (none) which causes problems
                    'address'       =>  $value['#']['address'][0]['#'],
                    'type'          =>  $value['#']['type'][0]['#']);
                    $records[] = $record;
            }
        }
        $types = array('A', 'MXE', 'MX', 'CNAME', 'URL', 'FRAME', 'TXT');

        $arguments['command'] = 'getdns';
        $response = $this->_makeRequest($params, $arguments);

        if ($response['interface-response']['#']['UseDNS'][0]['#'] == 'default') {
            $default = true;
        } else {
            $default = false;
        }
        return array('records' => $records, 'types' => $types, 'default' => $default);
    }

    function setDNS($params)
    {
        $arguments = array(
            'command'           => 'SetHosts',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'sld'               => $params['sld'],
            'tld'               => $params['tld'],
        );

        $updateMailService = false;
        // build data to send to enom
        foreach ($params['records'] as $index => $record) {
            $index++; // enom starts at 1
            $arguments['HostName'.$index] = $record['hostname'];
            $arguments['RecordType'.$index] = $record['type'];
            $arguments['Address'.$index] = $record['address'];
            // We are setting MX records, so eNom requires us to enable mail settings first.
            if ( $record['type'] == 'MX' ) {
                $updateMailService = true;
            }
        }

        if ($updateMailService == true) {
            $argumentsMail = array(
                'command'           => 'ServiceSelect',
                'uid'               => $params['Login'],
                'pw'                => $params['Password'],
                'sld'               => $params['sld'],
                'tld'               => $params['tld'],
                'Service'           => 'EmailSet',
                'NewOptionID'       => 1054,
                'Update'            => 'true'
            );

            $response = $this->_makeRequest($params, $argumentsMail);
        }

        $response = $this->_makeRequest($params, $arguments);
        return ($this->user->lang("Host information updated successfully"));
    }

    /**
     * Internal recursive function for iterating over the name server status array.
     *
     * @param mixed $arr The data to iterate over
     * @return String The stringified version of the status.
     */
    function _traverseStatus($arr)
    {
        if (!is_array($arr)) {
            return $arr.'<br />';
        }
        $str = '';
        foreach ($arr as $key => $val) {
            if (is_array($val[0]['#'])) {
                $str .= $this->_traverseStatus($val[0]['#']);
            } else {
                $str .= $key.' = '.$val[0]['#'].'<br />';
            }
        }
        return $str;
    }
}