<?php

require_once 'modules/admin/models/StatusAliasGateway.php';

/*
 * @package Reports
 */

class Account_Review extends Report {

    private $lang;

    protected $featureSet = 'accounts';
    public $hasgraph = true;

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Account Review');
        parent::__construct($user,$customer);
    }

    protected function _getCustomerStatusList() {
        $statusList = array();
        foreach (StatusAliasGateway::getInstance($this->user)->getAllStatuses(ALIAS_STATUS_USER) as $status) {
            $statusList[] = array($status->statusid, $this->user->lang($status->name));
        }

        return $statusList;
    }

    protected function _getProductStatusList() {
        $statusList = array();
        foreach (StatusAliasGateway::getInstance($this->user)->getAllStatuses(ALIAS_STATUS_PACKAGE) as $status) {
            $statusList[] = array($status->statusid, $this->user->lang($status->name));
        }

        return $statusList;
    }

    protected function _getTotal($status, $totals) {
        return isset($totals[$status]) ? $totals[$status] : 0;
    }

    public function GraphData($customer_totals, $package_totals) {

        $customerStatusList = $this->_getCustomerStatusList();
        $productStatusList = $this->_getProductStatusList();

        //building graph data to pass back
        $graph_data = array(
              "xScale" => "ordinal",
              "yScale" => "linear",
              "xType" => "number",
              "type" => "bar",
              "main" => array());


        $year_data = array();
        $year_data['className'] = ".report_customer_status";
        $year_data['data'] = array();


        foreach ($customerStatusList as $row) {
            if ($row[0]==-3) continue;

            $label = $row[1];
            $value = $this->_getTotal($row[0], $customer_totals);

            $status_data = array();
            $status_data["x"] = $label;
            $status_data["y"] = $value;
            $status_data["tip"] = "<strong>".$row[1]." ".$this->user->lang("Customers")."</strong><br/>".$value;
            $year_data['data'][] = $status_data;
        }
        $graph_data["main"][] = $year_data;            


        $year_data = array();
        $year_data['className'] = ".report_product_status";
        $year_data['data'] = array();
        foreach ($productStatusList as $row) {

            $label = $row[1];
            $value = $this->_getTotal($row[0], $package_totals);

            $status_data = array();
            $status_data["x"] = $label;
            $status_data["y"] = $value;
            $status_data["tip"] = "<strong>".$row[1]." ".$this->user->lang("Products")."</strong><br/>".$value;
            $year_data['data'][] = $status_data;
        }
        $graph_data["main"][] = $year_data;

        return json_encode($graph_data);
          
    }

    public function process() {
        $this->SetDescription($this->user->lang('Your customer and product\'s status.'));

        $graphdata = @$_GET['graphdata'];

        $query = "SELECT COUNT(*) as total, status FROM users WHERE groupid=1 GROUP BY status";
        $result = $this->db->query($query);
        $customer_totals = array();
        while ($row = $result->fetch()) {
            $customer_totals[$row['status']] = $row['total'];
        }

        $query = 'SELECT COUNT(*) AS total, status FROM domains GROUP BY status';
        $result = $this->db->query($query);
        $package_totals = array();
        while ($row = $result->fetch()) {
            $package_totals[$row['status']] = $row['total'];
        }

        if ($graphdata) {
            
            //this supports lazy loading and dynamic loading of graphs
            $this->reportData = $this->GraphData($customer_totals, $package_totals);
            return;            

        }     

        $aGroup = array();
        $aLabel = array($this->user->lang('Status'), $this->user->lang('Count'));

        $statusList = $this->_getCustomerStatusList();
        foreach ($statusList as $row) {
            $label = $row[1];
            $value = $this->_getTotal($row[0], $customer_totals);

            $group = array($label, $value);

            $aGroup[] = $group;
        }

        $this->reportData[] = array("group" => $aGroup,
            "groupname" => $this->user->lang("Customer Overview"),
            "label" => $aLabel,
            "groupId" => "",
            "isHidden" => false);


        $aGroup = array();
        $aLabel = array($this->user->lang('Status'), $this->user->lang('Count'));
        $statusList = $this->_getProductStatusList();
        foreach ($statusList as $row) {
            $label = $row[1];
            $value = $this->_getTotal($row[0], $package_totals);

            $group = array($label, $value);

            $aGroup[] = $group;
        }

        $this->reportData[] = array("group" => $aGroup,
            "groupname" => $this->user->lang("Package Overview"),
            "label" => $aLabel,
            "groupId" => "",
            "isHidden" => false);

    }

}
