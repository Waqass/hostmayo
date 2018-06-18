<?php

require_once 'modules/admin/models/SnapinPlugin.php';

class PluginDomainrenewallogs extends SnapinPlugin
{

    public function getVariables()
    {
        $variables = array(
            lang('Plugin Name')       => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => 'Domain Renewal Logs',
            )
        );
        return $variables;
    }

    public function init()
    {
        $this->setEnabledByDefault(true);
        $this->setDescription("This feature adds a grid to all domain packages to display domain renewal logs");
        $this->addMappingHook("admin_profileproducttab","package","Renewal Logs", "Adds a grid to display domain renewal logs to all domain packages", PACKAGE_TYPE_DOMAIN);
    }

    public function package()
    {

    }
}