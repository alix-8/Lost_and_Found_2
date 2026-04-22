<?php
// register page for users (di nagre-register ang admin)
require_once __DIR__ . "/database/db.php";;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (!$name || !$email || !$password) {
        $error = "All fields are required.";
    } else {
        $db = get_db();

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bindValue(1, $email, SQLITE3_TEXT);
        $result = $stmt->execute();

        if ($result->fetchArray()) {
            $error = "Email already registered.";
        } else {
            // Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $name, SQLITE3_TEXT);
            $stmt->bindValue(2, $email, SQLITE3_TEXT);
            $stmt->bindValue(3, $hashedPassword, SQLITE3_TEXT);
            $stmt->execute();
            header("Location: login.php?registered=true");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="icon" type="image/x-icon" href="../assets/search.png">
    <link rel="stylesheet" href="../reusable/style.css">
</head>

<body>
  <div class="wrapper-register m-0">

    <!-- LEFT SIDE -->
    <div class="left-register  m-0">
      <img src="data:image/jpeg;base64,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" 
           alt="box">
    <p></p>
      <div class="title">Join Our Community</div>
      <p></p>
      <div class="desc">Create an account to report lost items, help others find their belongings, and connect with the campus community.</div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-register  m-0">
      <div class="container">
          <p><a href="index.html" class="m-0">⬅️ Back to landing page</a></p>
          <h2>Create an Account</h2>
          <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
          <form method="POST" action="">
              <input type="text" name="name" placeholder="Full Name" required>
              <input type="email" name="email" placeholder="Email Address" required>
              <input type="password" name="password" placeholder="Password" required>
              <button class="btn" type="submit">Register</button>
          </form>
          <p>Already have an account? <a href="login.php">Login</a></p>
      </div>
    </div>

  </div>
</body>


</html>