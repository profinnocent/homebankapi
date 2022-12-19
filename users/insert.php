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

if (!isset($data->firstname) || !isset($data->lastname) || !isset($data->email) || !isset($data->password)) :

    echo json_encode([
        'success' => 0,
        'message' => 'Please fill all the fields.',
    ]);
    exit;

elseif (empty(trim($data->firstname)) || empty(trim($data->lastname)) || empty(trim($data->email)) || empty(trim($data->password))) :

    echo json_encode([
        'success' => 0,
        'message' => 'Oops! empty field detected. Please fill all the fields.',
    ]);
    exit;

endif;

try {

    $firstname = htmlspecialchars(trim($data->firstname));
    $lastname = htmlspecialchars(trim($data->lastname));
    $email = htmlspecialchars(trim($data->email));
    $password = htmlspecialchars(trim($data->password));

    $hash_password = password_hash($password, PASSWORD_DEFAULT);
    $balance = 0;


    $query = "INSERT INTO `bankusers`(firstname,lastname,email,password,balance) VALUES(:firstname,:lastname,:email,:hash_password,:balance)";

    $stmt = $conn->prepare($query);

    $stmt->bindValue(':firstname', $firstname, PDO::PARAM_STR);
    $stmt->bindValue(':lastname', $lastname, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':hash_password', $hash_password, PDO::PARAM_STR);
    $stmt->bindValue(':balance', $balance, PDO::PARAM_INT);


    if ($stmt->execute()) {

        http_response_code(201);
        echo json_encode([
            'success' => 1,
            'message' => 'New usre Inserted Successfully.'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => 0,
        'message' => 'New User NOT Inserted.'
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