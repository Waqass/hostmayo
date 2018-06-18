<?php

/**
 * Income Growth Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.3
 * @link     http://www.clientexec.com
 *
 * ************************************************
 *   1.3 Updated the report to use Pear Commenting & the new title handing to make app reports consistent.
 * ***********************************************
 */
require_once 'modules/billing/models/Currency.php';

/**
 * Income_Growth Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.3
 * @link     http://www.clientexec.com
 */
class Income_Growth extends Report {

    private $lang;

    protected $featureSet = 'billing';
    public $hasgraph = true;

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Income Growth');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process() {
        //unset($this->session->incomegrowthdata);

        // Set the report information
        $this->SetDescription($this->user->lang('Displays income trends from previous month and year'));


        if (!isset($_REQUEST['passedyear'])) {
            $currentYear = date("Y");
        } else {
            $currentYear = $_REQUEST['passedyear'];
        }

        if (!isset($_REQUEST['graphtype'])) {
            $currentGraph = "income";
        } else {
            $currentGraph = $_REQUEST['graphtype'];
        }

        $thisYear = Date("Y");

        $currency = new Currency($this->user);

        $graphdata = @$_GET['graphdata'];
        $getinvoices = @$_GET['getinvoices'];

        //if (!isset($this->session->incomegrowthdata)) {

            $sql = "SELECT DATE_FORMAT(i.datepaid,'%m') AS month, YEAR(i.datepaid) AS year, COUNT(*) AS counted, SUM(amount) AS amount, SUM(subtotal) as subtotal, SUM(tax) as tax "
                    . "FROM invoice i "
                    . "WHERE i.status=1 and i.datepaid <> '' "
                    . "GROUP BY YEAR(i.datepaid), MONTH(i.datepaid);";
            $result = $this->db->query($sql);



            $firstTime = true;
            $sumCount = 0;
            $sumAmount = 0;
            $sumTax = 0;
            $lastMonthAmount = "na";
            $aYear = array();
            $aYearForGraph = array();
            $newYear = 0;
            $rawYears = array();
            $rawYear = array();

            while ($row = $result->fetch()) {

                $tMonth = date("F", mktime(0, 0, 0, $row['month'] + 1, 0, 0));

                $tYear = $row['year'];
                if ($firstTime) {
                    $newYear = $tYear;
                    $firstTime = false;
                }
                if ($newYear != $tYear) {
                    $rawYears[$newYear] = $rawYear;
                    $aYears[$newYear]["array"] = $aYear;
                    $aYears[$newYear]["arrayForGraph"] = $aYearForGraph;
                    $aYears[$newYear]["sumAmount"] = $sumAmount;
                    $aYears[$newYear]["sumCount"] = $sumCount;
                    $aYears[$newYear]["sumTax"] = $sumTax;

                    $newYear = $tYear;
                    unset($aYear);
                    $sumCount = 0;
                    $sumAmount = 0;
                    $sumTax = 0;
                }
                $tAmount = $row['amount'];
                $sumAmount += $tAmount;

                $tSubTotal = $row['subtotal'];

                //$tTax = $row['tax'];
                // $sumTax += $tTax;
                //Modified in reference to ticket #40938

                $tTax = $tAmount - $tSubTotal;
                $sumTax += $tTax;


                $tCounted = $row['counted'];
                $sumCount += $tCounted;

                $tAmount = $currency->format($this->settings->get('Default Currency'), $tAmount, true);
                $tTax = $currency->format($this->settings->get('Default Currency'), $tTax, true);

                $aYear[] = array(
                    "<a href=\"javascript:LoadInvoiceForMonthYear('".$row['month']."','".$row['year']."','".$row['year'].$tMonth."');\">".$tMonth."</a>",
                    $tCounted,
                    $this->AverageInvoicePrice($tCounted, $row['amount']),
                    $tAmount,
                    $tTax,
                    $this->GetLastMonthDifference($lastMonthAmount, $tMonth, $row['amount']),
                    $this->GetLastYearDifference($rawYears, $tMonth, $tYear, $row['amount']),
                );

                //same data as above but we are including the month as the key for graph
                $aYearForGraph[$tMonth] = array(
                    $tMonth,
                    $tCounted,
                    $this->AverageInvoicePrice($tCounted, $row['amount']),
                    $tAmount,
                    $tTax,
                    $this->GetLastMonthDifference($lastMonthAmount, $tMonth, $row['amount']),
                    $this->GetLastYearDifference($rawYears, $tMonth, $tYear, $row['amount']),
                );

                $rawYear[$tMonth] = $row['amount'];
                $lastMonthAmount = $row['amount'];
            }

            $rawYears[$newYear] = $rawYear;
            $aYears[$newYear]["array"] = $aYear;
            $aYears[$newYear]["arrayForGraph"] = $aYearForGraph;
            $aYears[$newYear]["sumAmount"] = $sumAmount;
            $aYears[$newYear]["sumCount"] = $sumCount;
            $aYears[$newYear]["sumTax"] = $sumTax;
            //$this->session->incomegrowthdata = $aYears;
        //} else {
        //    $aYears = $this->session->incomegrowthdata;
        //}

        if ($graphdata) {
            //this supports lazy loading and dynamic loading of graphs
            $this->reportData = $this->GraphData($currentGraph, $aYears);
            return;
        } else if ($getinvoices) {
            $this->GetInvoiceForMonth();
            exit;
        }

        $yearCount = count($aYears);
        foreach ($aYears as $key => $aYear) {
            $grouphidden = true;
            $groupid = "id-" . $key;

            if ($currentYear == $key) {
                $grouphidden = false;
            } else {
                if (mb_substr($currentYear, 0, 4) == "Last") {
                    $tYears = mb_substr($currentYear, 4);
                    for ($y = 0; $y < $tYears; $y++) {
                        if ($key == date("Y") - $y)
                            $grouphidden = false;
                    }
                }
            }

            $this->reportData[] = array(
                "group" => $aYear["array"],
                "groupname" => $key,
                "label" => array($this->user->lang('Month'), $this->user->lang('Invoices'), $this->user->lang('Avg. Price'), $this->user->lang('Month Total'), $this->user->lang('Month Tax'), "&Delta; " . $this->user->lang('Last Month'), "&Delta; " . $this->user->lang('Last Year')),
                "groupId" => $groupid,
                "isHidden" => $grouphidden);

            $aGroup[] = array("--------", $aYear["sumCount"], $currency->format($this->settings->get('Default Currency'), $aYear["sumAmount"], true), $currency->format($this->settings->get('Default Currency'), $aYear["sumTax"], true), "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");

            $this->reportData[] = array(
                "istotal" => true,
                "group" => $aGroup,
                "label" => array("", $this->user->lang('Total Count'), $this->user->lang('Total Amount'), $this->user->lang('Total Tax'), ""),
                "groupId" => $groupid . "-totals",
                "isHidden" => $grouphidden);


            unset($aGroup);
        }

        //Display Report
        echo "\n\n<script type='text/javascript'>";

        echo "function ShowYears(obj){ ";
        echo "    var strYear = obj.value;";
        //Loop the number of years
        for ($x = 0; $x < $yearCount; $x++) {
            $xYear = $thisYear - $x;
            echo "if(document.getElementById('id-$xYear') != null) {\n";
            echo "    document.getElementById('id-$xYear').style.display='none';\n";
            echo "}\n";
            echo "if(document.getElementById('id-$xYear-totals') != null) {\n";
            echo "    document.getElementById('id-$xYear-totals').style.display='none';\n";
            echo "}\n";
        }
        echo "    if(strYear.substring(0,4)==\"Last\"){\n";
        echo "        yearsback = strYear.substring(4);\n";
        echo "        for(x=0;x<yearsback;x++){\n";
        echo "            if(document.getElementById('id-'+(" . $thisYear . "-x)) != null){\n";
        echo "                document.getElementById('id-'+(" . $thisYear . "-x)).style.display='';\n";
        echo "            };\n";
        echo "            if(document.getElementById('id-'+(" . $thisYear . "-x)+'-totals') != null){\n";
        echo "                document.getElementById('id-'+(" . $thisYear . "-x)+'-totals').style.display='';\n";
        echo "            };\n";
        echo "        }\n";
        echo "    }else{\n";
        echo "        if(document.getElementById('id-'+obj.value) != null){\n";
        echo "            document.getElementById('id-'+obj.value).style.display='';\n";
        echo "        };\n";
        echo "        if(document.getElementById('id-'+obj.value+'-totals') != null){\n";
        echo "            document.getElementById('id-'+obj.value+'-totals').style.display='';\n";
        echo "        };\n";
        echo "    }\n";
        echo "    clientexec.populate_report('Income_Growth-Income','#myChart',{passedyear:strYear});\n";
        echo "}\n";

        echo "function updateInvoicesForMonthYear(responseObj){\n";
        echo "    var responseArr = responseObj.responseText.split('||');\n";
        echo "    document.getElementById(responseArr[0]).style.display='';\n";
        echo "    document.getElementById(responseArr[0]).innerHTML=responseArr[1];\n";
        echo "}\n";

        echo "function LoadInvoiceForMonthYear(month,year,id){\n";
        echo "    var url;\n";


        $url = "admin/index.php?noheaderfooter=1&getinvoices=1&month='+month+'&year='+year+'&view=".CE_Lib::viewEscape($_REQUEST['view'])."&fuse=".CE_Lib::viewEscape($_REQUEST['fuse'])."&report=".urlencode($_REQUEST['report'])."&type=".CE_Lib::viewEscape($_REQUEST['type']);
        $url = mb_substr(CE_Lib::getSoftwareURL(),-1,1) == "//" ? CE_Lib::getSoftwareURL().$url : CE_Lib::getSoftwareURL()."/".$url;
        echo "    url ='".$url."';\n";

        echo "    window.open(url, '', 'top=100, left=100, width=800, height=600, scrollbars=yes');\n";
        echo "}\n";


        echo "</script>";
        echo "\n\n";

        echo "<form id='reportdropdown' method='GET'>";
        echo "<input type='hidden' name='fuse' value='reports' />";
        echo "<input type='hidden' name='view' value='viewreport' />";
        echo "<input type='hidden' name='report' value='" . CE_Lib::viewEscape($_REQUEST['report']) . "' />";
        echo "<input type='hidden' name='type' value='" . CE_Lib::viewEscape($_REQUEST['type']) . "' />";

        echo "<div style='margin-left:20px;'>";
        echo "<b>" . $this->user->lang('Select Year Range') . "</b><br/>";
        echo "<select id='passedyear' name='passedyear' onChange='ShowYears(this);'>";

        // If year count is 1, and we don't have any values for this year, we need to make year count 2 so it shows last year as we have values for that.
        if ($yearCount == 1 && !isset($aYears[$thisYear])) {
            $yearCount = 2;
        }

        //Loop the number of years
        for ($x = 0; $x < $yearCount; $x++) {
            $xYear = $thisYear - $x;
            if ($currentYear == $xYear) {
                echo "<option value='" . $xYear . "' selected>" . $xYear . "</option>";
            } else {
                echo "<option value='" . $xYear . "'>" . $xYear . "</option>";
            }
        }

        //Create based on number of years of data available
        for ($x = 1; $x < $yearCount; $x++) {
            $value = $x + 1;
            if ($currentYear == "Last$value") {
                echo "<option value='Last$value' selected>Last $value Years&nbsp;&nbsp;&nbsp;</option>";
            } else {
                echo "<option value='Last$value'>Last $value Years&nbsp;&nbsp;&nbsp;</option>";
            }
        }

        echo "</select></div>";

        echo "</form>";
    }

    /**
     * Function to generate the graph data
     *
     * @return null - direct output
     */
    function GraphData($graphType, $aYears) {


        if (!isset($_REQUEST['passedyear'])) {
            $currentYear = date("Y");
            if (isset($_REQUEST['indashboard'])) {
                $currentYear = "Last2";
            }
        } else {
            $currentYear = $_REQUEST['passedyear'];
        }


        $yearCount = 0;
        $ydata = array();

        foreach ($aYears as $key => $aYear) {
            if ($currentYear == $key) {
                $ydata[] = array($key, $this->ReturnMonthsArray($aYear["arrayForGraph"], $graphType, $key));
            } else {
                if (mb_substr($currentYear, 0, 4) == "Last") {
                    $tYears = mb_substr($currentYear, 4);
                    for ($y = 0; $y < $tYears; $y++) {
                        if ($key == date("Y") - $y) {
                            $ydata[] = array($key, $this->ReturnMonthsArray($aYear["arrayForGraph"], $graphType, $key));
                        }

                    }
                }
            }
            $yearCount++;
        }

        //get default currency symbol
        $currency = new Currency($this->user);
        if (strtoupper($this->settings->get('Default Currency')) == 'EUR') {
            $currencySymbol = 'EUR';
        } elseif (strtoupper($this->settings->get('Default Currency')) == 'GBP') {
            $currencySymbol = 'GBP';
        } elseif (strtoupper($this->settings->get('Default Currency')) == 'JPY') {
            $currencySymbol = 'JPY';
        } else {
            $currencySymbol = $currency->ShowCurrencySymbol($this->settings->get('Default Currency'));
        }

        //building graph data to pass back
        $graph_data = array(
              "xScale" => "time",
              "yScale" => "linear",
              "yType" => "currency",
              "yPre" => $currencySymbol,
              "yFormat" => "addcomma",
              "type" => "line-dotted",
              "main" => array());

        foreach ($ydata as $y) {
            $year_data = array();
            $year_data['className'] = ".report_".$y[0];
            $year_data['data'] = array();

            foreach ($y[1] as $key => $monthtotal) {
                $pretty_month = (date("F",strtotime($key)));
                $pretty_year = (date("Y",strtotime($key)));
                $pretty_total = $currency->format($this->settings->get('Default Currency'),$monthtotal,true);

                $month_data = array();
                if ($monthtotal == "") $monthtotal = "0";

                $month_data["x"] = $key;
                $month_data["y"] = $monthtotal;
                $month_data["tip"] = "<strong>".$pretty_month.", ".$pretty_year."</strong><br/>".$pretty_total;
                $year_data['data'][] = $month_data;
            }

            $graph_data["main"][] = $year_data;
        }

        return json_encode($graph_data);
    }

    //*********************************************
    // Custom Function Definitions for this report
    //*********************************************
    function AverageInvoicePrice($invoices, $price) {
        $currency = new Currency($this->user);
        $average = ((float) $price) / ((float) $invoices);
        return $currency->format($this->settings->get('Default Currency'), $average, true);
    }

    function GetLastMonthDifference($lastmonthamount, $month, $amount) {
        $currency = new Currency($this->user);
        if ($lastmonthamount == "na")
            return "<b>na</b>";

        $retValue = (float) $amount - (float) $lastmonthamount;

        if ($retValue <= 0) {
            $fontcolor = "red";
            $retValue = "(<font color=red>" . $currency->format($this->settings->get('Default Currency'), $retValue, true) . "</font>)";
        } else {
            $retValue = $currency->format($this->settings->get('Default Currency'), $retValue, true);
        }
        return $retValue;
    }

    function GetLastYearDifference($rawYears, $month, $year, $amount) {
        $currency = new Currency($this->user);
        $retValue = "<b>na</b>";
        if (isset($rawYears[$year - 1][$month])) {
            $lastYearPrice = $rawYears[$year - 1][$month];
            $retValue = (float) $amount - (float) $lastYearPrice;
            if ($retValue <= 0) {
                $fontcolor = "red";
                $retValue = "(<font color='$fontcolor'>" . $currency->format($this->settings->get('Default Currency'), $retValue, true) . "</font>)";
            } else {
                $retValue = $currency->format($this->settings->get('Default Currency'), $retValue, true);
            }
        }
        return $retValue;
    }

    function CleanFloat($strFormattedAmount) {
        //remove formatting
        $currency = new Currency($this->user);
        $strFormattedAmount = str_replace(
                array(
            $currency->ShowCurrencySymbol($this->settings->get('Default Currency'), false),
            $currency->getThousandsSeparator($this->settings->get('Default Currency')),
            $currency->getDecimalsSeparator($this->settings->get('Default Currency')),
                ), array(
            "",
            "",
            ".",
                ), $strFormattedAmount
        );

        $strFormattedAmount = strip_tags($strFormattedAmount);
        $strFormattedAmount = str_replace(array("(", ")"), array('', ''), $strFormattedAmount);
        $strFormattedAmount = trim($strFormattedAmount);

        return (float) $strFormattedAmount;
    }

    function ReturnMonthsArray($aMonth, $graphType, $currentYear) {
        //Get all months values
        //Loop through 12 months using long date description and determine
        //if aMonth contains that month.  If not return 0.0

        $aReturn = array();

        for ($x = 1; $x <= 12; $x++) {
            $tstrMonth = date("F", mktime(0, 0, 0, $x + 1, 0, 0));
            $date = date("Y-m-d", mktime(0, 0, 0, $x, 1, $currentYear));
            $valIndex = 3;
            /*
            switch ($graphType) {
                case "year":
                    $valIndex = 6;
                    break;
                case "month":
                    $valIndex = 5;
                    break;
                case "avginvoice":
                    $valIndex = 2;
                    break;
                case "taxcollected":
                    $valIndex = 4;
                    break;
                default:
                    $valIndex = 3;
                    break;
            }*/

            if (!isset($aMonth[$tstrMonth])) {
                //what should we be doing here?
                $aReturn[$date] = "";
            } else if ($this->CleanFloat($aMonth[$tstrMonth][$valIndex]) == "na") {
                $aReturn[$date] = "";
            } else {
                $tempY = (int) $this->CleanFloat($aMonth[$tstrMonth][$valIndex]);
                $aReturn[$date] = $tempY;
            }

            if (($currentYear == date("Y")) && (date("F") == $tstrMonth)) {
                return $aReturn;
            }

        }

        return $aReturn;
    }

    function AllEmpty($yData) {
        if (!is_array($yData))
            return true;

        $tArray = (array_diff($yData, array("", "", "", "", "", "", "", "", "", "", "", "")));

        if (count($tArray) == 0) {
            return true;
        }

        return false;
    }

    //Functions called asynchrounsly
    //Most of this comes from the old Income Graph first developed by Kevin Grubbs
    //Return Formatted HMTL
    function GetInvoiceForMonth() {

        // date format of the paid date
        // uses the PHP date() format syntax
        $strDateFormat = "j M Y";

        // Base currency.. set this to the currency you want to see your report in
        $strBaseCurrency = $this->settings->get('Default Currency');

        include_once 'modules/billing/models/Currency.php';
        $currency = new Currency($this->user);

        $nCurrentYear = $_GET["year"];
        $nCurrentMonth = $_GET["month"];

        //Display Report
        $tMonth = date("F", mktime(0, 0, 0, $nCurrentMonth + 1, 0, 0));

        echo "<H1>" . $tMonth . " " . CE_Lib::viewEscape($nCurrentYear) . "</h1></br>";

        echo "<style>
            table {
                width:100%;
            }
            table, th, td {
                border: 1px solid black;
                border-collapse: collapse;
            }
            th, td {
                padding: 5px;
                text-align: left;
            }
            table#t01 tr:nth-child(even) {
                background-color: #eee;
            }
            table#t01 tr:nth-child(odd) {
               background-color:#fff;
            }
            table#t01 th	{
                background-color: white;
                color: black;
            }
            </style>";

        echo "<table id='t01'>";
        echo "<tr><th>". $this->user->lang('Invoice') . "</th><th>" . $this->user->lang('Customer') . "</th><th>" . $this->user->lang('Amount') . "</th><th>" . $this->user->lang('Tax') . "</th><th>" . $this->user->lang('Due Date') . "</th></tr>";

        //SQL to generate the the result set of the report. Added subtotal field to get tax amount instead of tax rate.
        $reportSQL = "SELECT `invoice`.id, `invoice`.customerid, amount, subtotal, tax, datepaid, currency, `invoice`.tax "
                . "FROM `users` "
                . "INNER JOIN `invoice` ON `users`.`id` = `invoice`.`customerid` "
                . "WHERE `invoice`.status = 1 "
                . "AND YEAR(datepaid) = ? "
                . "AND MONTH(datepaid) = ? "
                . " ORDER BY datepaid";

        $result = $this->db->query($reportSQL, $nCurrentYear, $nCurrentMonth);

        //initialize
        $incomeTotal = 0;
        $monthSubTotal = 0;
        //$prevMonth = "-1";
        $recCount = 0;
        $TotalTax = 0;
        $totalInvoices = 0;
        $monthTax = 0;
        $monthInvoices = 0;

        // Modified in reference to ticket #40938 : To make tax equal to amount - subtotal instead of just displaying tax rate.

        while (list($nInvoiceId, $userID, $dAmount, $dSubtotal, $dTax, $dtDatePaid, $strCurrency, $taxRate) = $result->fetch()) {
            $thisMonth = date("n", strtotime($dtDatePaid));

            // Get list of use name
            $title = '';
            $tUser = new User($userID);
            if ($tUser->IsOrganization()) {
                $lastName = $tUser->getOrganization();
                if (strlen($lastName) > 20) {
                    $dCustomerName = mb_substr($lastName, 0, 20) . "...";
                    $title = "title=\"$lastName\"";
                } else {
                    $dCustomerName = $lastName;
                }
            } else {
                $dCustomerName = $tUser->getFirstName() . " " . $tUser->getLastName();
                $title = "";
            }
            // build a link to the customer profile
            $CustomerLink = "<a href=\"index.php?fuse=clients&controller=userprofile&frmClientID=" . $userID . "&view=profileinvoices\" $title target=blank>" . CE_Lib::viewEscape($dCustomerName) . "</a>";

            // build the Print to PDF Link
            $printInvoiceLink = "<a href=index.php?fuse=billing&frmClientID=" . $userID . "&controller=invoice&view=invoice&profile=1&amp;invoiceid=" . $nInvoiceId . " target=blank>" . $nInvoiceId . "</a>";

            $taxRate = $taxRate / 100;

            // Modified in reference to ticket #40938 : Tax is (amount-subtotal) in all cases.

            $dTax = $dAmount - $dSubtotal;
            $strTaxFormatted = $currency->format($strBaseCurrency, $dTax, true, "NONE", true);
            $strAmountFormatted = $currency->format($strBaseCurrency, $dAmount, true, "NONE", true);
            $monthTax += $dTax;
            $TotalTax += $dTax;
            $incomeTotal += $dAmount;
            $monthSubTotal += $dAmount;
            $aGroup[] = array($printInvoiceLink, $CustomerLink, $strAmountFormatted, $strTaxFormatted, date($strDateFormat, strtotime($dtDatePaid)));
            $recCount++;
            $monthInvoices++;
            $totalInvoices++;

            echo "<tr><td>" . $printInvoiceLink . "</td><td>" . $CustomerLink . "</td><td>" . $strAmountFormatted . "</td><td>" . $strTaxFormatted. "</td><td>" . date($strDateFormat, strtotime($dtDatePaid)) . "</td></tr>";
        }
        echo "</table>";

        if ($monthInvoices > 0){
            echo "</br><table>";
            echo "<tr><th>" . $this->user->lang('Invoices') . "</th><th>" . $this->user->lang('Income (Tax In)') . "</th><th>" . $this->user->lang('Tax Charged') . "</th><th>" . $this->user->lang('Income (Tax Out)') . "</th></tr>";
            echo "<tr><td>" . $totalInvoices . "</td><td>" . $currency->format($strBaseCurrency, $incomeTotal, true, "NONE", true) . "</td><td>" .  $currency->format($strBaseCurrency, $TotalTax, true, "NONE", true) . "</td><td>" .  $currency->format($strBaseCurrency, $incomeTotal - $TotalTax, true, "NONE", true) . "</td></tr>";
            echo "</table>";
        }
    }
}

?>
