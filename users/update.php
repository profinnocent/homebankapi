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

if (!isset($data->id)) {
    echo json_encode(['success' => 0, 'message' => 'Please provide a valid user ID.']);
    exit;
}

try {

    $fetch_post = "SELECT * FROM `bankusers` WHERE id = :id";
    $fetch_stmt = $conn->prepare($fetch_post);
    $fetch_stmt->bindValue(':id', $data->id, PDO::PARAM_INT);
    $fetch_stmt->execute();

    if ($fetch_stmt->rowCount() > 0) :

        $row = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        $user_firstname = isset($data->firstname) ? $data->firstname : $row['firstname'];
        $user_lastname = isset($data->lastname) ? $data->lastname : $row['lastname'];
        $user_email = isset($data->email) ? $data->email : $row['email'];
        $user_password = isset($data->password) ? password_hash($data->password, PASSWORD_DEFAULT) : $row['password'];
        $user_balance = isset($data->balance) ? $data->balance : $row['balance'];


        $update_query = "UPDATE `bankusers` SET firstname = :firstname, lastname = :lastname, email = :email, password = :hpassword, balance = :balance 
        WHERE id = :id";

        $update_stmt = $conn->prepare($update_query);

        $update_stmt->bindValue(':firstname', htmlspecialchars(strip_tags($user_firstname)), PDO::PARAM_STR);
        $update_stmt->bindValue(':lastname', htmlspecialchars(strip_tags($user_lastname)), PDO::PARAM_STR);
        $update_stmt->bindValue(':email', htmlspecialchars(strip_tags($user_email)), PDO::PARAM_STR);
        $update_stmt->bindValue(':hpassword', htmlspecialchars(strip_tags($user_password)), PDO::PARAM_STR);
        $update_stmt->bindValue(':balance', htmlspecialchars(strip_tags($user_balance)), PDO::PARAM_INT);

        $update_stmt->bindValue(':id', $data->id, PDO::PARAM_INT);


        if ($update_stmt->execute()) {

            echo json_encode([
                'success' => 1,
                'message' => 'User updated successfully'
            ]);
            exit;
        }

        echo json_encode([
            'success' => 0,
            'message' => 'User Not updated. Something is went wrong.'
        ]);
        exit;

    else :
        $uid = $data->id;
        echo json_encode(['success' => 0, 'message' => 'Invalid ID. No user found by that ID.', 'uid'=> $uid]);
        exit;
    endif;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'message' => $e->getMessage()
    ]);
    exit;
}