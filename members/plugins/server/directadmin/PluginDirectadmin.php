<?php
require_once 'library/CE/NE_MailGateway.php';

/*****************************************************************/
// function plugin_directadmin_variables - required function
/*****************************************************************/
require_once 'modules/admin/models/ServerPlugin.php';
/**
* @package Plugins
*/
class PluginDirectAdmin extends ServerPlugin
{
    public $features = array(
        'packageName' =>  true,
        'testConnection' => false,
        'showNameservers' => true
    );
    public $usesPackageName = true;

    function getVariables()
    {
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password,hidden ( type hidden are variables used by CE and are required )
              description - description of the variable, displayed in ClientExec
              encryptable - used to indicate the variable's value must be encrypted in the database
        */

        $variables = array (
                   lang("Name") => array (
                                        "type"=>"hidden",
                                        "description"=>"Used By CE to show plugin - must match how you call the action function names",
                                        "value"=>"DirectAdmin"
                                       ),
                   lang("Description") => array (
                                        "type"=>"hidden",
                                        "description"=>lang("Description viewable by admin in server settings"),
                                        "value"=>lang("DirectAdmin control panel integration")
                                       ),
                   lang("Username") => array (
                                        "type"=>"text",
                                        "description"=>lang("Username used to connect to server"),
                                        "value"=>""
                                       ),
                   lang("Password") => array (
                                        "type"=>"password",
                                        "description"=>lang("Password used to connect to server"),
                                        "value"=>"",
                                        "encryptable"=>true
                                       ),
                   lang("Failure E-mail") => array (
                                        "type"=>"text",
                                        "description"=>lang("An E-mail will be sent to this E-mail address in case of a failure"),
                                        "value"=>""
                                       ),
                   lang("Use SSL") => array (
                                        "type"=>"yesno",
                                        "description"=> '',
                                        "value"=>"1"
                                       ),
                   lang("Port") => array (
                                        "type"=>"text",
                                        "description"=>lang("Port used to connect to server"),
                                        "value"=>"2222"
                                       ),
                   lang('reseller')  => array(
                                        'type'          => 'hidden',
                                        'description'   => lang('Whether this server plugin can set reseller accounts'),
                                        'value'         => '1',
                                       ),
                   lang("Actions") => array (
                                        "type"=>"hidden",
                                        "description"=>lang("Current actions that are active for this plugin per server"),
                                        "value"=>"Create,Delete,Update,Suspend,UnSuspend"
                                       )
        );
        return $variables;
    }

    function validateCredentials($args)
    {
        // direct admin only allows for all lowercase usernames.
        $args['package']['username'] = trim(strtolower($args['package']['username']));

        return $args['package']['username'];
    }

    function processResult($result)
    {
        $return = array();
        if ( substr($result, 0, 7) == 'error=1' ) {
            $msg = explode('&', $result);
            $return['error'] = '1';
            $return['msg'] = $msg[1];
        }
        else {
            $return['error'] = '0';
        }
        return $return;
    }

    //plugin function called after new account is activated
    function create($args)
    {
        $errormsg = "";

        $packagename = $args['package']['name_on_server'];

        //try to create the account
        if (isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
            $cmd = '/CMD_API_ACCOUNT_RESELLER';
            $ip = 'shared';
        } else {
            $cmd = '/CMD_API_ACCOUNT_USER';
            $ip = $args['package']['ip'];
        }
        $sock = new DA($this->settings);
        $sock->connect($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_directadmin_Port'], $args['server']['variables']['plugin_directadmin_Use_SSL']);
        $sock->set_login($args['server']['variables']['plugin_directadmin_Username'], $args['server']['variables']['plugin_directadmin_Password']);
        $sock->set_method('POST');
        $tArray = array(  'action' => 'create',
                'add' => 'Submit',
                'username' => $args['package']['username'],
                'email' => $args['customer']['email'],
                'passwd' => $args['package']['password'],
                'passwd2' => $args['package']['password'],
                'domain' => $args['package']['domain_name'],
                'package' => $packagename,
                'ip' => $ip,
                'notify' => 'no'
        );

        $result = $sock->query($cmd, $tArray);

        // Log the result
        CE_Lib::log(4, "plugin_directadmin::create::result: ".$result);
        $result = $this->processResult($result);

        // Check the results
        if ($result['error'] == '1') {
            // Start the mailer
            $mailGateway = new NE_MailGateway();

            $mailGateway->mailMessageEmail("DirectAdmin plugin: A failure occurred while connecting to the DA server. Message Returned: {$result['msg']}.",
                    $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                    "DirectAdmin Plugin",
                    $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                    "",
                    "[CE] DirectAdmin plugin: Connection to DA server failed");

            // Create and log the error. Then throw an error.
            $errormsg = "A failure occurred while connecting to the DA server. An E-mail with details has been sent to ".$args['server']['variables']['plugin_directadmin_Failure_E-mail'].". Please note that your query has not been executed on the server.";
            CE_Lib::log(4, "plugin_directadmin::create::error: ".$result['msg']);

            throw new CE_Exception($errormsg);
        }
        return;
    }

    function Delete($args)
    {
        $sock = new DA($this->settings);
        $sock->connect($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_directadmin_Port'], $args['server']['variables']['plugin_directadmin_Use_SSL']);
        $sock->set_login($args['server']['variables']['plugin_directadmin_Username'],$args['server']['variables']['plugin_directadmin_Password']);
        $sock->set_method('POST');
        $tArray = array( 'confirmed' => 'Confirm',
            'delete' => 'yes',
            'select0' => $args['package']['username']
        );
        $result = $sock->query('/CMD_API_SELECT_USERS',$tArray);
        // Log the result
        CE_Lib::log(4, "Directadmin::delete::result:: ".$result);

        $result = $this->processResult($result);

        // Check the results
        if ($result['error'] == '1') {
            $mailGateway = new NE_MailGateway();

            $mailGateway->mailMessageEmail("DirectAdmin plugin: A failure occurred while deleting ".$args['package']['username'].".  Message Returned: {$result['msg']}.",
                $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                "DirectAdmin Plugin",
                $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                "",
                "[CE] DirectAdmin plugin: Failure on deleting user."
            );

            // Create and log the error. Then throw an error.
            $errormsg = "A failure occurred while deleting a user. An E-mail with details has been sent to ".$args['server']['variables']['plugin_directadmin_Failure_E-mail'].".";
            CE_Lib::log(4, "plugin_directadmin::delete::error: ".$errormsg);

            throw new CE_Exception($errormsg);
        }
    }

    function Update($args)
    {
        $errormsg = "";

        //Determine what package name to use.  If server variable package name is entered then use that
        $packagename = $args['package']['name_on_server'];

        // Start the connections
        $sock = new DA($this->settings);
        $sock->connect($args['server']['variables']['ServerHostName'], 2222, $args['server']['variables']['plugin_directadmin_Use_SSL']);
        $sock->set_login($args['server']['variables']['plugin_directadmin_Username'],$args['server']['variables']['plugin_directadmin_Password']);
        $sock->set_method('POST');

        foreach ( $args['changes'] as $key => $value ) {
            $mailGateway = new NE_MailGateway();

            switch ( $key ) {
                case 'password':
                    $tArray = array(
                        'username' => $args['package']['username'],
                        'passwd' => $value,
                        'passwd2' => $value
                    );
                    $result = $sock->query('/CMD_API_USER_PASSWD', $tArray);
                    $result = $this->processResult($result);
                    CE_Lib::log(4, "Directadmin::update::password::result:: ".$result);

                    if ($result['error'] == '1') {
                        $mailGateway->mailMessageEmail("DirectAdmin plugin: A failure occurred while changing the password of ".$args['package']['username'].".  Message Returned: {$result['msg']}.",
                            $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                            "DirectAdmin Plugin",
                            $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                            "",
                            "[CE] DirectAdmin plugin: Failure on changing password");

                        // Create and log the error. Then throw an error.
                        $errormsg = "A failure occurred while changing the password. An E-mail with details has been sent to ".$args['server']['variables']['plugin_directadmin_Failure_E-mail'].".";
                        CE_Lib::log(4, "plugin_directadmin::update::error: ".$errormsg);

                        throw new CE_Exception($errormsg);
                    }
                    break;

                case 'ip':
                    $tArray = array(
                        'action' => 'ip',
                        'user' => $args['package']['username'],
                        'ip' => $value
                    );

                    $result = $sock->query('/CMD_MODIFY_USER',$tArray);
                    if ($result['error'] == '1') {
                        $mailGateway->mailMessageEmail("DirectAdmin plugin: A failure occurred while changing the IP of user ".$args['package']['username'].". Message Returned: {$result['msg']}.",
                            $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                            "DirectAdmin Plugin",
                            $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                            "",
                            "[CE] DirectAdmin plugin: Failure on changing IP");

                        // Create and log the error. Then throw an error.
                        $errormsg = "A failure occurred while changing the IP. An E-mail with details has been sent to ".$args['server']['variables']['plugin_directadmin_Failure_E-mail'].".";
                        CE_Lib::log(4, "plugin_directadmin::update::error: ".$errormsg);

                        throw new CE_Exception($errormsg);
                    }
                break;

                case 'package':
                    $tArray = array(
                        'action' => 'package',
                        'user' => $args['package']['username'],
                        'package' => $value
                    );

                    $result = $sock->query('/CMD_MODIFY_USER',$tArray);
                    CE_Lib::log(4, "Directadmin::update::package::result:: ".$result);
                    $result = $sock->query('/CMD_MODIFY_USER',$tArray);
                    if ($result['error'] == '1') {
                        $mailGateway->mailMessageEmail("DirectAdmin plugin: A failure occurred while changing the package of user ".$args['package']['username'].". Message Returned: {$result['msg']}.",
                            $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                            "DirectAdmin Plugin",
                            $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                            "",
                            "[CE] DirectAdmin plugin: Failure on changing package");

                        // Create and log the error. Then throw an error.
                        $errormsg = "A failure occurred while changing the package. An E-mail with details has been sent to ".$args['server']['variables']['plugin_directadmin_Failure_E-mail'].".";
                        CE_Lib::log(4, "plugin_directadmin::update::error: ".$errormsg);

                        throw new CE_Exception($errormsg);
                    }
                    break;
            }
        }
        return;
    }

    function Suspend($args)
    {
        // Start the connections
        $sock = new DA($this->settings);
        $sock->connect($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_directadmin_Port'], $args['server']['variables']['plugin_directadmin_Use_SSL']);
        $sock->set_login($args['server']['variables']['plugin_directadmin_Username'],$args['server']['variables']['plugin_directadmin_Password']);
        $sock->set_method('POST');
        $tArray = array( 'location' => 'CMD_SELECT_USERS',
            'suspend'  => 'Suspend/Unsuspend',
            'select0'  => $args['package']['username'],
            'dosuspend'	=> 1
        );
        $result = $sock->query('/CMD_API_SELECT_USERS',$tArray);

        // Log the result
        CE_Lib::log(4, "Directadmin::suspend::result:: ".$result);
        $result = $this->processResult($result);

         if ($result['error'] == '1') {
            $mailGateway = new NE_MailGateway();

            $mailGateway->mailMessageEmail("DirectAdmin plugin: A failure occurred while suspending ".$args['package']['username'].".  Message Returned: {$result['msg']}.",
                $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                "DirectAdmin Plugin",
                $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                "",
                "[CE] DirectAdmin plugin: Failure on suspending user."
            );

            // Create and log the error. Then throw an error.
            $errormsg = "A failure occurred while suspending a user. An E-mail with details has been sent to ".$args['server']['variables']['plugin_directadmin_Failure_E-mail'].".";
            CE_Lib::log(4, "plugin_directadmin::suspend::error: ".$errormsg);

            throw new CE_Exception($errormsg);
        }
    }

    function UnSuspend($args)
    {
        // Start the connections
        $sock = new DA($this->settings);
        $sock->connect($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_directadmin_Port'], $args['server']['variables']['plugin_directadmin_Use_SSL']);
        $sock->set_login($args['server']['variables']['plugin_directadmin_Username'],$args['server']['variables']['plugin_directadmin_Password']);
        $sock->set_method('POST');
        $tArray = array( 'location' => 'CMD_SELECT_USERS',
            'suspend'  => 'Suspend/Unsuspend',
            'select0'  => $args['package']['username'],
            'dounsuspend' => 1
        );
        $result = $sock->query('/CMD_API_SELECT_USERS',$tArray);

        // Log the result
        CE_Lib::log(4, "Directadmin::unsuspend::result:: ".$result);
        $result = $this->processResult($result);

         if ($result['error'] == '1') {
            $mailGateway = new NE_MailGateway();

            $mailGateway->mailMessageEmail("DirectAdmin plugin: A failure occurred while unsuspending ".$args['package']['username'].".  Message Returned: {$result['msg']}.",
                $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                "DirectAdmin Plugin",
                $args['server']['variables']['plugin_directadmin_Failure_E-mail'],
                "",
                "[CE] DirectAdmin plugin: Failure on suspending user."
            );

            // Create and log the error. Then throw an error.
            $errormsg = "A failure occurred while unsuspending a user. An E-mail with details has been sent to ".$args['server']['variables']['plugin_directadmin_Failure_E-mail'].".";
            CE_Lib::log(4, "plugin_directadmin::unsuspend::error: ".$errormsg);

            throw new CE_Exception($errormsg);
        }
    }

    function doCreate($args)
    {
            $userPackage = new UserPackage($args['userPackageId']);
            $this->create($this->buildParams($userPackage));
            return $userPackage->getCustomField("Domain Name") .  ' has been created.';
    }

    function doUpdate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->update($this->buildParams($userPackage, $args));
        return $userPackage->getCustomField("Domain Name") .  ' has been update.';
    }

    function doSuspend($args)
    {
            $userPackage = new UserPackage($args['userPackageId']);
            $this->suspend($this->buildParams($userPackage));
            return $userPackage->getCustomField("Domain Name") .  ' has been suspended.';
    }

    function doUnSuspend($args)
    {
            $userPackage = new UserPackage($args['userPackageId']);
            $this->unsuspend($this->buildParams($userPackage));
            return $userPackage->getCustomField("Domain Name") .  ' has been unsuspended.';
    }

    function doDelete($args)
    {
            $userPackage = new UserPackage($args['userPackageId']);
            $this->delete($this->buildParams($userPackage));
            return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }

    function doCheckUserName($args)
    {
            $userPackage = new UserPackage($args['userPackageId']);
            return $this->checkUserName($this->buildParams($userPackage));
    }

    function checkUserName($args)
    {
        $sock = new DA($this->settings);
        $sock->connect($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_directadmin_Port'], $args['server']['variables']['plugin_directadmin_Use_SSL']);
        $sock->set_login($args['server']['variables']['plugin_directadmin_Username'], $args['server']['variables']['plugin_directadmin_Password']);
        $sock->set_method('GET');
        $str = 'user=' .  $args['package']['username'];
        $result = $sock->query('/CMD_API_SHOW_USER_CONFIG?' . $str);
        $result = $this->processResult($result);

        // Check the results
        if ($result['error'] == '1')
            return false;
        else
            return true;
    }

    function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $actions = array();
        $sock = new DA($this->settings);
        $sock->connect($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_directadmin_Port'], $args['server']['variables']['plugin_directadmin_Use_SSL']);
        $sock->set_login($args['server']['variables']['plugin_directadmin_Username'], $args['server']['variables']['plugin_directadmin_Password']);
        $sock->set_method('GET');
        $str = 'user=' .  $args['package']['username'];
        $result = $sock->query('/CMD_API_SHOW_USER_CONFIG?' . $str);
        if ( substr($result, 0, 7) == 'error=1') {
            $actions[] = 'Create';
        } else {
            $info = explode('&', $result);
            $suspended = '';
            foreach ( $info as $i ) {
                $tmp = explode('=', $i);
                if ( $tmp[0] == 'suspended' ) {
                    $suspended = $tmp[1];
                    break;
                }
            }
            if ( $suspended == 'yes' )
                $actions[] = 'UnSuspend';
            else
                $actions[] = 'Suspend';

            $actions[] = 'Delete';
        }
        return $actions;
    }
}

class DA {
    var $method = 'GET';
    var $host;
    var $port;
    var $user;
    var $pass;
    var $useSSL;
    var $settings;

    function __construct($settings)
    {
        $this->settings = $settings;
    }

    function connect($host, $port, $useSSL)
    {
        if ( substr($host, 0, 6) == 'ssl://' ) {
            $this->host = substr($host, 6);
        } else {
            $this->host = $host;
        }
        $this->port = $port;
        $this->useSSL = $useSSL;
    }

    function set_login($uname, $passwd)
    {
        $this->user = $uname;
        $this->pass = $passwd;
    }

    function set_method( $method = 'GET' )
    {
        $this->method = strtoupper($method);
    }

    function query( $request, $content = '' )
    {
        $url = ($this->useSSL) ? 'https://' : 'http://';
        $url .=  "{$this->host}:{$this->port}{$request}";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);

        if ( $this->method == 'POST' ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }

        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $this->result = curl_exec($ch);
        if ( curl_errno($ch) ) {
            curl_close($ch);
            throw new CE_Exception('DirectAdmin Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $this->result;
    }

    function fetch_result()
    {
        return $this->result;
    }
}
