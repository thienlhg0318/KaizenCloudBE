<?php
//TODO:Clear Cache
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

//TODO:End Clear Cache

$host = '192.168.30.19';
$db_name = 'EIP';
$username = 'lacty';
$password = 'lacty';
$conn_eip = null;

try {
//TODO:Thay doi driver
$conn_eip = odbc_connect("DRIVER={SQL Server Native Client 11.0};Server={$host};Database={$db_name};String Types=Unicode", $username, $password);
    if (!$conn_eip) {
        die(" connect EIP DB error");
    }
} catch (Exception $e) {
    echo $e;
}
 

?>