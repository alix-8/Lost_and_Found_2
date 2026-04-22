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

// ==========================================
// LOGIC: ADD POST (STORE) + IMAGE UPLOAD
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && $action === "store") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category_id = intval($_POST["category_id"] ?? 0);
    $item_status = trim($_POST["item_status"] ?? "");
    $location_lost = trim($_POST["location_lost"] ?? "");
    $location_found = trim($_POST["location_found"] ?? "");
    $date_lost_or_found = trim($_POST["date_lost_or_found"] ?? "");
    $current_location = trim($_POST["current_location"] ?? "");
    $user_id = $_SESSION["user"]["id"];
    
    // --- IMAGE UPLOAD LOGIC START DITO ---
    $image_path_db = null; // Default to null if no image uploaded

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['item_image']['tmp_name'];
        $fileName = $_FILES['item_image']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed extensions
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Create a unique name to prevent overwriting
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            
            $uploadFileDir = __DIR__ . '/../../uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Success :D
                $image_path_db = 'uploads/' . $newFileName; 
            }
        }
    }
    // --- IMAGE UPLOAD LOGIC END HEREE---

    if ($title !== "" && $item_status !== "") {
        $stmt = $db->prepare("
            INSERT INTO items (title, description, category_id, item_status, user_id, location_lost, location_found, date_lost_or_found, current_location, image_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bindValue(1, $title, SQLITE3_TEXT);
        $stmt->bindValue(2, $description, SQLITE3_TEXT);
        $stmt->bindValue(3, $category_id, SQLITE3_INTEGER);
        $stmt->bindValue(4, $item_status, SQLITE3_TEXT);
        $stmt->bindValue(5, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(6, $location_lost, SQLITE3_TEXT);
        $stmt->bindValue(7, $location_found, SQLITE3_TEXT);
        $stmt->bindValue(8, $date_lost_or_found, SQLITE3_TEXT);
        $stmt->bindValue(9, $current_location, SQLITE3_TEXT);
        $stmt->bindValue(10, $image_path_db, SQLITE3_TEXT); // Bind the image path
        
        $stmt->execute();

        header("Location: dashboard_admin.php?msg=Item+Added");
        exit;
    } else {
        $error = "Title and Type are required.";
        $action = "create";
    }
}


// ==========================================
// LOGIC: EDIT POST ITO
// ==========================================x
if ($_SERVER["REQUEST_METHOD"] === "POST" && $action === "edit") {
    $id = (int)($_POST['id'] ?? 0);

    $title = trim($_POST['title'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $category_id = intval($_POST["category_id"] ?? 0);
    $item_status = trim($_POST['item_status'] ?? "");
    $location_lost = trim($_POST['location_lost'] ?? "");
    $location_found = trim($_POST['location_found'] ?? "");
    $date_lost_or_found = trim($_POST['date_lost_or_found'] ?? "");
    $current_location = trim($_POST['current_location'] ?? "");

    if ($id > 0 && $title !== "" && $item_status !== "") {
        $stmt = $db->prepare("
            UPDATE items
            SET title = ?, description = ?, category_id = ?, item_status = ?, location_lost = ?, location_found = ?, date_lost_or_found = ?, current_location = ?
            WHERE id = ?
        ");
        $stmt->bindValue(1, $title, SQLITE3_TEXT);
        $stmt->bindValue(2, $description, SQLITE3_TEXT);
        $stmt->bindValue(3, $category_id, SQLITE3_INTEGER); // Fixed type to INTEGER
        $stmt->bindValue(4, $item_status, SQLITE3_TEXT);
        $stmt->bindValue(5, $location_lost, SQLITE3_TEXT);
        $stmt->bindValue(6, $location_found, SQLITE3_TEXT);
        $stmt->bindValue(7, $date_lost_or_found, SQLITE3_TEXT);
        $stmt->bindValue(8, $current_location, SQLITE3_TEXT);
        $stmt->bindValue(9, $id, SQLITE3_INTEGER);

        $stmt->execute();

        header("Location: dashboard_admin.php?msg=Item+Updated");
        exit;
    } else {
        $error = "Title and Type are required.";
        $action = "edit";
        $_GET['id'] = (string)$id;
    }
}

// ==========================================
// LOGIC: DELETE ITEM HERE
// ==========================================
if ($action === "delete") {
    $id = (int)($_GET["id"] ?? 0);
    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();

        header("Location: dashboard_admin.php?msg=Item+Deleted");
        exit;
    }
}

// ==========================================
// LOGIC: CLAIM ITEMMMM
// ==========================================
if ($action === "claim") {
    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {

        // Update item status to claimed
        $stmt = $db->prepare("UPDATE items SET item_status = 'claimed' WHERE id = ?");
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();

        // Delete related notifications
        $deleteNotif = $db->prepare("UPDATE notifications SET status = 'resolved' WHERE item_id = ?");
        $deleteNotif->bindValue(1, $id, SQLITE3_INTEGER);
        $deleteNotif->execute();

        header("Location: dashboard_admin.php?msg=Item+Claimed.");
        exit;
    } else {
        $error = "Invalid item ID for claiming.";
    }
}


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




// ==========================================
// LOGIC: FETCH ITEMS (SEARCH + FILTER NA)
// ==========================================
$where = [];

// Logged-in user filter
$currentUserId = $_SESSION["user"]["id"];
$where[] = "items.user_id = $currentUserId";

if (!empty($_GET['search'])) {
    $search = $db->escapeString($_GET['search']);
    $where[] = "(items.title LIKE '%$search%' 
                 OR items.description LIKE '%$search%'
                 OR items.location_lost LIKE '%$search%'
                 OR items.location_found LIKE '%$search%')";
}

if (!empty($_GET['category_id'])) {
    $cat = intval($_GET['category_id']);
    $where[] = "items.category_id = $cat";
}

if (!empty($_GET['item_status'])) {
    $status = $db->escapeString($_GET['item_status']);
    $where[] = "items.item_status = '$status'";
}

$sql = "SELECT items.*, categories.name AS category_name, users.username AS posted_by, items.created_at AS date_created
        FROM items
        LEFT JOIN categories ON items.category_id = categories.id
        LEFT JOIN users ON users.id = items.user_id";

$sql .= " WHERE " . implode(" AND ", $where);

$sql .= " ORDER BY items.id DESC";


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Recent Posts</title>
    <link rel="icon" type="image/x-icon" href="../../assets/search.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../reusable/header.css">
    <link rel="stylesheet" href="../../reusable/cards.css">
    <link rel="stylesheet" href="../../reusable/form.css">
    <link rel="stylesheet" href="../../reusable/hero.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="m-0 border-0 bd-example m-0 border-0">
    <nav class="navbar p-3 sticky-top">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions"
                aria-expanded="false" aria-label="Toggle navigation">
            <img src="/assets/hamburger.png" alt="hamburger icon" width="20px" height="20px">
        </button>
        <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1"
            id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
            <div class="offcanvas-body">
                <a href="dashboard_admin.php">Dashboard</a>
                <a href="myposts_admin.php"  style="color: #2289e6; font-weight: 700;">My Wall</a>
                <!-- FIXED LOGOUT PATH :D -->
                <a class="logout" href="../../logout.php" onclick = "return confirm('Are you sure you want to LOG OUT?');">Log out</a>
            </div>
        </div>
        <strong><a class="navbar-brand me-auto d-none d-md-inline" href="#">Campus<span class = "find">Find</a></strong>

        <?php 
        $adminId = $_SESSION['user']['id']; 
        $notifCount = $db->querySingle("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE status = 'unread' AND notify_to = $adminId AND type = 'to_admin'
        ");
        ?>
        <div class = "ms-auto">
            <a href="myposts_admin.php" class="notif mx-4">
                🔔<?= $notifCount ?>
            </a>
            <a class="navbar-brand text-white" href="#">Hello, <?php echo htmlspecialchars($admin["username"]); ?></a>
        </div>
    </nav>
<div class="container my-3">
    <!-- alert messages -->
    <?php if ($msg): ?>
        <div class="alert alert-success alrtsuccess"><?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alrtnotsuccess"><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- CREATE FORM -->
    <!-- ========================================== -->
    <?php if ($action === "create"): ?>
        <h3 class="mb-4" style="font-weight: 700; color: #334155;">Post New Item</h3>
        
        <form method="post" action="?action=store" enctype="multipart/form-data">
            <div class="form-grid-layout">
                
                <!-- LEFT COLUMN -->
                <div class="form-left">
                    <!-- Title -->
                    <div class="input-group-modern">
                        <label>Item Name</label>
                        <input type="text" name="title" placeholder="e.g. Blue Jansport Backpack" required>
                    </div>

                    <!-- Row: Status & Category -->
                    <div class="form-row">
                        <div class="input-group-modern">
                            <label>Status</label>
                            <select name="item_status" id="status" required>
                                <option value="">Select Status</option>
                                <option value="lost">Lost</option>
                                <option value="found">Found</option>
                            </select>
                        </div>
                        <div class="input-group-modern">
                            <label>Category</label>
                            <select name="category_id" id="category_id" required>
                                <option value="">Select Category</option>
                                <?php
                                $catQ = $db->query("SELECT * FROM categories ORDER BY name");
                                while($c = $catQ->fetchArray(SQLITE3_ASSOC)):
                                ?>
                                    <option value="<?= $c['id']; ?>">
                                        <?= ucfirst(str_replace('_', ' ', $c['name'])); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="input-group-modern">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Describe the item (color, distinctive marks, brand)..."></textarea>
                    </div>

                    <!-- Locations (nadi-disable ang isang field kapag di sya applicable) -->
                    <div class="form-row">
                        <div class="input-group-modern">
                            <label>Lost Location</label>
                            <input type="text" name="location_lost" id="location_lost" placeholder="Where was it lost?">
                        </div>
                        <div class="input-group-modern">
                            <label>Found Location</label>
                            <input type="text" name="location_found" id="location_found" placeholder="Where was it found?" disabled>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="form-row">
                        <div class="input-group-modern">
                            <label id="dateLabel">Date Event</label>
                            <div class="date-card">
                                <input type="date" name="date_lost_or_found" id="date_lost_or_found" style="border:none; background:transparent; padding:0;">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: IMAGE UPLOAD -->
                <div class="form-right">
                    <div class="input-group-modern" style="height: 100%;">
                        <label>Item Image</label>
                        
                        <div class="image-upload-wrapper">
                            <input type="file" name="item_image" id="file-input-real" accept="image/*" onchange="previewImage(event)">
                            
                            <!-- The Preview Image -->
                            <img id="image-preview" src="#" alt="Image Preview">

                            <!-- The Placeholder UI -->
                            <div class="upload-placeholder" id="upload-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                                <p><strong>Click to Upload</strong><br>or drag and drop here</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- buttonsss -->
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-primary w-50" type="submit">Publish Post</button>
                        <a class="btn btn-secondary w-50" href="dashboard_admin.php" style="background-color: #cbd5e1; color: #334155; border:none;">Cancel</a>
                    </div>

            </div>
        </form>

        <!-- Inline JS for Image Preview -->
        <script>
            function previewImage(event) {
                const input = event.target;
                const preview = document.getElementById('image-preview');
                const placeholder = document.getElementById('upload-placeholder');
                
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

        </script>

<!-- ========================================== -->
    <!-- EDIT FORM -->
    <!-- ========================================== -->
    <?php elseif ($action === "edit"): 
    $id = (int)($_GET["id"] ?? 0);
    $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $item = $res->fetchArray(SQLITE3_ASSOC);

    if ($item): ?>
    
        <h3 class="mb-4">Edit Item</h3>
        <form method="post" action="?action=edit" enctype="multipart/form-data">
            <div class="form-grid-layout">
                <div class="form-left">
                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">

                <!-- Title -->
                <div class="input-group-modern">
                    <label>Item Name</label>
                    <input type="text" name="title" placeholder="e.g. Blue Jansport Backpack" 
                        value="<?php echo htmlspecialchars($item['title']); ?>" required>
                </div>

                <!-- Status & Category Row -->
                <div class="form-row">
                    <div class="input-group-modern">
                        <label>Status</label>
                        <select name="item_status" id="status" required>
                            <option value="">Select Status</option>
                            <option value="lost"  <?php if($item['item_status']=='lost')  echo 'selected'; ?>>Lost</option>
                            <option value="found" <?php if($item['item_status']=='found') echo 'selected'; ?>>Found</option>
                        </select>
                    </div>
                    <div class="input-group-modern">
                        <label>Category</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">Select Category</option>
                            <?php
                            $catQ = $db->query("SELECT * FROM categories ORDER BY name");
                            while($c = $catQ->fetchArray(SQLITE3_ASSOC)):
                            ?>
                                <option value="<?= $c['id']; ?>" 
                                    <?= ($item['category_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $c['name'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            
                
                <!-- Description -->
                <div class="input-group-modern">
                    <label>Description</label>
                    <textarea name="description" rows="3" 
                            placeholder="Describe the item (color, distinctive marks, brand)..."><?php 
                        echo htmlspecialchars($item['description']); ?></textarea>
                </div>

                <!-- Locations Row -->
                <div class="form-row">
                    <div class="input-group-modern">
                        <label>Lost Location</label>
                        <input type="text" name="location_lost" id="location_lost" 
                            placeholder="Where was it lost?"
                            value="<?php echo htmlspecialchars($item['location_lost']); ?>">
                    </div>
                    <div class="input-group-modern">
                        <label>Found Location</label>
                        <input type="text" name="location_found" id="location_found" 
                            placeholder="Where was it found?"
                            value="<?php echo htmlspecialchars($item['location_found']); ?>" disabled>
                    </div>
                </div>

                <!-- Date -->
                <div class="form-row">
                    <div class="input-group-modern">
                        <label id="dateLabel">Date Event</label>
                        <div class="date-card">
                            <input type="date" name="date_lost_or_found" id="date_lost_or_found"
                                value="<?php echo htmlspecialchars($item['date_lost_or_found']); ?>"
                                style="border:none; background:transparent; padding:0;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-right">
                <div class="input-group-modern" style="height: 100%;">
                    <label>Item Image</label>

                    <div class="image-upload-wrapper">

                        <input type="file" name="item_image" id="file-input-real" accept="image/*" onchange="previewImage(event)">

                        <?php if (!empty($item['image_path'])): ?>
                            <img id="image-preview" 
                                src="../../<?= htmlspecialchars($item['image_path']); ?>" 
                                style="display:block; max-width:100%; border-radius:10px;">

                        <?php else: ?>
                            <img id="image-preview" src="#" style="display:none; max-width:100%; border-radius:10px;">
                        <?php endif; ?>

                        <div class="upload-placeholder" id="upload-placeholder"
                            style="<?php if (!empty($item['image_path'])) echo 'display:none;'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                            </svg>
                            <p><strong>Click to Upload</strong><br>or drag and drop here</p>
                        </div>

                    </div>
                </div>
            </div>
            
            <!-- Buttons -->
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-primary w-100" type="submit">Update Item</button>
                    <a class="btn btn-secondary w-100" href="dashboard_admin.php">Cancel</a>
                </div>

            
            
        </form>

    <?php else: ?>
        <p>Item not found.</p>
        <a class="btn btn-secondary" href="dashboard_admin.php">Back</a>
    <?php endif; ?>

    <?php else: ?>
    <!-- ========================================== -->
    <!-- DASHBOARD LIST VIEW -->
    <!-- ========================================== -->
        
        <!-- hero section -->
        <section class = "heroSection pt-3">
            <div>
                <h1><strong>My Wall</strong></h1>
                <p class = "subtext">Review your posts and check your notification</p>
            </div>
            <a id = "postItems" type="button" class="btn btn-primary ms-auto" href="?action=create">
                Post Item +
            </a>
        </section>

        <!-- search + filter section -->
        <div class="search-filter-container my-2">
    
            <form class="d-flex py-2 w-100" method="GET">
                <input 
                    class="form-control me-2" 
                    type="search" 
                    name="search"
                    placeholder="Search (input any keyword e.g. color, item)" 
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                />
                <button class="btn searchBtn" type="submit">Search</button>
            </form>

            <form method="GET" class="d-flex">
                <select name="item_status" class="form-select m-2 me-auto">
                    <option value="">All Items</option>
                    <option value="lost" <?php if(isset($_GET['item_status']) && $_GET['item_status']=="lost") echo "selected"; ?>>Lost</option>
                    <option value="found" <?php if(isset($_GET['item_status']) && $_GET['item_status']=="found") echo "selected"; ?>>Found</option>
                    <option value="claimed" <?php if(isset($_GET['item_status']) && $_GET['item_status']=="claimed") echo "selected"; ?>>Claimed</option>
                </select>
                <button type="submit" class="btn btn-primary m-2">Filter</button>
            </form>

            <form method="GET" class="d-flex">
                <select id="filterCategory" name="category_id" class="form-select m-2 me-auto">
                    <option value="">All Categories</option>
                    <?php 
                    $catQuery = $db->query("SELECT * FROM categories ORDER BY name");
                    while($row = $catQuery->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?= $row['id']; ?>">
                            <?= ucfirst(str_replace('_', ' ', $row['name'])); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary m-2">Filter</button>
            </form>
            
        </div>
        <!-- ================= -->   
        <!-- NOTIFICATIONS HEREEE -->
         <!-- ================= -->   
        <!-- card -->
        <div class="notifications row mt-5">
             <h3><strong>
                <img src="/assets/bell.png" alt="bell" style="width:25px">
                My Notifications
            </strong></h3>
            <?php if (empty($adminNotifications)): ?>
                <p class="text-muted">No notifications yet.</p>
            <?php else: ?>
               
                <?php foreach ($adminNotifications as $note): ?>
                    <div class="col-md-6">
                        <a href="../admin/dashboard_admin.php#item-<?= $note['item_id']; ?>"
                        class="text-decoration-none text-dark">

                            <div class="notificationCard p-3 my-2 w-100">

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



        <!-- my posts cards dito -->
        <?php
        $result = $db->query($sql);
        $items = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $items[] = $row;
        } ?>

        <div class="row mt-5">
            <h3><strong>My Posts</strong></h3>
            <?php if (empty($items)): ?>
                <p class = "noItemFound">
                    <img src="/assets/empty.png" alt="Empty box" style="width: 300px;">
                    No items found.
                </p>
            <?php else: ?>
                <?php $itemCount = count($items);?>
                <p>Showing <strong><?php echo $itemCount; ?></strong>  items.</p>
                <?php foreach ($items as $it): ?>
                    <div class="col-md-4">
                        <!-- cardddd -->
                        <div class="card my-2">
                            <!-- IMAGE DISPLAY LOGIC -->
                            <?php if(!empty($it['image_path'])): ?>
                                <img src="../../<?php echo htmlspecialchars($it['image_path']); ?>" class="card-img-top" alt="Item image">
                            <?php else: ?>
                                <img src="/assets/image.png" class="card-img-top" alt="Default image">
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title"><strong><?php echo htmlspecialchars($it["title"]); ?></strong></h5>
                                <p class="posted-by">
                                    Posted by: <strong><?= htmlspecialchars($it["posted_by"]); ?></strong>
                                </p>


                                <!-- badgess -->
                                <div class="d-flex gap-2 mb-2">
                                    <span class="badge rounded-pill bg-<?php echo $it['item_status']; ?>">
                                        <?php echo ucfirst($it["item_status"]); ?>
                                    </span>
                                    <?php if ($it["category_name"]): ?>
                                    <span class="category badge rounded-pill">
                                        <?php echo ucfirst(str_replace('_', ' ', $it["category_name"])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <p class="card-text"><?php echo htmlspecialchars($it["description"]); ?></p>
                                <!-- Button para sa modal -->
                                <button id = "seeDetails" type="button" class="btn btn-primary rounded-3 w-100" data-bs-toggle="modal" data-bs-target="#modal-<?php echo $it['id']; ?>">
                                    <img class = "view" src="/assets/eye.png" alt="view" > See Details
                                </button>

                                <!-- Modal -->
                                <div class="modal fade" id="modal-<?php echo $it['id']; ?>">
                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content mx-3">
                                        <div class="modal-header">
                                            <h5 class="modal-title fs-5" id="staticBackdropLabel">
                                                <strong><?php echo htmlspecialchars($it["title"]); ?></strong>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if(!empty($it['image_path'])): ?>
                                                <img src="../../<?php echo htmlspecialchars($it['image_path']); ?>" class="card-img-top" alt="Item image">
                                            <?php else: ?>
                                                <img src="/assets/image.png" class="card-img-top" alt="Default image">
                                            <?php endif; ?>
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="badge rounded-pill bg-<?php echo $it['item_status']; ?>">
                                                    <?php echo ucfirst($it["item_status"]); ?>
                                                </span>
                                                <?php if ($it["category_name"]): ?>
                                                <span class="category badge rounded-pill">
                                                    <?php echo ucfirst(str_replace('_', ' ', $it["category_name"])); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>

                                            <p class="card-text">
                                                <strong>Description</strong><br>
                                                <?php echo htmlspecialchars($it["description"]); ?><br>
                                                <div class = "details">
                                                    <div class="field">
                                                        <?php if ($it["item_status"] == "lost"): ?>
                                                        <strong>Location lost <br></strong>
                                                        <?php echo htmlspecialchars($it["location_lost"]); ?>
                                                        <?php elseif ($it["item_status"] == "found"): ?>
                                                            <strong>Location found <br></strong>
                                                            <?php echo htmlspecialchars($it["location_found"]); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="field">
                                                        <?php if ($it["item_status"] == "lost"): ?>
                                                        <strong>Date lost <br></strong>
                                                        <?php elseif ($it["item_status"] == "found"): ?>
                                                            <strong>Date found <br></strong>
                                                        <?php endif; ?>  
                                                        <?php echo htmlspecialchars($it["date_lost_or_found"]); ?> <br> 
                                                    </div>
                                                        <div>
                                                            <strong>Posted at<br></strong>
                                                            <?php echo htmlspecialchars($it["date_created"]);?>
                                                        </div>
                                                </div>
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <?php if ($it['item_status'] !== 'claimed'): ?>
                                                <a href="?action=claim&id=<?php echo (int)$it["id"]; ?>" onclick="return confirm('Are you sure you want to mark this item CLAIMED?');" class="btn btn-success">Claim</a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>Claimed</button>
                                            <?php endif; ?>

                                            
                                            <a href="?action=edit&id=<?php echo (int)$it["id"]; ?>" class="btn btn-warning">Edit</a>
                                            <a href="?action=delete&id=<?php echo (int)$it["id"]; ?>" class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>   
  </body>
<script src="../../javascripts/form.js"></script>
</html>