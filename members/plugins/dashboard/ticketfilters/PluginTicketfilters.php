<?php
require_once 'modules/admin/models/DashboardPlugin.php';

class PluginTicketFilters extends DashboardPlugin
{
    var $name;
    var $smallName;

    var $description;
    var $default = true; // plugin to be included with fresh installs
    var $cache = true;
    var $sidebarPlugin = true;
    var $order = 1;
    var $iconName  = "icon-filter"; // This must be a bootstrap defined icon

    var $jsLibs  = array('plugins/dashboard/ticketfilters/plugin.js');
    var $cssPages = array('plugins/dashboard/ticketfilters/plugin.css');

    function __construct($user, $typeOfFetch = 1) {
        $this->name = ucwords("Ticket filters");
        $this->smallName = "Tickets";
        $this->description = "This shows your custom ticket filters, also available under the Support menu.";
        parent::__construct($user,$typeOfFetch);
    }

    public function getPanel() {
        include_once "modules/support/models/TicketSummaryGateway.php";
        $ticketSummaryGateway = new TicketSummaryGateway($this->user);
        $this->view->filters = $ticketSummaryGateway->GetTicketFilters();
        $this->view->filters = $this->view->filters['filters'];
        return parent::getPanel();
    }
}

