<?php
require_once 'config.php';

$ADMIN_SECRET = 'finhabits_admin'; // promeni u nešto jače

if (!isset($_GET['key']) || $_GET['key'] !== $ADMIN_SECRET) {
    http_response_code(403);
    die('Forbidden');
}

/* ------------------ AKCIJE (approve / delete / hide / unhide) ------------------ */
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id     = (int)$_GET['id'];

    if ($id > 0) {

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE comments SET approved = 1 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

        } elseif ($action === 'hide') {
            $stmt = $conn->prepare("UPDATE comments SET hidden = 1 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

        } elseif ($action === 'unhide') {
            $stmt = $conn->prepare("UPDATE comments SET hidden = 0 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // redirect da se ne ponavlja akcija
    header('Location: comments_admin.php?key=' . urlencode($ADMIN_SECRET));
    exit;
}

/* ------------------ UČITAVANJE KOMENTARA ------------------ */
$result = $conn->query("
    SELECT c.id, c.post_id, c.name, c.comment, c.created_at, 
           c.approved, c.hidden,
           p.title, p.slug
    FROM comments c
    LEFT JOIN posts p ON c.post_id = p.id
    ORDER BY c.approved ASC, c.created_at DESC
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Comments admin – FinancialFreedomHabits</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: #f3f4f6;
      padding: 20px;
    }
    h1 {
      margin-bottom: 16px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
    }
    th, td {
      padding: 8px 10px;
      font-size: 13px;
      border-bottom: 1px solid #e5e7eb;
      vertical-align: top;
    }
    th {
      background: #f9fafb;
      text-align: left;
      font-weight: 600;
    }
    tr.pending {
      background: #fffbeb;
    }
    tr.hidden-comment {
      background: #f0f0f0;
      opacity: 0.7;
    }
    .badge {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 999px;
      font-size: 11px;
    }
    .badge-pending {
      background: #f97316;
      color: #fff;
    }
    .badge-approved {
      background: #22c55e;
      color: #fff;
    }
    .badge-hidden {
      background: #6b7280;
      color: #fff;
    }
    a.btn {
      display: inline-block;
      padding: 4px 8px;
      font-size: 11px;
      border-radius: 999px;
      text-decoration: none;
      margin-right: 4px;
      color: white;
    }
    .btn-approve { background: #22c55e; }
    .btn-delete  { background: #ef4444; }
    .btn-hide    { background: #f59e0b; }
    .btn-unhide  { background: #3b82f6; }

    .post-link {
      font-weight: 500;
      color: #111827;
      text-decoration: none;
    }
    .post-link:hover {
      text-decoration: underline;
    }
    .comment-text {
      white-space: pre-wrap;
    }
  </style>
</head>
<body>
  <h1>Comments admin</h1>
  <p>Pending comments are highlighted in yellow. Hidden comments are grayed out.</p>

  <table>
    <tr>
      <th>ID</th>
      <th>Status</th>
      <th>Post</th>
      <th>Name</th>
      <th>Comment</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>

        <tr class="<?php 
            echo !$row['approved'] ? 'pending' : '';
            echo $row['hidden'] ? ' hidden-comment' : ''; 
        ?>">
          <td><?php echo (int)$row['id']; ?></td>

          <td>
            <?php if ($row['hidden']): ?>
              <span class="badge badge-hidden">Hidden</span>
            <?php elseif (!$row['approved']): ?>
              <span class="badge badge-pending">Pending</span>
            <?php else: ?>
              <span class="badge badge-approved">Approved</span>
            <?php endif; ?>
          </td>

          <td>
            <?php if (!empty($row['slug'])): ?>
              <a class="post-link" href="post.php?slug=<?php echo htmlspecialchars($row['slug']); ?>" target="_blank">
                <?php echo htmlspecialchars($row['title']); ?>
              </a>
            <?php else: ?>
              (post #<?php echo (int)$row['post_id']; ?>)
            <?php endif; ?>
          </td>

          <td><?php echo htmlspecialchars($row['name']); ?></td>
          <td class="comment-text"><?php echo nl2br(htmlspecialchars($row['comment'])); ?></td>
          <td><?php echo htmlspecialchars($row['created_at']); ?></td>

          <td>
            <?php if (!$row['approved']): ?>
              <a class="btn btn-approve" 
                 href="comments_admin.php?key=<?php echo urlencode($ADMIN_SECRET); ?>&action=approve&id=<?php echo (int)$row['id']; ?>">
                 Approve
              </a>
            <?php endif; ?>

            <?php if ($row['hidden'] == 0): ?>
              <a class="btn btn-hide"
                 href="comments_admin.php?key=<?php echo urlencode($ADMIN_SECRET); ?>&action=hide&id=<?php echo (int)$row['id']; ?>">
                 Hide
              </a>
            <?php else: ?>
              <a class="btn btn-unhide"
                 href="comments_admin.php?key=<?php echo urlencode($ADMIN_SECRET); ?>&action=unhide&id=<?php echo (int)$row['id']; ?>">
                 Unhide
              </a>
            <?php endif; ?>

            <a class="btn btn-delete" 
               href="comments_admin.php?key=<?php echo urlencode($ADMIN_SECRET); ?>&action=delete&id=<?php echo (int)$row['id']; ?>"
               onclick="return confirm('Delete this comment?');">
               Delete
            </a>
          </td>

        </tr>
      <?php endwhile; ?>

    <?php else: ?>
      <tr><td colspan="7">No comments yet.</td></tr>
    <?php endif; ?>

  </table>

</body>
</html>
<?php
if ($result) $result->free();
$conn->close();
