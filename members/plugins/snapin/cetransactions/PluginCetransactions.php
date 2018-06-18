<?php

require_once 'modules/admin/models/SnapinPlugin.php';

class PluginCetransactions extends SnapinPlugin
{

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')       => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => 'Transactions',
            )
        );
        return $variables;
    }

    function init()
    {
        $this->setEnabledByDefault(true);
        $this->setSystemPlugin(true);
        $this->setDescription("This feature adds transaction grids to your installation.");

        // $this->setSettingLocation("billing");
        $this->setPermissionLocation("billing");

        $this->addMappingForTopMenu("admin","billing","globaltransactions","Transactions","Show all billing transactions off of main menu");
        //$this->addMappingForTopMenu("public","clients","usertransactions","Transactions","Show billing transactions to customer in public portal");
        if ($this->user->hasPermission('snapin_cetransactions')) {
            $this->addMappingHook("admin_profiletab","profiletransactions","Transactions", "Show transactions for each user within the client view");
        }

    }

    /**
     * method that gets called before view profiletransactions is processed
     * @return return view
     */
    function profiletransactions()
    {
        // $this->view->title = "testing";
    }

    /**
     * Method that gets called before view globaltransactions is processed
     * @return return view
     */
    function globaltransactions()
    {

    }

    function callAction($callback=true)
    {
        if (!isset($_REQUEST['pluginaction'])) $_REQUEST['pluginaction'] = 'getTransactions';
        switch($_REQUEST['pluginaction']) {
            case "getTransactions":

                $limit = (int)$_REQUEST['limit'];
                $start = (int)$_REQUEST['start'];
                $dir = (isset($_REQUEST['dir'])) ? $_REQUEST['dir']: "ASC";
                $sort = (isset($_REQUEST['dir'])) ? $_REQUEST['sort']: "domain";

                $data = $this->getTransactions($sort, $dir, $start, $limit);
                $this->sendJson($data['results'], $data['total']);
                break;
        }
    }


    private function getTransactions($sort, $dir, $start, $limit)
    {
        include_once 'modules/billing/models/Currency.php';

        $sort = $this->db->escape_string($sort);
        $dir = $this->db->escape_string($dir);

        $invoiceid = $this->getParam('invoiceid',FILTER_SANITIZE_NUMBER_INT,0);
        $userid = $this->getParam('userid',FILTER_SANITIZE_NUMBER_INT,0);
        //if we pass userid, let's get all transactions for the user

        $returnArray = array();
        $returnArray['results'] = array();

        //get all transactions and loop thru to show them
        // XXX This should be turned into an Iterator for 5.2.
        if ($invoiceid != 0) {
            $query = "SELECT it.id, it.accepted, it.response, UNIX_TIMESTAMP(it.transactiondate) AS transactiondate, it.invoiceid, it.transactionid, it.action, it.last4, it.amount, i.pluginused, i.customerid FROM invoicetransaction it, invoice i WHERE i.id = it.invoiceid AND invoiceid=?";
        } else if ($userid != 0) {
            $query = "SELECT it.id, it.accepted, it.response, UNIX_TIMESTAMP(it.transactiondate) AS transactiondate, it.invoiceid, it.transactionid, it.action, it.last4, it.amount, i.pluginused, i.customerid FROM invoicetransaction it, invoice i WHERE i.id = it.invoiceid AND invoiceid in (SELECT id FROM `invoice` WHERE `customerid` = ".$this->db->escape($userid).")";
        } else {
            // allow to get ALL transactions
            $query = "SELECT it.id, it.accepted, it.response, UNIX_TIMESTAMP(it.transactiondate) AS transactiondate, it.invoiceid, it.transactionid, it.action, it.last4, it.amount, i.pluginused, i.customerid FROM invoicetransaction it, invoice i WHERE i.id = it.invoiceid";
        }

        // get full count:
        $fullResult = $this->db->query($query);

        $returnArray['total'] = $fullResult->getNumRows();

        $query .= " ORDER BY $sort $dir LIMIT $start, $limit";

        $result = $this->db->query( $query );
        $num = $result->getNumRows();

        $currency = new Currency( $this->user );

        while ( list( $transid, $accepted, $response, $transdate, $invoiceid, $transactionid, $action, $last4, $amount, $pluginused, $customerid ) = $result->fetch() ) {
            $user = new User($customerid);
            $amount = $currency->format( $user->getCurrency(), $amount, true, 'NONE', true );
            $data  = array(
                    'transid' => $transid,
                    'response' =>$response,
                    'invoiceid' => $invoiceid,
                    'transactionid' => $transactionid,
                    'amount'=> $amount,
                    'pluginused'=>$pluginused,
                    'action' => $action,
                    'userid' => $customerid,
                    'last4' => $last4,
                    'transactiondate' => date( $this->settings->get( 'Date Format' ), $transdate )." ".date( "h:i:s A", $transdate )
                );
            $returnArray['results'][] = $data;
        }
        return $returnArray;
    }

    function sendJson($arrData, $totalData = 0, $error=false, $message = "")
    {
        if ($error) {
            $arr = array("success" => false, "error"=>true, "message"=>$message, "total"=>$totalData, "data"=>$arrData);
        } else {
            $arr = array("success" => true, "error"=>false, "message"=>"", "total"=>$totalData, "data"=>$arrData);
        }
        echo CE_Lib::jsonencode($arr);
    }
}
