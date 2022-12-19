<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: DELETE");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') :
    http_response_code(405);
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid Request Method. HTTP method should be DELETE',
    ]);
    exit;
endif;

require '../config/database.php';
$database = new Database();
$conn = $database->dbConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    echo json_encode(['success' => 0, 'message' => 'Please provide the user ID.']);
    exit;
}

try {

    $fetch_post = "SELECT * FROM `bankusers` WHERE id=:id";
    $fetch_stmt = $conn->prepare($fetch_post);
    $fetch_stmt->bindValue(':id', $data->id, PDO::PARAM_INT);
    $fetch_stmt->execute();

    if ($fetch_stmt->rowCount() > 0) :

        $delete_post = "DELETE FROM `bankusers` WHERE id=:post_id";
        $delete_post_stmt = $conn->prepare($delete_post);
        $delete_post_stmt->bindValue(':post_id', $data->id,PDO::PARAM_INT);

        if ($delete_post_stmt->execute()) {

            echo json_encode([
                'success' => 1,
                'message' => 'User Deleted successfully'
            ]);
            exit;
        }

        echo json_encode([
            'success' => 0,
            'message' => 'User Not Deleted. Something is going wrong.'
        ]);
        exit;

    else :
        echo json_encode(['success' => 0, 'message' => 'Invalid ID. No user found by that ID.']);
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