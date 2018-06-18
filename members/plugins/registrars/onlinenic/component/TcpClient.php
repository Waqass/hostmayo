<?php
/**
 * 
 * Tcp Client
 * @author CHENWP
 *
 */
class TcpClient {
	protected $link;
	protected $lastMessage;
	
	public function __construct(&$link) {
		$this->link = &$link;
		$rs = fread($link, 1024);
		$this->lastMessage = $rs;
	}
	
	static public function connect($config) {
		$errno = '';
		$errstr = '';
		$link = fsockopen($config['server'], $config['port'], $errno, $errstr, $config['timeout']);
		if ( $link ) {
			return new TcpClient($link);
		}
		return false;
	}
	
	public function sendCommand($cmd) {
		fwrite($this->link, $cmd);
		$rs = fread($this->link, 8192);
		$this->lastMessage = $rs;
		return $rs;
	}
	
	public function disconnect() {
		if ( $this->link ) {
			fclose($this->link);
		}
	}
	
	public function getLastMessage() {
		return $this->lastMessage;
	}
	
	public function __destruct() {
		$this->disconnect();
	}
}
