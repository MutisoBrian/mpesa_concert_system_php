<?php

// Set error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session for potential future use
session_start();

// Database configuration
$db_config = [
    'host' => 'localhost',
    'dbname' => 'concert_tickets',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// M-Pesa configuration
$mpesa_config = [
    'base_url' => 'https://sandbox.safaricom.co.ke',
    'access_token_url' => 'oauth/v1/generate?grant_type=client_credentials',
    'stk_push_url' => 'mpesa/stkpush/v1/processrequest',
    'stk_query_url' => 'mpesa/stkpushquery/v1/query',
    'business_short_code' => '174379',
    'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
    'till_number' => '174379',
    'callback_url' => 'https://mydomain.com/callback',
    'consumer_key' => 'E7RkuNKKVFG3p2nWjEM78RcbFOwH2qb5UHpGvpOhzodFGbHV',
    'consumer_secret' => 'tQw44mUODFBqUk25oS5NweJBMrlvdWwkYdap6P3895kekW2LmLFcHT4Lvjr4figm'
];

/**
 * PDO Database Connection
 *
 * Establishes a connection to the database using PDO.
 *
 * @return PDO Database connection
 */
function getDbConnection() {
    global $db_config;

    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $db_config['username'], $db_config['password'], $options);
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

function getConcerts() {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT concertId, title, price, imageUrl FROM concert ORDER BY showTime ASC");
    return $stmt->fetchAll();
}


/**
 * Create Database Tables
 *
 * Creates all necessary database tables if they don't exist.
 */
function createDatabaseTables() {
    $pdo = getDbConnection();

    // Concert table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS concert (
            concertId INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            showTime DATETIME NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            max_tickets INT NOT NULL DEFAULT 100,
            imageUrl VARCHAR(255),
            dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Ticket table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ticket (
            ticketId INT AUTO_INCREMENT PRIMARY KEY,
            concertId INT NOT NULL,
            customerName VARCHAR(255) NOT NULL,
            phoneNumber VARCHAR(20) NOT NULL,
            quantity INT NOT NULL,
            totalAmount DECIMAL(10, 2) NOT NULL,
            paymentStatus ENUM('Pending', 'Paid', 'Failed') NOT NULL DEFAULT 'Pending',
            mpesaReceiptNumber VARCHAR(100),
            transactionDate DATETIME,
            dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_Ticket_Concert FOREIGN KEY (concertId) REFERENCES concert(concertId) ON DELETE CASCADE
        )
    ");

    // PushRequest table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pushrequest (
            pushRequestId INT AUTO_INCREMENT PRIMARY KEY,
            ticketId INT NOT NULL,
            checkoutRequestId VARCHAR(255) NOT NULL,
            dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_PushRequest_Ticket FOREIGN KEY (ticketId) REFERENCES ticket(ticketId) ON DELETE CASCADE
        )
    ");
}

/**
 * Response Helper
 *
 * Renders a PHP template file as HTML response.
 *
 * @param string $template_file Path to the PHP template file
 * @param array $data Variables to make available in the template
 * @param int $status_code HTTP status code
 */
function htmlResponse($template_file, $data = [], $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: text/html; charset=utf-8');

    // Make data variables available in the template
    extract($data, EXTR_SKIP);

    // Start output buffering
    ob_start();

    // Include the template file
    include $template_file;

    // Get the contents and clean the buffer
    $html = ob_get_clean();

    echo $html;
    exit;
}

/**
 * Response Helper
 *
 * Outputs a JSON response with appropriate headers and status code.
 *
 * @param mixed $data Data to be encoded as JSON
 * @param int $status_code HTTP status code
 */
function jsonResponse($data, $status_code = 200) {
    
     http_response_code($status_code);
     header('Content-Type: application/json');
     
    echo json_encode($data);
    exit;
}

/**
 * Get M-Pesa Access Token
 *
 * Sends a request to Safaricom to get an OAuth access token.
 *
 * @return string|null Access token if successful, null otherwise
 */
function getMpesaAccessToken() {
    global $mpesa_config;

    $url = $mpesa_config['base_url'] . '/' . $mpesa_config['access_token_url'];
    $credentials = base64_encode($mpesa_config['consumer_key'] . ':' . $mpesa_config['consumer_secret']);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 200) {
        $response_data = json_decode($response, true);
        return $response_data['access_token'] ?? null;
    }

    return null;
}

/**
 * Format Phone Number
 *
 * Formats a phone number to the required format for M-Pesa API (2547XXXXXXXX).
 *
 * @param string $phone_number Phone number to format
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone_number) {
    // Remove any non-digit characters
    $phone_number = preg_replace('/\D/', '', $phone_number);

    // Check if the number starts with '0' and replace with '254'
    if (substr($phone_number, 0, 1) === '0') {
        $phone_number = '254' . substr($phone_number, 1);
    }

    // Check if the number starts with '+254' and remove the '+'
    elseif (substr($phone_number, 0, 4) === '+254') {
        $phone_number = substr($phone_number, 1);
    }

    // Check if the number doesn't have the country code and add it
    elseif (substr($phone_number, 0, 3) !== '254') {
        $phone_number = '254' . $phone_number;
    }

    return $phone_number;
}

/**
 * Index Route
 *
 * Handles requests to the root path.
 */
function handleIndexRoute() {
    htmlResponse('index.php');
    htmlResponse($html);
}

/**
 * Get All Concert
 *
 * Retrieves all concert from the database.
 */
///handleGetAllConcert
function handleGetAllConcert() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM concert");
        $concert = $stmt->fetchAll();

        // Convert decimal and date fields
        foreach ($concert as &$concert) {
            $concert['price'] = (float) $concert['price'];
            $concert['showTime'] = $concert['showTime'];
        }

        jsonResponse(['concerts' => $concert]);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

/**
 * Get Concert by ID
 *
 * Retrieves a specific concert by ID.
 *
 * @param int $id Concert ID
 */
function handleGetConcertById($id) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM concert WHERE concertId = ?");
        $stmt->execute([$id]);
        $concert = $stmt->fetch();

        if (!$concert) {
            jsonResponse(['error' => 'Concert not found'], 404);
        }

        // Convert decimal and date fields
        $concert['price'] = (float) $concert['price'];

        jsonResponse(['concert' => $concert]);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

/**
 * Add Concert
 *
 * Adds a new concert to the database.
 */
function handleAddConcert() {
    try {
        // Get JSON request body
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        $required_fields = ['title', 'showTime', 'price'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                jsonResponse(['error' => "Missing required field: {$field}"], 400);
            }
        }

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO concert (title, description, showTime, price, max_tickets, imageUrl)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['showTime'],
            $data['price'],
            $data['max_tickets'] ?? 100,
            $data['imageUrl'] ?? null
        ]);

        // Get the inserted concert
        $concertId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM concert WHERE concertId = ?");
        $stmt->execute([$concertId]);
        $concert = $stmt->fetch();

        // Convert decimal and date fields
        $concert['price'] = (float) $concert['price'];

        jsonResponse(['message' => 'Concert added successfully', 'concert' => $concert], 201);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

/**
 * Purchase Ticket
 *
 * Retrieves all concert for ticket purchase.
 */
function handlePurchaseTicket() {
    handleGetAllConcert();
}

/**
 * Make Payment
 *
 * Initiates an M-Pesa payment.
 */
function handleMakePayment() {
    try {
        // Get JSON request body
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        $required_fields = ['concertId', 'customerName', 'phoneNumber', 'quantity'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                jsonResponse(['error' => "Missing required field: {$field}"], 400);
            }
        }

        $pdo = getDbConnection();

        // Get the concert
        $stmt = $pdo->prepare("SELECT * FROM concert WHERE concertId = ?");
        $stmt->execute([$data['concertId']]);
        $concert = $stmt->fetch();

        if (!$concert) {
            jsonResponse(['error' => 'Concert not found'], 404);
        }

        $quantity = (int) $data['quantity'];

        // Check if enough tickets are available
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(quantity), 0) as tickets_sold
            FROM ticket
            WHERE concertId = ? AND paymentStatus = 'Paid'
        ");
        $stmt->execute([$concert['concertId']]);
        $result = $stmt->fetch();
        $tickets_sold = (int) $result['tickets_sold'];

        if ($tickets_sold + $quantity > $concert['max_tickets']) {
            jsonResponse([
                'error' => "Not enough tickets available. Only " . ($concert['max_tickets'] - $tickets_sold) . " left."
            ], 400);
        }

        // Calculate total amount
        $total_amount = (float) $concert['price'] * $quantity;

        // Format phone number
        $formatted_phone = formatPhoneNumber($data['phoneNumber']);

        // Create a new ticket record with pending status
        $stmt = $pdo->prepare("
            INSERT INTO ticket (concertId, customerName, phoneNumber, quantity, totalAmount, paymentStatus)
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");

        $stmt->execute([
            $concert['concertId'],
            $data['customerName'],
            $formatted_phone,
            $quantity,
            $total_amount
        ]);

        $ticketId = $pdo->lastInsertId();

        // Get access token for M-Pesa API
        $access_token = getMpesaAccessToken();
        if (!$access_token) {
            // Rollback by updating ticket status to Failed
            $stmt = $pdo->prepare("UPDATE ticket SET paymentStatus = 'Failed' WHERE ticketId = ?");
            $stmt->execute([$ticketId]);

            jsonResponse(['error' => 'Failed to get M-Pesa access token'], 500);
        }

        // Prepare STK push request
        global $mpesa_config;
        $timestamp = date('YmdHis');
        $password = base64_encode($mpesa_config['business_short_code'] . $mpesa_config['passkey'] . $timestamp);

        $stk_push_url = $mpesa_config['base_url'] . '/' . $mpesa_config['stk_push_url'];

        $stk_push_data = [
            'BusinessShortCode' => $mpesa_config['business_short_code'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerBuyGoodsOnline',
            'Amount' => (int) $total_amount,  // Amount must be an integer
            'PartyA' => $formatted_phone,
            'PartyB' => $mpesa_config['till_number'],
            'PhoneNumber' => $formatted_phone,
            'CallBackURL' => $mpesa_config['callback_url'],
            'AccountReference' => "Concert Ticket {$concert['title']}",
            'TransactionDesc' => 'Covie Ticket Purchase'
        ];

        // Send STK push request
        $ch = curl_init($stk_push_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stk_push_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $mpesa_response = json_decode($response, true);

        // Check if STK push was successful
        if (isset($mpesa_response['ResponseCode']) && $mpesa_response['ResponseCode'] === '0') {
            // Create PushRequest record
            $checkout_request_id = $mpesa_response['CheckoutRequestID'];
            $stmt = $pdo->prepare("
                INSERT INTO pushrequest (ticketId, checkoutRequestId)
                VALUES (?, ?)
            ");

            $stmt->execute([$ticketId, $checkout_request_id]);

            jsonResponse([
                'message' => 'Payment initiated successfully',
                'ticketId' => $ticketId,
                'checkoutRequestId' => $checkout_request_id,
                'responseDescription' => $mpesa_response['ResponseDescription'] ?? ''
            ]);
        } else {
            // Update ticket status to Failed
            $stmt = $pdo->prepare("UPDATE ticket SET paymentStatus = 'Failed' WHERE ticketId = ?");
            $stmt->execute([$ticketId]);

            jsonResponse([
                'error' => 'Failed to initiate payment',
                'mpesaResponse' => $mpesa_response
            ], 500);
        }
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

/**
 * Query Payment Status
 *
 * Queries the status of an M-Pesa STK push transaction.
 */
function handleQueryPaymentStatus() {
    try {
        // Get JSON request body
        $data = json_decode(file_get_contents('php://input'), true);
        $checkout_request_id = $data['checkoutRequestId'] ?? null;

        if (!$checkout_request_id) {
            jsonResponse(['error' => 'Checkout Request ID not provided'], 400);
        }

        // Get access token for M-Pesa API
        $access_token = getMpesaAccessToken();
        if (!$access_token) {
            jsonResponse(['error' => 'Failed to get M-Pesa access token'], 500);
        }

        // Prepare STK query request
        global $mpesa_config;
        $timestamp = date('YmdHis');
        $password = base64_encode($mpesa_config['business_short_code'] . $mpesa_config['passkey'] . $timestamp);

        $query_url = $mpesa_config['base_url'] . '/' . $mpesa_config['stk_query_url'];

        $query_data = [
            'BusinessShortCode' => $mpesa_config['business_short_code'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkout_request_id
        ];

        // Send STK query request
        $ch = curl_init($query_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $mpesa_response = json_decode($response, true);

        jsonResponse($mpesa_response);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

/**
 * M-Pesa Callback
 *
 * Callback endpoint for M-Pesa payment notifications.
 */
function handleMpesaCallback() {
    try {
        // Get JSON request body
        $response = json_decode(file_get_contents('php://input'), true);
        $callback_data = $response['Body']['stkCallback'] ?? null;

        // Check if callback data exists
        if (!$callback_data) {
            jsonResponse(['error' => 'Invalid callback data'], 400);
        }

        // Get result code and checkout request ID
        $result_code = $callback_data['ResultCode'] ?? null;
        $checkout_request_id = $callback_data['CheckoutRequestID'] ?? null;

        $pdo = getDbConnection();

        // Find the associated push request
        $stmt = $pdo->prepare("SELECT * FROM pushrequest WHERE checkoutRequestId = ?");
        $stmt->execute([$checkout_request_id]);
        $push_request = $stmt->fetch();

        if (!$push_request) {
            jsonResponse(['error' => 'No matching push request found'], 404);
        }

        // Get the associated ticket
        $stmt = $pdo->prepare("SELECT * FROM ticket WHERE ticketId = ?");
        $stmt->execute([$push_request['ticketId']]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            jsonResponse(['error' => 'No matching ticket found'], 404);
        }

        // Process successful payment
        if ($result_code == 0) {
            // Extract payment details
            $callback_metadata = $callback_data['CallbackMetadata']['Item'] ?? [];

            // Extract amount, receipt number, and transaction date
            $amount = null;
            $receipt_number = null;
            $transaction_date_str = null;

            foreach ($callback_metadata as $item) {
                if ($item['Name'] === 'Amount') {
                    $amount = $item['Value'];
                } elseif ($item['Name'] === 'MpesaReceiptNumber') {
                    $receipt_number = $item['Value'];
                } elseif ($item['Name'] === 'TransactionDate') {
                    $transaction_date_str = $item['Value'];
                }
            }

            // Convert transaction date string to datetime
            $transaction_date = null;
            if ($transaction_date_str) {
                try {
                    // Format from Safaricom is typically YYYYMMDDHHmmss
                    $transaction_date = date('Y-m-d H:i:s', strtotime($transaction_date_str));
                } catch (Exception $e) {
                    // If that fails, use current date
                    $transaction_date = date('Y-m-d H:i:s');
                }
            }

            // Update ticket with payment details
            $stmt = $pdo->prepare("
                UPDATE ticket SET
                paymentStatus = 'Paid',
                mpesaReceiptNumber = ?,
                transactionDate = ?
                WHERE ticketId = ?
            ");

            $stmt->execute([$receipt_number, $transaction_date, $ticket['ticketId']]);

            jsonResponse(['message' => 'Payment completed successfully']);
        }
        // Process failed payment
        else {
            // Update ticket status to Failed
            $stmt = $pdo->prepare("UPDATE ticket SET paymentStatus = 'Failed' WHERE ticketId = ?");
            $stmt->execute([$ticket['ticketId']]);

            jsonResponse(['message' => 'Payment failed', 'result_code' => $result_code]);
        }
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

/**
 * Get Ticket
 *
 * Retrieves details of a specific ticket.
 *
 * @param int $id Ticket ID
 */
function handleGetTicket($id) {
    try {
        $pdo = getDbConnection();

        // Get the ticket
        $stmt = $pdo->prepare("
            SELECT t.*, m.title, m.description, m.showTime, m.price, m.max_tickets, m.imageUrl
            FROM ticket t
            JOIN concert m ON t.concertId = m.concertId
            WHERE t.ticketId = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if (!$result) {
            jsonResponse(['error' => 'Ticket not found'], 404);
        }

        // Format the response
        $ticket = [
            'ticketId' => $result['ticketId'],
            'concertId' => $result['concertId'],
            'customerName' => $result['customerName'],
            'phoneNumber' => $result['phoneNumber'],
            'quantity' => (int) $result['quantity'],
            'totalAmount' => (float) $result['totalAmount'],
            'paymentStatus' => $result['paymentStatus'],
            'mpesaReceiptNumber' => $result['mpesaReceiptNumber'],
            'transactionDate' => $result['transactionDate'],
            'dateCreated' => $result['dateCreated'],
            'lastUpdated' => $result['lastUpdated'],
            'concert' => [
                'concertId' => $result['concertId'],
                'title' => $result['title'],
                'description' => $result['description'],
                'showTime' => $result['showTime'],
                'price' => (float) $result['price'],
                'max_tickets' => (int) $result['max_tickets'],
                'imageUrl' => $result['imageUrl']
            ]
        ];

        jsonResponse(['ticket' => $ticket]);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

/**
 * Route Handler
 *
 * Handles all incoming requests and routes them to the appropriate handler.
 */
function handleRequest() {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    // Remove query string and trailing slash
    $uri = strtok($uri, '?');
    $uri = rtrim($uri, '/');

    // Create database tables if they don't exist
    createDatabaseTables();

    // Routes
    if ($uri === '' || $uri === '/') {
        handleIndexRoute();
    }

    // API routes
    elseif ($uri === '/api/concert' && $method === 'GET') {
        handleGetAllConcert();
    }
    elseif (preg_match('#^/api/concerts/(\d+)$#', $uri, $matches) && $method === 'GET') {
        handleGetConcertById($matches[1]);
    }
    elseif ($uri === '/api/concerts' && $method === 'POST') {
        handleAddConcert();
    }
    elseif ($uri === '/api/purchase-ticket' && $method === 'GET') {
        handlePurchaseTicket();
    }
    elseif ($uri === '/api/make-payment' && $method === 'POST') {
        handleMakePayment();
    }
    elseif ($uri === '/api/query-payment-status' && $method === 'POST') {
        handleQueryPaymentStatus();
    }
    elseif ($uri === '/api/mpesa-callback' && $method === 'POST') {
        handleMpesaCallback();
    }
    elseif (preg_match('#^/api/tickets/(\d+)$#', $uri, $matches) && $method === 'GET') {
        handleGetTicket($matches[1]);
    }

    // Route not found
    else {
        jsonResponse(['error' => 'Route not found'], 404);
    }
}

// Handle the request
handleRequest();
