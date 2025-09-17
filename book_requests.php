<?php
// Start the session to check user login status
session_start();

// Check if user is logged in as admin, if not redirect to login page
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
} else if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "if0_38414067_library_management_system";

// Create connection to MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch all pending book requests
$sql = "SELECT br.id AS request_id, 
               l.unique_id, 
               l.name AS student_name, 
               br.book_name, 
               br.author, 
               b.branch, 
               br.request_date
        FROM book_requests br
        INNER JOIN login l ON br.unique_id = l.unique_id
        INNER JOIN books b ON br.book_name = b.book_name AND br.author = b.author
        WHERE br.status = 'Pending'";

// Execute the query
$result = $conn->query($sql);

// Handle Approve or Decline actions when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    // Default status is Declined unless approved
    $status = 'Declined';

    // If approving a request
    if ($action == 'approve') {
        $status = 'Approved';

        // First check if book is available
        $check_sql = "SELECT b.quantity, br.book_name, br.author, b.branch, br.unique_id
                      FROM book_requests br
                      INNER JOIN books b ON br.book_name = b.book_name AND br.author = b.author
                      WHERE br.id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result_check = $stmt->get_result();
        $book = $result_check->fetch_assoc();

        // If book is available (quantity > 0)
        if ($book && $book['quantity'] > 0) {
            // Reduce book quantity by 1
            $update_quantity_sql = "UPDATE books SET quantity = quantity - 1 
                                   WHERE book_name = ? AND author = ? AND branch = ?";
            $update_stmt = $conn->prepare($update_quantity_sql);
            $update_stmt->bind_param("sss", $book['book_name'], $book['author'], $book['branch']);
            $update_stmt->execute();

            // Record the book issue in issue_report table
            $issue_date = date('Y-m-d'); // Today's date
            $due_date = date('Y-m-d', strtotime($issue_date . ' +14 days')); // Due in 14 days
            
            $insert_issue_sql = "INSERT INTO issue_report 
                                (unique_id, book_name, author, branch, issue_date, due_date, status)
                                VALUES (?, ?, ?, ?, ?, ?, 'Issued')";
            $insert_stmt = $conn->prepare($insert_issue_sql);
            $insert_stmt->bind_param("ssssss", $book['unique_id'], $book['book_name'], 
                                     $book['author'], $book['branch'], $issue_date, $due_date);
            $insert_stmt->execute();
        } else {
            // If book not available, decline the request
            $status = 'Declined';
            echo "<script>alert('Book not available. Request declined.');</script>";
        }
    }

    // Update the request status in database
    $update_request_sql = "UPDATE book_requests SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_request_sql);
    $stmt->bind_param("si", $status, $request_id);

    // Execute the update and show success/error message
    if ($stmt->execute()) {
        echo "<script>alert('Request has been $status successfully!'); 
              window.location.href = 'book_requests.php';</script>";
    } else {
        echo "<script>alert('Error updating request status.');</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Book Requests</title>
    <link rel="stylesheet" href="library.css">
    <style>
        /* Main content styling */
        main {
            max-width: 1300px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        /* Table header and cell styling */
        th, td {
            border: 1px solid #ccc;
            padding: 12px 15px;
            text-align: left;
        }

        /* Table header styling */
        th {
            background-color: #4CAF50;
            color: white;
        }

        /* Alternate row coloring */
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        /* Row hover effect */
        tr:hover {
            background-color: #e8f5e9;
        }

        /* Form styling */
        form {
            margin: 0;
        }

        /* Button styling */
        .approve-btn, .decline-btn {
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            margin: 2px;
        }

        /* Approve button specific styling */
        .approve-btn {
            background-color: #45a049;
        }

        /* Decline button specific styling */
        .decline-btn {
            background-color: #e53935;
        }

        /* Button hover effects */
        .approve-btn:hover {
            background-color: #388e3c;
        }
        .decline-btn:hover {
            background-color: #b71c1c;
        }

        /* Mobile menu icon - hidden by default */
        .menu-icon {
            display: none;
            font-size: 24px;
            cursor: pointer;
            position: absolute;
            top: 10px;
            left: 20px;
            color: white;
            z-index: 1001;
        }

        /* Responsive design for smaller screens */
        @media (max-width: 768px) {
            /* Stack table cells vertically on small screens */
            table, thead, tbody, th, td, tr {
                display: block;
            }
            
            /* Hide table headers */
            thead tr {
                display: none;
            }
            
            /* Style each row as a card */
            tr {
                margin-bottom: 10px;
                border: 1px solid #ccc;
                padding: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-radius: 8px;
                background-color: #f9f9f9;
            }
            
            /* Style table cells */
            td {
                display: flex;
                justify-content: space-between;
                padding: 8px 10px;
                position: relative;
                border-bottom: 1px solid #ddd;
            }
            
            /* Remove border from last cell */
            td:last-child {
                border-bottom: none;
            }
            
            /* Add labels before each cell content */
            td:before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 15px;
                color: #555;
                min-width: 100px;
            }
            
            /* Special styling for actions cell */
            td[data-label='Actions'] {
                flex-direction: column;
                gap: 10px;
            }
            
            /* Show mobile menu icon */
            .menu-icon {
                display: block;
            }
            
            /* Mobile menu styling */
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

            /* Active mobile menu state */
            nav ul.active {
                left: 0;
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <!-- Mobile menu button -->
            <span class="menu-icon" onclick="toggleMenu()">☰</span>
            <h1>📚 Digital Library</h1>
        </header>
        
        <nav>
            <ul id="menu">
                <li><a href="admin.php">Admin</a></li>
                <li><a href="add_book.php">Add Book</a></li>
                <li><a href="browse_books.php">Browse Books</a></li>
                <li><a href="book_requests.php" class="active">Book Requests</a></li>
                <li><a href="add_person.php">Add Person</a></li>
                <li><a href="student_report.php">Student Report</a></li>
                <li><a href="issue_book.php">Issue Book</a></li>
                <li><a href="issue_report.php">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Pending Book Requests</h2>
            
            <!-- Requests Table -->
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Unique ID</th>
                        <th>Student Name</th>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Branch</th>
                        <th>Request Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Check if there are any pending requests
                    if ($result->num_rows > 0) {
                        // Loop through each request and display as table row
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td data-label='Request ID'>" . htmlspecialchars($row['request_id']) . "</td>
                                    <td data-label='Unique ID'>" . htmlspecialchars($row['unique_id']) . "</td>
                                    <td data-label='Student Name'>" . htmlspecialchars($row['student_name']) . "</td>
                                    <td data-label='Book Name'>" . htmlspecialchars($row['book_name']) . "</td>
                                    <td data-label='Author'>" . htmlspecialchars($row['author']) . "</td>
                                    <td data-label='Branch'>" . htmlspecialchars($row['branch']) . "</td>
                                    <td data-label='Request Date'>" . htmlspecialchars($row['request_date']) . "</td>
                                    <td data-label='Actions'>
                                        <form method='POST'>
                                            <input type='hidden' name='request_id' value='" . htmlspecialchars($row['request_id']) . "'>
                                            <button type='submit' name='action' value='approve' class='approve-btn'>Approve</button>
                                            <button type='submit' name='action' value='decline' class='decline-btn'>Decline</button>
                                        </form>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        // Display message if no pending requests
                        echo "<tr><td colspan='8' style='text-align: center;'>No pending requests found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <script>
        // Function to toggle mobile menu visibility
        function toggleMenu() {
            var menu = document.getElementById("menu");
            
            // Toggle 'active' class on click
            if (menu.classList.contains("active")) {
                menu.classList.remove("active");
            } else {
                menu.classList.add("active");
            }
        }
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>