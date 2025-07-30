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
    <h1 class="logo">
      <a href="<?php echo home_url('/'); ?>">
        <span class="pink">DREAM</span> BOOKS
      </a>
    </h1>

    <?php
      $nav_items = [
        [ 'url' => home_url('/'), 'label' => '에디터의 선택' ],
        [ 'url' => get_category_link(get_category_by_slug('bestseller')->term_id), 'label' => '베스트셀러' ],
        [ 'url' => get_category_link(get_category_by_slug('new')->term_id), 'label' => '신상품' ],
        // [ 'url' => get_category_link(get_category_by_slug('discount')->term_id), 'label' => '이 달의 특가' ],
      ];
      function print_nav($class, $items) {
        echo "<nav class=\"$class\"><ul>";
        foreach ($items as $item) {
          echo "<li><a href=\"{$item['url']}\">{$item['label']}</a></li>";
        }
        echo "</ul></nav>";
      }
      print_nav("main-nav", $nav_items);
    ?>
  </div>

  <button class="menu-toggle" aria-label="메뉴 열기">☰</button>

  <?php print_nav("mobile-nav", $nav_items); ?>

</header>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector(".menu-toggle");
    const mobileNav = document.querySelector(".mobile-nav");

    if (toggle && mobileNav) {
      toggle.addEventListener("click", function () {
        mobileNav.classList.add("active");
        toggle.classList.add("hide"); 
        document.body.style.overflow = "hidden";
        history.pushState({ menuOpen: true }, "", "");
      });
      window.addEventListener("popstate", function () {
        if (mobileNav.classList.contains("active")) {
          mobileNav.classList.remove("active");
          toggle.classList.remove("hide");
          document.body.style.overflow = "";
        }
      });

      let touchStartX = null;
      mobileNav.addEventListener("touchstart", function (e) {
        touchStartX = e.touches[0].clientX;
      });
      mobileNav.addEventListener("touchend", function (e) {
        if (touchStartX !== null) {
          let touchEndX = e.changedTouches[0].clientX;
          if (touchEndX - touchStartX > 70) {
            mobileNav.classList.remove("active");
            toggle.classList.remove("hide");
            document.body.style.overflow = "";
            history.back();
          }
          touchStartX = null;
        }
      });
    }
  });
</script>
