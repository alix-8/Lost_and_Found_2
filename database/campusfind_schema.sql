-- CampusFind Database Schema/queries  
-- Lost & Found Item Tracker System
-- Created for WAD 1 & RWD Project


-- BY: JAY R SANTOS

-- ==========================================
-- USERS TABLE
-- ==========================================
CREATE TABLE users (
    user_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user', -- 'admin' or 'user'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- ITEMS TABLE
-- ==========================================
CREATE TABLE items (
    item_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title VARCHAR(200) NOT NULL,
    description TEXT,
    location VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Found', -- 'Lost', 'Found', 'Claimed'
    image VARCHAR(255), -- File path or URL
    posted_by UUID NOT NULL,
    claimed_by UUID,
    claimed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (claimed_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ==========================================
-- INDEXES (For Better Query Performance)
-- ==========================================
CREATE INDEX idx_items_posted_by ON items(posted_by);
CREATE INDEX idx_items_claimed_by ON items(claimed_by);
CREATE INDEX idx_items_status ON items(status);
CREATE INDEX idx_items_location ON items(location);
CREATE INDEX idx_users_email ON users(email);

-- ==========================================
-- SAMPLE DATA (Optional - for testing)
-- ==========================================
-- INSERT INTO users (first_name, last_name, address, email, password, role)
-- VALUES ('John', 'Doe', '123 Campus St', 'john@example.com', 'hashed_password_here', 'user');

-- INSERT INTO items (title, description, location, status, posted_by)
-- VALUES ('Blue Backpack', 'Lost near library entrance', 'Campus Library', 'Lost', 'user_id_here');




-- ===============================================================================================
-- BY: JESS CARBONEL

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user', -- 'user', 'staff', 'admin'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1
);

