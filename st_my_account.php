<?php
// Start the session to access user data
session_start();

// Check if user is logged in as student, if not redirect to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// Check if unique_id is set in session, if not show alert and redirect
if (!isset($_SESSION['unique_id']) || empty($_SESSION['unique_id'])) {
    echo "<script>alert('Please login first!'); window.location.href='login.php';</script>";
    exit();
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "if0_38414067_library_management_system";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details from session
$unique_id = $_SESSION['unique_id'];
$role = $_SESSION['role'];

// Prepare SQL to get user details from database
$user_sql = "SELECT unique_id, name, branch, role FROM login WHERE unique_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$user_result = $stmt->get_result();

// Check if user exists in database
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
} else {
    echo "<script>alert('User details not found.'); window.location.href='login.php';</script>";
    exit();
}

// SQL query to get all book requests and issued books for this student
$request_sql = "SELECT 
                    br.id AS request_id,
                    br.book_name, 
                    br.author, 
                    br.request_date, 
                    br.status, 
                    ir.issue_date, 
                    ir.due_date
                FROM 
                    book_requests br
                LEFT JOIN 
                    issue_report ir 
                ON 
                    br.book_name = ir.book_name 
                    AND br.unique_id = ir.unique_id
                WHERE 
                    br.unique_id = ?
                UNION
                SELECT 
                    NULL AS request_id,
                    ir.book_name, 
                    ir.author AS author, 
                    'N/A' AS request_date, 
                    'Issued' AS status, 
                    ir.issue_date, 
                    ir.due_date
                FROM 
                    issue_report ir
                WHERE 
                    ir.unique_id = ?
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM book_requests br2 
                        WHERE br2.book_name = ir.book_name 
                        AND br2.unique_id = ir.unique_id
                    )";

// Prepare and execute the query
$stmt = $conn->prepare($request_sql);
$stmt->bind_param("ss", $unique_id, $unique_id);
$stmt->execute();
$request_result = $stmt->get_result();

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if action and request_id are set in POST data
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = $_POST['request_id'];
        
        // Check which action was requested
        if ($_POST['action'] == 'remove') {
            // Delete the book request
            $delete_sql = "DELETE FROM book_requests WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            echo "<script>alert('Request removed successfully.'); window.location.href='st_my_account.php';</script>";
            exit();
        } else if ($_POST['action'] == 'request_again') {
            // Update the book request to Pending status
            $update_sql = "UPDATE book_requests SET status = 'Pending', request_date = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            echo "<script>alert('Request submitted again successfully.'); window.location.href='st_my_account.php';</script>";
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="library.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 1.2rem 0;
            font-size: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        nav {
            background-color: #333;
            color: white;
            display: flex;
            justify-content: center;
        }

        nav ul {
            list-style-type: none;
            display: flex;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
        }

        nav ul li {
            margin: 0;
        }

        nav ul li a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            transition: background 0.3s;
        }

        nav ul li a:hover,
        nav ul li a.active {
            background-color: #4CAF50;
        }

        main {
            background-color: white;
            margin: 2rem auto;
            padding: 2rem;
            width: 90%;
            max-width: 1200px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #4CAF50;
            margin-bottom: 1.5rem;
        }

        .user-details {
            margin-bottom: 2rem;
        }

        .user-details p {
            margin: 5px 0;
            font-size: 1rem;
            color: #333;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #e0f7e0;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .action-btns button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .action-btns .request-again {
            background-color: #4CAF50;
            color: white;
        }

        .action-btns .request-again:hover {
            background-color: #45a049;
        }

        .action-btns .remove {
            background-color: #f44336;
            color: white;
        }

        .action-btns .remove:hover {
            background-color: #e53935;
        }

        /* Responsive Table Styles */
        @media (max-width: 768px) {
            table {
                width: 100%;
                border: 1px solid #ddd;
            }

            thead tr {
                display: none;
            }

            tr {
                display: grid;
                grid-template-columns: 1fr 1fr; /* Two columns for labels and values */
                gap: 10px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
                background: white;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            td {
                display: contents; /* grid layout for cells */
            }

            td:before {
                content: attr(data-label);
                font-weight: bold;
                color: #4CAF50;
                text-align: left;
            }

            td > span,
            td > div {
                text-align: right; /* Align values to the right */
                word-break: break-word;
            }

            .action-btns {
                grid-column: span 2; /* Span across both columns */
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .action-btns button {
                width: 100%;
                padding: 10px;
                font-size: 14px;
            }
        }

        .menu-icon {
            display: none;
            font-size: 24px;
            cursor: pointer;
            position: absolute;
            top: 10px;
            left: 20px;
            color: white;
            z-index: 1001;
            transition: left 0.3s ease-in-out;
        }

        @media screen and (max-width: 768px) {
            .menu-icon {
                display: block;
            }

            nav ul {
                display: none;
                flex-direction: column;
                background-color: #333;
                position: absolute;
                z-index: 2;
                top: 0;
                left: -200px;
                width: 200px;
                height: 100vh;
                padding-top: 50px;
                transition: left 0.3s ease-in-out;
            }

            nav ul.active {
                left: 0;
                display: flex;
            }

            .menu-icon.active {
                left: 170px;
                position: absolute;
            }
        }
    </style>
</head>

<body>
    <header>
        <span class="menu-icon" onclick="toggleMenu()">☰</span>
        <h1>📚 Digital Library</h1>
    </header>

    <nav>
        <ul id="menu">
            <li><a href="student.php">Welcome</a></li>
            <li><a href="st_my_account.php" class="active">My Account</a></li>
            <li><a href="st_browse_books.php">Browse Books</a></li>
            <li><a href="st_request_book.php">Request Book</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <main>
        <!-- Display welcome message with user's name -->
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        
        <!-- Display user details -->
        <div class="user-details">
            <p><strong>Unique ID:</strong> <?php echo htmlspecialchars($user['unique_id']); ?></p>
            <p><strong>Branch:</strong> <?php echo htmlspecialchars($user['branch']); ?></p>
            <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
        </div>

        <!-- Display book requests section -->
        <h2>Your Book Requests</h2>
        
        <?php 
        // Check if there are any book requests
        if ($request_result->num_rows > 0) { 
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Request Date</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Loop through each book request
                    while ($row = $request_result->fetch_assoc()) { 
                    ?>
                        <tr>
                            <td data-label="Book Name"><?php echo htmlspecialchars($row['book_name']); ?></td>
                            <td data-label="Author"><?php echo htmlspecialchars($row['author']); ?></td>
                            <td data-label="Request Date"><?php echo htmlspecialchars($row['request_date']); ?></td>
                            <td data-label="Issue Date">
                                <?php 
                                // Display issue date or N/A if not set
                                if (isset($row['issue_date'])) {
                                    echo htmlspecialchars($row['issue_date']);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td data-label="Due Date">
                                <?php 
                                // Display due date or N/A if not set
                                if (isset($row['due_date'])) {
                                    echo htmlspecialchars($row['due_date']);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td data-label="Status"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></td>
                            <td data-label="Actions">
                                <?php 
                                // Check if status is Declined or Pending to show action buttons
                                if ($row['status'] == 'Declined' || $row['status'] == 'Pending') { 
                                ?>
                                    <div class="action-btns">
                                        <?php 
                                        // Show Request Again button only for Declined status
                                        if ($row['status'] == 'Declined') { 
                                        ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                                <button type="submit" name="action" value="request_again" class="request-again">Request Again</button>
                                            </form>
                                        <?php 
                                        } 
                                        ?>
                                        <!-- Always show Remove button for these statuses -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                            <button type="submit" name="action" value="remove" class="remove">Remove</button>
                                        </form>
                                    </div>
                                <?php 
                                } else {
                                    // Show N/A for other statuses
                                    echo 'N/A';
                                } 
                                ?>
                            </td>
                        </tr>
                    <?php 
                    } 
                    ?>
                </tbody>
            </table>
        <?php 
        } else {
            // Show message if no book requests found
            echo "<p>No book requests found.</p>";
        } 
        ?>
    </main>

    <script>
        // Function to toggle mobile menu
        function toggleMenu() {
            var menu = document.getElementById("menu");
            menu.classList.toggle("active");
        }
    </script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>