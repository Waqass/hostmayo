<?php
require_once 'modules/admin/models/DashboardPlugin.php';
require_once 'modules/clients/models/UserGateway.php';

class PluginWhoisonline extends DashboardPlugin
{
    /* plugin member vars used by ClientExec */
    var $name;
    var $smallName;

    var $description;
    var $default = true; // plugin to be included with fresh installs
    var $cache = true;
    var $sidebarPlugin = true;
    var $order = 2;
    var $iconName  = "icon-eye-open"; // This must be a bootstrap defined icon

    var $jsLibs  = array('plugins/dashboard/whoisonline/plugin.js');
    var $cssPages = array('plugins/dashboard/whoisonline/plugin.css,plugins/dashboard/whoisonline/assets/flags.css');

    function __construct($user, $typeOfFetch = 1) {
        $this->name = lang("Who's Online");
        $this->smallName = lang("Online");
        $this->description = lang("This shows the users currently logged into your ClientExec.");
        parent::__construct($user,$typeOfFetch);
    }

    //override the getPanel of DashboardPlugin as we do not want or have an index.phtml to output
    //we can just return html directly
    public function getPanel() {
        return '<div id="plugin-whoisonline"><img class="content-loading" src="../images/loader.gif" /></div>';
    }

}
