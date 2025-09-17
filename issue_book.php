<?php
// Start the session to check if user is logged in
session_start();

// Check if the user is an admin, if not redirect to login page
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
} else if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "if0_38414067_library_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Variable to store messages for the user
$message = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['issue'])) {
    // Get the form data
    $unique_id = $_POST['unique_id'];
    $book_name = $_POST['book_name'];

    // First check if the student exists
    $check_user_sql = "SELECT role FROM login WHERE unique_id = ?";
    $check_user_stmt = $conn->prepare($check_user_sql);
    $check_user_stmt->bind_param("s", $unique_id);
    $check_user_stmt->execute();
    $check_user_result = $check_user_stmt->get_result();

    // If student exists
    if ($check_user_result->num_rows > 0) {
        $user = $check_user_result->fetch_assoc();
        
        // Check if the user is a student (not admin)
        if ($user['role'] === 'student') {
            // Check if the book is available
            $check_sql = "SELECT branch, author, quantity FROM books WHERE book_name = ? LIMIT 1";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("s", $book_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();

            // If book exists and is available
            if ($book && $book['quantity'] > 0) {
                // Reduce the book quantity by 1
                $update_quantity_sql = "UPDATE books SET quantity = quantity - 1 WHERE book_name = ? AND branch = ?";
                $update_stmt = $conn->prepare($update_quantity_sql);
                $update_stmt->bind_param("ss", $book_name, $book['branch']);
                $update_stmt->execute();

                // Calculate issue and due dates
                $issue_date = date('Y-m-d'); // Today's date
                $due_date = date('Y-m-d', strtotime($issue_date . ' +14 days')); // 14 days from today

                // Record the book issue in the database
                $insert_issue_sql = "INSERT INTO issue_report 
                                    (unique_id, book_name, author, branch, issue_date, due_date, status)
                                    VALUES (?, ?, ?, ?, ?, ?, 'Issued')";
                $insert_stmt = $conn->prepare($insert_issue_sql);
                $insert_stmt->bind_param("ssssss", $unique_id, $book_name, $book['author'], 
                                         $book['branch'], $issue_date, $due_date);

                // Check if the book was issued successfully
                if ($insert_stmt->execute()) {
                    $message = "<p style='color: green;'>Book issued successfully!</p>";
                } else {
                    $message = "<p style='color: red;'>Error issuing book.</p>";
                }
            } else {
                $message = "<p style='color: red;'>Book not available.</p>";
            }
        } else {
            $message = "<p style='color: red;'>You cannot issue books to an admin.</p>";
        }
    } else {
        $message = "<p style='color: red;'>The provided Unique ID does not exist.</p>";
    }
}

// Get all available books from the database
$sql = "SELECT book_name, branch, quantity FROM books WHERE quantity > 0";
$available_books = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Issue Book</title>
    <link rel="stylesheet" href="library.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .form-container input, .form-container select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-container button {
            width: 100%;
            background-color: green;
            color: white;
            border: none;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        .form-container button:hover {
            background-color: darkgreen;
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
    <div class="container">
        <header>
            <span class="menu-icon" onclick="toggleMenu()">☰</span>
            <h1>📚 Digital Library</h1>
        </header>
        <nav>
            <ul id="menu">
                <li><a href="admin.php">Admin</a></li>
                <li><a href="add_book.php">Add Book</a></li>
                <li><a href="browse_books.php">Browse Books</a></li>
                <li><a href="book_requests.php">Book Requests</a></li>
                <li><a href="add_person.php">Add Person</a></li>
                <li><a href="student_report.php">Student Report</a></li>
                <li><a href="issue_book.php" class="active">Issue Book</a></li>
                <li><a href="issue_report.php">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <main>
            <h2>Issue a Book</h2>
            <div class="form-container">
                <form method="POST">
                    <label for="unique_id">Student Unique ID:</label>
                    <input type="text" id="unique_id" name="unique_id" required>
                    
                    <?php if (!empty($message)): ?>
                        <div style="color: red; margin-bottom: 15px;"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <label for="book_name">Book Name:</label>
                    <select id="book_name" name="book_name" required>
                        <?php
                        // Check if there are available books
                        if ($available_books->num_rows > 0) {
                            // Loop through each available book
                            while ($row = $available_books->fetch_assoc()) {
                                // Display each book as an option in the dropdown
                                echo "<option value='" . htmlspecialchars($row['book_name']) . "'>" 
                                    . htmlspecialchars($row['book_name']) . " (Branch: " 
                                    . htmlspecialchars($row['branch']) . ", Available: " 
                                    . htmlspecialchars($row['quantity']) . ")</option>";
                            }
                        } else {
                            // Show message if no books are available
                            echo "<option value=''>No books available</option>";
                        }
                        ?>
                    </select>
                    
                    <button type="submit" name="issue">Issue Book</button>
                </form>
            </div>
        </main>
    </div>
    <script>
        // Function to toggle the mobile menu
        function toggleMenu() {
            var menu = document.getElementById("menu");
            
            // Toggle the 'active' class on the menu
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
// Close the database connection
$conn->close();
?>