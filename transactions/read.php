<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') :

    http_response_code(405);
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid Request Method. HTTP method should be GET',
    ]);
    exit;

endif;

require '../config/database.php';
$database = new Database();
$conn = $database->dbConnection();
$trans_id = null;


if (isset($_GET['id'])) {

    $trans_id = filter_var($_GET['id'], FILTER_VALIDATE_INT, [
        'options' => [
            'default' => 'all_transactions',
            'min_range' => 1
        ]
    ]);
    
}

try {

    $sql = is_numeric($trans_id) ? "SELECT * FROM `transactions` WHERE trans_id='$trans_id'" : "SELECT * FROM `transactions`";

    $stmt = $conn->prepare($sql);

    $stmt->execute();

    if ($stmt->rowCount() > 0){

        $data = null;

        if (is_numeric($trans_id)) {

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

        } else {

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        }

        echo json_encode([
            'success' => 1,
            'data' => $data,
        ]);

    }else{

        echo json_encode([
            'success' => 0,
            'message' => 'No Transactions Found!',
        ]);
        
    }

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'message' => $e->getMessage()
    ]);
    exit;

}