<?php
/*

GameCP ClientExec5.x Module

Updated by William Bowman 2014
Created by Billy & William Bowman 2008


*/

/** TODO
* Allow per-game suspensions(make use of PackageID)
*/

/**  changelog
*
* 3.0:
* Update for CE5 and new gamecpx
*
* 2.5:
* Update for CE4 and new gamecpx features
*
* 2.4:
* Changed sv_location wordage to say what options really are and what to call the field.
*
* 2.3:
* Added some more package variables
*
* 1.5:
*	added more debugging options
*
*/


require_once 'modules/admin/models/ServerPlugin.php';

require_once 'library/CE/NE_MailGateway.php';
require_once 'library/CE/NE_Network.php';

// set to something if youre having issues
define('DEBUGGING','0');
error_reporting(0);

/**
* @package Plugins
*/
class PluginGamecp extends ServerPlugin
{
    public $features = array(
        'packageName' => false,
        'testConnection' => false,
        'showNameservers' => false
    );

    var $pluginVersion = '2.6';
    function getVariables(){
        $variables = array (
                   lang("Name") => array (
                                        "type"=>"hidden",
                                        "description"=>"Used By CE to show plugin",
                                        "value"=>"GameCP"
                                       ),
                   lang("Description") => array (
                                        "type"=>"hidden",
                                        "description"=>lang("Description viewable by admin in server settings"),
                                        "value"=>lang("This is the GameCP Control Module " . $this->pluginVersion . " for ClientExec")
                                       ),
			        lang("GameCP URL") => array (
                                    "type"            =>    "text",
                                    "description"     =>    lang("This is the url to your GameCP install. Eg: http://www.gamecp.com/gcp"),
                                    "value"           =>    ""
                                       ),
			        lang("Contact E-mail") => array (
                                    "type"            =>    "text",
                                    "description"     =>    lang("The e-mail to send notifications to"),
                                    "value"           =>    ""
                                       ),

				   lang("Connector Passphrase") => array (
                                        "type"=>"text",
                                        "description"=>lang("The API Connector Password in Settings > Billing"),
                                        "value"=>""
                                       ),


					lang('package_vars')  => array(
										'type'            => 'hidden',
										'description'     => lang('Whether package settings are set'),
										'value'           => '0',
									),
					lang('package_vars_values') => array(
										'type'            => 'hidden',
										'description'     => lang('GameCP Settings'),
										'value'           => array(

										'gcp_ipAllocation' => array(
											'type'            => 'text',
											'label'            => 'IP Allocation',
											'description'     => lang('1=Auto, 2=Location Addon (package addon called sv_location)'),
											'value'           => '1',
										),
										'gcp_gameId' => array(
											'type'            => 'text',
											'label'            => 'Game ID',
											'description'     => lang('Found in Manage Games'),
											'value'           => '',
										),
										'gcp_pubpriv' => array(
											'type'            => 'yesno',
											'label'            => 'Private Server',
											'description'     => lang('Server will require a password to connect'),
											'value'           => '0',
										),
										'login_path' => array(
											'type'            => 'yesno',
											'description'     => lang('Enable shell access on Linux/FreeBSD'),
											'label'            => 'Allow SSH',
											'value'           => '0',
										),
										'srv_affinty' => array(
											'type'            => 'text',
											'label'            => 'Affinity',
											'description'     => lang('The CPU(s) this will go on, blank, 0, 1, 2, 3'),
											'value'           => '',
										),
										'addon_tickrate' => array(
											'type'            => 'text',
											'label'            => 'Tickrate',
											'description'     => lang('This is used for srcds and hlds based games.'),
											'value'           => '',
										),
										'addon_fps' => array(
											'type'            => 'text',
											'label'            => 'FPS',
											'description'     => lang('This is used for srcds and hlds based games.'),
											'value'           => '',
										)
										),
									),

					lang("Actions") => array (
										"type"=>"hidden",
										"description"=>lang("Current actions that are active for this plugin per server"),
										"value"=>"Create,Delete,Suspend,UnSuspend"
									   )
        );
        return $variables;
   }

   function update($args, $userPackage = null) {

		if($args['package']['variables']['gcp_gameId'] == "1000" || $args['package']['variables']['gcp_gameId'] == "1001" || $args['package']['variables']['gcp_gameId'] == "1002") return false;

		$urlvars = array(
			'action' => 'changeplayers',
			"packageid"  => $args['package']['id'],
			"max_players" => $args['package']['addons']['sv_slots']
		);



		$r_result=$this->curl2gcp($args, $urlvars);
		if ($this->checkStatus($r_result) == true)
		{
			return true;
		} else return false;
   }

   function create($args) {

		$args = $this->set_all_args($args);


		$packagevars = @$args['package']['variables'];

		if (isset($packagevars) && is_array($packagevars)) {
			foreach ($packagevars as $key => $result) {
            	if ($key == 'addon_tickrate' || $key == 'addon_fps' || $key == 'sv_slots'){
					if(@$args['package']['addons'][$key] != ""){
						$args['package']['addons'][$key] = $args['package']['addons'][$key];
					} else {
						if($result != "") $args['package']['addons'][$key] = $result;
					}
				}
            }
        }

		$urlvars = array(
			"action" => "create",
			"function"   => "createacct",
			"username"   => $args['package']['username'],
			"password"   => $args['package']['password'],
			"customerid"  => $args['customer']['id'],
			"packageid"  => $args['package']['id'],
			// profile information
			"emailaddr"  => $args['customer']['email'],
			"firstname"  => $args['customer']['first_name'],
			"lastname"   => $args['customer']['last_name'],
			"address"    => $args['user_information']['address'],
			"city"       => $args['user_information']['city'],
			"state"      => $args['user_information']['state'],
			"country"    => $args['user_information']['country'],
			"zipcode"    => $args['user_information']['zip'],
			"phonenum"   => $args['user_information']['phone'],
			// Game server information
			"game_id"     => $args['package']['variables']['gcp_gameId'],
			"max_players" => $args['package']['addons']['sv_slots'],
			"pub_priv"    => $args['package']['variables']['gcp_pubpriv'],
			"website"     => $args['gamecp_info']['website'],
			"hostname"    => $args['gamecp_info']['hostname'],
			"motd"   	  => $args['gamecp_info']['motd'],
			"rcon_password"    => $args['gamecp_info']['rcon'],
			"priv_password"    => $args['gamecp_info']['serverpass'],
			"sv_location" => $args['gamecp_info']['location'],
			"affinty"    => $args['package']['variables']['srv_affinty'],
			"login_path" => $args['package']['variables']['login_path'],
			"addons" => serialize($args['package']['addons']));

		$r_result=$this->curl2gcp($args, $urlvars);
		if ($this->checkStatus($r_result) == true)
		{


				// update the username as it may have changed
				preg_match_all('/USER: (?P<name>\w+) ::/', $r_result, $matches);
				if(@$matches['name'][0] && ($matches['name'][0] != $args['package']['username'])){
					//mysql_query("UPDATE object_customfield SET value='".$matches['name'][0]."' WHERE objectid='".$args['package']['id']."' AND value='".$args['package']['username']."'");
				}
				preg_match_all('/PASS: (?P<pass>\w+) ::/', $r_result, $pwmatch);
				if(@$pwmatch['pass'][0] && $pwmatch['pass'][0] != $args['package']['password']){
					//mysql_query("UPDATE object_customfield SET value='".$pwmatch['pass'][0]."' WHERE objectid='".$args['package']['id']."' AND value='".$args['package']['password']."'");
				}


			return true;
		}
		else
		{   // there was an error
			$message = "GameCP was unable to complete setup on the following server:
				Error From: ".$args['customer']['first_name']." ".$args['customer']['last_name']." @ " . $args['customer']['email'] ."\n
				Package Name: ".$args['package']['name']." (". $args['package']['id'].")\n\n
				Result: ". $r_result ."";

			$mailGateway = new NE_MailGateway();
			$mailGateway->mailMessageEmail($message,$this->settings->get('Support E-mail'),'GameCP Plugin',$args['server']['variables']['plugin_gamecp_Contact_E-mail'],'','GameCP server creation failed.','','');
			return new CE_Error('An error has occured, a detailed explantion has been emailed to '.$args['server']['variables']['plugin_gamecp_Contact_E-mail'].'',300);
		}
   }

    function delete($args){
		$urlvars = array(
        	'action' => 'delete',
            'customerid' => $args['customer']['id'],
			'packageid' => $args['package']['id']
        );
		$r_result=$this->curl2gcp($args, $urlvars);

		if ($this->checkStatus($r_result) == true){
			return true;
		} else {
			return new CE_Error('An error has occured, unable to delete server.','',300);
		}
	}

    function suspend($args){
		if($args['package']['variables']['gcp_gameId'] == "1000" || $args['package']['variables']['gcp_gameId'] == "1001" || $args['package']['variables']['gcp_gameId'] == "1002"){
			$action="suspendvoice";
		} else $action="suspendgame";

		$urlvars = array(
        	'action' => $action,
            'customerid' => $args['customer']['id'],
            'packageid' => $args['package']['id']
        );
		$r_result=$this->curl2gcp($args, $urlvars);

		if ($this->checkStatus($r_result) == true){
			return true;
		} else {
			return new CE_Error('An error has occured, unable to suspend server.','',300);
		}
    }

	function unsuspend($args){
		if($args['package']['variables']['gcp_gameId'] == "1000" || $args['package']['variables']['gcp_gameId'] == "1001" || $args['package']['variables']['gcp_gameId'] == "1002"){
			$action="unsuspendvoice";
		} else $action="unsuspendgame";

		$urlvars = array(
        	'action' => $action,
            'customerid' => $args['customer']['id'],
            'packageid' => $args['package']['id']
        );
		$r_result=$this->curl2gcp($args, $urlvars);

		if ($this->checkStatus($r_result) == true){
			return true;
		} else {
			return new CE_Error('An error has occured, unable to unsuspend server.','',300);
		}
    }

    function curl2gcp($args, $values) {
		if(DEBUGGING) {
        	$post = 'passphrase='.urlencode($args['server']['variables']['plugin_gamecp_Connector_Passphrase']).'&debugging=true&connector=ce';
		}
		else
		{
			$post = 'passphrase='.urlencode($args['server']['variables']['plugin_gamecp_Connector_Passphrase']).'&connector=ce';
		}
		if(is_array($values)){
			foreach ($values as $key => $value) {
				$post .= "&$key=".urlencode(str_replace('\'','',$value));
			}
		}
        $url = $args['server']['variables']['plugin_gamecp_GameCP_URL']."/billing/mb/index.php";
        $result = NE_Network::curlRequest($this->settings, $url, $post, false, true);
		if(DEBUGGING) {
			print($post);
			print_r($values);
			print('<br /><br /><br />');
			print_r($result);
			//exit;
		}
        return $result;
    }

	function checkStatus($r_result){
		$haystack = "/Command Execution Result: Ok/i";
		if (preg_match($haystack, $r_result)){
			return true;
		} else {
			return false;
		}
	}

	function set_all_args($args) {
        $package = new UserPackage($args['package']['id']);

		$clientUser = new User($args['customer']['id']);

		// Determine sv_location
		$iptype = $args['package']['variables']['gcp_ipAllocation'];
		if ($iptype == "1"){
			$sv_location = "";
		} else $sv_location = $args['package']['addons']['sv_location'];

		// load gameserver values
		$srv_hostname = $package->getCustomField('Server Hostname');
		$srv_motd = $package->getCustomField('Server MOTD');
		$srv_www = $package->getCustomField('Server Website');
		$srv_rcon = $package->getCustomField('Rcon Password');
		$srv_privpass = $package->getCustomField('Server Password');

        $address = $clientUser->getAddress();
        $phone = $clientUser->getPhone();
        $zip = $clientUser->getZipCode();
        $city = $clientUser->getCity();
        $state = $clientUser->getState();
        $country = $clientUser->getCountry();
		// Client information
        $args['user_information'] = array(
                'address' => substr($address,0,128),
                'phone' => substr($phone,0,20),
                'zip' => substr($zip,0,10),
                'city' => substr($city,0,50),
                'state' => substr($state,0,50),
                'country' => substr($country,0,2)
        );
		// Game information
		$args['gamecp_info'] = array(
                'hostname' => $srv_hostname,
                'motd' => $srv_motd,
				'website' => $srv_www,
                'rcon' => $srv_rcon,
                'serverpass' => $srv_privpass,
				'location' => $sv_location
        );
        return $args;
    }

    function getAvailableActions($userPackage)
    {
		if($this->ServiceExists($userPackage)){
			$actions[] = 'Delete';
			$actions[] = 'UnSuspend';
			$actions[] = 'Suspend';
		} else $actions[] = 'Create';

        return $actions;
    }


	function doUpdate($args)
	{
        $userPackage = new UserPackage($args['userPackageId']);
        return $this->update($this->buildParams($userPackage));
	}

    function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->create($this->buildParams($userPackage));
        return  'Package has been created.';
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

	function doCheckUserName($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$actions = $this->getAvailableActions($userPackage);
		if(is_array($actions)){
			return !in_array('Create', $actions);
		}else{
			return false;
		}
	}


	function ServiceExists($userPackageId){

		$args = $this->buildParams($userPackageId);

		$res= false;
		$res2= false;
		if(!isset($args['customer']['id']) || !isset($args['package']['id'] ) ) return false;

		$urlvars = array(
                    'action' => 'ceuservalidation',
                    'packageid' => $args['package']['id'],
                    'customerid' => $args['customer']['id']
                );
		$r_result=$this->curl2gcp($args, $urlvars);

		// update the username as it may have changed
		preg_match_all('/USER: (?P<name>\w+) ::/', $r_result, $matches);

		if(@$matches['name'][0] && (@$matches['name'][0] != $args['package']['username'])){
			$this->db->query("UPDATE object_customfield SET value='".$matches['name'][0]."' WHERE objectid='".$args['package']['id']."' AND value='".$args['package']['username']."'");
			$res= true;
		}
		preg_match_all('/PASS: (?P<pass>\w+) ::/', $r_result, $pwmatch);
		if(@$pwmatch['pass'][0] && @$pwmatch['pass'][0] != $args['package']['password']){
			$this->db->query("UPDATE object_customfield SET value='".$pwmatch['pass'][0]."' WHERE objectid='".$args['package']['id']."' AND value='".$args['package']['password']."'");
		}

		if(@$matches['name'][0] == $args['package']['username']) $res= true;

		$haystack = "/Command Execution Result: Ok/i";
		if (preg_match($haystack, $r_result)) $res2=true;


		if($res == true && $res2 == true){
			return true;
		} else return false;


	}
}
?>
