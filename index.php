<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");


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

    case "OPTIONS":
      // This is for handling preflight requests
      http_response_code(204); // No content
      exit();

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