<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");

include 'database-connection.php';
$objDb = new DatabaseConnect;
$conn = $objDb->connect();

$method = $_SERVER['REQUEST_METHOD'];
switch($method) {
  case "GET":
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10; // Default to 10 records per page
    $offset = ($page - 1) * $pageSize;
    // Retrieve records in descending order based on the datum column
    $sql = "SELECT * FROM recordings ORDER BY datum DESC LIMIT $pageSize OFFSET $offset";
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
    

  case "POST":
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
      // Extract the recording ID from the URL
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
      // Extract the recording ID from the URL
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
    header("Access-Control-Allow-Origin: http://localhost:3000"); // Replace with the actual origin of your React app
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header("Access-Control-Allow-Credentials: true"); // Add this line for credentials support
    http_response_code(204); // No content
    exit();



  default:
      http_response_code(405); // Method Not Allowed
      echo json_encode(['error' => 'Method Not Allowed']);
}

function getTotalRecordCount($conn) {
    $countQuery = "SELECT COUNT(*) as total FROM recordings";
    $countResult = $conn->query($countQuery);
    $countData = $countResult->fetch(PDO::FETCH_ASSOC);
    return $countData['total'];
}
?>