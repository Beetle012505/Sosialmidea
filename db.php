<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "buffer");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if(isset($_SESSION['user_id'])){
    $uid = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT end_date FROM subscriptions WHERE user_id=? AND status='Active'");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $sub = $result->fetch_assoc();
        if(date("Y-m-d") > $sub['end_date']){
            $update = $conn->prepare("UPDATE subscriptions SET status='Expired' WHERE user_id=?");
            $update->bind_param("i", $uid);
            $update->execute();
        }
    }
}
?>
