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

if (!isset($data->post_id)) {
    echo json_encode(['success' => 0, 'message' => 'Please provide a valid post ID.']);
    exit;
}

try {

    $fetch_post = "SELECT * FROM `posts` WHERE post_id = :post_id";
    $fetch_stmt = $conn->prepare($fetch_post);
    $fetch_stmt->bindValue(':post_id', $data->post_id, PDO::PARAM_INT);
    $fetch_stmt->execute();

    if ($fetch_stmt->rowCount() > 0) :

        $row = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        $post_title = isset($data->title) ? $data->title : $row['title'];
        $post_body = isset($data->body) ? $data->body : $row['body'];
        $post_author_id = isset($data->author_id) ? $data->author_id : $row['author_id'];

        $update_query = "UPDATE `posts` SET title = :title, body = :body, author_id = :author_id 
        WHERE post_id = :post_id";

        $update_stmt = $conn->prepare($update_query);

        $update_stmt->bindValue(':title', htmlspecialchars(strip_tags($post_title)), PDO::PARAM_STR);
        $update_stmt->bindValue(':body', htmlspecialchars(strip_tags($post_body)), PDO::PARAM_STR);
        $update_stmt->bindValue(':author_id', htmlspecialchars(strip_tags($post_author_id)), PDO::PARAM_INT);
        $update_stmt->bindValue(':post_id', $data->post_id, PDO::PARAM_INT);

        if ($update_stmt->execute()) {

                echo json_encode([
                    'success' => 1,
                    'message' => 'Post updated successfully'
                ]);
                exit;

            }else{

            echo json_encode([
                'success' => 0,
                'message' => 'Post Not updated. Something is going wrong.'
            ]);
            exit;

        }

    else :

        echo json_encode(['success' => 0, 'message' => 'Invalid ID. No posts found by the ID.']);
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