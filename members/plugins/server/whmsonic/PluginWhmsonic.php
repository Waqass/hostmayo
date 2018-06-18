<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServerPlugin.php';

/**
* WHMSonic Server Plugin
* @package Plugins
* @version 1.0
* @author Matt Grandy
* @email matt@clientexec.com
*/

Class PluginWhmsonic extends ServerPlugin {

    public $features = array(
        'packageName' => false,
        'testConnection' => false,
        'showNameservers' => true
    );

    var $host;
    var $user;
    var $password;
    var $useSSL;
    var $url;
    var $port;
    var $schema;

    function setup ( $args ) {

        if ( isset($args['server']['variables']['ServerHostName']) && isset($args['server']['variables']['plugin_whmsonic_Username']) && isset($args['server']['variables']['plugin_whmsonic_Password']) && isset($args['server']['variables']['plugin_whmsonic_Use_SSL']) ) {
            $this->host = $args['server']['variables']['ServerHostName'];
            $this->user = $args['server']['variables']['plugin_whmsonic_Username'];
            $this->password = $args['server']['variables']['plugin_whmsonic_Password'];
            $this->useSSL = $args['server']['variables']['plugin_whmsonic_Password'];
            $this->port = ( $this->useSSL == true ) ? 2087 : 2086;
            $this->schema = ( $this->useSSL == true ) ? 'https://' : 'http://';
            $this->url = $this->schema . $this->host .':'. $this->port .'/whmsonic/modules/api.php?';

        } else {
            throw new CE_Exception('Missing Server Credentials: please fill out all information when editing the server.');
        }
    }

    function email_error ( $name, $message, $params, $args ) {
        $error = "WHMSonic Account " .$name." Failed. ";
        $error .= "An email with the Details was sent to ". $args['server']['variables']['plugin_whmsonic_Failure_E-mail'].'<br /><br />';

        if ( is_array($message) ) {
            $message = implode ( "\n", trim($message) );
        }

        CE_Lib::log(1, 'WHMSonic Error: '.print_r(array('type' => $name, 'error' => $error, 'message' => $message, 'params' => $params, 'args' => $args), true));

        if ( !empty($args['server']['variables']['plugin_whmsonic_Failure_E-mail']) ) {
            $mailGateway = new NE_MailGateway();
            $mailGateway->mailMessageEmail( $message,
            $args['server']['variables']['plugin_whmsonic_Failure_E-mail'],
            "WHMSonic Plugin",
            $args['server']['variables']['plugin_whmsonic_Failure_E-mail'],
            "",
            "WHMSonic Account ".$name." Failure");
        }
        return $error.nl2br($message);
    }

    function getVariables() {

        $variables = array (
            lang("Name") => array (
                "type"=>"hidden",
                "description"=>"Used by CE to show plugin - must match how you call the action function names",
                "value"=>"WHMSonic"
            ),
            lang("Description") => array (
                "type"=>"hidden",
                "description"=>lang("Description viewable by admin in server settings"),
                "value"=>lang("WHMSonic control panel integration")
            ),
            lang("Username") => array (
                "type"=>"text",
                "description"=>lang("Username used to connect to the server"),
                "value"=>""
            ),
            lang("Password") => array (
                "type"=>"textarea",
                "description"=>lang("Password used to connect to the server"),
                "value"=>"",
                "encryptable"=>true
            ),
            lang("Use SSL") => array (
                "type"=>"yesno",
                "description"=>lang("Set this to YES if SSL should be used to connect to the server"),
                "value"=>"1"
            ),
             lang("Radio Username Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the Radio Username."),
                "value"       => ""
            ),
            lang("Radio Password Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the Radio Password."),
                "value"       => ""
            ),
            lang("Failure E-mail") => array (
                "type"=>"text",
                "description"=>lang("E-mail address Cpanel error messages will be sent to"),
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
                "value"=>"0",
            ),
            lang("package_addons") => array (
                "type"=>"hidden",
                "description"=>lang("Supported signup addons variables"),
                "value"=>"AUTODJ,BANDWIDTH,BITRATE,LISTENERS",
            ),
            lang('package_vars')  => array(
                'type'            => 'hidden',
                'description'     => lang('Whether package settings are set'),
                'value'           => '0',
            ),

        );

        return $variables;
    }

    function doDelete($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->delete($args);
        return 'Radio has been deleted.';
    }

    function doCreate($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        return 'Radio has been created.';
    }

    function doSuspend($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->suspend($args);
        return 'Radio has been suspended.';
    }

    function doUnSuspend($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->unsuspend($args);
        return 'Radio has been unsuspended.';
    }

    function unsuspend($args) {
        $this->setup($args);
        $userPackage = new UserPackage($args['package']['id']);
        $params = array();

        $params['cmd'] = 'unsuspend';
        $params['rad_username'] = $userPackage->getCustomField($args['server']['variables']['plugin_whmsonic_Radio_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

        $this->call($params);
    }

    function suspend($args) {
        $this->setup($args);
        $userPackage = new UserPackage($args['package']['id']);
        $params = array();

        $params['cmd'] = 'suspend';
        $params['rad_username'] = $userPackage->getCustomField($args['server']['variables']['plugin_whmsonic_Radio_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $this->call($params);
    }

    function delete($args) {
        $this->setup($args);
        $userPackage = new UserPackage($args['package']['id']);
        $params = array();

        $params['cmd'] = 'terminate';
        $params['rad_username'] = $userPackage->getCustomField($args['server']['variables']['plugin_whmsonic_Radio_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $this->call($params);

    }

    function getAvailableActions($userPackage) {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $actions = array();

        $params = array();
        $params['cmd'] = 'status';
        $params['rad_username'] = $userPackage->getCustomField($args['server']['variables']['plugin_whmsonic_Radio_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

        try {
            $response = $this->call($params);
            $actions[] = 'Delete';
            if ( $response == 'suspended' ) {
                $actions[] = 'UnSuspend';
            } else {
                $actions[] = 'Suspend';
            }
        } catch (Exception $e) {
            $actions[] = 'Create';
        }

        return $actions;
    }

    function create($args) {
        $this->setup($args);
        $userPackage = new UserPackage($args['package']['id']);

        // Check to ensure all required addons are set
        if ( !isset($args['package']['addons']['AUTODJ']) ) {
            // AutoDJ isn't required, just turn it off if they don't use it.
            $args['package']['addons']['AUTODJ'] = 0;
        }

        if ( !isset($args['package']['addons']['BANDWIDTH']) ) {
            throw new CE_Exception ('Missing Bandwidth Addon');
        }

        if ( !isset($args['package']['addons']['BITRATE']) ) {
            throw new CE_Exception ('Missing Bitrate Addon');
        }

        if ( !isset($args['package']['addons']['LISTENERS']) ) {
            throw new CE_Exception ('Missing Listeners Addon');
        }

        // set autodj var to how WHMSonic wants it
        if ( $args['package']['addons']['AUTODJ'] == 1 ) {
            $autoDJ = 'yes';
        } else {
            $autoDJ = 'no';
        }

        $params['cmd'] = 'create';
        $params['ctype'] = 'External';
        $params['ip'] = $args['package']['ip'];
        $params['bitrate'] = $args['package']['addons']['BITRATE'];
        $params['autodj'] = $autoDJ;
        // bandwidth is in gb, so multiply by 1024.
        $params['bw'] = ($args['package']['addons']['BANDWIDTH']*1024);
        $params['limit'] = $args['package']['addons']['LISTENERS'];
        $params['cemail'] = $args['customer']['email'];
        $params['cname'] =  $args['customer']['first_name'] . ' ' . $args['customer']['last_name'];
        $params['rad_username'] = $userPackage->getCustomField($args['server']['variables']['plugin_whmsonic_Radio_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $params['pass'] = $userPackage->getCustomField($args['server']['variables']['plugin_whmsonic_Radio_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $this->call($params);
    }


    function call($params)
    {
        if ( !function_exists('curl_init') )
        {
            throw new CE_Exception('cURL is required in order to connect to WHMSonic');
        }

        CE_Lib::log(4, 'WHMSonic Params: ' . print_r($params, true));
        $ch = curl_init();
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->password}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $this->url);

        $data = curl_exec($ch);

        if ( $data === false )
        {
            $error = "WHMSonic API Request / cURL Error: ".curl_error($ch);
            CE_Lib::log(4, $error);
            throw new CE_Exception($error);
        }

        curl_close($ch);
        if ( $data == "Complete" || $data == 'active' || $data == 'suspended' ) {
            return $data;
        } else if ( strpos($data,"Login Attempt Failed!") == true ) {
            CE_Lib::log(4, 'Error connecting to WHMSonic Server, invalid username/password');
            throw new CE_Exception('Invalid username or password used to connect to WHMSonic server.');
        }  else {
            CE_Lib::log(4, $data);
            throw new CE_Exception($data);
        }
    }
}
