<?php
require_once 'modules/admin/models/ImportPlugin.php';
require_once 'modules/admin/models/ServerGateway.php';
require_once 'modules/admin/models/PackageGateway.php';

class PluginWhmaccounts extends ImportPlugin
{
    var $_title;
    var $_description;

    public function __construct($user)
    {
        $this->_title = lang('cPanel / WHM Accounts');
        $this->_name = 'whmaccounts';
        $this->_description = lang("This import plugin imports accounts from your cPanel/WHM servers.");
        parent::__construct($user);
    }

    function process()
    {
        $packageGateway = new PackageGateway($this->user);
        $userGateway = new UserGateway($this->user);
        $userPackageGateway = new UserPackageGateway($this->user);

        $serverId = $_POST['server'];
        unset($_POST['server']);
        $packageCount = 0;

        foreach ( $_POST as $key => $value ) {
            $exploded = explode('_', $key);
            if ( $exploded[1] == 'selected' ) {

                if ( $value == 'on' ) {

                    $internalName = substr($key, 0, strpos($key, '_'));
                    $username = $_POST[$internalName . '_user'];
                    $domain = $_POST[$internalName . '_domain'];
                    $product = $_POST[$internalName . '_product'];
                    $email = $_POST[$internalName . '_email'];

                    $userId = $userGateway->SearchUserByEmail($email, true);
                    if ( $userId == 0 ) {
                        // need to create a new user
                        $userId = $userGateway->quick_user_create($email, '', '');
                    } else {
                        // found a user... could be a guest, so check and set as needed
                        $user = new User($userId);
                        if ($user->isGuest()) {
                            $user->setGroupId(1);
                            $user->save();
                        }
                    }

                    $userPackageId = $userPackageGateway->saveNewProductToCustomer($userId, $product);
                    $userPackage = new UserPackage($userPackageId);
                    $userPackage->setCustomField('Domain Name', $domain);
                    $userPackage->setCustomField('Server Id', $serverId);
                    $userPackage->setCustomField('User Name', $username);
                    $userPackage->status = PACKAGE_STATUS_ACTIVE;
                    $userPackage->save();
                    $packageCount++;
                }
            }
        }
        CE_Lib::addMessage($packageCount . ' ' . $this->user->lang("packages added from %s.", $this->_name));
        CE_Lib::redirectPage("index.php?fuse=admin&view=viewimportplugins&controller=importexport&plugin={$this->_name}");
    }

    function getForm()
    {
        $serverGateway = new ServerGateway($this->user);
        $pluginGateway = new PluginGateway($this->user);
        $packageTypeGateway = new PackageTypeGateway($this->user);
        $packageGateway = new PackageGateway($this->user);
        $userPackageGateway = new userPackageGateway($this->user);

        $this->view->servers = $serverGateway->getServersByPlugin('cpanel');

        // we have a server selected
        if (isset($_GET['server'])) {

            $serverId = $_GET['server'];

            $this->view->serverId = $serverId;
            $this->view->packages = array();
            $this->view->accounts = array();

            $server = $serverGateway->getServer($serverId);
            $plugin = $pluginGateway->getPluginByName('server', $server['plugin']);
            try {
                $packages = $plugin->getPackages($plugin->buildTestParams($serverId));
                foreach ( $packages as $package ) {
                    $this->view->packages[] = $package->name;
                }

                $accounts = $plugin->getAccounts($plugin->buildTestParams($serverId));

                foreach ( $accounts->acct as $account ) {
                    // if the account already exists, then skip it, as we can't improt it.
                    if ( $userPackageGateway->searchForPackage($account->domain, $account->user, $serverId) ) {
                        continue;
                    }

                    $acct['domain'] = $account->domain;
                    $acct['user'] = $account->user;
                    $acct['plan'] = $account->plan;
                    $acct['email'] = $account->email;
                    $this->view->accounts[] = $acct;
                }
            } catch (Exception $e ) {
                throw new CE_Exception($e->getMessage());
            }

            // get a list of server packages
            $packages = $packageGateway->getProductsGrid();
            $this->view->packages = array();
            foreach ( $packages as $package ) {

                // crude but easy way to check if cPanel, so we only show these packages.
                if ( substr($package['name'], -8) == '(cpanel)' ) {
                    $this->view->packages[$package['groupid']][$package['id']] = $package['name'];
                }
            }
        }
        return $this->view->render('PluginWhmaccounts.phtml');
    }
}