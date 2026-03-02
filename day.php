<?php
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if(!isset($_GET['day'])){
    header("Location: dashboard.php");
    exit();
}

$dayName = $_GET['day'];
$user_id = $_SESSION['user_id'];

/* =========================
   UPLOAD (MARK AS PUBLISHED)
========================= */
if(isset($_GET['upload_id'])){
    $post_id = intval($_GET['upload_id']);

    $stmt = $conn->prepare("UPDATE posts SET status='Published' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();

    header("Location: day.php?day=".$dayName);
    exit();
}

/* =========================
   DELETE POST
========================= */
if(isset($_GET['delete_id'])){
    $post_id = intval($_GET['delete_id']);

    $stmt = $conn->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();

    header("Location: day.php?day=".$dayName);
    exit();
}

/* =========================
   GET POSTS BY DAY
========================= */
$stmt = $conn->prepare("
    SELECT * FROM posts 
    WHERE user_id = ?
    AND DAYNAME(post_date) = ?
    ORDER BY post_date ASC, post_time ASC
");
$stmt->bind_param("is", $user_id, $dayName);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title><?php echo $dayName; ?> Schedule</title>
<style>
body{margin:0;font-family:Arial;background:#f5f6f8;}
.container{max-width:900px;margin:40px auto;}
.section{background:#fff;padding:25px;border-radius:12px;border:1px solid #e5e7eb;}
.post-card{border:1px solid #e5e7eb;padding:15px;border-radius:8px;margin-bottom:15px;}
.post-date{font-size:13px;color:#6b7280;}
.post-time{font-size:12px;color:#6b7280;}
.badge{
    display:inline-block;
    padding:4px 8px;
    border-radius:6px;
    font-size:12px;
    margin-right:5px;
    margin-top:5px;
    background:#e5e7eb;
}
.status-Queued{background:#facc15;color:#000;}
.status-Published{background:#16a34a;color:#fff;}
button{
    padding:6px 10px;
    border:none;
    border-radius:6px;
    background:#111827;
    color:#fff;
    cursor:pointer;
    margin-top:10px;
    margin-right:5px;
}
.upload-btn{background:#2563eb;}
.delete-btn{background:#dc2626;}
img, video{
    margin-top:10px;
    max-width:250px;
    border-radius:8px;
    display:block;
}
.back{
    text-decoration:none;
    display:inline-block;
    margin-bottom:20px;
}
</style>
</head>

<body>

<div class="container">

<a class="back" href="dashboard.php">← Back to Dashboard</a>

<div class="section">
<h2><?php echo $dayName; ?> Schedule</h2>

<?php if($result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>

<div class="post-card">

<div class="post-date">Date: <?php echo $row['post_date']; ?></div>
<div class="post-time">Time: <?php echo date('g:i A', strtotime($row['post_time'])); ?></div>

<!-- STATUS -->
<span class="badge status-<?php echo $row['status']; ?>">
<?php echo $row['status']; ?>
</span>

<br>

<p><?php echo htmlspecialchars($row['content']); ?></p>

<!-- PLATFORM BADGES -->
<?php 
$platforms = explode(",", $row['platforms']);
foreach($platforms as $platform):
?>
<span class="badge"><?php echo trim($platform); ?></span>
<?php endforeach; ?>

<!-- MEDIA PREVIEW -->
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

<br>

<?php if($row['status'] == "Queued"): ?>

<a href="day.php?day=<?php echo $dayName; ?>&upload_id=<?php echo $row['id']; ?>" 
   onclick="return confirm('Upload this post? It will be marked as Published.')">
<button class="upload-btn">Upload</button>
</a>

<?php endif; ?>

<a href="day.php?day=<?php echo $dayName; ?>&delete_id=<?php echo $row['id']; ?>" 
   onclick="return confirm('Delete this post permanently?')">
<button class="delete-btn">Delete</button>
</a>

</div>

<?php endwhile; ?>
<?php else: ?>
<p>No scheduled posts for <?php echo $dayName; ?>.</p>
<?php endif; ?>

</div>
</div>

</body>
</html>
