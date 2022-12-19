<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


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

if (!isset($data->title) || !isset($data->body) || !isset($data->author_id)) :

    echo json_encode([
        'success' => 0,
        'message' => 'Please fill all the fields | title, body, author.',
    ]);
    exit;

elseif (empty(trim($data->title)) || empty(trim($data->body)) || empty(trim($data->author_id))) :

    echo json_encode([
        'success' => 0,
        'message' => 'Oops! empty field detected. Please fill all the fields.',
    ]);
    exit;

endif;

try {

    $title = htmlspecialchars(trim($data->title));
    $body = htmlspecialchars(trim($data->body));
    $author_id = htmlspecialchars(trim($data->author_id));

    $query = "INSERT INTO `posts`(title,body,author_id) VALUES(:title,:body,:author_id)";

    $stmt = $conn->prepare($query);

    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':body', $body, PDO::PARAM_STR);
    $stmt->bindValue(':author_id', $author_id, PDO::PARAM_INT);

    if ($stmt->execute()) {

        http_response_code(201);
        echo json_encode([
            'success' => 1,
            'message' => 'Data Inserted Successfully.'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => 0,
        'message' => 'Data not Inserted.'
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'message' => $e->getMessage()
    ]);
    exit;
}