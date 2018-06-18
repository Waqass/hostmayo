<?php
require_once 'library/CE/XmlFunctions.php';
require_once 'modules/admin/models/ServerPlugin.php';

/**
* @package Plugins
*/
class PluginOnapp extends ServerPlugin
{
    public $features = array(
        'packageName' => false,
        'testConnection' => false,
        'showNameservers' => true
    );

    /*****************************************************************/
    // function getVariables - required function
    /*****************************************************************/

    function getVariables(){
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password,dropdown
              description - description of the variable, displayed in ClientExec
              encryptable - used to indicate the variable's value must be encrypted in the database
        */


        $variables = array (
            lang("Name") => array (
                "type"        => "hidden",
                "description" => "Used By CE to show plugin",
                "value"       => "OnApp"
            ),
            lang("Description") => array (
                "type"        => "hidden",
                "description" => lang("Description viewable by admin in server settings"),
                "value"       => lang("OnApp control panel integration")
            ),
            lang("Server URL") => array (
                "type"        => "text",
                "description" => lang("URL to the OnApp server, ending with / (slash)"),
                "value"       => ""
            ),
            lang("OnApp 2,1 or higher") => array (
                "type"        => "yesno",
                "description" => lang("Tick if the OnApp server is using the OnApp version 2.1 or higher"),
                "value"       => ""
            ),
            lang("Username") => array (
                "type"        => "text",
                "description" => lang("Username used to connect to server"),
                "value"       => ""
            ),
            lang("Password") => array (
                "type"        => "password",
                "description" => lang("Password used to connect to server"),
                "value"       => "",
                "encryptable" => true
            ),

            // PACKAGE CUSTOM FIELDS
            lang("VM Label Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the VM label for OnApp."),
                "value"       => ""
            ),
            lang("VM Hostname Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the VM hostname for OnApp."),
                "value"       => ""
            ),
            lang("VM Password Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the VM initial root password for OnApp."),
                "value"       => ""
            ),

//            Cloud location
//              not an Onapp API field, but in time we will have multiple onapp installs in multiple geographies.
//              Can we allow customers to chose which onapp to deploy to *eg: Cloud 1, Dublin: Cloud 2, London, etc)

            lang('package_vars_values') => array(
                'type'        => 'hidden',
                'description' => lang('VM account parameters'),
                'value'       => array(
                    // VIRTUAL MACHINE PROPERTIES
                    // Template
                    'template' => array(
                        'type'        => 'dropdown',
                        'multiple'    => false,
                        'getValues'   => 'getTemplateValues',
                        'label'       => lang('Template'),
                        'description' => lang('A Template is a pre-configured OS image that you can build a Virtual Machine on.'),
                        'value'       => '',
                    ),

                    // Hypervisor
                    'hypervisor' => array(
                        'type'        => 'dropdown',
                        'multiple'    => false,
                        'getValues'   => 'getHypervisorValues',
                        'label'       => lang('Hypervisor'),
                        'description' => lang('Hypervisors provide CPU, RAM and network resources for your Virtual Machines.'),
                        'value'       => '',
                    ),


                    //RESOURCES
                    //  RAM
                    'ram' => array(
                        'type'        => 'text',
                        'label'       => lang('RAM'),
                        'description' => lang('Amount of RAM assigned in MB.'),
                        'value'       => '',
                    ),

                    //  CPU Cores
                    'cpu_cores' => array(
                        'type'        => 'text',
                        'label'       => lang('CPU Cores'),
                        'description' => lang('Number of CPUs.'),
                        'value'       => '',
                    ),

                    //  CPU Priority
                    'cpu_priority' => array(
                        'type'        => 'text',
                        'label'       => lang('CPU Priority'),
                        'description' => lang('Priority of the CPU in %.'),
                        'value'       => '',
                    ),

                    //  Primary disk size
                    'primary_disk_size' => array(
                        'type'        => 'text',
                        'label'       => lang('Primary disk size'),
                        'description' => lang('Amount of primary disk size in GB.'),
                        'value'       => '',
                    ),

                    //  Swap disk size
                    'swap_disk_size' => array(
                        'type'        => 'text',
                        'label'       => lang('Swap disk size'),
                        'description' => lang('Amount of swap disk size in GB.'),
                        'value'       => '',
                    ),

                    //  Build virtual machine automatically
                    'build_virtual_machine' => array(
                        'type'        => 'check',
                        'label'       => lang('Build virtual machine automatically'),
                        'description' => lang('Tick to build automatically'),
                        'value'       => '1',
                    ),


                    //NETWORK CONFIGURATION
                    //  Primary network
                    'primary_network' => array(
                        'type'        => 'dropdown',
                        'multiple'    => false,
                        'getValues'   => 'getPrimaryNetworkValues',
                        'label'       => lang('Primary network'),
                        'description' => lang('Primary network for the Virtual Machines.'),
                        'value'       => '',
                    ),

                    //  Port Speed
                    'port_speed' => array(
                        'type'        => 'text',
                        'label'       => lang('Port Speed'),
                        'description' => lang('Port speed in Mbps. Unlimited if not set.'),
                        'value'       => '',
                    ),

                    //  Assign an IP automatically
                    'assign_an_ip' => array(
                        'type'        => 'check',
                        'label'       => lang('Assign an IP automatically'),
                        'description' => lang('Tick to assign automatically'),
                        'value'       => '1',
                    ),


                    //USER DETAILS
                    //  Billing Plan or Group
                    'billing_group' => array(
                        'type'        => 'dropdown',
                        'multiple'    => false,
                        'getValues'   => 'getUserGroupValues',
                        'label'       => lang('User Billing Plan or Group'),
                        'description' => lang('Billing Plan or Group to set prices for resources.'),
                        'value'       => '',
                    ),

                    //  User Roles
                    'user_roles' => array(
                        'type'        => 'dropdown',
                        'multiple'    => true,
                        'getValues'   => 'getUserRolesValues',
                        'label'       => lang('User Roles'),
                        'description' => lang('A set of actions that the user will be allowed to perform.'),
                        'value'       => '',
                    ),
                ),
            ),

            lang("Actions") => array (
                "type"        => "hidden",
                "description" => lang("Current actions that are active for this plugin per server"),
                "value"       => "Create,Delete,Startup,Reboot,Stop"
                //"value"       => "Create,Delete,Startup,Reboot,Stop,ShutDown,PowerOff"  //ShutDown & PowerOff do not works very well in OnApp, but Stop do.
            )
        );
        return $variables;
    }

    //plugin function called after account is activated
    function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        return $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE) . ' VM has been created.';
    }

    //plugin function called after new account is activated ( approved )
    function create($args){
        $this->getPluginProperties($args);

        if($args['UserOnAppId'] == ''){
            $this->createUser($args);
        }

        $this->createVM($args);
    }

    function getPluginProperties(&$args){
        $args['VMServerId']        = '';
        $args['UserOnAppId']       = '';
        $args['UserOnAppLogin']    = '';
        $args['UserOnAppPassword'] = '';
        $args['VMOnAppId']         = '';
        $args['VMOnAppIdentifier'] = '';

        $args['OnAppPluginProperties'] = array();

        $query = "SELECT `id` "
            ."FROM `customField` "
            ."WHERE `name` = 'Plugin Properties' ";
        $result = $this->db->query($query);
        list($args['PluginPropertiesCustomFieldId']) = $result->fetch();

        $args['VMServerId'] = $args['server']['id'];

        $query = "SELECT `objectid`, `value` "
            ."FROM `object_customField` "
            ."WHERE `customFieldId` = ? "
            ."AND `objectid` IN (SELECT `id` "
            ."FROM `domains` "
            ."WHERE `CustomerID` = ?) ";
        $result = $this->db->query($query, $args['PluginPropertiesCustomFieldId'], $args['customer']['id']);

        while(list($objectid, $AllPluginProperties) = $result->fetch()){
            $PluginProperties = unserialize($AllPluginProperties);

            if(isset($PluginProperties['server']['type'])
              && $PluginProperties['server']['type'] == 'onapp'){
                $args['UserOnAppLogin']    = $PluginProperties['user']['login'];
                $args['UserOnAppPassword'] = ($PluginProperties['user']['password'] == '')? $PluginProperties['user']['password'] : base64_decode($PluginProperties['user']['password']);

                if(isset($PluginProperties['server']['id'])
                  && $PluginProperties['server']['id'] == $args['VMServerId']){
                    $args['UserOnAppId'] = $PluginProperties['user']['id'];

                    if($objectid == $args['package']['id']){
                        $args['VMOnAppId']         = $PluginProperties['vm']['id'];
                        $args['VMOnAppIdentifier'] = $PluginProperties['vm']['identifier'];

                        $args['OnAppPluginProperties'] = $PluginProperties;
                        break;
                    }
                }
            }
        }
    }

    function setPluginProperties(&$args){
        if(count($args['OnAppPluginProperties']) == 0){
            $query = "INSERT INTO `object_customField` "
                ."SET `value` = ?, "
                ."`objectid` = ?, "
                ."`customFieldId` = ? ";
        }else{
            $query = "UPDATE `object_customField` "
                ."SET `value` = ? "
                ."WHERE `objectid` = ? "
                ."AND `customFieldId` = ? ";
        }

        $args['OnAppPluginProperties']['server']['id']     = $args['VMServerId'];
        $args['OnAppPluginProperties']['server']['type']   = 'onapp';

        $args['OnAppPluginProperties']['user']['id']       = $args['UserOnAppId'];
        $args['OnAppPluginProperties']['user']['login']    = $args['UserOnAppLogin'];
        $args['OnAppPluginProperties']['user']['password'] = ($args['UserOnAppPassword'] == '')? $args['UserOnAppPassword'] : base64_encode($args['UserOnAppPassword']);

        $args['OnAppPluginProperties']['vm']['id']         = $args['VMOnAppId'];
        $args['OnAppPluginProperties']['vm']['identifier'] = $args['VMOnAppIdentifier'];


        $PluginProperties = serialize($args['OnAppPluginProperties']);

        $this->db->query($query, $PluginProperties, $args['package']['id'], $args['PluginPropertiesCustomFieldId']);
    }

    function createUser(&$args){
        if($args['UserOnAppLogin'] == ''){
            $args['UserOnAppLogin'] = $args['customer']['email'];
        }

        if($args['UserOnAppPassword'] == ''){
            $userPackage = new UserPackage($args['package']['id']);

            $args['UserOnAppPassword'] = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        }

        $url = $args['server']['variables']['plugin_onapp_Server_URL']."users.xml";

        $xml =  "<user>\n"
               ."    <login>".$args['UserOnAppLogin']."</login>\n"
               ."    <first_name>".$args['customer']['first_name']."</first_name>\n"
               ."    <last_name>".$args['customer']['last_name']."</last_name>\n"
               ."    <email>".$args['customer']['email']."</email>\n"
               ."    <password>".$args['UserOnAppPassword']."</password>\n"
               ."    <password_confirmation>".$args['UserOnAppPassword']."</password_confirmation>\n"

               // This one is for 2.1
               ."    <billing_plan_id>".$args['package']['variables']['billing_group']."</billing_plan_id>\n"

               // This one is previous to 2.1
               ."    <group_id>".$args['package']['variables']['billing_group']."</group_id>\n";

        if(isset($args['package']['variables']['user_roles']) && $args['package']['variables']['user_roles'] != ''){
            $user_roles = explode(',', $args['package']['variables']['user_roles']);

            $xml .= "    <role_ids type=\"array\">\n";

            foreach($user_roles as $user_role_id){
                $xml .= "        <role_id>".$user_role_id."</role_id>\n";
            }

            $xml .= "    </role_ids>\n";
        }

        $xml .= "</user>\n";

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "POST");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        if(isset($response['errors']['#']['error'])){
            $errors = $response['errors']['#']['error'];

            $errormsg = "There were some errors creating the User for the VM:";

            foreach($errors AS $error){
                $errormsg .= "<br>- ".$error['#'];
            }

            CE_Lib::log(4, "plugin_onapp::createUser::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }elseif(isset($response['user']['#']['id'][0]['#'])){
            $args['UserOnAppId'] = $response['user']['#']['id'][0]['#'];

            $this->setPluginProperties($args);
        }else{
            $errormsg = "There was an error creating the User for the VM:";
            $errormsg .= "<br>- The server didn't return an id for the user";

            CE_Lib::log(4, "plugin_onapp::createUser::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }
    }

    function createVM(&$args){
        $userPackage = new UserPackage($args['package']['id']);

        $Label = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Label_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

        $Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

        $Password = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

        if(!isset($args['package']['variables']['port_speed'])){
            $args['package']['variables']['port_speed'] = '';
        }

        $url = $args['server']['variables']['plugin_onapp_Server_URL']."virtual_machines.xml";

        $xml = "<virtual_machine>\n"
              ."    <cpu_shares>".$args['package']['variables']['cpu_priority']."</cpu_shares>\n"
              ."    <cpus>".$args['package']['variables']['cpu_cores']."</cpus>\n"
              ."    <hostname>".$Hostname."</hostname>\n"
              ."    <hypervisor_id>".$args['package']['variables']['hypervisor']."</hypervisor_id>\n"
              ."    <initial_root_password>".$Password."</initial_root_password>\n"
              ."    <memory>".$args['package']['variables']['ram']."</memory>\n"
              ."    <template_id>".$args['package']['variables']['template']."</template_id>\n"
              ."    <primary_disk_size>".$args['package']['variables']['primary_disk_size']."</primary_disk_size>\n"
              ."    <swap_disk_size>".$args['package']['variables']['swap_disk_size']."</swap_disk_size>\n"

              ."    <label>".$Label."</label>\n"
              ."    <required_virtual_machine_build>".$args['package']['variables']['build_virtual_machine']."</required_virtual_machine_build>\n"
              ."    <primary_network_id>".$args['package']['variables']['primary_network']."</primary_network_id>\n"
              ."    <rate_limit>".$args['package']['variables']['port_speed']."</rate_limit>\n"
              ."    <required_ip_address_assignment>".$args['package']['variables']['assign_an_ip']."</required_ip_address_assignment>\n"

              ."</virtual_machine>\n";

        if($args['server']['variables']['plugin_onapp_OnApp_2,1_or_higher']){
            //ADMIN CREDENTIALS
            $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];
        }else{
            //USER CREDENTIALS
            $credentials = $args['UserOnAppLogin'].":".$args['UserOnAppPassword'];
        }

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "POST");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        if(isset($response['errors']['#']['error'])){
            $errors = $response['errors']['#']['error'];

            $errormsg = "There were some errors creating the VM:";

            foreach($errors AS $error){
                $errormsg .= "<br>- ".$error['#'];
            }

            CE_Lib::log(4, "plugin_onapp::createVM::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }elseif(isset($response['virtual_machine']['#']['id'][0]['#'])
          && isset($response['virtual_machine']['#']['identifier'][0]['#'])){
            $args['VMOnAppId']         = $response['virtual_machine']['#']['id'][0]['#'];
            $args['VMOnAppIdentifier'] = $response['virtual_machine']['#']['identifier'][0]['#'];

            $this->setPluginProperties($args);

            if($args['server']['variables']['plugin_onapp_OnApp_2,1_or_higher']){
                // transfer the VM to the new user
                $this->transferVM($args);
            }
        }else{
            $errormsg = "There was an error creating the VM:";
            $errormsg .= "<br>- The server didn't return an id for the VM";

            CE_Lib::log(4, "plugin_onapp::createVM::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }
    }

    function transferVM(&$args){
        $url = $args['server']['variables']['plugin_onapp_Server_URL']."virtual_machines/".$args['VMOnAppId']."/change_owner.xml";

        $xml = "<user_id>".$args['UserOnAppId']."</user_id>\n";

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "POST");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        if(isset($response['errors']['#']['error'])){
            $errors = $response['errors']['#']['error'];

            $errormsg = "There were some errors creating the VM:";

            foreach($errors AS $error){
                $errormsg .= "<br>- ".$error['#'];
            }

            CE_Lib::log(4, "plugin_onapp::createVM::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }elseif(isset($response['virtual_machine']['#']['id'][0]['#'])
          && isset($response['virtual_machine']['#']['identifier'][0]['#'])
          && isset($response['virtual_machine']['#']['user_id'][0]['#'])){
            if($args['VMOnAppId'] != $response['virtual_machine']['#']['id'][0]['#']
              || $args['VMOnAppIdentifier'] != $response['virtual_machine']['#']['identifier'][0]['#']
              || $args['UserOnAppId'] != $response['virtual_machine']['#']['user_id'][0]['#']){
                $errormsg = "There was an error transfering the VM";
                $errormsg .= "<br>- The server returned a VM id or User id that do not match";

                CE_Lib::log(4, "plugin_onapp::transferVM::error: ".$errormsg);
                throw new CE_Exception($errormsg, 200);
            }
        }else{
            $errormsg = "There was an error transfering the VM";
            $errormsg .= "<br>- The server didn't return the VM id or User id";

            CE_Lib::log(4, "plugin_onapp::transferVM::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }
    }
    function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $this->delete($args);
        return $VM_Hostname . ' VM has been deleted.';
    }

    function delete($args){
        $this->getPluginProperties($args);

        $response = $this->getVMdetails($args);

        $errormsg = '';

        if(isset($response['virtual_machine']['#'])){
            $virtual_machine = $response['virtual_machine']['#'];

            // $virtual_machine['id'][0]['#'] => 761
            // $virtual_machine['booted'][0]['#'] => true
            // $virtual_machine['built'][0]['#'] => true

            if($virtual_machine['id'][0]['#'] != $args['VMOnAppId']){
                $errormsg = "There was an error when trying to delete the VM with ID ".$args['VMOnAppId'].".<br>The VM was not found.";
            }elseif($virtual_machine['locked'][0]['#'] == 'true'){
                $response = $this->unlockVM($args);
                $response = $this->getVMdetails($args);

                // We need to validate if the VM is locked or not.
                if(isset($response['virtual_machine']['#'])){
                    $virtual_machine = $response['virtual_machine']['#'];

                    // $virtual_machine['id'][0]['#'] => 761
                    // $virtual_machine['booted'][0]['#'] => true
                    // $virtual_machine['built'][0]['#'] => true

                    if($virtual_machine['id'][0]['#'] != $args['VMOnAppId']){
                        $errormsg = "There was an error when trying to delete the VM with ID ".$args['VMOnAppId'].".<br>The VM was not found.";
                    }elseif($virtual_machine['locked'][0]['#'] == 'true'){
                        $errormsg = "There was an error when trying to delete the VM with ID ".$args['VMOnAppId'].".<br>The VM is locked.";
                    }
                }else{
                    // An HTTP 404 is returned if the VM's ID is not found.
                    $errormsg = "There was an error when trying to delete the VM with ID ".$args['VMOnAppId'].".<br>Possibly the VM was not found.";
                }
            }
        }else{
            // An HTTP 404 is returned if the VM's ID is not found.
            $errormsg = "There was an error when trying to delete the VM with ID ".$args['VMOnAppId'].".<br>Possibly the VM was not found.";
        }

        if($errormsg != ''){
            CE_Lib::log(4, "plugin_onapp::delete::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }

        $response = $this->destroyVM($args);

        // On successful deletion an HTTP 200 response is returned.
        if(isset($response['HTTP_status_code']['#'][0]) && $response['HTTP_status_code']['#'][0] == 200){

            // See if the user has more VMs. If not, delete it.
            // DELETE is slow (takes variable time), and because of that, we can also get here the VM we are deleting.
            // There are some cases that can makes this fail:
            // - If we just try to use this call, and count how many VMs were returned -1, it could be a little tricky, and if for example the VM is deleted faster that time, then we can get a wrong value and end deleting what we shouldn't.
            // - If we request for the available VMs before trying to delete, could be a little more accurate, but there is always a chance for a customer to be buying a VM in the same time we are deleting the other, so it will cause deleting the user while it was buying the other VM. Small chance, but I think can happen.
            // - If you are deleting more than one VM at a time, a similar issue can happen, since all of them takes some time to be deleted. For example, if a customer has 2 VMs, and you are deleting both, the first one will get there are 2 VMs, and if do the -1 then we think there is another VM so not delete the user this time, then you go and delete the other VM, and it will also get there are 2 VMs, the first one that is currently been deleted, and this one you have started to delete. So once again, if you ignore the current VM (-1), you will keep thinking there is another VM, so you will not delete the user.
            //$response = $this->getUserVMs($args);

            return true;
        }else{
            // An HTTP 404 is returned if the VM's ID is not found.
            $errormsg = "There was an error when trying to delete the VM with ID ".$args['VMOnAppId'].".<br>Possibly the VM was not found.";

            CE_Lib::log(4, "plugin_onapp::delete::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }
    }

    function suspend($args){
    }

    function unsuspend($args){
    }

    function doSuspend($args){
        return $this->doStop($args);
    }

    function doUnSuspend($args){
        return $this->doStartup($args);
    }

    function getTemplateValues($serverid){
        $xml = "";

        require_once 'modules/admin/models/server.php';
        $server = New Server($serverid);
        $pluginVariables = $server->getAllServerPluginVariables($this->user, 'onapp');

        $url = $pluginVariables['plugin_onapp_Server_URL']."templates.xml";
        $credentials = $pluginVariables['plugin_onapp_Username'].":".$pluginVariables['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "GET");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        $values = array();

        $templates = '';
        if(isset($response['image-templates']['#']['image-template'])){
            $templates = $response['image-templates']['#']['image-template'];
        }elseif(isset($response['image_templates']['#']['image_template'])){
            $templates = $response['image_templates']['#']['image_template'];
        }

        if($templates != ''){
            foreach($templates AS $template){
                if($template['#']['state'][0]['#'] == 'active'){
                    $values[] = array($template['#']['id'][0]['#'].'-'.$template['#']['operating_system'][0]['#'], $template['#']['label'][0]['#']);
                }

                //$template['#']['allowed_swap'][0]['#'] // false
                //$template['#']['min_disk_size'][0]['#'] // 10
            }
        }

        return $values;
    }

    function getHypervisorValues($serverid){
        $xml = "";

        require_once 'modules/admin/models/server.php';
        $server = New Server($serverid);
        $pluginVariables = $server->getAllServerPluginVariables($this->user, 'onapp');

        $url = $pluginVariables['plugin_onapp_Server_URL']."settings/hypervisors.xml";
        $credentials = $pluginVariables['plugin_onapp_Username'].":".$pluginVariables['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "GET");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        $values = array();

        if(isset($response['hypervisors']['#']['hypervisor'])){
            $hypervisors = $response['hypervisors']['#']['hypervisor'];

            foreach($hypervisors AS $hypervisor){
                if($hypervisor['#']['online'][0]['#'] == 'true'){
                    $values[] = array($hypervisor['#']['id'][0]['#'], $hypervisor['#']['label'][0]['#']);
                }

                //$hypervisor['#']['locked'][0]['#'] // false
                //$hypervisor['#']['health'][0]['#']['xm_info'][0]['#'] // host                   : DEV1-HV1
                //                                                      // release                : 2.6.18-194.3.1.el5xen
                //                                                      // version                : #1 SMP Thu May 13 13:49:53 EDT 2010
                //                                                      // machine                : x86_64
                //                                                      // nr_cpus                : 4
                //                                                      // nr_nodes               : 1
                //                                                      // cores_per_socket       : 4
                //                                                      // threads_per_core       : 1
                //                                                      // cpu_mhz                : 1995
                //                                                      // hw_caps                : bfebfbff:28100800:00000000:00000340:009ce3bd:00000000:00000001:00000000
                //                                                      // virt_caps              : hvm
                //                                                      // total_memory           : 16374
                //                                                      // free_memory            : 8605
                //                                                      // node_to_cpu            : node0:0-3
                //                                                      // node_to_memory         : node0:8605
                //                                                      // xen_major              : 3
                //                                                      // xen_minor              : 4
                //                                                      // xen_extra              : .2
                //                                                      // xen_caps               : xen-3.0-x86_64 xen-3.0-x86_32p hvm-3.0-x86_32 hvm-3.0-x86_32p hvm-3.0-x86_64
                //                                                      // xen_scheduler          : credit
                //                                                      // xen_pagesize           : 4096
                //                                                      // platform_params        : virt_start=0xffff800000000000
                //                                                      // xen_changeset          : unavailable
                //                                                      // cc_compiler            : gcc version 4.1.2 20080704 (Red Hat 4.1.2-44)
                //                                                      // cc_compile_by          : root
                //                                                      // cc_compile_domain      : gitco.tld
                //                                                      // cc_compile_date        : Wed Nov 11 21:16:28 CET 2009
                //                                                      // xend_config_format     : 4
                //$hypervisor['#']['health'][0]['#']['disk'][0]['#'] // Filesystem            Size  Used Avail Use% Mounted on
                //                                                   // /dev/sda3             129G   36G   87G  30% /
                //                                                   // /dev/sda1              99M   30M   64M  32% /boot
                //                                                   // tmpfs                 150M     0  150M   0% /dev/shm
                //                                                   // none                  150M  616K  150M   1% /var/lib/xenstored
                //$hypervisor['#']['health'][0]['#']['memory'][0]['#'] //              total       used       free     shared    buffers     cached
                //                                                     // Mem:           300        269         30          0          4         46
                //                                                     // -/+ buffers/cache:        217         82
                //                                                     // Swap:         4000        240       3759
                //$hypervisor['#']['memory_overhead'][0]['#'] // 464
                //$hypervisor['#']['ip_address'][0]['#'] // 109.123.91.22
            }
        }

        return $values;
    }

    function getPrimaryNetworkValues($serverid){
        $xml = "";

        require_once 'modules/admin/models/server.php';
        $server = New Server($serverid);
        $pluginVariables = $server->getAllServerPluginVariables($this->user, 'onapp');

        $url = $pluginVariables['plugin_onapp_Server_URL']."settings/networks.xml";
        $credentials = $pluginVariables['plugin_onapp_Username'].":".$pluginVariables['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "GET");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        $values = array();

        if(isset($response['networks']['#']['network'])){
            $networks = $response['networks']['#']['network'];

            foreach($networks AS $network){
                $values[] = array($network['#']['id'][0]['#'], $network['#']['label'][0]['#']);

                //$network['#']['identifier'][0]['#'] // d3ily2rrz33nb9
            }
        }

        return $values;
    }

    function getUserGroupValues($serverid){
        $xml = "";

        require_once 'modules/admin/models/server.php';
        $server = New Server($serverid);
        $pluginVariables = $server->getAllServerPluginVariables($this->user, 'onapp');

        if($pluginVariables['plugin_onapp_OnApp_2,1_or_higher']){
            $url = $pluginVariables['plugin_onapp_Server_URL']."billing_plans.xml";
            $rGroups[] = "billing-plans";
            $rGroups[] = "billing_plans";
            $rGroup[]  = "billing-plan";
            $rGroup[]  = "billing_plan";
        }else{
            $url = $pluginVariables['plugin_onapp_Server_URL']."groups.xml";
            $rGroups[] = "groups";
            $rGroup[]  = "group";
        }

        $credentials = $pluginVariables['plugin_onapp_Username'].":".$pluginVariables['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "GET");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        $values = array();

        $groups = '';
        for($iterator = 0; $iterator < count($rGroups); $iterator++){
            if(isset($response[$rGroups[$iterator]]['#'][$rGroup[$iterator]])){
                $groups = $response[$rGroups[$iterator]]['#'][$rGroup[$iterator]];
                break;
            }
        }

        if($groups != ''){
            foreach($groups AS $group){
                $values[] = array($group['#']['id'][0]['#'], $group['#']['label'][0]['#']);

                // before 2.1
                //$group['#']['identifier'][0]['#'] // d3ily2rrz33nb9
            }
        }

        return $values;
    }

    function getUserRolesValues($serverid){
        $xml = "";

        require_once 'modules/admin/models/server.php';
        $server = New Server($serverid);
        $pluginVariables = $server->getAllServerPluginVariables($this->user, 'onapp');

        $url = $pluginVariables['plugin_onapp_Server_URL']."roles.xml";
        $credentials = $pluginVariables['plugin_onapp_Username'].":".$pluginVariables['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "GET");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        $values = array();

        if(isset($response['roles']['#']['role'])){
            $roles = $response['roles']['#']['role'];

            foreach($roles AS $role){
                $values[] = array($role['#']['id'][0]['#'], $role['#']['label'][0]['#']);

                //$role['#']['identifier'][0]['#'] // d3ily2rrz33nb9
            }
        }

        return $values;
    }

    function getVMdetails($args){
        $url = $args['server']['variables']['plugin_onapp_Server_URL']."virtual_machines/".$args['VMOnAppId'].".xml";
        $xml = "";

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "GET");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        return $response;
    }

    function unlockVM($args){
        $url = $args['server']['variables']['plugin_onapp_Server_URL']."virtual_machines/".$args['VMOnAppId']."/unlock";
        $xml = "";

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "POST");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        return $response;
    }

    function getUserVMs($args){
        $url = $args['server']['variables']['plugin_onapp_Server_URL']."users/".$args['UserOnAppId']."/virtual_machines.xml";
        $xml = "";

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "GET");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        return $response;
    }

    // - Returns HTTP 200 response on successful deletion, or HTTP 404 when a user with the ID specified is not found.
    // - When you delete a user in OnApp, its status becomes DELETED, so a user cannot perform any actions on their VMs, but statistics, backups, and billing details are still available for Administrator.
    // - To erase an already deleted user from the system with all their details, run this function again.
    function deleteUser($args){
        $url = $args['server']['variables']['plugin_onapp_Server_URL']."users/".$args['UserOnAppId'].".xml";
        $xml = "";

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "DELETE");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        return $response;
    }

    function destroyVM($args){
        $url = $args['server']['variables']['plugin_onapp_Server_URL']."virtual_machines/".$args['VMOnAppId'].".xml";
        $xml = "";

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "DELETE");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        return $response;
    }

    function doStartup($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $extraArgs['newAction'] = "startup";
        $this->changeStatus($args, $extraArgs);
        return $VM_Hostname . ' VM has been started up.';
    }

    function doReboot($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $extraArgs['newAction'] = "reboot";
        $this->changeStatus($args, $extraArgs);
        return $VM_Hostname . ' VM has been rebooted.';
    }

    function doStop($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $extraArgs['newAction'] = "stop";
        $this->changeStatus($args, $extraArgs);
        return $VM_Hostname . ' VM has been stopped.';
    }

    //ShutDown & PowerOff do not works very well in OnApp, but Stop do.
    /*
    function doShutDown($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $extraArgs['newAction'] = "shutdown";
        $extraArgs['additionalParams'] = "<hard>0</hard>\n";
        $this->changeStatus($args, $extraArgs);
        return $VM_Hostname . ' VM has been shut down.';
    }

    function doPowerOff($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_onapp_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $extraArgs['newAction'] = "shutdown";
        $extraArgs['additionalParams'] = "<hard>1</hard>\n";
        $this->changeStatus($args, $extraArgs);
        return $VM_Hostname . ' VM has been powered off.';
    }
    */

    function changeStatus($args, $extraArgs){
        $this->getPluginProperties($args);

        $response = $this->getVMdetails($args);

        $errormsg = '';

        if(isset($response['virtual_machine']['#'])){
            $virtual_machine = $response['virtual_machine']['#'];

            // $virtual_machine['id'][0]['#'] => 761
            // $virtual_machine['booted'][0]['#'] => true
            // $virtual_machine['built'][0]['#'] => true

            if($virtual_machine['id'][0]['#'] != $args['VMOnAppId']){
                $errormsg = "There was an error when trying to ".$extraArgs['newAction']." the VM with ID ".$args['VMOnAppId'].".<br>The VM was not found.";
            }elseif($virtual_machine['locked'][0]['#'] == 'true'){
                $errormsg = "There was an error when trying to ".$extraArgs['newAction']." the VM with ID ".$args['VMOnAppId'].".<br>The VM is locked.";
            }elseif($virtual_machine['built'][0]['#'] != 'true'){
                $errormsg = "There was an error when trying to ".$extraArgs['newAction']." the VM with ID ".$args['VMOnAppId'].".<br>The VM is not built.";
            }
        }else{
            // An HTTP 404 is returned if the VM's ID is not found.
            $errormsg = "There was an error when trying to ".$extraArgs['newAction']." the VM with ID ".$args['VMOnAppId'].".<br>Possibly the VM was not found.";
        }

        if($errormsg != ''){
            CE_Lib::log(4, "plugin_onapp::".$extraArgs['newAction']."::error: ".$errormsg);
            throw new CE_Exception($errormsg, 200);
        }

        $response = $this->changeStatusVM($args, $extraArgs);

        return true;
    }

    function changeStatusVM($args, $extraArgs){
        $url = $args['server']['variables']['plugin_onapp_Server_URL']."virtual_machines/".$args['VMOnAppId']."/".$extraArgs['newAction'].".xml";
        $xml = "";

        if(isset($extraArgs['additionalParams'])){
            $xml .= $extraArgs['additionalParams'];
        }

        $credentials = $args['server']['variables']['plugin_onapp_Username'].":".$args['server']['variables']['plugin_onapp_Password'];

        $header  = array(
            "POST ".$url." HTTP/1.1",
            "Content-Length: ".strlen($xml),
            "Content-type: text/xml; charset=UTF8",
            "Connection: close; Keep-Alive",
            "Authorization: Basic " . base64_encode($credentials),
        );

        $response = NE_Network::curlRequest($this->settings, $url, $xml, $header, true, false, "POST");

        if ($response){
            $response = XmlFunctions::xmlize($response);
        }

        return $response;
    }

    function getAvailableActions($userPackage)
    {
        $actions = array();
        $args = $this->buildParams($userPackage);
        $this->getPluginProperties($args);
        $response = $this->getVMdetails($args);

        if(isset($response['virtual_machine']['#'])){
            $virtual_machine = $response['virtual_machine']['#'];

            if($virtual_machine['id'][0]['#'] == $args['VMOnAppId']){
                $actions[] = 'Delete';

                if($virtual_machine['built'][0]['#'] == 'true'){
                    if($virtual_machine['booted'][0]['#'] == 'true'){
                        $actions[] = 'Reboot';
                        $actions[] = 'Stop';

                        //ShutDown & PowerOff do not works very well in OnApp, but Stop do.
                        /*
                        $actions[] = 'ShutDown';
                        $actions[] = 'PowerOff';
                        */
                    }else{
                        $actions[] = 'Startup';
                    }
                }
            }else{
                $actions[] = 'Create';
            }
        }else{
            // An HTTP 404 is returned if the VM's ID is not found.
            $actions[] = 'Create';
        }

        return $actions;
    }
}
?>
