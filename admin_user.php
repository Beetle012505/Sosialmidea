<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$user_id = intval($_GET['id']);

/* =========================
   GET USER INFO
========================= */

$userStmt = $conn->prepare("
    SELECT u.email,
           us.end_date,
           us.status,
           sp.name AS plan_name
    FROM users u
    LEFT JOIN user_subscriptions us 
        ON u.id = us.user_id
        AND us.status='active'
    LEFT JOIN subscription_plans sp 
        ON us.plan_id = sp.id
    WHERE u.id=? AND u.role='user'
    LIMIT 1
");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows == 0) {
    header("Location: admin.php");
    exit();
}

$user = $userResult->fetch_assoc();

/* =========================
   GET TRANSACTIONS
========================= */

$transStmt = $conn->prepare("
    SELECT t.amount,
           t.payment_date,
           sp.name AS plan_name
    FROM transactions t
    LEFT JOIN subscription_plans sp
        ON t.plan_id = sp.id
    WHERE t.user_id=?
    ORDER BY t.payment_date DESC
");
$transStmt->bind_param("i", $user_id);
$transStmt->execute();
$transactions = $transStmt->get_result();

/* =========================
   TOTAL PAID
========================= */

$totalStmt = $conn->prepare("
    SELECT SUM(amount) AS total
    FROM transactions
    WHERE user_id=?
");
$totalStmt->bind_param("i", $user_id);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalData = $totalResult->fetch_assoc();
$totalPaid = $totalData['total'] ? $totalData['total'] : 0;

?>

<!DOCTYPE html>
<html>
<head>
<title>User Details</title>
<style>
body{
    font-family:Arial;
    background:#111;
    color:#fff;
    padding:40px;
}
.top{
    display:flex;
    justify-content:space-between;
    margin-bottom:30px;
}
.card{
    background:#222;
    padding:20px;
    border-radius:10px;
    margin-bottom:30px;
}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    padding:10px;
    border:1px solid #333;
}
th{
    background:#222;
}
a.button{
    padding:6px 10px;
    background:#444;
    color:#fff;
    text-decoration:none;
    border-radius:4px;
}
</style>
</head>
<body>

<div class="top">
    <h2>User Details</h2>
    <a class="button" href="admin.php">Back to Admin</a>
</div>

<div class="card">
    <h3>User Info</h3>
    <p>Email: <?php echo $user['email']; ?></p>
    <p>Plan: <?php echo $user['plan_name'] ? $user['plan_name'] : 'None'; ?></p>
    <p>Expiry: <?php echo $user['end_date'] ? $user['end_date'] : '-'; ?></p>
    <p>Status: <?php echo $user['status'] ? $user['status'] : 'No Subscription'; ?></p>
    <p>Total Paid: ₱<?php echo number_format($totalPaid,2); ?></p>
</div>

<h3>Transaction History</h3>

<table>
<tr>
<th>Plan</th>
<th>Amount</th>
<th>Payment Date</th>
</tr>

<?php if($transactions->num_rows > 0): ?>
    <?php while($row = $transactions->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['plan_name']; ?></td>
        <td>₱<?php echo number_format($row['amount'],2); ?></td>
        <td><?php echo $row['payment_date']; ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="3">No transactions found.</td>
</tr>
<?php endif; ?>

</table>

</body>
</html>