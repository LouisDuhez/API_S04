<?php
require_once 'includes/config.php';
require_once 'classes/JWT.php';

// identifiant de connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=marie_curie_db';
$username = 'root';
$password = '';

// Connexion à la base de données
try {
    $pdo = new PDO($dsn, $username, $password);
} catch(PDOException $e) {
    echo 'Connexion échouée : ' . $e->getMessage();
}

function verifyJWT() {
    // On vérifie si on reçoit un token
    if(isset($_SERVER['Authorization'])){
        $token = trim($_SERVER['Authorization']);
    } elseif(isset($_SERVER['HTTP_AUTHORIZATION'])){
        $token = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif(function_exists('apache_request_headers')){
        $requestHeaders = apache_request_headers();
        if(isset($requestHeaders['Authorization'])){
            $token = trim($requestHeaders['Authorization']);
        }
    }

    // On vérifie si la chaine commence par "Bearer "
    if(!isset($token) || !preg_match('/Bearer\s(\S+)/', $token, $matches)){
        http_response_code(400);
        echo json_encode(['message' => 'Token introuvable']);
        exit;
    }

    // On extrait le token
    $token = str_replace('Bearer ', '', $token);

    require_once 'includes/config.php';
    require_once 'classes/JWT.php';

    $jwt = new JWT();

    // On vérifie la validité
    if(!$jwt->isValid($token)){
        http_response_code(400);
        echo json_encode(['message' => 'Token invalide']);
        exit;
    }

    // On vérifie la signature
    if(!$jwt->check($token, SECRET)){
        http_response_code(403);
        echo json_encode(['message' => 'Le token est invalide']);
        exit;
    }

    // On vérifie l'expiration
    if($jwt->isExpired($token)){
        http_response_code(403);
        echo json_encode(['message' => 'Le token a expiré']);
        exit;
    }
}

$request_method = $_SERVER["REQUEST_METHOD"];
$path_info = isset($_SERVER['PATH_INFO']) ? explode('/', trim($_SERVER['PATH_INFO'], '/')) : [];

// On redirige vers la bonne fonction en fonction de l'endpoint
switch($path_info[0]) {
    case 'users':
        handleUsers($pdo, $request_method, $path_info);
        break;
    case 'reservations':
        handleReservation($pdo, $request_method, $path_info);
        break;
    case 'key' : 
        handleKey($pdo, $request_method);
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        echo json_encode(["message" => "Endpoint not found"]);
        break;
}

function handleKey($pdo, $request_method) {
    if($request_method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if(isset($data['login']) && isset($data['mdp']) ) {
            if($data['login'] == LOGIN) {
                if(password_verify(MDP, $data['mdp'])) {
                    $jwt = new JWT();
                    $token = $jwt->generate([
                        'typ' => 'JWT',
                        'alg' => 'HS256'
                        ],[],SECRET, 3600);
                    echo json_encode(['token' => $token]);
                }else {
                    http_response_code(401);
                    echo json_encode(['message' => 'Invalid mdp']);
                }
            } else {
                http_response_code(401);
                echo json_encode(['message' => 'Invalid login']);
            }
        }
    }else {
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
    }
}

function handleUsers ($pdo, $request_method, $path_info) {
    switch($request_method) {
        case 'POST' : 
            if(isset($_POST["email"]) && isset($_POST["user_name"]) && isset($_POST["user_lastname"])) {
                $email = $_POST["email"];
                $user_name = $_POST["user_name"];
                $user_lastname = $_POST["user_lastname"];
                
                $stmt = $pdo->prepare("INSERT INTO user (user_email, user_name, user_lastname) VALUES (:email, :user_name, :user_lastname)");
                $result = $stmt->execute(['email' => $email, 'user_name' => $user_name, 'user_lastname' => $user_lastname]);
                
                // Affichage du message de succès ou d'erreur sous forme de tableau JSON
                if ($result) {
                    $json = array("status" => 200, "message" => "User successfully inserted");
                } else {
                    $json = array("status" => 300, "message" => "Error inserting user");
                }
            } else {
                $json = array("status" => 400, "message" => "Invalid input");
            }
            
            echo json_encode($json);
            break;
        case 'GET' : 
            verifyJWT();
            if(isset($path_info[1])) {
                if ($path_info[1] === 'count') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user");
                    $stmt->execute();
                    $count = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($count);
                } else {
                    $id = intval($path_info[1]);
                    $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id=:id");
                    $stmt->execute(['id' => $id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($user);
                }
            } else {
                $stmt = $pdo->prepare("SELECT * FROM user");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users);
            }
            
            break;
        case 'DELETE' : 
            verifyJWT();
            $id = intval($path_info[1]);
            $stmt = $pdo->prepare("DELETE FROM user WHERE user_id=:id");
            $result = $stmt->execute(['id' => $id]);
            
            if ($result) {
                $json = array("status" => 200, "message" => "User successfully deleted");
            } else {
                $json = array("status" => 300, "message" => "Error deleting user");
            }
            
            echo json_encode($json);
            break;
    }
}

function handleReservation ($pdo, $request_method, $path_info) {
    switch($request_method) {
        case 'POST' :
            $date = $_POST["date"];
            $nbStudent = $_POST["student"];
            $nbNormal = $_POST["normal"];
            $user = $_POST["user"];
            $stmt = $pdo->prepare("INSERT INTO reservation (reservation_date, reservation_nb_student, reservation_nb_normal, reservation_user_fk) VALUES (:date, :nbStudent, :nbNormal, :user)");
            $result = $stmt->execute(['date' => $date, 'nbStudent' => $nbStudent, 'nbNormal' => $nbNormal, 'user' => $user]);

            if($result) {
                $json = array("status" => 200, "message" => "Reservation successfully inserted");
            } else {
                $json = array("status" => 300, "message" => "Error inserting reservation");
            }
            echo json_encode($json);
            break;
        case 'GET' :
            verifyJWT();
            if(isset($path_info[1])) {
                if ($path_info[1] === 'count') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservation");
                    $stmt->execute();
                    $count = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($count);
                    return;
                } 
                if($path_info[1] === 'sum') {
                    $stmt = $pdo->prepare("SELECT SUM(reservation_nb_student) as sum_student, SUM(reservation_nb_normal) as sum_normal FROM reservation");
                    $stmt->execute();
                    $sums = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($sums);
                    return;
                }
                if($path_info[1] === 'daily') {
                    $stmt = $pdo->prepare("SELECT DATE(reservation_date) as date, COUNT(*) as count FROM reservation GROUP BY DATE(reservation_date)");
                    $stmt->execute();
                    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($reservations);
                    return;
                }
                else {
                    $id = intval($path_info[1]);
                    $stmt = $pdo->prepare("SELECT * FROM reservation INNER JOIN user ON reservation.reservation_user_fk = user.user_id WHERE reservation_id=:id");
                    $stmt->execute(['id' => $id]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($reservation);
                    return;
                }
            } else {
                $stmt = $pdo->prepare("SELECT * FROM reservation INNER JOIN user ON reservation.reservation_user_fk = user.user_id");
                $stmt->execute();
                $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($reservations);
                return;
            }
            break;
        
        case 'PUT' :
            verifyJWT();
            $id = intval($path_info[1]);
            $date = $_POST["date"];
            $nbStudent = $_POST["student"];
            $nbNormal = $_POST["normal"];
            $user = $_POST["user"];
            $stmt = $pdo->prepare("UPDATE reservation SET reservation_date=:date, reservation_nb_student=:nbStudent, reservation_nb_normal=:nbNormal, reservation_user_fk=:user WHERE reservation_id=:id");
            $result = $stmt->execute(['date' => $date, 'nbStudent' => $nbStudent, 'nbNormal' => $nbNormal, 'user' => $user, 'id' => $id]);

            // Affichage du message de succès ou d'erreur sous forme de tableau JSON
            if($result) {
                $json = array("status" => 200, "message" => "Reservation successfully updated");
            } else {
                $json = array("status" => 300, "message" => "Error updating reservation");
            }

            echo json_encode($json);
            break;
        
        case 'DELETE' :
            verifyJWT();
            $id = intval($path_info[1]);
            $stmt = $pdo->prepare("DELETE FROM reservation WHERE reservation_id=:id");
            $result = $stmt->execute(['id' => $id]);

            if($result) {
                $json = array("status" => 200, "message" => "Reservation successfully deleted");
            } else {
                $json = array("status" => 300, "message" => "Error deleting reservation");
            }

            echo json_encode($json);
            break;
    }
}