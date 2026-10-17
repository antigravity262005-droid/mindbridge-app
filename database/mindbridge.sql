-- MindBridge: A Student Mental Wellness, Awareness, and Support Referral System
-- Database Schema: mindbridge_db
-- Target: MySQL / MariaDB (XAMPP Environment)

DROP DATABASE IF EXISTS `mindbridge_db`;
CREATE DATABASE `mindbridge_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `mindbridge_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables in reverse dependency order for clean reinstalls
DROP TABLE IF EXISTS `campaign_participants`;
DROP TABLE IF EXISTS `awareness_campaigns`;
DROP TABLE IF EXISTS `wellness_checkins`;
DROP TABLE IF EXISTS `support_request_updates`;
DROP TABLE IF EXISTS `support_requests`;
DROP TABLE IF EXISTS `support_services`;
DROP TABLE IF EXISTS `mental_health_resources`;
DROP TABLE IF EXISTS `resource_categories`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE `users` (
  `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'administrator') NOT NULL DEFAULT 'user',
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB;

-- 2. Resource Categories Table
CREATE TABLE `resource_categories` (
  `category_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL
) ENGINE=InnoDB;

-- 3. Mental Health Resources Table
CREATE TABLE `mental_health_resources` (
  `resource_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `content` MEDIUMTEXT NOT NULL,
  `source_url` VARCHAR(500) NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `published_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_res_cat` FOREIGN KEY (`category_id`) REFERENCES `resource_categories`(`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_resources_category` (`category_id`),
  INDEX `idx_resources_published` (`published_at`)
) ENGINE=InnoDB;

-- 4. Support Services Table
CREATE TABLE `support_services` (
  `service_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_name` VARCHAR(180) NOT NULL,
  `service_type` VARCHAR(80) NOT NULL,
  `description` TEXT NOT NULL,
  `contact_info` VARCHAR(500) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_srv_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_services_active` (`is_active`, `service_type`)
) ENGINE=InnoDB;

-- 5. Support Requests Table
CREATE TABLE `support_requests` (
  `request_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NULL,
  `request_type` ENUM('appointment', 'referral') NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NULL,
  `status` ENUM('Submitted', 'In Review', 'Scheduled', 'Completed', 'Closed') NOT NULL DEFAULT 'Submitted',
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_req_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_service` FOREIGN KEY (`service_id`) REFERENCES `support_services`(`service_id`) ON DELETE SET NULL,
  INDEX `idx_requests_user` (`user_id`),
  INDEX `idx_requests_status` (`status`),
  INDEX `idx_requests_submitted` (`submitted_at`)
) ENGINE=InnoDB;

-- 6. Support Request Updates Table
CREATE TABLE `support_request_updates` (
  `update_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT UNSIGNED NOT NULL,
  `updated_by` INT UNSIGNED NOT NULL,
  `status` ENUM('Submitted', 'In Review', 'Scheduled', 'Completed', 'Closed') NOT NULL,
  `update_note` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_upd_req` FOREIGN KEY (`request_id`) REFERENCES `support_requests`(`request_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_upd_admin` FOREIGN KEY (`updated_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_updates_request` (`request_id`),
  INDEX `idx_updates_created` (`created_at`)
) ENGINE=InnoDB;

-- 7. Wellness Checkins Table (Private to User)
CREATE TABLE `wellness_checkins` (
  `checkin_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `mood_rating` TINYINT NOT NULL,
  `reflection` TEXT NULL,
  `checkin_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_chk_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_mood_range` CHECK (`mood_rating` >= 1 AND `mood_rating` <= 5),
  INDEX `idx_checkins_user` (`user_id`),
  INDEX `idx_checkins_date` (`checkin_date`)
) ENGINE=InnoDB;

-- 8. Awareness Campaigns Table
CREATE TABLE `awareness_campaigns` (
  `campaign_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cmp_admin` FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_campaign_dates` CHECK (`end_date` >= `start_date`),
  INDEX `idx_campaigns_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB;

-- 9. Campaign Participants Table (Many-to-Many Resolver)
CREATE TABLE `campaign_participants` (
  `campaign_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`campaign_id`, `user_id`),
  CONSTRAINT `fk_part_cmp` FOREIGN KEY (`campaign_id`) REFERENCES `awareness_campaigns`(`campaign_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_part_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_part_user` (`user_id`)
) ENGINE=InnoDB;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Seed 1 Administrator: admin@mindbridge.edu / Admin@123 / must_change_password=1
-- Seed 1 Student User: student@mindbridge.edu / Student@123 / must_change_password=0
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `must_change_password`, `created_at`)
VALUES
  (1, 'MindBridge Administrator', 'admin@mindbridge.edu', '$2y$10$3EzldJekCfMDoRRrhmiWlOY0uYLvNs0r7KlYxd1nwnHl1a8wULLeG', 'administrator', 1, '2026-10-01 08:00:00'),
  (2, 'Alex Rivera', 'student@mindbridge.edu', '$2y$10$rCvh7PZ7sH6kE0eFmgN8h.7uT6i6L/C3bJ3nN9l0xG4qT.p0K2e.q', 'user', 0, '2026-10-02 09:30:00');

-- 3 Resource Categories
INSERT INTO `resource_categories` (`category_id`, `category_name`, `description`)
VALUES
  (1, 'Mental Wellness', 'Guidance, self-care routines, and strategies for emotional well-being, mindfulness, and navigating school pressure.'),
  (2, 'Finding Support', 'Empathetic advice on how to reach out, what to expect during counseling sessions, and overcoming hesitation.'),
  (3, 'Suicide-Prevention Awareness', 'Educational resources on recognizing distress signals, holding supportive non-judgmental conversations, and finding verified help.');

-- At least 8 Reviewed Educational Resources
INSERT INTO `mental_health_resources` (`resource_id`, `category_id`, `title`, `content`, `source_url`, `created_by`, `published_at`)
VALUES
  (1, 1, 'Navigating Academic Stress & Preventing Burnout',
   'College life brings tight deadlines, examinations, and major life transitions that can easily lead to cognitive exhaustion. Burnout does not happen overnight; it manifests through subtle fatigue, loss of motivation, and irritability. Setting realistic boundaries, breaking study blocks into 25-minute Pomodoro sprints, and designating deliberate downtime without digital notifications are proven steps to protect emotional resilience.',
   'https://www.apa.org/topics/stress', 1, '2026-10-02 10:00:00'),

  (2, 1, 'Practical Grounding Techniques for Everyday Anxiety',
   'When anxiety causes racing thoughts or physical tension, sensory grounding brings your nervous system back to equilibrium. Try the 5-4-3-2-1 technique: acknowledge 5 things you can see, 4 things you can physically touch, 3 sounds you can hear, 2 scents you can smell, and 1 positive affirmation you can tell yourself. Paired with slow diaphragmatic breathing (4 counts in, 6 counts out), grounding helps reduce acute stress.',
   'https://www.healthline.com/health/grounding-techniques', 1, '2026-10-03 11:15:00'),

  (3, 1, 'Sleep Architecture and Emotional Equilibrium',
   'Sleep and mental stability share a bidirectional relationship. Chronic sleep deprivation compromises the brain prefrontal cortex, which governs emotional regulation and rational problem-solving. Cultivate a consistent sleep window, avoid blue-light screens 45 minutes before rest, and reserve your bed strictly for resting rather than studying.',
   'https://www.sleepfoundation.org/mental-health', 1, '2026-10-04 14:00:00'),

  (4, 2, 'How to Have a Supportive Conversation with a Friend',
   'Noticing a peer withdrawing or struggling can feel daunting, but you do not need to be a certified therapist to offer meaningful help. Start with neutral, caring observations such as: "I noticed you seemed quieter lately, and I just wanted to check in." Listen attentively without immediately offering fixes. Refrain from minimizing statements like "You will get over it," and gently offer to walk with them to school support services.',
   'https://www.jedfoundation.org/how-to-help-a-friend', 1, '2026-10-05 09:45:00'),

  (5, 2, 'What Really Happens in a College Counseling Session?',
   'Many students postpone seeking help because they imagine clinical interrogations or fear being judged. In reality, a school counseling intake is simply a supportive, collaborative conversation. The counselor listens to your current circumstances, helps you identify specific stressors, and collaborates on coping tools that fit your personal lifestyle and academic goals.',
   'https://www.activeminds.org/about-mental-health/first-counseling-appointment/', 1, '2026-10-06 13:20:00'),

  (6, 3, 'Recognizing Subtle Distress Indicators in Peers',
   'Suicide prevention starts with recognizing when someone is overwhelmed and struggling in silence. Warning signs include uncharacteristic social withdrawal, dramatic mood fluctuations, expressing deep feelings of being a burden, or giving away prized possessions. Taking these indicators seriously and connecting the individual with qualified guidance staff saves lives.',
   'https://www.who.int/news-room/fact-sheets/detail/suicide', 1, '2026-10-07 10:30:00'),

  (7, 3, 'De-escalation and Crisis Navigation: Safe Protocols',
   'If someone reveals intense feelings of hopelessness, remain calm, stay present, and prioritize immediate safety. Never promise absolute secrecy when personal safety is at risk. Encourage them to contact dedicated crisis professionals or help them notify a campus guidance counselor or medical authority immediately.',
   'https://afsp.org/talk-saves-lives', 1, '2026-10-08 15:10:00'),

  (8, 2, 'Building Meaningful Connection in Digital Environments',
   'While online communication keeps us connected, digital interactions often lack emotional depth, sometimes heightening feelings of isolation. Intentionally engaging in face-to-face peer study circles, campus interest clubs, or community volunteer groups provides visceral social validation that protects against loneliness.',
   'https://www.mhanational.org/connect-with-others', 1, '2026-10-09 11:00:00');

-- 4 Support Services (Campus + Philippines verified sample)
INSERT INTO `support_services` (`service_id`, `service_name`, `service_type`, `description`, `contact_info`, `is_active`, `created_by`, `created_at`)
VALUES
  (1, 'University Guidance and Counseling Center', 'Campus Counseling',
   'Confidential student psychological consultations, emotional wellness guidance, stress management coaching, and structured referral navigation.',
   'Student Center Building, 3rd Floor, Room 304 | guidance@mindbridge.edu | Mon-Fri 8:00 AM - 5:00 PM', 1, 1, '2026-10-01 08:30:00'),

  (2, 'Peer Wellness Advocates & Mentorship Desk', 'Community Support',
   'Student-led listening circles, peer mentorship, study-stress de-escalation sessions, and safe spaces for compassionate conversation.',
   'Student Activity Hub, Kiosk B | peerwellness@mindbridge.edu | Mon-Thu 10:00 AM - 4:00 PM', 1, 1, '2026-10-01 08:45:00'),

  (3, 'Philippine National Center for Mental Health (NCMH) Crisis Hotline', 'Crisis Hotline (Directory Entry)',
   'Official nationwide Philippine crisis contact. Note: MindBridge does not monitor this line. Admin and users must verify current availability via ncmh.gov.ph. For urgent crisis intervention, contact directly.',
   'Toll-Free Hotline: 1553 | Mobile: 0917-899-8727 / 0966-351-4518 / 0908-639-2672 | 24/7 National Hotline', 1, 1, '2026-10-01 09:00:00'),

  (4, 'Campus Health and Wellness Medical Clinic', 'Medical / Clinical Care',
   'Initial medical evaluation, consultation on physical symptoms connected to chronic anxiety or insomnia, and immediate referrals.',
   'Health Services Pavilion, Ground Floor | clinic@mindbridge.edu | Phone: (02) 8920-1122 | Mon-Sat 7:30 AM - 6:00 PM', 1, 1, '2026-10-01 09:15:00');

-- 2 Awareness Campaigns
INSERT INTO `awareness_campaigns` (`campaign_id`, `title`, `description`, `start_date`, `end_date`, `created_by`, `created_at`)
VALUES
  (1, 'Mind Matters: Campus Wellness & Resilience Week 2026',
   'A week-long series of interactive workshops, guided breathing sessions, expressive art booths, and student wellness discussions designed to normalize seeking help and destigmatize mental health discussions.',
   '2026-10-20', '2026-10-27', 1, '2026-10-02 08:00:00'),

  (2, 'Break the Silence: Peer Listening & Hope Circle',
   'A collaborative dialogue initiative where students share experiences in a supportive, moderated circle. Discover helpful strategies for managing academic expectations and cultivating everyday optimism.',
   '2026-11-05', '2026-11-12', 1, '2026-10-05 08:00:00');

-- Demo Campaign Participants
INSERT INTO `campaign_participants` (`campaign_id`, `user_id`, `joined_at`)
VALUES
  (1, 2, '2026-10-03 14:10:00');

-- Demo Support Requests
INSERT INTO `support_requests` (`request_id`, `user_id`, `service_id`, `request_type`, `subject`, `message`, `status`, `submitted_at`)
VALUES
  (1, 2, 1, 'appointment', 'Requesting guidance consultation for midterms burnout',
   'I have been experiencing difficulty focusing and persistent fatigue over the past few weeks due to consecutive exam deadlines. Would appreciate setting an exploratory appointment with a counselor.',
   'In Review', '2026-10-03 15:30:00'),

  (2, 2, 2, 'referral', 'Inquiry regarding peer study and listening circles',
   'Seeking connection to a peer mentorship circle for managing presentation anxiety and daily academic stress.',
   'Submitted', '2026-10-04 11:20:00');

-- Demo Support Request Updates
INSERT INTO `support_request_updates` (`update_id`, `request_id`, `updated_by`, `status`, `update_note`, `created_at`)
VALUES
  (1, 1, 1, 'In Review',
   'Your appointment request has been received by the Guidance Office. Counselor Santos is reviewing the schedule and will confirm available time slots.',
   '2026-10-03 16:45:00');

-- Demo Wellness Check-ins (Private to User 2 Alex Rivera)
INSERT INTO `wellness_checkins` (`checkin_id`, `user_id`, `mood_rating`, `reflection`, `checkin_date`)
VALUES
  (1, 2, 3, 'Feeling moderately drained after two back-to-back presentations, but looking forward to resting this evening.', '2026-10-03 19:00:00'),
  (2, 2, 4, 'Managed a good 8-hour sleep and felt more energized during group study today.', '2026-10-04 20:30:00'),
  (3, 2, 2, 'Felt anxious about upcoming project requirements. Completed a 10-minute grounding exercise which helped calm my nerves.', '2026-10-05 18:15:00'),
  (4, 2, 4, 'Submitted my term draft on time. Grateful for a supportive conversation with my classmate.', '2026-10-06 21:00:00'),
  (5, 2, 5, 'Felt grounded and calm today. Participated in the campus wellness walking break.', '2026-10-07 19:45:00');

SET FOREIGN_KEY_CHECKS = 1;
