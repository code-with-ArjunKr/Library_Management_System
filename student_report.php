<?php
// Start session to check admin login status
session_start();

// Redirect to login if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle CSV download request
if (isset($_GET['download_csv']) && isset($_GET['unique_id'])) {
    // Database connection for CSV download
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "if0_38414067_library_management_system";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Sanitize input
    $unique_id = $conn->real_escape_string($_GET['unique_id']);
    
    // Verify student exists
    $check_user_sql = "SELECT role FROM login WHERE unique_id = ?";
    $check_user_stmt = $conn->prepare($check_user_sql);
    $check_user_stmt->bind_param("s", $unique_id);
    $check_user_stmt->execute();
    $check_user_result = $check_user_stmt->get_result();
    
    // If student exists, generate CSV
    if ($check_user_result->num_rows > 0) {
        $user = $check_user_result->fetch_assoc();
        if ($user['role'] === 'student') {
            $query = "SELECT * FROM issue_report WHERE unique_id = '$unique_id'";
            $report_result = $conn->query($query);
            
            if ($report_result && $report_result->num_rows > 0) {
                // Set CSV headers
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="student_report.csv"');
                
                // Create CSV output
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Unique ID', 'Book Name', 'Author', 'Branch', 'Issue Date', 'Due Date', 'Status']);
                
                // Add data rows
                while ($row = $report_result->fetch_assoc()) {
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
                fclose($output);
                exit();
            }
        }
    }
    $conn->close();
}

// Main report generation code
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "if0_38414067_library_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$report_data = '';
$no_books_message = '';

// Check if Unique ID was submitted
if (isset($_GET['unique_id'])) {
    $unique_id = $conn->real_escape_string($_GET['unique_id']);

    // Verify student exists
    $check_user_sql = "SELECT role FROM login WHERE unique_id = ?";
    $check_user_stmt = $conn->prepare($check_user_sql);
    $check_user_stmt->bind_param("s", $unique_id);
    $check_user_stmt->execute();
    $check_user_result = $check_user_stmt->get_result();

    if ($check_user_result->num_rows > 0) {
        $user = $check_user_result->fetch_assoc();
        if ($user['role'] === 'student') {
            // Get student's book records
            $query = "SELECT * FROM issue_report WHERE unique_id = '$unique_id'";
            $report_result = $conn->query($query);

            if ($report_result && $report_result->num_rows > 0) {
                // Build HTML table rows
                while ($row = $report_result->fetch_assoc()) {
                    $report_data .= "<tr>
                        <td data-label='Unique ID'><strong>" . htmlspecialchars($row['unique_id']) . "</strong></td>
                        <td data-label='Book Name'>" . htmlspecialchars($row['book_name']) . "</td>
                        <td data-label='Author'>" . htmlspecialchars($row['author']) . "</td>
                        <td data-label='Branch'>" . htmlspecialchars($row['branch']) . "</td>
                        <td data-label='Issue Date'>" . htmlspecialchars($row['issue_date']) . "</td>
                        <td data-label='Due Date'>" . htmlspecialchars($row['due_date']) . "</td>
                        <td data-label='Status'>" . htmlspecialchars($row['status']) . "</td>
                    </tr>";
                }
            } else {
                $no_books_message = "No books found for this student.";
            }
        } else {
            $no_books_message = "You cannot generate a report for an admin.";
        }
    } else {
        $no_books_message = "The provided Unique ID does not exist.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Student Report</title>
    <link rel="stylesheet" href="library.css">
    <!-- Library for taking screenshots -->
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        /* Main heading style */
        main h2 {
            margin-top: 40px;
            margin-bottom: 40px;
            text-align: center;
            color: #4CAF50;
        }

        /* Export buttons container */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            align-items: center;
        }

        /* Base button style */
        .export-btn {
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            transition: background-color 0.3s;
        }

        /* CSV button specific style */
        .csv-btn {
            background-color: #4CAF50;
        }

        .csv-btn:hover {
            background-color: #45a049;
        }

        /* Screenshot button specific style */
        .screenshot-btn {
            background-color: #3F51B5;
            margin-bottom: 15px;
        }

        .screenshot-btn:hover {
            background-color: #303F9F;
        }

        /* Form container style */
        .form-container {
            max-width: 700px;
            margin: 20px auto;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Form input style */
        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Form button style */
        .form-container button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Main content area */
        main {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        /* Table header style */
        table th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }

        /* Table row hover effect */
        table tr:hover {
            background-color: #e8f5e9 !important;
        }

        /* Alternate row coloring */
        table tr:nth-child(even) {
            background-color: #f2f2f2;
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

        /* Responsive styles for mobile */
        @media screen and (max-width: 600px) {
            /* Stack table cells vertically */
            table, thead, tbody, th, td, tr {
                display: block;
            }
            
            /* Hide table headers */
            thead tr {
                display: none;
            }
            
            /* Style each row as a card */
            tr {
                margin-bottom: 12px;
                border: 1px solid #ddd;
                padding: 12px;
                background: white;
            }
            
            /* Style table cells with data labels */
            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 14px 20px;
                text-align: left;
            }

            /* Add data labels before content */
            td::before {
                content: attr(data-label);
                font-weight: bold;
                color: black;
                flex: 1;
                min-width: 120px;
            }

            /* Stack export buttons vertically */
            .export-buttons {
                flex-direction: column;
            }
        }

        /* Mobile menu styles */
        @media screen and (max-width: 768px) {
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
                <li><a href="book_requests.php">Book Requests</a></li>
                <li><a href="add_person.php">Add Person</a></li>
                <li><a href="student_report.php" class="active">Student Report</a></li>
                <li><a href="issue_book.php">Issue Book</a></li>
                <li><a href="issue_report.php">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Student Report</h2>
            
            <!-- Search form -->
            <div class="form-container">
                <form method="GET" action="">
                    <label for="unique_id">Student ID (Unique ID):</label>
                    <input type="text" name="unique_id" id="unique_id" placeholder="Enter Unique ID" required>
                    <button type="submit">Generate Report</button>
                </form>
            </div>
            
            <?php if ($report_data): ?>
                <!-- Export buttons -->
                <div class="export-buttons">
                    <a href="?download_csv=1&unique_id=<?= htmlspecialchars($_GET['unique_id'] ?? '') ?>" class="export-btn csv-btn">
                        Export to CSV
                    </a>
                    <button class="export-btn screenshot-btn" id="screenshot-btn">Download Screenshot</button>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $report_data; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($no_books_message): ?>
                <!-- No results message -->
                <p style="text-align: center;"><?php echo $no_books_message; ?></p>
            <?php endif; ?>
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
                document.querySelector('h2'),
                document.querySelector('.form-container')
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
                link.download = 'student_report_' + new Date().toISOString().slice(0, 10) + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    </script>
</body>
</html>