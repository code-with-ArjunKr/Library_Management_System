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

// Handle book deletion if delete_id is provided in URL
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // First get the book details including cover image path
    $sql = "SELECT cover_image FROM books WHERE id = $delete_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['cover_image'];
        
        // Delete the image file if it's not the default image
        if ($image_path != 'images/default.jpg' && file_exists($image_path)) {
            unlink($image_path); // Delete the file from server
        }
        
        // Delete the book record from database
        $delete_sql = "DELETE FROM books WHERE id = $delete_id";
        if ($conn->query($delete_sql) === TRUE) {
            // Redirect with success message
            header("Location: browse_books.php?deleted=1");
            exit();
        } else {
            // Redirect with error message
            header("Location: browse_books.php?error=delete_failed");
            exit();
        }
    }
}

// Get all books from database
$sql = "SELECT id, book_name, author, publication, branch, quantity, details, cover_image FROM books";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Browse Books</title>
    <link rel="stylesheet" href="library.css">
    <style>
        /* Reset some default styles */
        body, h1, h2, h3, p {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Main container styling */
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            overflow-x: hidden;
        }

        /* Header styling */
        header {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 1.2rem 0;
            font-size: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Navigation bar styling */
        nav {
            background-color: #333;
            color: white;
            display: flex;
            justify-content: center;
        }

        /* Navigation list styling */
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

        /* Navigation links styling */
        nav ul li a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            transition: background 0.3s;
        }

        /* Hover and active states for nav links */
        nav ul li a:hover,
        nav ul li a.active {
            background-color: #4CAF50;
        }

        /* Main content area styling */
        main {
            background-color: white;
            margin: 2rem auto;
            padding: 2rem;
            width: 100%;
            max-width: 1800px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Page heading */
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #4CAF50;
        }

        /* Card grid layout */
        .card-container {
            display: grid;
            grid-template-columns: repeat(4, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Individual book card styling */
        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            min-width: 280px;
            box-sizing: border-box;
            position: relative;
        }

        /* Card hover effect */
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        /* Book cover image styling */
        .card img {
            width: 100%;
            height: 350px;
            object-fit: contain;
            display: block;
            padding-top: 10px;
        }

        /* Card content area */
        .card-content {
            padding: 15px;
            padding-bottom: 50px;
        }

        /* Book title styling */
        .card-content h3 {
            margin: 0 0 10px;
            font-size: 1.2em;
            color: #333;
        }

        /* Book details text */
        .card-content p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #666;
        }

        /* Available status styling */
        .card-content .status {
            font-weight: bold;
            color: #007bff;
        }

        /* Unavailable status styling */
        .card-content .status.unavailable {
            color: red;
        }

        /* Action buttons container */
        .card-buttons {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
        }

        /* Base button styling */
        .action-button {
            padding: 8px 16px;
            color: white;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-size: 0.9em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* Add quantity button */
        .add-button {
            background-color: #007bff;
            min-width: 100px; 
            padding: 8px 10px;
        }

        .add-button:hover {
            background-color: #0056b3;
        }

        /* Edit button */
        .edit-button {
            background-color: #ffc107;
        }

        .edit-button:hover {
            background-color: #e0a800;
        }

        /* Delete button */
        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        /* Success and error message styling */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
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

        /* Responsive design adjustments */
        @media (max-width: 1200px) {
            .card-container {
                grid-template-columns: repeat(3, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: repeat(2, minmax(250px, 1fr));
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

        @media (max-width: 480px) {
            .card-container {
                grid-template-columns: 1fr;
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
                <li><a href="browse_books.php" class="active">Browse Books</a></li>
                <li><a href="book_requests.php">Book Requests</a></li>
                <li><a href="add_person.php">Add Person</a></li>
                <li><a href="student_report.php">Student Report</a></li>
                <li><a href="issue_book.php">Issue Book</a></li>
                <li><a href="issue_report.php">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Browse & Manage Book Collection</h2>
            
            <!-- Display success/error messages -->
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                <div class="alert alert-success">Book deleted successfully!</div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] == 'delete_failed'): ?>
                <div class="alert alert-danger">Failed to delete the book. Please try again.</div>
            <?php endif; ?>
            
            <!-- Book cards container -->
            <div class="card-container">
                <?php
                // Check if there are any books
                if ($result->num_rows > 0) {
                    // Loop through each book and display as a card
                    while ($row = $result->fetch_assoc()) {
                        // Determine availability status
                        $status = ($row['quantity'] > 0) ? 'Available' : 'Unavailable';
                        $status_class = ($status == 'Unavailable') ? 'status unavailable' : 'status';

                        // Get cover image path, use default if not found
                        $image_url = !empty($row['cover_image']) ? $row['cover_image'] : "images/default.jpg";
                        
                        // Verify the image exists
                        if (!file_exists($image_url)) {
                            $image_url = "images/default.jpg";
                        }

                        // Display book card
                        echo "
                        <div class='card'>
                            <img src='" . htmlspecialchars($image_url) . "' alt='" . htmlspecialchars($row['book_name']) . "'>
                            <div class='card-content'>
                                <h3>" . htmlspecialchars($row['book_name']) . "</h3>
                                <p><strong>Author:</strong> " . htmlspecialchars($row['author']) . "</p>
                                <p><strong>Publication:</strong> " . htmlspecialchars($row['publication']) . "</p>
                                <p><strong>Branch:</strong> " . htmlspecialchars($row['branch']) . "</p>
                                <p><strong>Details:</strong> " . htmlspecialchars($row['details']) . "</p>
                                <p><strong>Status:</strong> <span class='" . $status_class . "'>" . $status . "</span></p>
                                <p><strong>Quantity:</strong> " . htmlspecialchars($row['quantity']) . "</p>
                                <div class='card-buttons'>
                                    <a href='add_book.php?book_id=" . $row['id'] . "' class='action-button add-button'>Add Quantity</a>
                                    <a href='edit_book.php?book_id=" . $row['id'] . "' class='action-button edit-button'>Edit</a>
                                    <a href='browse_books.php?delete_id=" . $row['id'] . "' class='action-button delete-button' onclick='return confirm(\"Are you sure you want to delete this book?\")'>Delete</a>
                                </div>
                            </div>
                        </div>";
                    }
                } else {
                    // Display message if no books found
                    echo "<p style='text-align:center; width:100%;'>No books found in the library.</p>";
                }
                ?>
            </div>
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