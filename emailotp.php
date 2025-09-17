<?php
// Start the session to store OTP and email
session_start();

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

// Handle email check and OTP generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_email'])) {
    // Get and sanitize email from form
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT email FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email exists, generate OTP (4-digit random number)
        $otp = rand(1000, 9999);
        
        // Store email and OTP in session for verification
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp'] = $otp;
        
        // Return success response with OTP
        echo json_encode([
            'status' => 'success',
            'otp' => $otp,
            'message' => 'OTP generated successfully!'
        ]);
    } else {
        // Email not found in database
        echo json_encode([
            'status' => 'error',
            'message' => 'Email not registered! Contact administrator.'
        ]);
    }
    exit();
}

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    // Check if OTP exists in session
    if (!isset($_SESSION['otp'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Session expired. Please restart process.'
        ]);
        exit();
    }
    
    // Get OTP entered by user
    $user_otp = $_POST['otp'];
    
    // Compare user's OTP with session OTP
    if ($user_otp == $_SESSION['otp']) {
        // Mark as verified
        $_SESSION['verified'] = true;
        echo json_encode([
            'status' => 'success',
            'message' => 'OTP verified successfully!'
        ]);
    } else {
        // OTP doesn't match
        echo json_encode([
            'status' => 'error', 
            'message' => 'Invalid OTP! Please try again.'
        ]);
    }
    exit();
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    // Check if user is verified and has email in session
    if (!isset($_SESSION['verified']) || !isset($_SESSION['reset_email'])) {
        die("Invalid request. Please start over.");
    }
    
    // Get email and passwords from form
    $email = $_SESSION['reset_email'];
    $new_password = $conn->real_escape_string($_POST['password']);
    $confirm_password = $conn->real_escape_string($_POST['confirm_password']);

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Passwords do not match!'
        ]);
        exit();
    }

    // Update password in database
    $stmt = $conn->prepare("UPDATE login SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $new_password, $email);
    
    if ($stmt->execute()) {
        // Clear session and show success message
        session_destroy();
        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error updating password: ' . $conn->error
        ]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link rel="stylesheet" href="emailotp.css">
    <!-- EmailJS library for sending emails -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script>
        // Initialize EmailJS with your public key
        emailjs.init("hhlnI0iSWdtATtxYR");
        
        // Function to send OTP
        async function sendOTP() {
            // Get email from input field
            const email = document.getElementById('email').value;
            const messageDiv = document.getElementById('message');

            // Clear previous messages
            messageDiv.innerHTML = '';
            messageDiv.className = '';

            // Validate email format
            if (!validateEmail(email)) {
                showMessage('Please enter a valid email address!', 'error');
                return;
            }

            try {
                // First check if email exists in database
                const checkResponse = await fetch('emailotp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `check_email=1&email=${encodeURIComponent(email)}`
                });
                
                // Parse the JSON response
                const checkData = await checkResponse.json();
                
                // If email not found, show error
                if (checkData.status === 'error') {
                    showMessage(checkData.message, 'error');
                    return;
                }

                // If email exists, send OTP via EmailJS
                emailjs.send("service_s918eoj", "template_ic8hhyb", {
                    to_email: email,
                    message: checkData.otp  // OTP from server response
                }).then(
                    function(response) {
                        // Show success message
                        showMessage(`OTP sent to ${email}!`, 'success');
                        // Show OTP input section
                        document.getElementById('otpSection').style.display = 'block';
                    },
                    function(error) {
                        showMessage('Error sending OTP!', 'error');
                        console.error("EmailJS Error:", error);
                    }
                );

            } catch (error) {
                showMessage('Error processing request. Try again.', 'error');
                console.error('Error:', error);
            }
        }

        // Function to verify OTP
        async function verifyOTP() {
            const otp = document.getElementById('otp').value;
            const messageDiv = document.getElementById('message');

            // Validate OTP format (4 digits)
            if (!otp || otp.length !== 4) {
                showMessage('Please enter a 4-digit OTP!', 'error');
                return;
            }

            try {
                // Send OTP to server for verification
                const verifyResponse = await fetch('emailotp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `verify_otp=1&otp=${encodeURIComponent(otp)}`
                });
                
                const verifyData = await verifyResponse.json();
                
                if (verifyData.status === 'success') {
                    // If OTP is correct
                    showMessage(verifyData.message, 'success');
                    // Show password reset section
                    document.getElementById('passwordSection').style.display = 'block';
                    // Hide OTP section
                    document.getElementById('otpSection').style.display = 'none';
                } else {
                    showMessage(verifyData.message, 'error');
                }
            } catch (error) {
                showMessage('Verification failed. Try again.', 'error');
                console.error('Error:', error);
            }
        }

        // Function to reset password
        async function resetPassword() {
            // Get password values
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            const messageDiv = document.getElementById('message');

            // Check if passwords match
            if (password !== confirmPassword) {
                showMessage('Passwords do not match!', 'error');
                return;
            }

            try {
                // Send new password to server
                const resetResponse = await fetch('emailotp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `reset_password=1&password=${encodeURIComponent(password)}&confirm_password=${encodeURIComponent(confirmPassword)}`
                });
                
                const resetData = await resetResponse.json();
                
                if (resetData.status === 'success') {
                    // If password reset successful
                    showMessage(resetData.message, 'success');
                    // Show back to login button
                    document.getElementById('backToLogin').style.display = 'block';
                    // Hide password section
                    document.getElementById('passwordSection').style.display = 'none';
                } else {
                    showMessage(resetData.message, 'error');
                }
            } catch (error) {
                showMessage('Error resetting password. Try again.', 'error');
                console.error('Error:', error);
            }
        }

        // Function to validate email format
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Function to show messages to user
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = text;
            messageDiv.className = type + '-message';
            messageDiv.style.display = 'block';
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h1>Password Reset Portal</h1>
        <div id="message" class=""></div>

        <!-- Email Input Section -->
        <div class="form-section">
            <input type="email" id="email" placeholder="Enter registered email">
            <button class="btn primary" onclick="sendOTP()">Send OTP</button>
        </div>

        <!-- OTP Verification Section (hidden by default) -->
        <div id="otpSection" class="form-section" style="display: none;">
            <input type="number" id="otp" placeholder="Enter 4-digit OTP">
            <button class="btn secondary" onclick="verifyOTP()">Verify OTP</button>
        </div>

        <!-- Password Reset Section (hidden by default) -->
        <div id="passwordSection" class="form-section" style="display: none;">
            <form onsubmit="event.preventDefault(); resetPassword();">
                <input type="password" name="password" placeholder="New password" required>
                <input type="password" name="confirm_password" placeholder="Confirm password" required>
                <button type="submit" class="btn success">Reset Password</button>
            </form>
        </div>

        <!-- Back to Login Button (hidden by default) -->
        <div id="backToLogin" class="form-section" style="display: none;">
            <a href="login.php" class="btn info">Back to Login Page</a>
        </div>
    </div>
</body>
</html>