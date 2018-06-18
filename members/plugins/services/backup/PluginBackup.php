<?php
require_once 'modules/admin/models/ServicePlugin.php';
/**
* @package Plugins
*/
class PluginBackup extends ServicePlugin
{
    protected $featureSet = 'restricted';
    public $hasPendingItems = false;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('CE Database Backup'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('Generates a file with an SQL dump of your ClientExec database on a periodic basis, and delivers it to any of the locations set below.'),
                'value'         => '0',
            ),
            lang('Compress file with gzip') => array(
                'type'          => 'yesno',
                'description'   => lang('Only possible if you have the zlib extension in your PHP installation.'),
                'value'         => '0',
                'enableIf'      => 'return extension_loaded(\'zlib\');',
            ),
            lang('Encryption password') => array(
                'type'          => 'text',
                'description'   => lang('Enter a password if you wish to encrypt the file, or else leave empty. Only possible if you have the mcrypt extension in your PHP installation'),
                'value'         => '',
                'enableIf'      => 'return extension_loaded(\'mcrypt\');',
            ),
            lang('Deliver to remote FTP or SFTP account') => array(
                'type'          => 'text',
                'description'   => lang('To send the file to a remote FTP or SFTP account enter the host and your credentials in the format <b>ftp://username:password@host.com/subdirectory</b> for FTP<br>or<br><b>sftp://username:password@host.com/subdirectory</b> for SFTP<br>SFTP is only possible if you have the ssh2 extension in your PHP installation'),
                'value'         => '',
            ),
            lang('Deliver to local directory') => array(
                'type'          => 'text',
                'description'   => lang('To save the file in a local directory accessible and writable by the web server, enter it\'s full path here.'),
                'value'         => '',
            ),
            lang('Deliver to E-mail address') => array(
                'type'          => 'textarea',
                'description'   => lang('To send the file as an E-mail attachment, enter the address here.'),
                'value'         => '',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '30',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '01',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
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

        $messages = array();
        $configuration = Zend_Registry::get('configuration');
        $fileName = "backup_" . $configuration['application']['dbSchema'] . "_" . date('Y_m_d_H_i') . ".sql";

        $sql = $this->getSQL($this->db);
        if ($this->settings->get('plugin_backup_Compress file with gzip')) {
            $fileName .= '.gz';
            if ($message = $this->_gzip($sql, $fileName)) {
                $messages[] = $message;
            }
        }

        if ($this->settings->get('plugin_backup_Encryption password')) {
            $fileName .= '.enc';
            if ($message = $this->_encrypt($sql, $this->settings->get('plugin_backup_Encryption password'))) {
                $messages[] = $message;
            }
        }

        if ($this->settings->get('plugin_backup_Deliver to remote FTP or SFTP account')) {
            $messages[] = $this->_sendFTP($sql, $fileName, $this->settings->get('plugin_backup_Deliver to remote FTP or SFTP account'));
        }

        if ($this->settings->get('plugin_backup_Deliver to local directory')) {
            $messages[] = $this->_sendLocal($sql, $fileName, $this->settings->get('plugin_backup_Deliver to local directory'));
        }

        if ($this->settings->get('plugin_backup_Deliver to E-mail address')) {
            $destinataries = explode("\r\n", $this->settings->get('plugin_backup_Deliver to E-mail address'));
            foreach ($destinataries as $destinatary) {
                $messages[] = $this->_sendEmail($sql, $fileName, $destinatary);
            }
        }

        return $messages;
    }

    function output()
    {
        $output = "<a href=\"#\" onclick=\"window.open('index.php?fuse=admin&view=decryptsqldump&controller=index', 'decryptFile', 'width=400,height=200')\" style=\"font-weight:bold\">"
                 .$this->user->lang('Click here to decrypt files')
                 ."</a>";

        return $output;
    }

    function dashboard()
    {
    }

    // this is a non-standard plugin function. Don't use it anywhere else.
    function _decrypt()
    {
	if ( is_file($_FILES['file']['tmp_name']) )
	{
	    $sql= CE_Lib::decryptData(   file_get_contents($_FILES['file']['tmp_name']),
                                        $this->settings->get('plugin_backup_Encryption password'));
	    if (is_a($sql, 'CE_Error')) {
		die($sql->getMessage());
	    }
	    $this->_downloadFile($sql);
	}
	else
	{
	    $string = 'Invalid file; Please <a onclick="history.go(-1)" href="#">go back</a> and try again.';
	    die($string);
	}
    }


    static public function getSQL($db)
    {
        $sql = file_get_contents(__DIR__.'/../../../library/setup/sql/schema.sql');

        $modulesDir = realpath(__DIR__ . '/../../../modules');
    	$dir = dir($modulesDir);
    	while (false !== $entry = $dir->read()) {
            if (is_dir($modulesDir.'/'.$entry) && $entry != '.' && $entry != '..' && $entry != 'CVS') {
                if(file_exists($modulesDir.'/'.$entry.'/setup/sql/schema.sql')){
                    $sql .= file_get_contents($modulesDir.'/'.$entry.'/setup/sql/schema.sql');
                } else {
                     continue;
                }
            }
    	}

        $sql .= "\n\n";

        $result1 = $db->query('SHOW TABLES');
        while ($row1 = $result1->fetch()) {
            $sql .= "--\n-- Dumping data for table `{$row1[0]}`\n--\n\n";
            $result2 = $db->query("SELECT * FROM {$row1[0]}");
			$i=0;
			$field_names= '';
			while ($i < $result2->getNumFields()) {
				if($result2->getFieldName($i)!='')
				$field_names .= "`".$result2->getFieldName($i)."`, ";
				$i++;
			}
			$field_names = rtrim($field_names,', ');
            while ($row2 = $result2->fetch(MYSQLI_NUM)) {
                for ($i = 0; $i < count($row2); $i++) {
                    $row2[$i] = $db->escape_string($row2[$i]);
                }
                $values = implode("', '", $row2);
                $values = "'$values'";
                $values = str_replace("'NULL'", "NULL", $values);
                $sql .= "INSERT INTO `{$row1[0]}` ({$field_names}) VALUES($values);\n";
            }
            $sql .= "\n\n";
        }
        $sql = preg_replace('/ENGINE(\s)*=(\s)*\[MyISAM\]/i', 'ENGINE=MyISAM', $sql);
        return $sql;
    }

    function _gzip(&$sql, $fileName)
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

    function _encrypt(&$sql, $password)
    {
        if (!extension_loaded('mcrypt')) {
            return new CE_Error($this->user->lang('Error: attempted to encrypt SQL dump file without having the mcrypt extension enabled'));
        }

        $sql = CE_Lib::encryptData($sql, $password);
    }

    // we don't use a reference for $sql here because the ftp class might change the data
    function _sendFTP($sql, $fileName, $host)
    {
        require_once 'plugins/services/backup/ftp_class.php';

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
        	if(!function_exists('ssh2_connect')) {
        	    return new CE_Error($this->user->lang('ssh2 extension not found in your PHP installation'));
        	}
            $connection = @ssh2_connect($domain, 22);
            if($connection == false) {
                return new CE_Error($this->user->lang('Conection to %s failed using SFTP. No SSH server listening?', $domain));
            }
            $result = @ssh2_auth_password($connection, $login, $password);

            if($result == false) {
                return new CE_Error($this->user->lang('Authentication Failure - Username/Password not accepted'));
            }
            $sftp = @ssh2_sftp($connection);
            $dir = "ssh2.sftp://$sftp/".$directory;

            $stream = @fopen("$dir/$fileName", 'w');
            if (!$stream) {
                return new CE_Error($this->user->lang('Unable to create file on remote server'));
            }
            if(@fwrite($stream, $sql) === FALSE) {
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
        require_once 'library/CE/NE_MailGateway.php';
        $mailGateway = new NE_MailGateway();
        $mailSend = $mailGateway->mailMessageEmail("Attached $fileName",
            $this->settings->get('Support E-mail'),
            'ClientExec Backup Service',
            $email,
            '',
            $this->user->lang('%s attached',$fileName),
            3,
            false,
            $sql,
            $fileName);

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
