<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


// ================================================================
// Transaction will be updated but will need to be
//  approved by admin for it to update user balance
// ================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') :
    http_response_code(405);
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid Request Method. HTTP method should be POST',
    ]);
    exit;
endif;

require '../config/database.php';
$database = new Database();
$conn = $database->dbConnection();

$data = json_decode(file_get_contents("php://input"));


if(empty(trim($data->user_id)) || empty(trim($data->trans_type)) || empty(trim($data->trans_amount))) :

    echo json_encode([
        'success' => 0,
        'message' => 'Oops! empty field detected. Please fill all the fields.',
    ]);
    exit;

endif;

try {

    $user_id = htmlspecialchars(trim($data->user_id));
    $trans_type = htmlspecialchars(trim($data->trans_type));
    $trans_amount = abs(htmlspecialchars(trim($data->trans_amount)));


    // Calculate and update user acct balance on bankusers  table

    // Fisrt get the account balance of the user
    $bal_query = "SELECT * FROM `bankusers` WHERE id = :userid";

    $stmt = $conn->prepare($bal_query);

    $stmt->bindValue(':userid', $user_id, PDO::PARAM_INT);

    if($stmt->execute()){

        $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
        $bal = $user_row['balance'];

        // Then do the arithmetic for deposit or withdrawal
        if($trans_type == 'Withdrawal' && $trans_amount > $bal){

            http_response_code(400);
            echo json_encode([
                'success' => 0,
                'message' => 'You dont have sufficient balance to carry out this transaction.',
            ]);
            exit;

        }

    }else{

        echo json_encode([
            'success' => 0,
            'message' => 'New transaction can not be Inserted as User fecthing failed. Pls try again.'
        ]);
        exit;

    }




    // Insert the transaction into transaction table
    $insert_trans_query = "INSERT INTO `transactions`(user_id,trans_type,trans_amount) VALUES(:user_id,:trans_type,:trans_amount)";

    $stmt2 = $conn->prepare($insert_trans_query);

    $stmt2->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt2->bindValue(':trans_type', $trans_type, PDO::PARAM_STR);
    $stmt2->bindValue(':trans_amount', $trans_amount, PDO::PARAM_INT);


    if ($stmt2->execute()) {

            http_response_code(201);
            echo json_encode([
                'success' => 1,
                'message' => 'New transaction Inserted Successfully.',
            ]);
            exit;

    }else{

        http_response_code(400);
            echo json_encode([
                'success' => 0,
                'message' => 'Transaction update failed. Update Transaction again.',
            ]);
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