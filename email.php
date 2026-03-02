<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$status = "";

if(isset($_POST['send_email'])){
    $to = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $headers = "From: noreply@socialhub.com";

    if(mail($to, $subject, $message, $headers)){
        $status = "Email sent successfully.";
    } else {
        $status = "Email failed to send.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Email</title>

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
    max-width:900px;
    margin:60px auto;
    padding:0 20px;
}

.section{
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(25px);
    border-radius:24px;
    padding:40px;
    border:1px solid rgba(255,255,255,0.15);
    box-shadow:0 0 40px rgba(139,92,246,0.2);
}

h2{
    font-family:'Orbitron',sans-serif;
    margin-bottom:25px;
}

input, textarea{
    width:100%;
    padding:14px;
    border-radius:20px;
    border:1px solid rgba(255,255,255,0.15);
    background:rgba(255,255,255,0.05);
    color:#fff;
    margin-bottom:18px;
}

textarea{
    resize:none;
}

button{
    padding:12px 20px;
    border:none;
    border-radius:20px;
    background:linear-gradient(135deg,#8b5cf6,#00ffc8);
    color:#fff;
    cursor:pointer;
    font-weight:600;
}

.success{
    color:#00ffc8;
    margin-bottom:20px;
}

.error{
    color:red;
    margin-bottom:20px;
}
</style>
</head>

<body>

<div class="topbar">
    <div class="logo">SOCIALHUB</div>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="email.php">Email</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">

<div class="section">
<h2>Email Communication</h2>

<?php if($status!=""): ?>
<p class="<?php echo ($status == 'Email sent successfully.') ? 'success' : 'error'; ?>">
<?php echo $status; ?>
</p>
<?php endif; ?>

<form method="POST">

<input type="email" name="email" placeholder="Recipient Email" required>

<input type="text" name="subject" placeholder="Subject" required>

<textarea name="message" rows="6" placeholder="Write your message..." required></textarea>

<button name="send_email">Send Email</button>

</form>

</div>

</div>

</body>
</html>