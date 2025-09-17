<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "if0_38414067_library_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $unique_id = trim($_POST['unique_id']);
    $branch = trim($_POST['branch']); 

    $check_sql = "SELECT * FROM login WHERE unique_id = ? OR email = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ss", $unique_id, $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Error: Unique ID or Email already exists. Please use a different one.'); window.location.href='add_person.php';</script>";
        exit();
    }

    $insert_sql = "INSERT INTO login (unique_id, name, email, password, role, branch) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssssss", $unique_id, $name, $email, $password, $role, $branch);

    if ($stmt->execute()) {
        echo "<script>alert('Person added successfully!'); window.location.href='add_person.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Person</title>
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

        /* Enhanced Form Styling */
        main {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color:#4CAF50;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Changed from checkbox to radio button styling */
        input[type="radio"] {
            margin-right: 10px;
        }

        .branch-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .branch-options label {
            font-weight: normal;
            display: flex;
            align-items: center;
        }

        button {
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #218838;
        }

        @media screen and (max-width: 600px) {
            main {
                padding: 15px;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"],
            select {
                font-size: 14px;
            }

            button {
                font-size: 14px;
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
                <li><a href="/project/admin.php">Admin</a></li>
                <li><a href="/project/add_book.php">Add Book</a></li>
                <li><a href="/project/browse_books.php">Browse Books</a></li>
                <li><a href="/project/book_requests.php">Book Requests</a></li>
                <li><a href="/project/add_person.php" class="active">Add Person</a></li>
                <li><a href="/project/student_report.php">Student Report</a></li>
                <li><a href="/project/issue_book.php">Issue Book</a></li>
                <li><a href="/project/issue_report.php">Issue Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <main>
            <h2>Add New Person</h2>
            <form action="add_person.php" method="POST">
                <label for="unique_id">Unique ID:</label>
                <input type="text" name="unique_id" required>

                <label for="name">Name:</label>
                <input type="text" name="name" required>

                <label>Select Branch:</label>
                <div class="branch-options">
                    <label><input type="radio" name="branch" value="BCA" checked> BCA</label>
                    <label><input type="radio" name="branch" value="BBM"> BBM</label>
                    <label><input type="radio" name="branch" value="B.Sc IT"> B.Sc IT</label>
                    <label><input type="radio" name="branch" value="BBA"> BBA</label>
                </div>

                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>

                <label for="role">Role:</label>
                <select name="role" required>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>

                <button type="submit">Add Person</button>
            </form>
        </main>
    </div>
    <script>
        function toggleMenu() {
            document.getElementById("menu").classList.toggle("active");
        }
    </script>
</body>
</html>