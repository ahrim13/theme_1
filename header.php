<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <!-- 사이트의 문자 인코딩과 반응형 뷰포트 설정 -->
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- 워드프레스 사이트 이름을 페이지 제목으로 표시 -->
  <title><?php bloginfo('name'); ?></title>

  <!-- wp_head(): 테마나 플러그인이 필요한 스크립트·스타일 삽입 -->
  <?php wp_head(); ?>
</head>

<!-- body_class(): 현재 페이지 상황에 맞는 클래스 자동 부여 -->
<body <?php body_class(); ?>>

<!-- 상단 헤더 전체 영역 -->
<header class="site-header">
  <div class="container">

    <!-- 왼쪽 로고: 홈으로 이동하는 링크 -->
    <h1 class="logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <span class="pink">DREAM</span> BOOKS
      </a>
    </h1>

    <?php
      // 상단 네비게이션 메뉴 항목 정의
      $nav_items = [
        [ 'url' => home_url('/'), 'label' => '에디터의 선택' ],
        [ 'url' => get_category_link(get_category_by_slug('bestseller')->term_id), 'label' => '베스트셀러' ],
        [ 'url' => get_category_link(get_category_by_slug('new')->term_id), 'label' => '신상품' ],
      ];

      // 메뉴 출력용 함수 정의
      // (class명과 메뉴 배열을 받아 HTML로 출력)
      function print_nav($class, $items) {
        echo "<nav class=\"$class\"><ul>";
        foreach ($items as $item) {
          echo "<li><a href=\"".esc_url($item['url'])."\">".esc_html($item['label'])."</a></li>";
        }
        echo "</ul></nav>";
      }
    ?>

    <!-- 오른쪽 영역: 네비게이션 + 장바구니 + 모바일메뉴버튼 -->
    <div class="header-right">
      <!-- PC용 네비게이션 메뉴 출력 -->
      <?php print_nav("main-nav", $nav_items); ?>

      <?php
        // 장바구니 아이콘 영역
        // get_cart_url(), book_cart_count()는 functions.php 등에서 정의된 함수로 가정
        $cart_url   = function_exists('get_cart_url') ? get_cart_url() : home_url('/cart');
        $cart_count = function_exists('book_cart_count') ? (int) book_cart_count() : 0;
      ?>
      
      <!-- 장바구니 아이콘 + 수량 배지 -->
      <a href="<?php echo esc_url($cart_url); ?>" class="cart-icon-wrapper" aria-label="장바구니로 이동">
        <span class="cart-emoji" aria-hidden="true">🛒</span>
        <!-- 장바구니에 상품이 있을 때만 배지 표시 -->
        <?php if ($cart_count > 0): ?>
          <span class="cart-badge" aria-live="polite"><?php echo $cart_count; ?></span>
        <?php endif; ?>
      </a>

      <!-- 모바일용 메뉴 열기 버튼 (햄버거 아이콘) -->
      <button class="menu-toggle" aria-label="메뉴 열기" aria-controls="mobileNav" aria-expanded="false">☰</button>
    </div>
  </div>

  <!-- 모바일용 네비게이션 메뉴 출력 -->
  <?php print_nav("mobile-nav", $nav_items); ?>
</header>

<!-- 모바일 메뉴 토글 관련 스크립트 -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector(".menu-toggle");
    const mobileNav = document.querySelector(".mobile-nav");
    if (!toggle || !mobileNav) return; // 요소 없으면 종료

    // 햄버거 버튼 클릭 시 메뉴 열기/닫기
    toggle.addEventListener("click", function () {
      const open = mobileNav.classList.toggle("active"); // active 토글
      toggle.setAttribute("aria-expanded", open ? "true" : "false"); // 접근성 상태 업데이트
      document.body.style.overflow = open ? "hidden" : ""; // 메뉴 열릴 때 스크롤 잠금
      if (open) history.pushState({ menuOpen: true }, "", ""); // 뒤로가기로 닫을 수 있도록 상태 추가
    });

    // 브라우저 뒤로가기(popstate) 시 메뉴 닫기
    window.addEventListener("popstate", function () {
      if (mobileNav.classList.contains("active")) {
        mobileNav.classList.remove("active");
        toggle.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
      }
    });

    // 오른쪽 → 왼쪽 스와이프 시 메뉴 닫기
    let touchStartX = null;
    mobileNav.addEventListener("touchstart", (e) => touchStartX = e.touches[0].clientX);
    mobileNav.addEventListener("touchend", (e) => {
      if (touchStartX === null) return;
      const dx = e.changedTouches[0].clientX - touchStartX;
      // 70px 이상 오른쪽 스와이프하면 메뉴 닫기
      if (dx > 70) {
        mobileNav.classList.remove("active");
        toggle.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
        history.back(); // 뒤로가기 이벤트로 닫기 연동
      }
      touchStartX = null;
    });
  });
</script>

