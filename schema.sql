-- Gerbang Inbox — MySQL schema
-- Import this in phpMyAdmin (select your database -> Import -> choose this file)
-- or via: mysql -u USERNAME -p DATABASE_NAME < schema.sql

CREATE TABLE IF NOT EXISTS conversations (
  id VARCHAR(191) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  channel VARCHAR(20) NOT NULL,
  handle VARCHAR(255) DEFAULT NULL,
  external_id VARCHAR(255) NOT NULL,
  tag VARCHAR(50) DEFAULT 'Baru',
  unread INT DEFAULT 0,
  online TINYINT(1) DEFAULT 1,
  last_at VARCHAR(20) DEFAULT NULL,
  updated_at BIGINT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id VARCHAR(191) NOT NULL,
  from_who VARCHAR(10) NOT NULL,
  text TEXT,
  time VARCHAR(20) DEFAULT NULL,
  status VARCHAR(20) DEFAULT NULL,
  agent VARCHAR(255) DEFAULT NULL,
  created_at BIGINT DEFAULT NULL,
  CONSTRAINT fk_messages_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id)
    ON DELETE CASCADE,
  INDEX idx_messages_conv (conversation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dashboard agent accounts. Create these with create_user.php (CLI) — there is no
-- public signup form, this is an internal team tool.
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(255) DEFAULT NULL,
  created_at BIGINT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
