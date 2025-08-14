<?php
$apiUrl = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();

if (empty($apiUrl)) {
    http_response_code(400);
    echo json_encode(['message' => 'API Endpoint missing']);
    exit;
}

$url = 'http://127.0.0.1:8000' . $apiUrl;

$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Return the response
curl_setopt($ch, CURLOPT_HEADER, true);         // Include headers in the response
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); // Set the HTTP method
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);   // Follow redirects

// Set headers
$reqHeaders = [];
foreach ($headers as $key => $value) {
    if (strtolower($key) !== 'host') { //Exclude host header
        $reqHeaders[] = $key . ': ' . $value;
    }
}

// Retrieve and pass the Authorization token for all request
if (isset($_COOKIE['accessToken'])) {
    $token = $_COOKIE['accessToken'];
    $reqHeaders[] = "Authorization: Bearer " . $token;
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

// Set POST data if it's a POST request
if ($method === 'POST') {
    $postData = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
}

//Send request
$response = curl_exec($ch);

// Get header size and separate headers from body
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$responseHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Forward headers to the client
$headerLines = explode("\r\n", trim($responseHeaders));
foreach ($headerLines as $header) {
    // Avoid forwarding the transfer-encoding header.
    if (stripos($header, 'transfer-encoding') === false) {
        header($header);
    }
}
// Force Content-Type to application/json if not successful
if ($responseCode >= 400) {
    header('Content-Type: application/json');
}

curl_close($ch);

// Set HTTP response code
http_response_code($responseCode);

// Output the response body
echo $responseBody;
?>