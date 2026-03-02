<?php

/* =========================
   SAFE SESSION START
========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "db.php";

/* =========================
   CHECK LOGIN
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   GET CURRENT ACTIVE PLAN
========================= */
$currentPlan = null;

$stmt = $conn->prepare("
    SELECT us.plan_id,
           us.end_date,
           sp.name,
           sp.price,
           sp.duration_days
    FROM user_subscriptions us
    JOIN subscription_plans sp ON us.plan_id = sp.id
    WHERE us.user_id=? AND us.status='active'
    ORDER BY us.end_date DESC
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $currentPlan = $result->fetch_assoc();
}

/* =========================
   HANDLE PAYMENT
========================= */
if (isset($_POST['process_payment'])) {

    $new_plan_id    = intval($_POST['plan_id']);
    $payment_method = $_POST['payment_method'];
    $today = date('Y-m-d');

    $planStmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id=?");
    $planStmt->bind_param("i", $new_plan_id);
    $planStmt->execute();
    $planResult = $planStmt->get_result();

    if ($planResult->num_rows > 0) {

        $newPlan = $planResult->fetch_assoc();

        if ($currentPlan) {

            if ($newPlan['id'] == $currentPlan['plan_id']) {
                header("Location: subscription.php");
                exit();
            }

            /* UPGRADE */
            if ($newPlan['price'] > $currentPlan['price']) {

                $difference = $newPlan['price'] - $currentPlan['price'];

                $expire = $conn->prepare("
                    UPDATE user_subscriptions
                    SET status='expired'
                    WHERE user_id=? AND status='active'
                ");
                $expire->bind_param("i", $user_id);
                $expire->execute();

                $start_date = $today;
                $end_date   = date('Y-m-d', strtotime("+".$newPlan['duration_days']." days"));

                $insertSub = $conn->prepare("
                    INSERT INTO user_subscriptions
                    (user_id, plan_id, start_date, end_date, status)
                    VALUES (?, ?, ?, ?, 'active')
                ");
                $insertSub->bind_param("iiss", $user_id, $new_plan_id, $start_date, $end_date);
                $insertSub->execute();

                $insertTrans = $conn->prepare("
                    INSERT INTO transactions
                    (user_id, plan_id, amount, payment_date)
                    VALUES (?, ?, ?, NOW())
                ");
                $insertTrans->bind_param("iid", $user_id, $new_plan_id, $difference);
                $insertTrans->execute();
            }

            /* DOWNGRADE */
            if ($newPlan['price'] < $currentPlan['price']) {

                $expire = $conn->prepare("
                    UPDATE user_subscriptions
                    SET status='expired'
                    WHERE user_id=? AND status='active'
                ");
                $expire->bind_param("i", $user_id);
                $expire->execute();

                $start_date = $today;
                $end_date   = date('Y-m-d', strtotime("+".$newPlan['duration_days']." days"));

                $insertSub = $conn->prepare("
                    INSERT INTO user_subscriptions
                    (user_id, plan_id, start_date, end_date, status)
                    VALUES (?, ?, ?, ?, 'active')
                ");
                $insertSub->bind_param("iiss", $user_id, $new_plan_id, $start_date, $end_date);
                $insertSub->execute();

                $insertTrans = $conn->prepare("
                    INSERT INTO transactions
                    (user_id, plan_id, amount, payment_date)
                    VALUES (?, ?, ?, NOW())
                ");
                $insertTrans->bind_param("iid", $user_id, $new_plan_id, $newPlan['price']);
                $insertTrans->execute();
            }

        } else {

            /* NEW USER */
            $start_date = $today;
            $end_date   = date('Y-m-d', strtotime("+".$newPlan['duration_days']." days"));

            $insertSub = $conn->prepare("
                INSERT INTO user_subscriptions
                (user_id, plan_id, start_date, end_date, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            $insertSub->bind_param("iiss", $user_id, $new_plan_id, $start_date, $end_date);
            $insertSub->execute();

            $insertTrans = $conn->prepare("
                INSERT INTO transactions
                (user_id, plan_id, amount, payment_date)
                VALUES (?, ?, ?, NOW())
            ");
            $insertTrans->bind_param("iid", $user_id, $new_plan_id, $newPlan['price']);
            $insertTrans->execute();
        }

        header("Location: subscription.php");
        exit();
    }
}

/* =========================
   FETCH ALL PLANS
========================= */
$plans = $conn->query("SELECT * FROM subscription_plans ORDER BY price ASC");
?>