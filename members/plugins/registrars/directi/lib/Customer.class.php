<?php
//	include_once("nusoap.php");
//	include_once("apiutil.php");

	require_once dirname(__FILE__).'/nusoap.php';
        require_once dirname(__FILE__).'/apiutil.php';


	class Customer
	{
		var $serviceObj;
		var $wsdlFileName;
		function Customer($wsdlFileName="wsdl/Customer.wsdl")
		{
			$this->wsdlFileName = $wsdlFileName;
			$this->serviceObj = new lbsoapclient($this->wsdlFileName,"wsdl");
		}
		function sendTemporaryPassword(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $emailAddr)
		{
			$return = $this->serviceObj->call("sendTemporaryPassword",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $emailAddr));
			debugfunction($this->serviceObj);
			return $return;
		}
		function createTemporaryPassword(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId)
		{
			$return = $this->serviceObj->call("createTemporaryPassword",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId));
			debugfunction($this->serviceObj);
			return $return;
		}
		function getCustomerId(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerUsername)
		{
			$return = $this->serviceObj->call("getCustomerId",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerUsername));
			debugfunction($this->serviceObj);
			return $return;
		}
		function getDetailsByCustomerId(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $option)
		{
			$return = $this->serviceObj->call("getDetailsByCustomerId",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $option));
			debugfunction($this->serviceObj);
			return $return;
		}
		function getDetailsByCustomerEmail(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $username, $option)
		{
			$return = $this->serviceObj->call("getDetailsByCustomerEmail",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $username, $option));
			debugfunction($this->serviceObj);
			return $return;
		}
		function authenticateCustomer(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $username, $passwd)
		{
			$return = $this->serviceObj->call("authenticateCustomer",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $username, $passwd));
			debugfunction($this->serviceObj);
			return $return;
		}
		function authenticateCustomerId(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $userLoginId)
		{
			$return = $this->serviceObj->call("authenticateCustomerId",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $userLoginId));
			debugfunction($this->serviceObj);
			return $return;
		}
		function login(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $ipAddress, $headers)
		{
			$return = $this->serviceObj->call("login",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $ipAddress, $headers));
			debugfunction($this->serviceObj);
			return $return;
		}
		function authenticateLoginID(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $loginID)
		{
			$return = $this->serviceObj->call("authenticateLoginID",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $loginID));
			debugfunction($this->serviceObj);
			return $return;
		}
		function generateLoginID(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $ipAddress)
		{
			$return = $this->serviceObj->call("generateLoginID",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $ipAddress));
			debugfunction($this->serviceObj);
			return $return;
		}
		function addCustomer(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerUserName, $customerPassword, $name, $company, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo, $customerLangPref)
		{
			$return = $this->serviceObj->call("addCustomer",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerUserName, $customerPassword, $name, $company, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo, $customerLangPref));
			debugfunction($this->serviceObj);
			return $return;
		}
		function signUp(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $userName, $passwd, $name, $company, $address1, $address2, $address3, $city, $stateName, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo, $langPref, $mobileNoCc, $mobileNo)
		{
			$return = $this->serviceObj->call("signUp",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $userName, $passwd, $name, $company, $address1, $address2, $address3, $city, $stateName, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo, $langPref, $mobileNoCc, $mobileNo));
			debugfunction($this->serviceObj);
			return $return;
		}
		function modDetails(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $customerUserName, $name, $company, $langPref, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo)
		{
			$return = $this->serviceObj->call("modDetails",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $customerUserName, $name, $company, $langPref, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo));
			debugfunction($this->serviceObj);
			return $return;
		}
		function changePassword(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $newPasswd)
		{
			$return = $this->serviceObj->call("changePassword",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $newPasswd));
			debugfunction($this->serviceObj);
			return $return;
		}
		function listOrder(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $resellerId, $username, $name, $company, $city, $country, $customerStatus, $creationDtStart, $creationDtEnd, $totalReceiptStart, $totalReceiptEnd, $noOfRecords, $pageNo, $orderBy)
		{
			$return = $this->serviceObj->call("list",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $resellerId, $username, $name, $company, $city, $country, $customerStatus, $creationDtStart, $creationDtEnd, $totalReceiptStart, $totalReceiptEnd, $noOfRecords, $pageNo, $orderBy));
			debugfunction($this->serviceObj);
			return $return;
		}
		function mod(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $userName, $name, $company, $langPref, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo, $mobileNoCc, $mobileNo)
		{
			$return = $this->serviceObj->call("mod",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId, $userName, $name, $company, $langPref, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $altTelNoCc, $altTelNo, $faxNoCc, $faxNo, $mobileNoCc, $mobileNo));
			debugfunction($this->serviceObj);
			return $return;
		}
	}
?>
