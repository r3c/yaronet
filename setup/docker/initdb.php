<?php
$servername = $_SERVER["DB_HOST"];
$username = $_SERVER["DB_USER"];
$password = $_SERVER["DB_PASSWORD"];
$database = $_SERVER["DB_NAME"];
$port = $_SERVER["DB_PORT"];
$initdb_script_path = $_SERVER["APP_HOME"] . '/setup/database/schema.sql'; 

// Create connection
$maxTries = 10;
do {
	$conn = new mysqli($servername, $username, $password, '', $port);
	if ($conn->connect_error) {
		echo $stderr, "\n" . 'MySQL Connection Error: (' . $conn->connect_errno . ') ' . $conn->connect_error . "\n";
		--$maxTries;
		if ($maxTries <= 0) {
			exit(1);
        }
        echo "Retrying...(" . $maxTries . ")\n";
		sleep(3);
	}
} while ($conn->connect_error);

echo "Connected successfully to MySQL\n";

$db_exists = mysqli_select_db($conn, $database);

if($db_exists) {
    echo "Database $database already created.\n";
    mysqli_multi_query($conn,file_get_contents($initdb_script_path));
    //Make sure this keeps php waiting for queries to be done
    do{} while(mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Database schema initialized.";
} else {
    die("Database $database not present. You need to create it.");
}

?>

