<?php
function get_db(): SQLite3 
{
    static $db = null;
    if ($db !== null) {
        return $db;
    }

    $dbPath = __DIR__ . '/../database/campusFind.db';
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);

    // USERS TABLE
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // CATEGORIES TABLE
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL
    )");

    return $db;
}


function add_admin(): void
{
    $db = get_db();

    // Check kung nag-eexist na yung admin
    $check = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $result = $check->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result) {
        echo "Admin already exists.\n";
        return;
    }

    //credentials ng admin
    $username = "admin";
    $email = "admin@example.com";
    $password_hash = password_hash("helloworld", PASSWORD_DEFAULT);
    $role = "admin";

    $stmt = $db->prepare("
        INSERT INTO users (username, email, password_hash, role)
        VALUES (:username, :email, :password_hash, :role)
    ");

    $stmt->bindValue(":username", $username, SQLITE3_TEXT);
    $stmt->bindValue(":email", $email, SQLITE3_TEXT);
    $stmt->bindValue(":password_hash", $password_hash, SQLITE3_TEXT);
    $stmt->bindValue(":role", $role, SQLITE3_TEXT);

    $stmt->execute();

    echo "Admin account created successfully.\n";
}

function add_categories(): void
{
    $db = get_db();

    $stmt = $db->prepare("
        INSERT INTO categories (name)
        VALUES ('id_cards'),
        ('electronics'),
        ('stationery'),
        ('bags_wallets'),
        ('clothing_accessories'),
        ('jewelry_personal'),
        ('keys'),
        ('sports_equipment'),
        ('others')");

    $stmt->execute();

    echo "Categories inserted\n";
}

function create_notifications_table(): void
{
    $db = get_db();

    $stmt = $db->prepare("
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER,
            user_id INTEGER,      -- sino nag triggered ng notification
            notify_to INTEGER,    -- cno dapat magre receive (id 'to)
            message TEXT,
            type TEXT,            -- 'to_admin' | 'to_user'
            status TEXT DEFAULT 'unread',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $stmt->execute();

    echo "Notifications table created successfully\n";
}

function add_items_table(): void
{
    $db = get_db();

    $stmt = $db->prepare("
    -- Step 2: Create new table with claimed_by TEXT
    CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        item_status TEXT NOT NULL,
        category_id INTEGER,
        location_lost TEXT,
        location_found TEXT,
        date_lost_or_found DATE,
        current_location TEXT,
        image_path TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        claimed_by TEXT,
        claimed_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    );
    ");

    $stmt->execute();

    echo "Table added successfully\n";
}


function delete_table_contents(): void
{
    $db = get_db();

    $stmt = $db->prepare("
    DELETE FROM notifications;
    VACUUM;
    ");

    $stmt->execute();

    echo "Table contents deleted successfully\n";
}

get_db();
// delete_table_contents();
// add_items_table()
// create_notifications_table();
// add_categories();
// add_admin();
?>