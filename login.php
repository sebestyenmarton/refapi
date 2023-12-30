<?php
//header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
//header("Access-Control-Allow-Credentials: true");

// Database connection details
$host = 'localhost';
$database = 'reftarka_client';
$username = 'root';
$password = '';

// Get username and password from the POST request
$input = json_decode(file_get_contents('php://input'), true);
$usernameInput = $input['username'] ?? '';
$passwordInput = $input['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// Create a PDO connection to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute the SQL statement to fetch the user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
    $stmt->bindParam(':username', $usernameInput);
    $stmt->bindParam(':password', $passwordInput);
    $stmt->execute();

    // Check if the user exists
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $token = base64_encode(random_bytes(32));

        // Elmentjük a tokent a felhasználó adataival együtt
        $user['token'] = $token;

        // Inicializáljuk a felhasználói ülést és elmentjük az aktuális felhasználó adatait
        session_start();
        $_SESSION['user'] = $user;

        $response = ['status' => 200, 'token' => $token, 'user' => $user, 'message' => 'Sikeres bejelentkezés!'];
        echo json_encode($response);
        return;
    } else {
        http_response_code(401);
        echo json_encode(['status' => 401, 'error' => 'Hibás jelszó vagy felhasználónév!']);
        return;  
    }
} catch (PDOException $e) {
    // Handle database connection error
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 500, 'error' => 'Adatbázis kapcsolódási hiba...']);
}
?>
