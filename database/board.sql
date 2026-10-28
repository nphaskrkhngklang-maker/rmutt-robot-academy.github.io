CREATE TABLE IF NOT EXISTS board_posts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 camp_id BIGINT UNSIGNED NOT NULL,
 submission_token CHAR(64) NOT NULL UNIQUE,
 author_key CHAR(64) NOT NULL,
 author_name VARCHAR(80) NOT NULL DEFAULT '',
 title VARCHAR(150) NOT NULL,
 body TEXT NOT NULL,
 tone ENUM('blue','yellow','green','pink') NOT NULL DEFAULT 'blue',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY board_feed(camp_id,id), KEY board_rate(author_key,created_at),
 FOREIGN KEY(camp_id) REFERENCES camps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
