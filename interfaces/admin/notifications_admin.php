<?php
session_start();

// SECURITY CHECK
if (!isset($_SESSION["user"])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SESSION["user"]["role"] !== 'admin') {
    header("Location: ../users/dashboard_user.php");
    exit;
}

$admin = $_SESSION["user"];
require_once __DIR__ . "/../../database/db.php";
$db = get_db();

// action routing
$action = $_GET["action"] ?? "list";
$msg = $_GET["msg"] ?? "";
$error = "";


// =====================================
// fetch notifss from user to admin
// =====================================
$adminNotifications = [];
$user_id = $_SESSION['user']['id'];

$q = $db->query("
    SELECT 
        n.*, 
        i.title AS item_title,
        i.description,
        i.image_path,
        i.item_status,
        i.location_lost,
        i.location_found,
        i.date_lost_or_found,
        c.name AS category_name,
        u.username AS posted_by
    FROM notifications n
    LEFT JOIN items i ON n.item_id = i.id
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE n.notify_to = $user_id AND n.status != 'resolved' AND  n.type = 'to_admin'
    ORDER BY n.created_at DESC
");

while($row = $q->fetchArray(SQLITE3_ASSOC)) {
    $adminNotifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications</title>
    <link rel="icon" type="image/x-icon" href="../../assets/search.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../reusable/header.css">
    <link rel="stylesheet" href="../../reusable/cards.css">
    <link rel="stylesheet" href="../../reusable/form.css">
    <link rel="stylesheet" href="../../reusable/hero.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <nav class="navbar p-3 sticky-top">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions"
                aria-expanded="false" aria-label="Toggle navigation">
            <img src="/assets/hamburger.png" alt="hamburger icon" width="20px" height="20px">
        </button>
        <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1"
                id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
            <div class="offcanvas-body">
                <a href="dashboard_admin.php" style="color: #2289e6; font-weight: 700;">Dashboard</a>
                <a href="myposts_admin.php" >My Wall</a>
                <!-- FIXED LOGOUT PATH :D -->
                <a class="logout" href="../../logout.php" onclick = "return confirm('Are you sure you want to LOG OUT?');">Log out</a>
            </div>
        </div>
        <strong><a class="navbar-brand me-auto d-none d-md-inline" href="#">Found<span class = "Box">Box</a></strong>

        <?php 
        $adminId = $_SESSION['user']['id']; 
        $notifCount = $db->querySingle("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE status = 'unread' AND notify_to = $adminId AND type = 'to_admin'
        ");
        ?>

        <div class = "ms-auto">
            <a href="notifications_admin.php" class="notif mx-4">
                🔔<?= $notifCount ?>
            </a>
            <a class="navbar-brand text-white" href="#">Hello, <?php echo htmlspecialchars($admin["username"]); ?></a>
    </nav>
    <div class="container mt-5">
        <div class="notifications row">
            <div class="col-12">
                <h3><strong>
                    <img src="/assets/bell.png" alt="bell" style="width:25px">
                    My Notifications
                </strong></h3>
                <hr>
            </div>

            <?php if (empty($adminNotifications)): ?>
                <div class="col-12">
                    <p class="text-muted">No notifications yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($adminNotifications as $note): ?>
                    <div class="col-md-6">
                        <a href="../admin/dashboard_admin.php#item-<?= $note['item_id']; ?>" 
                           class="text-decoration-none text-dark">
                            
                            <div class="notificationCard p-3 my-2 border rounded shadow-sm w-100">
                                <strong><?= htmlspecialchars($note['item_title']); ?></strong>
                                <p class="mb-1">
                                    <?= htmlspecialchars($note['message']); ?>
                                </p>
                                <small class="text-secondary">
                                    <?= $note["created_at"]; ?>
                                </small>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    </body>
</html>