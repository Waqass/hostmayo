<?php
require_once 'modules/admin/models/ImportPlugin.php';
require_once 'modules/admin/models/ServerGateway.php';
require_once 'modules/admin/models/PackageGateway.php';

class PluginWhmaccounts extends ImportPlugin
{
    var $_title;
    var $description;

    public function __construct($user)
    {
        $this->_title = lang('cPanel / WHM Accounts');
        $this->_name = 'whmaccounts';
        $this->description = lang("This import plugin imports accounts from your cPanel/WHM servers.");
        parent::__construct($user);
    }

    function process()
    {
        $packageGateway = new PackageGateway($this->user);

        $serverId = $_POST['server'];
        unset($_POST['server']);
        $packageCount = 0;

        foreach ( $_POST as $key => $value ) {
            $exploded = explode('_', $key);
            if ( $exploded[0] == 'selected' ) {

                if ( $value == 'on' ) {
                    $internalName = substr($key, strpos($key, '_')+1);
                    $productName = $_POST['name_' . $internalName];
                    $group = $_POST['group_' . $internalName];

                    $productId = $packageGateway->createNewPackage($group);
                    $package = new Package($productId);
                    $package->planname = $productName;
                    $package->save();
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

        $this->view->servers = $serverGateway->getServersByPlugin('cpanel');

        // we have a server selected
        if ( isset($_GET['server']) ) {

            $this->view->packageGroups = array();
            $this->view->packages = array();
            $this->view->accounts = array();

            $packageTypes = $packageTypeGateway->getPackageTypes(1, 'type');
            while ( $type = $packageTypes->fetch() ) {
                $group = array();
                $group['id'] = $type->getId();
                $group['name'] = $type->getName();
                $this->view->packageGroups[$group['id']] = $group;
            }

            $serverId = $_GET['server'];
            $this->view->serverId = $serverId;
            $server = $serverGateway->getServer($serverId);
            $plugin = $pluginGateway->getPluginByName('server', $server['plugin']);
            try {
                $packages = $plugin->getPackages($plugin->buildTestParams($serverId));
                foreach ( $packages as $package ) {
                    $this->view->packages[] = $package->name;
                }

                $accounts = $plugin->getAccounts($plugin->buildTestParams($serverId));

                foreach ( $accounts->acct as $account ) {
                    $acct = array();
                    $acct['domain'] = $account->domain;
                    $acct['user'] = $account->user;
                    $acct['plan'] = $account->plan;
                    $this->view->accounts[] = $acct;
                }
             } catch (Exception $e ) {
                throw new CE_Exception($e->getMessage());
            }

            $packages = $packageGateway->getProductsGrid(1);
            $this->view->packages = array();
            foreach ( $packages as $package ) {
                $this->view->packages[$package['groupid']][] = $package['name'];
            }
        }
        return $this->view->render('PluginWhmaccounts.phtml');
    }
}