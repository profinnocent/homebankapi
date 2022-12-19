<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: PUT");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') :
    http_response_code(405);
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid Request Method. HTTP method should be PUT',
    ]);
    exit;
endif;

require '../config/database.php';
$database = new Database();
$conn = $database->dbConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->trans_id)) {
    echo json_encode(['success' => 0,
                      'message' => 'Please provide a valid user ID.',
                      'trans_id'=>$data->trans_id
                    ]);
    exit;
}

try {

    $fetch_post = "SELECT * FROM `transactions` WHERE trans_id = :id";
    $fetch_stmt = $conn->prepare($fetch_post);
    $fetch_stmt->bindValue(':id', $data->trans_id, PDO::PARAM_INT);
    $fetch_stmt->execute();

    if ($fetch_stmt->rowCount() > 0){

        $row = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = isset($data->user_id) ? $data->user_id : $row['user_id'];
        $trans_type = isset($data->trans_type) ? $data->trans_type : $row['trans_type'];
        $trans_amount = isset($data->trans_amount) ? $data->trans_amount : $row['trans_amount'];
        $trans_status = isset($data->trans_status) ? $data->trans_status : $row['trans_status'];
        $updated_date = date('d-m-Y H:i:s');


        $update_query = "UPDATE `transactions` SET user_id = :user_id, trans_type = :trans_type, trans_amount = :trans_amount, trans_status = :trans_status, updated_date = :updated_date 
        WHERE trans_id = :trans_id";

        $update_stmt = $conn->prepare($update_query);

        $update_stmt->bindValue(':user_id', htmlspecialchars(strip_tags($user_id)), PDO::PARAM_INT);
        $update_stmt->bindValue(':trans_type', htmlspecialchars(strip_tags($trans_type)), PDO::PARAM_STR);
        $update_stmt->bindValue(':trans_amount', abs(htmlspecialchars(strip_tags($trans_amount))), PDO::PARAM_INT);
        $update_stmt->bindValue(':trans_status', htmlspecialchars(strip_tags($trans_status)), PDO::PARAM_STR);
        $update_stmt->bindValue(':updated_date', htmlspecialchars(strip_tags($updated_date)), PDO::PARAM_STR);

        $update_stmt->bindValue(':trans_id', $data->trans_id, PDO::PARAM_INT);

        if ($update_stmt->execute()) {

            http_response_code(200);
            echo json_encode([
                'success' => 1,
                'message' => 'Transaction updated successfully'
            ]);
            exit;

        }else{

                http_response_code(400);
                echo json_encode([
                'success' => 0,
                'message' => 'Transaction Not updated. Something went wrong.'
            ]);
            exit;

        }

    }else{

        http_response_code(401);
        echo json_encode(['success' => 0, 'message' => 'Invalid ID. No transaction found by that ID.']);
        exit;

    }
} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'message' => $e->getMessage()
    ]);
    exit;

}