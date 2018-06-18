<?php
require_once 'modules/admin/models/RegistrarPlugin.php';
require_once dirname(__FILE__).'/ApiClient.php';

/**
* @package Plugins
*/
class PluginOnlinenic extends RegistrarPlugin
{

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('OnlineNIC')
                               ),
            lang('Use testing server') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use OnlineNIC\'s testing environment, so that transactions are not actually made.'),
                                'value'         => 0
                               ),
            lang('Username') => array(
                                'type'          => 'text',
                                'description'   => lang('Enter your username for your OnlineNIC reseller account.'),
                                'value'         => '',
                            ),
            lang('Private Key')  => array(
                                'type'          => 'password',
                                'description'   => lang('Enter your OnlineNIC reseller password.'),
                                'value'         => '',
                            ),
           lang('Supported Features')  => array(
                                'type'          => 'label',
                                'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>',
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
        $domains = array();

        $api = new ApiClient($this->getConfig($params));
        $cmd = $api->buildCommand('client', 'Login');
        $rs  = $api->request($cmd);
        if ( $rs ) {
            //something error happen
            $lastResult = $api->getLastResult();
            $error = $this->getError(__LINE__, $rs, $lastResult);
            $status = 5;
            $domains[] = array('tld' => $params['tld'], 'domain' => $params['sld'], 'status' => $status);
            return array("result"=>$domains);
        }

        $infoDomainParams = array(
            'domaintype' => $this->getDomainType($params['tld']),
            'domain'     => $params['sld'] . '.' . $params['tld']
        );
        $cmd = $api->buildCommand('domain', 'CheckDomain', $infoDomainParams);
        $rs = $api->request($cmd);
        $lastResult = $api->getLastResult();
        if ( $rs ) {
            $error = $this->getError(__LINE__, $rs, $lastResult);
            $status = 5;
            $domains[] = array('tld' => $params['tld'], 'domain' => $params['sld'], 'status' => $status);
            return array("result"=>$domains);
        } else {

            $status = 1;

            // avail = 1 means the domain is available for registration.
            if ( $lastResult['resData']['avail'] == 1 ) {
                $status = 0;
            }
        }

        //logout api
        $cmd = $api->buildCommand('client', 'Logout');
        $rs  = $api->request($cmd);

        $domains[] = array('tld' => $params['tld'], 'domain' => $params['sld'], 'status' => $status);
        return array("result"=>$domains);
    }


    /**
     * Register domain name
     *
     * @param array $params
     */
    function doRegister($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->registerDomain($this->buildRegisterParams($userPackage, $params));
        // no order id given in API, so just set it to the package id.
        $userPackage->setCustomField("Registrar Order Id", $userPackage->getCustomField("Registrar") . '-' . $params['userPackageId']);
        return $userPackage->getCustomField('Domain Name') . ' has been registered.';
    }

    function registerDomain($params)
    {
        $api = new ApiClient($this->getConfig($params));
        $cmd = $api->buildCommand('client', 'Login');
        $rs  = $api->request($cmd);
        if ( $rs ) {
            //something error happen
            $lastResult = $api->getLastResult();
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new CE_Exception($error);
        }

        $domain = strtolower($params['sld'] . '.' . $params['tld']);
        $domainType = $this->getDomainType($params['tld']);

        if ( $params['RegistrantOrganizationName'] == '' ) {
            $params['RegistrantOrganizationName'] = 'N/A';
        }

        $contactNum = 4;
        $contactIdArr = array();
        for ($i=0; $i<$contactNum; $i++) {
            $createContactParams = array(
                'domaintype' => $domainType,
                'name'       => $params['RegistrantFirstName'] . ' ' . $params['RegistrantLastName'],
                'org'        => $params['RegistrantOrganizationName'],
                'country'    => $params['RegistrantCountry'],
                'province'   => $params['RegistrantStateProvince'],
                'city'       => $params['RegistrantCity'],
                'street'     => $params['RegistrantAddress1'],
                'postalcode' => $params['RegistrantPostalCode'],
                'voice'      => $this->validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
                'fax'        => $this->validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
                'email'      => $params['RegistrantEmailAddress'],
                'password'   => $params['DomainPassword'],
            );

            $cmd        = $api->buildCommand('domain', 'CreateContact', $createContactParams);
            $rs         = $api->request($cmd);
            $lastResult = $api->getLastResult();
            if ($rs) {
                $error = $this->getError(__LINE__, $rs, $lastResult);
                throw new CE_Exception($error);
            } else {
                $contactIdArr[$i] = $lastResult['resData']['contactid'];
            }
        }

        $dnsList = array();
        if (isset($params['NS1'])) {
            // maximum 6 name servers are allowed by Enom
            for ($i = 1; $i <= 6; $i++) {
                if (isset($params["NS$i"])) {
                    $dnsList[] = $params["NS$i"]['hostname'];
                } else {
                    break;
                }
            }
        } else {
            $dnsList[] = 'ns1.dns-diy.net';
            $dnsList[] = 'ns2.dns-diy.net';
        }

        $createDomainParams = array(
            'domaintype' => $domainType,
            'mltype'     => 0,
            'domain'     => $domain,
            'period'     => $params['NumYears'],
            'dns'        => $dnsList,
            'registrant' => $contactIdArr[0],
            'admin'      => $contactIdArr[1],
            'tech'       => $contactIdArr[2],
            'billing'    => $contactIdArr[3],
            'password'   => $params['DomainPassword'],
        );

        $cmd        = $api->buildCommand('domain', 'CreateDomain', $createDomainParams);
        $rs         = $api->request($cmd);
        $lastResult = $api->getLastResult();
        if ($rs) {
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new CE_Exception($error);
        }

        $cmd = $api->buildCommand('client', 'Logout');
        $rs  = $api->request($cmd);
    }

    function getGeneralInfo($params)
    {
        $api = new ApiClient($this->getConfig($params));
        $cmd = $api->buildCommand('client', 'Login');
        $rs  = $api->request($cmd);
        if ( $rs ) {
            //something error happen
            $lastResult = $api->getLastResult();
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new CE_Exception($error, EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        $data = array();
        $infoDomainParams = array(
            'domaintype' => $this->getDomainType($params['tld']),
            'domain'     => $params['sld'] . '.' . $params['tld']
        );

        $cmd = $api->buildCommand('domain', 'InfoDomain', $infoDomainParams);
        $rs = $api->request($cmd);
        $lastResult = $api->getLastResult();
        if ( $rs ) {
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new Exception($error);
        } else {
            $data['id'] = $params['userPackageId'];
            $data['domain'] = $params['sld'] . '.' . $params['tld'];
            $data['expiration'] = $lastResult['resData']['exDate'];
            $data['registrationstatus'] = $this->user->lang('Registered On: ') . $lastResult['resData']['crDate'];
            $data['purchasestatus'] = 'N/A';
            $data['is_registered'] = true;
        }

        //logout api
        $cmd = $api->buildCommand('client', 'Logout');
        $rs  = $api->request($cmd);

        return $data;
    }

    private function validatePhone($phone, $country)
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
        if ($phone[0] == '+') {
            return $phone;
        }

        // if not, prepend it
        return "+$code.$phone";
    }

    private function getConfig($params)
    {
        $server = 'www.onlinenic.com';
        if ( $params['Use testing server'] == 1 ) {
            $server = 'ote.onlinenic.com';
        }
        return array (
            'server' => $server,
            'port' => '30009',
            'user' => $params['Username'],
            'pass' => $params['Private Key'],
            'timeout' => 15,
            'log_record' => true
        );
    }

    private function getError($line, $rs, $lastResult)
    {
        switch($rs) {
            case 1:
                $error = "Can't connect to server";
                break;
            case 2:
                $error = $lastResult['msg'] . ': ' . $lastResult['value'];
                break;
            default:
                $error = 'unknow error';
                break;
        }
        CE_Lib::log(4, $error);

        return $error;
    }

    private function getDomainType($tld)
    {
        switch($tld) {
            case 'com':
            case 'net':
                $domainType = 0;
                break;
            case 'org':
                $domainType = 807;
                break;
            case 'biz':
                $domainType = 800;
                break;
            case 'info':
                $domainType = 805;
                break;
            case 'us':
                $domainType = 806;
                break;
            case 'in':
                $domainType = 808;
                break;
            case 'co':
                $domainType = 908;
                break;
        }

        return $domainType;
    }

    function getContactInformation ($params)
    {
        $api = new ApiClient($this->getConfig($params));
        $cmd = $api->buildCommand('client', 'Login');
        $rs  = $api->request($cmd);
        if ( $rs ) {
            //something error happen
            $lastResult = $api->getLastResult();
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new CE_Exception($error, EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        $data = array();
        $infoDomainParams = array(
            'domaintype' => $this->getDomainType($params['tld']),
            'domain'     => $params['sld'] . '.' . $params['tld']
        );

        $cmd = $api->buildCommand('domain', 'InfoDomain', $infoDomainParams);
        $rs = $api->request($cmd);
        $lastResult = $api->getLastResult();
        if ( $rs ) {
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new Exception($error);
        } else {
            $info = array();

            $name = explode(' ', $lastResult['resData']['r_name']);
            $firstName = $name[0];
            unset($name[0]);
            $lastName = implode(' ', $name);

            foreach (array('Registrant', 'AuxBilling', 'Admin', 'Tech') as $type) {
                $info[$type]['OrganizationName']  = array($this->user->lang('Organization'), $lastResult['resData']['r_org']);
                //$info[$type]['JobTitle']  = array($this->user->lang('Job Title'), $data[$type.'JobTitle'][0]['#']);
                $info[$type]['FirstName'] = array($this->user->lang('First Name'), $firstName);
                $info[$type]['LastName']  = array($this->user->lang('Last Name'), $lastName);
                $info[$type]['Address1']  = array($this->user->lang('Address').' 1', $lastResult['resData']['r_address']);
                $info[$type]['Address2']  = array($this->user->lang('Address').' 2', '');
                $info[$type]['City']      = array($this->user->lang('City'), $lastResult['resData']['r_city']);
                //$info[$type]['StateProvChoice']  = array($this->user->lang('State or Province'), $data[$type.'StateProvinceChoice'][0]['#']);
                $info[$type]['StateProvince']  = array($this->user->lang('Province').'/'.$this->user->lang('State'), $lastResult['resData']['r_province']);
                $info[$type]['Country']   = array($this->user->lang('Country'), $lastResult['resData']['r_country']);
                $info[$type]['PostalCode']  = array($this->user->lang('Postal Code'), $lastResult['resData']['r_postalcode']);
                $info[$type]['EmailAddress']     = array($this->user->lang('E-mail'), $lastResult['resData']['r_email']);
                $info[$type]['Phone']  = array($this->user->lang('Phone'), $lastResult['resData']['r_telephone']);
                //$info[$type]['PhoneExt']  = array($this->user->lang('Phone Ext'), $data[$type.'PhoneExt'][0]['#']);
                $info[$type]['Fax']       = array($this->user->lang('Fax'), $lastResult['resData']['r_fax']);
            }
        }

        //logout api
        $cmd = $api->buildCommand('client', 'Logout');
        $rs  = $api->request($cmd);

        return $info;
    }

    function doRenew ($params)
    {

        $userPackage = new UserPackage($params['userPackageId']);
        $this->renewDomain($this->buildRegisterParams($userPackage, $params));

        return $userPackage->getCustomField('Domain Name') . ' has been renewed.';
    }

    function setContactInformation ($params)
    {
        throw new CE_Exception('Method setContactInformation() has not been implemented yet.');
    }

    function getNameServers ($params)
    {
        $api = new ApiClient($this->getConfig($params));
        $cmd = $api->buildCommand('client', 'Login');
        $rs  = $api->request($cmd);
        if ( $rs ) {
            //something error happen
            $lastResult = $api->getLastResult();
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new CE_Exception($error, EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        $data= array();
        $info= array();
        $infoDomainParams = array(
            'domaintype' => $this->getDomainType($params['tld']),
            'domain'     => $params['sld'] . '.' . $params['tld']
        );

        $cmd = $api->buildCommand('domain', 'InfoDomain', $infoDomainParams);
        $rs = $api->request($cmd);
        $lastResult = $api->getLastResult();
        if ( $rs ) {
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new Exception($error);
        } else {
            $data = $lastResult['resData']['dns'];
        }
        if (is_array($data)) {
            $info = $data;
            return $info;
        }

        //logout api
        $cmd = $api->buildCommand('client', 'Logout');
        $rs  = $api->request($cmd);
    }

    function setNameServers ($params)
    {
        throw new CE_Exception('Method setNameServers() has not been implemented yet.');
    }

    function checkNSStatus ($params)
    {
        throw new CE_Exception('Method checkNSStatus() has not been implemented yet.');
    }

    function registerNS ($params)
    {
        throw new CE_Exception('Method registerNS() has not been implemented yet.');
    }

    function editNS ($params)
    {
        throw new CE_Exception('Method editNS() has not been implemented yet.', EXCEPTION_CODE_NO_EMAIL);
    }

    function deleteNS ($params)
    {
        throw new CE_Exception('Method deleteNS() has not been implemented yet.');
    }

    function setAutorenew ($params)
    {
        throw new MethodNotImplemented('Method setAutorenew() has not been implemented yet.');
    }

    function renewDomain($params)
    {
        $api = new ApiClient($this->getConfig($params));
        $cmd = $api->buildCommand('client', 'Login');
        $rs  = $api->request($cmd);
        if ($rs) {
            //something error happen
            $lastResult = $api->getLastResult();
            $error = getError(__LINE__, $rs, $lastResult);
            throw new CE_Exception($error, EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        $domain = strtolower($params['sld'] . '.' . $params['tld']);
        $domainType = $this->getDomainType($params['tld']);

        //RenewDomain
        $renewDomainParams = array(
            'domaintype' => $domainType,
            'domain'     => $domain,
            'period'     => 1,//1 year
        );

        $cmd        = $api->buildCommand('domain', 'RenewDomain', $renewDomainParams);
        $rs         = $api->request($cmd);
        $lastResult = $api->getLastResult();
        if ($rs) {
            $error = $this->getError(__LINE__, $rs, $lastResult);
            throw new CE_Exception($error);
        }

        //logout api
        $cmd = $api->buildCommand('client', 'Logout');
        $rs  = $api->request($cmd);
        return $data;
    }

    function getRegistrarLock ($params)
    {
        throw new CE_Exception('Method getRegistrarLock() has not been implemented yet.', EXCEPTION_CODE_NO_EMAIL);
    }

    function setRegistrarLock ($params)
    {
        throw new CE_Exception('Method setRegistrarLock() has not been implemented yet.');
    }

    function sendTransferKey ($params)
    {
        throw new CE_Exception('Method sendTransferKey() has not been implemented yet.');
    }
    function disablePrivateRegistration($parmas)
    {
        throw new MethodNotImplemented('Method disablePrivateRegistration has not been implemented yet.');
    }
    function getTransferStatus($params)
    {
        throw new MethodNotImplemented('Method getTransferStatus has not been implemented yet.', EXCEPTION_CODE_NO_EMAIL);
    }

}
