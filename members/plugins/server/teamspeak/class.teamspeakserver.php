<?php

/**
 * A class used to connect and manage teamspeak servers.
 *
 * @package Clientexec
 * @author Mike Mallinson (Clientexec.com)
 */
class TeamspeakServer
{
    var $port = 51234;
    var $host;
    var $user;
    var $password;
    var $connection;

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
        $this->user = $user;
        $this->password = $password;

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
    	if (strpos($return, "TS") === false)
    	{
    		fclose($this->connection);
    		return new CE_Error('ERROR: Cannot connect to server('.$return.')', 90);
    	}
    	// Login to the server and check that it was successful
    	$command = "slogin $this->user $this->password\n";
    	fputs($this->connection, $command);
    	$return = fgets($this->connection, 20);
    	if (strpos($return, "OK") === false)
    	{
    		fclose($this->connection);
    		return new CE_Error('ERROR: Invalid Username or Password ('.$command.')', 90);
    	}
    	return true;
    }

    /**
     * Checks to see if a port is already in use
     *
     * @param Integer $requestedPort
     * @return true if port is in available and false otherwise
     */
    function checkPortAvailability($requestedPort)
    {
        fputs($this->connection, "sl\n");
    	$return = fgets($this->connection, 10);
    	while (strpos($return, "OK") === false)
    	{
    	   $return .= fgets($this->connection, 10);
    	}
    	if (strpos($return, $requestedPort) !== false)
    	{
    	   return false;
    	}
    	return true;
    }

    /**
     * Returns an array with all the ports in use.
     *
     * @return an array with all ports in use.
     */
    function getPortList()
    {
        fputs($this->connection, "sl\n");
    	$return = fgets($this->connection, 10);
    	while (strpos($return, "OK") === false)
    	{
    	   $return .= fgets($this->connection, 10);
    	}
    	// Remove the extra new lines and OK at the end
    	$return = mb_substr($return, 0, strlen($return) - 5);
    	$portList = explode("\n", $return);
    	for ($i = 0; $i < sizeof($portList); $i++) {
    	    $portList[$i] = trim($portList[$i]);
    	}
    	return $portList;
    }

    /**
     * Private function for flushing unused output from the teamspeak telnet server
     */
    function _flush_output() {
        while (fgets($this->connection, 10) != null) {
            ;
        }
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
    function add($port, $user, $pass, $maxusers) {
        // Add the server and check for success
    	fputs($this->connection, "serveradd $port \n");
    	$return = fgets($this->connection, 10);
    	if (strpos($return, "OK") === false)
    	{
    	    fclose($this->connection);
    	    return new CE_Error('Error: Encountered an error while adding the server', 90);
    	}
    	// Select the server
    	fputs($this->connection, "sel $port \n");
    	$return = fgets($this->connection, 10);
    	if (strpos($return, "OK") === false)
    	{
    	    fclose($this->connection);
    	    return new CE_Error('Error: Encountered an error while selecting the server', 90);
    	}
    	// Change the slot count
    	fputs($this->connection, "serverset server_maxusers $maxusers \n");
    	$return = fgets($this->connection, 10);
    	if (strpos($return, "OK") === false)
    	{
    	    fclose($this->connection);
    	    return new CE_Error('Error: Could not change the slot count to '.$maxusers, 90);
    	}
    	// Add the admin user
    	fputs($this->connection, "dbuseradd $user $pass $pass 1 \n");
    	$return = fgets($this->connection, 10);
    	if (strpos($return, "OK") === false)
    	{
    	    fclose($this->connection);
    	    return new CE_Error('Error: Could not add user ('.$user.') with password ('.$pass.')', 90);
    	}
    	fclose($this->connection);
    	return new CE_Error('Teamspeak Account Creation Success!', 90);
    }

    function delete($port) {
        // Check that the requested port is in use
    	fputs($this->connection, "dbserverlist\n");
    	$return = fgets($this->connection, 100);
    	while (strpos($return, $port) === false)
    	{
    	   $return = fgets($this->connection, 100);
    	   if (strpos($return, "OK") !== false)
    	   {
    	       fclose($this->connection);
    	       return new CE_Error('ERROR: Server with port ('.$port.') does not exist.', 90);
    	   }
    	}
    	$serverid = trim(mb_substr($return, 0, 2));
        $this->_flush_output();
    	fputs($this->connection, "serverstop $port \n");
        $return = fgets($this->connection, 10);
        if (strpos($return, "OK") === false)
        {
            fputs($this->connection, "serverstop $serverid \n");
            $returna = fgets($this->connection, 10);
            if (strpos($returna, "OK") === false)
            {
                fclose($this->connection);
                return new CE_Error('Error: Stopping server (ID:'.$serverid.' , Port:'.$port.') failed. Return('.$return.') and returna('.$returna.')', 90);
            }
        }
    	// Delete the server and check that it was succesful
    	fputs($this->connection, "serverdel $serverid \n");
    	$return = fgets($this->connection, 20);
    	if (strpos($return, "OK") === false)
    	{
    	    fclose($this->connection);
    	    return new CE_Error('Error: Deleting server ('.$serverid.') failed.  Error Message: ('.$return.')', 90);
    	}
    	fclose($this->connection);
    	return new CE_Error('Teamspeak Deletion successful', 90);
    }

    /**
     * Updates a packages slot count.
     *
     * @param Integer $port
     * @param Interger $maxusers
     * @return CE_Error object with the return message.
     */
    function update($port, $maxusers) {
        // Select the server and check that it was successful
    	fputs($this->connection, "sel $port\n");
    	$return = fgets($this->connection, 10);
    	if (strpos($return, "OK") === false)
    	{
    	    fclose($this->connection);
    	    return new CE_Error('ERROR: Could not select server with port ('.$port.').  It does not exist.', 90);
    	}
        // Change the slot count
        fputs($this->connection, "serverset server_maxusers $maxusers \n");
        $return = fgets($this->connection, 10);
        if (strpos($return, "OK") === false) {
            return new CE_Error("Error: Could not change the slot count to $maxusers.", 90);
        }
        return new CE_Error("Teamspeak updated successfully.", 90);
    }
}
?>
