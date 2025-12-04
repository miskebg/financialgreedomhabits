<?php
// Default values if page doesn't set them
if (!isset($page_title)) {
    $page_title = "Financial Freedom Habits – Simple Money & Mindset Blog";
}
if (!isset($page_description)) {
    $page_description = "FinancialFreedomHabits.site – simple tips, habits and mindset shifts for financial freedom.";
}

// Build current full URL
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$current_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// OG defaults (post.php can override)
if (!isset($og_type))  { $og_type  = 'website'; }
if (!isset($og_image)) { $og_image = 'https://financialfreedomhabits.site/assets/default-og.jpg'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <!-- Google Search Console Verification -->
    <meta name="google-site-verification" content="pJ4cGM-wdSTGlXT_3CGvsFessvtbgucCk0C915f7QmQ" />
    
    <meta name="google-adsense-account" content="ca-pub-8916833360205996">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8916833360205996"
     crossorigin="anonymous"></script>

    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <!-- OpenGraph SEO -->
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($current_url) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

    <!-- Styles -->
    <link rel="stylesheet" href="assets/style.css">

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-FJE14795T3"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-FJE14795T3');
    </script>
    
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8916833360205996"
     crossorigin="anonymous"></script>

</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <div class="logo">
      <a href="index.php">Financial<span>Freedom</span>Habits<span class="dot">.site</span></a>
    </div>
    <nav class="main-nav">
      <a href="index.php">Home</a>
      <a href="#habits">Habits</a>
      <a href="#money">Money</a>
      <a href="#challenges">21-Day Challenges</a>
      <a href="about.php">About</a>
<a href="privacy.php">Privacy</a>
<a href="terms.php">Terms</a>
    </nav>
  </div>
</header>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-text">

      <h1>Build calm, clear<br> <span>Financial Freedom Habits</span></h1>
      <p>Simple daily actions, better money mindset, and practical tips to get out of debt, save more, and live lighter.</p>
      <a href="#latest" class="btn-primary">Read latest articles</a>
    </div>
    <div class="hero-card">
      <h2>21-Day Money & Mindset Reset</h2>
      <p>Start a gentle 21-day reset: short daily prompts to shift your habits and thoughts around money.</p>
      <a href="#" class="btn-ghost">Coming soon</a>
    </div>
  </div>
</section>

<main class="site-main">
  <div class="container layout">
    <div class="content">
