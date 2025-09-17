<?php
// Start the session to check admin login status
session_start();

// Check if user is logged in as admin, if not redirect to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
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

// Get book ID from URL parameter and validate it
$book_id = 0;
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
}

// Variables to store book details and messages
$book_details = null;
$success_message = '';
$error_message = '';

// SQL query to fetch book details
$sql = "SELECT id, book_name, author, publication, branch, quantity, details, cover_image FROM books WHERE id = $book_id";
$result = $conn->query($sql);

// Check if book exists
if ($result->num_rows > 0) {
    $book_details = $result->fetch_assoc();
} else {
    // Redirect if book not found
    header("Location: browse_books.php");
    exit();
}

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $book_name = $conn->real_escape_string($_POST['book_name']);
    $author = $conn->real_escape_string($_POST['author']);
    $publication = $conn->real_escape_string($_POST['publication']);
    $branch = $conn->real_escape_string($_POST['branch']);
    $details = $conn->real_escape_string($_POST['details']);
    
    // Count words in details field
    $word_count = str_word_count($details);
    
    // Validate word count
    if ($word_count > 30) {
        $error_message = 'Details field must be 30 words or less. Current count: ' . $word_count;
    }
    
    // Only proceed if no validation errors
    if (empty($error_message)) {
        // Keep current cover image by default
        $cover_image = $book_details['cover_image'];
        
        // Check if new image was uploaded
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'images/';
            $file_name = basename($_FILES['cover_image']['name']);
            $target_path = $upload_dir . $file_name;
            
            // Create upload directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Check if uploaded file is an image
            $check = getimagesize($_FILES['cover_image']['tmp_name']);
            if ($check !== false) {
                // Delete old image if it's not the default
                if ($cover_image != 'images/default.jpg' && file_exists($cover_image)) {
                    unlink($cover_image);
                }
                
                // Try to move uploaded file
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                    $cover_image = $target_path;
                } else {
                    $error_message = 'Sorry, there was an error uploading your file.';
                }
            } else {
                $error_message = 'File is not an image.';
            }
        }
        
        // Update book if no errors
        if (empty($error_message)) {
            $update_sql = "UPDATE books SET 
                          book_name = '$book_name', 
                          author = '$author', 
                          publication = '$publication', 
                          branch = '$branch', 
                          details = '$details', 
                          cover_image = '$cover_image' 
                          WHERE id = $book_id";
            
            if ($conn->query($update_sql)) {
                $success_message = 'Book updated successfully!';
                // Refresh book details
                $result = $conn->query($sql);
                $book_details = $result->fetch_assoc();
            } else {
                $error_message = 'Error updating book: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Edit Book</title>
    <link rel="stylesheet" href="library.css">
    <style>
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

        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-container input,
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .form-container button:hover {
            background-color: #45a049;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
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

        .toggle-form {
            text-align: center;
            margin-top: 20px;
        }
        .toggle-form a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        /* Word count indicator */
        .word-count {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        .word-count.limit-exceeded {
            color: #ff0000;
            font-weight: bold;
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
                <li><a href="/project/admin.php">Admin</a></li>
                <li><a href="/project/add_book.php">Add Book</a></li>
                <li><a href="/project/browse_books.php" class="active">Browse Books</a></li>
                <li><a href="/project/book_requests.php">Book Requests</a></li>
                <li><a href="/project/add_person.php">Add Person</a></li>
                <li><a href="/project/student_report.php">Student Report</a></li>
                <li><a href="/project/issue_book.php">Issue Book</a></li>
                <li><a href="/project/issue_report.php">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <div class="form-container">
                <h2>Edit Book</h2>
                
                <!-- Display success message if exists -->
                <?php if (!empty($success_message)) { ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php } ?>
                
                <!-- Display error message if exists -->
                <?php if (!empty($error_message)) { ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php } ?>
                
                <!-- Book edit form -->
                <form method="POST" action="edit_book.php?book_id=<?php echo $book_id; ?>" enctype="multipart/form-data" id="bookForm">
                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                    
                    <!-- Book Name Field -->
                    <label for="book_name">Book Name*</label>
                    <input type="text" id="book_name" name="book_name" value="<?php echo htmlspecialchars($book_details['book_name']); ?>" required>
                    
                    <!-- Author Field -->
                    <label for="author">Author*</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book_details['author']); ?>" required>
                    
                    <!-- Publication Field -->
                    <label for="publication">Publication*</label>
                    <input type="text" id="publication" name="publication" value="<?php echo htmlspecialchars($book_details['publication']); ?>" required>
                    
                    <!-- Branch Field -->
                    <label for="branch">Branch*</label>
                    <input type="text" id="branch" name="branch" value="<?php echo htmlspecialchars($book_details['branch']); ?>" required>
                    
                    <!-- Details Field with Word Count -->
                    <label for="details">Details</label>
                    <textarea id="details" name="details" rows="3" oninput="updateWordCount()"><?php echo htmlspecialchars($book_details['details']); ?></textarea>
                    <div id="wordCount" class="word-count"><?php echo str_word_count($book_details['details']); ?>/30 words</div>
                    
                    <!-- Current Cover Image -->
                    <label>Current Cover Image:</label>
                    <?php if (!empty($book_details['cover_image'])) { ?>
                        <img src="<?php echo htmlspecialchars($book_details['cover_image']); ?>" alt="Book Cover" style="max-width: 200px; display: block; margin-bottom: 15px;">
                    <?php } ?>
                    
                    <!-- New Cover Image Upload -->
                    <label for="cover_image">New Cover Image (Leave blank to keep current)</label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*">
                    
                    <!-- Submit Button -->
                    <button type="submit">Update Book</button>
                </form>
                
                <!-- Back to Browse Books Link -->
                <div class="toggle-form">
                    <a href="browse_books.php">Back to Browse Books</a>
                </div>
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
        
        // Function to update word count display
        function updateWordCount() {
            var textarea = document.getElementById('details');
            var wordCountDisplay = document.getElementById('wordCount');
            var text = textarea.value.trim();
            var wordCount = 0;
            
            // Count words if text exists
            if (text) {
                wordCount = text.split(/\s+/).length;
            }
            
            // Update display
            wordCountDisplay.textContent = wordCount + '/30 words';
            
            // Change color if over limit
            if (wordCount > 30) {
                wordCountDisplay.classList.add('limit-exceeded');
            } else {
                wordCountDisplay.classList.remove('limit-exceeded');
            }
        }
        
        // Form validation for word count
        document.getElementById('bookForm').addEventListener('submit', function(event) {
            var textarea = document.getElementById('details');
            var text = textarea.value.trim();
            var wordCount = 0;
            
            if (text) {
                wordCount = text.split(/\s+/).length;
            }
            
            if (wordCount > 30) {
                alert('Details field must be 30 words or less. Current count: ' + wordCount);
                event.preventDefault();
            }
        });
        
        // Initialize word count when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateWordCount();
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>