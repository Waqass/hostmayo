<?php
require_once 'modules/admin/models/ExportPlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;

/* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
require_once 'library/encrypted/Clientexec.php';
/* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */

/**
* @package Plugins
*/
class PluginClientdata extends ExportPlugin
{
    protected $_description = 'This export plugin exports customer profile data to a CSV file.';
    protected $_title = 'Customer Data CSV';

    /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
    protected $_credit_cards = false;
    /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */

    function getForm()
    {
        if (isset($_REQUEST['cc']) && $_REQUEST['cc'] == 1) {
            $this->_credit_cards = true;
        }
        $this->view->fields = array();
        $fields = $this->_getCustomersFields();
        for ($i = 0; $i < count($fields); $i++) {
            $this->view->fields[$i]['inputName'] = str_replace(array(' ', '_'), array('_', '__'), $fields[$i]['name']);
            $this->view->fields[$i]['fieldName'] = $this->user->lang($fields[$i]['name']);
            if ($fields[$i]['isRequired']) {
                $this->view->fields[$i]['checked'] = 'checked';
            } else {
                $this->view->fields[$i]['checked'] = '';
            }

            $this->view->fields[$i]['onaction'] = '';

            /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
            if ($this->_credit_cards) {
                if ($fields[$i]['name'] == 'Credit Card Number') {
                    $this->view->fields[$i]['onaction'] = 'onClick="rerquestPassphrase()"';
                }
            }
            /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
        }

        //let's get customer groups
        require_once 'library/CE/NE_GroupsGateway.php';
        $groupsGateway = new NE_GroupsGateway();
        $groupsIt = $groupsGateway->getCustomerGroups();
        $this->view->groups = array();
        if ($groupsIt->getNumItems()) {
            $group = array(
                'groupValue'    => 'all',
                'groupLabel'    => '-- ' . $this->user->lang('any') . ' --'
            );
            $this->view->groups[] = $group;
            $group = array(
                'groupValue'    => 0,
                'groupLabel'    => '-- ' . $this->user->lang('none') . ' --'
            );
            $this->view->groups[] = $group;
            while ($group = $groupsIt->fetch()) {
                if ($group->isAdmin() || $group->isSuperAdmin() || $group->getId() == 1) {
                    continue;
                }
                $group = array(
                    'groupValue'    => $group->getId(),
                    'groupLabel'    => $group->getName()
                );
                $this->view->groups[] = $group;
            }
        }

        return $this->view->render('PluginClientdata.phtml');
    }

    function _getCustomersFields()
    {
        $query = "SELECT name, isRequired FROM customuserfields WHERE (inSignup = 1 OR inSettings = 1) ORDER BY myOrder";
        $result = $this->db->query($query);
        $arrReturn = array(
            array(
                'name'       => 'id',
                'isRequired' => 1
            ),
            array(
                'name'       => 'Status',
                'isRequired' => 1
            ),
            array(
                'name'       => 'Date Created',
                'isRequired' => 1
            )
        );
        while ($row = $result->fetch()) {
            if ( $row['name'] == 'Full Name' || $row['name'] == 'Full Address') {
                continue;
            }


            $arrReturn[] = array(
                'name'       => $row['name'],
                'isRequired' => $row['isRequired']
            );
        }
        /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
        if ($this->_credit_cards) {
            $arrReturn[] = array(
                'name'       => 'Credit Card Number',
                'isRequired' => 0
            );
            $arrReturn[] = array(
                'name'       => 'Expiration Month',
                'isRequired' => 0
            );
            $arrReturn[] = array(
                'name'       => 'Expiration Year',
                'isRequired' => 0
            );
        }
        /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
        return $arrReturn;
    }

    function process($post)
    {
        if(isset($post['passphrase']) && $post['passphrase'] != ''){
            $this->_credit_cards = true;
        }
        $fields = array();
        $filter = array();
        foreach ($post as $fieldname => $value) {
            if (strpos($fieldname, 'clients_field_') === 0) {
                $fields[] = str_replace(array('__', '_'), array('_', ' '), mb_substr($fieldname, 14));
            } else {
                //check to see if any dates were passed
                if ($fieldname == 'startdate' && $value != '') {
                    $startDateArray = explode('/', $value);
                    if ($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/",$value)) {
                        $temp2StartDate = mktime(0, 0, 0, $startDateArray[0], $startDateArray[1], $startDateArray[2]);
                        $filter['startdate'] = $temp2StartDate;
                    } elseif ($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/",$value)) {
                        $temp2StartDate = mktime(0, 0, 0, $startDateArray[1], $startDateArray[0], $startDateArray[2]);
                        $filter['startdate'] = $temp2StartDate;
                    }
                }

                if ($fieldname == 'enddate' && $value != '') {
                    $endDateArray = explode('/', $value);
                    if ($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/",$value)) {
                        $temp2EndDate = mktime(0, 0, 0, $endDateArray[0], $endDateArray[1], $endDateArray[2]);
                        $filter['enddate'] = $temp2EndDate;
                    } elseif ($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/",$value)) {
                        $temp2EndDate = mktime(0, 0, 0, $endDateArray[1], $endDateArray[0], $endDateArray[2]);
                        $filter['enddate'] = $temp2EndDate;
                    }
                }

                if ($fieldname == 'dropdown_customerGroup' && $value !== 'all') {
                    $filter['customergroup'] = $value;
                }
            }
        }
        if (!$fields) {
            CE_Lib::redirectPage("index.php?fuse=reports&view=ViewExport");
        }
        $csv = $this->_getCustomersCSV($fields, $filter, $post['passphrase']);
        CE_Lib::download($csv, $this->user->lang("customers").'.csv');
    }

    function _getCustomersCSV($fields, $filter, $passphrase)
    {
        $numFields = count($fields);
        $fieldsFiltered = array();
        $fieldstranslated = "";
        $numOfTheField = 1;
        foreach ($fields as $field) {
            $fieldsFiltered[] = $this->db->escape_string($field);
            if ($numOfTheField == $numFields) {
                $fieldstranslated .= '"'.$this->user->lang($field).'"';
            } else {
                $fieldstranslated .= '"'.$this->user->lang($field).'",';
            }
            $numOfTheField ++;
        }
        $fields_str = implode("', '", $fieldsFiltered);
        $csv = $fieldstranslated. "\n";
        $users = array();

        if (isset($filter['customergroup'])) {
            $user_groups_join = 'LEFT JOIN `user_groups` ug ON u.`id` = ug.`user_id` ';
            $user_groups_where = 'AND IFNULL(ug.`group_id`, 0) = '.$filter['customergroup'].' ';
        } else {
            $user_groups_join = '';
            $user_groups_where = '';
        }

        $query = "SELECT u.`id`, u.`status`, u.`dateActivated`, cu.`name`, u_cu.`value` "
            ."FROM `users` u ".$user_groups_join
            .", `user_customuserfields` u_cu, `customuserfields` cu "
            ."WHERE u.`groupid` = 1 AND u.`id` = u_cu.`userid` AND u_cu.`customid` = cu.`id` AND cu.`name` IN('$fields_str') "
            .$user_groups_where;
        if (isset($filter['startdate']) && isset($filter['enddate'])) {
            $query .= "AND ( u.`dateActivated` BETWEEN '".gmdate("Y-m-d 0:0:0", $filter['startdate'])."' AND '".gmdate("Y-m-d 23:59:59", $filter['enddate'])."') ";
        }
        $query .= " ORDER BY u.`id` ASC ";
        $result = $this->db->query($query);

        // special cases
        $showId = in_array('id', $fields);
        $showStatus = in_array('Status', $fields);
        $showDateCreated = in_array('Date Created', $fields);
        /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
        if ($this->_credit_cards) {
            $showCreditCardNumber = in_array('Credit Card Number', $fields);
            $showExpirationMonth = in_array('Expiration Month', $fields);
            $showExpirationYear = in_array('Expiration Year', $fields);
        }
        /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */

        while ($row = $result->fetch()) {
            if ($showId) {
                $users[$row['id']]['id'] = $row['id'];
            }
            if ($showStatus) {
                $status = $this->user->lang(StatusAliasGateway::getInstance($this->user)->getUserStatus($row['status'])->name);
                $users[$row['id']]['Status'] = $status;
            }
            if ($showDateCreated) {
                $users[$row['id']]['Date Created'] = CE_Lib::db_to_form($row['dateActivated'], $this->settings->get('Date Format'), '/');
            }
            /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */
            if ($this->_credit_cards) {
                if ($showCreditCardNumber || $showExpirationMonth || $showExpirationYear) {
                    $tempUser = new user($row['id']);

                    if ($showCreditCardNumber) {
                        if (isset($passphrase)) {
                            //Check Passphrase
                            if (Clientexec::getPassPhraseHash($this->settings) == md5($passphrase)) {
                                if ($tempUser->isPassphrased()) {
                                    $cc_num = $tempUser->getCreditCardInfo($passphrase);
                                } else {
                                    $cc_num = $tempUser->getCreditCardInfo();
                                }
                                $users[$row['id']]['Credit Card Number'] = trim($cc_num);
                            } else {
                                $users[$row['id']]['Credit Card Number'] = 'Invalid Passphrase';
                            }
                        } else {
                            $users[$row['id']]['Credit Card Number'] = '';
                        }
                    }
                    if ($showExpirationMonth) {
                        $users[$row['id']]['Expiration Month'] = $tempUser->getCCMonth();
                    }
                    if ($showExpirationYear) {
                        $users[$row['id']]['Expiration Year'] = $tempUser->getCCYear();
                    }
                }
            }
            /* THIS CODE IS TO EXPORT CREDIT CARDS, BUT WE WILL DECIDE LATER HOW TO DO IT */

            $users[$row['id']][$row['name']] = $row['value'];
        }

        foreach ($users as $userItem) {
            for ($i = 0; $i < $numFields; $i++) {
                // I tried more elegant ways, but this one assures field order :-P
                $csv .= "\"";
                if (isset($fields[$i])) {
                    if (isset($userItem[$fields[$i]])) {
                        $csv .= $userItem[$fields[$i]];
                    }
                }
                $csv .= "\"";
                if ($i == ($numFields - 1)) {
                    $csv .= "\n";
                } else {
                    $csv .= ",";
                }
            }
        }
        return $csv;
    }
}

?>
