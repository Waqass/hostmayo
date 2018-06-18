<?php
//	include_once("nusoap.php");
//	include_once("apiutil.php");

	require_once dirname(__FILE__).'/nusoap.php';
        require_once dirname(__FILE__).'/apiutil.php';


	class Order
	{
		var $serviceObj;
		var $wsdlFileName;
		function Order($wsdlFileName="wsdl/Order.wsdl")
		{
			$this->wsdlFileName = $wsdlFileName;
			$this->serviceObj = new lbsoapclient($this->wsdlFileName,"wsdl");
		}
		function getDetails(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $entityId, $option)
		{
			$return = $this->serviceObj->call("getDetails",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $entityId, $option));
			debugfunction($this->serviceObj);
			return $return;
		}
		function setCustomerLock(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $orderId)
		{
			$return = $this->serviceObj->call("setCustomerLock",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $orderId));
			debugfunction($this->serviceObj);
			return $return;
		}
		function removeCustomerLock(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $orderId)
		{
			$return = $this->serviceObj->call("removeCustomerLock",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $orderId));
			debugfunction($this->serviceObj);
			return $return;
		}
		function getLockList(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $orderId)
		{
			$return = $this->serviceObj->call("getLockList",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $orderId));
			debugfunction($this->serviceObj);
			return $return;
		}
		function bulkLockCustomerOrders(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerIds, $lockName, $add, $reason, $removeChildLocks)
		{
			$return = $this->serviceObj->call("bulkLockCustomerOrders",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerIds, $lockName, $add, $reason, $removeChildLocks));
			debugfunction($this->serviceObj);
			return $return;
		}
		function bulkLockOrders(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $entityIds, $lockName, $add, $reason, $removeChildLocks)
		{
			$return = $this->serviceObj->call("bulkLockOrders",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $entityIds, $lockName, $add, $reason, $removeChildLocks));
			debugfunction($this->serviceObj);
			return $return;
		}
		function bulkLockResellerOrders(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $resellerIds, $lockName, $add, $reason, $removeChildLocks)
		{
			$return = $this->serviceObj->call("bulkLockResellerOrders",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $resellerIds, $lockName, $add, $reason, $removeChildLocks));
			debugfunction($this->serviceObj);
			return $return;
		}
		function bulkSuspendCustomerOrders(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerIds, $add, $reason, $removeChildLocks)
		{
			$return = $this->serviceObj->call("bulkSuspendCustomerOrders",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $customerIds, $add, $reason, $removeChildLocks));
			debugfunction($this->serviceObj);
			return $return;
		}
		function bulkSuspendOrders(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $entityIds, $add, $reason, $removeChildLocks)
		{
			$return = $this->serviceObj->call("bulkSuspendOrders",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $entityIds, $add, $reason, $removeChildLocks));
			debugfunction($this->serviceObj);
			return $return;
		}
		function bulkSuspendResellerOrders(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $resellerIds, $add, $reason, $removeChildLocks)
		{
			$return = $this->serviceObj->call("bulkSuspendResellerOrders",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $resellerIds, $add, $reason, $removeChildLocks));
			debugfunction($this->serviceObj);
			return $return;
		}
		function checkServiceAvailability(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $productkey)
		{
			$return = $this->serviceObj->call("checkServiceAvailability",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domainName, $productkey));
			debugfunction($this->serviceObj);
			return $return;
		}
		function getOrderIdByDomainAndProductCategory(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domain, $productCategory)
		{
			$return = $this->serviceObj->call("getOrderIdByDomainAndProductCategory",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $domain, $productCategory));
			debugfunction($this->serviceObj);
			return $return;
		}
	}
?>
