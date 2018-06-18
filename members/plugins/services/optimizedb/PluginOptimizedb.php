<?php
require_once 'modules/admin/models/ServicePlugin.php';
/**
* @package Plugins
*/
class PluginOptimizedb extends ServicePlugin
{
    protected $featureSet = 'restricted';
    public $hasPendingItems = false;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Optimize DataBase'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, this service will optimize all tables in your ClientExec database.'),
                'value'         => '0',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '30',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '01',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '15',
            ),
            lang('Run schedule - Month')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day of the week')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'         => '*',
            ),
        );

        return $variables;
    }

    function execute()
    {
        @set_time_limit(0);

        $toOptimize = $this->getTablesToOptimize();
        if ( count ( $toOptimize ) > 0 ) {
            foreach ( $toOptimize as $table ) {
                $this->db->query("OPTIMIZE TABLE `{$table}`");
            }
        }
    }

    function getTablesToOptimize()
    {
        $configuration = Zend_Registry::get('configuration');
        $database = $this->db->escape_string($configuration['application']['dbSchema']);
        // We need to remove Engine='MyISAM' when we can see how innodb treats fragmented tables.
        $result = $this->db->query("SHOW TABLE STATUS FROM `{$database}` WHERE Data_free>0 AND Engine='MyISAM'");
        $toOptimize = array();
        while ( $row = $result->fetch() ) {
            $toOptimize [] = $row['Name'];
        }
        return $toOptimize;
    }
}
