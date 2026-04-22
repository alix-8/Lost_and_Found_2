<?php
session_start();

require_once __DIR__ . "/../../database/db.php";
$db = get_db();

// 1. Security Check
if (!isset($_SESSION["user"])) {
    header("Location: ../../login.php");
    exit;
}
$user = $_SESSION["user"];

// ==========================================
// LOGIC: FETCH NOTIFSS GALING KAY ADMIN
// ==========================================
$user_id = $_SESSION['user']['id'];
$notifQuery = $db->query("
    SELECT notifications.*, items.title AS item_title
    FROM notifications
    LEFT JOIN items ON items.id = notifications.item_id
    WHERE notifications.notify_to = $user_id AND notifications.type = 'to_user' AND notifications.status != 'resolved'
    ORDER BY notifications.created_at DESC
");

$userNotifications = [];
while ($n = $notifQuery->fetchArray(SQLITE3_ASSOC)) {
    $userNotifications[] = $n;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>About Us - CampusFind</title>
    <link rel="icon" type="image/x-icon" href="../../assets/search.png">    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS FILES -->
    <link rel="stylesheet" href="../../reusable/header.css">
    <link rel="stylesheet" href="../../reusable/about_us.css"> 
    <link rel="stylesheet" href="../../reusable/footer.css"> 
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="m-0 border-0">

    <!-- NAVIGATION -->
    <nav class="navbar p-3 sticky-top">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions"
                    aria-expanded="false" aria-label="Toggle navigation">
                <img src="/assets/hamburger.png" alt="hamburger icon" width="20px" height="20px">
            </button>
            <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1"
                id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
                <div class="offcanvas-body">
                    <a href="dashboard_user.php">Dashboard</a>
                    <a href="myposts_user.php">My Wall</a>
                    <a href="about_us.php" style="color: #2289e6; font-weight: 700;">About</a>
                    <a class="logout" href="../../logout.php" onclick="return confirm('Are you sure you want to LOG OUT?');">Log out</a>
                </div>
            </div>
            <strong><a class="navbar-brand me-auto" href="dashboard_user.php">Campus<span class="find">Find</span></a></strong>
            
            <?php 
            $notifCount = $db->querySingle("
                SELECT COUNT(*) 
                FROM notifications 
                WHERE status = 'unread' AND notify_to = $user_id AND type = 'to_user'
            ");
            ?>
            <div class = "ms-auto">
                <a href="myposts_user.php" class="notif mx-4">
                    🔔<?= $notifCount ?>
                </a>
                <a class="navbar-brand text-white" href="#">Hello, <?php echo htmlspecialchars($user["username"]); ?></a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container">
        
        <!-- HERO BANNER -->
        <div class="hero-section">
            <h1>Our Mission</h1>
            <p>To reconnect lost items with their owners through a simple, secure, and community-driven platform.</p>
        </div>

        <!-- FEATURES -->
        <div class="features-container">
            <div class="feature-box">
                <div class="feature-icon">📢</div>
                <h4>Post Items</h4>
                <p class="text-secondary">Easily report lost or found items with photos and location details.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">🔍</div>
                <h4>Smart Search</h4>
                <p class="text-secondary">Filter by category, date, or status to find what you are looking for.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">🤝</div>
                <h4>Secure Claim</h4>
                <p class="text-secondary">A verified process to ensure items return to their rightful owners.</p>
            </div>
        </div>

        <!-- TEAM SECTION -->
        <div class="team-section">
            <h2 class="mb-2">Meet the Team</h2>
            <p class="text-secondary mb-4">The developers behind the project</p>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="avatar-circle">A</div>
                    <div class="member-name">Alexandrian Bon</div>
                    <div class="member-role">Developer / Project Manager</div>
                </div>
                <div class="team-member">
                    <div class="avatar-circle">A</div>
                    <div class="member-name">Anne Stephanne Buenaflor</div>
                    <div class="member-role">Developer / Designer</div>
                </div>
                <div class="team-member">
                    <div class="avatar-circle">J</div>
                    <div class="member-name">Jess Carbonel</div>
                    <div class="member-role">Logic / Backend</div>
                </div>
                <div class="team-member">
                    <div class="avatar-circle">J</div>
                    <div class="member-name">Jay R Santos</div>
                    <div class="member-role">Backend / DB</div>
                </div>
                <div class="team-member">
                    <div class="avatar-circle">M</div>
                    <div class="member-name">Mel Magdaraog</div>
                    <div class="member-role">Developer</div>
                </div>
            </div>
        </div>

        <!-- ============================================= -->
        <!-- FAQ SECTION (INSERTED HERE) -->
        <!-- ============================================= -->
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>
            
            <div class="accordion" id="faqAccordion">
                
                <!-- Question 1 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                            How do I report a lost item?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Go to the <strong>My Wall</strong> page and click "Report Lost Item". Fill in the details such as description, location lost, and upload a photo if you have one.
                        </div>
                    </div>
                </div>

                <!-- Question 2 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                            How do I claim a found item?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Browse the Dashboard for "Found" items. If you see something that belongs to you, click <strong>"See Details"</strong> to see more infos about the item then visit the Lost & Found office with proof of ownership.
                        </div>
                    </div>
                </div>

                <!-- Question 3 -->
                <div class="accordion-item" id="lf_office">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                            Where is the Lost & Found office located?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            The main office is located at the <strong>Student Affairs Building, Room 101</strong>. We are open from 8:00 AM to 5:00 PM, Monday to Friday.
                        </div>
                    </div>
                </div>

                <!-- Question 4 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                            Can I delete my post after the item is found?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes! Go to <strong>My Wall</strong>, find the item you posted, and click the "Delete" button. We recommend doing this once your item has been successfully recovered.
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- ============================================= -->

        
    </div>
    <footer>
        <!-- FOOTER -->
        <div class="layer1">
            <div class="brand">
                <h4>Campus<span class="find">Find</span></h4>
                Your Campus Lost & Found Hub. Helping students and staff reunite with their lost items quickly and easily.
            </div>
            <div class="qlinks">
                <strong>Quick Links</strong>
                <a href="dashboard_user.php">Dashboard</a>
                <a href="myposts_user.php">My Wall</a>
            </div>
            <div class="contacts">
                <strong>Contact</strong>
                Lost & Found Office
                Main Building, Room 101
                campusfind@university.edu
            </div>
        </div>
        <div class="layer2">
            &copy 2025 CampusFind. All rights reserved.
        </div>
        
    </footer>

</body>
</html>