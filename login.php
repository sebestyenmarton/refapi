<?php

header('Content-Type: application/json');

// Database connection details
$host = 'your_database_host';
$username = 'your_database_username';
$password = 'your_database_password';
$database = 'reftarka_client'; // Change to your actual database name

// Simulated user credentials (replace with your actual authentication logic)
$validUsername = 'sledelp';
$validPassword = 'parokia56';

// Get username and password from the POST request
$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

// Create a PDO connection to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute the SQL statement to fetch the user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    // Check if the user exists
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        // Simulate a successful login with a token
        $token = base64_encode(random_bytes(32)); // Generate a random token
        echo json_encode(['status' => 1, 'token' => $token, 'message' => 'Login successful']);
    } else {
        // Simulate a failed login
        https_response_code(401); // Unauthorized
        echo json_encode(['status' => 0, 'error' => 'Invalid credentials']);
    }
} catch (PDOException $e) {
    // Handle database connection error
    https_response_code(500); // Internal Server Error
    echo json_encode(['status' => 0, 'error' => 'Database connection error']);
}

?>
