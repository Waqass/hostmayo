<?php

/**
 * A class used to connect and manage teamspeak servers.
 *
 * @package Clientexec
 * @author Mike Mallinson (Clientexec.com)
 */
class Teamspeak3Server
{
    var $port = 10011;
    var $host;
    var $user;
    var $password;
    var $connection;
    var $lastErrorId = 0;
    var $lastErrorMsg = "ok";

    var $escapeCharsArr = array(
        92      => "\\\\",
        47      => "\\/",
        32      => "\\s",
        124     => "\\p",
        7       => "\\a",
        8       => "\\b",
        12      => "\\f",
        10      => "\\n",
        3       => "\\r",
        9       => "\\t",
        11      => "\\v"
    );

    var $unescapeCharsArr = array(
        "\\\\"  => 92,
        "\\/"   => 47,
        "\\s"   => 32,
        "\\p"   => 124,
        "\\a"   => 7,
        "\\b"   => 8,
        "\\f"   => 12,
        "\\n"   => 10,
        "\\r"   => 3,
        "\\t"   => 9,
        "\\v"   => 11
    );

    /**
     * Creates a new teamspeak server object.
     *
     * @param String $host The server hostname.
     * @param String $user The superadmin username
     * @param String $password The superadmin password
     * @return TeamspeakServer
     */
    function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $this->escapeChars($user);
        $this->password = $this->escapeChars($password);

    }

    /**
     * Function used to connect and login to the Teamspeak server.
     */
    function connect()
    {
        // Connect to the server and check that it was successful
        $this->connection = @fsockopen($this->host, $this->port);
        if (!$this->connection) {
            return new CE_Error('ERROR: Cannot connect to server: '.$this->host.':'.$this->port, 90);
        }
        $return = fgets($this->connection, 10);
        if (strpos($return, "TS3") === false) {
            fclose($this->connection);
            return new CE_Error('ERROR: Cannot connect to server('.$return.')', 90);
        }

        // Login to the server and check that it was successful
        $return = $this->callCommand("login $this->user $this->password");
        if (strpos($return, "msg=ok") === false) {
            fclose($this->connection);
            return new CE_Error('ERROR: Invalid Username or Password.', 90);
        }
        return true;
    }

    function disconect()
    {
        @fclose($this->connection);
    }

    function callCommand($command)
    {
        fputs($this->connection, $command . "\n");
        $result = "";
        $char = "";
        $line = "";
        while (($char = fgetc($this->connection)) !== false) {
            $result .= $char;
            $line .= $char;
            if ($char == "\n") {
                //echo "Line: " . $line . "<br>\n";
                if (strpos($line, "msg=") !== false && strpos($line, "error id=") !== false) {
                    // parse the error id
                    $matches = array();
                    if (preg_match("/error id=(.+) .*/", $line, $matches) && isset($matches[1])) {
                        $this->lastErrorId = $matches[1];
                    } else {
                        $this->lastErrorId = "Failed to parse error id";
                    }

                    // parse the message
                    $matches = array();
                    if (preg_match("/.* msg=(.+).*/", $line, $matches) && isset($matches[1])) {
                        $this->lastErrorMsg = $this->unescapeChars(trim($matches[1]));
                    } else {
                        $this->lastErrorMsg = "Failed to parse message";
                    }
                    break;
                } else {
                    $line = "";
                }
            }
        }
        return $result;
    }

    /**
     * Private function for flushing unused output from the teamspeak telnet server
     */
    function _flush_output()
    {
    }

    function escapeChars($text)
    {
        $result = "";
        for ($i = 0; $i < strlen($text); $i++) {
            $val = ord($text[$i]);
            if (isset($this->escapeCharsArr[$val])) {
                $result .= $this->escapeCharsArr[$val];
            } else {
                $result .= $text[$i];
            }
        }
        return $result;
    }

    function unescapeChars($text)
    {
        $result = "";
        for ($i = 0; $i < strlen($text); $i++) {
            if ($text[$i] == "\\" && isset($text[$i + 1]) && isset($this->unescapeCharsArr[$text[$i] . $text[$i + 1]])) {
                $result .= chr($this->unescapeCharsArr[$text[$i] . $text[$i + 1]]);
                $i++;
            } else {
                $result .= $text[$i];
            }
        }
        return $result;
    }

    function formatLastError()
    {
        return " Error ID: " . $this->lastErrorId . " ( " . $this->lastErrorMsg . ")";
    }


    /**
     * Function to add a new teamspeak server.
     *
     * @param Integer $port
     * @param String $user
     * @param String $pass
     * @param Integer $maxusers
     * @return An CE_Error object with the status message.
     */
    function add($port, $name, $maxusers)
    {
        // escape the name
        $name = $this->escapeChars($name);

        // create command
        $command = 'servercreate virtualserver_name='.$name
                    .' virtualserver_port='.$port
                    .' virtualserver_maxclients='.$maxusers;

        // Add the server and check for success
        $return = $this->callCommand($command);

        if (strpos($return, "msg=ok") === false) {
            return new CE_Error('Error: Encountered an error while adding the server. ' . $this->formatLastError(), 90);
        }

        // Get the serverid
        $matches = array();
        $serverid = -1;
        if (preg_match("/sid=(\d+)/", $return, $matches) && isset($matches[1])) {
            $serverid = $matches[1];
        } else {
            return new CE_Error('Error: No server id returned in server create result. ' . $this->formatLastError(), 90);
        }

        $token = '';
        if (preg_match("/token=(.+)( )/", $return, $matches) && isset($matches[1])) {
            $token = $this->unescapeChars($matches[1]);
        } else {
            return new CE_Error('Error: No administration token returned in server create result. ' . $this->formatLastError(), 90);
        }

        // Select the server
        $return = $this->callCommand("use $serverid");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error('Error: Encountered an error while selecting the server. ' . $this->formatLastError(), 90);
        }

        // Set the server to autostart
        $return = $this->callCommand("serveredit virtualserver_autostart=1");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error("Error: Could not change the autostart option on the server. " . $this->formatLastError(), 90);
        }

        return array ($serverid, $token);
    }

    function delete($port)
    {
        // Get the Server ID
        $serverid = $this->getSidForPort($port);
        if (is_a($serverid, 'CE_Error')) {
            return $serverid;
        }

        $this->stop($serverid);

        // Delete the server and check that it was succesful
        $return = $this->callCommand("serverdelete sid=$serverid");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error('Error: Deleting server ('.$serverid.') failed. ' . $this->formatLastError(), 90);
        }

        return new CE_Error('Teamspeak Deletion successful', 90);
    }

    /**
     * Updates a packages slot count.
     *
     * @param Integer $port
     * @param Interger $maxusers
     * @return CE_Error object with the return message.
     */
    function update($port, $maxusers)
    {
        $sid = $this->getSidForPort($port);
        if (is_a($sid, 'CE_Error')) {
            return $sid;
        }

        // Select the server and check that it was successful
        $return = $this->callCommand("use sid=$sid");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error('ERROR: Could not select server with port ('.$port.').  It does not exist. ' . $this->formatLastError(), 90);
        }

        // Change the slot count
        $return = $this->callCommand("serveredit virtualserver_maxclients=$maxusers");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error("Error: Could not change the slot count to $maxusers. " . $this->formatLastError(), 90);
        }
        return new CE_Error("Teamspeak updated successfully.", 90);
    }

    function suspend($port)
    {
        $sid = $this->getSidForPort($port);
        if (is_a($sid, 'CE_Error')) {
            return $sid;
        }

        // Select the server and check that it was successful
        $return = $this->callCommand("use sid=$sid");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error('ERROR: Could not select server with port ('.$port.').  It does not exist. ' . $this->formatLastError(), 90);
        }

        // Change the autostart option
        $return = $this->callCommand("serveredit virtualserver_autostart=0");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error("Error: Could not change the autostart option on the server. " . $this->formatLastError(), 90);
        }

        $this->stop($sid);

        return new CE_Error("Teamspeak suspended successfully.", 90);
    }

    function unsuspend($port)
    {
        $sid = $this->getSidForPort($port);
        if (is_a($sid, 'CE_Error')) {
            return $sid;
        }

        $this->start($sid);

        // Select the server and check that it was successful
        $return = $this->callCommand("use sid=$sid");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error('ERROR: Could not select server with port ('.$port.').  It does not exist. ' . $this->formatLastError(), 90);
        }

        // Change the autostart option which restarts the server
        $return = $this->callCommand("serveredit virtualserver_autostart=1");
        if (strpos($return, "msg=ok") === false) {
            return new CE_Error("Error: Could not change the autostart option on the server. " . $this->formatLastError(), 90);
        }

        return new CE_Error("Teamspeak unsuspended successfully.", 90);
    }

    function start($serverid)
    {
        $status = $this->getServerStatus($serverid);
        if ($status == 'virtual') {
            // not sure why I must do this...
            $return = $this->callCommand("serverstop sid=$serverid");
            if (strpos($return, "msg=ok") === false) {
                return new CE_Error("Error: Could not stop the Teamspeak server. " . $this->formatLastError(), 90);
            }
        }

        if ($status != 'online') {
            // Start the server
            $return = $this->callCommand("serverstart sid=$serverid");
            if (strpos($return, "msg=ok") === false) {
                return new CE_Error("Error: Could not start the Teamspeak server. " . $return, 90);
            }
        }
    }

    function stop($serverid)
    {
        $status = $this->getServerStatus($serverid);
        if ($status != 'none') {
            // Stop the server
            $return = $this->callCommand("serverstop sid=$serverid");
            if (strpos($return, "msg=ok") === false) {
                return new CE_Error("Error: Could not stop the Teamspeak server. " . $this->formatLastError(), 90);
            }
        }
    }

    /**
     * Checks to see if a port is already in use
     *
     * @param Integer $requestedPort
     * @return true if port is in available and false otherwise
     */
    function checkPortAvailability($requestedPort)
    {
        $return = $this->callCommand("serveridgetbyport virtualserver_port=$requestedPort");
        if (strpos($return, "msg=ok") !== false) {
            return false;
        }
        return true;
    }

    function getSidForPort($port)
    {
        $return = $this->callCommand("serveridgetbyport virtualserver_port=$port");
        // Get the serverid
        $matches = array();
        $serverid = -1;
        if (preg_match("/server_id=(\d+)/", $return, $matches) && isset($matches[1])) {
            return trim($matches[1]);
        } else {
            return new CE_Error('Error: No server id returned in server create result. ' . $this->formatLastError(), 90);
        }
    }

    function getServerStatus($serverid)
    {
        $return = $this->callCommand("use $serverid");
        if (strpos($return, "msg=ok") === false) {
            return '';
        }

        $return = $this->callCommand("serverinfo");
        if (preg_match("/.* virtualserver_status=([a-z]+) .*/", $return, $matches) && isset($matches[1])) {
            return trim($matches[1]);
        } else {
            return '';
        }

        $this->callCommand("use");
    }

    function getAutoStart($serverid)
    {
        $return = $this->callCommand("use $serverid");
        if (strpos($return, "msg=ok") === false) {
            return '';
        }

        $return = $this->callCommand("serverinfo");
        if (preg_match("/.* virtualserver_autostart=(\d+)/", $return, $matches) && isset($matches[1])) {
            return trim($matches[1]);
        } else {
            return '';
        }
    }

    /**
     * Returns an array with all the ports in use.
     *
     * @return an array with all ports in use.
     */
    function getPortList()
    {
        $return = $this->callCommand("serverlist");
        $matches = array();
        if (preg_match_all("/virtualserver_port=(\d+)/", $return, $matches) && isset($matches[1])) {
            return $matches[1];
        } else {
            return array();
        }
    }
}