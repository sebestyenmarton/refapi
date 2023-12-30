<?php
function verifyUserToken() {
  $receivedToken = getBearerToken();
  if (!$receivedToken) {
      $errorMessage = 'Missing or invalid token.';
      error_log($errorMessage);
      echo json_encode(['status' => 401, 'error' => $errorMessage, 'receivedToken' => $receivedToken]);
      return false;
  }

  session_start();
  $loggedInUser = $_SESSION['user'] ?? null;

  if (!$loggedInUser || $receivedToken !== $loggedInUser['token']) {
      $errorMessage = 'User authentication failed.';
      error_log($errorMessage);
      echo json_encode(['status' => 401, 'error' => $errorMessage, 'receivedToken' => $receivedToken, 'expectedToken' => ($loggedInUser ? $loggedInUser['token'] : null)]);
      return false;
  }

  return true;
}

function getBearerToken() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }

    $authHeader = $headers['Authorization'];
    $tokenParts = explode(' ', $authHeader);
    if (count($tokenParts) !== 2 || $tokenParts[0] !== 'Bearer') {
        return null;
    }

    return $tokenParts[1];
}
?>
