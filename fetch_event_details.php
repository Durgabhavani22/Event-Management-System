<?php
// Connect to your database
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "mydatabase";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);

    $sql = "SELECT er.*, e.name AS event_name, e.date AS event_date
            FROM event_registrations er
            JOIN events e ON er.event_id = e.id
            WHERE er.event_id = $event_id";

    $result = $conn->query($sql);

    $registrations = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }
    }

    echo json_encode($registrations);
}
?>
