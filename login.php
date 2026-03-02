<?php
session_start();
include "db.php";

$error = "";

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){

        $user = $result->fetch_assoc();

        if($password === $user['password']){

            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Wrong password.";
        }

    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:
        radial-gradient(circle at top left, rgba(139,92,246,0.35), transparent 40%),
        radial-gradient(circle at bottom right, rgba(0,255,200,0.25), transparent 40%),
        #050510;
    overflow:hidden;
}

.card{
    width:380px;
    padding:50px 40px;
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
    background:linear-gradient(135deg, rgba(139,92,246,0.5), rgba(0,255,200,0.4));
    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    -webkit-mask-composite:xor;
    mask-composite:exclude;
    pointer-events:none;
}

h2{
    font-family:'Orbitron',sans-serif;
    font-size:28px;
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
    background:linear-gradient(135deg,#8b5cf6,#00ffc8);
    box-shadow:0 0 20px rgba(139,92,246,0.4);
    transition:0.3s;
}

button:hover{
    box-shadow:0 0 30px rgba(0,255,200,0.6);
    transform:translateY(-2px);
}

p{
    margin-top:25px;
    color:rgba(255,255,255,0.7);
    text-align:center;
}

a{
    color:#00ffc8;
    text-decoration:none;
    font-weight:600;
}

.error{
    margin-top:15px;
    color:#ff6b6b;
    text-align:center;
    font-size:14px;
}
</style>
</head>

<body>

<div class="card">
<h2>LOGIN</h2>

<form method="POST">
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button name="login">Login</button>
</form>

<?php if($error != ""){ ?>
<p class="error"><?php echo $error; ?></p>
<?php } ?>

<p>No account? <a href="register.php">Register</a></p>
</div>

</body>
</html>
