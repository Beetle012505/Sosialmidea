<?php
session_start();
include "db.php";

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

    $new_plan_id   = intval($_POST['plan_id']);
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

                header("Location: subscription.php");
                exit();
            }

        } else {

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

            header("Location: subscription.php");
            exit();
        }
    }
}

$plans = $conn->query("SELECT * FROM subscription_plans ORDER BY price ASC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Subscription</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:
        radial-gradient(circle at top left, rgba(139,92,246,0.35), transparent 40%),
        radial-gradient(circle at bottom right, rgba(0,255,200,0.25), transparent 40%),
        #050510;
    color:#fff;
    min-height:100vh;
}

.topbar{
    padding:20px 40px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(255,255,255,0.1);
}

.logo{
    font-family:'Orbitron',sans-serif;
    font-size:20px;
}

.nav a{
    margin-left:20px;
    text-decoration:none;
    color:#00ffc8;
    font-weight:600;
}

.container{
    max-width:1100px;
    margin:60px auto;
    padding:0 20px;
}

.section{
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(25px);
    border-radius:24px;
    padding:30px;
    margin-bottom:30px;
    border:1px solid rgba(255,255,255,0.15);
    box-shadow:0 0 40px rgba(139,92,246,0.2);
}

.plan-box{
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.1);
    padding:20px;
    border-radius:20px;
    margin-bottom:20px;
}

.current{
    border:2px solid #00ffc8;
}

button{
    padding:10px 18px;
    border:none;
    border-radius:20px;
    background:linear-gradient(135deg,#8b5cf6,#00ffc8);
    color:#fff;
    cursor:pointer;
    font-weight:600;
    margin-top:10px;
}

input[type=radio]{
    margin-right:6px;
}

label{
    display:block;
    margin-bottom:6px;
}
</style>
</head>
<body>

<div class="topbar">
    <div class="logo">SOCIALHUB</div>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="subscription.php">Subscription</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">

<div class="section">
<h2 style="font-family:'Orbitron',sans-serif;margin-bottom:20px;">Subscription</h2>

<?php if($currentPlan): ?>
<div class="plan-box current">
    <strong>Current Plan:</strong> <?php echo $currentPlan['name']; ?><br><br>
    Price: ₱<?php echo number_format($currentPlan['price'],2); ?><br>
    Expires: <?php echo $currentPlan['end_date']; ?>
</div>
<?php endif; ?>

<?php while($plan = $plans->fetch_assoc()): ?>

<div class="plan-box <?php if($currentPlan && $currentPlan['plan_id'] == $plan['id']) echo 'current'; ?>">

    <h3><?php echo $plan['name']; ?></h3>
    <p>₱<?php echo number_format($plan['price'],2); ?></p>
    <p><?php echo $plan['duration_days']; ?> days</p>

    <?php if(!$currentPlan || $currentPlan['plan_id'] != $plan['id']): ?>

    <form method="POST">
        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">

        <label>
            <input type="radio" name="payment_method" value="GCash" required>
            GCash
        </label>

        <label>
            <input type="radio" name="payment_method" value="Maya">
            Maya
        </label>

        <label>
            <input type="radio" name="payment_method" value="Credit Card">
            Credit Card
        </label>

        <button type="submit" name="process_payment">
            <?php echo $currentPlan ? "Pay & Upgrade" : "Pay & Subscribe"; ?>
        </button>
    </form>

    <?php else: ?>
        <p><strong>This is your current plan</strong></p>
    <?php endif; ?>

</div>

<?php endwhile; ?>

</div>
</div>

</body>
</html>