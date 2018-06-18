<?php
/**
 * Exports data from a WHMCS installation to ClientExec format
 *
 */

error_reporting(0);

class Database
{
    protected $_db;
    protected $_numRows;

    public function closeConnection ()
    {
        if (!mysqli_close($this->_db)) {
            throw new Exception('Unable to close connection.');
        }
    }

    public function connect ($hostname, $username, $password, $database)
    {
        if (!($this->_db = mysqli_connect($hostname, $username, $password, $database))) {
            throw new Exception('Unable to connect to database.');
        }
    }

    public function getDb ()
    {
        return $this->_db;
    }

    public function getNumRows ()
    {
        return $this->_numRows;
    }

    public function packagePriceByCycle ($row)
    {
        switch($row['billingcycle']) {
            case 'Free Account':
            case 'One Time':
                $price = 0;
                break;
            case 'Monthly':
                $price = $row['monthly'];
                break;
            case 'Quarterly':
                $price = $row['quarterly'];
                break;
            case 'Semi-Annually':
                $price = $row['semiannually'];
                break;
            case 'Annually':
                $price = $row['annually'];
                break;
            case 'Biennially':
            case 'Triennially':
                $price = $row['biennially'];
                break;
            default:
                if (empty($row['billingcycle'])) {
                    throw new Exception('Unable to get the price by cycle.');
                } else {
                    throw new Exception("Unable to get the price by cycle '{$row['billingcycle']}'.");
                }
        }

        return $price;
    }

    public function query ($query)
    {
        $result = mysqli_query($this->getDb(), $query) or die(mysqli_error($this->getDb()));

        if ($result === false) {
            throw new Exception('Unable to execute the query. ' . mysqli_error($this->getDb()));
        } elseif ($result === true) {
            $affectedRows = mysqli_affected_rows($this->getDb());

            return $affectedRows;
        } elseif ($result) {
            $rows = array();
            $this->_numRows = mysqli_num_rows($result);

            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            return $rows;
        } else {
            throw new Exception('Unexpected return from query.');
        }
    }

    public function setDb ($value)
    {
        $this->_db = $value;
    }
}

abstract class Exporter extends Database
{
    public $encryptionHash = '';

    /**
     * Header columns names.
     */
    protected $_columns = array(
        'domains' => array('clientid', 'activateddate', 'domainname', 'recurring', 'registrationperiod', 'status', 'nextduedate', 'registrar'),
        'hosting' => array('id', 'clientid', 'username', 'plan', 'regdate', 'domain', 'status', 'nextinvoicedate', 'paymentterm', 'price', 'server', 'coupon'),
        'hosting_addons' => array('clientid', 'packageid', 'addonid', 'addonoptionid', 'nextinvoicedate', 'paymentterm', 'price', 'name'),
        'invoices' => array('id', 'clientid', 'amount', 'datedue', 'datepaid', 'description', 'detail', 'tax', 'subtotal', 'status'),
        'invoices_entries' => array('id', 'clientid', 'invoiceid', 'relid', 'amount', 'tax', 'datedue', 'detail', 'description'),
        'packages' => array('id', 'name', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennial', 'setup', 'description', 'packagetype', 'tax'),
        'packages_addons' => array('id', 'name', 'description'),
        'packages_addons_options' => array('id', 'packageaddonid', 'detail', 'monthly', 'quarterly', 'semiannually', 'annually', 'biannually', 'setup'),
        'packages_groups' => array('id', 'description', 'insignup', 'name', 'type', 'canDelete', 'groupOrder', 'style', 'advanced' ),
        'servers' => array('id', 'name', 'hostname', 'ipaddress', 'assignedips', 'statusaddress', 'maxaccounts', 'type', 'username', 'password', 'accesshash', 'secure', 'nameserver1', 'nameserver1ip', 'nameserver2', 'nameserver2ip', 'nameserver3', 'nameserver3ip', 'nameserver4', 'nameserver4ip'),
        'users' => array('id', 'firstname', 'lastname', 'address', 'email', 'city', 'state', 'zip', 'phone', 'country', 'company', 'status', 'language', 'cardnum', 'expdate' ),
        'departments' => array ('id', 'name' ),
        'tickets' => array ( 'id', 'userid', 'date', 'title', 'message', 'status', 'urgency', 'name', 'email' ),
        'ticket_logs' => array ( 'id', 'tid', 'userid', 'date', 'message', 'email', 'is_staff'),
        'coupons' => array ('id', 'code', 'type', 'recurring', 'value', 'appliesto', 'startdate', 'expirationdate'),
        'staff' => array('id', 'firstname', 'lastname', 'email', 'status')
    );

    protected $_columnsBuffer = array();

    protected $_filename;

    protected $_isUtf8 = false;

    /**
     * Lines buffer. For performance propulse.
     */
    protected $_linesBuffer = array();

    /**
     * This controls the amount of lines needed to be processed before writting to the file.
     */
    protected $_linesBufferLimit = 100;

    protected $_mysqlBufferLimit = 50;

    /**
     * Zlib file pointer
     */
    protected $_zp;

    function __construct ()
    {
        $this->_setupFile();
    }

    protected function _addColumn ($value, $skipEscaping = false)
    {
        $value = str_replace("\r\n", "\n", $value);

        if (!$this->_isUtf8) {
            $value = utf8_encode($value);
        }

        if (!$skipEscaping) {
            $value = json_encode($value);
            $value = str_replace(',', '\c', $value);
        }

        $this->_columnsBuffer[] = $value;
    }

    protected function _addHeader ($section)
    {
        if (!array_key_exists($section, $this->_columns)) {
            throw new Exception("Invalid section '{$section}'.");
        }

        $this->_addLine("; {$section}");

        foreach ($this->_columns[$section] as $column) {
            $this->_addColumn($column, true);
        }

        $this->_addLine();
    }

    protected function _addLine ($lineContents = null)
    {
        if ($lineContents === null) {
            if (count($this->_columnsBuffer) < 1) {
                throw new Exception('Cannot add a line without columns.');
            }

            $lineContents = implode(',', $this->_columnsBuffer);
            $this->_columnsBuffer = array();
        }

        $lineContents = trim($lineContents);

        if (empty($lineContents)) {
            throw new Exception('Cannot add an empty line to the file.');
        }

        $lineContents .= "\n";

        $this->_linesBuffer[] = $lineContents;

        if (count($this->_linesBuffer) >= $this->_linesBufferLimit) {
            $this->_clearLinesBuffer();
        }
    }

    protected function _clearLinesBuffer ()
    {
        foreach ($this->_linesBuffer as $line) {
            if (!gzwrite($this->_zp, $line)) {
                throw new Exception("Unable to write to the file '{$this->_filename}'.");
            }
        }

        $this->_linesBuffer = array();
    }

    protected function _setupFile ()
    {
        $this->_filename = tempnam(sys_get_temp_dir(), 'PHP');

        if (!file_exists($this->_filename)) {
            // Attempt to create the file
            if (!touch($this->_filename)) {
                throw new Exception('Unable to create the temporary file.');
            }
        }

        if (!is_writable($this->_filename)) {
            // Attempt to give write permissions
            if (!chmod($this->_filename, 0666)) {
                throw new Exception('Unable to set temporary file permissions.');
            }
        }
    }

    public function deleteFile ()
    {
        @unlink($this->_filename);
    }

    public function downloadFile ()
    {
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="clientexec.csv.gz"');
        header('Content-Length: ' . filesize($this->_filename));
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        echo file_get_contents($this->_filename);
    }

    public function export ()
    {
        if (!($this->_zp = gzopen($this->_filename, 'w9'))) {
            throw new Exception("Unable to open the file '{$this->_filename}'.");
        }

        $this->_addHeader('staff');
        $this->exportStaff();
        $this->_addHeader('users');
        $this->exportUsers();
        $this->_addHeader('servers');
        $this->exportServers();
        $this->_addHeader('packages_groups');
        $this->exportPackagesGroups();
        $this->_addHeader('packages');
        $this->exportPackages();
        $this->_addHeader('packages_addons');
        $this->exportPackagesAddons();
        $this->_addHeader('packages_addons_options');
        $this->exportPackagesAddonsOptions();
        $this->_addHeader('coupons');
        $this->exportCoupons();
        $this->_addHeader('hosting');
        $this->exportHosting();
        $this->_addHeader('invoices');
        $this->exportInvoices();
        $this->_addHeader('invoices_entries');
        $this->exportInvoicesEntries();
        $this->_addHeader('domains');
        $this->exportDomains();
        $this->_addHeader('hosting_addons');
        $this->exportHostingAddons();
        $this->_addHeader('departments');
        $this->exportDepartments();
        $this->_addHeader('tickets');
        $this->exportTickets();
        $this->_addHeader('ticket_logs');
        $this->exportTicketLogs();

        $this->_clearLinesBuffer();

        if (!gzclose($this->_zp)) {
            throw new Exception("Unable to close the file '{$this->_filename}'.");
        }
    }

    abstract public function exportDomains ();
    abstract public function exportHosting ();
    abstract public function exportHostingAddons ();
    abstract public function exportInvoices ();
    abstract public function exportInvoicesEntries ();
    abstract public function exportPackages ();
    abstract public function exportPackagesAddons ();
    abstract public function exportPackagesAddonsOptions ();
    abstract public function exportPackagesGroups ();
    abstract public function exportUsers ();
    abstract public function exportServers ();
    abstract public function exportDepartments ();
    abstract public function exportTickets ();
    abstract public function exportTicketLogs ();
    abstract public function exportCoupons();
    abstract public function exportStaff();

    public function getFileContentsUncompressed ()
    {
        $fileContents = '';

        $zd = gzopen($this->_filename, 'r');

        while (!gzeof($zd)) {
            $buffer = gzgets($zd);
            $fileContents .= $buffer;
        }

        gzclose($zd);

        return $fileContents;
    }
}

class WHMCS_Exporter extends Exporter
{
    protected $_isUtf8 = false;

    public function cycle2ce ($cycle)
    {
        switch($cycle) {
            case 'Free Account':
            case 'One Time':
                $ce_cycle = 0;
                break;
            case 'Monthly':
                $ce_cycle = 1;
                break;
            case 'Quarterly':
                $ce_cycle = 3;
                break;
            case 'Semi-Annually':
                $ce_cycle = 6;
                break;
            case 'Annually':
                $ce_cycle = 12;
                break;
            case 'Biennially':
            case 'Triennially':
                $ce_cycle = 24;
                break;
            default:
                if (empty($cycle)) {
                    throw new Exception('Unable to convert the cycle.');
                } else {
                    throw new Exception("Unable to convert the cycle '{$cycle}'.");
                }
        }

        return $ce_cycle;
    }

    public function exportDomains ()
    {
        $offset = 0;

        do {
            $query = "SELECT userid, registrationdate, domain, recurringamount, registrationperiod, status, nextinvoicedate, registrar FROM tbldomains LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $row['registrationdate'] = date('Y-m-d', strtotime($row['registrationdate']));
                $row['status'] = $this->packageStatus2ce($row['status']);
                $row['nextinvoicedate'] = date('Y-m-d', strtotime($row['nextinvoicedate']));
                $this->_addColumn($row['userid']);
                $this->_addColumn($row['registrationdate']);
                $this->_addColumn($row['domain']);
                $this->_addColumn($row['recurringamount']);
                $this->_addColumn($row['registrationperiod']);
                $this->_addColumn($row['status']);
                $this->_addColumn($row['nextinvoicedate']);
                $this->_addColumn($row['registrar']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportCoupons()
    {
        $offset = 0;

        do {
            $query = "SELECT id, code, type, recurring, value, appliesto, startdate, expirationdate FROM tblpromotions LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['code']);
                $this->_addColumn($row['type']);
                $this->_addColumn($row['recurring']);
                $this->_addColumn($row['value']);
                $this->_addColumn($row['appliesto']);
                $this->_addColumn($row['startdate']);
                $this->_addColumn($row['expirationdate']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }


    public function exportHosting ()
    {
        $offset = 0;

        do {
            $query = "SELECT h.id, h.userid, h.username, h.packageid, h.regdate, h.domain, h.domainstatus, h.nextinvoicedate, h.billingcycle, h.server, pri.monthly, pri.quarterly, pri.semiannually, pri.annually, pri.biennially, pri.triennially, h.promoid FROM tblhosting h, tblpricing pri WHERE pri.type='product' AND pri.relid=h.packageid LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $domainstatus = $this->packageStatus2ce($row['domainstatus']);
                $billingcycle = $this->cycle2ce($row['billingcycle']);
                $packagePrice = $this->packagePriceByCycle($row);
                $this->_addColumn($row['id']);
                $this->_addColumn($row['userid']);
                $this->_addColumn($row['username']);
                $this->_addColumn($row['packageid']);
                $this->_addColumn($row['regdate']);
                $this->_addColumn($row['domain']);
                $this->_addColumn($domainstatus);
                $this->_addColumn($row['nextinvoicedate']);
                $this->_addColumn($billingcycle);
                $this->_addColumn($packagePrice);
                $this->_addColumn($row['server']);
                $this->_addColumn($row['promoid']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportHostingAddons ()
    {
        $offset = 0;

        do {
            $query = "SELECT pkg.userid, opt.relid, opt.configid, opt.optionid, pkg.billingcycle, pkg.nextinvoicedate, pri.monthly, pri.quarterly, pri.semiannually, pri.annually, pri.biennially, pri.triennially, optsub.optionname FROM tblhosting pkg, tblhostingconfigoptions opt, tblpricing pri, tblproductconfigoptionssub optsub where pkg.id = opt.relid AND pri.type='configoptions' AND pri.relid=opt.id AND optsub.id= opt.optionid LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $billingcycle = $this->cycle2ce($row['billingcycle']);
                $packagePrice = $this->packagePriceByCycle($row);
                $this->_addColumn($row['userid']);
                $this->_addColumn($row['relid']);
                $this->_addColumn($row['configid']);
                $this->_addColumn($row['optionid']);
                $this->_addColumn($row['nextinvoicedate']);
                $this->_addColumn($billingcycle);
                $this->_addColumn($packagePrice);
                $this->_addColumn($row['optionname']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportInvoices ()
    {
        $offset = 0;

        do {
            $query = "SELECT id, userid, total, duedate, datepaid, notes, tax, subtotal, status FROM tblinvoices LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $row['duedate'] = date('Y-m-d', strtotime($row['duedate']));
                $row['notes'] = str_replace('"', '\"', $row['notes']);
                $row['status'] = $this->packageStatus2ce($row['status']);

                if ($row['datepaid'] != 0) {
                    $row['datepaid'] = date('Y-m-d', strtotime($row['datepaid']));
                } else {
                    $row['datepaid'] = 0;
                }

                $this->_addColumn($row['id']);
                $this->_addColumn($row['userid']);
                $this->_addColumn($row['total']);
                $this->_addColumn($row['duedate']);
                $this->_addColumn($row['datepaid']);
                $this->_addColumn($row['Imported Invoice']);
                $this->_addColumn($row['notes']);
                $this->_addColumn($row['tax']);
                $this->_addColumn($row['subtotal']);
                $this->_addColumn($row['status']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportInvoicesEntries ()
    {
        $offset = 0;

        do {
            $query = "SELECT ie.id, ie.userid, ie.invoiceid, ie.relid, ie.amount, ie.taxed, ie.notes, ie.description, i.duedate FROM tblinvoiceitems ie, tblinvoices i WHERE ie.invoiceid=i.id LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $row['duedate'] = date('Y-m-d', strtotime($row['duedate']));
                $this->_addColumn($row['id']);
                $this->_addColumn($row['userid']);
                $this->_addColumn($row['invoiceid']);
                $this->_addColumn($row['relid']);
                $this->_addColumn($row['amount']);
                $this->_addColumn($row['taxed']);
                $this->_addColumn($row['duedate']);
                $this->_addColumn($row['notes']);
                $this->_addColumn($row['description']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportPackages ()
    {
        $offset = 0;

        do {
            $query = "SELECT pro.id, pro.gid, pro.name, pri.monthly, pri.quarterly, pri.semiannually, pri.annually, pri.biennially, pro.description, pro.type, pro.tax, pri.msetupfee FROM tblproducts pro, tblpricing pri WHERE pri.currency =1 AND pro.id = pri.relid AND pri.type = 'product' LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $row['description'] = str_replace('"', '\"', $row['description']);
                $this->_addColumn($row['id']);
                $this->_addColumn($row['name']);
                $this->_addColumn($row['monthly']);
                $this->_addColumn($row['quarterly']);
                $this->_addColumn($row['semiannually']);
                $this->_addColumn($row['annually']);
                $this->_addColumn($row['biennially']);
                $this->_addColumn($row['msetupfee']);
                $this->_addColumn($row['description']);
                $this->_addColumn($row['gid']);
                $this->_addColumn($row['tax']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportPackagesAddons ()
    {
        $offset = 0;

        do {
            $query = "SELECT id, optionname FROM tblproductconfigoptions LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['optionname']);
                $this->_addColumn($row['optionname']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportPackagesAddonsOptions ()
    {
        $offset = 0;

        do {
            $query = "SELECT optsub.id, optsub.configid, optsub.optionname, pri.monthly, pri.quarterly, pri.semiannually, pri.annually, pri.biennially, pri.msetupfee FROM tblproductconfigoptionssub optsub, tblpricing pri WHERE pri.relid=optsub.id AND pri.type='configoptions' LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['configid']);
                $this->_addColumn($row['optionname']);
                $this->_addColumn($row['monthly']);
                $this->_addColumn($row['quarterly']);
                $this->_addColumn($row['semiannually']);
                $this->_addColumn($row['annually']);
                $this->_addColumn($row['biennially']);
                $this->_addColumn($row['msetupfee']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportPackagesGroups ()
    {
        $offset = 0;

        do {
            $query = "SELECT id, name FROM tblproductgroups LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn('');
                $this->_addColumn(0);
                $this->_addColumn($row['name']);
                $this->_addColumn(1);
                $this->_addColumn(1);
                $this->_addColumn(1);
                $this->_addColumn('default');
                $this->_addColumn('');
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportStaff()
    {
        $offset = 0;
        do {
            $query = "SELECT * FROM `tbladmins` LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['firstname']);
                $this->_addColumn($row['lastname']);
                $this->_addColumn($row['email']);
                $this->_addColumn(1);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportUsers ()
    {
        $offset = 0;
        $hash = $this->encryptionHash;
        do {
            $query = "SELECT *, AES_DECRYPT(cardnum,md5(CONCAT('{$hash}', id ))) as realcardnum, AES_DECRYPT(expdate,md5(CONCAT('{$hash}', id ))) as realcardexp FROM tblclients LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);
            foreach ($result as $row) {
                $row['status'] = $this->userStatus2ce($row['status']);

                $this->_addColumn($row['id']);
                $this->_addColumn($row['firstname']);
                $this->_addColumn($row['lastname']);
                $this->_addColumn($row['address1'] . $row['address2']);
                $this->_addColumn($row['email']);
                $this->_addColumn($row['city']);
                $this->_addColumn($row['state']);
                $this->_addColumn($row['postcode']);
                $this->_addColumn($row['phonenumber']);
                $this->_addColumn($row['country']);
                $this->_addColumn($row['companyname']);
                $this->_addColumn($row['status']);
                $this->_addColumn('English');
                $this->_addColumn($row['realcardnum']);
                $this->_addColumn($row['realcardexp']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportServers ()
    {
        $offset = 0;

        do {
            $query = "SELECT * FROM tblservers LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['name']);
                $this->_addColumn($row['hostname']);
                $this->_addColumn($row['ipaddress']);
                $this->_addColumn($row['assignedips']);
                $this->_addColumn($row['statusaddress']);
                $this->_addColumn($row['maxaccounts']);
                $this->_addColumn($row['type']);
                $this->_addColumn($row['username']);
                $this->_addColumn($row['password']);
                $this->_addColumn($row['accesshash']);
                $this->_addColumn($row['secure']);
                $this->_addColumn($row['nameserver1']);
                $this->_addColumn($row['nameserver1ip']);
                $this->_addColumn($row['nameserver2']);
                $this->_addColumn($row['nameserver2ip']);
                $this->_addColumn($row['nameserver3']);
                $this->_addColumn($row['nameserver3ip']);
                $this->_addColumn($row['nameserver4']);
                $this->_addColumn($row['nameserver4ip']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportDepartments()
    {
        $offset = 0;
        do {
            $query = "SELECT * FROM tblticketdepartments LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['name']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportTickets()
    {
        $offset = 0;
        do {
            $query = "SELECT * FROM tbltickets LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['userid']);
                $this->_addColumn($row['date']);
                $this->_addColumn($row['title']);
                $this->_addColumn($row['message']);
                $this->_addColumn($this->ticketStatus2ce($row['status']));
                $this->_addColumn($this->ticketUrgency2ce($row['urgency']));
                $this->_addColumn($row['name']);
                $this->_addColumn($row['email']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportTicketLogs()
    {
        $offset = 0;
        do {
            $query = "SELECT * FROM tblticketreplies LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['tid']);
                $this->_addColumn($row['userid']);
                $this->_addColumn($row['date']);
                $this->_addColumn($row['message']);
                $this->_addColumn($row['email']);
                $this->_addColumn('');
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function ticketUrgency2ce($urgency)
    {
        switch ($urgency) {
            case 'Low':
                $priority = 3;
                break;

            case 'Medium':
                $priority = 2;
                break;

            case 'High':
            case 'Critical':
                $priority = 1;
                break;

        }
        return $priority;
    }

    public function ticketStatus2ce($status)
    {
        switch ($status) {
            case 'Open':
                $ceStatus = 1;
                break;

            case 'Closed':
                $ceStatus = -1;
                break;

            case 'Answered':
                $ceStatus = 3;
                break;

            case 'On Hold':
            case 'In Progress':
            case 'Customer-Reply':
            default:
                $ceStatus = 2;
                break;
        }
        return $ceStatus;
    }

    public function userStatus2ce ($status)
    {
        switch ($status) {
            case 'Draft':
            case 'Pending':
            case 'Pending Transfer':
                $ce_status = 0;
                break;
            case 'Active':
            case 'Paid':
                $ce_status = 1;
                break;
            case 'Inactive':
            case 'Suspended':
                $ce_status = -1;
                break;
            case 'Closed':
            case 'Cancelled':
            case 'Refunded':
            case 'Terminated':
            case 'Unpaid':
            case 'Expired':
                $ce_status = -2;
                break;
            case 'Fraud':
                $ce_status = -3;
                break;
            default:
                if (empty($status)) {
                    throw new Exception('Unable to convert the status.');
                } else {
                    throw new Exception("Unable to convert the status '{$status}'.");
                }
        }
        return $ce_status;
    }
    public function packageStatus2ce ($status)
    {
        switch ($status) {
            case 'Draft':
            case 'Pending':
            case 'Pending Transfer':
                $ce_status = 0;
                break;
            case 'Active':
            case 'Paid':
                $ce_status = 1;
                break;
            case 'Closed':
            case 'Suspended':
                $ce_status = 2;
                break;
            case 'Cancelled':
            case 'Fraud':
            case 'Inactive':
            case 'Refunded':
            case 'Terminated':
                $ce_status = 3;
                break;
            case 'Unpaid':
                $ce_status = 4;
                break;
            case 'Expired':
                $ce_status = 5;
                break;
            default:
                if (empty($status)) {
                    throw new Exception('Unable to convert the status.');
                } else {
                    throw new Exception("Unable to convert the status '{$status}'.");
                }
        }
        return $ce_status;
    }
}

try {
    if (!file_exists('configuration.php')) {
        throw new Exception('Unable to find the config file.');
    }

    include 'configuration.php';

    $exporter = new WHMCS_Exporter;
    $exporter->encryptionHash = $cc_encryption_hash;
    $exporter->connect($db_host, $db_username, $db_password, $db_name);
    $exporter->export();
    $exporter->closeConnection();
    $exporter->downloadFile();
    $exporter->deleteFile();
} catch (Exception $e) {
    echo '<pre>' . $e->getMessage() . "</pre>\n";
}
