<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(!isset($_GET['day'])){
    header("Location: dashboard.php");
    exit();
}

$day = $_GET['day'];

$stmt = $conn->prepare("
    SELECT * FROM posts
    WHERE user_id=?
    AND status='Queued'
    AND DAYNAME(post_date)=?
    ORDER BY post_time
");
$stmt->bind_param("is", $user_id, $day);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $day; ?></title>
<style>
body{background:#111;color:#fff;font-family:Arial;padding:40px;}
.post{background:#1a1a1a;padding:15px;margin-bottom:15px;border-radius:10px;}
.time{color:#00ffc8;font-size:14px;}
a{color:#00ffc8;text-decoration:none;}
</style>
</head>
<body>

<h2><?php echo $day; ?> Posts</h2>
<a href="dashboard.php">Back</a>
<br><br>

<?php if($result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>
<div class="post">
<div class="time"><?php echo date('g:i A', strtotime($row['post_time'])); ?></div>
<p><?php echo htmlspecialchars($row['content']); ?></p>
</div>
<?php endwhile; ?>
<?php else: ?>
<p>No posts for this day.</p>
<?php endif; ?>

</body>
</html>