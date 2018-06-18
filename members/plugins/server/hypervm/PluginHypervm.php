<?php
require_once 'modules/admin/models/ServerPlugin.php';

require_once 'library/CE/NE_MailGateway.php';
require_once 'library/CE/NE_Network.php';

/**
* @package Plugins
*/
class PluginHypervm extends ServerPlugin
{
    public $features = array(
        'packageName' => true,
        'testConnection' => false,
        'showNameservers' => true
    );

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
                                        "value"=>"Hypervm"
                                       ),
                   lang("Description") => array (
                                        "type"=>"hidden",
                                        "description"=>lang("Description viewable by admin in server settings"),
                                        "value"=>lang("HyperVM control panel integration")
                                       ),
                    lang("HyperVM API url") => array (
                                    "type"            =>    "text",
                                    "description"     =>    lang("Example http://cp.yourdomain.com:8888/webcommand.php"),
                                    "value"           =>    ""
                            ),
                   lang("Username") => array (
                                        "type"=>"text",
                                        "description"=>lang("Username used to connect to HyperVM"),
                                        "value"=>""
                                       ),
                   lang("Password") => array (
                                        "type"=>"password",
                                        "description"=>lang("Password used to connect to HyperVM"),
                                        'value'         => '',
                                        'encryptable'   => true,
                                       ),
                   lang("Failure E-mail") => array (
                                        "type"=>"text",
                                        "description"=>lang("E-mail address HyperVM error messages will be sent to"),
                                        "value"=>""
                                        ),
                    lang("VM Type") => array(
                                    "type"            =>    "text",
                                    "description"     => lang("Enter the type of VM this server creates.  (openvz or xen)"),
                                    "value"           => "openvz"
                            ),
                    lang("VM Name Custom Field") => array(
                                    "type"            =>    "text",
                                    "description"     => lang("Enter the name of the package custom field that will hold the VM name for HyperVM."),
                                    "value"           => ""
                            ),
                    lang("VM Password Custom Field") => array(
                                    "type"            => "text",
                                    "description"     => lang("Enter the name of the package custom field that will hold the VM password"),
                                    "value"           => ""
                            ),
                    lang("Number Of Ips Custom Field") => array(
                                    "type"            =>    "text",
                                    "description"     => lang("Enter the name of the package custom field that will hold the Number Of Ips for HyperVM."),
                                    "value"           => ""
                            ),
                    lang("Hostname Custom Field") => array(
                                    "type"            =>    "text",
                                    "description"     => lang("Enter the name of the package custom field that will hold the Hostname for HyperVM."),
                                    "value"           => ""
                            ),
                    lang("Ostemplate Custom Field") => array(
                                    "type"            =>    "text",
                                    "description"     => lang("Enter the name of the package custom field that will hold the Ostemplate for HyperVM."),
                                    "value"           => ""
                            ),
                   lang("Actions") => array (
                                        "type"=>"hidden",
                                        "description"=>lang("Current actions that are active for this plugin per server"),
                                        //"value"=>"Create (Create Account),Delete (Delete Account),Suspend (Suspend Account),UnSuspend (Un-Suspend Account)"
										"value"=>"Create,Delete,Suspend,UnSuspend"
                                       ),
           );
        return $variables;
    }

    function create($args) {

        $args = $this->set_all_args($args);
        $newVPS = array();

        $newVPS['login-class'] = "client";
        $newVPS['action'] = "add";
        $newVPS['class'] = "vps";
        $newVPS['v-type'] = trim($args['server']['variables']['plugin_hypervm_VM_Type']);

        if ($newVPS['v-type'] != 'openvz' && $newVPS['v-type'] != 'xen') {

            CE_Lib::log(4, "HyperVM Create Fail: VM type must be one of openvz or xen.");

            throw new CE_Exception($this->user->lang("HyperVM Create Fail: VM type must be one of openvz or xen.", 200));
        }

        if(isset($args['vmname']) && ($args['vmname'] != "")) {
            $newVPS['name'] = $args['vmname'];
        }
        if(isset($args['vmnumofips']) && ($args['vmnumofips'] != "")) {
            $newVPS['v-num_ipaddress_f'] = $args['vmnumofips'];
        }

        if(isset($args['customer']['email']) && ($args['customer']['email'] != "")) {
            $newVPS['v-contactemail'] = $args['customer']['email'];
        }

        $newVPS['v-send_welcome_f'] = "on";

        if(isset($args['vmpassword']) && ($args['vmpassword'] != "")) {
            $newVPS['v-password'] = $args['vmpassword'];
        }
        if(isset($args['vmostemplate']) && ($args['vmostemplate'] != "")) {
            $newVPS['v-ostemplate'] = $args['vmostemplate'];
        }
        if(isset($args['package']['ip']) && ($args['package']['ip'] != "")) {
            $newVPS['v-syncserver'] = $args['package']['ip'];
        }
        if(isset($args['package']['name_on_server']) && ($args['package']['name_on_server'] != "")) {
            $newVPS['v-plan_name'] = $args['package']['name_on_server'];
        }
        if(isset($args['vmhostname']) && ($args['vmhostname'] != "")) {
            $newVPS['v-hostname'] = $args['vmhostname'];
        }

        $create = $this->sendtohypervm($args,$newVPS);
        if (is_a($create, 'CE_Error')) {

            // Create and log the error. Then throw an error.
            $errormsg = "HyperVM Create Fail.";
            CE_Lib::log(4, "plugin_hypervm::create::error: ".$errormsg);

            throw new CE_Exception($errormsg);

        } else {

            // Log the result
            CE_Lib::log(4, "HyperVm:: Returned" . print_r($create,true));

            return;
        }

    }

    // HyperVM does not support this currently.
    function update($args) {
        $args = $this->set_all_args($args);
        $updateVPS = array();
        $updateVPS['login-class'] = "client";
        $updateVPS['class'] = "vps";
        $updateVPS['name'] = $args['vmname'].".vm";
        $updateVPS['action'] = "update";
        $updateVPS['subaction'] = "change_plan";
        $updateVPS['v-resourceplan_name'] = $args['PackageNameOnServer'];
        $update = $this->sendtohypervm($args,$updateVPS);

        // Check the result
        if (is_a($update, 'CE_Error')) {

            // Create and log the error. Then throw an error.
            $errormsg = "HyperVM Update Account Failure for VPS {$args['vmname']}.vm";
            CE_Lib::log(4, "plugin_hypervm::update::error: ".$errormsg);

            throw new CE_Exception($errormsg);

        } else {

            // Log the result
            CE_Lib::log(4, "HyperVm:: Returned" . print_r($update,true));

            return;
        }
    }

    function suspend($args) {

        $args = $this->set_all_args($args);
        $suspendVPS = array();
        $suspendVPS['login-class'] = "client";
        $suspendVPS['class'] = "vps";
        $suspendVPS['name'] = $args['vmname'].".vm";
        $suspendVPS['action'] = "update";
        $suspendVPS['subaction'] = "disable";
        $suspend = $this->sendtohypervm($args,$suspendVPS);

        // Check the result
        if (is_a($suspend, 'CE_Error')) {

            // Create and log the error. Then throw an error.
            $errormsg = "HyperVM Suspend Account Failure for VPS {$args['vmname']}.vm";
            CE_Lib::log(4, "plugin_hypervm::suspend::error: ".$errormsg);

            throw new CE_Exception($errormsg);

        } else {

            // Log the result
            CE_Lib::log(4, "HyperVm:: Returned" . print_r($suspend,true));

            return;
        }
    }

    function unsuspend($args) {

        $args = $this->set_all_args($args);
        $suspendVPS = array();
        $suspendVPS['login-class'] = "client";
        $suspendVPS['class'] = "vps";
        $suspendVPS['name'] = $args['vmname'].".vm";
        $suspendVPS['action'] = "update";
        $suspendVPS['subaction'] = "enable";
        $suspend = $this->sendtohypervm($args,$suspendVPS);

        // Check the result
        if (is_a($suspend, 'CE_Error')) {

            // Create and log the error. Then throw an error.
            $errormsg = "HyperVM Unsuspend Account Failure for VPS {$args['vmname']}.vm";
            CE_Lib::log(4, "plugin_hypervm::suspend::error: ".$errormsg);

            throw new CE_Exception($errormsg);

        } else {

            // Log the result
            CE_Lib::log(4, "HyperVm:: Returned" . print_r($suspend,true));

            return;
        }
    }

    function delete($args) {
        $args = $this->set_all_args($args);
        $suspendVPS = array();
        $suspendVPS['login-class'] = "client";
        $suspendVPS['class'] = "vps";
        $suspendVPS['name'] = $args['vmname'].".vm";
        $suspendVPS['action'] = "delete";
        $suspend = $this->sendtohypervm($args,$suspendVPS);

        // Check the result
        if (is_a($suspend, 'CE_Error')) {

            // Create and log the error. Then throw an error.
            $errormsg = "HyperVM Delete Account Failure for VPS {$args['vmname']}.vm";
            CE_Lib::log(4, "plugin_hypervm::suspend::error: ".$errormsg);

           throw new CE_Exception($errormsg);

        } else {

            // Log the result
            CE_Lib::log(4, "HyperVm:: Returned" . print_r($suspend,true));

            return;
        }
    }

    function set_all_args($args) {

        $package = new UserPackage($args['package']['id'], $this->user);
        $vmname = $package->getCustomField($args['server']['variables']['plugin_hypervm_VM_Name_Custom_Field']);
        $vmpass = $package->getCustomField($args['server']['variables']['plugin_hypervm_VM_Password_Custom_Field']);
        $noofip = $package->getCustomField($args['server']['variables']['plugin_hypervm_Number_Of_Ips_Custom_Field']);
        $vmhostname = $package->getCustomField($args['server']['variables']['plugin_hypervm_Hostname_Custom_Field']);
        $ostemplate = $package->getCustomField($args['server']['variables']['plugin_hypervm_Ostemplate_Custom_Field']);

        $args['vmname'] = $vmname;
        $args['vmpassword'] = $vmpass;
        $args['vmnumofips'] = $noofip;
        $args['vmhostname'] = $vmhostname;
        $args['vmostemplate'] = $ostemplate;
        $args['package_class'] = $package;
        return $args;
    }

    function sendtohypervm($args,$values) {
        $post = 'login-name='.urlencode($args['server']['variables']['plugin_hypervm_Username']).'&login-password='.urlencode($args['server']['variables']['plugin_hypervm_Password']);
        foreach ($values as $key => $value) {
            $post .= "&$key=".urlencode(str_replace('\'','',$value));
        }
        $url = $args['server']['variables']['plugin_hypervm_HyperVM_API_url'];
        $result = NE_Network::curlRequest($this->settings, $url, $post, false, true);
        return $result;
    }

	function doCreate($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->create($this->buildParams($userPackage));
		return 'Virtual Machine has been created.';
	}

	function doSuspend($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->suspend($this->buildParams($userPackage));
		return 'Virtual Machine has been suspended.';
	}

	function doUnSuspend($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->unsuspend($this->buildParams($userPackage));
		return 'Virtual Machine has been unsuspended.';
	}

	function doDelete($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->delete($this->buildParams($userPackage));
		return 'Virtual Machine has been deleted.';
	}
}
?>
