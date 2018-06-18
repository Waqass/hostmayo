<?php

require_once dirname(__FILE__).'/class.pleskserver10.php';
require_once 'modules/admin/models/ServerPlugin.php';
/**
* @package Plugins
*/
class PluginPlesk10 extends ServerPlugin
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
              template    - used to indicate the variable is in the domain template
        */

        $variables = array(
            lang('Name')          => array(
                'type'          => 'hidden',
                'description'   => 'Used By CE to show plugin - must match how you call the action function names',
                'value'         => 'Plesk10',
                               ),
            lang('Description')   => array(
                'type'          => 'hidden',
                'description'   => lang('Description viewable by admin in server settings'),
                'value'         => lang('Plesk10 control panel integration'),
                               ),
            lang('Username')      => array(
                'type'          => 'text',
                'description'   => lang('If you\'ll provide reseller accounts, enter here the Plesk administrator credentials. Otherwise just enter a regular user\'s credentials, but be aware that such user has to have \'Domain creation\', \'Physical hosting management\', \'Hard disk quota assignment\', \'Domain limits adjustment\'  and \'Ability to use remote XML interface\' permissions. This user will also need to have all its personal data configured in Plesk (E-mail, phone, etc), even those fields that are not marked as required.'),
                'value'         => '',
                               ),
            lang('Password')      => array(
                'type'          => 'password',
                'description'   => lang('If you\'ll provide reseller accounts, enter here the Plesk administrator credentials. Otherwise just enter a regular user\'s credentials, but be aware that such user has to have \'Domain creation\', \'Physical hosting management\', \'Hard disk quota assignment\', \'Domain limits adjustment\'  and \'Ability to use remote XML interface\' permissions. This user will also need to have all its personal data configured in Plesk (E-mail, phone, etc), even those fields that are not marked as required.'),
                'value'         => '',
                'encryptable'   => true,
                               ),
            lang('Non-Admin Username') => array(
                'type'          => 'text',
                'description'   => lang('If you\'ll provide both reseller and non-reseller accounts, use the Plesk administrator account to create a user under whom non-reseller accounts (domains) will be created and enter its credentials here. Otherwise leave this field empty. This user will also need to have all its personal data configured in Plesk (E-mail, phone, etc), even those fields that are not marked as required.'),
                'value'         => '',
                               ),
            lang('Non-Admin Password') => array(
                'type'          => 'password',
                'description'   => lang('If you\'ll provide both reseller and non-reseller accounts, use the Plesk administrator account to create a user under whom non-reseller accounts (domains) will be created and enter its credentials here. Otherwise leave this field empty. This user will also need to have all its personal data configured in Plesk (E-mail, phone, etc), even those fields that are not marked as required.'),
                'value'         => '',
                'encryptable'   => true,
                               ),
            lang('Actions')       => array(
                'type'          => 'hidden',
                'description'   => lang('Current actions that are active for this plugin per server'),
                'value'         => 'Create,Delete,Suspend,UnSuspend',
                               ),
            lang('package_vars')  => array(
                'type'          => 'hidden',
                'description'   => lang('Whether package settings are set'),
                'value'         => '0',
                               ),
            lang('package_vars_values') => array(
                'type'          => 'hidden',
                'description'   => lang('Hosting account parameters'),
                'value'         => array(
                                        'reseller_account' => array(
                                                               'type'           => 'check',
                                                               'label'          =>'Reseller Account',
                                                               'description'    => lang('You need Plesk Administrator access.<br>NOTE: The username for non-reseller accounts is the domain name'),
                                                               'value'          => '0',
                                                           ),
                                        'max_dom'       => array(
                                                               'type'           => 'text',
                                                                'label'          =>'Maximum number of domains',
                                                               'description'    => lang('For resellers only, leave empty for unlimited'),
                                                               'value'          => '',
                                                            ),
                                        'www'           => array(
                                                               'type'           => 'check',
                                                               'label'    => lang('Use www prefix'),
                                                               'value'          => '1',
                                                            ),
                                        'max_subdom'    => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of subdomains (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'disk_space'    => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Disk space (in bytes, leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_traffic'   => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum amount of traffic (in bytes/month, leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_wu'        => array(
                                                               'type'           => 'text',
                                                               'description'    => lang('Maximum number of web users (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_db'        => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of databases (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_box'       => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of mailboxes (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'mbox_quota'    => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Mailbox quota (in bytes, leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_redir'     => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of mail redirects (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_mg'        => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of mail groups (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_resp'      => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of mail autoresponders (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_maillists' => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of mail lists (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'max_webapps'   => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('Maximum number of Java applications (leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'ftp_quota'     => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('FTP quota (in bytes, leave empty for unlimited)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'fp'            => array(
                                                               'type'           => 'check',
                                                               'label'    => lang('FrontPage support'),
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'fp_ssl'        => array(
                                                               'type'           => 'check',
                                                               'label'    => lang('FrontPage over SSL support'),
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'fp_auth'       => array(
                                                               'type'           => 'check',
                                                               'label'    => lang('Allow FrontPage Authorization'),
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'ssl'           => array(
                                                               'type'           => 'check',
                                                               'label'    => lang('SSL support'),
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'shell'         => array(
                                                               'type'           => 'text',
                                                               'label'    => lang('System shell (e.g. /bin/bash. Leave empty to disallow)'),
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'php'           => array(
                                                               'type'           => 'check',
                                                               'label'    => lang('PHP support'),
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'ssi'           => array(
                                                               'type'           => 'check',
                                                               'label'          => 'SSI support',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'cgi'           => array(
                                                               'type'           => 'check',
                                                               'label'          => 'CGI support',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'mod_perl'      => array(
                                                               'type'           => 'check',
                                                               'label'          => 'mod_perl support',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'mod_python'    => array(
                                                               'type'           => 'check',
                                                               'label'          => 'mod_python support',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'asp'           => array(
                                                               'type'           => 'check',
                                                               'label'          => 'Apache ASP support',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'asp_dot_net'   => array(
                                                               'label'          => 'ASP.NET support',
                                                               'type'           => 'check',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'max_mssql_db'   => array(
                                                               'type'           => 'text',
                                                               'label'          => 'Maximum number of Microsoft SQL Server databases',
                                                               'value'          => '',
                                                               'template'       => true,
                                                            ),
                                        'coldfusion'    => array(
                                                               'type'           => 'check',
                                                               'label'          => 'Coldfusion support',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'webstat'       => array(
                                                               'type'           => 'text',
                                                               'label'          => 'Webserver statistics support (awstats, webalizer, smarterstats, urchin, none)',
                                                               'value'          => 'none',
                                                               'template'       => true,
                                                            ),
                                        'errdocs'       => array(
                                                               'type'           => 'check',
                                                               'label'          => 'Custom error documents support',
                                                               'value'          => '0',
                                                               'template'       => true,
                                                            ),
                                        'at_domains'    => array(
                                                               'type'           => 'check',
                                                               'label'          => '@-domains support',
                                                               'value'          => '0',
                                                            ),
                                    ),
                ),
            lang('package_addons') => array(
                'type'          => 'hidden',
                'description'   => lang('Supported signup addons variables'),
                'value'         => array(
                    'DISKSPACE', 'BANDWIDTH', 'EMAIL_ACCOUNTS', 'EMAIL_QUOTA', 'SSH_ACCESS', 'SSL'
                ),
            ),
        );

        return $variables;
    }

    function validateCredentials($args)
    {
        $errors = array();
        if (!preg_match('/^[\w.-]+$/', $args['package']['username'])) {
            $errors[] = lang('Domain username can only contain alphanumeric characters, dots, dashes and underscores');
        }

        // remove any character that isn't alpha-numeric from the username
        $args['package']['username'] = preg_replace("/[^a-zA-Z0-9]/", '', $args['package']['username']);

        if (strpos($args['package']['password'], $args['package']['username']) !== false) {
            $errors[] = lang('Domain password can\'t contain domain username');
        }

        if (trim($args['package']['username']) == '') {
            $errors[] = lang('Domain username can\'t be empty');
        }

        if (strlen($args['package']['password']) > 14 && @$args['generateNewUsername']) {
            $args['package']['password'] = mb_substr($args['package']['password'], 0, 14);
        }

        if (strlen($args['package']['password']) < 5 || strlen($args['package']['password']) > 14) {
            $errors[] = lang('Password length must be between 5 and 14 characters');
        }

        // Plesk only allows lower case user names
        $args['package']['username'] = strtolower($args['package']['username']);

        if(isset($args['noError'])) {
            return $args['package']['username'];
        } else  {
            if (count($errors) > 0) {
               throw new CE_Exception($errors[0]);
            }
        }
    }

	function doCreate($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->create($this->buildParams($userPackage));
		return $userPackage->getCustomField("Domain Name") . ' has been created.';
	}

    function create($args)
    {
        // package add-ons handling
        if (isset($args['package']['addons']['DISKSPACE'])) {
            $args['package']['variables']['disk_space'] += ((int)$args['package']['addons']['DISKSPACE']) * 1048576; // 1 Meg in bytes
        }
        if (isset($args['package']['addons']['BANDWIDTH'])) {
            $args['package']['variables']['max_traffic'] += ((int)$args['package']['addons']['BANDWIDTH']) * 1073741824; // 1 Gig in bytes
        }
        if (isset($args['package']['addons']['EMAIL_ACCOUNTS'])) {
            $args['package']['variables']['max_box'] += ((int)$args['package']['addons']['EMAIL_ACCOUNTS']);
        }
        if (isset($args['package']['addons']['EMAIL_QUOTA'])) {
            $args['package']['variables']['mbox_quota'] += ((int)$args['package']['addons']['EMAIL_QUOTA']) * 1048576;
        }
        if (isset($args['package']['addons']['SSH_ACCESS']) && $args['package']['addons']['SSH_ACCESS'] == 1) {
            $args['package']['variables']['shell'] = '/bin/bash';
        }
        if (isset($args['package']['addons']['SSL']) && $args['package']['addons']['SSL'] == 1) {
            $args['package']['variables']['ssl'] = '1';
        }

		$tUser = new User($args['customer']['id']);
		$server = $this->getServer($args);
		$userId = $server->addUser( $args['customer']['first_name'] . ' ' . $args['customer']['last_name'] . ' (' . $args['package']['id'] . ')',
                                        $args['package']['username'],
                                        $args['package']['password'],
                                        $tUser);

		// add domain to user
        if ( $args['package']['name_on_server'] != null && $args['package']['name_on_server'] !=  '' ){
            $args['package']['variables']['PackageNameOnServer'] = $args['package']['name_on_server'];
            $variables = $this->getVariables();
            foreach($variables['package_vars_values']['value'] AS $varName=>$attrs){
                if(isset($attrs['template']) && $attrs['template']){
                    $args['package']['variables']['TemplateAttr'][] = $varName;
                }
            }
        }
		$domainId = $server->addWebSpaceToUser($userId, $args['package']['username'], $args['package']['password'], $args['package']['domain_name'], $args['package']['ip'], @$args['package']['variables'], @$tUser );

        if (isset($args['package']['variables']['reseller_account']) && $args['package']['variables']['reseller_account'] == 1) {
			$server->upgradeUserToReseller($userId);
			$server->addResellerPermissionsAndLimits($userId, @$args['package']['variables']);
            $server->addIpToUser($userId, $args['package']['ip']);
        }
    }

	function doUpdate($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->update($this->buildParams($userPackage, $args));
		return $userPackage->getCustomField("Domain Name") . ' has been updated.';
	}

    // use $server to test with mock object
    function update($args)
    {
		$userName = '';
		$password = '';
		$ip = $args['package']['ip'];
		$package = '';

        foreach ( $args['changes'] as $change => $newValue )
		{
            switch($change)
			{
                case 'username':
                    $userName = $newValue;
                    break;
                case 'password':
                    $password = $newValue;
                    break;
                case 'package':
                    $package = $newValue;
                    break;
                case 'ip':
                    $ip = $newValue;
                    break;
            }
        }

		$server = $this->getServer($args);
		$tUser = new User($args['customer']['id']);

        if (isset($args['package']['variables']['reseller_account']) && $args['package']['variables']['reseller_account'] == 1) {


            // update reseller account if necessary
            if ($userName != $args['package']['username'] || $password != $args['package']['password']) {

                $result = $server->updateAccount($tUser, $args['package']['ServerAcctProperties']['userId'], $userName, $password);
            }
        } else {
			$userID = $this->getUserId($args, $server);
			$server->updateUserAccount($tUser, $userID, $userName, $password);
		}

		 if ( $package != null && $package !=  '' ){
            $args['package']['variables']['PackageNameOnServer'] = $package;
            $variables = $this->getVariables();
            foreach($variables['package_vars_values']['value'] AS $varName=>$attrs){
                if(isset($attrs['template']) && $attrs['template']){
                    $args['package']['variables']['TemplateAttr'][] = $varName;
                }
            }
        }


        $domainId = $this->getDomainId($args, $server);
        $server->updateWebSpace($domainId, $userName, $password, $ip, @$args['package']['variables']);
		return;
    }

	function doDelete($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->delete($this->buildParams($userPackage), $userPackage);
		return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
	}

    function delete($args)
    {
		$server = $this->getServer($args);
        if (isset($args['package']['variables']['reseller_account']) && $args['package']['variables']['reseller_account'] == 1) {
			$userID = $this->getResellerId($args, $server);
            $response = $server->deleteReseller($userID);
        } else {
			$userID = $this->getUserId($args, $server);
			$response = $server->deleteUser($userID);
        }
        return;
    }

	function doSuspend($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->suspend($this->buildParams($userPackage), $userPackage);
		return $userPackage->getCustomField("Domain Name") . ' has been suspended.';
	}

    function suspend($args)
    {
		$server = $this->getServer($args);
		// suspend user, which suspends the domain at the same time.
        if (isset($args['package']['variables']['reseller_account']) && $args['package']['variables']['reseller_account'] == 1) {
			$userID = $this->getResellerId($args, $server);
            $response = $server->setResellerStatus($userID, 16);
        } else {
			$userID = $this->getUserId($args, $server);
			$response = $server->setUserStatus($userID, 16);
        }
    }

	function doUnSuspend($args)
	{
		$userPackage = new UserPackage($args['userPackageId']);
		$this->unsuspend($this->buildParams($userPackage), $userPackage);
		return $userPackage->getCustomField("Domain Name") . ' has been unsuspended.';
	}

    function unsuspend($args)
    {
		$server = $this->getServer($args);
        if (isset($args['package']['variables']['reseller_account']) && $args['package']['variables']['reseller_account'] == 1) {
			$userID = $this->getResellerId($args, $server);
			$response = $server->setResellerStatus($userID, 0);
        }
		else
		{
			$userID = $this->getUserId($args, $server);
			$response = $server->setUserStatus($userID, 0);
		}
    }

    function getDomainId($args, $server)
    {
        return $server->getDomainId($args['package']["domain_name"]);
    }

	function getUserId($args, $server)
    {
        return $server->getUserId($args['package']["username"]);
    }

	function getResellerId($args, $server)
    {
        return $server->getResellerId($args['package']["username"]);
    }

	function getServer($args)
	{
		if ( isset($args['package']['variables']['reseller_account']) && $args['package']['variables']['reseller_account'] == 1 )
		{
			$server = new PleskServer10($this->settings, $args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_plesk10_Username'], $args['server']['variables']['plugin_plesk10_Password']);
		}
		else
		{
			if ($args['server']['variables']['plugin_plesk10_Non-Admin_Username'] != '' && $args['server']['variables']['plugin_plesk10_Non-Admin_Password'] != '') {
				$server = new PleskServer10($this->settings, $args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_plesk10_Non-Admin_Username'], $args['server']['variables']['plugin_plesk10_Non-Admin_Password']);
			} else {
				$server = new PleskServer10($this->settings, $args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_plesk10_Username'], $args['server']['variables']['plugin_plesk10_Password']);
			}
		}
		return $server;
	}

	function getAvailableActions($userPackage)
	{
		$args = $this->buildParams($userPackage);
		$actions = array();
		try {
			$server = $this->getServer($args);
			$domainID = $this->getDomainId($args, $server);
			$response = $server->getDomainStatus($domainID);
			$status = $response['packet']['#']['webspace'][0]['#']['get'][0]['#']['result'][0]['#']['data'][0]['#']['gen_info'][0]['#']['status'][0]['#'];
			$actions[] = 'Delete';
			if ( $status == '0' ) {
				$actions[] = 'Suspend';
			} else {
				$actions[] = 'UnSuspend';
			}
		} catch (Exception $e) {
			$actions[] = 'Create';
		}
		return $actions;
	}

}
?>
