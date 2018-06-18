<?php
require_once 'modules/admin/models/ServicePlugin.php';
/**
* @package Plugins
*/
class PluginArchivelogs extends ServicePlugin
{
    protected $featureSet = 'restricted';
    public $hasPendingItems = false;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Archive Logs'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('Generates a file with an SQL dump of the selected log tables on a periodic basis, and delivers it to any of the locations set below. Then the log tables are erased.'),
                'value'         => '0',
            ),
            lang('Archive events_log Table')       => array(
                'type'          => 'yesno',
                'description'   => lang('Select YES to archive the events_log table, which contains the events logged acording to the setting "general settings->global->Enable Events Logging".'),
                'value'         => '0',
            ),
            lang('Compress files with gzip') => array(
                'type'          => 'yesno',
                'description'   => lang('Only possible if you have the zlib extension in your PHP installation.'),
                'value'         => '0',
                'enableIf'      => 'return extension_loaded(\'zlib\');',
            ),

            lang('Deliver to remote FTP or SFTP account') => array(
                'type'          => 'text',
                'description'   => lang('To send the files to a remote FTP or SFTP account enter the host and your credentials in the format <b>ftp://username:password@host.com/subdirectory</b> for FTP<br>or<br><b>sftp://username:password@host.com/subdirectory</b> for SFTP<br>SFTP is only possible if you have the ssh2 extension in your PHP installation'),
                'value'         => '',
            ),
            lang('Deliver to local directory') => array(
                'type'          => 'text',
                'description'   => lang('To save the files in a local directory accessible and writable by the web server, enter it\'s full path here.'),
                'value'         => '',
            ),
            lang('Deliver to E-mail address') => array(
                'type'          => 'textarea',
                'description'   => lang('To send the files as an E-mail attachment, enter the address here.'),
                'value'         => '',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '30'
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '01',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '15',
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
        );

        return $variables;
    }

    function execute()
    {
        // this can take a while
        @set_time_limit(0);

        $tables = array();
        if ($this->settings->get('plugin_archivelogs_Archive events_log Table')) {
            $tables[] = 'events_log';
        }
        $messages = array();
        $configuration = Zend_Registry::get('configuration');

        foreach ($tables as $table) {
            $fileName = $table . '_' . $configuration['application']['dbSchema'] . "_" . date('Y_m_d_H_i') . ".sql";

            // use references to save memory
            $sql = '';
            $this->_getSQL($sql, $table);
            if ($this->settings->get('plugin_archivelogs_Compress files with gzip')) {
                $fileName .= '.gz';
                if ($message = $this->_gzip($sql)) {
                    $messages[] = $message;
                }
            }

            if ($this->settings->get('plugin_archivelogs_Deliver to remote FTP or SFTP account')) {
                $messages[] = $this->_sendFTP($sql, $fileName, $this->settings->get('plugin_archivelogs_Deliver to remote FTP or SFTP account'));
            }

            if ($this->settings->get('plugin_archivelogs_Deliver to local directory')) {
                $messages[] = $this->_sendLocal($sql, $fileName, $this->settings->get('plugin_archivelogs_Deliver to local directory'));
            }

            if ($this->settings->get('plugin_archivelogs_Deliver to E-mail address')) {
                $destinataries = explode("\r\n", $this->settings->get('plugin_archivelogs_Deliver to E-mail address'));
                foreach ($destinataries as $destinatary) {
                    $messages[] = $this->_sendEmail($sql, $fileName, $destinatary);
                }
            }
            for ($i=0;$i<count($messages);$i++) {
                if (!is_a($messages[$i], 'CE_Error')) {
                    $this->db->query("DELETE FROM `$table`");
                    break;
                }
            }
        }

        return @array_unique($messages);
    }


    function dashboard()
    {
    }


    // PRIVATE FUNCTIONS
    function _getSQL(&$sql, $table)
    {
        $sql .= "--\n-- Dumping data for table `$table`\n--\n\n";
        $result1 = $this->db->query("DESCRIBE `$table`");
        while ($row1 = $result1->fetch()) {
            $fields[]=$row1[0];
        }
        $result2 = $this->db->query("SELECT * FROM `$table`");
        while ($row2 = $result2->fetch(MYSQLI_NUM)) {
            for ($i = 0; $i < count($row2); $i++) {
                $row2[$i] = $this->db->escape_string($row2[$i]);
            }
            $values = implode("', '", $row2);
            $values = "'$values'";
            $values = str_replace("'NULL'", "NULL", $values);
            $sql.= "INSERT INTO `$table`(`".implode("`, `", $fields)."`)\n";
            $sql .= "VALUES($values);\n";
        }
        $sql .= "\n\n";

    }

    function _gzip(&$sql)
    {
        // maximum compression. Make it smaller if you're having performance problems
        $compressionLevel = 9;

        if (!extension_loaded('zlib')) {
            return new CE_Error($this->user->lang('Error: attempted to compress SQL dump file without having the zlib extension enabled'));
        }

        if (!$sql = gzencode($sql, $compressionLevel)) {
            return new CE_Error($this->user->lang('Error: Couldn\'t gzip SQL dump file'));
        }
    }

    // we don't use a reference for $sql here because the ftp class might change the data
    function _sendFTP($sql, $fileName, $host)
    {
        include_once 'plugins/services/archivelogs/ftp_class.php';

        list($login, $password, $domain, $directory) = $this->_getFTPVars($host);

        if (strpos($host, 'ftp://') === 0) {
            $ftp = new ftp();
            if (!$ftp->setServer($domain)) {
                $ftp->quit();
                return new CE_Error($this->user->lang('Error: error setting FTP server'));
            }
            if (!$ftp->connect()) {
                return new CE_Error($this->user->lang('Error: can\'t connect to FTP server'));
            }
            if (!$ftp->login($login, $password)) {
                $ftp->quit();
                return new CE_Error($this->user->lang('Error: authentication with FTP server failed'));
            }
            $ftp->setType(FTP_AUTOASCII);
            $ftp->passive(false);

            if ($directory) {
                $ftp->chdir($directory);
            }

            if (false !== $ftp->put($fileName, $sql)) {
                return $this->user->lang('SQL dump file successfuly uploaded to FTP server: '.$fileName);
            } else {
                $ftp->quit();
                return new CE_Error($this->user->lang('Error: ftp file uploading failed'));
            }
        } else if (strpos($host, 'sftp://') === 0) {
            if (!function_exists('ssh2_connect')) {
                return new CE_Error($this->user->lang('ssh2 extension not found in your PHP installation'));
            }
            $connection = @ssh2_connect($domain, 22);
            if ($connection == false) {
                return new CE_Error($this->user->lang('Conection to %s failed using SFTP. No SSH server listening?', $domain));
            }
            $result = @ssh2_auth_password($connection, $login, $password);

            if ($result == false) {
                return new CE_Error($this->user->lang('Authentication Failure - Username/Password not accepted'));
            }
            $sftp = @ssh2_sftp($connection);
            $dir = "ssh2.sftp://$sftp/".$directory;

            $stream = @fopen("$dir/$fileName", 'w');
            if (!$stream) {
                return new CE_Error($this->user->lang('Unable to create file on remote server'));
            }
            if (@fwrite($stream, $sql) === FALSE) {
                return new CE_Error($this->user->lang('Cannot write to file (%s)', $fileName));
            }
            @fclose($stream);
            return $this->user->lang('SQL dump file successfuly uploaded to SFTP server: '.$fileName);
        }
    }

    function _sendLocal(&$sql, $fileName, $dir)
    {
        $dir = $this->_stripDirSlash($dir);

        if (!@is_dir($dir)) {
            return new CE_Error($this->user->lang('Error: local directory to deploy SQL dump file is not accessible'));
        }

        if (!@is_writable($dir)) {
            return new CE_Error($this->user->lang('Error: local directory to deploy SQL dump file is not writable'));
        }

        if (!$fp = @fopen("$dir/$fileName", 'wb')) {
            return new CE_Error($this->user->lang('Error: couldn\'t create file'));
        }
        if (!fwrite($fp, $sql)) {
            return new CE_Error($this->user->lang('Error: couldn\'t write to new SQL dump file'));
        }
        fclose($fp);

        return $this->user->lang('SQL dump file successfully written:')."$dir/$fileName";
    }

    function _sendEmail(&$sql, $fileName, $email)
    {
        include_once 'library/CE/NE_MailGateway.php';
        $mailGateway = new NE_MailGateway();
        $mailSend = $mailGateway->mailMessageEmail("Attached $fileName",
            $this->settings->get('Support E-mail'),
            'ClientExec Archive Logs Service',
            $email,
            '',
            $this->user->lang('%s attached', $fileName),
            3,
            false,
            $sql,
            $fileName);
        if ($mailSend instanceof CE_Error) {
            return new CE_Error($this->user->lang('Error: couldn\'t send Email with SQL dump file'));
        }

        return $this->user->lang('SQL dump file successfuly sent by E-mail: '.$fileName);
    }

    function _downloadFile(&$sql)
    {
        header('content-disposition: attachment; filename='.$_FILES['file']['name']);
        header('content-type: application/x-download');
        header('content-length: '.strlen($sql));
        echo $sql;
        exit;
    }

    function _stripDirSlash($dir)
    {
        if ($dir{strlen($dir)-1} == '/') {
            $dir = mb_substr($dir, 0, strlen($dir)-1);
        }

        return $dir;
    }

    function _getFTPVars($host)
    {
        if (strpos($host, 'ftp://') === 0) {
            $host = mb_substr($host, 6);
        } else if (strpos($host, 'sftp://') === 0) {
            $host = mb_substr($host, 7);
        }

        $host = $this->_stripDirSlash($host);
        $loginpassword = mb_substr($host, 0, strrpos($host, '@'));
        list($login, $password) = explode(':', $loginpassword);

        $domain = mb_substr($host, strrpos($host, '@') + 1);
        if (($position = strpos($domain, '/')) === false) {
            $directory = false;
        } else {
            $directory = mb_substr($domain, $position);
            $domain = mb_substr($domain, 0, $position);
        }

        return array($login, $password, $domain, $directory);
    }
}
?>
