<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

include 'database-connection.php';
$objDb = new DatabaseConnect;
$conn = $objDb->connect();

$method = $_SERVER['REQUEST_METHOD'];
switch($method) {
    case "GET":
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10; // Default to 10 records per page
        $offset = ($page - 1) * $pageSize;
        
        // Retrieve records
        $sql = "SELECT * FROM recordings LIMIT $pageSize OFFSET $offset";
        $path = explode('/', $_SERVER['REQUEST_URI']);

        if(isset($path[3]) && is_numeric($path[3])) {
            $sql .= " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $path[3]);
            $stmt->execute();
            $users = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Return records along with total count
        $totalRecords = getTotalRecordCount($conn);
        $response = [
            'totalRecords' => $totalRecords,
            'records' => $users,
        ];

        echo json_encode($response);
        break;

    // Other cases...

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method Not Allowed']);
}

function getTotalRecordCount($conn) {
    // Function to retrieve the total count of records in the table
    $countQuery = "SELECT COUNT(*) as total FROM recordings";
    $countResult = $conn->query($countQuery);
    $countData = $countResult->fetch(PDO::FETCH_ASSOC);
    return $countData['total'];
}
?>