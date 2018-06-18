<?php // Test for ISPManager Plugin

$arr = array(
    "UserEmail" => 'user@example.com',
    "PassedUserName" => 'exampleuser',
    "plugin_ispmanager_Create" => 1,
    "plugin_ispmanager_Delete" => 1,
    "plugin_ispmanager_Password" => 'password',
    "plugin_ispmanager_Username" => 'billing',
    "DomainName" => 'example.name',
    "DomainUsername" => 'exampleuser',
    "DomainPassword" => '123',
    "DomainSharedIP" => 1,
    "DomainIP" => '192.168.1.2',
    "PackageID" => 17,
    "CustomerID" => 101,
    "PackageName" => 'Basic',
    "PackageNameOnServer" => '',
    "ServerHostName" => 'ce-hosting-01.example.com',
    "package_vars" => Array
        (
            "bandwidthlimit" => 10000,
            "baselimit" => 100,
            "baseuserlimit" => 100,
            "cgi" => 1,
            "disklimit" => 1000,
            "domainlimit" => 100,
            "ftplimit" => 100,
            "maildomainlimit" => 100,
            "maillimit" => 1000,
            "phpcgi" => 1,
            "ssi" => 1,
            "webdomainlimit" => 100
        )

);

$updarr = array(
    "ServerAcctProperties" => '',
    "CHANGE_PASSWORD" => '1234',
    "CHANGE_PACKAGE" => 'Standard',
    "NewPackageVars" => Array (
            "bandwidthlimit" => 30000,
            "baselimit" => 300,
            "baseuserlimit" => 300,
            "cgi" => 1,
            "disklimit" => 3000,
            "domainlimit" => 300,
            "ftplimit" => 300,
            "maildomainlimit" => 300,
            "maillimit" => 3000,
            "phpcgi" => 1,
            "phpfcgi" => 1,
            "phpmod" => 1,
            "shell" => 1,
            "ssi" => 1,
            "ssl" => 1,
            "webdomainlimit" => 300
        ),
    "plugin_ispmanager_Create" => 1,
    "plugin_ispmanager_Delete" => 1,
    "plugin_ispmanager_Password" => 'password',
    "plugin_ispmanager_Suspend" => 1,
    "plugin_ispmanager_UnSuspend" => 1,
    "plugin_ispmanager_Update" => 1,
    "plugin_ispmanager_Username" => 'billing',
    "DomainName" => 'example.name',
    "DomainUsername" => 'exampleuser',
    "DomainPassword" => '123',
    "DomainSharedIP" => 1,
    "DomainIP" => '192.168.1.2',
    "PackageID" => 17,
    "CustomerID" => 101,
    "ServerHostName" => 'ce-hosting-01.example.com',
    "PackageName" => 'Standard',
    "PackageNameOnServer" => '',
    "package_vars" => Array (
            "bandwidthlimit" => 30000,
            "baselimit" => 300,
            "baseuserlimit" => 300,
            "cgi" => 1,
            "disklimit" => 3000,
            "domainlimit" => 300,
            "ftplimit" => 300,
            "maildomainlimit" => 300,
            "maillimit" => 3000,
            "phpcgi" => 1,
            "phpfcgi" => 1,
            "phpmod" => 1,
            "shell" => 1,
            "ssi" => 1,
            "ssl" => 1,
            "webdomainlimit" => 300
        )
);



include("plugin.ispmanager.php");

//echo Plugin_ISPmanager_CreateUsername($arr);
//echo Plugin_ISPmanager_Create($arr);
//sleep(5);
//echo Plugin_ISPmanager_Update($updarr);
//sleep(5);
//echo Plugin_ISPmanager_Delete($arr);
