<?php
// Start session to check admin login status
session_start();

// Redirect to login if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "if0_38414067_library_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle book return (delete) request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    // First get book details before deleting
    $book_query = "SELECT book_name, branch, unique_id FROM issue_report WHERE id = $delete_id";
    $book_result = $conn->query($book_query);

    if ($book_result->num_rows > 0) {
        $row = $book_result->fetch_assoc();
        $book_name = $row['book_name'];
        $branch = $row['branch'];
        $unique_id = $row['unique_id'];

        // Increase book quantity in books table
        $update_quantity_sql = "UPDATE books SET quantity = quantity + 1 
                               WHERE book_name = '$book_name' AND branch = '$branch' 
                               LIMIT 1";
        
        if ($conn->query($update_quantity_sql) === TRUE) {
            // Delete from issue report
            $delete_sql = "DELETE FROM issue_report WHERE id = $delete_id LIMIT 1";
            
            if ($conn->query($delete_sql) === TRUE) {
                // Also delete any approved requests for this book
                $delete_request_sql = "DELETE FROM book_requests 
                                      WHERE book_name = '$book_name' 
                                      AND unique_id = '$unique_id' 
                                      AND status = 'approved'";
                $conn->query($delete_request_sql);
                
                // Show success message
                echo "<script>
                        alert('Book returned and quantity updated successfully! The approved request has been removed.'); 
                        window.location.href='issue_report.php';
                      </script>";
            } else {
                echo "Error deleting record: " . $conn->error;
            }
        } else {
            echo "Error updating quantity: " . $conn->error;
        }
    }
}

// Handle CSV export request
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $sql = "SELECT * FROM issue_report";
    $result = $conn->query($sql);

    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="issue_report.csv"');

    // Create CSV file
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Unique ID', 'Book Name', 'Author', 'Branch', 'Issue Date', 'Due Date', 'Status']);

    // Add data rows
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['unique_id'],
                $row['book_name'],
                $row['author'],
                $row['branch'],
                $row['issue_date'],
                $row['due_date'],
                $row['status']
            ]);
        }
    }

    fclose($output);
    $conn->close();
    exit();
}

// Get all issued books for display
$sql = "SELECT * FROM issue_report";  
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Issue Report</title>
    <link rel="stylesheet" href="library.css">
    <!-- Library for taking screenshots -->
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        main {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 20px;
        }
        main h2{
            padding-top: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e8f5e9;
        }
        form {
            margin: 0;
        }
        .delete-btn {
            background-color: #e53935;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }
        .delete-btn:hover {
            background-color: #b71c1c;
        }
        .btn-export {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 15px;
            margin-right: 10px;
            display: inline-block;
        }
        .btn-export:hover {
            background-color: #45a049;
        }
        .btn-screenshot {
            background-color: #3F51B5;;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 15px;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-screenshot:hover {
            background-color: #303F9F;
        }
        .export-buttons {
            margin-bottom: 15px;
        }

        @media (min-width: 769px) {
            td[data-label='Issue Date'],
            td[data-label='Due Date'] {
                min-width: 150px; /* Increase width for Issue Date and Due Date */
                white-space: nowrap; /* Prevent text wrapping */
            }
        }

        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                display: none;
            }
            tr {
                margin-bottom: 10px;
                border: 1px solid #ccc;
                padding: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-radius: 8px;
                background-color: #f9f9f9;
            }
            td {
                display: flex;
                justify-content: space-between;
                padding: 8px 10px;
                position: relative;
                border-bottom: 1px solid #ddd;
            }
            td:last-child {
                border-bottom: none;
            }
            td:before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 15px; /* Increased space between label and value */
                color: #555;
                min-width: 100px; /* Ensures consistent label width */
            }
            .export-buttons {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .btn-export, .btn-screenshot {
                margin-right: 0;
                text-align: center;
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
                <li><a href="book_requests.php">Book Requests</a></li>
                <li><a href="add_person.php">Add Person</a></li>
                <li><a href="student_report.php">Student Report</a></li>
                <li><a href="issue_book.php">Issue Book</a></li>
                <li><a href="issue_report.php" class="active">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Issue Report</h2>
            
            <!-- Export buttons -->
            <div class="export-buttons">
                <a href="issue_report.php?export=csv" class="btn-export">Export to CSV</a>
                <button class="btn-screenshot" id="screenshot-btn">Download Screenshot</button>
            </div>
            
            <!-- Report table -->
            <div id="table-container">
                <table id="report-table">
                    <thead>
                        <tr>
                            <th>Unique ID</th>
                            <th>Book Name</th>
                            <th>Author</th>
                            <th>Branch</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Check if there are any issued books
                        if ($result->num_rows > 0) {
                            // Loop through each issued book
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td data-label='Unique ID'>" . htmlspecialchars($row['unique_id']) . "</td>
                                        <td data-label='Book Name'>" . htmlspecialchars($row['book_name']) . "</td>
                                        <td data-label='Author'>" . htmlspecialchars($row['author']) . "</td>
                                        <td data-label='Branch'>" . htmlspecialchars($row['branch']) . "</td>
                                        <td data-label='Issue Date'>" . htmlspecialchars($row['issue_date']) . "</td>
                                        <td data-label='Due Date'>" . htmlspecialchars($row['due_date']) . "</td>
                                        <td data-label='Status'>" . htmlspecialchars($row['status']) . "</td>
                                        <td data-label='Action'>
                                            <form method='POST' action=''>
                                                <input type='hidden' name='delete_id' value='" . htmlspecialchars($row['id']) . "'>
                                                <button type='submit' class='delete-btn'>Delete</button>
                                            </form>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            // Show message if no books are issued
                            echo "<tr><td colspan='8'>No issued books found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Function to toggle mobile menu
        function toggleMenu() {
            var menu = document.getElementById("menu");
            if (menu.classList.contains("active")) {
                menu.classList.remove("active");
            } else {
                menu.classList.add("active");
            }
        }

        // Screenshot button functionality
        document.getElementById('screenshot-btn').addEventListener('click', function() {
            // Hide elements that shouldn't be in the screenshot
            const elementsToHide = [
                document.querySelector('.menu-icon'),
                document.querySelector('nav'),
                document.querySelector('.export-buttons'),
                document.querySelector('h2')
            ];
            
            // Store original display values
            const originalDisplay = [];
            elementsToHide.forEach(el => {
                if (el) {
                    originalDisplay.push(el.style.display);
                    el.style.display = 'none';
                }
            });

            // Take screenshot of the table
            html2canvas(document.getElementById('table-container')).then(canvas => {
                // Restore hidden elements
                elementsToHide.forEach((el, index) => {
                    if (el) {
                        el.style.display = originalDisplay[index];
                    }
                });

                // Create download link
                const link = document.createElement('a');
                link.download = 'issue_report_' + new Date().toISOString().slice(0, 10) + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>