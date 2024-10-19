<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'auth_system';

// Connect to database
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    response(false, "Connection failed: " . $e->getMessage());
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Function to send response
function response($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, 'Invalid request method');
}

// Get POST data
try {
    $data = json_decode(file_get_contents('php://input'), true);
} catch (Exception $e) {
    response(false, 'Invalid JSON data');
}

// Validate required fields
if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    response(false, 'Missing required fields');
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

// Validate input
if (empty($name) || empty($email) || empty($password)) {
    response(false, 'All fields are required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    response(false, 'Invalid email format');
}

if (strlen($password) < 8) {
    response(false, 'Password must be at least 8 characters long');
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        response(false, 'Email already registered');
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, created_at) 
        VALUES (?, ?, ?, NOW())
    ");

    // Execute statement with parameters
    $stmt->execute([$name, $email, $password_hash]);

    // Check if insertion was successful
    if ($stmt->rowCount() > 0) {
        response(true, 'Registration successful');
    } else {
        response(false, 'Registration failed');
    }

} catch(PDOException $e) {
    response(false, 'Registration failed: ' . $e->getMessage());
}
?>