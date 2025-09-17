<?php
// Start the session to access user data
session_start();

// Check if user is logged in as student, if not redirect to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// Check if user has a valid unique_id in session
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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student's unique_id from session
$unique_id = $_SESSION['unique_id'];

// Get all available books (quantity > 0)
$book_query = "SELECT book_name, author FROM books WHERE quantity > 0";
$book_result = $conn->query($book_query);

// Get book details from URL if coming from browse page
$selected_book = '';
$selected_author = '';
if (isset($_GET['book_name'])) {
    $selected_book = urldecode($_GET['book_name']);
}
if (isset($_GET['author'])) {
    $selected_author = urldecode($_GET['author']);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $book_name = $_POST['book_name'];
    $author = $_POST['author'];
    
    // First check if book is still available
    $check_availability = $conn->prepare("SELECT * FROM books WHERE book_name = ? AND author = ? AND quantity > 0");
    $check_availability->bind_param("ss", $book_name, $author);
    $check_availability->execute();
    $availability_result = $check_availability->get_result();
    
    if ($availability_result->num_rows > 0) {
        // Book is available, insert request
        $insert_request = $conn->prepare("INSERT INTO book_requests (unique_id, book_name, author, status) VALUES (?, ?, ?, 'pending')");
        $insert_request->bind_param("sss", $unique_id, $book_name, $author);
        
        if ($insert_request->execute()) {
            // Request successful
            echo "<script>alert('Book request submitted successfully!'); window.location.href='st_request_book.php';</script>";
        } else {
            // Request failed
            echo "<script>alert('Failed to submit request. Please try again.');</script>";
        }
    } else {
        // Book is not available
        echo "<script>alert('The selected book by this author is not available.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Book</title>
    <link rel="stylesheet" href="library.css">
    <style>
        main {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        select, button {
            width: 100%;
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: green;
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
    <header>
        <span class="menu-icon" onclick="toggleMenu()">☰</span>
        <h1>📚 Digital Library</h1>
    </header>

    <nav>
        <ul id="menu">
            <li><a href="/project/student.php">Welcome</a></li>
            <li><a href="/project/st_my_account.php">My Account</a></li>
            <li><a href="/project/st_browse_books.php">Browse Books</a></li>
            <li><a href="/project/st_request_book.php" class="active">Request Book</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <main>
        <h2>Request a Book</h2>
        <p style="color:#4CAF50;margin-bottom:10px;">You can either select a book manually or choose one from the <u>Browse Books</u> page to auto-fill the details below.</p>
        
        <form method="POST" action="st_request_book.php">
            <!-- Book selection dropdown -->
            <label for="book_name">Select Book:</label>
            <select name="book_name" id="book_name" required>
                <option value="">-- Select Book --</option>
                <?php
                // Reset pointer to beginning of result set
                $book_result->data_seek(0);
                
                // Array to track already displayed books (avoid duplicates)
                $displayed_books = array();
                
                // Loop through available books
                while ($book = $book_result->fetch_assoc()) {
                    $book_name = htmlspecialchars($book['book_name']);
                    
                    // Only show each book once in the dropdown
                    if (!in_array($book_name, $displayed_books)) {
                        // Check if this book was pre-selected from browse page
                        $selected = '';
                        if ($book_name == $selected_book) {
                            $selected = 'selected';
                        }
                        
                        echo "<option value='$book_name' $selected>$book_name</option>";
                        $displayed_books[] = $book_name;
                    }
                }
                ?>
            </select>

            <!-- Author selection dropdown -->
            <label for="author">Select Author:</label>
            <select name="author" id="author" required>
                <option value="">-- Select Author --</option>
                <?php
                // Reset pointer to beginning of result set again
                $book_result->data_seek(0);
                
                // Loop through available books to show authors
                while ($book = $book_result->fetch_assoc()) {
                    $author = htmlspecialchars($book['author']);
                    
                    // Check if this author was pre-selected from browse page
                    $selected = '';
                    if ($author == $selected_author) {
                        $selected = 'selected';
                    }
                    
                    echo "<option value='$author' $selected>$author</option>";
                }
                ?>
            </select>

            <button type="submit">Submit Your Request</button>
        </form>
    </main>

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
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>