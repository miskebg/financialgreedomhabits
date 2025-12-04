<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$post_id      = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$slug         = isset($_POST['slug']) ? trim($_POST['slug']) : '';
$name         = isset($_POST['name']) ? trim($_POST['name']) : '';
$comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';
$website_hp   = isset($_POST['website']) ? trim($_POST['website']) : '';

// Honeypot – ako je ispunjen, tretiramo kao spam i samo se "zahvalimo"
if ($website_hp !== '') {
    // Možeš da loguješ ako hoćeš, ali ovde samo glumimo success
    header('Location: post.php?slug=' . urlencode($slug) . '#comments');
    exit;
}

// Osnovna validacija
if ($post_id <= 0 || $slug === '' || $name === '' || $comment_text === '') {
    header('Location: post.php?slug=' . urlencode($slug) . '#comments');
    exit;
}

$created_at = date('Y-m-d H:i:s');
$approved   = 0; // SADA ide na moderaciju

$stmt = $conn->prepare("
    INSERT INTO comments (post_id, name, comment, created_at, approved)
    VALUES (?, ?, ?, ?, ?)
");
if (!$stmt) {
    die("DB error: " . $conn->error);
}
$stmt->bind_param("isssi", $post_id, $name, $comment_text, $created_at, $approved);
$stmt->execute();
$stmt->close();
$conn->close();

// Možeš kasnije da dodaš poruku "Your comment is awaiting approval"
header('Location: post.php?slug=' . urlencode($slug) . '#comments');
exit;
