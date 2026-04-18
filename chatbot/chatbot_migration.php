<?php
/**
 * SABP Chatbot — Database Migration
 * Run this file once to create the chatbot tables.
 */
require __DIR__ . '/../config.php';

try {
    // 1. Chat history table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chatbot_history (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     VARCHAR(50)  NOT NULL,
            role        VARCHAR(20)  NOT NULL,
            sender      ENUM('user','bot') NOT NULL,
            message     TEXT         NOT NULL,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user    (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅  Table 'chatbot_history' created successfully.<br>";

    // 2. FAQ knowledge-base table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chatbot_faqs (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            role        VARCHAR(20)  NOT NULL DEFAULT 'all',
            keywords    VARCHAR(500) NOT NULL,
            question    VARCHAR(500) NOT NULL,
            answer      TEXT         NOT NULL,
            priority    INT          DEFAULT 0,
            is_active   TINYINT(1)   DEFAULT 1,
            INDEX idx_role   (role),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅  Table 'chatbot_faqs' created successfully.<br>";

    // 3. Feedback / rating table (for Bot Feedback Rating feature)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chatbot_feedback (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            message_id  INT          NOT NULL,
            user_id     VARCHAR(50)  NOT NULL,
            rating      ENUM('up','down') NOT NULL,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_msg_user (message_id, user_id),
            INDEX idx_msg  (message_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅  Table 'chatbot_feedback' created successfully.<br>";

    echo "<br><strong>Migration complete!</strong> You can now run <a href='chatbot_knowledge.php'>chatbot_knowledge.php</a> to seed FAQ data.";

} catch (PDOException $e) {
    echo "❌  Migration failed: " . htmlspecialchars($e->getMessage());
}
