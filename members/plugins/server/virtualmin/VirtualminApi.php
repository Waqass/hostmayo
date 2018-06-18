<?php

/**
 * Provides an interface to issue commands to a remove Virtualmin server.
 * @link http://www.virtualmin.com/documentation/developer/http
 * @author Steven King
 * @email kingrst@gmail.com
 * @version July.23.2011
 */
class VirtualminApi {

    protected $host;
    protected $username;
    protected $hash;
    protected $ssh = false;
    var $port;
    var $schema;
    var $type = 'json';
    var $result;
    var $url;

    /**
     * Begin
     * @param string $host Host name of server
     * @param string $username Username with Virtualmin privileges
     * @param string $hash Access hash
     * @param boolean $ssl Use an SSL connection
     * @param string $type Output type
     */
    public function __construct($host, $username, $hash, $ssl = false, $type = 'json') {
        $this->host = $host;
        $this->username = $username;
        $this->hash = $hash;
        $this->ssl = $ssl;
        $this->port = 10000;
        $this->schema = ( $ssl == true ) ? 'https://' : 'http://';
        $this->type = 'json';
    }

    /**
     * Makes a request through the Virtualmin API
     * @param string $function
     * @param array $params
     * @return Boolean
     */
    public function call($function, $params = array()) {
        if (!function_exists('curl_init')) {
            throw new Exception('cURL is required in order to connect to Virtualmin');
        }

        $this->url = $url = $this->schema . $this->username . ":" . $this->hash . "@" . $this->host . ':' . $this->port . '/virtual-server/remote.cgi?json=1&program=' . $function . '&' . $this->buildQS($params, $function);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $data = curl_exec($ch);

        if ($data == false) {
            $error = "Virtualmin API Request / cURL Error: " . curl_error($ch);
            CE_Lib::log(4, $error);
        }

        $result = $this->result = json_decode($data);

        $this->request = array('url' => $this->url, 'function' => $function, 'params' => $params, 'raw' => $data, 'json' => $result);

        CE_Lib::log(4, 'Virtualmin: ' . print_r($this->request, true));

        if (!is_object($result)) {
            CE_Lib::log(1, "Virtualmin call method: Invalid JSON please check your connection");

            throw new Exception("Virtualmin call method: Invalid JSON please check your connection");
        }

        return $result;
    }

    /**
     * Builds an array suited for a Vitualmin request.
     * @param array $params Key => Value array of parameters to send as the request.
     * @return string Properly built http query string
     */
    private function buildQS($params = array(), $function) {
        if (count($params) == 0) {
            return 'multiline=';
        }

        $queryString = array();
        foreach ($params as $k => $v) {
            $queryString[] = $k . '=' . $v;
        }

        if ($function == "create-domain" || $function == "delete-domain" || $function == "disable-domain" || $function == "enable-domain" || $function == "modify-domain") {
            return implode('&', $queryString);
        } else {
            return implode('&', $queryString) . '&multiline=';
        }
    }

    /**
     * Gets all packages available to the Virtualmin user.
     * @return Array of packages (key = package name, index = package array)
     */
    public function packages() {
        $result = $this->call('list-plans');
        $packages = array();

        if ($result->status != "success") {
            throw new Exception($result->error);
        } else {
            foreach ($result->data as $p) {
                array_push($packages, $p->values->name[0]);
            }

            return $packages;
        }
    }

}
