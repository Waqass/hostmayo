<?php
//	include_once("nusoap.php");
//	include_once("apiutil.php");

	require_once dirname(__FILE__).'/nusoap.php';
        require_once dirname(__FILE__).'/apiutil.php';


	class DotEu
	{
		var $serviceObj;
		var $wsdlFileName;
		function DotEu($wsdlFileName="wsdl/DotEu.wsdl")
		{
			$this->wsdlFileName = $wsdlFileName;
			$this->serviceObj = new lbsoapclient($this->wsdlFileName,"wsdl");
		}
		function transferDomain(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $technicalContactId, $customerId, $invoiceOption)
		{
			$return = $this->serviceObj->call("transferDomain",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $technicalContactId, $customerId, $invoiceOption));
			debugfunction($this->serviceObj);
			return $return;
		}
		function getEUCountryList(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID)
		{
			$return = $this->serviceObj->call("getEUCountryList",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID));
			debugfunction($this->serviceObj);
			return $return;
		}
		function tradeDomain(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $registrantContactId, $technicalContactId, $customerId, $invoiceOption)
		{
			$return = $this->serviceObj->call("tradeDomain",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $registrantContactId, $technicalContactId, $customerId, $invoiceOption));
			debugfunction($this->serviceObj);
			return $return;
		}
		function trade(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $registrantContactId, $customerId, $nameServers, $childNameServers, $invoiceOption)
		{
			$return = $this->serviceObj->call("trade",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $registrantContactId, $customerId, $nameServers, $childNameServers, $invoiceOption));
			debugfunction($this->serviceObj);
			return $return;
		}
		function isEUCountry(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $countryCode)
		{
			$return = $this->serviceObj->call("isEUCountry",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $countryCode));
			debugfunction($this->serviceObj);
			return $return;
		}
		function add(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $noOfYears, $nameServers, $registrantContactId, $technicalContactId, $customerId, $invoiceOption)
		{
			$return = $this->serviceObj->call("add",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $noOfYears, $nameServers, $registrantContactId, $technicalContactId, $customerId, $invoiceOption));
			debugfunction($this->serviceObj);
			return $return;
		}
		function transfer(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $customerId, $nameServers, $childNameServers, $invoiceOption)
		{
			$return = $this->serviceObj->call("transfer",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $customerId, $nameServers, $childNameServers, $invoiceOption));
			debugfunction($this->serviceObj);
			return $return;
		}
	}
?>
