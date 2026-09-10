<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['authenticated' => !empty($_SESSION['user_email']), 'name' => (string) ($_SESSION['user_name'] ?? ''), 'email' => (string) ($_SESSION['user_email'] ?? '')]);
