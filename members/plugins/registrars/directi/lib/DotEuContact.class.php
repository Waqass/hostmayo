<?php
	include_once("nusoap.php");
	include_once("apiutil.php");

	class DotEuContact
	{
		var $serviceObj;
		var $wsdlFileName;
		function DotEuContact($wsdlFileName="wsdl/DotEuContact.wsdl")
		{
			$this->wsdlFileName = $wsdlFileName;
			$this->serviceObj = new soapclient($this->wsdlFileName,"wsdl");
		}
		function addEuDefaultContact(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId)
		{
			$return = $this->serviceObj->call("addEuDefaultContact",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerId));
			debugfunction($this->serviceObj);
			return $return;
		}
		function add(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $name, $company, $emailAddr, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $faxNoCc, $faxNo, $customerId, $type)
		{
			$return = $this->serviceObj->call("add",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $name, $company, $emailAddr, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $faxNoCc, $faxNo, $customerId, $type));
			debugfunction($this->serviceObj);
			return $return;
		}
		function mod(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactId, $name, $company, $emailAddr, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $faxNoCc, $faxNo)
		{
			$return = $this->serviceObj->call("mod",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactId, $name, $company, $emailAddr, $address1, $address2, $address3, $city, $state, $country, $zip, $telNoCc, $telNo, $faxNoCc, $faxNo));
			debugfunction($this->serviceObj);
			return $return;
		}
	}
?>