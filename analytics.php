<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_posts = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id=? AND status='Published'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_published = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id=? AND status='Queued'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_queued = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT * FROM posts 
                        WHERE user_id=? 
                        AND status='Published'
                        ORDER BY post_date DESC, post_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$published_posts = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Analytics</title>

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
    letter-spacing:1px;
}

.nav a{
    margin-left:20px;
    text-decoration:none;
    color:#00ffc8;
    font-weight:600;
}

.container{
    max-width:1300px;
    margin:40px auto;
    padding:0 20px;
}

.section{
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(25px);
    border-radius:24px;
    padding:30px;
    border:1px solid rgba(255,255,255,0.15);
    box-shadow:0 0 40px rgba(139,92,246,0.2);
}

h2{
    font-family:'Orbitron',sans-serif;
    margin-bottom:20px;
}

.stat-grid{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:20px;
    margin-bottom:40px;
}

.stat-card{
    background:rgba(255,255,255,0.08);
    padding:30px;
    border-radius:24px;
    text-align:center;
    border:1px solid rgba(255,255,255,0.15);
}

.stat-number{
    font-size:40px;
    font-weight:700;
    font-family:'Orbitron',sans-serif;
    margin-top:10px;
    background:linear-gradient(135deg,#8b5cf6,#00ffc8);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.post-card{
    background:rgba(255,255,255,0.08);
    padding:20px;
    border-radius:24px;
    border:1px solid rgba(255,255,255,0.15);
    margin-bottom:20px;
}

.post-date{
    font-size:13px;
    color:#aaa;
    margin-bottom:10px;
}

img, video{
    max-width:220px;
    margin-top:15px;
    border-radius:16px;
    border:1px solid rgba(255,255,255,0.2);
}
</style>
</head>

<body>

<div class="topbar">
    <div class="logo">SOCIALHUB</div>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="analytics.php">Analytics</a>
        <a href="email.php">Email</a>
        <a href="login.php">Logout</a>
    </div>
</div>

<div class="container">

<div class="section">
<h2>Analytics Overview</h2>

<div class="stat-grid">

<div class="stat-card">
<h3>Total Posts</h3>
<div class="stat-number"><?php echo $total_posts; ?></div>
</div>

<div class="stat-card">
<h3>Queued Posts</h3>
<div class="stat-number"><?php echo $total_queued; ?></div>
</div>

<div class="stat-card">
<h3>Published Posts</h3>
<div class="stat-number"><?php echo $total_published; ?></div>
</div>

</div>

<h2>Uploaded Posts</h2>

<?php if($published_posts->num_rows > 0): ?>
<?php while($row = $published_posts->fetch_assoc()): ?>

<div class="post-card">

<div class="post-date">
Date: <?php echo $row['post_date']; ?> |
Time: <?php echo date('g:i A', strtotime($row['post_time'])); ?>
</div>

<p><?php echo htmlspecialchars($row['content']); ?></p>

<?php if(!empty($row['media_path'])): ?>
<?php 
$ext = strtolower(pathinfo($row['media_path'], PATHINFO_EXTENSION));
?>
<?php if(in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
<img src="<?php echo $row['media_path']; ?>">
<?php elseif(in_array($ext, ['mp4','webm','ogg'])): ?>
<video controls>
<source src="<?php echo $row['media_path']; ?>">
</video>
<?php endif; ?>
<?php endif; ?>

</div>

<?php endwhile; ?>
<?php else: ?>
<p>No uploaded posts yet.</p>
<?php endif; ?>

</div>

</div>

</body>
</html>
