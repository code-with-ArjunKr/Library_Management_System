<?php
// Start the session to access user data
session_start();

// Check if user is logged in as a student
if (!isset($_SESSION['role'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
} else if ($_SESSION['role'] !== 'student') {
    // If logged in but not as student, redirect to login
    header("Location: login.php");
    exit();
}

// Get student information from session
$unique_id = $_SESSION['unique_id'];
$name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="library.css">
    <style>
        /* Main Content Styling */
        main {
            padding: 2rem;
            min-height: calc(100vh - 200px);
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-guide {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        .guide-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-left: 4px solid #4CAF50;
            background-color: #f8f8f8;
            border-radius: 4px;
        }

        .guide-section h3 {
            color: #4CAF50;
            margin-top: 0;
            margin-bottom: 1rem;
        }

        .guide-list {
            list-style: none;
            padding-left: 0;
        }

        .guide-list li {
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        /* Footer Styling */
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1rem;
            position: relative;
            margin-top: auto;
        }

        footer p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Hamburger Menu Fixes */
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

        nav ul {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
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
            }

            .dashboard-guide {
                padding: 1rem;
            }
            
            .guide-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <!-- Mobile menu button -->
        <span class="menu-icon" onclick="toggleMenu()">☰</span>
        <h1>📚 Digital Library</h1>
    </header>

    <nav>
        <ul id="menu">
            <li><a href="student.php" class="active">Welcome</a></li>
            <li><a href="st_my_account.php">My Account</a></li>
            <li><a href="st_browse_books.php">Browse Books</a></li>
            <li><a href="st_request_book.php">Request Book</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <main>
        <!-- Welcome message with student name -->
        <h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2>
        <p class="highlight"><strong>Your Unique ID:</strong> <?php echo htmlspecialchars($unique_id); ?></p>
        
        <!-- Dashboard guide sections -->
        <div class="dashboard-guide">
            <!-- Menu Guide Section -->
            <div class="guide-section">
                <h3>📋 Menu Guide</h3>
                <ul class="guide-list">
                    <li>
                        <strong>My Account:</strong> See your account details
                        and view your book requests with status.
                    </li>
                    <li>
                        <strong>Browse Books:</strong> browse and request your 
                         books.
                    </li>
                    <li>
                        <strong>Request Book:</strong> manually search our book collection and 
                        submit requests for books you'd like to borrow.
                    </li>
                    <li>
                        <strong>Logout:</strong> Securely logout from webpage in one click!.
                    </li>
                </ul>
            </div>

            <!-- Quick Guide Section -->
            <div class="guide-section">
                <h3>ℹ️ Quick Guide</h3>
                <ul class="guide-list">
                    <li>Use the menu above to navigate between sections or click on this icon ☰ on top left of your screen (for smartphone/ios users only)</li>
                    <li>Check due dates regularly to avoid late fees</li>
                    <li>Contact library staff for any account inquiries</li>
                    <li>Remember to logout when finished your requests</li>
                </ul>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Digital Library Management System | Student Portal</p>
    </footer>
    
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