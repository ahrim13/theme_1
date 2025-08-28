<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php bloginfo('name'); ?></title>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
  <div class="container">

    <!-- Left: Logo -->
    <h1 class="logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <span class="pink">DREAM</span> BOOKS
      </a>
    </h1>

    <?php
      $nav_items = [
        [ 'url' => home_url('/'), 'label' => '에디터의 선택' ],
        [ 'url' => get_category_link(get_category_by_slug('bestseller')->term_id), 'label' => '베스트셀러' ],
        [ 'url' => get_category_link(get_category_by_slug('new')->term_id), 'label' => '신상품' ],
      ];
      function print_nav($class, $items) {
        echo "<nav class=\"$class\"><ul>";
        foreach ($items as $item) {
          echo "<li><a href=\"".esc_url($item['url'])."\">".esc_html($item['label'])."</a></li>";
        }
        echo "</ul></nav>";
      }
    ?>

    <div class="header-right">
      <?php print_nav("main-nav", $nav_items); ?>

      <?php
        $cart_url   = function_exists('get_cart_url') ? get_cart_url() : home_url('/cart');
        $cart_count = function_exists('book_cart_count') ? (int) book_cart_count() : 0;
      ?>
      <a href="<?php echo esc_url($cart_url); ?>" class="cart-icon-wrapper" aria-label="장바구니로 이동">
        <span class="cart-emoji" aria-hidden="true">🛒</span>
        <?php if ($cart_count > 0): ?>
          <span class="cart-badge" aria-live="polite"><?php echo $cart_count; ?></span>
        <?php endif; ?>
      </a>

      <button class="menu-toggle" aria-label="메뉴 열기" aria-controls="mobileNav" aria-expanded="false">☰</button>
    </div>
  </div>

  <?php print_nav("mobile-nav", $nav_items); ?>
</header>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector(".menu-toggle");
    const mobileNav = document.querySelector(".mobile-nav");
    if (!toggle || !mobileNav) return;

    toggle.addEventListener("click", function () {
      const open = mobileNav.classList.toggle("active");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      document.body.style.overflow = open ? "hidden" : "";
      if (open) history.pushState({ menuOpen: true }, "", "");
    });

    window.addEventListener("popstate", function () {
      if (mobileNav.classList.contains("active")) {
        mobileNav.classList.remove("active");
        toggle.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
      }
    });

    let touchStartX = null;
    mobileNav.addEventListener("touchstart", (e) => touchStartX = e.touches[0].clientX);
    mobileNav.addEventListener("touchend", (e) => {
      if (touchStartX === null) return;
      const dx = e.changedTouches[0].clientX - touchStartX;
      if (dx > 70) { mobileNav.classList.remove("active"); toggle.setAttribute("aria-expanded","false"); document.body.style.overflow=""; history.back(); }
      touchStartX = null;
    });
  });
</script>
