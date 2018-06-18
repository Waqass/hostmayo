<?php
/**
 * Prowl Plugin FIle
 *
 * @category Services
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */

require_once 'modules/admin/models/ServicePlugin.php';
require_once 'plugins/services/prowl/ProwlPHP.php';

/**
 * PluginProwl Action Class
 *
 * @category Services
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */
class PluginProwl extends ServicePlugin
{
    public $hasPendingItems = false;

    var $lastRun = null;
    var $notifications = array();

    /**
     * Service getVariables Method
     *
     * @return array - list of valid variables
     */
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Prowl Push Notifications'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, this service plugin pushes certain notifications from ClientExec to the Prowl application on your iPhone. Please note, Curl with SSL is required to use the Prowl service. <b>This service requires the Paid iPhone application Prowl to work</b>'),
                'value'         => '0',
            ),
            lang('Prowl API Keys')  => array(
                'type'          => 'textarea',
                'description'   => lang('Enter the API key for your Prowl account (separate multiple keys with a comma)'),
                'value'         => ''
            ),
            lang('Notify of New Orders')  => array(
                'type'          => 'yesno',
                'description'   => lang('Select Yes to be notified when a new order is placed.'),
                'value'         => '1',
            ),
            lang('Notify of New High Priority Tickets')  => array(
                'type'          => 'yesno',
                'description'   => lang('Select Yes to be notified when a new High Priority Ticket is received.'),
                'value'         => '1',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*/5',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
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
            lang('LastRun')  => array(
                'type'          => 'hidden',
                'description'   => lang('Timestamp of last run. Used to determine new items to push'),
                'value'         => ''
            ),
        );

        return $variables;
    }

    /**
     * Function that is called by the service when executing via Cron
     *
     * @return null
     */
    function execute()
    {

        // service execution can take a while
        @set_time_limit(0);

        // Grab the last run, make one up if the LastRun isn't set to avoid sending alerts for every item.
        $this->lastRun = $this->settings->get('plugin_prowl_LastRun');
        ($this->lastRun == '')? $this->lastRun = time() : false;
        $this->lastRun = date('Y-m-d G:i:s', $this->lastRun);

        // Get the orders
        $this->getNewOrders();

        // Get the tickets
        $this->getHighPriTickets();

        // Get the list of Prowl API key's and start making some alerts
        $APIKeys = $this->settings->get('plugin_prowl_Prowl API Keys');
        $APIKeys = explode(',', $APIKeys);

        foreach($APIKeys AS $key) {

            // Start the prowl API
            $prowl = new Prowl($key);

            //Loop the notifications to send
            foreach($this->notifications AS $notification) {

                $prowl->push(array(
                        'application'=>'ClientExec',
                        'event'=>$notification['event'],
                        'description'=>$notification['description'],
                        'priority'=>0
                    ),true);
            }
        }

        // Set the last run
        $this->settings->deleteValue('plugin_prowl_LastRun');
        $this->settings->insertValue('plugin_prowl_LastRun', time(), '', false, true);
    }

    /**
     * Function to save a list of new high priority tickets
     *
     * @return null
     */
    function getHighPriTickets()
    {
        // Check if we are doing this bit
        if($this->settings->get('plugin_prowl_Notify of New High Priority Tickets')) {

            // Start querying
            $query = "SELECT id FROM troubleticket WHERE priority = '1' AND datesubmitted >= '".$this->lastRun."'";
            $result = $this->db->query($query);
            while ($row = $result->fetch()) {

                $this->notifications[] = array('event' => 'High Priority Ticket', 'description' => "A new High Priority Ticket has been submitted #".$row['id']);
            }
        }
    }

    /**
     * Function to save a list of new orders
     *
     * @return null
     */
    function getNewOrders()
    {
        // Check if we are doing this bit
        if($this->settings->get('plugin_prowl_Notify of New Orders')) {

            // Start querying
            $query = "SELECT id FROM domains WHERE dateActivated >= '".$this->lastRun."'";
            $result = $this->db->query($query);
            while ($row = $result->fetch()) {

                $this->notifications[] = array('event' => 'New Order', 'description' => "A new order has been received #".$row['id']);
            }
        }
    }

    function output()
    {
    }

    function dashboard()
    {
    }
}
?>
