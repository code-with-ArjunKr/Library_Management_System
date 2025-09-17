<?php
// Start the session to access stored user data
session_start();

// Check if user is logged in and has admin role
// If not, redirect to login page
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
} 
else if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Admin Dashboard</title>
    <link rel="stylesheet" href="library.css">
    <style>
        /* Main content styling */
        main {
            padding: 2rem;
            min-height: calc(100vh - 200px); /* Full height minus header/footer */
            max-width: 1200px;
            margin: 0 auto; /* Center the content */
        }

        /* Dashboard guide grid layout */
        .dashboard-guide {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* 2 columns */
            gap: 1.5rem; /* Space between items */
            margin-top: 2rem;
        }

        /* Each guide section box */
        .guide-section {
            padding: 1.5rem;
            border-left: 4px solid #4CAF50; /* Green left border */
            background-color: #f8f8f8; /* Light gray background */
            border-radius: 4px;
            min-height: 200px; /* Minimum height */
        }

        /* Section headings */
        .guide-section h3 {
            color: #4CAF50; /* Green color */
            margin-top: 0;
            margin-bottom: 1rem;
        }

        /* List styling */
        .guide-list {
            list-style: none; /* Remove bullet points */
            padding-left: 0; /* Remove default padding */
        }

        /* List items */
        .guide-list li {
            padding: 1rem;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); /* Subtle shadow */
            margin-bottom: 0.5rem;
        }

        /* Category labels */
        .menu-category {
            color: #666; /* Dark gray */
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        /* Footer styling */
        footer {
            background-color: #333; /* Dark background */
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: auto; /* Push to bottom */
        }

        footer p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Mobile menu icon (hidden by default) */
        .menu-icon {
            display: none; /* Hidden on desktop */
            font-size: 24px;
            cursor: pointer;
            position: absolute;
            top: 10px;
            left: 20px;
            color: white;
            z-index: 1001; /* Above other elements */
            transition: left 0.3s ease-in-out; /* Smooth animation */
        }

        /* Responsive styles for mobile */
        @media screen and (max-width: 768px) {
            .dashboard-guide {
                grid-template-columns: 1fr;
            }
            
            .menu-icon {
                display: block;
                left: 20px;
            }
            
            nav ul {
                display: none;
                flex-direction: column;
                background-color: #333;
                position: absolute;
                z-index: 2;
                top: 0;
                left: -200px; /* Start hidden off-screen */
                width: 200px;
                height: 100vh;
                padding-top: 50px;
                transition: left 0.3s ease-in-out;
            }
            
            nav ul.active {
                left: 0; /* Slide into view */
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <!-- Menu icon (visible only on mobile) -->
            <span class="menu-icon" onclick="toggleMenu()">☰</span>
            <h1>📚 Digital Library</h1>
        </header>
        
        <nav>
            <ul id="menu">
                <li><a href="admin.php" class="active">Admin</a></li>
                <li><a href="add_book.php">Add Book</a></li>
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
            <h2>Admin Dashboard</h2>
            
            <div class="dashboard-guide">
                <!-- First guide section - Library Management -->
                <div class="guide-section">
                    <h3>📊 Library Management Guide</h3>
                    <ul class="guide-list">
                        <li>
                            <div class="menu-category">Add Book</div>
                            Add new books to the library collection with details
                        </li>
                        <li>
                            <div class="menu-category">Browse Books</div>
                            View complete inventory and manage existing books
                        </li>
                        <li>
                            <div class="menu-category">Book Requests</div>
                            Manage and approve book requests from students
                        </li>
                    </ul>
                </div>

                <!-- Second guide section - User Management -->
                <div class="guide-section">
                    <h3>👥 User Management Guide</h3>
                    <ul class="guide-list">
                        <li>
                            <div class="menu-category">Add Person</div>
                            Register new students or create new admin
                        </li>
                        <li>
                            <div class="menu-category">Student Report</div>
                            View and manage specific student issued book records
                        </li>
                    </ul>
                </div>

                <!-- Third guide section - Transactions -->
                <div class="guide-section">
                    <h3>📚 Transactions Guide</h3>
                    <ul class="guide-list">
                        <li>
                            <div class="menu-category">Issue Book</div>
                            Manage book issuing process manually
                        </li>
                        <li>
                            <div class="menu-category">Issue Report</div>
                            Track all issued books and due dates
                        </li>
                    </ul>
                </div>

                <!-- Fourth guide section - Account Management -->
                <div class="guide-section">
                    <h3>⚙️ Account Management Guide</h3>
                    <ul class="guide-list">
                        <li>
                            <div class="menu-category">Logout</div>
                            Securely end your admin session
                        </li>
                    </ul>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Digital Library Management System | Admin Portal</p>
        </footer>
    </div>
    
    <script>
        // Function to toggle mobile menu visibility
        function toggleMenu() {
            // Get the menu element
            var menu = document.getElementById("menu");
            
            // Toggle 'active' class on click
            if (menu.classList.contains("active")) {
                menu.classList.remove("active"); // Hide menu
            } else {
                menu.classList.add("active"); // Show menu
            }
            
            // Also toggle the menu icon
            var icon = document.querySelector(".menu-icon");
            if (icon.classList.contains("active")) {
                icon.classList.remove("active");
            } else {
                icon.classList.add("active");
            }
        }
    </script>
</body>
</html>