<?php
// Start the session to store user data across pages
session_start();

// Database connection details
$servername = "localhost"; // Server name (usually localhost)
$username = "root";        // Database username
$password = "";            // Database password (empty for localhost)
$dbname = "if0_38414067_library_management_system"; // Database name

// Create connection to MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); // Stop script and show error
}

// SQL query to create login table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS `login` (
    `email` VARCHAR(255) NOT NULL,
    `unique_id` VARCHAR(15) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `branch` ENUM('BCA', 'BBA', 'B.Sc IT', 'BBM') DEFAULT 'BCA',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `password` VARCHAR(255) NOT NULL,
    `role` TEXT NOT NULL,
    PRIMARY KEY (`unique_id`)
)";

// Execute the table creation query
if ($conn->query($createTableQuery) === FALSE) {
    die("Error creating table: " . $conn->error); // Stop if table creation fails
}

// Check if form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and remove extra spaces
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']); // Get role (student or admin)
    
    // Prepare SQL query to check user credentials
    $sql = "SELECT unique_id, name, role FROM login WHERE email = ? AND password = ? AND role = ? AND unique_id IS NOT NULL";
    
    // Create prepared statement to prevent SQL injection
    $stmt = $conn->prepare($sql);
    
    // Bind parameters to the statement (s = string)
    $stmt->bind_param("sss", $email, $password, $role);
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Check if any rows were returned
    if ($result->num_rows > 0) {
        // Fetch user data as associative array
        $user = $result->fetch_assoc();
        
        // Store user data in session variables
        $_SESSION['unique_id'] = $user['unique_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect user based on their role
        if ($user['role'] == 'student') {
            header("Location: student.php"); // Go to student dashboard
            exit();
        } 
        else if ($user['role'] == 'admin') {
            header("Location: admin.php"); // Go to admin dashboard
            exit();
        } 
        else {
            header("Location: login.php"); // Stay on login if role is invalid
            exit();
        }
    } 
    else {
        // Set error message if login fails
        $_SESSION['loginError'] = "Invalid email, password, or role.";
        
        // Redirect back to login page with role parameter
        header("Location: login.php?role=$role");
        exit();
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Digital Library - Login</title>
  <link rel="stylesheet" href="login.css"> 
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    /* Simple CSS for forgot password link */
    .forgot-password {
      display: block;
      margin-top: 15px;
      text-align: center;
      color: #2196F3;
      text-decoration: none;
      font-size: 14px;
    }

    .forgot-password:hover {
      color: #1976D2;     
      text-decoration: underline;
      transform: translateY(-1px);
    }
    
    .forgot-password::after {
      content: " 🔑";
    }
  </style>
</head>
<body>
  <h1>Welcome to 📚 Digital Library</h1>
  <p>Please log in to access the system</p>

  <div class="container">
    <!-- Student Login Form -->
    <div class="login-box student">
      <h2>Student Login</h2>
      <form action="login.php" method="POST">
        <input type="hidden" name="role" value="student"> <!-- Hidden role field -->
        
        <div class="input-group">
          <label for="student-email">Email </label>
          <input type="email" name="email" id="student-email" placeholder="Your Email *" required>
        </div>
        
        <div class="input-group">
          <label for="student-password">Password </label>
          <div class="password-wrapper">
            <input type="password" name="password" id="student-password" placeholder="Your Password *" required>
            <span id="student-eye" class="eye-icon">👁️</span> <!-- Password toggle icon -->
          </div>
        </div>
        
        <button type="submit" class="btn">Login</button>
        <a href="emailotp.php" class="forgot-password">Forgot Password?</a>
      </form>
      
      <!-- Show error message if student login fails -->
      <?php 
      if (isset($_SESSION['loginError']) && isset($_GET['role']) && $_GET['role'] === 'student') { 
        echo '<p class="error-message">' . $_SESSION['loginError'] . '</p>';
        unset($_SESSION['loginError']); // Clear the error after showing
      } 
      ?>
    </div>

    <!-- Admin Login Form -->
    <div class="login-box admin">
      <h2>Admin Login</h2>
      <form action="login.php" method="POST">
        <input type="hidden" name="role" value="admin"> <!-- Hidden role field -->
        
        <div class="input-group">
          <label for="admin-email">Email </label>
          <input type="email" name="email" id="admin-email" placeholder="Your Email *" required>
        </div>
        
        <div class="input-group">
          <label for="admin-password">Password </label>
          <div class="password-wrapper">
            <input type="password" name="password" id="admin-password" placeholder="Your Password *" required>
            <span id="admin-eye" class="eye-icon">👁️</span> <!-- Password toggle icon -->
          </div>
        </div>
        
        <button type="submit" class="btn">Login</button>
      </form>
      
      <!-- Show error message if admin login fails -->
      <?php 
      if (isset($_SESSION['loginError']) && isset($_GET['role']) && $_GET['role'] === 'admin') { 
        echo '<p class="error-message">' . $_SESSION['loginError'] . '</p>';
        unset($_SESSION['loginError']); // Clear the error after showing
      } 
      ?>
    </div>
  </div>

  <script>
    // JavaScript to toggle password visibility for student
    document.getElementById('student-eye').addEventListener('click', function() {
      var passwordField = document.getElementById('student-password');
      
      // Check current type and toggle between password/text
      if (passwordField.type === 'password') {
        passwordField.type = 'text'; // Show password
      } else {
        passwordField.type = 'password'; // Hide password
      }
    });

    // JavaScript to toggle password visibility for admin
    document.getElementById('admin-eye').addEventListener('click', function() {
      var passwordField = document.getElementById('admin-password');
      
      // Check current type and toggle between password/text
      if (passwordField.type === 'password') {
        passwordField.type = 'text'; // Show password
      } else {
        passwordField.type = 'password'; // Hide password
      }
    });
  </script>
</body>
</html>