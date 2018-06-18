<?php
require_once 'modules/admin/models/ImportPlugin.php';

class PluginBlesta extends ImportPlugin
{
    protected $_description;
    protected $_name = 'blesta';
    protected $_title = 'Blesta (Beta)';
    protected $_tplPath = 'PluginBlesta.phtml';

    function __construct($user, $typeOfFetch = 1)
    {
        $this->_description = lang("This plugin imports customers, packages, invoices, servers from a Blesta installation.");
        parent::__construct($user, $typeOfFetch);
    }
}
