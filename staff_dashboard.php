<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'mydatabase';
$username = 'root';
$password = 'password';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    if ($role === 'staff') {
        if (isset($_POST['event_name'])) {
            // Create event
            $stmt = $mysqli->prepare("INSERT INTO events (name, date, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $_POST['event_name'], $_POST['event_date'], $_POST['event_description'], $username);
            $stmt->execute();
            header('Location: staff_dashboard.php');
            exit();
        } elseif (isset($_POST['delete_event'])) {
            // Delete event
            $stmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param('i', $_POST['event_id']);
            $stmt->execute();
            header('Location: staff_dashboard.php');
            exit();
        }
    } elseif ($role === 'student' && isset($_POST['register_event_id'])) {
        // Register for event
        $stmt = $mysqli->prepare("INSERT INTO event_registrations (event_id, student_username) VALUES (?, ?)");
        $stmt->bind_param('is', $_POST['register_event_id'], $username);
        $stmt->execute();
        header('Location: student_dashboard.php');
        exit();
    }
}

// Fetch user's events
$stmt = $mysqli->prepare("SELECT * FROM events WHERE created_by = ?");
$stmt->bind_param('s', $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$userEvents = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($_SESSION['role']); ?> Dashboard</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensure the body takes full height */
        }

        h2 {
            text-align: center;
            color: #fff;
            margin: 20px 0; /* Space around the heading */
        }

        .nav {
            display: flex;
            justify-content: center;
            margin: 20px 0; /* Space around navigation */
        }

        .nav button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            background-color: #45a049; /* Green color */
            color: white;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav button:hover {
            background-color: #4CAF50; /* Darker green on hover */
        }

        .nav form {
            margin-left: auto;
        }

        .container {
            width: 80%;
            margin: auto;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            display: none; /* Initially hidden */
        }

        .section {
            display: none;
            margin-top: 20px;
        }

        .section.active {
            display: block;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        input, textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color: #45a049;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #666;
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

        ul li:hover {
            background-color: #777;
        }
    </style>
    <script>
        function showSection(sectionId) {
            const container = document.querySelector('.container');
            const section = document.getElementById(sectionId);
            const sections = document.querySelectorAll('.section');

            container.style.display = 'block';
            sections.forEach(section => section.classList.remove('active'));
            section.classList.add('active');
        }
    </script>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <div class="nav">
        <?php if ($_SESSION['role'] === 'staff'): ?>
            <button onclick="showSection('create-event')">Create Event</button>
            <button onclick="showSection('created-events')">Created Events</button>
        <?php endif; ?>
        <form action="logout.php" method="POST">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
    <center><h3>Welcome To Staff Dashboard</h3></center>
    <p>This is your <?php echo ($_SESSION['role'] === 'staff') ? 'staff dashboard' : 'student dashboard'; ?>. It provides sections to create new events and view events you've created.</p>
    <ul>
        <strong>Event Creation:</strong> Easily create new events by filling out a simple form. Provide details such as the event name, date, and description to keep everyone informed.
        <br><br><strong>Event Management:</strong> See a list of all the events you have created. This helps you keep track of your organizational efforts and ensures nothing is overlooked.
        <br><br><strong>Delete Events:</strong> If an event is canceled or needs to be removed for any reason, you can easily delete it from your list.
    </ul>
    <div class="container">
        <?php if ($_SESSION['role'] === 'staff'): ?>
            <div id="create-event" class="section">
                <h3>Create Event</h3>
                <form method="POST" action="">
                    <label for="event_name">Event Name:</label>
                    <input type="text" id="event_name" name="event_name" required>
                    <label for="event_date">Event Date:</label>
                    <input type="date" id="event_date" name="event_date" required>
                    <label for="event_description">Event Description:</label>
                    <textarea id="event_description" name="event_description" required></textarea>
                    <button type="submit">Create Event</button>
                </form>
            </div>

            <div id="created-events" class="section">
                <h3>Your Events</h3>
                <ul>
                    <?php foreach ($userEvents as $event): ?>
                        <li>
                            <strong>Event Name:</strong> <?php echo htmlspecialchars($event['name']); ?><br>
                            <br><strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?><br>
                           <br> <strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" name="delete_event">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
