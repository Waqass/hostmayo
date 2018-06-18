<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServerPlugin.php';
require_once dirname(__FILE__).'/VirtualminApi.php';

/**
 * Virtualmin Plugin for ClientExec
 * @package Plugins
 * @version July.23.2011
 * @lastAuthor Steven King
 * @email kingrst@gmail.com
 */

Class PluginVirtualmin extends ServerPlugin {

	public $features = array(
        'packageName' => true,
        'testConnection' => false,
        'showNameservers' => true
    );
	public $api;

	public function setup ( $args ) {
		if ( isset($args['server']['variables']['ServerHostName']) &&
			isset($args['server']['variables']['plugin_virtualmin_Username']) &&
			isset($args['server']['variables']['plugin_virtualmin_Password']) &&
			isset($args['server']['variables']['plugin_virtualmin_Use_SSL']) ) {
				$this->api = new VirtualminApi ($args['server']['variables']['ServerHostName'],
					$args['server']['variables']['plugin_virtualmin_Username'],
					$args['server']['variables']['plugin_virtualmin_Password'],
					$args['server']['variables']['plugin_virtualmin_Use_SSL']);
		} else {
			throw new CE_Exception("Missing Server Credentials: please fill out all information when editing the server.");
		}
	}

	/**
	 * Check if a plan exists.
	 * @param String $plan to check against.
	 * @param <type> $args
	 * @return boolean
	 */
	function CheckVirtualminPlan($plan) {
		$packages = $this->api->packages();
		foreach ( $packages as $p ) {
			if ( $p == $plan ) {
				return true;
			}
		}

		return false;
	}

	/**
	* Emails Virtualmin server errors.
	* @param String $name
	* @param String $message
	* @param Array $args
	* @return string
	*/
	function email_error ( $name, $message, $args ) {
		$error = "Virtualmin Account " .$name." Failed. ";
		$error .= "An email with the Details was sent to ". $args['server']['variables']['plugin_virtualmin_Failure_E-mail'].'<br /><br />';

		if ( is_array($message) ) {
			$message = implode ( "\n", trim($message) );
		}

		CE_Lib::log(1, 'Virtualmin Error: '.print_r(array('type' => $name, 'error' => $error, 'message' => $message, 'params' => $args), true));

		if ( !empty($args['server']['variables']['plugin_virtualmin_Failure_E-mail']) ) {
			$mailGateway = new NE_MailGateway();
			$mailGateway->mailMessageEmail( $message,
				$args['server']['variables']['plugin_virtualmin_Failure_E-mail'],
				"Virtualmin Plugin",
				$args['server']['variables']['plugin_virtualmin_Failure_E-mail'],
				"",
				"Virtualmin Account ".$name." Failure");
		}

		return $error.nl2br($message);
	}

	function getVariables() {
		/* Specification
			itemkey		- used to identify variable in your own functions
			type		- text,textarea,yesno,password,hidden ( type hidden are variables used by CE and are required )
			description - description of the variable, displayed in ClientExec
			encryptable - used to indicate the variable's value must be encrypted in the database
		*/

		$variables = array (
			lang("Name") => array (
				"type"=>"hidden",
				"description"=>"Used by CE to show plugin - must match how you call the action function names",
				"value"=>"Virtualmin"
				),
			lang("Description") => array (
				"type"=>"hidden",
				"description"=>lang("Description viewable by admin in server settings"),
				"value"=>lang("Virtualmin control panel integration")
				),
			lang("Username") => array (
				"type"=>"text",
				"description"=>lang("Username used to connect to server"),
				"value"=>"",
				"encryptable"=>true
				),
			lang("Password") => array (
				"type"=>"text",
				"description"=>lang("Password used to connect to server"),
				"value"=>"",
				"encryptable"=>true
				),
			lang("Use SSL") => array (
				"type"=>"yesno",
				"description"=>lang("Set NO if you do not have SSL Support"),
				"value"=>"1"
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
				"value"=>"0",
				),
			lang("package_addons") => array (
				"type"=>"hidden",
				"description"=>lang("Supported signup addons variables"),
				"value"=>"",
				),
		);

		return $variables;
	}

	function validateCredentials($args) {
		$errors = array();

		// Ensure that the username is not test and doesn't contain test
		if (strpos($args['package']['username'], 'test') !== false) {
			$errors[] = "Domain username can't contain 'test'.";
		}

		// Username cannot start with a number
		if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1))) {
			if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1)) || strlen(trim($args['package']['username'])) == 0) {
				$errors[] = "Domain username can't start with a number.";
			}
		}

		// Username cannot contain a dash '-'
		if (strpos($args['package']['username'], "-") !== false) {
			$errors[] = "Domain username can't contain dashes";
		}

		// Username cannot contain an underscore "_"
		if (strpos($args['package']['username'], "_") !== false) {
			$errors[] = "Domain username can't contain underscores.";
		}

		// Username cannot be greater than 8 characters
		if (strlen($args['package']['username']) > 8) {
			$args['package']['username'] = mb_substr($args['package']['username'], 0, 8);
		}
		else if (strlen(trim($args['package']['username'])) <= 0) {
			$errors[] = "The Virtualmin username is blank.";
		}
		else if (strlen(trim($args['package']['password'])) <= 0) {
			$errors[] = "The Virtualmin password is blank.";
		}

		// Don't process errors for now
		if (isset($args['noError'])) {
			return $args['package']['username'];
		}

		// Only process requests if no errors occurred
		if (count($errors) > 0) {
			CE_Lib::log(4, "plugin_virtualmin::validate::error: ".print_r($errors,true));
			throw new CE_Exception($errors[0]);
		} else {
			// If username and password is valid, username
			return $args['package']['username'];
		}
	}

	function doDelete($args) {
		$userPackage = new UserPackage($args['userPackageId']);
		$this->delete($this->buildParams($userPackage));
		return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
	}

	function doCreate($args) {
		$userPackage = new UserPackage($args['userPackageId']);
    	$args = $this->create($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name"). ' has been created.';
	}

	function doSuspend($args) {
		$userPackage = new UserPackage($args['userPackageId']);
		$this->suspend($this->buildParams($userPackage));
		return $userPackage->getCustomField("Domain Name") . ' has been suspended.';
	}

	function doUnSuspend($args) {
		$userPackage = new UserPackage($args['userPackageId']);
		$this->unsuspend($this->buildParams($userPackage));
		return $userPackage->getCustomField("Domain Name") . ' has been unsuspended.';
	}

	function doUpdate($args) {
		$userPackage = new UserPackage($args['userPackageId']);
		$this->update($this->buildParams($userPackage, $args));
		return $userPackage->getCustomField("Domain Name") . ' has been updated.';
	}

	function update($args) {
		$this->setup($args);
		$userPackage = new UserPackage($args['package']['id']);
		$args = $this->updateArgs($args);
		$errors = array();

		// Loop over changes array
		foreach ($args['changes'] as $key => $value) {
			switch($key) {
				/**
				 * Change Username
				 */
				case 'username':
					$params = array();
					$params['domain'] = $args['package']['domain_name'];
					$params['user'] = $value;

					$result = $this->api->call('modify-domain', $params);

					if ($result->status != "success") {
						$errors[] = $this->email_error("Username Change", $result->full_error, $args);
					}

					// Internal fix, in case we are also changing the domain name.
					$args['package']['username'] = $value;
					break;

				/**
				 * Change password
				 */
				case 'password':
					$params = array();
					$params['domain'] = $args['package']['domain_name'];
					$params['pass'] = urlencode($value);

					$result = $this->api->call('modify-domain', $params);

					if ($result->status != "success") {
						$errors[] = $this->email_error("Password Change", $result->full_error, $args);
					}
					break;

				/**
				 * Change IP Address
				 */
				case 'ip':
					$params = array();
					$params['domain'] = $args['package']['domain_name'];

					if ($userPackage->getCustomField("Shared") == '1') {
						$params['default-ip'] = "";
					} else {
						$params['ip'] = $value;
					}

					$result = $this->api->call('modify-domain', $params);

					if ($result->status != "success") {
						$errors[] = $this->email_error("IP Change", $result->full_error, $args);
					}
					break;

				/**
				 * Change Domain Name
				 */
				case 'domain':
					$params = array();
					$params['domain'] = $args['package']['domain_name'];
					$params['newdomain'] = $value;

					$result = $this->api->call('modify-domain', $params);

					if ($result->status != "success") {
						$errors[] = $this->email_error("Domain Change", $result->full_error, $args);
					}

					// Ensure data consistency
					$args['package']['domain_name'] = $value;
					break;

					/**
					 * Change Package
					 */
					case 'package':
						$params = array();
						$params['domain'] = $args['package']['domain_name'];
						$params['apply-plan'] = urlencode($args['package']['name_on_server']);

						$result = $this->api->call('modify-domain', $params);

						if ($result->status != "success") {
							$errors[] = $this->email_error("Plan Change", $result->full_error, $args);
						}
					break;
			}
		}

		if (count($errors) > 0) {
			CE_Lib::log(4, "plugin_virtualmin::update::error: ".print_r($errors,true));
			throw new CE_Exception($errors[0]);
		} else {
			return;
		}
	}

	function unsuspend($args) {
		$this->setup($args);
		$args = $this->updateArgs($args);
		$params = array();

		$params['domain'] = $args['package']['domain_name'];

		$request = $this->api->call("enable-domain", $params);

		if ($request->status != "success") {
			$errors[] = $this->email_error("Unsuspension", $requset->full_error, $args);
		}
		else if ($request->status == "success") {
			return;
		}

		if (count($errors) > 0) {
			CE_Lib::log(4, "plugin_virtualmin::unsuspend::error: ".print_r($errors,true));
			throw new CE_Exception ($errors[0]);
		}
	}

	function suspend($args) {
		$this->setup($args);
		$args = $this->updateArgs($args);
		$params = array();

		$params['domain'] = $args['package']['domain_name'];

		$request = $this->api->call("disable-domain", $params);

		if ($request->status != "success") {
			$errors[] = $this->email_error("Suspension", $request->full_error, $args);
		}
		else if ($request->status == "success") {
			return;
		}

		if (count($errors) > 0) {
			CE_Lib::log(4, "plugin_virtualmin::suspend::error: ".print_r($errors,true));
			throw new CE_Exception ($errors[0]);
		}
	}

	function delete($args) {
		$this->setup($args);
		$args = $this->updateArgs($args);
		$params = array();

		$params['domain'] = $args['package']['domain_name'];

		$request = $this->api->call('delete-domain', $params);

		if ($request->status != "success") {
			$errors[] = $this->email_error("Deletion", $request->full_error, $args);
		}
		else if ($request->status == "success") {
			return;
		}

		if (count($errors) > 0) {
			CE_Lib::log(4, "plugin_virtualmin::delete::error: ".print_r($errors,true));
			throw new CE_Exception ($errors[0]);
		}
	}

	private function updateArgs($args) {
		$args['package']['username'] = trim(strtolower($args['package']['username']));
		$args['package']['domain_name'] = trim(strtolower($args['package']['domain_name']));

		if (isset($args['changes']['username'])) {
			$args['changes']['username'] = trim(strtolower($args['changes']['username']));
		}

		return $args;
	}

	function getAvailableActions($userPackage) {
		$args = $this->buildParams($userPackage);
		$args = $this->updateArgs($args);
		$this->setup($args);
		$actions = array();
		$params = array();

		$params['domain'] = $args['package']['domain_name'];

		$request = $this->api->call("list-domains", $params);

		if ($request->status != "success") {
			$actions[] = "Create";
		}
		else if (isset($request->data[0]->values->disabled[0])) {
			$actions[] = "UnSuspend";
			$actions[] = "Delete";
		} else {
			$actions[] = "Suspend";
			$actions[] = "Delete";
		}

		return $actions;
	}

	function create($args) {

		$this->setup($args);
		$userPackage = new UserPackage($args['package']['id']);

		$args = $this->updateArgs($args);
		$errors = array();


		// Check if plan exists.
		if (!$this->CheckVirtualminPlan($args['package']['name_on_server'])) {
			$error = "The package '{$args['package']['name_on_server']}' was not found on the server.";
			$errors[] = $this->email_error('Creation', $error, $args);
		}

		if ($args['package']['username'] == '') {
			$error = 'No username was provided';
			$errors[] = $this->email_error('Creation', $error, $args);
		}

		if ($args['package']['password'] == '') {
			$error = 'No password was provided';
			$errors[] = $this->email_error('Creation', $error, $args);
		}

		if ($args['package']['domain_name'] == '') {
			$error = 'No domain name was provided';
			$errors[] = $this->email_error('Creation', $error, $args);
		}

		if (count($errors) > 0) {
			CE_Lib::log(4, "plugin_virtualmin::create::error: ".print_r($errors,true));
			throw new CE_Exception ( $errors[0] );
		}

		// Params array we pass to Virtualmin
		$params = array();

		$params['user'] = $args['package']['username'];
		$params['pass'] = $args['package']['password'];
		$params['domain'] = $args['package']['domain_name'];
		$params['plan'] = urlencode($args['package']['name_on_server']);
		$params['email'] = $args['customer']['email'];

		if ($userPackage->getCustomField("Shared") == '0') {
			$params['ip'] = $args['package']['ip'];
		}

		$params['features-from-plan'] = "";
		$params['limits-from-plan'] = "";

		$request = $this->api->call('create-domain', $params);

		if ($request->status != "success") {
			$errors[] = $this->email_error('Creation', $request->full_error, $args);
		}
		else if ($request->status == "success") {
			return;
		}

		if (count($errors) > 0) {
			CE_Lib::log(4, "plugin_virtualmin::create::error: ".print_r($errors,true));
			throw new CE_Exception ( $errors[0] );
		}
	}
 }
