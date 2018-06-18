<?php
require_once 'modules/admin/models/ExportPlugin.php';

/**
* @package Plugins
*/
class PluginInvoicesdata extends ExportPlugin
{
    protected $_description = 'This export plugin exports invoice data to a CSV file.';
    protected $_title = 'Invoice Data CSV';

    function getForm()
    {
        $this->view->fields = array();
        $fields = array(    'Invoice ID',
                            'Taxable Amount',
                            'Tax percentage',
                            'Total Amount Before Taxes',
                            'Tax amount',
                            'Total Amount After Taxes',
                            'Balance Due',
                            'Customer ID',
                            'Customer Name',
                            'Customer Last Name',
                            'Organization',
                            'Description',
                            'Bill Date',
                            'Date Paid',
                            'Payment Reference',
                            'Payment Method',
        );
        for ($i = 0; $i < count($fields); $i++) {
            $this->view->fields[$i]['inputName'] = str_replace(array(' ', '_'), array('_', '__'), $fields[$i]);
            $this->view->fields[$i]['fieldName'] = $this->user->lang($fields[$i]);
            $this->view->fields[$i]['checked'] = 'checked';
        }

        return $this->view->render('PluginInvoicesdata.phtml');
    }

    function process($post)
    {
        $fields = array();
        $filter = array();
        foreach ($post as $fieldname => $value) {
            if (strpos($fieldname, 'invoices_field_') === 0) {
                $fields[] = str_replace(array('__', '_'), array('_', ' '), mb_substr($fieldname, 15));
            }else{
                //check to see if any dates were passed
                if($fieldname == 'startdate' && $value != ''){
                    $startDateArray = explode('/', $value);
                    if($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/",$value)){
                        $temp2StartDate = mktime(0, 0, 0, $startDateArray[0], $startDateArray[1], $startDateArray[2]);
                        $filter['startdate'] = $temp2StartDate;
                    }elseif($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/",$value)){
                        $temp2StartDate = mktime(0, 0, 0, $startDateArray[1], $startDateArray[0], $startDateArray[2]);
                        $filter['startdate'] = $temp2StartDate;
                    }
                }

                if($fieldname == 'enddate' && $value != ''){
                    $endDateArray = explode('/', $value);
                    if($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/",$value)){
                        $temp2EndDate = mktime(0, 0, 0, $endDateArray[0], $endDateArray[1], $endDateArray[2]);
                        $filter['enddate'] = $temp2EndDate;
                    }elseif($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/",$value)){
                        $temp2EndDate = mktime(0, 0, 0, $endDateArray[1], $endDateArray[0], $endDateArray[2]);
                        $filter['enddate'] = $temp2EndDate;
                    }
                }

                if($fieldname == 'startdate2' && $value != ''){
                    $startDate2Array = explode('/', $value);
                    if($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/",$value)){
                        $temp2StartDate2 = mktime(0, 0, 0, $startDate2Array[0], $startDate2Array[1], $startDate2Array[2]);
                        $filter['startdate2'] = $temp2StartDate2;
                    }elseif($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/",$value)){
                        $temp2StartDate2 = mktime(0, 0, 0, $startDate2Array[1], $startDate2Array[0], $startDate2Array[2]);
                        $filter['startdate2'] = $temp2StartDate2;
                    }
                }

                if($fieldname == 'enddate2' && $value != ''){
                    $endDate2Array = explode('/', $value);
                    if($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/",$value)){
                        $temp2EndDate2 = mktime(0, 0, 0, $endDate2Array[0], $endDate2Array[1], $endDate2Array[2]);
                        $filter['enddate2'] = $temp2EndDate2;
                    }elseif($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/",$value)){
                        $temp2EndDate2 = mktime(0, 0, 0, $endDate2Array[1], $endDate2Array[0], $endDate2Array[2]);
                        $filter['enddate2'] = $temp2EndDate2;
                    }
                }
            }
        }
        if (!$fields) {
            CE_Lib::redirectPage("index.php?fuse=reports&action=ViewExport");
        }
        $csv = $this->_getInvoicesCSV($fields, $filter, $_POST['invoice_status']);
        CE_Lib::download($csv, $this->user->lang("invoices").'.csv');
    }

    function _getInvoicesCSV($fields, $filter, $status)
    {
        include_once 'modules/billing/models/Currency.php';

        $currency = new Currency($this->user);
        $numFields = count($fields);
        $fieldsMap = array(
            'Invoice ID'                => 'id',
            'Tax percentage'            => 'tax',
            'Total Amount Before Taxes' => 'subtotal',
            'Total Amount After Taxes'  => 'amount',
            'Balance Due'               => 'balance_due',
            'Description'               => 'description',
            'Bill Date'                 => 'billdate',
            'Date Paid'                 => 'datepaid',
            'Payment Reference'         => 'checknum',
            'Payment Method'            => 'pluginused',
        );
        $dbFields = array();
        foreach ($fieldsMap as $human => $machine) {
            if (in_array($human, $fields)) {
                $dbFields[] = $machine;
            }
        }
        if (!in_array('id', $dbFields)){
            $dbFields[] = 'id';
        }
        if (in_array('Tax amount', $fields)) {
            if (!in_array('tax', $dbFields)){
                $dbFields[] = 'tax';
            }
            if (!in_array('subtotal', $dbFields)){
                $dbFields[] = 'subtotal';
            }
            if (!in_array('amount', $dbFields)){
                $dbFields[] = 'amount';
            }
        }
        $dbFields[] = 'customerid';
        $dbFields = implode(", ", $dbFields);
        $query = "SELECT $dbFields FROM invoice";
        $where = false;
        if ($status == 'paid') {
            $query .= " WHERE status = 1";
            $where = true;
        } elseif ($status == 'unpaid') {
            $query .= " WHERE (status = 0 OR status = 5)";
            $where = true;
        }

        if(isset($filter['startdate']) && isset($filter['enddate'])){
            if($where){
                $query .= " AND ";
            }else{
                $query .= " WHERE ";
                $where = true;
            }
            $query .= " ( billdate BETWEEN '".gmdate("Y-m-d 0:0:0", $filter['startdate'])."' AND '".gmdate("Y-m-d 23:59:59", $filter['enddate'])."') ";
        }

        if(isset($filter['startdate2']) && isset($filter['enddate2'])){
            if($where){
                $query .= " AND ";
            }else{
                $query .= " WHERE ";
                $where = true;
            }
            $query .= " ( datepaid BETWEEN '".gmdate("Y-m-d 0:0:0", $filter['startdate2'])."' AND '".gmdate("Y-m-d 23:59:59", $filter['enddate2'])."') ";
        }
        $query .= " ORDER BY id ASC ";

        $result = $this->db->query($query);
        $fieldstranslated = "";
        $numOfTheField = 1;
        foreach ($fields as $field) {
            if ($numOfTheField == $numFields) {
                $fieldstranslated .= '"'.$this->user->lang($field).'"';
            } else {
        	$fieldstranslated .= '"'.$this->user->lang($field).'",';
            }
            $numOfTheField ++;
        }
        $csv = $fieldstranslated. "\n";
        while ($row = $result->fetch()) {
            for ($i = 0; $i < $numFields; $i++) {
                if ($fields[$i] == 'Customer Name') {
                    $query = "SELECT value "
                            ."FROM user_customuserfields uc "
                            .'LEFT JOIN customuserfields c ON uc.customid=c.id '
                            ."WHERE uc.userid = '{$row['customerid']}' AND c.type=?";
                    $result2 = $this->db->query($query, typeFIRSTNAME);
                    $row2 = $result2->fetch();
                    $csv .= "\"{$row2['value']}\"";
                } elseif ($fields[$i] == 'Customer Last Name') {
                    $query = "SELECT value "
                            ."FROM user_customuserfields uc "
                            .'LEFT JOIN customuserfields c ON uc.customid=c.id '
                            ."WHERE uc.userid = '{$row['customerid']}' AND c.type=?";
                    $result2 = $this->db->query($query, typeLASTNAME);
                    $row2 = $result2->fetch();
                    $csv .= "\"{$row2['value']}\"";
                } elseif ($fields[$i] == 'Customer ID') {
                    $csv .= "\"{$row['customerid']}\"";
                } elseif ($fields[$i] == 'Taxable Amount') {
                    $csv .= "\"".$currency->format($this->settings->get('Default Currency'), $this->_getTaxableAmount($row['id']))."\"";
                } elseif ($fields[$i] == 'Total Amount Before Taxes') {
                    $csv .= "\"".$currency->format($this->settings->get('Default Currency'), $row['subtotal'])."\"";
                } elseif ($fields[$i] == 'Tax amount') {
                    $taxAmount = $row['amount'] - $row['subtotal'];
                    $csv .= "\"".$currency->format($this->settings->get('Default Currency'), $taxAmount)."\"";
                } elseif ($fields[$i] == 'Total Amount After Taxes') {
                    $csv .= "\"".$currency->format($this->settings->get('Default Currency'), $row['amount'])."\"";
                } elseif ($fields[$i] == 'Balance Due') {
                    $csv .= "\"".$currency->format($this->settings->get('Default Currency'), $row['balance_due'])."\"";
                } elseif ($fields[$i] == 'Organization') {
                    $query = "SELECT value "
                            ."FROM user_customuserfields uc "
                            .'LEFT JOIN customuserfields c ON uc.customid=c.id '
                            ."WHERE uc.userid = ? AND c.type=?";
                    $result2 = $this->db->query($query, $row['customerid'], typeORGANIZATION);
                    $row2 = $result2->fetch();

                    $organization = $row2['value'];
                    
                    //If organization is empty, use Last Name, First Name
                    if($organization == ''){
                        $query = "SELECT value "
                                ."FROM user_customuserfields uc "
                                .'LEFT JOIN customuserfields c ON uc.customid=c.id '
                                ."WHERE uc.userid = '{$row['customerid']}' AND c.type=?";
                        $result2 = $this->db->query($query, typeLASTNAME);
                        $row2 = $result2->fetch();
                        $organization = $row2['value'];
                    
                        $query = "SELECT value "
                                ."FROM user_customuserfields uc "
                                .'LEFT JOIN customuserfields c ON uc.customid=c.id '
                                ."WHERE uc.userid = '{$row['customerid']}' AND c.type=?";
                        $result2 = $this->db->query($query, typeFIRSTNAME);
                        $row2 = $result2->fetch();
                        if($organization == ''){
                            $organization = $row2['value'];
                        }else{
                            $organization .= ', '.$row2['value'];
                        }
                    }
                    
                    //Repacle &amp; with &, and &#039; with '
                    $organization = str_replace(array("&amp;", "&#039;"), array("&", "'"), $organization);
                    
                    $csv .= "\"{$organization}\"";

                } else {
                    //Use the Invoice Entry Descriptions instead of the Invoice Description
                    if($fields[$i] == 'Description'){
                        $query = "SELECT `description` "
                                ."FROM `invoiceentry` "
                                ."WHERE `invoiceid` = ? ";
                        $result2 = $this->db->query($query, $row['id']);
                        $invoiceEntryDescriptionArray = array();
                        while ($row2 = $result2->fetch()) {
                            $invoiceEntryDescriptionArray[] = $row2['description'];
                        }
                        if(count($invoiceEntryDescriptionArray) > 0){
                            $csv .= '"' . implode(" - ", $invoiceEntryDescriptionArray) . '"';
                        }else{
                            $csv .= '"' . $row[$fieldsMap[$fields[$i]]] . '"';
                        }
                    }else{
                        $csv .= '"' . $row[$fieldsMap[$fields[$i]]] . '"';
                    }
                }
                if ($i == ($numFields - 1)) {
                    $csv .= "\n";
                } else {
                    $csv .= ",";
                }
            }
        }
	$csv = str_replace('Invoice #', $this->user->lang('Invoice #'), $csv);
        return $csv;
    }

    function _getTaxableAmount($invoiceId)
    {
        $total = 0;
        $query = "SELECT price, taxable FROM invoiceentry WHERE invoiceid=?";
        $result = $this->db->query($query, $invoiceId);
        while ($row = $result->fetch()) {
            if ($row['taxable'] == '1') {
                $total += $row['price'];
            }
        }

        return $total;
    }
}

?>
