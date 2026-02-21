<?php
// config.php

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'story_db');
define('DB_USER', 'root');
define('DB_PASS', 'admin');

// Qwen/DashScope API Key (Get from: https://dashscope.console.aliyun.com/)
define('DASHSCOPE_API_KEY', 'sk-433444444444444443333333333333333');

// Qwen Model Selection
define('QWEN_MODEL', 'qwen-turbo'); // Options: qwen-turbo, qwen-plus, qwen-max

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>