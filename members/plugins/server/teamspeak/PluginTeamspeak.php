<?php
require_once 'library/CE/NE_MailGateway.php';
require_once 'plugins/server/teamspeak/class.teamspeakserver.php';
require_once 'modules/admin/models/ServerPlugin.php';
/**
* @package Plugins
*/
class PluginTeamspeak extends ServerPlugin
{
    public $features = array(
        'packageName' => true,
        'testConnection' => false,
        'showNameservers' => false
    );

    /*****************************************************************/
    // function getVariables - required function
    /*****************************************************************/
    function getVariables(){
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password
              description - description of the variable, displayed in ClientExec
              encryptable - used to indicate the variable's value must be encrypted in the database
        */


        $variables = array (
                   lang("Name") => array (
                                        "type"          => "hidden",
                                        "description"   => "Used By CE to show plugin - must match how you call the action function names",
                                        "value"         => "Teamspeak"
                                       ),
                   lang("Description") => array (
                                        "type"          => "hidden",
                                        "description"   => lang("Description viewable by admin in server settings"),
                                        "value"         => lang("Teamspeak voice server integration.  Note: The custom field settings are used to hold information about the clients server.  Please create these fields in admin->custom fields->packages first.  The package name on server fields for each package hold the slot count.  Suspending a server sets the slot count to 0.")
                                       ),
                   lang("Username") => array (
                                        "type"          => "text",
                                        "description"   => lang("Username used to connect to server"),
                                        "value"         => ""
                                       ),
                   lang("Password") => array (
                                        "type"          => "password",
                                        "description"   => lang("Password used to connect to server"),
                                        "value"         => "",
                                        "encryptable"   => true
                                       ),
                   lang("Starting Teamspeak Port Number") => array(
                                        "type"          => "text",
                                        "description"   => lang("Enter the starting teamspeak port number you'd like to use.  If the port is already in use it will use the next available port."),
                                        "value"         => "8767"
                                        ),
                   lang("Client Port Custom Field") => array(
                                        "type"          => "text",
                                        "description"   => lang("Enter the name of the package custom field that will hold the client teamspeak port number."),
                                        "value"         => ""
                                        ),
                   lang("Client Username Custom Field") => array(
                                        "type"          => "text",
                                        "description"   => lang("Enter the name of the package custom field that will hold the client teamspeak admin username."),
                                        "value"         => ""
                                        ),
                   lang("Client Password Custom Field") => array(
                                        "type"          => "text",
                                        "description"   => lang("Enter the name of the package custom field that will hold the client teamspeak admin password"),
                                        "value"         => ""
                                        ),
                   lang("Actions") => array (
                                        "type"          => "hidden",
                                        "description"   => lang("Current actions that are active for this plugin per server"),
                                        "value"         => "Create,Delete,Suspend,UnSuspend"
                                       )
        );
        return $variables;
    }

    function create($args)
    {
        if (    $args['package']['name_on_server'] == null                            ||
                $args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'] == ""        ||
                $args['server']['variables']['plugin_teamspeak_Client_Username_Custom_Field'] == ""    ||
                $args['server']['variables']['plugin_teamspeak_Client_Password_Custom_Field'] == ""
           )
            throw new CE_Exception ("Team Speak plugin not setup properly");

    	$user = $args['server']['variables']['plugin_teamspeak_Username'];
    	$pass = $args['server']['variables']['plugin_teamspeak_Password'];
    	$slotcount = $args['package']['name_on_server'];
    	$package = new UserPackage($args['package']['id'], $this->user);

    	$port = "";
    	$clientuser = "";
    	$clientpass = "";
    	$port = $package->getCustomField($args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
    	$clientuser = $package->getCustomField($args['server']['variables']['plugin_teamspeak_Client_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
    	$clientpass = $package->getCustomField($args['server']['variables']['plugin_teamspeak_Client_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

    	if ($clientuser == "") {
    	    $clientuser = "tsadmin";
    	    $package->setCustomField($args['server']['variables']['plugin_teamspeak_Client_Username_Custom_Field'], $clientuser, CUSTOM_FIELDS_FOR_PACKAGE);
    	}
    	if ($clientpass == "") {
    	    // generate some random password
    	    for ($i = 0; $i < 8; $i++) {
    	        $rand = rand(1, 3);
                switch ($rand) {
                    case 1:
                        $clientpass .= chr(rand(48, 57)); // [0-9]
                        break;
                    case 2:
                        $clientpass .= chr(rand(97, 122)); // [a-z]
                        break;
                    case 3:
                        $clientpass .= chr(rand(65, 90)); // [A-Z]
                        break;
                    }
    	    }
    	    $package->setCustomField($args['server']['variables']['plugin_teamspeak_Client_Password_Custom_Field'], $clientpass, CUSTOM_FIELDS_FOR_PACKAGE);
    	}



    	$tsServer = new TeamspeakServer(
                                       $args['server']['variables']['ServerHostName'],
                                       $args['server']['variables']['plugin_teamspeak_Username'],
                                       $args['server']['variables']['plugin_teamspeak_Password']
    	                               );
    	$return = $tsServer->connect();
        if (is_a($return, 'CE_Error')) {
            throw new CE_Exception($return->getMessage());
        }

        /* If a port is already defined then ensure it's available,
           otherwise find the next available port. */
    	if ($port != "") {
        	if (!$tsServer->checkPortAvailability($port)) {
        	    throw new CE_Exception('Port '. $port.' is not available.');
        	}
    	} else {
    	    $portList = $tsServer->getPortList();
    	    $currentPort = $args['server']['variables']['plugin_teamspeak_Starting_Teamspeak_Port_Number'];
    	    while (true) {
    	        if (!in_array($currentPort, $portList)) {
    	            $port = $currentPort;
    	            break;
    	        }
    	        $currentPort++;
    	    }
    	    $package->setCustomField($args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'], $port, CUSTOM_FIELDS_FOR_PACKAGE);
    	}
        $return = $tsServer->add(
                        $port,
                        $clientuser,
                        $clientpass,
                        $args['package']['name_on_server']
                      );
        return $return;
    }

    function delete($args)
    {
        if (    $args['package']['name_on_server'] == null                            ||
                $args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'] == ""        ||
                $args['server']['variables']['plugin_teamspeak_Client_Username_Custom_Field'] == ""    ||
                $args['server']['variables']['plugin_teamspeak_Client_Password_Custom_Field'] == ""
           ) throw new CE_Exception ("Team Speak plugin not setup properly");

    	$user = $args['server']['variables']['plugin_teamspeak_Username'];
    	$pass = $args['server']['variables']['plugin_teamspeak_Password'];
    	$package = new UserPackage($args['package']['id'], $this->user);

    	$port = "";
    	$port = $package->getCustomField($args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
    	if ($port == "") return;

        $tsServer = new TeamspeakServer(
                                       $args['server']['variables']['ServerHostName'],
                                       $args['server']['variables']['plugin_teamspeak_Username'],
                                       $args['server']['variables']['plugin_teamspeak_Password']
    	                               );
        $return = $tsServer->connect();
        if (is_a($return, 'CE_Error')) {
            throw new CE_Exception($return->getMessage());
        }

        return $tsServer->delete($port);
    }

    function update($args)
    {
        if ( $args['package']['name_on_server'] == null                            ||
                $args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'] == ""        ||
                $args['server']['variables']['plugin_teamspeak_Client_Username_Custom_Field'] == ""    ||
                $args['server']['variables']['plugin_teamspeak_Client_Password_Custom_Field'] == ""
           ) throw new CE_Exception ("Team Speak plugin not setup properly");

        $user = $args['server']['variables']['plugin_teamspeak_Username'];
    	$pass = $args['server']['variables']['plugin_teamspeak_Password'];
    	$slotcount = $args['package']['name_on_server'];
    	$package = new UserPackage($args['package']['id'], $this->user);

    	$port = "";
    	$port = $package->getCustomField($args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
    	if ($port == "") return;

    	if (isset($args['changes']['package'])) {
        	$tsServer = new TeamspeakServer(
                                           $args['server']['variables']['ServerHostName'],
                                           $args['server']['variables']['plugin_teamspeak_Username'],
                                           $args['server']['variables']['plugin_teamspeak_Password']
        	                               );
            $return = $tsServer->connect();
            if (is_a($return, 'CE_Error')) {
                throw new CE_Exception($return->getMessage());
            }
            return $tsServer->update($port, $args['package']['name_on_server']);
       }
    }

    function suspend($args) {
        if (    $args['package']['name_on_server'] == null                            ||
                $args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'] == ""        ||
                $args['server']['variables']['plugin_teamspeak_Client_Username_Custom_Field'] == ""    ||
                $args['server']['variables']['plugin_teamspeak_Client_Password_Custom_Field'] == ""
           ) throw new CE_Exception ("Team Speak plugin not setup properly");

        $user = $args['server']['variables']['plugin_teamspeak_Username'];
    	$pass = $args['server']['variables']['plugin_teamspeak_Password'];
    	$package = new UserPackage($args['package']['id'], $this->user);

    	$port = "";
    	$port = $package->getCustomField($args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
    	if ($port == "") return;
    	$tsServer = new TeamspeakServer(
                                       $args['server']['variables']['ServerHostName'],
                                       $args['server']['variables']['plugin_teamspeak_Username'],
                                       $args['server']['variables']['plugin_teamspeak_Password']
    	                               );
        $return = $tsServer->connect();
        if (is_a($return, 'CE_Error')) {
            throw new CE_Exception($return->getMessage());
        }
        return $tsServer->update($port, 0);
    }

    function unsuspend($args) {
        if (    $args['package']['name_on_server'] == null                            ||
                $args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'] == ""        ||
                $args['server']['variables']['plugin_teamspeak_Client_Username_Custom_Field'] == ""    ||
                $args['server']['variables']['plugin_teamspeak_Client_Password_Custom_Field'] == ""
           ) throw new CE_Exception ("Team Speak plugin not setup properly");
        $user = $args['server']['variables']['plugin_teamspeak_Username'];
    	$pass = $args['server']['variables']['plugin_teamspeak_Password'];
    	$package = new UserPackage($args['package']['id'], $this->user);

    	$port = "";
    	$port = $package->getCustomField($args['server']['variables']['plugin_teamspeak_Client_Port_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
    	if ($port == "") return;
    	$tsServer = new TeamspeakServer(
                                       $args['server']['variables']['ServerHostName'],
                                       $args['server']['variables']['plugin_teamspeak_Username'],
                                       $args['server']['variables']['plugin_teamspeak_Password']
    	                               );
        $return = $tsServer->connect();
        if (is_a($return, 'CE_Error')) {
            throw new CE_Exception($return->getMessage());
        }
        return $tsServer->update($port, $args['package']['name_on_server']);
    }

    function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->create($this->buildParams($userPackage));
        return 'Package has been created.';
    }

    function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->suspend($this->buildParams($userPackage));
        return 'Package has been suspended.';
    }

    function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->unsuspend($this->buildParams($userPackage));
        return 'Package has been unsuspended.';
    }

    function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->delete($this->buildParams($userPackage));
        return 'Package has been deleted.';
    }
}
?>
