<?php
require_once 'modules/admin/models/RegistrarPlugin.php';
require_once dirname(__FILE__).'/class.planetdomain.php';

/**
* @package Plugins
*/
class PluginPlanetdomain extends RegistrarPlugin {

    function getVariables(){

        $variables = array(
            lang('Plugin Name') => array (
                'type'          =>'hidden',
                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                'value'         =>lang('PlanetDomain')
            ),
            lang('Account No') => array(
                'type'          => 'text',
                'description'   => lang('Enter your Account No found in API Login Credentials.'),
                'value'         => '',
            ),
            lang('Login')  => array(
                'type'          => 'text',
                'description'   => lang('Enter your Login found in API Login Credentials..'),
                'value'         => '',
            ),
            lang('Password')  => array(
                'type'          => 'password',
                'description'   => lang('Enter your Password found in API Login Credentials.'),
                'value'         => '',
            ),
            lang('Default Account') => array(
                'type'          => 'text',
                'description'   => lang('Enter the Account Reference all domain names should be registered under.  Leaving this blank will create a different account for each client at the registrar.'),
                'value'         => '',
            ),
            lang('Supported Features')  => array(
                'type'          => 'label',
                'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').'<br>* '.lang('Automatically Renew Domain').'<br>* '.lang('Get / Set Nameserver Records').' <br>* '.lang('Get / Set Registrar Lock').' <br>* '.lang('Get / Set Contact Information').' <br>',
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
                'value'         => 'Renew (Renew Domain),Cancel',
            )
        );

        return $variables;
    }

    function checkDomain($params)
    {
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));
        $api = new QueryAPI($args);
        $return = $api->domainLookup();

        if ( $return->isSuccess() ) {
            $status = 0;
        } else {
            CE_Lib::log(4, 'PlanetDomain Error: ' . $return->getModuleError());
            $status = 1;
        }
        $domains[] = array("tld"=>$params['tld'],"domain"=>$params['sld'],"status"=>$status);
        return array("result"=>$domains);
    }

    function getCredentials($params)
    {
        return array (
            'AccountNo' => $params['Account No'],
            'UserId' => $params['Login'],
            'Password' => $params['Password']
        );
    }

    function getDomainArgs($params)
    {
        return array (
            'sld' => $params['sld'],
            'tld' => $params['tld']
        );
    }

    function getDomainCredentials($params)
    {
        if  ( $params['Default Account'] != '' ) {
            return array(
                'AccountOption'     => 'CONSOLE',
                'AccountReference'  => $params['Default Account']
            );
        } else {
            $userPackage = new UserPackage($params['userPackageId']);
            return array(
                'AccountOption'     => 'EXTERNAL',
                'AccountReference'  => $userPackage->CustomerId
            );
        }
    }

    function getDomainRenewArgs($params)
    {
        return array(
            'regperiod' => $params['NumYears'],
        );
    }

    function getDomainRegArgs($params)
    {
        return array(
            'regperiod' => $params['NumYears'],
            'contactdetails' => array (
                'Registrant' =>
                    array (
                        'OrganisationName' => $params['RegistrantOrganizationName'],
                        'FirstName' => $params['RegistrantFirstName'],
                        'LastName' => $params['RegistrantLastName'],
                        'Address1' => $params['RegistrantAddress1'],
                        'City' => $params['RegistrantCity'],
                        'Region' => $params['RegistrantStateProvince'],
                        'PostalCode' => $params['RegistrantPostalCode'],
                        'CountryCode' => $params['RegistrantCountry'],
                        'Email' => $params['RegistrantEmailAddress'],
                        'PhoneNumber' => $params['RegistrantPhone']
                ),
                'Admin' =>
                    array (
                        'OrganisationName' => $params['RegistrantOrganizationName'],
                        'FirstName' => $params['RegistrantFirstName'],
                        'LastName' => $params['RegistrantLastName'],
                        'Address1' => $params['RegistrantAddress1'],
                        'City' => $params['RegistrantCity'],
                        'Region' => $params['RegistrantStateProvince'],
                        'PostalCode' => $params['RegistrantPostalCode'],
                        'CountryCode' => $params['RegistrantCountry'],
                        'Email' => $params['RegistrantEmailAddress'],
                        'PhoneNumber' => $params['RegistrantPhone']
                ),
                'Tech' =>
                    array (
                        'OrganisationName' => $params['RegistrantOrganizationName'],
                        'FirstName' => $params['RegistrantFirstName'],
                        'LastName' => $params['RegistrantLastName'],
                        'Address1' => $params['RegistrantAddress1'],
                        'City' => $params['RegistrantCity'],
                        'Region' => $params['RegistrantStateProvince'],
                        'PostalCode' => $params['RegistrantPostalCode'],
                        'CountryCode' => $params['RegistrantCountry'],
                        'Email' => $params['RegistrantEmailAddress'],
                        'PhoneNumber' => $params['RegistrantPhone']
                ),
                'Billing' =>
                    array (
                        'OrganisationName' => $params['RegistrantOrganizationName'],
                        'FirstName' => $params['RegistrantFirstName'],
                        'LastName' => $params['RegistrantLastName'],
                        'Address1' => $params['RegistrantAddress1'],
                        'City' => $params['RegistrantCity'],
                        'Region' => $params['RegistrantStateProvince'],
                        'PostalCode' => $params['RegistrantPostalCode'],
                        'CountryCode' => $params['RegistrantCountry'],
                        'Email' => $params['RegistrantEmailAddress'],
                        'PhoneNumber' => $params['RegistrantPhone']
                ),
            ),
        );
    }

    function getDomainExtraArgs($params)
    {
        if ( $params['tld'] == 'com.au' ) {

            return array(
                'additionalfields' => array (
                    'Registrant Name' => $params['ExtendedAttributes']['au_registrantname'],
                    'Registrant ID' => $params['ExtendedAttributes']['au_registrantid'],
                    'Eligibility ID Type' => $params['ExtendedAttributes']['au_entityidtype'],
                    'Eligibility Type' => $params['ExtendedAttributes']['au_eligibilitytype'],
                    'Eligibility Reason' => $params['ExtendedAttributes']['au_eligibilityreason'],
                )
            );

        } else {
            return array();
        }
    }


    /**
     * Register domain name
     *
     * @param array $params
     */
    function doRegister($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderId = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
        $userPackage->setCustomField("Registrar Order Id",$userPackage->getCustomField("Registrar").'-'.$orderId);
        return $userPackage->getCustomField('Domain Name') . ' has been registered.';
    }

    function registerDomain($params)
    {
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params), $this->getDomainCredentials($params), $this->getDomainRegArgs($params), $this->getDomainExtraArgs($params));
        $api = new OrderAPI($args);
        $return = $api->domainRegister();
        if ( $return->isSuccess() ) {
            return $return->getResponse();
        } else {
            $errorMessage = 'PlanetDomain Error: ' . $return->getModuleError();
            CE_Lib::log(4, $errorMessage);
            throw new CE_Exception($errorMessage);
        }
    }


    function doRenew($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->renewDomain($this->buildRenewParams($userPackage,$params));
        $userPackage->setCustomField("Registrar Order Id",$userPackage->getCustomField("Registrar").'-'.$orderid);
        return $userPackage->getCustomField('Domain Name') . ' has been renewed.';
    }

    function renewDomain($params)
    {
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params), $this->getDomainCredentials($params), $this->getDomainRenewArgs($params));
        $api = new OrderAPI($args);
        $return = $api->domainRenewal();
        if ( $return->isSuccess() ) {
            return $return->getResponse();
        } else {
            $errorMessage = 'PlanetDomain Error: ' . $return->getModuleError();
            CE_Lib::log(4, $errorMessage);
            throw new CE_Exception($errorMessage);
        }
    }



    function getContactInformation($params){
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));
        $api = new QueryAPI($args);

        $results = $api->domainWhois();

        if ( $results->isSuccess() ) {
            $info = array();

            $info['Registrant']['OrganizationName']  = array($this->user->lang('Organization'), $results->get('Owner-OrganisationName'));
            $info['Registrant']['FirstName'] = array($this->user->lang('First Name'), $results->get('Owner-FirstName'));
            $info['Registrant']['LastName'] = array($this->user->lang('Last Name'), $results->get('Owner-LastName'));
            $info['Registrant']['Address1']  = array($this->user->lang('Address').' 1', $results->get('Owner-Address1'));
            $info['Registrant']['Address2']  = array($this->user->lang('Address').' 2', $results->get('Owner-Address2'));
            $info['Registrant']['City']      = array($this->user->lang('City'), $results->get('Owner-City'));
            $info['Registrant']['StateProv']  = array($this->user->lang('Province').'/'.$this->user->lang('State'), $results->get('Owner-Region'));
            $info['Registrant']['Country']   = array($this->user->lang('Country'), $results->get('Owner-CountryCode'));
            $info['Registrant']['PostalCode']  = array($this->user->lang('Postal Code').'/'.$this->user->lang('Zip'), $results->get('Owner-PostalCode'));
            $info['Registrant']['EmailAddress']   = array($this->user->lang('E-mail'), $results->get('Owner-Email'));
            $info['Registrant']['Phone']  = array($this->user->lang('Phone'), $results->get('Owner-PhoneNumber'));
            return $info;
        } else {
            throw new Exception('Failed to retrieve data from PlanetDomain');
        }
    }

    function setContactInformation($params){
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));
        $args = array_merge($args, array (
            'AccountOption' => 'EXTERNAL',
            'contactdetails' => array (
                'Registrant' =>
                    array (
                        'OrganisationName' => $params['Registrant_OrganizationName'],
                        'FirstName' => $params['Registrant_FirstName'],
                        'LastName' => $params['Registrant_LastName'],
                        'Address1' => $params['Registrant_Address1'],
                        'City' => $params['Registrant_City'],
                        'Region' => $params['Registrant_StateProv'],
                        'PostalCode' => $params['Registrant_PostalCode'],
                        'CountryCode' => $params['Registrant_Country'],
                        'Email' => $params['Registrant_EmailAddress'],
                        'PhoneNumber' => $this->_validatePhone($params['Registrant_Phone'],$params['Registrant_Country'])
                )
            )
        ));
        $api = new OrderAPI($args);
        $results = $api->contactsUpdate();

        if($results->isSuccess()) {
            return true;
        } else {
            throw new Exception($results->getModuleError());
        }
    }

    function getGeneralInfo($params) {
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));

        $api = new QueryAPI($args);
        $results = $api->domainWhois();

        if($results->isSuccess()) {
            $userPackage = new UserPackage($params['userPackageId']);
            $data = array();
            $data['expiration'] = $results->get('ExpiryDate');
            $data['domain'] = $userPackage->getCustomField('Domain Name');
            $data['id'] = $this->user->lang('Unknown');
            $data['registrationstatus'] = $this->user->lang('N/A');
            $data['purchasestatus'] = $this->user->lang('N/A');
            $data['autorenew'] = false;

            return $data;
        }
        else {
            throw new CE_Exception($results->getModuleError(), EXCEPTION_CODE_CONNECTION_ISSUE);
        }
    }

    function getNameServers($params){
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));

        $api = new QueryAPI($args);
        $results = $api->domainWhois();

        if($results->isSuccess()) {

            $nameServers = $results->getArray('Nameserver');
            $ns = array();
            $ns['hasDefault'] = false;
            $ns['usesDefault'] = false;
            foreach($nameServers as $nameServer)
            {
                $ns[] = $nameServer;
            }

            return $ns;
        }
        else {
            throw new CE_Exception($results->getModuleError());
        }
    }

    function setNameServers ($params)
    {
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));
        $args = array_merge($args, array('RemoveHost' => 'ALL'));

        $nameServers = array();
        foreach ( $params['ns'] as $ns ) {
            $nameServers[] = $ns;
        }
        $args = array_merge($args, array('AddHost' => $nameServers));

        $api = new OrderAPI($args);
        $results = $api->domainDelegation();
        if(!$results->isSuccess()) {
            throw new CE_Exception($results->getModuleError());
        }
    }

    function getDNS ($params)
    {
        throw new CE_Exception('PlanetDomain does not support creating host records in their API.');
    }

    function checkNSStatus ($params)
    {
        throw new MethodNotImplemented('Method checkNSStatus() has not been implemented yet.');
    }

    function registerNS ($params)
    {
        throw new MethodNotImplemented('Method registerNS() has not been implemented yet.');
    }

    function editNS ($params)
    {
        throw new MethodNotImplemented('Method editNS() has not been implemented yet.');
    }

    function deleteNS ($params)
    {
        throw new MethodNotImplemented('Method deleteNS() has not been implemented yet.');
    }

    function setAutorenew ($params)
    {
        //throw new MethodNotImplemented('Method setAutorenew() has not been implemented yet.');
    }

    function getRegistrarLock ($params)
    {
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));

        $api = new QueryAPI($args);
        $results = $api->domainWhois();

        if($results->isSuccess()) {
            $lockStatus = $results->get('DomainStatus');
            if ( $lockStatus == 'REGISTRAR-LOCK') {
                return true;
            } else {
                return false;
            }
        }
        else {
            throw new CE_Exception($results->getModuleError());
        }
    }

    function setRegistrarLock ($params)
    {
        $args = array_merge($this->getCredentials($params), $this->getDomainArgs($params));
        $lock = $params['lock'];
        $args = array_merge($args, array('lockenabled' => $lock));

        $api = new OrderAPI($args);
        $results = $api->domainLock();
        if(!$results->isSuccess()) {
            throw new Exception($results->getModuleError());
        }
    }

    function sendTransferKey ($params)
    {
        throw new MethodNotImplemented('Method sendTransferKey() has not been implemented yet.');
    }
    function disablePrivateRegistration($parmas)
    {
        throw new MethodNotImplemented('Method disablePrivateRegistration has not been implemented yet.');
    }
    function getTransferStatus($params)
    {
        throw new MethodNotImplemented('Method getTransferStatus has not been implemented yet.');
    }

    function doSetRegistrarLock($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->setRegistrarLock($this->buildLockParams($userPackage,$params));
        return "Updated Registrar Lock.";
    }


    function _validatePhone($phone, $country){
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
        if ($phone[0] == '+') {
            return $phone;
        }

        // if not, prepend it
        return "+$code.$phone";
    }
}
