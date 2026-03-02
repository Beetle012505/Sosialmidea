<?php
session_start();
include "db.php";

$error = "";

if (isset($_POST['login'])) {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT * FROM users
        WHERE email=? AND password=? AND role='admin'
    ");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();
        $_SESSION['admin_id'] = $user['id'];

        header("Location: admin.php");
        exit();

    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:
        radial-gradient(circle at top left, rgba(139,92,246,0.35), transparent 40%),
        radial-gradient(circle at bottom right, rgba(0,255,200,0.25), transparent 40%),
        #050510;

    overflow:auto;
    padding:20px;
}

.card{
    width:90%;
    max-width:380px;
    padding:45px 35px;
    border-radius:24px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(25px);
    -webkit-backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.15);

    box-shadow:
        0 0 40px rgba(139,92,246,0.25),
        0 0 60px rgba(0,255,200,0.15);

    position:relative;
}

.card::before{
    content:"";
    position:absolute;
    inset:0;
    border-radius:24px;
    padding:1px;

    background:linear-gradient(
        135deg,
        rgba(139,92,246,0.5),
        rgba(0,255,200,0.4)
    );

    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);

    -webkit-mask-composite:xor;
    mask-composite:exclude;

    pointer-events:none;
}

h2{
    font-family:'Orbitron',sans-serif;
    font-size:26px;
    margin-bottom:30px;
    color:#fff;
    text-align:center;
    letter-spacing:1px;
}

input{
    width:100%;
    padding:14px;
    margin:12px 0;

    border-radius:24px;

    border:1px solid rgba(255,255,255,0.15);

    background:rgba(255,255,255,0.05);

    color:#fff;

    outline:none;

    backdrop-filter:blur(10px);

    font-size:14px;
}

input::placeholder{
    color:rgba(255,255,255,0.6);
}

input:focus{
    border:1px solid rgba(139,92,246,0.7);

    box-shadow:0 0 15px rgba(139,92,246,0.5);
}

button{
    width:100%;
    padding:14px;
    margin-top:15px;

    border:none;

    border-radius:24px;

    font-weight:600;

    color:#fff;

    cursor:pointer;

    background:linear-gradient(
        135deg,
        #8b5cf6,
        #00ffc8
    );

    box-shadow:0 0 20px rgba(139,92,246,0.4);

    transition:0.3s;

    font-size:15px;
}

button:hover{
    box-shadow:0 0 30px rgba(0,255,200,0.6);

    transform:translateY(-2px);
}

.error{
    margin-top:15px;
    color:#ff6b6b;
    text-align:center;
    font-size:14px;
}

/* mobile adjustments */

@media (max-width:480px){

    .card{
        padding:35px 25px;
    }

    h2{
        font-size:22px;
    }

}

</style>
</head>

<body>

<div class="card">

<h2>ADMIN LOGIN</h2>

<form method="POST">

<input type="email" name="email" placeholder="Admin Email" required>

<input type="password" name="password" placeholder="Password" required>

<button name="login">Login</button>

</form>

<?php if($error != ""){ ?>

<p class="error"><?php echo $error; ?></p>

<?php } ?>

</div>

</body>
</html>