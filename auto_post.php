<?php
// auto_post.php

// ---------- SECRET PROTECTION ----------
$SECRET_KEY = ""; // ostavi svoj

if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    // Ako je cron job, možda neće koristiti GET, pa dozvoljavamo CLI izvršenje:
    if (php_sapi_name() !== 'cli') {
        http_response_code(403);
        die("Forbidden");
    }
}
// --------------------------------------

require_once 'config.php';
date_default_timezone_set('Europe/Belgrade');

// ⚠️ OVDE stavi svoj pravi API ključ
// ⚠️ OPENAI API ključ se čuva u config_local.php (koji NIJE u GitHubu)
$OPENAI_API_KEY = null;
if (file_exists(__DIR__ . '/config_local.php')) {
    include __DIR__ . '/config_local.php';
}

if (empty($OPENAI_API_KEY)) {
    die("OPENAI_API_KEY is not set. Please set it in config_local.php");
}


// Teme za blog - osnovne, AI će praviti long-tail varijante
$topics = array(
    "financial freedom habits",
    "getting out of debt step by step",
    "21-day money mindset reset",
    "simple budgeting for beginners",
    "psychology of money and habits",
    "side hustles for extra monthly income",
    "how to stop emotional spending",
    "daily rituals for a rich money mindset",
    "how to build an emergency fund",
    "small money wins you can start today"
);

// ---- UČITAVANJE POSTOJEĆIH NASLOVA ZA ANTI-DUPLIKATE ----
$existing_titles = array();
$res = $conn->query("SELECT title FROM " . $TABLE_POSTS . " ORDER BY id DESC LIMIT 50");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['title'])) {
            $existing_titles[] = $row['title'];
        }
    }
    $res->free();
}

$existing_titles_str = '';
if (!empty($existing_titles)) {
    $existing_titles_str = "- " . implode("\n- ", $existing_titles);
}

// Funkcija za slug
function slugify_local($text) {
    $text = trim($text);
    if (function_exists('mb_strtolower')) {
        $text = mb_strtolower($text, 'UTF-8');
    } else {
        $text = strtolower($text);
    }
    $replacements = array(
        'š' => 's','đ' => 'dj','č' => 'c','ć' => 'c','ž' => 'z',
        'Š' => 's','Đ' => 'dj','Č' => 'c','Ć' => 'c','Ž' => 'z'
    );
    $text = strtr($text, $replacements);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'post';
    }
    return $text;
}

// ---- POKUŠAJ GENERISANJA VIŠE PUTA AKO JE DUPLIKAT ----
$max_attempts = 3;
$attempt = 0;
$inserted = false;

while ($attempt < $max_attempts && !$inserted) {
    $attempt++;

    // Izaberi random osnovnu temu
    $base_topic = $topics[array_rand($topics)];

    // Prompt sa anti-duplicate i SEO pravilima
    $prompt = "You are a blog writer for a website called FinancialFreedomHabits.site.

We already have articles with the following titles:
" . $existing_titles_str . "

Do NOT repeat these titles or create something too similar. 
Instead, create a fresh, unique long-tail angle on the topic: \"$base_topic\".

SEO rules:
- First choose a specific long-tail keyword (for example: 'simple weekly budgeting system for families') and build the article around it.
- Use this long-tail keyword naturally in the title, first paragraph, at least one <h2>, and in the conclusion.
- Aim for around 1500–2000 words.
- Use short paragraphs and clear structure.
- Add at least 4–6 <h2>/<h3> sections.
- Include a small FAQ section at the end (3–5 questions), each as <h3> with short <p> answers.

Return ONLY JSON with this exact structure:

{
  \"title\": \"...\",
  \"slug\": \"...\",
  \"excerpt\": \"...\",
  \"content_html\": \"...\"
}

Rules:
- 'title' should be clear, helpful, and focused on ONE concrete long-tail keyword (no clickbait).
- 'slug' must be URL friendly: lowercase, words separated by hyphens, no special characters.
- 'excerpt' should be 2–3 sentences, max ~400 characters, plain text (no HTML).
- 'content_html' must be valid HTML using <h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>, and FAQ section at the end.
- Do NOT include <html>, <head> or <body> tags.
- Respond ONLY with JSON.";

    // Poziv OpenAI API
    $apiUrl = "https://api.openai.com/v1/chat/completions";

    $payload = array(
        "model" => "gpt-4.1-mini", // ili drugi model koji koristiš
        "messages" => array(
            array("role" => "system", "content" => "You are a professional blog writer and SEO expert."),
            array("role" => "user", "content" => $prompt)
        ),
        "temperature" => 0.7,
        "max_tokens" => 2200
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $OPENAI_API_KEY
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        die($error);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code < 200 || $http_code >= 300) {
        die("OpenAI API returned HTTP code " . $http_code . ": " . htmlspecialchars($response));
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        die("Invalid API response.");
    }

    $jsonString = trim($data['choices'][0]['message']['content']);

    $postData = json_decode($jsonString, true);
    if (!$postData) {
        die("Failed to decode generated JSON. Raw: <pre>" . htmlspecialchars($jsonString) . "</pre>");
    }

    $title        = isset($postData['title']) ? trim($postData['title']) : '';
    $slug         = isset($postData['slug']) ? trim($postData['slug']) : '';
    $excerpt      = isset($postData['excerpt']) ? trim($postData['excerpt']) : '';
    $content_html = isset($postData['content_html']) ? $postData['content_html'] : '';

    if ($title === '' || $slug === '' || $content_html === '') {
        // ako je ova tura fail, probaj sledeći attempt
        continue;
    }

    // Slug sanitizacija
    $slug = slugify_local($slug);

    // ---- PROVERA DA LI VEĆ POSTOJI ISTI TITLE ILI SLUG ----
    $dup = false;
    $chk = $conn->prepare("SELECT id FROM " . $TABLE_POSTS . " WHERE slug = ? OR title = ? LIMIT 1");
    if ($chk) {
        $chk->bind_param("ss", $slug, $title);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $dup = true;
        }
        $chk->close();
    }

    if ($dup) {
        // ako je duplikat, probaj sledeći attempt
        continue;
    }

    // Unikatan slug (za svaki slučaj, ako ima manualnih kolizija)
    $base_slug = $slug;
    $i = 2;
    $exists = true;

    while ($exists) {
        $exists = false;
        $stmt = $conn->prepare("SELECT id FROM " . $TABLE_POSTS . " WHERE slug=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $exists = true;
                $slug = $base_slug . '-' . $i;
                $i++;
            }
            $stmt->close();
        }
    }

    // Ako excerpt prazan, iseckaj iz content-a
    if ($excerpt === '') {
        $plain = strip_tags($content_html);
        if (function_exists('mb_strlen')) {
            if (mb_strlen($plain, 'UTF-8') > 300) {
                $excerpt = mb_substr($plain, 0, 300, 'UTF-8') . '...';
            } else {
                $excerpt = $plain;
            }
        } else {
            if (strlen($plain) > 300) {
                $excerpt = substr($plain, 0, 300) . '...';
            } else {
                $excerpt = $plain;
            }
        }
    }

    $now = date('Y-m-d H:i:s');
    $status = 1;

    $stmt = $conn->prepare("INSERT INTO " . $TABLE_POSTS . " (slug, title, excerpt, content, created_at, status) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssssi", $slug, $title, $excerpt, $content_html, $now, $status);

    if ($stmt->execute()) {
        $inserted = true;
        echo "Post created successfully!<br>";
        echo "Title: " . htmlspecialchars($title) . "<br>";
        echo "Slug: " . htmlspecialchars($slug) . "<br>";
        echo '<a href="post.php?slug=' . urlencode($slug) . '" target="_blank">View post</a>';
    } else {
        echo "DB error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
}

// Ako posle nekoliko pokušaja i dalje duplira/previše liči
if (!$inserted) {
    echo "Could not generate a unique, non-duplicate post after {$max_attempts} attempts.";
}


$conn->close();
