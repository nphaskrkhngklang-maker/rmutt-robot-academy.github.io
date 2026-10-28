CREATE TABLE IF NOT EXISTS schools (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 camp_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(200) NOT NULL,
 registration_status ENUM('not_started','in_progress','submitted') NOT NULL DEFAULT 'not_started',
 link_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 registration_version INT UNSIGNED NOT NULL DEFAULT 0,
 submitted_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY school_name_per_camp (camp_id,name),
 UNIQUE KEY school_camp_identity (id,camp_id),
 CONSTRAINT fk_school_camp FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS school_registration_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 token_encrypted TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 revoked_at DATETIME NULL,
 KEY school_token (school_id,revoked_at),
 CONSTRAINT fk_token_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS participants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 camp_id BIGINT UNSIGNED NOT NULL,
 school_id BIGINT UNSIGNED NOT NULL,
 type ENUM('student','teacher') NOT NULL,
 name VARCHAR(200) NOT NULL,
 shirt_size ENUM('XS','S','M','L','XL','2XL','3XL','4XL') NULL,
 sub_team_id BIGINT UNSIGNED NULL DEFAULT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY participant_pool (camp_id,type,school_id),
 CONSTRAINT fk_participant_school_camp FOREIGN KEY (school_id,camp_id) REFERENCES schools(id,camp_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
