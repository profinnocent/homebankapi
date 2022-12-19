<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: DELETE");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE'){
    http_response_code(405);
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid Request Method. HTTP method should be DELETE',
    ]);
    exit;
}

require '../config/database.php';
$database = new Database();
$conn = $database->dbConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->trans_id)) {

    http_response_code(401);
    echo json_encode(['success' => 0, 'message' => 'Please provide the user ID.']);
    exit;

}

try {

    $fetch_post = "SELECT * FROM `transactions` WHERE trans_id = :trans_id";
    $fetch_stmt = $conn->prepare($fetch_post);
    $fetch_stmt->bindValue(':trans_id', htmlspecialchars(strip_tags($data->trans_id)), PDO::PARAM_INT);
    $fetch_stmt->execute();

    if ($fetch_stmt->rowCount() > 0){

        $trans_row = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        $trans_user_id = $trans_row['user_id'];
        $trans_type = $trans_row['trans_type'];
        $trans_amount = $trans_row['trans_amount'];


        // Get user acct balance and subtract the transaction amount
        $user_acct_bal_query = "SELECT * FROM `bankusers` WHERE id = :id";
        $user_acct_bal_stmt = $conn->prepare($user_acct_bal_query);
        $user_acct_bal_stmt->bindValue(':id', htmlspecialchars(strip_tags($trans_user_id)), PDO::PARAM_INT);

        if($user_acct_bal_stmt->execute()){

            $user_doing_trans = $user_acct_bal_stmt->fetch(PDO::FETCH_ASSOC);
            $user_balance = $user_doing_trans['balance'];

            if($trans_type == 'Deposit'){

                $user_balance = $user_balance - $trans_amount;

            }else{

                $user_balance = $user_balance + $trans_amount;
            }


            // Update user balance in bankusers table with new updated value of $user_balance of $trans_user_id
            $update_newbal_query = "UPDATE `bankusers` SET balance = :balance WHERE id = :id";
            $update_newbal_stmt = $conn->prepare($update_newbal_query);
            $update_newbal_stmt->bindValue(':balance', htmlspecialchars(strip_tags($user_balance)), PDO::PARAM_INT);
            $update_newbal_stmt->bindValue(':id', htmlspecialchars(strip_tags($trans_user_id)), PDO::PARAM_INT);

            if($update_newbal_stmt->execute()){

                // Now delete the transaction
                $delete_post = "DELETE FROM `transactions` WHERE trans_id = :trans_id";
                $delete_post_stmt = $conn->prepare($delete_post);
                $delete_post_stmt->bindValue(':trans_id', $data->trans_id, PDO::PARAM_INT);

                if ($delete_post_stmt->execute()) {

                    http_response_code(200);
                    echo json_encode([
                        'success' => 1,
                        'message' => 'User Balance updated and transaction Deleted successfully'
                    ]);
                    exit;

                }else{

                    http_response_code(401);
                    echo json_encode([
                        'success' => 0,
                        'message' => 'Transaction Not Deleted. Pls do this again or delete manually as new balance has been updated.'
                    ]);
                    exit;

                }

            }

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