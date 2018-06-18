<?php
require_once 'modules/admin/models/RegistrarPlugin.php';
require_once dirname(__FILE__).'/class.resellone.php';

/**
* @package Plugins
*/
class PluginResellOne extends RegistrarPlugin
{
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('ResellOne')
                               ),
            lang('Username') => array(
                                'type'          => 'text',
                                'description'   => lang('Enter your username for your ResellOne reseller account.'),
                                'value'         => '',
                            ),
            lang('Private Key')  => array(
                                'type'          => 'password',
                                'description'   => lang('Enter your ResellOne reseller private key.'),
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
                                'value'         => 'Cancel',
                                )
        );

        return $variables;
    }

    function checkDomain($params)
    {
        $host = 'resellers.resellone.net';

        $resellone = new ResellOne($host,
                                   $params['Username'],
                                   $params['Private Key']);

        $return = $resellone->lookup_domain(strtolower($params['sld'].".".$params['tld']));

        if ($return == null) {
            CE_Lib::log(4, "ResellOne Error: Ensure port 52443 is open and PHP is compiled with OpenSSL.");
            throw new Exception('ResellOne Error: Ensure port 52443 is open and PHP is compiled with OpenSSL.', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        // Check for errors
        foreach ($return as $key=>$val)
        {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
        }
        if ($return[$success_key]['#'] != 1) {
            CE_Lib::log(4, "ResellOne Lookup Failed: ".$return[$status_text]['#']);
            $status = 5;
        }

        $available = $return[$status_key]['#'];
        if ($available == 200 || $available == 210) {
            $status = 0;
        }
        else if ($available == 211 || $available == 212) {
            $status = 1;
        } else {
            $status = 5;
        }

        $domains[] = array("tld"=>$params['tld'],"domain"=>$params['sld'],"status"=>$status);
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
        $orderid = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
        $userPackage->setCustomField("Registrar Order Id",$userPackage->getCustomField("Registrar").'-'.$orderid);
        return true;
    }

    function registerDomain($params)
    {
            $host = 'resellers.resellone.net';
            if (isset($params['NS1'])) {
               $params['Custom NS'] = 1;
            } else {
                $params['Custom NS'] = 0;
            }

        $resellone = new ResellOne($host,
                                   $params['Username'],
                                   $params['Private Key']);


        $params['domain'] = strtolower($params['sld'].".".$params['tld']);
        $params['RegistrantPhone'] = $this->_plugin_resellone_validatePhone($params['RegistrantPhone'],$params['RegistrantCountry']);
        if ($params['RegistrantOrganizationName'] == "") $params['RegistrantOrganizationName'] = $params['RegistrantFirstName']." ".$params['RegistrantLastName'];

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


        $return = $resellone->register_domain($params);

        if ($return == null) {
            CE_Lib::log(4, "ResellOne Error: Ensure port 52443 is open and PHP is compiled with OpenSSL.");
            throw new Exception('ResellOne Error: Ensure port 52443 is open and PHP is compiled with OpenSSL.', EXCEPTION_CODE_CONNECTION_ISSUE);
        }
        foreach ($return as $key=>$val)
        {
            if ($val['@']['key'] == 'response_code') $status_key = $key;
            if ($val['@']['key'] == 'is_success') $success_key = $key;
            if ($val['@']['key'] == 'response_text') $status_text = $key;
            if ($val['@']['key'] == 'attributes') $attributes_key = $key;
        }

        CE_Lib::log(4, "ResellOne Registration Response: ".$return[$status_text]['#']);

        if (isset($attributes_key)) {
            $attributes = $return[$attributes_key]['#']['dt_assoc'][0]['#']['item'];
            if ($return[$success_key]['#'] == 1) {
                foreach ($attributes as $key=>$val) {
                    if ($val['@']['key'] == 'id') $regId = $val['#'];
                }
            }
        }
        $code = $return[$status_key]['#'];
        if ($code == 210 || $code == 200 || $code == 250) return array($regId);
        if ($code == 485) return array(0);

        throw new Exception("Domain registration failed: ".$return[$status_text]['#']);
    }

    // @access private
    function _plugin_resellone_validatePhone($phone, $country)
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

    function getContactInformation ($params)
    {
        throw new Exception('Getting Contact Information is not supported in this plugin.');
    }

    function setContactInformation ($params)
    {
        throw new Exception('Method setContactInformation() has not been implemented yet.');
    }

    function getNameServers ($params)
    {
        throw new Exception('Getting Name Server Records is not supported in this plugin.', EXCEPTION_CODE_NO_EMAIL);
    }

    function setNameServers ($params)
    {
        throw new Exception('Method setNameServers() has not been implemented yet.');
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

    function getGeneralInfo ($params)
    {
        throw new Exception('Method getGeneralInfo() has not been implemented yet.', EXCEPTION_CODE_NO_EMAIL);
    }

    function setAutorenew ($params)
    {
        throw new MethodNotImplemented('Method setAutorenew() has not been implemented yet.');
    }

    function getRegistrarLock ($params)
    {
        throw new Exception('Method getRegistrarLock() has not been implemented yet.', EXCEPTION_CODE_NO_EMAIL);
    }

    function setRegistrarLock ($params)
    {
        throw new Exception('Method setRegistrarLock() has not been implemented yet.');
    }

    function sendTransferKey ($params)
    {
        throw new Exception('Method sendTransferKey() has not been implemented yet.');
    }
    function disablePrivateRegistration($parmas)
    {
        throw new MethodNotImplemented('Method disablePrivateRegistration has not been implemented yet.');
    }
    function getTransferStatus($params)
    {
        throw new MethodNotImplemented('Method getTransferStatus has not been implemented yet.');
    }
}

?>
