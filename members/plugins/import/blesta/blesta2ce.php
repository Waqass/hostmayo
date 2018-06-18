<?php
/**
 * Exports data from a Blesta installation to ClientExec
 *
 */

require 'vendors/autoload.php';

error_reporting(E_ALL);

class Database
{
    protected $_db;
    protected $_numRows;
    protected $systemKey;

    public function closeConnection ()
    {
        if (!mysqli_close($this->_db)) {
            throw new Exception('Unable to close connection.');
        }
    }

    public function connect ($hostname, $username, $password, $database, $systemKey)
    {
        if (!($this->_db = mysqli_connect($hostname, $username, $password, $database))) {
            throw new Exception('Unable to connect to database.');
        }
        $this->systemKey = $systemKey;
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

class Blesta_Exporter extends Exporter
{
    protected $_isUtf8 = false;

    public function cycle2ce ($term, $period)
    {
        switch ($period) {
            case 'day':
            case 'week':
                throw new Exception("Unable to convert the cycle '{$term} {$period}'.");
                break;
            case 'month':
                switch ($term) {
                    case 1:
                    case 3:
                    case 6:
                    case 12:
                    case 24:
                    case 36:
                    case 48:
                    case 60:
                    case 72:
                    case 84:
                    case 96:
                    case 108:
                    case 120:
                        $ce_cycle = $term;
                        break;
                    default:
                        throw new Exception("Unable to convert the cycle '{$term} {$period}'.");
                        break;
                }
                break;
            case 'year':
                switch ($term) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    case 10:
                        $ce_cycle = $term*12;
                        break;
                    default:
                        throw new Exception("Unable to convert the cycle '{$term} {$period}'.");
                        break;
                }
                break;
            case 'onetime':
                $ce_cycle = 0;
                break;
            default:
                throw new Exception("Unable to convert the cycle '{$term} {$period}'.");
                break;
        }
        return $ce_cycle;
    }

    //It is missing the registrar
    public function exportDomains ()
    {
        include_once 'app/models/services.php';
        include_once 'app/models/packages.php';

        $services = new Services();
        $packages = new Packages();

        $query = "SELECT `id` FROM `services` ";
        $result = $this->query($query);
        foreach ($result as $row) {
            $service = $services->get($row['id']);

            $package = $packages->get($service->package->id);
            if ($package->groups[0]->type == 'standard') {
                $domain = '';
                $isDomain = false;
                foreach ($service->fields as $field) {
                    if ($field->key === 'domain') {
                        $domain = $field->value;

                        //It is a domain package
                        $isDomain = true;
                    }
                }

                if ($isDomain) {
                    $this->_addColumn($service->client_id);
                    $this->_addColumn($service->date_added);
                    $this->_addColumn($domain);
                    if (!is_null($service->override_price)) {
                        $this->_addColumn($service->override_price);
                    } else {
                        $this->_addColumn($service->package_pricing->price);
                    }
                    $billingcycle = $this->cycle2ce($service->package_pricing->term, $service->package_pricing->period);
                    $this->_addColumn(($billingcycle/12));
                    $domainstatus = $this->packageStatus2ce($service->status);
                    $this->_addColumn($domainstatus);
                    $this->_addColumn($service->date_renews);
                    $this->_addColumn($this->getModuleName($service->package->module_row));
                    $this->_addLine();
                }
            }
        }
    }

    public function exportCoupons()
    {
        $offset = 0;
        do {
            $query = "SELECT c.`id`, "
                ."c.`code`, "
                ."ca.`type`, "
                ."c.`recurring`, "
                ."ca.`amount`, "
                ."c.`start_date`, "
                ."c.`end_date` "
                ."FROM `coupons` c "
                ."INNER JOIN `coupon_amounts` ca ON ca.`coupon_id` = c.`id` "
                ."LIMIT {$offset}, {$this->_mysqlBufferLimit} ";
            $result = $this->query($query);
            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['code']);
                $this->_addColumn(($row['type'] == 'percent')? 'Percentage' : 'Amount');
                $this->_addColumn($row['recurring']);
                $this->_addColumn($row['amount']);
                $this->_addColumn('');
                $this->_addColumn($row['start_date']);
                $this->_addColumn($row['end_date']);
                $this->_addLine();
            }
            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }


    //It is missing the server
    public function exportHosting ()
    {
        include_once 'app/models/services.php';
        include_once 'app/models/packages.php';

        $services = new Services();
        $packages = new Packages();

        $query = "SELECT `id` FROM `services` ";
        $result = $this->query($query);
        foreach ($result as $row) {
            $service = $services->get($row['id']);

            $package = $packages->get($service->package->id);
            if ($package->groups[0]->type == 'standard') {
                $username = '';
                $domain = '';
                foreach ($service->fields as $field) {
                    if ($field->key === 'domain') {
                        //Exclude domain packages
                        continue 2;
                    } elseif (stristr($field->key, 'domain') !== false) {
                        $domain = $field->value;
                    } elseif (stristr($field->key, 'username') !== false) {
                        $username = $field->value;
                    }
                }
                $this->_addColumn($service->id);
                $this->_addColumn($service->client_id);
                $this->_addColumn($username);
                $this->_addColumn($service->package->id);
                $this->_addColumn($service->date_added);
                $this->_addColumn($domain);
                $domainstatus = $this->packageStatus2ce($service->status);
                $this->_addColumn($domainstatus);
                $this->_addColumn($service->date_renews);
                $billingcycle = $this->cycle2ce($service->package_pricing->term, $service->package_pricing->period);
                $this->_addColumn($billingcycle);
                if (!is_null($service->override_price)) {
                    $this->_addColumn($service->override_price);
                } else {
                    $this->_addColumn($service->package_pricing->price);
                }

                $this->_addColumn($service->package->module_row);
                $this->_addColumn($service->coupon_id);
                $this->_addLine();
            }
        }
    }

    public function exportHostingAddons ()
    {
        include_once 'app/models/services.php';
        include_once 'app/models/packages.php';

        $services = new Services();
        $packages = new Packages();

        $query = "SELECT `id` FROM `services` ";
        $result = $this->query($query);
        foreach ($result as $row) {
            $service = $services->get($row['id']);

            $package = $packages->get($service->package->id);
            if ($package->groups[0]->type == 'addon') {
                $this->_addColumn($service->client_id);
                $this->_addColumn($service->parent_service_id);
                $this->_addColumn($service->package_group_id);
                $this->_addColumn($service->package->id);
                $this->_addColumn($service->date_renews);
                $billingcycle = $this->cycle2ce($service->package_pricing->term, $service->package_pricing->period);
                $this->_addColumn($billingcycle);
                if (!is_null($service->override_price)) {
                    $this->_addColumn($service->override_price);
                } else {
                    $this->_addColumn($service->package_pricing->price);
                }
                $this->_addColumn($service->package->name);
                $this->_addLine();
            }
        }
    }

    public function exportInvoices ()
    {
        $offset = 0;

        do {
            $query = "SELECT i.`id`, "
                ."i.`client_id`, "
                ."i.`total`, "
                ."i.`date_due`, "
                ."i.`date_closed`, "
                ."i.`note_public`, "
                ."IFNULL((SELECT MAX(t.`amount`) "
                ."    FROM `taxes` t "
                ."    INNER JOIN `invoice_line_taxes` ilt ON t.`id` = ilt.`tax_id` "
                ."    INNER JOIN `invoice_lines` il ON ilt.`line_id` = il.`id` "
                ."    WHERE il.`invoice_id` = i.`id` "
                ."), 0) AS tax, "
                ."i.`subtotal`, "
                ."i.`status` "
                ."FROM `invoices` i "
                ."LIMIT {$offset}, {$this->_mysqlBufferLimit} ";
            $result = $this->query($query);

            foreach ($result as $row) {
                $row['date_due'] = date('Y-m-d', strtotime($row['date_due']));
                $row['note_public'] = str_replace('"', '\"', $row['note_public']);

                if ($row['date_closed'] != 0) {
                    $row['date_closed'] = date('Y-m-d', strtotime($row['date_closed']));
                } else {
                    $row['date_closed'] = 0;
                }

                $row['status'] = $this->invoiceStatus2ce($row['status'], $row['date_closed']);

                $this->_addColumn($row['id']);
                $this->_addColumn($row['client_id']);
                $this->_addColumn($row['total']);
                $this->_addColumn($row['date_due']);
                $this->_addColumn($row['date_closed']);
                $this->_addColumn($row['Imported Invoice']);
                $this->_addColumn($row['note_public']);
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
            $query = "SELECT il.`id`, "
                ."i.`client_id`, "
                ."il.`invoice_id`, "
                ."IFNULL(il.`service_id`, 0) AS relid, "
                ."(il.`qty` * il.`amount`) AS total_amount, "
                ."(SELECT COUNT(*) "
                ."    FROM `invoice_line_taxes` ilt "
                ."    WHERE ilt.`line_id` = il.`id` "
                .") AS taxed, "
                ."i.`date_due`, "
                ."il.`description` "
                ."FROM `invoice_lines` il "
                ."INNER JOIN `invoices` i ON il.`invoice_id` = i.`id` "
                ."LIMIT {$offset}, {$this->_mysqlBufferLimit} ";
            $result = $this->query($query);

            foreach ($result as $row) {
                $row['duedate'] = date('Y-m-d', strtotime($row['duedate']));
                $this->_addColumn($row['id']);
                $this->_addColumn($row['client_id']);
                $this->_addColumn($row['invoice_id']);
                $this->_addColumn($row['relid']);
                $this->_addColumn($row['total_amount']);
                $this->_addColumn(($row['taxed'] > 0) ? 1 : 0);
                $this->_addColumn($row['date_due']);
                $this->_addColumn('');
                $this->_addColumn($row['description']);
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportPackages ()
    {
        include_once 'app/models/packages.php';

        $packages = new Packages();
        $allPackages = $packages->getAll(1);
        foreach ($allPackages as $p) {
            $package = $packages->get($p->id);

            // we handle domains differently, so skip this entry (for now)
            if ($package->meta->type == 'domain') {
                continue;
            }

            if ($package->groups[0]->type == 'standard') {
                $this->_addColumn($package->id);
                $this->_addColumn($package->name);

                $cePricing = [];
                $cePricing['monthly'] = '';
                $cePricing['quarterly'] = '';
                $cePricing['semiannually'] = '';
                $cePricing['annually'] = '';
                $cePricing['biennially'] = '';
                $cePricing['setup'] = '';

                foreach ($package->pricing as $price) {
                    switch ($price->period) {
                        case 'day':
                        case 'week':
                            break;
                        case 'month':
                            switch ($price->term) {
                                case 1:
                                    $cePricing['monthly'] = $price->price;

                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['monthlysetup'] = $price->setup_fee;
                                    break;
                                case 3:
                                    $cePricing['quarterly'] = $price->price;

                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['quarterlysetup'] = $price->setup_fee;
                                    break;
                                case 6:
                                    $cePricing['semiannually'] = $price->price;

                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['semiannuallysetup'] = $price->setup_fee;
                                    break;
                                case 12:
                                    $cePricing['annually'] = $price->price;

                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['annuallysetup'] = $price->setup_fee;
                                    break;
                                case 24:
                                    $cePricing['biennially'] = $price->price;

                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['bienniallysetup'] = $price->setup_fee;
                                    break;
                                case 36:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['3years'] = $price->price;
                                    //$cePricing['3yearssetup'] = $price->setup_fee;
                                    break;
                                case 48:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['4years'] = $price->price;
                                    //$cePricing['4yearssetup'] = $price->setup_fee;
                                    break;
                                case 60:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['5years'] = $price->price;
                                    //$cePricing['5yearssetup'] = $price->setup_fee;
                                    break;
                                case 72:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['6years'] = $price->price;
                                    //$cePricing['6yearssetup'] = $price->setup_fee;
                                    break;
                                case 84:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['7years'] = $price->price;
                                    //$cePricing['7yearssetup'] = $price->setup_fee;
                                    break;
                                case 96:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['8years'] = $price->price;
                                    //$cePricing['8yearssetup'] = $price->setup_fee;
                                    break;
                                case 108:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['9years'] = $price->price;
                                    //$cePricing['9yearssetup'] = $price->setup_fee;
                                    break;
                                case 120:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['10years'] = $price->price;
                                    //$cePricing['10yearssetup'] = $price->setup_fee;
                                    break;
                            }
                            break;
                        case 'year':
                            switch ($price->term) {
                                case 1:
                                    $cePricing['annually'] = $price->price;

                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['annuallysetup'] = $price->setup_fee;
                                    break;
                                case 2:
                                    $cePricing['biennially'] = $price->price;

                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['bienniallysetup'] = $price->setup_fee;
                                    break;
                                case 3:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['3years'] = $price->price;
                                    //$cePricing['3yearssetup'] = $price->setup_fee;
                                    break;
                                case 4:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['4years'] = $price->price;
                                    //$cePricing['4yearssetup'] = $price->setup_fee;
                                    break;
                                case 5:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['5years'] = $price->price;
                                    //$cePricing['5yearssetup'] = $price->setup_fee;
                                    break;
                                case 6:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['6years'] = $price->price;
                                    //$cePricing['6yearssetup'] = $price->setup_fee;
                                    break;
                                case 7:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['7years'] = $price->price;
                                    //$cePricing['7yearssetup'] = $price->setup_fee;
                                    break;
                                case 8:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['8years'] = $price->price;
                                    //$cePricing['8yearssetup'] = $price->setup_fee;
                                    break;
                                case 9:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['9years'] = $price->price;
                                    //$cePricing['9yearssetup'] = $price->setup_fee;
                                    break;
                                case 10:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['10years'] = $price->price;
                                    //$cePricing['10yearssetup'] = $price->setup_fee;
                                    break;
                            }
                            break;
                        case 'onetime':
                            //Our class ImportPlugin is not making use of this value, but it could.
                            //$cePricing['onetime'] = $price->price;
                            break;
                    }
                }
                $this->_addColumn($cePricing['monthly']);
                $this->_addColumn($cePricing['quarterly']);
                $this->_addColumn($cePricing['semiannually']);
                $this->_addColumn($cePricing['annually']);
                $this->_addColumn($cePricing['biennially']);
                $this->_addColumn($cePricing['setup']);
                $this->_addColumn($package->description_html);
                $this->_addColumn($package->groups[0]->id);
                $this->_addColumn($package->taxable);
                $this->_addLine();
            }
        }
    }

    public function exportPackagesAddons ()
    {
        include_once 'app/models/packages.php';

        $packages = new Packages();
        $allPackages = $packages->getAll(1);
        $packageAddons = array();
        foreach ($allPackages as $p) {
            $package = $packages->get($p->id);

            if ($package->groups[0]->type == 'addon' && !in_array($package->groups[0]->id, $packageAddons)) {
                $packageAddons[] = $package->groups[0]->id;
                $this->_addColumn($package->groups[0]->id);
                $this->_addColumn($package->groups[0]->name);
                $this->_addColumn($package->groups[0]->description);
                $this->_addLine();
            }
        }
    }

    //Blesta allows to have a setup fee per billing cycle in an addon option
    //Clientexec allows to have only one setup fee for all the billing cycles in an addon option
    //To avoid lossing income, this importer will be using the greatest value in setup fees from Blesta to Clientexec
    public function exportPackagesAddonsOptions ()
    {
        include_once 'app/models/packages.php';

        $packages = new Packages();
        $allPackages = $packages->getAll(1);
        foreach ($allPackages as $p) {
            $package = $packages->get($p->id);

            if ($package->groups[0]->type == 'addon') {
                $this->_addColumn($package->id);
                $this->_addColumn($package->groups[0]->id);
                $this->_addColumn($package->name);

                $cePricing = [];
                $cePricing['monthly'] = '';
                $cePricing['quarterly'] = '';
                $cePricing['semiannually'] = '';
                $cePricing['annually'] = '';
                $cePricing['biennially'] = '';
                $cePricing['setup'] = '';

                foreach ($package->pricing as $price) {
                    switch ($price->period) {
                        case 'day':
                        case 'week':
                            break;
                        case 'month':
                            switch ($price->term) {
                                case 1:
                                    $cePricing['monthly'] = $price->price;
                                    break;
                                case 3:
                                    $cePricing['quarterly'] = $price->price;
                                    break;
                                case 6:
                                    $cePricing['semiannually'] = $price->price;
                                    break;
                                case 12:
                                    $cePricing['annually'] = $price->price;
                                    break;
                                case 24:
                                    $cePricing['biennially'] = $price->price;
                                    break;
                                case 36:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['3years'] = $price->price;
                                    break;
                                case 48:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['4years'] = $price->price;
                                    break;
                                case 60:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['5years'] = $price->price;
                                    break;
                                case 72:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['6years'] = $price->price;
                                    break;
                                case 84:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['7years'] = $price->price;
                                    break;
                                case 96:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['8years'] = $price->price;
                                    break;
                                case 108:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['9years'] = $price->price;
                                    break;
                                case 120:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['10years'] = $price->price;
                                    break;
                            }
                            break;
                        case 'year':
                            switch ($price->term) {
                                case 1:
                                    $cePricing['annually'] = $price->price;
                                    break;
                                case 2:
                                    $cePricing['biennially'] = $price->price;
                                    break;
                                case 3:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['3years'] = $price->price;
                                    break;
                                case 4:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['4years'] = $price->price;
                                    break;
                                case 5:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['5years'] = $price->price;
                                    break;
                                case 6:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['6years'] = $price->price;
                                    break;
                                case 7:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['7years'] = $price->price;
                                    break;
                                case 8:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['8years'] = $price->price;
                                    break;
                                case 9:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['9years'] = $price->price;
                                    break;
                                case 10:
                                    //Our class ImportPlugin is not making use of this value, but it could.
                                    //$cePricing['10years'] = $price->price;
                                    break;
                            }
                            break;
                    }

                    //Blesta allows to have a setup fee per billing cycle in an addon option
                    //Clientexec allows to have only one setup fee for all the billing cycles in an addon option
                    //To avoid lossing income, this importer will be using the greatest value in setup fees from Blesta to Clientexec
                    if ($cePricing['setup'] == '' || $cePricing['setup'] < $price->setup_fee) {
                        $cePricing['setup'] = $price->setup_fee;
                    }
                }
                $this->_addColumn($cePricing['monthly']);
                $this->_addColumn($cePricing['quarterly']);
                $this->_addColumn($cePricing['semiannually']);
                $this->_addColumn($cePricing['annually']);
                $this->_addColumn($cePricing['biennially']);
                $this->_addColumn($cePricing['setup']);
                $this->_addLine();
            }
        }
    }

    public function exportPackagesGroups ()
    {
        $offset = 0;

        do {
            $query = "SELECT `id`, `name`, `description` FROM `package_groups` WHERE `type` = 'standard' LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['description']);
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

    public function exportUsers ()
    {
        $offset = 0;
        do {
            $query = "SELECT *, (SELECT number FROM contact_numbers WHERE contact_id = contacts.id LIMIT 1) as phone  FROM users, clients, contacts WHERE users.id = clients.user_id AND clients.id=contacts.id LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);
            foreach ($result as $row) {
                $row['status'] = $this->userStatus2ce($row['status']);
                $this->_addColumn($row['id']);
                $this->_addColumn($row['first_name']);
                $this->_addColumn($row['last_name']);
                $join = '';
                if ($row['address1'] != '' && $row['address2'] != '') {
                    $join = ' ';
                }
                $address = $row['address1'].$join.$row['address2'];
                $this->_addColumn($address);
                $this->_addColumn($row['email']);
                $this->_addColumn($row['city']);
                $this->_addColumn($row['state']);
                $this->_addColumn($row['zip']);
                $this->_addColumn($row['phone']);
                $this->_addColumn($row['country']);
                $this->_addColumn($row['company']);
                $this->_addColumn($row['status']);
                $this->_addColumn('English');
                $this->_addColumn('');
                $this->_addColumn('');
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    private function getModuleName($id)
    {
        $query = "SELECT LOWER(name) as name FROM modules WHERE id={$id}";
        $result = $this->query($query);
        return $result[0]['name'];
    }

    private function getModuleMeta($moduleId, $key)
    {

        $query = "SELECT value,encrypted FROM module_row_meta WHERE module_row_id={$moduleId} AND `key`='{$key}'";
        $result = $this->query($query);
        if ($result[0]['encrypted']) {
            $value = $this->decryptData($result[0]['value']);
            return $value;
        }
        return $result[0]['value'];
    }

    private function decryptData($data)
    {
        $helperClass = new HelperClass();
        return $helperClass->systemDecrypt($data, $this->systemKey, $this->systemKey);

    }

    public function exportServers ()
    {
        $offset = 0;

        do {
            $query = "SELECT id,class FROM modules LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                // no server name, so not a server, skip
                if ($this->getModuleMeta($row['id'], 'server_name') == '') {
                    continue;
                }
                $this->_addColumn($row['id']);

                $this->_addColumn($this->getModuleMeta($row['id'], 'server_name'));
                $this->_addColumn($this->getModuleMeta($row['id'], 'host_name'));

                // XXX: Need to do IPs, which I can not find in Blesta
                $this->_addColumn('');
                $this->_addColumn('');
                $this->_addColumn('');
                $this->_addColumn($this->getModuleMeta($row['id'], 'account_limit'));
                $this->_addColumn($row['class']);
                $this->_addColumn($this->getModuleMeta($row['id'], 'user_name'));
                $this->_addColumn($this->getModuleMeta($row['id'], 'key'));
                $this->_addColumn($this->getModuleMeta($row['id'], 'key'));
                $this->_addColumn($this->getModuleMeta($row['id'], 'use_ssl'));

                $nameServers = unserialize($this->getModuleMeta($row['id'], 'name_servers'));
                $this->_addColumn($nameServers[0]);
                $this->_addColumn('');
                $this->_addColumn($nameServers[1]);
                $this->_addColumn('');
                $this->_addColumn($nameServers[2]);
                $this->_addColumn('');
                $this->_addColumn($nameServers[3]);
                $this->_addColumn('');
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function exportDepartments()
    {
        $offset = 0;
        do {
            $query = "SELECT * FROM support_departments LIMIT {$offset}, {$this->_mysqlBufferLimit}";
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
            $query = "SELECT * FROM `support_tickets` LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['client_id']);
                $this->_addColumn($row['date_added']);
                $this->_addColumn($row['summary']);
                $this->_addColumn();
                $this->_addColumn($this->ticketStatus2ce($row['status']));
                $this->_addColumn($this->ticketUrgency2ce($row['urgency']));
                $this->_addColumn();
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
            $query = "SELECT * FROM support_replies LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['ticket_id']);
                if ($row['staff_id'] == null) {
                    $this->_addColumn($this->getUserIdFromTicket($row['ticket_id']));
                } else {
                    $this->_addColumn($row['staff_id']);
                }
                $this->_addColumn($row['date_added']);
                $this->_addColumn($row['details']);
                $this->_addColumn('');
                $this->_addColumn(($row['staff_id'] != null ? 1 : 0));
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    private function getUserIdFromTicket($ticketId)
    {
        $query = "SELECT client_id FROM support_tickets WHERE id={$ticketId} LIMIT 1";
        $result = $this->query($query);
        return $result[0]['client_id'];
    }

    public function exportStaff()
    {
        $offset = 0;
        do {
            $query = "SELECT id, first_name, last_name, email, status FROM `staff` LIMIT {$offset}, {$this->_mysqlBufferLimit}";
            $result = $this->query($query);

            foreach ($result as $row) {
                $this->_addColumn($row['id']);
                $this->_addColumn($row['first_name']);
                $this->_addColumn($row['last_name']);
                $this->_addColumn($row['email']);
                $this->_addColumn($this->userStatus2ce($row['status']));
                $this->_addLine();
            }

            $offset += $this->_mysqlBufferLimit;
        } while ($this->getNumRows() >= 1);
    }

    public function ticketUrgency2ce($urgency)
    {
        switch ($urgency) {
            case 'low':
                $priority = 3;
                break;

            case 'medium':
                $priority = 2;
                break;

            case 'critical':
            case 'high':
            case 'emergency':
                $priority = 1;
                break;

        }
        return $priority;
    }

    public function ticketStatus2ce($status)
    {
        switch ($status) {
            case 'open':
                $ceStatus = 1;
                break;

            case 'closed':
                $ceStatus = -1;
                break;

            case 'awaiting_reply':
            case 'in_progress':
            default:
                $ceStatus = 2;
                break;
        }
        return $ceStatus;
    }

    public function userStatus2ce ($status)
    {
        switch ($status) {
            case 'active':
                $ce_status = 1;
                break;
            case 'inactive':
                $ce_status = -1;
                break;
            case 'fraud':
                $ce_status = -3;
                break;
            default:
                if (empty($status)) {
                    throw new Exception('Unable to convert empty user status.');
                } else {
                    throw new Exception("Unable to convert user status '{$status}'.");
                }
        }
        return $ce_status;
    }
    public function packageStatus2ce ($status)
    {
        switch ($status) {
            case 'pending':
            case 'in_review':
                $ce_status = 0;
                break;
            case 'active':
                $ce_status = 1;
                break;
            case 'suspended':
                $ce_status = 2;
                break;
            case 'canceled':
                $ce_status = 3;
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
    public function invoiceStatus2ce ($status, $date_closed)
    {
        switch ($status) {
            case 'draft':
            case 'proforma':
                $ce_status = -1;
                break;
            case 'active':
                if ($date_closed == 0) {
                    $ce_status = 0;
                } else {
                    $ce_status = 1;
                }
                break;
            case 'void':
                $ce_status = 2;
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
    if (!file_exists('config/blesta.php')) {
        throw new Exception('Unable to find config file.');
    }

    include 'lib/init.php';
    include 'config/blesta.php';

    class HelperClass extends AppModel {

    }

    $vars = Configure::get('Blesta.database_info');
    $db_host = $vars['host'];
    $db_username = $vars['user'];
    $db_password = $vars['pass'];
    $db_name = $vars['database'];
    $systemKey = Configure::get('Blesta.system_key');

    $exporter = new Blesta_Exporter;
    $exporter->connect($db_host, $db_username, $db_password, $db_name, $systemKey);
    $exporter->export();
    $exporter->closeConnection();
    $exporter->downloadFile();
    $exporter->deleteFile();
} catch (Exception $e) {
    echo '<pre>' . $e->getMessage() . "</pre>\n";
}