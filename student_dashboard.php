<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$dbname = 'mydatabase';
$username = 'root';
$password = 'password';

// Create MySQLi connection
$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Fetch all events
$eventsResult = $mysqli->query("SELECT * FROM events");
$events = $eventsResult->fetch_all(MYSQLI_ASSOC);

$studentUsername = $_SESSION['username'];

// Fetch student registered events
$query = $mysqli->prepare("SELECT e.id, e.name, e.date, e.description, e.created_by
FROM events e
INNER JOIN event_registrations er ON e.id = er.event_id
WHERE er.student_username = ?");
$query->bind_param('s', $studentUsername);
$query->execute();
$todoResult = $query->get_result();
$todo = $todoResult->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['event_id'])) {
        $eventId = $_POST['event_id'];
        $studentUsername = $_SESSION['username'];

        if (isset($_POST['register'])) {
            $studentEmail = $_POST['email'];
            $studentBranch = $_POST['branch'];
            $studentRollNo = $_POST['roll_no'];

            // Check if already registered
            $stmt = $mysqli->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND student_username = ?");
            $stmt->bind_param('is', $eventId, $studentUsername);
            $stmt->execute();
            $registrationResult = $stmt->get_result();
            $registration = $registrationResult->fetch_assoc();

            if (!$registration) {
                // Register student for the event
                $stmt = $mysqli->prepare("INSERT INTO event_registrations (event_id, student_username, student_email, branch, roll_no) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('issss', $eventId, $studentUsername, $studentEmail, $studentBranch, $studentRollNo);
                $stmt->execute();
            }
        } elseif (isset($_POST['unregister'])) {
            // Unregister student from the event
            $stmt = $mysqli->prepare("DELETE FROM event_registrations WHERE event_id = ? AND student_username = ?");
            $stmt->bind_param('is', $eventId, $studentUsername);
            $stmt->execute();
        }

        header('Location: student_dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
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
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            margin-top: 50px;
            color: white;
            font-weight: bold;
        }
        .nav {
            display: flex;
            justify-content: center;
            margin-bottom: 0;
        }
        .nav button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            background-color:#45a049;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .nav button:hover {
            background-color: #666;
        }
        h2 {
            text-align: center;
            color: #fff;
        }
        button {
            padding: 10px 20px;
            border: none;
            background-color: #444;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #666;
        }
        a {
            color: white;
        }
        .logout {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            background-color:#45a049;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .logout button {
            border: none;
            background: none;
            color: inherit;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
        }
        .logout button:focus {
            outline: none;
        }
        #calendar-container {
            display: none;
            margin-top: 20px;
        }
        #calendar-container {
            display: none;
            margin-top: 20px;
            width: 50%; 
            margin: auto;
        }
    </style>
    <script>
        function toggleEventsSection() {
            var eventsSection = document.getElementById("events-section");
            var calendarContainer = document.getElementById("calendar-container");
            eventsSection.style.display = eventsSection.style.display === "none" ? "block" : "none";
            calendarContainer.style.display = "none";
        }

        function toggleCalendar() {
            var calendarContainer = document.getElementById("calendar-container");
            if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                calendarContainer.style.display = 'block';
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    events: [
                        <?php foreach ($todo as $event): ?>
                        {
                        title: '<?php echo $event['name']; ?>',
                        start: '<?php echo $event['date']; ?>'
                        },
                        <?php endforeach; ?>
                    ]
                });
                calendar.render();
            } else {
                calendarContainer.style.display = 'none';
            }
        }

        function toggleStudentDetailsForm(eventId) {
            var form = document.getElementById("student-details-form-" + eventId);
            form.style.display = form.style.display === "none" ? "block" : "none";
        }
    </script>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
    
    <div class="nav">
        <button onclick="toggleEventsSection()">Events</button>
        <button onclick="toggleCalendar()">Calendar</button>
        <div class="logout">
            <form action="logout.php" method="post">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>  
    </div>
    <center><h3>Welcome To Student Dashboard</h3></center>


    <div id="calendar-container">
        <div id="calendar"></div>
    </div> 
    
    <p>Welcome to your Student Dashboard! Here, you can manage your events and stay informed about upcoming events organized in the institution. It provides you with an easy-to-use interface to register for events, keep track of your registrations, and view details of various events that you might be interested in.</p>
    
    <h3>Key Features:</h3>
    <ul>
        <li><strong>Event Registration:</strong> Easily register for events by providing your details</li>
        <li><strong>Event Management:</strong> View all upcoming events, read their descriptions, and unregister if you change your plans.</li>
        <li><strong>Personalized Experience:</strong> Your dashboard is customized with your username and event registrations, ensuring a personalized experience.</li>
    </ul>
    
    <div id="events-section" style="display: none;">
        <div class="container">
            <ul>
                <?php foreach ($events as $event): ?>
                    <?php
                    // Check if the student is already registered for the event
                    $stmt = $mysqli->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND student_username = ?");
                    $stmt->bind_param('is', $event['id'], $_SESSION['username']);
                    $stmt->execute();
                    $registrationResult = $stmt->get_result();
                    $registration = $registrationResult->fetch_assoc();
                    $isRegistered = $registration ? true : false;
                    ?>
                    <li>
                        <strong><?php echo $event['name']; ?></strong>
                        <p>Date: <?php echo $event['date']; ?></p>
                        <p>Description: <?php echo $event['description']; ?></p>
                        <?php if ($isRegistered): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <input type="hidden" name="unregister" value="true">
                                <button type="submit">Unregister</button>
                            </form>
                        <?php else: ?>
                            
                            <button onclick="toggleStudentDetailsForm(<?php echo $event['id']; ?>)">Register</button>
                            <form id="student-details-form-<?php echo $event['id']; ?>" method="POST" action="" style="display: none;">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <input type="hidden" name="register" value="true">
                                <label for="email-<?php echo $event['id']; ?>">Email:</label>
                                <input type="email" id="email-<?php echo $event['id']; ?>" name="email" required>
                                <label for="branch-<?php echo $event['id']; ?>">Branch:</label>
                                <input type="text" id="branch-<?php echo $event['id']; ?>" name="branch" required>
                                <label for="roll_no-<?php echo $event['id']; ?>">Roll Number:</label>
                                <input type="text" id="roll_no-<?php echo $event['id']; ?>" name="roll_no" required>
                                <button type="submit">Submit</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
