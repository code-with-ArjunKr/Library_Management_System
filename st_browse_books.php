<?php
// Start the session to check user login status
session_start();

// Check if user is logged in as student, if not redirect to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
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

// SQL query to get all books from database
$sql = "SELECT * FROM books";

// Execute the query
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
        body, h1, h2, h3, p {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            overflow-x: hidden;
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
            width: 100%;
            max-width: 1800px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #4CAF50;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(4, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            min-width: 280px;
            box-sizing: border-box;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card-img-container {
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            padding: 10px;
        }

        .card-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }

        .card-content {
            padding: 15px;
        }

        .card-content h3 {
            margin: 0 0 10px;
            font-size: 1.2em;
            color: #333;
        }

        .card-content p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #666;
        }

        .status {
            font-weight: bold;
            color: #007bff;
        }

        .status.unavailable {
            color: red;
        }

        .request-button {
            margin-top: 10px;
        }

        .request-button a {
            display: inline-block;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .request-button a:hover {
            background-color: green;
        }

        @media (max-width: 1200px) {
            .card-container {
                grid-template-columns: repeat(3, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: repeat(2, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .card-container {
                grid-template-columns: 1fr;
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
                <li>
                    <a href="student.php" <?php if(basename($_SERVER['PHP_SELF']) == 'student.php') { echo 'class="active"'; } ?>>Welcome</a>
                </li>
                <li>
                    <a href="st_my_account.php" <?php if(basename($_SERVER['PHP_SELF']) == 'st_my_account.php') { echo 'class="active"'; } ?>>My Account</a>
                </li>
                <li>
                    <a href="st_browse_books.php" <?php if(basename($_SERVER['PHP_SELF']) == 'st_browse_books.php') { echo 'class="active"'; } ?>>Browse Books</a>
                </li>
                <li>
                    <a href="st_request_book.php" <?php if(basename($_SERVER['PHP_SELF']) == 'st_request_book.php') { echo 'class="active"'; } ?>>Request Book</a>
                </li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

        <main>
            <h2>Browse Books & Request</h2>
            <div class="card-container">
                <?php
                // Check if there are any books in the database
                if ($result->num_rows > 0) {
                    // Loop through each book in the result set
                    while ($row = $result->fetch_assoc()) {
                        // Determine if book is available based on quantity
                        $status = 'Available';
                        $status_class = 'status';
                        if ($row['quantity'] <= 0) {
                            $status = 'Unavailable';
                            $status_class = 'status unavailable';
                        }
                        
                        // Set the image path - use default if cover image doesn't exist
                        $image_url = "images/default.jpg";
                        if (!empty($row['cover_image']) && file_exists($row['cover_image'])) {
                            $image_url = $row['cover_image'];
                        }
                        
                        // URL encode book details for the request link
                        $book_name_encoded = urlencode($row['book_name']);
                        $author_encoded = urlencode($row['author']);
                        ?>
                        <!-- Book Card HTML -->
                        <div class='card'>
                            <div class='card-img-container'>
                                <img src='<?php echo htmlspecialchars($image_url); ?>' alt='<?php echo htmlspecialchars($row['book_name']); ?>'>
                            </div>
                            <div class='card-content'>
                                <h3><?php echo htmlspecialchars($row['book_name']); ?></h3>
                                <p><strong>Author:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
                                <p><strong>Publication:</strong> <?php echo htmlspecialchars($row['publication']); ?></p>
                                <p><strong>Branch:</strong> <?php echo htmlspecialchars($row['branch']); ?></p>
                                <p><strong>Details:</strong> <?php echo htmlspecialchars($row['details']); ?></p>
                                <p><strong>Status:</strong> <span class='<?php echo $status_class; ?>'><?php echo $status; ?></span></p>
                                <p><strong>Quantity:</strong> <?php echo htmlspecialchars($row['quantity']); ?></p>
                                <div class='request-button'>
                                    <a href='st_request_book.php?book_name=<?php echo $book_name_encoded; ?>&author=<?php echo $author_encoded; ?>'>Request</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Display message if no books found
                    echo "<p style='grid-column: 1/-1; text-align: center;'>No books found in the library.</p>";
                }
                ?>
            </div>
        </main>
    </div>
    
    <script>
        // Function to toggle mobile menu visibility
        function toggleMenu() {
            var menu = document.getElementById("menu");
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