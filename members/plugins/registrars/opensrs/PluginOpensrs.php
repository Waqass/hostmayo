<?php
/**
 * OpenSRS Domain Registrar Plugin
 *
 * @category Plugin
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  2
 * @link     http://www.clientexec.com
 */

require_once 'modules/admin/models/RegistrarPlugin.php';
require_once 'modules/domains/models/ICanImportDomains.php';
require_once dirname(__FILE__).'/class.opensrs.php';

/**
 * PluginOpensrs RegistrarPlugin Class
 *
 * TODO FUNCTIONS
 *
 * @category Plugin
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  2
 * @link     http://www.clientexec.com
 */
class PluginOpensrs extends RegistrarPlugin implements ICanImportDomains
{

    var $liveHost = "rr-n1-tor.opensrs.net";
    var $testHost = "horizon.opensrs.net";
    var $connPort = "55443";

    // Set a var for support of name suggest
    var $supportsNamesuggest = true;

    /**
     * Function to return a list of configurable variables for the class
     *
     * @return array
     */
    function getVariables()
    {
        $variables = array(
                lang('Plugin Name') => array (
                        'type'          =>'hidden',
                        'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                        'value'         =>lang('OpenSRS')
                ),
                lang('Use testing server') => array(
                        'type'          =>'yesno',
                        'description'   =>lang('Select Yes if you wish to use OpenSRS\'s testing environment, so that transactions are not actually made.'),
                        'value'         => 0
                ),
                lang('Username') => array(
                        'type'          => 'text',
                        'description'   => lang('Enter your username for your OpenSRS reseller account.<br/>'),
                        'value'         => '',
                ),
                lang('Private Key')  => array(
                        'type'          => 'text',
                        'description'   => lang('Enter your OpenSRS reseller private key.'),
                        'value'         => '',
                ),
                lang('Supported Features')  => array(
                        'type'          => 'label',
                        'description'   => '* '.lang('TLD Lookup, with Name Suggestions').'<br>* '.lang('Domain Registration').' <br>* '.lang('Domain Registration with ID Protect').' <br>* '.lang('Existing Domain Importing').' <br>* '.lang('Get / Set Auto Renew Status').' <br>* '.lang('Get / Set Nameserver Records').' <br>* '.lang('Get / Set Contact Information').' <br>* '.lang('Get / Set Registrar Lock').' <br>* '.lang('Initiate Domain Transfer').' <br>* '.lang('Automatically Renew Domain').' <br>',
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
                                'value'         => 'Renew (Renew Domain),DomainTransfer (Initiate Transfer),SendTransferKey (Send Auth Info),Cancel',
                                ),
              lang('Registered Actions For Customer') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin (when a domain is registered)'),
                                'value'         => 'SendTransferKey (Send Auth Info)',
            )
        );

        return $variables;
    }

    /**
     * Function to check if the given domain is available to be registered.
     *
     * This will eventually allow support for the name lookup command to return an array of available domains by suggestion
     *
     * @return array
     */
    function checkDomain($params)
    {
        // Calculate the host
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        CE_Lib::log(4, "Using $host");

        // Start the connection
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->logger, $this->user);

        // Check if we were passed any additional TLD's
        if(!@$params['namesuggest']) {
            $params['namesuggest'] = array();
            $namesuggest = false;
        }

        // Perform the lookup
        $return = $opensrs->lookup_domain(strtolower($params['sld']), strtolower($params['tld']), $params['namesuggest']);

        // Check the reply
        if ($return == null) {
            CE_Lib::log(1, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
            throw new Exception("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
        }

        // Check we had a succcess code
        if ($return['status']['response_code'] != 200) {
            CE_Lib::log(1, "OpenSRS Lookup Failed: ".$return['status']['response_text']);
            throw new Exception("OpenSRS Lookup Failed: ".$return['status']['response_text']);
        }
        unset($return['status']);
        //TODO we need to ensure that this is the array returned by checkDomain
        //since we can pass namesuggest we might get back a list of domains
        //for each domain let's add to domains array
        //$aDomain = DomainNameGateway::splitDomain($domainName);
        //$domains[] = array("tld"=>$aDomain[1],"domain"=>$aDomain[0],"status"=>$status);
        //return array("results"=>$domains);

        return $return;
    }

    /**
     * Renew domain name
     *
     * @param array $params
     */
    function doRenew($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->renewDomain($this->buildRenewParams($userPackage,$params));
        $userPackage->setCustomField("Registrar Order Id",$userPackage->getCustomField("Registrar").'-'.$orderid);
        return $userPackage->getCustomField('Domain Name') . ' has been renewed.';
    }

     /**
     * Initiate a domain transfer
     *
     * @param array $params
     */
    function doDomainTransfer($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $transferid = $this->initiateTransfer($this->buildTransferParams($userPackage,$params));
        $userPackage->setCustomField("Registrar Order Id",$userPackage->getCustomField("Registrar").'-'.$transferid);
        $userPackage->setCustomField('Transfer Status', $transferid);
        return "Transfer of has been initiated.";
    }

    function getTransferStatus($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        if ($params['Use testing server']) {
            $host = 'horizon.opensrs.net';
        } else {
            $host = 'rr-n1-tor.opensrs.net';
        }

        $params['domain'] = strtolower($params['sld'].".".$params['tld']);
        $opensrs = new OpenSRS($host, $this->connPort,
                $params['Username'],
                $params['Private Key'],
                $this->user);

        $return = $opensrs->check_transfer_status($params);

        if ($return == null) {
            CE_Lib::log(4, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
            throw new Exception ("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
        }
        foreach ($return as $key=>$val) {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
            if ($val['@']['key'] == 'attributes') $attributes_key = $key;
        }

        CE_Lib::log(4, "OpenSRS Transfer Status Response: ".$return[$status_text]['#']);

        // XXX: Get the status, check if it's transfered successfully, return status as needed.
        // Awaiting on OpenSRS for now.


        if (isset($attributes_key)) {
            $attributes = $return[$attributes_key]['#']['dt_assoc'][0]['#']['item'];
            if ($return[$success_key]['#'] == 1) {
                foreach ($attributes as $key=>$val) {
                    if ($val['@']['key'] == 'status') $transfer_status = $val['#'];
                }
            }
        }
        $code = $return[$status_key]['#'];
        if ($code == 210 || $code == 200 || $code == 250) {
             // We are completed, so update our internal status so we don't try to check everytime.
            if ( $transfer_status == 'completed' || $transfer_status == 'The transfer completed successfully' ) {
                $userPackage->setCustomField('Transfer Status', 'Completed');
            }
           return $transfer_status;
        }

        throw new CE_Exception("Getting Domain Transfer Status Failed: ".$return[$status_text]['#']." ".@$attributes[0]['#']);
    }


    function initiateTransfer($params)
    {
        if ($params['Use testing server']) {
            $host = 'horizon.opensrs.net';
        } else {
            $host = 'rr-n1-tor.opensrs.net';
        }

        $params['domain'] = strtolower($params['sld'].".".$params['tld']);
        $params['RegistrantPhone'] = $this->_plugin_opensrs_validatePhone($params['RegistrantPhone'],$params['RegistrantCountry']);
        if ($params['RegistrantOrganizationName'] == "") $params['RegistrantOrganizationName'] = $params['RegistrantFirstName']." ".$params['RegistrantLastName'];

        /* Grab some information that isn't passed by default */
        $query = "SELECT id from customuserfields where type='8'";
        $result = $this->db->query($query);
        list($fieldid) = $result->fetch();

        $query = "SELECT id FROM users WHERE email=?";
        $result = $this->db->query($query, $params['RegistrantEmailAddress']);
        list($userid) = $result->fetch();

        $query = "SELECT value FROM user_customuserfields WHERE customid=? AND userid=?";
        $result = $this->db->query($query, $fieldid, $userid);
        list($lang) = $result->fetch();
        if (strtolower($lang) == 'french') $params['RegistrantLanguage'] = 'FR';
        else $params['RegistrantLanguage'] = 'EN';


        $opensrs = new OpenSRS($host, $this->connPort,
                $params['Username'],
                $params['Private Key'],
                $this->user);

        $return = $opensrs->initiate_transfer($params);

        if ($return == null) {
            CE_Lib::log(4, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
            throw new Exception ("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
        }
        foreach ($return as $key=>$val) {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
            if ($val['@']['key'] == 'attributes') $attributes_key = $key;
        }

        CE_Lib::log(4, "OpenSRS Transfer Response: ".$return[$status_text]['#']);

        if (isset($attributes_key)) {
            $attributes = $return[$attributes_key]['#']['dt_assoc'][0]['#']['item'];
            if ($return[$success_key]['#'] == 1) {
                foreach ($attributes as $key=>$val) {
                    if ($val['@']['key'] == 'id') $transferId = $val['#'];
                }
            }
        }
        $code = $return[$status_key]['#'];
        if ($code == 210 || $code == 200 || $code == 250) {
            // Transfer was fine
            return $transferId;
        }

        throw new CE_Exception("Starting Domain Transfer Failed: ".$return[$status_text]['#']." ".@$attributes[0]['#']);

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
     * Function to renew the given domain. This needs tidying up
     *
     * @return array
     */
    function renewDomain($params)
    {
        if ($params['Use testing server']) {
            $host = 'horizon.opensrs.net';
        } else {
            $host = 'rr-n1-tor.opensrs.net';
        }


        $opensrs = new OpenSRS($host, $this->connPort,
                $params['Username'],
                $params['Private Key'],
                $this->user);

        //need to get current expiration date
        //so actually call up the plugin to get it from registrar
        try{
            require_once 'modules/clients/models/DomainNameGateway.php';
            $userPackage = new UserPackage($params['userPackageId']);
            $dng = new DomainNameGateway($this->user);
            $generalInfo = $dng->getGeneralInfoViaPlugin($userPackage);
            $expires = explode("-",$generalInfo['expires']);
            $params['expirationyear'] = (int) $expires[0];
            $params['renewname'] = $userPackage->getCustomField("Auto Renew");
            if(!$params['renewname'] == 0) {
                $params['renewname'] = 1;
            }
        }catch(Exception $ex){
            throw new CE_Exception("Domain renewal failed: ".$ex->getMessage());
        }

        $params['domain'] = strtolower($params['sld'].".".$params['tld']);

        $return = $opensrs->renew_domain($params);

        if ($return == null) {
            CE_Lib::log(4, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
            throw new Exception ("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
        }
        foreach ($return as $key=>$val) {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
            if ($val['@']['key'] == 'attributes') $attributes_key = $key;
        }

        CE_Lib::log(4, "OpenSRS Registration Response: ".$return[$status_text]['#']);

        if (isset($attributes_key)) {
            if(isset($return[$attributes_key]) && isset($return[$attributes_key]['#']['dt_assoc'][0]['#']['item'])){
                $attributes = $return[$attributes_key]['#']['dt_assoc'][0]['#']['item'];
                if ($return[$success_key]['#'] == 1) {
                    foreach ($attributes as $key=>$val) {
                        if ($val['@']['key'] == 'id') $regId = $val['#'];
                    }
                }
            }
        }

        $code = $return[$status_key]['#'];
        if ($code == 210 || $code == 200 || $code == 250) {
            // Registration was fine
            return $regId;
        }
        if ($code == 485) {
            // Domain is already registered
            throw new CE_Exception('Domain Name already registered.');
        }
        // Something went very wrong

        throw new CE_Exception("Domain renewal failed: ".$return[$status_text]['#']." ".@$attributes[0]['#']);
    }

    /**
     * Function to register the given domain. This needs tidying up
     *
     * @return array
     */
    function registerDomain($params)
    {
        if ($params['Use testing server']) {
            $host = 'horizon.opensrs.net';
            $params['NS1']['hostname'] = "default.opensrs.org";
            $params['NS2']['hostname'] = "default1.opensrs.org";
            $params['Custom NS'] = 1;
            for ($i = 3; $i <= 12; $i++) {
                unset($params['NS'.$i]);
            }
        } else {
            $host = 'rr-n1-tor.opensrs.net';
            if (isset($params['NS1'])) {
                $params['Custom NS'] = 1;
            } else {
                $params['Custom NS'] = 0;
            }
        }

        if(!@$params['renewname'] == 0) {
            $params['renewname'] = 1;
        }

        $opensrs = new OpenSRS($host, $this->connPort,
                $params['Username'],
                $params['Private Key'],
                $this->user);


        $params['domain'] = strtolower($params['sld'].".".$params['tld']);
        $params['RegistrantPhone'] = $this->_plugin_opensrs_validatePhone($params['RegistrantPhone'],$params['RegistrantCountry']);
        if ($params['RegistrantOrganizationName'] == "") $params['RegistrantOrganizationName'] = $params['RegistrantFirstName']." ".$params['RegistrantLastName'];

        // Process the extended attributes
        if (is_array($params['ExtendedAttributes'])) {
            foreach ($params['ExtendedAttributes'] as $name => $value) {
                $params[$name] = $value;
            }
        }

        /* Grab some information that isn't passed by default */
        $query = "SELECT id from customuserfields where type='8'";
        $result = $this->db->query($query);
        list($fieldid) = $result->fetch();

        $query = "SELECT id FROM users WHERE email=?";
        $result = $this->db->query($query, $params['RegistrantEmailAddress']);
        list($userid) = $result->fetch();

        $query = "SELECT value FROM user_customuserfields WHERE customid=? AND userid=?";
        $result = $this->db->query($query, $fieldid, $userid);
        list($lang) = $result->fetch();
        if (strtolower($lang) == 'french') $params['RegistrantLanguage'] = 'FR';
        else $params['RegistrantLanguage'] = 'EN';


        $return = $opensrs->register_domain($params);

        //print_r($return);

        if ($return == null) {
            CE_Lib::log(4, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
            throw new CE_Exception ("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
        }
        foreach ($return as $key=>$val) {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
            if ($val['@']['key'] == 'attributes') $attributes_key = $key;
        }

        CE_Lib::log(4, "OpenSRS Registration Response: ".$return[$status_text]['#']);

        if (isset($attributes_key)) {
            $attributes = $return[$attributes_key]['#']['dt_assoc'][0]['#']['item'];
            if ($return[$success_key]['#'] == 1) {
                foreach ($attributes as $key=>$val) {
                    if ($val['@']['key'] == 'id') $regId = $val['#'];
                }
            }
        }
        $code = $return[$status_key]['#'];
        if ($code == 210 || $code == 200 || $code == 250) {
            // Registration was fine
            // Check about private registration
            if ( isset($params['package_addons']['IDPROTECT']) && $params['package_addons']['IDPROTECT'] == 1 ) {
                $return = $opensrs->enable_whois_privacy($params);
                if ($return == null) {
                    CE_Lib::log(4, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
                    throw new CE_Exception ("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
                }
            }
            // return reg id for the domain
            return $regId;
        }
        if ($code == 485) {
            // Domain is already registered
            throw new CE_Exception('OpenSRS Error: Domain Name already registered.');
        }
        // Something went very wrong

        throw new CE_Exception("Domain registration failed: ".$return[$status_text]['#']." ".@$attributes[0]['#']);
    }

    function disablePrivateRegistration($parmas)
    {
        throw new MethodNotImplemented('Method disablePrivateRegistration has not been implemented.');
    }


    /**
     * Function to change the auto renew status of a given domain.
     *
     * First gets a "cookie" from OpenSRS to edit the given domain
     *
     * @return array
     */
    function setAutorenew($params)
    {
        //$userPackage = $params['userPackage'];

        // Calculate the host
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        // Start the connection
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        // Get the cookie
        //requires username and password so lets send
        //$params = array_merge($params,$this->getUsernamePassword($userPackage));

        // DOn't get the cookie as we're using no credentials
        //$cookie = $opensrs->get_cookie($params);

        // Run the command
        $return = $opensrs->set_autorenew(strtolower($params['sld']), strtolower($params['tld']), $params['autorenew']);

        // Check for an error
        if ($return['status']['response_code'] != 200) {
            CE_Lib::log(4, "OpenSRS API Call Failed: ".$return['status']['response_text']);
            throw new CE_Exception("Setting Autorenew Failed: ".$return['status']['response_text']);
        }
    }

    /**
     * Function to get the general information of a given domain
     *
     * First gets a "cookie" from OpenSRS to edit the given domain
     *
     * @return array
     */
    function getGeneralInfo($params)
    {
        // Calculate the host
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        // Start the connection
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        // Get the domain info
        $return = $opensrs->get_domain_info(strtolower($params['sld']), strtolower($params['tld']), 'general');

        if($return == null) {
            // This function can break some domains in admin panel. For example, transfer's that aren't yet in the account.
            // So if the response is null, then don't show an error
            return null;
        }

        // Check for an error
        if ($return['status']['response_code'] != 200) {
            CE_Lib::log(4, "OpenSRS API Call Failed: ".$return['status']['response_text']);
            throw new Exception("OpenSRS API Error - getGeneralInfo: ".$return['status']['response_text']);
        }

        // Build the standard CE response
        $data = array();
        $data['id'] = -1;
        $data['domain'] = strtolower($params['sld']).".".strtolower($params['tld']);
        $data['expiration'] = $return['generalInfo']['registry_expiredate'];
        $data['registrationstatus'] = 'Registered';
        $data['purchasestatus'] = 'Purchased on '.$return['generalInfo']['registry_createdate'];
        $data['autorenew'] = $return['generalInfo']['auto_renew'];

        return $data;

    }


    /**
     * Function to import domains from OpenSRS
     * @param <type> $params
     */
    function fetchDomains($params)
    {

        // Calculate the host
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        // Start the connection
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        $return = $opensrs->get_domains_list($params['next']);

        return $return;
    }

    // @access private
    function _plugin_opensrs_validatePhone($phone, $country)
    {
        // strip all non numerical values
        $phone = preg_replace('/[^\d]/', '', $phone);

        $query = "SELECT phone_code FROM country WHERE iso=? AND phone_code != ''";
        $result = $this->db->query($query, $country);
        if (!$row = $result->fetch()) {
            return $phone;
        }

        // check if code is already there
        $code = $row['phone_code'];
        $phone = preg_replace("/^($code)(\\d+)/", '+\1.\2', $phone);
        if (isset($phone[0]) && $phone[0] == '+') {
            return $phone;
        }

        // if not, prepend it
        return "+$code.$phone";
    }

    function getContactInformation ($params)
    {
        // Calculate the host
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        // Start the connection
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        // Get the domain info
        $return = $opensrs->get_domain_info(strtolower($params['sld']), strtolower($params['tld']), 'contact');

        return $return;
    }

    function setContactInformation ($params)
    {
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);
        $opensrs->update_contact($params);
    }

    function getNameServers ($params)
    {
        // Calculate the host
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        // Start the connection
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        // Get the domain info
        $return = $opensrs->get_domain_info(strtolower($params['sld']), strtolower($params['tld']), 'nameserver');

        $return['usesDefault'] = false;
        $return['hasDefault'] = false;

        return $return;
    }

    function setNameServers ($params)
    {
        // Calculate the host
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;

        // Start the connection
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        // Get the domain info
        $return = $opensrs->set_nameservers($params);

        foreach ($return as $key=>$val) {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
            if ($val['@']['key'] == 'attributes') $attributes_key = $key;
        }

        CE_Lib::log(4, "OpenSRS SetNameServers Response: ".$return[$status_text]['#']);
        if ( isset($return[$success_key]['#']) && $return[$success_key]['#']  == 1) {
            return;
        } else {
            throw new CE_Exception($return[$status_text]['#']);

        }
    }

    function checkNSStatus ($params)
    {
        throw new Exception('Method checkNSStatus() has not been implemented yet.');
    }

    function registerNS ($params)
    {
        throw new Exception('Method registerNS() has not been implemented yet.');
    }

    function editNS ($params)
    {
        throw new Exception('Method editNS() has not been implemented yet.');
    }

    function deleteNS ($params)
    {
        throw new Exception('Method deleteNS() has not been implemented yet.');
    }

    function getRegistrarLock ($params)
    {
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        $return = $opensrs->get_lock(strtolower($params['sld']), strtolower($params['tld']));

        if ($return == null) {
            CE_Lib::log(4, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
            throw new Exception ("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
        }
        foreach ($return as $key=>$val) {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
            if ($val['@']['key'] == 'attributes') $attributes_key = $key;
        }

        if (isset($attributes_key)) {
            $attributes = $return[$attributes_key]['#']['dt_assoc'][0]['#']['item'];
            if ($return[$success_key]['#'] == 1) {
                foreach ($attributes as $key=>$val) {
                    if ($val['@']['key'] == 'lock_state') $lockState = $val['#'];
                }
            }
        }

        if ( isset($lockState) ) {
            return $lockState;
        }

        throw new CE_Exception ("Could not determine lock state.");
    }

    function doSetRegistrarLock($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->setRegistrarLock($this->buildLockParams($userPackage,$params));
        return "Updated Registrar Lock.";
    }

    function setRegistrarLock ($params)
    {
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);

        $return = $opensrs->set_lock(strtolower($params['sld']), strtolower($params['tld']), $params['lock']);

        if ($return == null) {
            CE_Lib::log(4, "OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
            throw new Exception ("OpenSRS Error: Ensure port 55443 is open and PHP is compiled with OpenSSL.");
        }

    }

    function doSendTransferKey($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->sendTransferKey($this->buildRegisterParams($userPackage,$params));
        return 'Successfully sent auth info for ' . $userPackage->getCustomField('Domain Name');
    }

    function sendTransferKey ($params)
    {
        $host = ($params['Use testing server'])? $this->testHost:$this->liveHost;
        $opensrs = new OpenSRS($host, $this->connPort, $params['Username'], $params['Private Key'], $this->user);
        $opensrs->send_authcode(strtolower($params['sld']), strtolower($params['tld']));
    }
    function getDNS ($params)
    {
        throw new CE_Exception('Getting DNS Records is not supported in this plugin.', EXCEPTION_CODE_NO_EMAIL);
    }
    function setDNS ($params)
    {
        throw new CE_Exception('Setting DNS Records is not supported in this plugin.');
    }

}