<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'library/CE/NE_Network.php';
require_once 'modules/admin/models/ServerPlugin.php';

/**
* WHMPHP Server Plugin
* @package Plugins
* @version 0.1
* @Author Matt Grandy
* @email matt@clientexec.com
*/

Class PluginWhmphp extends ServerPlugin {

    public $features = array(
        'packageName' => true,
        'testConnection' => false,
        'showNameservers' => true
    );

    var $host;
    var $username;
    var $password;

    function setup ( $args ) {
        if ( isset($args['server']['variables']['ServerHostName']) && isset($args['server']['variables']['plugin_whmphp_Username']) && isset($args['server']['variables']['plugin_whmphp_Password']) ) {
            $this->host = $args['server']['variables']['ServerHostName'];
            $this->username = $args['server']['variables']['plugin_whmphp_Username'];
            $this->password = $args['server']['variables']['plugin_whmphp_Password'];
        } else {
            throw new CE_Exception("Missing Server Credentials: please fill out all information when editing the server.");
        }

        if ( !isset($args['server']['nameservers'][0]['hostname']) || !isset($args['server']['nameservers'][1]['hostname']) ) {
            throw new CE_Exception('No nameservers are defined for this server.');
        }

        if ( !isset($args['package']['acl']['acl_diskspace']) || !isset($args['package']['acl']['acl_bandwidth']) || !isset($args['package']['acl']['acl_limit']) ) {
			throw new CE_Exception('Package Diskspace, Bandwidth and Limit are required.');
        }
        return;

    }

    function email_error ( $name, $message, $params, $args ) {
        $error = "WHMPHP Account " .$name." Failed. ";
        $error .= "An email with the Details was sent to ". $args['server']['variables']['plugin_whmphp_Failure_E-mail'].'<br /><br />';

        if ( is_array($message) ) {
            $message = implode ( "\n", trim($message) );
        }

        CE_Lib::log(1, 'WHMPHP Error: '.print_r(array('type' => $name, 'error' => $error, 'message' => $message, 'params' => $params, 'args' => $args), true));

        if ( !empty($args['server']['variables']['plugin_whmphp_Failure_E-mail']) ) {
            $mailGateway = new NE_MailGateway();
            $mailGateway->mailMessageEmail( $message,
            $args['server']['variables']['plugin_whmphp_Failure_E-mail'],
            "WHMPHP Plugin",
            $args['server']['variables']['plugin_whmphp_Failure_E-mail'],
            "",
            "WHMPHP Account ".$name." Failure");
        }
        return $error.nl2br($message);
    }

    function getVariables() {

        $variables = array (
            lang("Name") => array (
                "type"=>"hidden",
                "description"=>"Used by CE to show plugin - must match how you call the action function names",
                "value"=>"WHMPHP"
            ),
            lang("Description") => array (
                "type"=>"hidden",
                "description"=>lang("Description viewable by admin in server settings"),
                "value"=>lang("WHMPHP control panel integration")
            ),
            lang("Username") => array (
                "type"=>"text",
                "description"=>lang("Reseller Username"),
                "value"=>"",
                "encryptable"=>false
            ),
            lang("Password") => array (
                "type"=>"password",
                "description"=>lang("Reseller Password"),
                "value"=>"",
                "encryptable"=>true
            ),
            lang("Failure E-mail") => array (
                "type"=>"text",
                "description"=>lang("E-mail address Virualmin error messages will be sent to"),
                "value"=>""
            ),
            lang("Actions") => array (
                "type"=>"hidden",
                "description"=>lang("Current actions that are active for this plugin per server"),
                "value"=>"Create,Delete,Suspend,UnSuspend"
            ),
            lang("reseller") => array (
                "type"=>"hidden",
                "description"=>lang("Whether this server plugin can set reseller accounts"),
                "value"=>"1",
            ),
              lang("disable_reseller_checkbox") => array (
                "type"=>"hidden",
                "description"=>lang("Remove Is Reseller Checkbox?"),
                "value"=>"1",
            ),
            lang('reseller-fieldset')  => array(
                'type'          => 'fieldset',
                'name'          => 'reseller-fieldset',
                'label'   => lang('Account Specific Fields'),
                'description'   => '',
                'value'         => '1',
            ),
            lang("package_addons") => array (
                "type"=>"hidden",
                "description"=>lang("Supported signup addons variables"),
                "value"=>"",
            ),
            lang('package_vars')  => array(
                'type'            => 'hidden',
                'description'     => lang('Whether package settings are set'),
                'value'           => '1',
            ),
            lang('reseller_acl_fields') => array(
                'type'          => 'hidden',
                'description'   => lang('ACL field for reseller account'),
                'value'         => array(
                    array(
                        'name' => 'acl_diskspace',
                        'type' => 'text',
                        'label' => 'Disk Space',
                        'description' => lang('Disk Space in MB for this account.'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl_bandwidth',
                        'type' => 'text',
                        'label' => 'Bandwidth',
                        'description' => lang('Bandwidth in MB for this account.'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl_limit',
                        'type' => 'text',
                        'label' => 'Limit Reseller By Number',
                        'description' => lang('Enter the maximum number of accounts allowed under this reseller.'),
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl_diskspace-overselling',
                        'type' => 'yesno',
                        'label' => 'Allow Disk Space Overselling?',
                        'description' => '',
                        'belongsto' => 'reseller-fieldset'
                    ),
                    array(
                        'name' => 'acl_bandwidth-overselling',
                        'type' => 'yesno',
                        'label' => 'Allow Bandwidth Overselling?',
                        'description' => '',
                        'belongsto' => 'reseller-fieldset'
                    ),
                )
            )
        );

        return $variables;
    }

    function validateCredentials($args)
    {
        $args['package']['username'] = trim(strtolower($args['package']['username']));
        $errors = array();

        // Ensure that the username is not test and doesn't contain test
        if (strpos(strtolower($args['package']['username']), 'test') !== false) {
            if (strtolower($args['package']['username']) != 'test') {
                $args['package']['username'] = str_replace('test', '', $args['package']['username']);
            } else {
                $errors[] = 'Domain username can\'t contain \'test\'';
            }
        }

        // Username cannot start with a number
        if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1))) {
            $args['package']['username'] = preg_replace("/^\d*/", '', $args['package']['username']);

            if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1)) || strlen(trim($args['package']['username'])) == 0) {
                $errors[] = 'Domain username can\'t start with a number';
            }
        }

        // Username cannot contain a dash (-)
        if (strpos($args['package']['username'], "-") !== false) {
            $args['package']['username'] = str_replace("-", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain dashes';
        }

        // Username cannot contain a space
        if (strpos($args['package']['username'], " ") !== false) {
            $args['package']['username'] = str_replace(" ", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain spaces';
        }

        // Username cannot contain an underscore (_)
        if (strpos($args['package']['username'], "_") !== false) {
            $args['package']['username'] = str_replace("_", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain underscores';
        }

        // Username cannot be greater than 8 characters
        if (strlen($args['package']['username']) > 8) {
            $args['package']['username'] = mb_substr($args['package']['username'], 0, 8);
        }
        else if ( strlen(trim($args['package']['username'])) <= 0 ) {
                  $errors[] = 'The cPanel username is blank.';
        }
        else if ( strlen(trim($args['package']['password'])) <= 0 ) {
                  $errors[] = 'The cPanel password is blank';
        }

        // Only make the request if there have been no errors so far.
        if ( count($errors) == 0 ) {
                  if (strpos($args['package']['password'], $args['package']['username']) !== false) {
                      $errors[] = 'Domain password can\'t contain domain username';
                  }
        }

        // Check if we want to supress errors during signup and just return a valid username
        if(isset($args['noError'])) {
            return $args['package']['username'];
        } else {

            if ( count($errors) > 0 ) {
                CE_Lib::log(4, "plugin_cpanel::validate::error: ".print_r($errors,true));
                throw new CE_Exception($errors[0]);
            }
            return $args['package']['username'];
        }
    }

    function doDelete($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->delete($args);
        return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }

    function doCreate($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        return $userPackage->getCustomField("Domain Name") . ' has been created.';
    }

    function doSuspend($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->suspend($args);
        return $userPackage->getCustomField("Domain Name") . ' has been suspended.';
    }

    function doUnSuspend($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->unsuspend($args);
        return $userPackage->getCustomField("Domain Name") . ' has been unsuspended.';
    }

    function unsuspend($args) {
        $this->setup($args);
        $errors = array();

        $username = $args['package']['username'] ;
        $request = "/cgi/whmphp/master/index.cgi?page=api&action=unsuspendreseller&username=$username";

        $response = $this->makeRequest('whm', $request);

        if ( $response->status != 1 ) {
            $errors[] = $this->email_error('UnSuspension', $response->statusmsg, $args);
        }

        if ( count($errors) > 0 ) {
            CE_Lib::log(4, "plugin_whmphp::unsuspend::error: ".print_r($errors,true));
            throw new CE_Exception ( $errors[0] );
        }

        return;
    }
    function suspend($args) {
        $this->setup($args);
        $errors = array();

        $username = $args['package']['username'];
        $request = "/cgi/whmphp/master/index.cgi?page=api&action=suspendreseller&username=$username";

        $response = $this->makeRequest('whm', $request);

        if ( $response->status != 1 ) {
            $errors[] = $this->email_error('Suspension', $response->statusmsg, $args);
        }

        if ( count($errors) > 0 ) {
            CE_Lib::log(4, "plugin_whmphp::suspend::error: ".print_r($errors,true));
            throw new CE_Exception ( $errors[0] );
        }

        return;
    }

    function delete($args) {
        $this->setup($args);
        $errors = array();

        $username = $args['package']['username'] ;
        $request = "/cgi/whmphp/master/index.cgi?page=api&action=terminatereseller&username=$username";

        $response = $this->makeRequest('whm', $request);

        if ( $response->status != 1 ) {
            $errors[] = $this->email_error('Delete', $response->statusmsg, $args);
        }

        if ( count($errors) > 0 ) {
            CE_Lib::log(4, "plugin_whmphp::delete::error: ".print_r($errors,true));
            throw new CE_Exception ( $errors[0] );
        }

        return;
    }

    function create($args) {

        $this->setup($args);
        $errors = array();

		$package = rawurlencode($args['package']['name_on_server']);
        $domain = $args['package']['domain_name'];
        $username = $args['package']['username'];
        $email = $args['customer']['email'];
        $password = $args['package']['password'];
        $request = "/cgi/whmphp/master/index.cgi?page=api&action=createreseller&domain=$domain&username=$username&email=$email&password=$password&package=$package";

        $response = $this->makeRequest('whm', $request);
        if ( $response->status != 1 ) {
            $errors[] = $this->email_error('Creation', $response->statusmsg, $args);
        }
        else if ( $response->status == 1 ) {
            if ( isset($args['package']['acl']['acl_diskspace']) && $args['package']['acl']['acl_diskspace'] > 0 ) {

                $diskspace = $args['package']['acl']['acl_diskspace'];
                $bandwidth = $args['package']['acl']['acl_bandwidth'];
                $limit = $args['package']['acl']['acl_limit'];
                $ns1 = $args['server']['nameservers'][0]['hostname'];
                $ns2 = $args['server']['nameservers'][1]['hostname'];
                $oversellDisk = ( strtolower($args['package']['acl']['acl_diskspace']) == 'yes') ? 1 : 0;
                $overSellBandwidth = ( strtolower($args['package']['acl']['acl_diskspace']) == 'yes' ) ? 1 : 0;
                $request = "/cgi/whmphp/master/index.cgi?page=api&action=setlimit&username=$username&disk=$diskspace&band=$bandwidth&limit=$limit&ns1=$ns1&ns2=$ns2&oversell_disk=$oversellDisk&oversell_bw=$overSellBandwidth";

                $response = $this->makeRequest('whm', $request);
                if ( $response->status != 1 ) {
                    $errors[] = $this->email_error('Creation', $response->statusmsg, $args);
                }
            }
        }

        if ( count($errors) > 0 ) {
            CE_Lib::log(4, "plugin_whmphp::create::error: ".print_r($errors,true));
            throw new CE_Exception ( $errors[0] );
        }

        return;
    }

    function makeRequest($type, $request)
    {
        $port = ($type == 'whm') ? 2087 : 2083;
        $url = 'https://' . $this->host . ':' . $port . $request;
        $auth = $this->username . ':' . $this->password;

        $data = NE_Network::curlRequest($this->settings, $url, false, false, true, false, false, $auth);
        if ( $data instanceof CE_Error ) {
            $error = 'WHMPHP Error: ' . $data->errMessage;
            throw new CE_Exception($error);
        }
        $response = json_decode($data);
        if ( !is_object($response) ) {
            // invalid json... check raw for an SSL error
            if ( strpos($data, 'SSL encryption is required for access to this server') ) {
                CE_Lib::log(1, "Error from cPanel: SSL encryption is required for access to this server.");
                throw new CE_Exception ('Error from cPanel: SSL encryption is required for access to this server.');
            }
            throw new CE_Exception("Cpanel call method: Invalid JSON please check your connection");
        }
        else if ( isset($response->data->result) && $response->data->result == 0 ) {
            CE_Lib::log(4, 'cPanel Result: '.$response->data->reason);
            throw new CE_Exception("Cpanel returned an error: ".$response->data->reason);
        }
        else if ( isset($response->status) && $response->status == 0 ) {
            CE_Lib::log(4, 'cPanel Status: '.$response->statusmsg);
            throw new CE_Exception("Cpanel returned an error: ".$response->statusmsg);
        }
        return $response;
    }

    function getAccountSummary($user) {
        $response = $this->makeRequest('whm', '/json-api/accountsummary?user=' . $user);
        return $response;
    }
}
