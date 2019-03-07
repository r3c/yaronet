<?php
$servername = $_SERVER["DB_HOST"];
$username = $_SERVER["DB_USER"];
$password = $_SERVER["DB_PASSWORD"];
$database = $_SERVER["DB_NAME"];
$initdb_script_path = '/yaronet/setup/database/schema.sql'; 

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection to MySQL failed: " . $conn->connect_error);
} 
echo "Connected successfully to MySQL\n";

$db_exists = mysqli_select_db($conn, $database);

if($db_exists) {
    echo "Database $database already created.\n";
    mysqli_multi_query($conn,file_get_contents($initdb_script_path));
    //Make sure this keeps php waiting for queries to be done
    do{} while(mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Database schema initialized.";
} else {
    echo "Database $database not present. Will create it.";
}

?>

