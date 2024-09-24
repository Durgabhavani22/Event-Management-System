<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Connect to  database
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

// Fetch all events created by staff
$sql_events = "SELECT * FROM events";
$result_events = $conn->query($sql_events);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('https://www.shutterstock.com/image-illustration/abstract-plexus-structure-many-glowing-260nw-776273992.jpg');
            background-size: cover;
            background-attachment: fixed;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #333;
        }
        td {
            border-bottom: 1px solid #ddd;
        }
        .toggle-details, .logout button, .event-button {
            cursor: pointer;
            color: #fff;
            background-color: #4CAF50;
            padding: 10px;
            margin: 10px;
            border-radius: 5px;
            width: 200px;
            text-align: center;
            border: none;
        }
        .nav {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }
        .nav button:hover {
            background-color: #666;
        }
        .intro {
            text-align: center;
            margin: 20px 0;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            background-color: #555;
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <center><h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2></center>
        <div class="nav">
            <button class="toggle-details" onclick="toggleEventButtons()">Student Registration Details</button>
            <form action="logout.php" method="post" class="logout">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
        <div class="intro">
            <h3>Welcome To Admin Dashboard</h3>
            <p>Here, you can manage and view details related to event registrations and events.</p>     
        </div>
            <li><strong>Student Registration Details:</strong> View detailed information about students registered for various events.</li>
            <li><strong>Logout:</strong> Securely log out of the dashboard.</li>
        <div class="events" id="event-buttons" style="display: none;">
            <h3>Events Created by Staff</h3>
            <?php
            if ($result_events->num_rows > 0) {
                while ($event = $result_events->fetch_assoc()) {
                    echo '<div class="event-container">';
                    echo '<button class="event-button" onclick="toggleEventDetails(' . $event['id'] . ', this)">' . htmlspecialchars($event['name']) . '</button>';
                    echo '<div class="event-details" id="event-details-' . $event['id'] . '" style="display: none;"></div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No events found.</p>';
            }
            ?>
        </div>
        <table id="registration-details" style="display: none;">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Registration Date</th>
                    <th>Student Email</th>
                    <th>Branch</th>
                    <th>Roll No</th>
                    <th>Event ID</th>
                    <th>Event Name</th>
                    <th>Event Date</th>
                </tr>
            </thead>
            <tbody id="registration-details-body">
                <!-- Student registration details will be inserted here by JavaScript -->
            </tbody>
        </table>
    </div>
    <script>
        function toggleEventButtons() {
            var eventButtons = document.getElementById('event-buttons');
            eventButtons.style.display = eventButtons.style.display === 'none' ? 'block' : 'none';
        }

        function toggleEventDetails(eventId, button) {
            var detailsContainer = document.getElementById('event-details-' + eventId);

            if (detailsContainer.style.display === 'none' || detailsContainer.style.display === '') {
                // Show event details
                detailsContainer.style.display = 'block';
                fetchEventDetails(eventId, detailsContainer);
            } else {
                // Hide event details
                detailsContainer.style.display = 'none';
            }
        }

        function fetchEventDetails(eventId, detailsContainer) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_event_details.php?event_id=' + eventId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var registrations = JSON.parse(xhr.responseText);
                    var html = '<table><thead><tr><th>Student ID</th><th>Student Name</th><th>Registration Date</th><th>Student Email</th><th>Branch</th><th>Roll No</th><th>Event ID</th><th>Event Name</th><th>Event Date</th></tr></thead><tbody>';
                    if (registrations.length > 0) {
                        registrations.forEach(function(registration) {
                            html += `
                                <tr>
                                    <td>${registration.id}</td>
                                    <td>${registration.student_username}</td>
                                    <td>${registration.registration_date}</td>
                                    <td>${registration.student_email}</td>
                                    <td>${registration.branch}</td>
                                    <td>${registration.roll_no}</td>
                                    <td>${registration.event_id}</td>
                                    <td>${registration.event_name}</td>
                                    <td>${registration.event_date}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html += '<tr><td colspan="9">No students registered for this event.</td></tr>';
                    }
                    html += '</tbody></table>';
                    detailsContainer.innerHTML = html;
                }
            };
            xhr.send();
        }
    </script>
</body>
</html>
