<?php
require_once 'modules/admin/models/ServerPlugin.php';
require_once 'plugins/server/interworx/InterworxApi.php';

/**
 * Interworx Plugin
 *
 * @author João Cagnoni <joao@clientexec.com>
 *
 * @package Plugins
 *
 * @todo Update method
 * @todo Resellers supporting
 */
class PluginInterworx extends ServerPlugin
{
    public $features = array(
        'packageName' => true,
        'testConnection' => false,
        'showNameservers' => true
    );

    /**
     * Process vars that is used on some method.
     * This method exists only to prevent duplicated code.
     *
     * @param array $args Arguments
     *
     * @return array
     */
    protected function _setup ($args)
    {
        if ($args instanceof UserPackage) {
            $userPackage = $args;
        } else {
            $userPackage = new UserPackage($args['userPackageId']);
        }

        $params = $this->buildParams($userPackage);
        $serverHostname = $params['server']['variables']['ServerHostName'];
        $serverKey = $params['server']['variables']['plugin_interworx_Access_Key'];
        $api = new InterworxApi($serverHostname, $serverKey);
        $domainName = $userPackage->getCustomField('Domain Name');
        $data = array($userPackage, $params, $serverHostname, $serverKey, $api, $domainName);

        return $data;
    }

    /**
     * Create a new account
     *
     * @param array $args Array of available arguments/variables
     *
     * @return string
     */
    public function doCreate ($args)
    {
        list($userPackage, $params, $serverHostname, $serverKey, $api, $domainName) = $this->_setup($args);
        $data = array(
            'domainname' => $params['package']['domain_name'],
            'ipaddress' => $params['package']['ip'],
            'database_server' => 'localhost',
            'billing_day' => date('j'),
            'uniqname' => (strlen($params['package']['username']) > 8) ? substr($params['package']['username'], 0, 8) : $params['package']['username'],
            'nickname' => "{$params['customer']['first_name']} {$params['customer']['last_name']}",
            'email' => $params['customer']['email'],
            'password' => $params['package']['password'],
            'confirm_password' => $params['package']['password'],
            'language' => 'en-us',
            'theme' => 'interworx',
            'menu_style' => 'small',
            'packagetemplate' => $params['package']['name_on_server']
        );
        $api->addSiteworxAccount($data);
        return "{$domainName} has been created.";
    }

    /**
     * Delete an account
     *
     * @param array $args Array of available arguments/variables
     *
     * @return string
     */
    public function doDelete ($args)
    {
        list($userPackage, $params, $serverHostname, $serverKey, $api, $domainName) = $this->_setup($args);
        $api->deleteSiteworxAccount($params['package']['domain_name']);
        return "{$domainName} has been deleted.";
    }

    /**
     * Suspend an account
     *
     * @param array $args Array of available arguments/variables
     *
     * @return string
     */
    public function doSuspend ($args)
    {
        list($userPackage, $params, $serverHostname, $serverKey, $api, $domainName) = $this->_setup($args);
        $api->suspendSiteworxAccount($params['package']['domain_name']);
        return "{$domainName} has been suspended.";
    }

    /**
     * Update an account
     *
     * @param array $args Array of available arguments/variables
     *
     * @return string
     */
    public function doUpdate ($args)
    {
        list($userPackage, $params, $serverHostname, $serverKey, $api, $domainName) = $this->_setup($args);
        $data = array(
            'domainname' => $params['package']['domain_name'],
            'ipaddress' => $params['package']['ip'],
            'uniqname' => (strlen($params['package']['username']) > 8) ? substr($params['package']['username'], 0, 8) : $params['package']['username'],
            'password' => $params['package']['password'],
            'confirm_password' => $params['package']['password'],
            'packagetemplate' => $params['package']['name_on_server']
        );
        $api->editSiteworxAccount($data);
        return "{$domainName} has been updated.";
    }

    /**
     * Unsuspend an account
     *
     * @param array $args Array of available arguments/variables
     *
     * @return string
     */
    public function doUnSuspend ($args)
    {
        list($userPackage, $params, $serverHostname, $serverKey, $api, $domainName) = $this->_setup($args);
        $api->unsuspendSiteworxAccount($params['package']['domain_name']);
        return "{$domainName} has been unsuspended.";
    }

    /**
     * Get the available actions based on the current status of the account
     *
     * @param UserPackage $userPackage User package
     *
     * @return array
     */
    public function getAvailableActions ($userPackage)
    {
        list(, $params, $serverHostname, $serverKey, $api, $domainName) = $this->_setup($userPackage);
        $actions = array();

        try {
            $account = $api->getSiteworxAccount($domainName);
            $actions[] = 'Delete';

            if ($account['status'] == 'suspended' || $account['status'] == 'inactive') {
                $actions[] = 'UnSuspend';
            } else {
                $actions[] = 'Suspend';
            }
        } catch (Exception $e) {
            $actions[] = 'Create';
        }

        return $actions;
    }

    /**
     * This function outlines variables used when setting up the plugin in the Servers section of ClientExec. It is a
     * required function.
     *
     * @return array
     */
    public function getVariables ()
    {
        /**
         * Specification:
         * - itemkey        used to identify variable in your other functions
         * - type           text, textarea, yesno, password, hidden (type hidden are variables used by CE and are required)
         * - description    description of the variable, displayed in ClientExec
         * - encryptable    used to indicate the variable's value must be encrypted in the database
         */
        $variables = array(
            lang('Name') => array(
                'type'        => 'hidden',
                'description' => lang('Used by ClientExec to display plugin. It must match the action function name(s).'),
                'value'       => 'InterWorx-CP'
            ),
            lang('Description') => array (
                'type'        => 'hidden',
                'description' => lang('Description viewable by admin in server settings'),
                'value'       => lang('InterWorx-CP integration.')
            ),
            lang('Access Key') => array (
                'type'        => 'textarea',
                'description' => lang('Access key used to authenticate to server.'),
                'value'       => '',
                'encryptable' => true
            ),
            lang('Actions') => array (
                'type'        => 'hidden',
                'description' => lang('Actions currently available for this plugin.'),
                'value'       => 'Create,Delete,Suspend,UnSuspend'
            )
        );

        return $variables;
    }
}
