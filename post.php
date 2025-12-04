<?php
require_once 'config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($slug === '') {
    header('Location: index.php');
    exit;
}

// Uzimamo ID, title, excerpt, content, created_at
$stmt = $conn->prepare("
    SELECT id, title, excerpt, content, created_at 
    FROM " . $TABLE_POSTS . " 
    WHERE slug=? AND status=1 
    LIMIT 1
");
if (!$stmt) {
    die("DB error: " . $conn->error);
}
$stmt->bind_param("s", $slug);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $page_title = "Article not found – Financial Freedom Habits";
    $page_description = "This article could not be found.";
    include 'header.php';
    echo "<section class='section'><h1>Article not found</h1><p>The article you are looking for doesn’t exist.</p></section>";
    include 'footer.php';
    exit;
}

$stmt->bind_result($post_id, $title, $excerpt, $content, $created_at);
$stmt->fetch();

/* ---------- SEO / OG VREDNOSTI ---------- */

// Ako nema excerpt u bazi, generišemo iz content-a
if (!$excerpt || trim($excerpt) === '') {
    $plain = strip_tags($content);
    if (function_exists('mb_substr')) {
        $excerpt = mb_substr($plain, 0, 160, 'UTF-8') . (mb_strlen($plain, 'UTF-8') > 160 ? '…' : '');
    } else {
        $excerpt = substr($plain, 0, 160) . (strlen($plain) > 160 ? '…' : '');
    }
}

$page_title       = $title . " – Financial Freedom Habits";
$page_description = $excerpt;

// OG vrednosti za header.php
$og_type  = 'article';
$og_image = 'https://financialfreedomhabits.site/assets/default-og.jpg';

// URL posta (za share)
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$post_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/post.php?slug=' . urlencode($slug);

/* ---------- READING TIME ---------- */
$plain_content = strip_tags($content);
$words = preg_split('/\s+/', $plain_content, -1, PREG_SPLIT_NO_EMPTY);
$word_count = is_array($words) ? count($words) : 0;
$reading_minutes = max(1, ceil($word_count / 200));

include 'header.php';

/* ---------- UČITAVANJE KOMENTARA ---------- */
$comments = array();

$cstmt = $conn->prepare("
    SELECT name, comment, created_at
    FROM comments
    WHERE post_id = ? AND approved = 1 AND hidden = 0
    ORDER BY created_at DESC
");
if ($cstmt) {
    $cstmt->bind_param("i", $post_id);
    $cstmt->execute();
    $cstmt->store_result();
    $cstmt->bind_result($c_name, $c_comment, $c_created);
    while ($cstmt->fetch()) {
        $comments[] = array(
            'name'    => $c_name,
            'comment' => $c_comment,
            'created' => $c_created
        );
    }
    $cstmt->close();
}

/* ---------- RELATED POSTS (INTERNI LINKOVI) ---------- */
$related_posts = array();

$rstmt = $conn->prepare("
    SELECT slug, title, created_at, excerpt
    FROM " . $TABLE_POSTS . "
    WHERE status = 1 AND id <> ?
    ORDER BY created_at DESC
    LIMIT 3
");
if ($rstmt) {
    $rstmt->bind_param("i", $post_id);
    $rstmt->execute();
    $rstmt->store_result();
    $rstmt->bind_result($r_slug, $r_title, $r_created, $r_excerpt);
    while ($rstmt->fetch()) {
        // fallback ako nema excerpt
        if (!$r_excerpt || trim($r_excerpt) === '') {
            $plain_r = strip_tags($content);
            if (function_exists('mb_substr')) {
                $r_excerpt = mb_substr($plain_r, 0, 120, 'UTF-8') . (mb_strlen($plain_r, 'UTF-8') > 120 ? '…' : '');
            } else {
                $r_excerpt = substr($plain_r, 0, 120) . (strlen($plain_r) > 120 ? '…' : '');
            }
        }
        $related_posts[] = array(
            'slug'    => $r_slug,
            'title'   => $r_title,
            'created' => $r_created,
            'excerpt' => $r_excerpt
        );
    }
    $rstmt->close();
}
?>

<article class="single-post">
  <h1 class="single-title"><?php echo htmlspecialchars($title); ?></h1>
  <div class="post-meta">
    <span><?php echo date('M d, Y', strtotime($created_at)); ?></span>
    <span class="meta-sep">•</span>
    <span><?php echo $reading_minutes; ?> min read</span>
  </div>

  <!-- SHARE DUGMIĆI -->
  <div class="share-bar">
    <span class="share-label">Share this article</span>
    <div class="share-buttons">
      <a class="share-btn share-fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($post_url); ?>" target="_blank" rel="noopener">
        <span>📘</span><span>Facebook</span>
      </a>
      <a class="share-btn share-x" href="https://twitter.com/intent/tweet?url=<?php echo urlencode($post_url); ?>&text=<?php echo urlencode($title); ?>" target="_blank" rel="noopener">
        <span>✖️</span><span>Post on X</span>
      </a>
      <a class="share-btn share-wa" href="https://api.whatsapp.com/send?text=<?php echo urlencode($title . ' ' . $post_url); ?>" target="_blank" rel="noopener">
        <span>💬</span><span>WhatsApp</span>
      </a>
      <button class="share-btn share-copy" type="button" onclick="copyPostLink('<?php echo htmlspecialchars($post_url, ENT_QUOTES); ?>')">
        <span>🔗</span><span>Copy link</span>
      </button>
    </div>
  </div>

  <!-- AD SLOT: iznad sadržaja -->
  <div class="ad-slot ad-inpost-top">
    <span>Ad space (top of article)</span>
  </div>

  <div class="post-content">
    <?php echo $content; ?>
  </div>

  <!-- AD SLOT: ispod sadržaja -->
  <div class="ad-slot ad-inpost-bottom">
    <span>Ad space (bottom of article)</span>
  </div>

  <?php if (!empty($related_posts)): ?>
  <section class="related-posts">
    <h2 class="related-title">You might also like</h2>
    <div class="related-grid">
      <?php foreach ($related_posts as $rp): ?>
        <article class="related-item">
          <h3>
            <a href="post.php?slug=<?php echo htmlspecialchars($rp['slug']); ?>">
              <?php echo htmlspecialchars($rp['title']); ?>
            </a>
          </h3>
          <p class="related-meta">
            <?php echo date('M d, Y', strtotime($rp['created'])); ?>
          </p>
          <p class="related-excerpt">
            <?php echo htmlspecialchars($rp['excerpt']); ?>
          </p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- KOMENTARI -->
  <section class="comments" id="comments">
    <h2 class="comments-title">Comments</h2>

    <?php if (count($comments) === 0): ?>
      <p class="no-comments">No comments yet. Be the first to share your thoughts.</p>
    <?php else: ?>
      <ul class="comment-list">
        <?php foreach ($comments as $c): ?>
          <li class="comment-item">
            <div class="comment-header">
              <span class="comment-author"><?php echo htmlspecialchars($c['name']); ?></span>
              <span class="comment-date"><?php echo date('M d, Y H:i', strtotime($c['created'])); ?></span>
            </div>
            <p class="comment-body"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <h3 class="comment-form-title">Leave a comment</h3>
    <form class="comment-form" action="comment_submit.php" method="post">
      <input type="hidden" name="post_id" value="<?php echo (int)$post_id; ?>">
      <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">

      <div class="form-row">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required maxlength="100">
      </div>

      <div class="form-row">
        <label for="comment_text">Comment</label>
        <textarea id="comment_text" name="comment_text" rows="4" required></textarea>
      </div>

      <button type="submit" class="btn-primary comment-submit">Post comment</button>
    </form>
  </section>

  <a class="back-link" href="index.php">← Back to all articles</a>
</article>

<script>
function copyPostLink(url) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(url).then(function() {
      alert('Link copied!');
    }, function() {
      alert('Could not copy link, please copy manually.');
    });
  } else {
    var temp = document.createElement('input');
    temp.value = url;
    document.body.appendChild(temp);
    temp.select();
    try { document.execCommand('copy'); alert('Link copied!'); }
    catch(e){ alert('Could not copy link, please copy manually.'); }
    document.body.removeChild(temp);
  }
}
</script>

<?php
include 'footer.php';
$stmt->close();
$conn->close();
