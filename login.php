<?php
// login interface for both ADMIN and USER
session_start();
require_once __DIR__ . "/database/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $db = get_db();
    $stmt = $db->prepare("SELECT id, username, email, password_hash, role FROM users WHERE email = ?");
    $stmt->bindValue(1, $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    // authentication
    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user"] = [
            "id" => $user["id"],
            "username" => $user["username"],
            "email" => $user["email"],
            "role" => $user["role"]
        ];

        if ($user["role"] === "admin") {
            header("Location: interfaces/admin/dashboard_admin.php");
            exit;
        } else {
            header("Location: interfaces/users/dashboard_user.php");
            exit;
        }
    }
    $error = "Invalid email or password.";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/x-icon" href="../assets/search.png">
    <link rel="stylesheet" href="../reusable/style.css">
</head>

<body>

    <div class="wrapper">

        <!-- LEFT SIDE -->
        <div class="left">
            <div class="container">
                 <p><a href="index.html" class="m-0">⬅️ Back to landing page</a></p>
                <h2>Login to Campus Find</h2>
                <?php if (isset($_GET["registered"])) echo "<p style='color:green;'>Registration successful! Please login.</p>"; ?>
                <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
                <form method="POST" action="">
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button class="btn" type="submit">Login</button>
                </form>
                <p>Don’t have an account? <a href="register.php">Register</a></p>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="right">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRaXsBOBXzBjPE-gEZm7mH4U05TlK8mqI3VQ8RruSFQpg&s" 
                    alt="Magnifying Glass">
            <p></p>
            <div class="title">Find What You've Lost</div>
            <p></p>
            <div class="desc">Join thousands of students and staff who have successfully reunited with their lost belongings through CampusFind.</div>
        </div>

    </div>

</body>



</html> 