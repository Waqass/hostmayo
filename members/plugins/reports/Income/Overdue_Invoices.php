<?php

require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/Currency.php';

/*
 * @package Reports
 */

class Overdue_Invoices extends Report {

    private $lang;

    protected $featureSet = 'accounts';
    public $hasgraph = false;

    function __construct($user=null,$customer=null)
    {
        parent::__construct($user, $customer);
    }

    public function process()
    {
        $this->SetDescription($this->user->lang('Overdue Invoices.'));

        if ( isset($_GET['generate'])) {
            $this->generate();
        }

        $aGroup = array();
        $aLabel = array("CE PKG Status","Invoice Date","Invoice #","Amount","Customer","CE PKG");

        if ( isset($_GET['page'])) {
            $page = $_GET['page'];
        } else {
            $page = 1;
        }
        if ( isset($_GET['results'])) {
            $count = $_GET['results'];
        } else {
            $count = 1000;
        }
        $offset = ($page - 1) * $count;
        $limit = array(
          'offset' => $offset,
          'count'  => $count
        );
        $OverdueInvoicesValues = $this->_getOverdueInvoicesData($limit);
        foreach ($OverdueInvoicesValues['Overdue Invoices Values'] as $OverdueInvoiceData) {
            if (count($OverdueInvoiceData["CE PKG"]) > 0) {
                foreach ($OverdueInvoiceData["CE PKG"] as $OverdueInvoice_CE_PKG) {
                    $CE_PKG = "<a href='index.php?fuse=clients&controller=userprofile&view=profileproduct&id=".$OverdueInvoice_CE_PKG."' >".$OverdueInvoice_CE_PKG."</a>";
                    $CE_PKG_Status = $OverdueInvoiceData["CE PKG Status"][$OverdueInvoice_CE_PKG];

                    $group = array(
                        $CE_PKG_Status,
                        $OverdueInvoiceData["Invoice Date"],
                        "<a href='index.php?fuse=billing&controller=invoice&view=invoice&frmClientID=".$OverdueInvoiceData["CE ID"]."&invoiceid=".$OverdueInvoiceData["Invoice #"]."'>".$OverdueInvoiceData["Invoice #"]."</a>",
                        $OverdueInvoiceData["Amount"],
                        "<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=".$OverdueInvoiceData["CE ID"]."'>".$OverdueInvoiceData["Customer"]."</a>",
                        $CE_PKG
                    );
                    $aGroup[] = $group;
                }
            } else {
                $group = array(
                    'NA',
                    $OverdueInvoiceData["Invoice Date"],
                    "<a href='index.php?fuse=billing&controller=invoice&view=invoice&frmClientID=".$OverdueInvoiceData["CE ID"]."&invoiceid=".$OverdueInvoiceData["Invoice #"]."'>".$OverdueInvoiceData["Invoice #"]."</a>",
                    $OverdueInvoiceData["Amount"],
                    "<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=".$OverdueInvoiceData["CE ID"]."'>".$OverdueInvoiceData["Customer"]."</a>",
                    'NA'
                );
                $aGroup[] = $group;
            }
        }

        $this->reportData[] = array(
            "group"     => $aGroup,
            "groupname" => "Overdue Invoices",
            "label"     => $aLabel,
            "groupId"   => "",
            "isHidden"  => false
        );

        $displayingInitial = $offset + 1;
        $displayingEnding = min(($offset + $count), $OverdueInvoicesValues['Total Count']);
        $disablePrevious = '';
        $disableNext = '';
        if ($page == 1) {
            $disablePrevious = ' disabled';
        }
        if ($displayingEnding == $OverdueInvoicesValues['Total Count']) {
            $disableNext = ' disabled';
        }

        echo '<table width=100%>';
        echo '<tr>';
        echo '<td>';
        echo '<b>Displaying items:&nbsp;'.$displayingInitial.' - '.$displayingEnding.' of '.$OverdueInvoicesValues['Total Count'].'</b>';
        echo '</td>';
        echo '<td width=250px align=right>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>';
        echo "<button class='btn".$disablePrevious."' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Overdue+Invoices&type=Income&page=".($page-1)."&results=".CE_Lib::viewEscape($count)."\"'".$disablePrevious."><&nbsp;&nbsp;"
            .$this->user->lang("Previous") . "</button>";
        echo "&nbsp;&nbsp;&nbsp;";
        echo "<button class='btn".$disableNext."' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Overdue+Invoices&type=Income&page=".($page+1)."&results=".CE_Lib::viewEscape($count)."\"'".$disableNext.">"
            .$this->user->lang("Next") . "&nbsp;&nbsp;></button>";
        echo '</td>';
        echo '<td width=250px align=right>';
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Overdue+Invoices&type=Income&generate=1\"'>"
            .$this->user->lang("Download .csv") . "</button>";
        echo '</td>';
        echo '</tr>';
        echo '</table>';

    }

    function generate()
    {
        $csv = $this->_getOverdueInvoicesCSV();
        CE_Lib::download($csv, 'Overdue_Invoices.csv');
    }

    function _getOverdueInvoicesCSV()
    {
        $csv = '"CE PKG Status","Invoice Date","Invoice #","Amount","Customer","CE PKG"'."\n";

        $OverdueInvoicesValues = $this->_getOverdueInvoicesData();
        foreach ($OverdueInvoicesValues['Overdue Invoices Values'] as $OverdueInvoiceData) {
            if (count($OverdueInvoiceData["CE PKG"]) > 0) {
                foreach ($OverdueInvoiceData["CE PKG"] as $OverdueInvoice_CE_PKG) {
                    $CE_PKG_Status = $OverdueInvoiceData["CE PKG Status"][$OverdueInvoice_CE_PKG];

                    $csv .= "\"".$CE_PKG_Status."\""
                        .",\"".$OverdueInvoiceData["Invoice Date"]."\""
                        .",\"".$OverdueInvoiceData["Invoice #"]."\""
                        .",\"".$OverdueInvoiceData["Amount"]."\""
                        .",\"".$OverdueInvoiceData["Customer"]."\""
                        .",\"".$OverdueInvoice_CE_PKG."\"\n";
                }
            } else {
                $csv .= "\"\""
                    .",\"".$OverdueInvoiceData["Invoice Date"]."\""
                    .",\"".$OverdueInvoiceData["Invoice #"]."\""
                    .",\"".$OverdueInvoiceData["Amount"]."\""
                    .",\"".$OverdueInvoiceData["Customer"]."\""
                    .",\"\"\n";
            }
        }

        return $csv;
    }

    function _getOverdueInvoicesData($limit = false)
    {
        include_once 'modules/admin/models/StatusAliasGateway.php';
        $userStatuses = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(array(USER_STATUS_PENDING, USER_STATUS_ACTIVE));

        $packageStatuses = StatusAliasGateway::getInstance($this->user)->getAllStatuses(ALIAS_STATUS_PACKAGE);
        $PackagesStatuses = array();
        foreach ($packageStatuses as $packageStatus) {
            $PackagesStatuses[$packageStatus->statusid] = $packageStatus->name;
        }

        $queryTotalCount = "SELECT COUNT(*) FROM `invoice` i, `users` u WHERE u.`id` = i.`customerid` AND i.`status` IN (".INVOICE_STATUS_UNPAID.", ".INVOICE_STATUS_PARTIALLY_PAID.") AND u.`status` IN (".implode(', ', $userStatuses).") AND DATE(i.`billdate`) <= DATE(NOW()) AND i.`balance_due` > 0 ";
        $resultTotalCount = $this->db->query($queryTotalCount);
        list($totalCount) = $resultTotalCount->fetch();

        $queryOverdueInvoices = "SELECT i.`billdate`, i.`id`, i.`balance_due`, u.`currency`, i.`customerid` FROM `invoice` i, `users` u WHERE u.`id` = i.`customerid` AND i.`status` IN (".INVOICE_STATUS_UNPAID.", ".INVOICE_STATUS_PARTIALLY_PAID.") AND u.`status` IN (".implode(', ', $userStatuses).") AND DATE(i.`billdate`) <= DATE(NOW()) AND i.`balance_due` > 0 ORDER BY i.`billdate` ASC ";
        if ($limit !== false) {
            $queryOverdueInvoices .= "LIMIT ".$limit['offset'].", ".$limit['count']." ";
        }
        $resultOverdueInvoices = $this->db->query($queryOverdueInvoices);
        $OverdueInvoices = array();
        $OverdueInvoicesIds = array();
        $currency = new Currency($this->user);
        while ($rowOverdueInvoices = $resultOverdueInvoices->fetch()) {
            $user = new User($rowOverdueInvoices['customerid']);
            $OverdueInvoices[$rowOverdueInvoices['id']] = array(
                'CE PKG Status'   => array(),
                'Invoice Date'    => $rowOverdueInvoices['billdate'],
                'Invoice #'       => $rowOverdueInvoices['id'],
                'Amount'          => $currency->format($rowOverdueInvoices['currency'], $rowOverdueInvoices['balance_due'], true),
                'CE ID'           => $rowOverdueInvoices['customerid'],
                'Customer'        => $user->getFullName(true) . ' (' . $user->getId() . ')',
                'CE PKG'          => array()
            );
            $OverdueInvoicesIds[] = $rowOverdueInvoices['id'];
        }

        if (count($OverdueInvoicesIds) > 0) {
            $queryPackagesValues = "SELECT DISTINCT d.`id`, d.`status`, d.`CustomerID`, ie.`invoiceid` FROM `domains` d, `invoiceentry` ie WHERE d.`id` = ie.`appliestoid` AND ie.`appliestoid` > 0 AND ie.`invoiceid` IN (".implode(',', $OverdueInvoicesIds).") ORDER BY d.`CustomerID` ASC, ie.`invoiceid` ASC, d.`id` ASC ";
            $resultPackagesValues = $this->db->query($queryPackagesValues);
            while ($rowPackagesValues = $resultPackagesValues->fetch()) {
                $OverdueInvoices[$rowPackagesValues['invoiceid']]['CE PKG Status'][$rowPackagesValues['id']] = $PackagesStatuses[$rowPackagesValues['status']];
                $OverdueInvoices[$rowPackagesValues['invoiceid']]['CE PKG'][$rowPackagesValues['id']]        = $rowPackagesValues['id'];
            }
        }

        return array(
            'Overdue Invoices Values' => $OverdueInvoices,
            'Total Count'     => $totalCount
        );
    }

}
