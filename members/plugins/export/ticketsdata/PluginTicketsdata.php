<?php
require_once 'modules/admin/models/ExportPlugin.php';

/**
* @package Plugins
*/
class PluginTicketsdata extends ExportPlugin
{
    protected $_description = 'This export plugin exports support ticket data to a CSV file.';
    protected $_title = 'Support Tickets Data CSV';

    function getForm()
    {
        $this->view->fields = array();
        $fields = array('Ticket Number', 'Subject', 'Customer ID', 'Customer Name', 'Customer Last Name', 'Assigned To', 'Ticket Type', 'Time Elapsed (seconds)', 'Status', 'Priority');
        for ($i = 0; $i < count($fields); $i++) {
            $this->view->fields[$i]['inputName'] = str_replace(array(' ', '_'), array('_', '__'), $fields[$i]);
            $this->view->fields[$i]['fieldName'] = $this->user->lang($fields[$i]);
            $this->view->fields[$i]['checked'] = 'checked';
        }

        return $this->view->render('PluginTicketsdata.phtml');
    }

    function process($post)
    {
        $fields = array();
        foreach ($post as $fieldname => $value) {
            if (strpos($fieldname, 'tickets_field_') === 0) {
                $fields[] = str_replace(array('__', '_'), array('_', ' '), mb_substr($fieldname, 15));
            }
        }
        if (!$fields) {
            CE_Lib::redirectPage("index.php?fuse=reports&view=ViewExport");
        }
        $csv = $this->_getTicketsCSV($fields, $_POST['tickets_status']);
        CE_Lib::download($csv, $this->user->lang("troubletickets").'.csv');
    }

    function _getTicketsCSV($fields, $status)
    {
        require_once 'modules/admin/models/StatusAliasGateway.php';

        $numFields = count($fields);
        $fieldsMap = array(
            'Ticket Number'             => 'id',
            'Amount'                    => 'amount',
            'Description'               => 'description',
            'Bill Date'                 => 'billdate',
            'Date Paid'                 => 'datepaid',
            'Subject'                   => 'subject',
            'Assigned To'               => 'assignedtoid',
            'Ticket Type'               => 'messagetype',
            'Time Elapsed (seconds)'    => 'datesubmitted',
            'Status'                    => 'status',
            'Priority'                  => 'priority'
        );
        $dbFields = array();
        foreach ($fieldsMap as $human => $machine) {
            if (in_array($human, $fields)) {
                $dbFields[] = $machine;
            }
        }
        $dbFields[] = 'userid';
        $dbFields = implode(", ", $dbFields);
        $query = "SELECT $dbFields FROM troubleticket";
        $statusClosed = StatusAliasGateway::ticketClosedAliases($this->user);
        if ($status == 'opened') {
            $query .= " WHERE status NOT IN (".implode(', ', $statusClosed).")";
        } elseif ($status == 'closed') {
            $query .= " WHERE status IN (".implode(', ', $statusClosed).") ";
        }
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
                            ."WHERE uc.userid = '{$row['userid']}' AND c.type=?";
                    $result2 = $this->db->query($query, typeFIRSTNAME);
                    $row2 = $result2->fetch();
                    $csv .= "\"{$row2['value']}\"";
                } elseif ($fields[$i] == 'Customer Last Name') {
                    $query = "SELECT value "
                            ."FROM user_customuserfields uc "
                            .'LEFT JOIN customuserfields c ON uc.customid=c.id '
                            ."WHERE uc.userid = '{$row['userid']}' AND c.type=?";
                    $result2 = $this->db->query($query, typeLASTNAME);
                    $row2 = $result2->fetch();
                    $csv .= "\"{$row2['value']}\"";
                } elseif ($fields[$i] == 'Customer ID') {
                    $csv .= "\"{$row['userid']}\"";
                } elseif ($fields[$i] == 'Assigned To') {
                    $query = "SELECT CONCAT(firstname,' ',lastname) as value FROM users WHERE id = '{$row['assignedtoid']}'";
                    $result2 = $this->db->query($query);
                    $row2 = $result2->fetch();
                    $csv .= "\"{$row2['value']}\"";
                } elseif ($fields[$i] == 'Time Elapsed (seconds)') {
                    $query = "SELECT datesubmitted FROM troubleticket WHERE id = '{$row['id']}'";
                    $result2 = $this->db->query($query);
                    $row2 = $result2->fetch();
                    $TimeElapsed = ((integer)strtotime(date('Y-m-d H:i:s')) - (int)strtotime($row['datesubmitted']));
                    $csv .= "\"{$TimeElapsed}\"";
                }else {
                    $csv .= '"' . $row[$fieldsMap[$fields[$i]]] . '"';
                }
                if ($i == ($numFields - 1)) {
                    $csv .= "\n";
                } else {
                    $csv .= ",";
                }
            }
        }
        $csv = str_replace('-1', $this->user->lang('closed'), $csv);
    	$csv = str_replace('Ticket #', $this->user->lang('Ticket #'), $csv);
        return $csv;
    }
}

?>
