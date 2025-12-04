<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

$page_title = "Financial Freedom Habits – Blog";
$page_description = "Latest articles about money habits, financial freedom, getting out of debt and building a calm money mindset.";

include 'header.php';

// Uzmi poslednje objave
$sql = "SELECT id, slug, title, excerpt, created_at 
        FROM " . $TABLE_POSTS . " 
        WHERE status = 1 
        ORDER BY created_at DESC 
        LIMIT 20";

$result = $conn->query($sql);
?>

<section class="section" id="latest">
  <h2 class="section-title">Latest articles</h2>

  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <article class="post-card">
        <h2 class="post-title">
          <a href="post.php?slug=<?php echo urlencode($row['slug']); ?>">
            <?php echo htmlspecialchars($row['title']); ?>
          </a>
        </h2>
        <div class="post-meta">
          <span><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
        </div>
        <p class="post-excerpt">
          <?php echo nl2br(htmlspecialchars($row['excerpt'])); ?>
        </p>
        <a class="post-read-more" href="post.php?slug=<?php echo urlencode($row['slug']); ?>">
          Read more →
        </a>
      </article>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No posts yet. The first article is coming soon.</p>
  <?php endif; ?>

</section>

<?php
include 'footer.php';
$conn->close();
