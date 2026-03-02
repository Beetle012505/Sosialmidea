<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

/* =========================
   RESET REVENUE
========================= */

if (isset($_POST['reset_revenue'])) {
    $conn->query("DELETE FROM transactions");
    header("Location: admin.php");
    exit();
}

/* =========================
   USER ACTIONS
========================= */

if (isset($_GET['expire'])) {
    $userId = intval($_GET['expire']);

    $stmt = $conn->prepare("
        UPDATE user_subscriptions
        SET status='expired'
        WHERE user_id=? AND status='active'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    header("Location: admin.php");
    exit();
}

if (isset($_GET['extend'])) {
    $userId = intval($_GET['extend']);

    $stmt = $conn->prepare("
        UPDATE user_subscriptions
        SET end_date = DATE_ADD(end_date, INTERVAL 30 DAY)
        WHERE user_id=? AND status='active'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    header("Location: admin.php");
    exit();
}

if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']);

    $conn->query("DELETE FROM transactions WHERE user_id=$userId");
    $conn->query("DELETE FROM user_subscriptions WHERE user_id=$userId");
    $conn->query("DELETE FROM users WHERE id=$userId");

    header("Location: admin.php");
    exit();
}

/* =========================
   REVENUE SUMMARY
========================= */

$revenue = $conn->query("SELECT SUM(amount) AS total FROM transactions");
$revenueData = $revenue->fetch_assoc();
$totalRevenue = $revenueData['total'] ? $revenueData['total'] : 0;

$activeCount = $conn->query("SELECT COUNT(*) AS total FROM user_subscriptions WHERE status='active'");
$activeData = $activeCount->fetch_assoc();

$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='user'");
$totalUsersData = $totalUsers->fetch_assoc();

/* =========================
   SEARCH + FILTER
========================= */

$where = "WHERE u.role='user'";

if (isset($_GET['search']) && $_GET['search'] != '') {
    $search = $conn->real_escape_string($_GET['search']);
    $where .= " AND u.email LIKE '%$search%'";
}

if (isset($_GET['expiring'])) {
    $where .= " AND us.end_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
}

/* =========================
   USER LIST
========================= */

$query = "
    SELECT u.id,
           u.email,
           us.end_date,
           us.status,
           sp.name AS plan_name
    FROM users u
    LEFT JOIN user_subscriptions us 
        ON u.id = us.user_id 
        AND us.status='active'
    LEFT JOIN subscription_plans sp 
        ON us.plan_id = sp.id
    $where
    ORDER BY u.id ASC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>

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
    padding:40px;
}

h2{
    font-family:'Orbitron',sans-serif;
    margin-bottom:20px;
}

.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:40px;
}

.button{
    padding:8px 14px;
    border:none;
    border-radius:20px;
    background:linear-gradient(135deg,#8b5cf6,#00ffc8);
    color:#fff;
    text-decoration:none;
    cursor:pointer;
    font-size:12px;
}

.cards{
    display:flex;
    gap:20px;
    margin-bottom:40px;
}

.card{
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(25px);
    border-radius:20px;
    padding:25px;
    width:250px;
    border:1px solid rgba(255,255,255,0.15);
}

table{
    width:100%;
    border-collapse:collapse;
    background:rgba(255,255,255,0.06);
    border-radius:20px;
    overflow:hidden;
}

th, td{
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,0.1);
    font-size:14px;
}

th{
    background:rgba(255,255,255,0.1);
    text-align:left;
}

tr:hover{
    background:rgba(255,255,255,0.08);
}

input{
    padding:8px;
    border-radius:20px;
    border:1px solid rgba(255,255,255,0.15);
    background:rgba(255,255,255,0.05);
    color:#fff;
}

.active{color:#00ffc8;font-weight:600;}
.expired{color:red;font-weight:600;}
.expiring{color:orange;font-weight:600;}

.reset{
    background:red;
}
</style>
</head>
<body>

<div class="top">
    <h2>Admin Dashboard</h2>
    <a class="button" href="admin_logout.php">Logout</a>
</div>

<div class="cards">

    <div class="card">
        <h3>Total Revenue</h3>
        <br>
        ₱<?php echo number_format($totalRevenue,2); ?>
        <form method="POST" style="margin-top:15px;">
            <button type="submit" name="reset_revenue" class="button reset" onclick="return confirm('Reset ALL revenue?')">
                Reset Revenue
            </button>
        </form>
    </div>

    <div class="card">
        <h3>Total Users</h3>
        <br>
        <?php echo $totalUsersData['total']; ?>
    </div>

    <div class="card">
        <h3>Active Subs</h3>
        <br>
        <?php echo $activeData['total']; ?>
    </div>

</div>

<form method="GET" style="margin-bottom:30px;">
    <input type="text" name="search" placeholder="Search email">
    <button type="submit" class="button">Search</button>
    <a class="button" href="admin.php?expiring=1">Expiring Soon</a>
</form>

<table>
<tr>
<th>ID</th>
<th>Email</th>
<th>Plan</th>
<th>Expiry</th>
<th>Status</th>
<th>Actions</th>
</tr>

<?php while($row = $result->fetch_assoc()): 

$statusLabel = "No Plan";
$statusClass = "";

if ($row['status'] == 'active' && $row['end_date']) {
    $daysLeft = (strtotime($row['end_date']) - time()) / 86400;
    if ($daysLeft <= 0) {
        $statusLabel = "Expired";
        $statusClass = "expired";
    } elseif ($daysLeft <= 3) {
        $statusLabel = "Expiring Soon";
        $statusClass = "expiring";
    } else {
        $statusLabel = "Active";
        $statusClass = "active";
    }
}
?>

<tr>
<td><?php echo $row['id']; ?></td>
<td>
<a class="button" href="admin_user.php?id=<?php echo $row['id']; ?>">
<?php echo $row['email']; ?>
</a>
</td>
<td><?php echo $row['plan_name'] ? $row['plan_name'] : 'None'; ?></td>
<td><?php echo $row['end_date'] ? $row['end_date'] : '-'; ?></td>
<td class="<?php echo $statusClass; ?>">
<?php echo $statusLabel; ?>
</td>
<td>
<a class="button" href="?extend=<?php echo $row['id']; ?>">Extend</a>
<a class="button" href="?expire=<?php echo $row['id']; ?>">Expire</a>
<a class="button" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete user?')">Delete</a>
</td>
</tr>

<?php endwhile; ?>

</table>

</body>
</html>