<?php
require_once 'modules/admin/models/DashboardPlugin.php';

class PluginKbsearch extends DashboardPlugin
{
    /* plugin member vars used by ClientExec */
    var $name;
    var $smallName;

    var $description;
    var $default = true; //to be included with fresh installs
    var $sidebarPlugin = true;
    var $cache = true;
    var $iconName  = "icon-book"; // must be bootstrap defined icon
    var $cssPages = array('plugins/dashboard/kbsearch/plugin.css');
    var $jsLibs  = array('plugins/dashboard/kbsearch/plugin.js');

    function __construct($user, $typeOfFetch = 1) {
        $this->name = lang("Knowledgebase Search");
        $this->smallName = lang("KB Search");
        $this->description = lang("Search your KB articles to assist support staff with ticket resolution.");
        parent::__construct($user,$typeOfFetch);
    }
}
