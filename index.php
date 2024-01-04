<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Content-Type: application/json');

include 'database-connection.php';
include 'auth.php';  // Include the authentication functions

$objDb = new DatabaseConnect;
$conn = $objDb->connect();

$method = $_SERVER['REQUEST_METHOD'];
switch($method) {
    case "GET":
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) && is_numeric($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10; // Default to 10 records per page
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT * FROM recordings WHERE 1"; // Start the query

        // Check if category and subcategory are provided and not empty
        if (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] !== 'Összes') {
            $sql .= " AND tipus = :category";
        }

        if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
            $sql .= " AND kategoria = :subcategory";
        }

        // Continue with the rest of your query...
        $sql .= " ORDER BY datum DESC LIMIT :pageSize OFFSET :offset";
        $stmt = $conn->prepare($sql);

        // Bind parameters if they are set
        if (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] !== 'Összes') {
            $stmt->bindParam(':category', $_GET['category']);
        }

        if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
            $stmt->bindParam(':subcategory', $_GET['subcategory']);
        }

        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            $totalRecords = getTotalRecordCount($conn);
            $response = [
                'totalRecords' => $totalRecords,
                'records' => $users,
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            echo '<html><head></head><body><h1>HTML response for page loading</h1></body></html>';
        }
        break;
    
    case "POST":
        // Check if the logged-in user's token matches the sent token
        if (!verifyUserToken()) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'error' => 'Invalid token. Please log in again!']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO recordings (tipus, cim, link, szolgal, datum, kategoria) 
                VALUES (:tipus, :cim, :link, :szolgal, :datum, :kategoria)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':tipus', $input['tipus']);
        $stmt->bindParam(':cim', $input['cim']);
        $stmt->bindParam(':link', $input['link']);
        $stmt->bindParam(':szolgal', $input['szolgal']);
        $stmt->bindParam(':datum', $input['datum']);
        $stmt->bindParam(':kategoria', $input['kategoria']);
        if ($stmt->execute()) {
            $response = ['status' => 1, 'message' => 'Record added successfully.'];
        } else {
            $response = ['status' => 0, 'message' => 'Failed to add record.'];
        }
        echo json_encode($response);
        break;

    case "PUT":
        // Check if the logged-in user's token matches the sent token
        if (!verifyUserToken()) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'error' => 'Invalid token. Please log in again!']);
            return;
        }

        $urlParts = explode('/', $_SERVER['REQUEST_URI']);
        $recordingId = end($urlParts);

        $input = json_decode(file_get_contents('php://input'), true);
        $sql = "UPDATE recordings 
                SET tipus = :tipus, cim = :cim, link = :link, szolgal = :szolgal, datum = :datum, kategoria = :kategoria
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $recordingId);
        $stmt->bindParam(':tipus', $input['tipus']);
        $stmt->bindParam(':cim', $input['cim']);
        $stmt->bindParam(':link', $input['link']);
        $stmt->bindParam(':szolgal', $input['szolgal']);
        $stmt->bindParam(':datum', $input['datum']);
        $stmt->bindParam(':kategoria', $input['kategoria']);
        try {
            $stmt->execute();
            $rowCount = $stmt->rowCount();  // Check the number of affected rows
            if ($rowCount > 0) {
                $response = ['status' => 1, 'message' => 'Record updated successfully.'];
            } else {
                $response = ['status' => 0, 'message' => 'Record not found or no changes made.'];
            }
        } catch (PDOException $e) {
            $response = ['status' => 0, 'message' => 'Error updating record: ' . $e->getMessage()];
        }
        echo json_encode($response);
        break;

    case "DELETE":
        // Check if the logged-in user's token matches the sent token
        if (!verifyUserToken()) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'error' => 'Invalid token. Please log in again!']);
            return;
        }

        $urlParts = explode('/', $_SERVER['REQUEST_URI']);
        $recordingId = end($urlParts);

        $sql = "DELETE FROM recordings WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $recordingId);
        try {
            $stmt->execute();
            $rowCount = $stmt->rowCount();  // Check the number of affected rows
            if ($rowCount > 0) {
                $response = ['status' => 1, 'message' => 'Record deleted successfully.'];
            } else {
                $response = [
                    'status' => 0,
                    'message' => 'Record not found or already deleted.',
                    'debug' => [
                        'input_id' => $recordingId,
                        'rowCount' => $rowCount,
                    ],
                ];
            }
        } catch (PDOException $e) {
            $response = [
                'status' => 0,
                'message' => 'Error deleting record: ' . $e->getMessage(),
                'debug' => [
                    'input_id' => $recordingId,
                ],
            ];
        }
        echo json_encode($response);
        break;

    case "OPTIONS":
        // This is for handling preflight requests
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        http_response_code(204); // No content
        exit();

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method Not Allowed']);
}

function getTotalRecordCount($conn) {
  $sql = "SELECT COUNT(*) as total FROM recordings WHERE 1"; // Start the query
  // Check if category and subcategory are provided and not empty
  if (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] !== 'Összes') {
      $sql .= " AND tipus = :category";
  }
  if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
      $sql .= " AND kategoria = :subcategory";
  }
  $stmt = $conn->prepare($sql);

  // Bind parameters if they are set
  if (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] !== 'Összes') {
      $stmt->bindParam(':category', $_GET['category']);
  }
  if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
      $stmt->bindParam(':subcategory', $_GET['subcategory']);
  }

  $stmt->execute();
  $countData = $stmt->fetch(PDO::FETCH_ASSOC);
  return $countData['total'];
}

function getDefaultRecordings($conn, $pageSize, $offset) {
    $sql = "SELECT * FROM recordings ORDER BY datum DESC LIMIT :pageSize OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
