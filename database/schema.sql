-- Smart Event Entry System - Database Schema
-- Folder: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\database
-- File: schema.sql
-- Purpose: Setup database structure, tables, constraints, and relationships.

-- Create database if not exists and switch to it


-- -------------------------------------------------------------
-- TABLE: users
-- Purpose: Holds credentials, profile details, and system roles for both Attendees and Administrators.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_users PRIMARY KEY (`id`),
    CONSTRAINT uq_users_email UNIQUE (`email`)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TABLE: events
-- Purpose: Stores organizer-created workshops, conferences, hackathons, and webinars.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `max_capacity` INT NOT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_events PRIMARY KEY (`id`),
    CONSTRAINT fk_events_creator FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TABLE: registrations
-- Purpose: Maps users to events (Many-to-Many relationship). Represents an event entry request.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_registrations PRIMARY KEY (`id`),
    CONSTRAINT uq_user_event UNIQUE (`user_id`, `event_id`), -- Prevents a user from registering twice for the same event
    CONSTRAINT fk_registrations_user FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_registrations_event FOREIGN KEY (`event_id`) 
        REFERENCES `events` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TABLE: qr_codes
-- Purpose: Stores secure verification tokens and file paths for entry badges.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_codes` (
    `id` INT AUTO_INCREMENT,
    `registration_id` INT NOT NULL,
    `qr_token` VARCHAR(100) NOT NULL,
    `qr_image_path` VARCHAR(255) NOT NULL,
    `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_qr_codes PRIMARY KEY (`id`),
    CONSTRAINT uq_qr_registration UNIQUE (`registration_id`), -- 1-to-1: One registration gets exactly one QR Code
    CONSTRAINT uq_qr_token UNIQUE (`qr_token`), -- Prevents duplicate validation tokens
    CONSTRAINT fk_qr_registration FOREIGN KEY (`registration_id`) 
        REFERENCES `registrations` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TABLE: attendance
-- Purpose: Logs physical check-ins scanned by volunteers or event admins.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT AUTO_INCREMENT,
    `registration_id` INT NOT NULL,
    `marked_by` INT NOT NULL,
    `marked_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_attendance PRIMARY KEY (`id`),
    CONSTRAINT uq_attendance_registration UNIQUE (`registration_id`), -- Prevents duplicate check-ins
    CONSTRAINT fk_attendance_registration FOREIGN KEY (`registration_id`) 
        REFERENCES `registrations` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_attendance_admin FOREIGN KEY (`marked_by`) 
        REFERENCES `users` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- -------------------------------------------------------------
CREATE INDEX idx_users_email ON `users` (`email`);
CREATE INDEX idx_events_date ON `events` (`date`);
CREATE INDEX idx_registrations_lookup ON `registrations` (`user_id`, `event_id`);
CREATE INDEX idx_qr_token ON `qr_codes` (`qr_token`);
