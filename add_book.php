<?php
// Start the session to check user login status
session_start();

// Check if user is logged in as admin, if not redirect to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

// Initialize variables with default values
$book_id = 0;
$book_details = null;
$is_edit = false;
$page_title = 'Add New Book';
$success_message = '';
$error_message = '';

// Check if we're editing an existing book
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']); // Convert to integer for safety
    $is_edit = ($book_id > 0);
    $page_title = 'Update Book Quantity';
    
    // If editing, fetch book details from database
    if ($is_edit) {
        $sql = "SELECT id, book_name, author, publication, branch, quantity, details, cover_image FROM books WHERE id = $book_id";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $book_details = $result->fetch_assoc();
        }
    }
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle quantity update form submission
    if (isset($_POST['update_quantity'])) {
        $book_id = intval($_POST['book_id']);
        $quantity_to_add = intval($_POST['quantity']);
        
        // Validate inputs
        if ($book_id > 0 && $quantity_to_add > 0) {
            // Update quantity in database
            $update_sql = "UPDATE books SET quantity = quantity + $quantity_to_add WHERE id = $book_id";
            
            if ($conn->query($update_sql) === TRUE) {
                $success_message = 'Book quantity updated successfully!';
            } else {
                $error_message = 'Error updating quantity: ' . $conn->error;
            }
        } else {
            $error_message = 'Invalid input. Please enter valid numbers.';
        }
    } 
    // Handle new book addition form submission
    elseif (isset($_POST['add_book'])) {
        // Get and sanitize form data
        $book_name = $conn->real_escape_string($_POST['book_name']);
        $author = $conn->real_escape_string($_POST['author']);
        $publication = $conn->real_escape_string($_POST['publication']);
        $branch = $conn->real_escape_string($_POST['branch']);
        $details = $conn->real_escape_string($_POST['details']);
        $quantity = intval($_POST['quantity']);
        
        // Check word count for details field
        $word_count = str_word_count($details);
        if ($word_count > 30) {
            $error_message = 'Details field must be 30 words or less. Current count: ' . $word_count;
        }
        
        // Only proceed if no error from word count validation
        if (empty($error_message)) {
            $cover_image = 'images/default.jpg'; // Default image
            
            // Handle file upload if image was provided
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = 'images/';
                $file_name = basename($_FILES['cover_image']['name']);
                $target_path = $upload_dir . $file_name;
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Verify the file is an actual image
                $check = getimagesize($_FILES['cover_image']['tmp_name']);
                if ($check !== false) {
                    // Try to move the uploaded file
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                        $cover_image = $target_path;
                    } else {
                        $error_message = 'Sorry, there was an error uploading your file.';
                    }
                } else {
                    $error_message = 'File is not an image. Please upload a valid image file.';
                }
            }
            
            // If no errors, insert new book into database
            if (empty($error_message)) {
                $insert_sql = "INSERT INTO books (book_name, author, publication, branch, details, quantity, cover_image) 
                              VALUES ('$book_name', '$author', '$publication', '$branch', '$details', $quantity, '$cover_image')";
                
                if ($conn->query($insert_sql) === TRUE) {
                    $success_message = 'New book added successfully!';
                } else {
                    $error_message = 'Error adding book: ' . $conn->error;
                }
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
    <title>Digital Library - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="library.css">
    <style>
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

        /* Form container styling */
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Form heading */
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        /* Form labels */
        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        /* Form inputs */
        .form-container input,
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* Form buttons */
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

        /* Success and error messages */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
        }

        /* Toggle link between forms */
        .toggle-form {
            text-align: center;
            margin-top: 20px;
        }
        .toggle-form a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        /* Image preview styling */
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 10px 0;
            display: none;
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

        /* Responsive styles for mobile */
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
                <li><a href="add_book.php" class="active">Add Book</a></li>
                <li><a href="browse_books.php">Browse Books</a></li>
                <li><a href="book_requests.php">Book Requests</a></li>
                <li><a href="add_person.php">Add Person</a></li>
                <li><a href="student_report.php">Student Report</a></li>
                <li><a href="issue_book.php">Issue Book</a></li>
                <li><a href="issue_report.php">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <div class="form-container">
                <h2><?php echo $page_title; ?></h2>
                
                <!-- Display success message if exists -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <!-- Display error message if exists -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if ($is_edit && $book_details): ?>
                    <!-- Form for updating book quantity -->
                    <form method="POST" action="add_book.php?book_id=<?php echo $book_id; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                        
                        <label for="book_name">Book Name:</label>
                        <input type="text" id="book_name" name="book_name" value="<?php echo htmlspecialchars($book_details['book_name']); ?>" readonly>
                        
                        <label for="author">Author:</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book_details['author']); ?>" readonly>
                        
                        <label for="publication">Publication:</label>
                        <input type="text" id="publication" name="publication" value="<?php echo htmlspecialchars($book_details['publication']); ?>" readonly>
                        
                        <?php if (!empty($book_details['cover_image'])): ?>
                            <label>Current Cover Image:</label>
                            <img src="<?php echo htmlspecialchars($book_details['cover_image']); ?>" alt="Book Cover" style="max-width: 200px; display: block; margin-bottom: 15px;">
                        <?php endif; ?>
                        
                        <label for="quantity">Quantity to Add:</label>
                        <select id="quantity" name="quantity" required>
                            <?php for ($i = 1; $i <= 50; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        
                        <button type="submit" name="update_quantity">Update Quantity</button>
                    </form>
                    
                    <div class="toggle-form">
                        <a href="add_book.php">Add New Book Instead</a>
                    </div>
                <?php else: ?>
                    <!-- Form for adding new book -->
                    <form method="POST" action="add_book.php" enctype="multipart/form-data" id="bookForm">
                        <label for="book_name">Book Name*</label>
                        <input type="text" id="book_name" name="book_name" required>
                        
                        <label for="author">Author*</label>
                        <input type="text" id="author" name="author" required>
                        
                        <label for="publication">Publication*</label>
                        <input type="text" id="publication" name="publication" required>
                        
                        <label for="branch">Branch*</label>
                        <input type="text" id="branch" name="branch" required>
                        
                        <label for="details">Details</label>
                        <textarea id="details" name="details" rows="3" oninput="updateWordCount()"></textarea>
                        <div id="wordCount" class="word-count">0/30 words</div>
                        
                        <label for="quantity">Initial Quantity*</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                        
                        <label for="cover_image">Cover Image</label>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*" onchange="previewImage(this)">
                        <img id="imagePreview" class="image-preview" src="#" alt="Image Preview">
                        
                        <button type="submit" name="add_book">Add New Book</button>
                    </form>
                    
                    <?php if (isset($_GET['book_id'])): ?>
                        <div class="toggle-form">
                            <a href="add_book.php">Add New Book Instead</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Toggle mobile menu visibility
        function toggleMenu() {
            var menu = document.getElementById("menu");
            menu.classList.toggle("active");
        }
        
        // Preview selected image before upload
        function previewImage(input) {
            var preview = document.getElementById('imagePreview');
            var file = input.files[0];
            var reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }
        
        // Update word count as user types
        function updateWordCount() {
            var textarea = document.getElementById('details');
            var wordCountDisplay = document.getElementById('wordCount');
            var text = textarea.value.trim();
            var words = text ? text.split(/\s+/) : [];
            var wordCount = words.length;
            
            wordCountDisplay.textContent = wordCount + '/30 words';
            
            if (wordCount > 30) {
                wordCountDisplay.classList.add('limit-exceeded');
            } else {
                wordCountDisplay.classList.remove('limit-exceeded');
            }
        }
        
        // Validate word count before form submission
        document.getElementById('bookForm').addEventListener('submit', function(event) {
            var textarea = document.getElementById('details');
            var text = textarea.value.trim();
            var words = text ? text.split(/\s+/) : [];
            var wordCount = words.length;
            
            if (wordCount > 30) {
                alert('Details field must be 30 words or less. Current count: ' + wordCount);
                event.preventDefault();
            }
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>