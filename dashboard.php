<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db.php";

include "subscription_check.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================================
   SUBSCRIPTION CHECK
================================ */

$subStmt = $conn->prepare("
    SELECT end_date 
    FROM user_subscriptions 
    WHERE user_id=? AND status='active'
    ORDER BY end_date DESC 
    LIMIT 1
");

$subStmt->bind_param("i", $user_id);
$subStmt->execute();
$subResult = $subStmt->get_result();

if ($subResult->num_rows > 0) {

    $subscription = $subResult->fetch_assoc();
    $today = date('Y-m-d');

    if ($today > $subscription['end_date']) {

        $update = $conn->prepare("
            UPDATE user_subscriptions 
            SET status='expired' 
            WHERE user_id=? AND status='active'
        ");
        $update->bind_param("i", $user_id);
        $update->execute();

        header("Location: subscription_expired.php");
        exit();
    }

} else {
    header("Location: subscription.php");
    exit();
}

/* ================================
   PUBLISH POST
================================ */

if (isset($_GET['publish_id']) && isset($_GET['platform'])) {

    $post_id  = intval($_GET['publish_id']);
    $platform = $_GET['platform'];

    $stmt = $conn->prepare("UPDATE posts SET status='Published' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();

    if ($platform == "Facebook") {
        header("Location: https://www.facebook.com/");
    } elseif ($platform == "Twitter") {
        header("Location: https://twitter.com/compose/tweet");
    } elseif ($platform == "LinkedIn") {
        header("Location: https://www.linkedin.com/feed/");
    } elseif ($platform == "TikTok") {
        header("Location: https://www.tiktok.com/upload");
    } elseif ($platform == "YouTube") {
        header("Location: https://www.youtube.com/upload");
    }

    exit();
}

/* ================================
   ADD POST
================================ */

if (isset($_POST['add_post'])) {

    $content   = $_POST['content'];
    $platforms = isset($_POST['platforms']) ? implode(", ", $_POST['platforms']) : "";
    $date      = $_POST['post_date'];
    $time      = $_POST['post_time'];

    $media_path = null;

    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {

        $upload_dir = "uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['media']['name']);
        $target    = $upload_dir . $file_name;

        move_uploaded_file($_FILES['media']['tmp_name'], $target);
        $media_path = $target;
    }

    $stmt = $conn->prepare("
        INSERT INTO posts 
        (user_id, content, platforms, post_date, post_time, status, media_path)
        VALUES (?, ?, ?, ?, ?, 'Queued', ?)
    ");
    $stmt->bind_param("isssss", $user_id, $content, $platforms, $date, $time, $media_path);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}

/* ================================
   FETCH POSTS
================================ */

$start = date('Y-m-d', strtotime('monday this week'));
$end   = date('Y-m-d', strtotime('friday this week'));

$stmt = $conn->prepare("
    SELECT * FROM posts
    WHERE user_id=?
    AND status='Queued'
    AND post_date BETWEEN ? AND ?
    ORDER BY post_date, post_time
");

$stmt->bind_param("iss", $user_id, $start, $end);
$stmt->execute();
$result = $stmt->get_result();

$posts_by_day = [];

while ($row = $result->fetch_assoc()) {
    $dayName = date('l', strtotime($row['post_date']));
    $posts_by_day[$dayName][] = $row;
}

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
?>


<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(139,92,246,0.35), transparent 40%),
                radial-gradient(circle at bottom right, rgba(0,255,200,0.25), transparent 40%),
                #050510;
            color: #fff;
            min-height: 100vh;
        }

        .topbar {
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            letter-spacing: 1px;
        }

        .nav a {
            margin-left: 20px;
            text-decoration: none;
            color: #00ffc8;
            font-weight: 600;
        }

        .container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .section {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.15);
            box-shadow: 0 0 40px rgba(139,92,246,0.2);
        }

        h2 {
            font-family: 'Orbitron', sans-serif;
            margin-bottom: 20px;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        input, select {
            padding: 10px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        button {
            padding: 10px 18px;
            border: none;
            border-radius: 20px;
            background: linear-gradient(135deg,#8b5cf6,#00ffc8);
            color: #fff;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
        }

        .publish-btn {
            width: 100%;
            margin-top: 6px;
            font-size: 12px;
        }

        .week-calendar {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        .day-column {
            background: rgba(255,255,255,0.06);
            border-radius: 20px;
            padding: 15px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
        }

        .day-header {
            font-weight: 600;
            margin-bottom: 15px;
            color: #00ffc8;
        }

        .post-card {
            background: rgba(255,255,255,0.08);
            padding: 12px;
            border-radius: 18px;
            margin-bottom: 10px;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .post-time {
            font-size: 12px;
            color: #aaa;
        }

        .small {
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

<?php if(isset($subscriptionExpired) && $subscriptionExpired): ?>

<div id="holdOverlay">
    <div class="holdBox">
        <h2>Your Account Is On Hold</h2>
        <p>Your subscription has expired.</p>
        <a href="subscription.php">
            <button>Update Payment</button>
        </a>
    </div>
</div>

<style>
#holdOverlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.9);
    display:flex;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.holdBox{
    background:#111;
    padding:40px;
    border-radius:10px;
    text-align:center;
}

.holdBox button{
    padding:10px 20px;
    background:red;
    color:#fff;
    border:none;
    cursor:pointer;
}
</style>

<?php endif; ?>

<div class="topbar">
    <div class="logo">SOCIALHUB</div>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="create.php">Ideas</a>
        <a href="analytics.php">Analytics</a>
        <a href="email.php">Email</a>
        <a href="logout.php">Logout</a>
        <a href="subscription.php">Subscription</a>
    </div>
</div>


<div class="container">

<div class="section">
    <h2>Create Post</h2>

    <form method="POST" enctype="multipart/form-data">
        <textarea name="content" required></textarea>

        <div style="margin:15px 0;">
            <label><input type="checkbox" name="platforms[]" value="Facebook"> Facebook</label>
            <label><input type="checkbox" name="platforms[]" value="Twitter"> Twitter</label>
            <label><input type="checkbox" name="platforms[]" value="LinkedIn"> LinkedIn</label>
            <label><input type="checkbox" name="platforms[]" value="TikTok"> TikTok</label>
            <label><input type="checkbox" name="platforms[]" value="YouTube"> YouTube</label>
        </div>

        <input type="file" name="media" accept="image/*,video/*">
        <br><br>
        <input type="date" name="post_date" required>
        <input type="time" name="post_time" required>
        <br><br>
        <button name="add_post">Add to Queue</button>
    </form>
</div>

<div class="section">
    <h2>Weekly Queue</h2>

    <div class="week-calendar">

        <?php foreach ($days as $day): ?>
            <div class="day-column" onclick="openDay('<?php echo $day; ?>')">

                <div class="day-header">
                    <?php echo substr($day, 0, 3); ?>
                </div>

                <?php if (isset($posts_by_day[$day])): ?>
                    <?php foreach ($posts_by_day[$day] as $post): ?>
                        <div class="post-card">

                            <div class="post-time">
                                <?php echo date('g:i A', strtotime($post['post_time'])); ?>
                            </div>

                            <p>
                                <?php echo htmlspecialchars(substr($post['content'], 0, 50)); ?>
                            </p>

                            <?php
                                $platforms = explode(", ", $post['platforms']);
                                foreach ($platforms as $platform):
                            ?>
                                <a href="dashboard.php?publish_id=<?php echo $post['id']; ?>&platform=<?php echo urlencode($platform); ?>">
                                    <button type="button" class="publish-btn">
                                        Publish to <?php echo $platform; ?>
                                    </button>
                                </a>
                            <?php endforeach; ?>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="small">No posts</div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>
</div>

</div>

<script>
function openDay(day) {
    window.location.href = "day.php?day=" + day;
}
</script>

</body>
</html>