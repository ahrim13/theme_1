<?php
/**
 * Template Name: Cart
 * 장바구니 페이지
 */
get_header();

// 장바구니 데이터 가져오기 (book_cart_get()은 세션 또는 DB에 저장된 장바구니 불러오는 함수)
$cart = book_cart_get();
$total = 0; // 총합 초기화
?>

<section class="cart-page">
  <div class="container">
    <h1>장바구니</h1>

    <?php if (empty($cart)) : ?>
      <!-- 장바구니가 비어 있을 때 -->
      <p>장바구니가 비어 있습니다.</p>

    <?php else : ?>
      <!-- 장바구니에 상품이 있을 때 테이블 형태로 표시 -->
      <table class="cart-table">
        <colgroup>
          <col style="width:48px">
          <col class="col-item">
          <col class="col-qty" style="width:160px">
          <col class="col-subtotal" style="width:140px">
        </colgroup>

        <thead>
          <tr>
            <th>
              <input type="checkbox" id="select-all" />
            </th>
            <th>도서</th>
            <th>수량</th>
            <th>소계</th>
          </tr>
        </thead>

        <tbody>
          <?php 
          // 장바구니에 담긴 도서들 반복 출력
          foreach ($cart as $book_id => $qty) :
            $title = get_the_title($book_id);            // 책 제목
            $per   = book_cart_price($book_id);          // 단가(할인 반영된 가격)
            $line  = $per * $qty;                        // 소계(단가 x 수량)
            $total += $line;                             // 총합에 더하기

            // 썸네일 (책 표지 이미지)
            $thumb = get_the_post_thumbnail($book_id, 'medium', [
              'alt'   => $title,
              'class' => 'cart-thumb'
            ]);

            // 삭제 버튼 클릭 시 호출될 URL (보안용 nonce 포함)
            $remove_url = wp_nonce_url(
              admin_url('admin-post.php?action=book_cart_remove&book_id=' . (int)$book_id),
              'book_cart',
              'cart_nonce'
            );
          ?>
            <tr>
              <!-- 선택 체크박스 -->
              <td class="td-check">
                <input type="checkbox" class="row-check"
                  name="selected[]"
                  value="<?php echo (int)$book_id; ?>"
                  form="cart-select-form" />
              </td>

              <!-- 도서 정보 -->
              <td class="cart-item">
                <!-- 썸네일 클릭 시 상세페이지로 이동 -->
                <a href="<?php echo get_permalink($book_id); ?>" class="thumb-wrap"><?php echo $thumb ?: ''; ?></a>
                <div class="cart-item-meta">
                  <!-- 제목 클릭 시 상세페이지로 이동 -->
                  <a class="cart-item-title" href="<?php echo get_permalink($book_id); ?>"><?php echo esc_html($title); ?></a>
                  <!-- 단가(1권 가격) 표시 -->
                  <div class="unit-price"><?php echo number_format($per); ?>원</div>
                </div>
                <a href="<?php echo esc_url($remove_url); ?>" class="item-remove" aria-label="삭제">×</a>
              </td>

              <!-- 수량 조절 -->
              <td class="td-qty">
                <div class="qty-controls">

                <!-- 수량 줄이기 버튼 -->
                <!-- 클릭하면 수량이 1 줄어든 값으로 서버에 다시 전송됨 -->
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="qty-form">
                  <!-- 워드프레스 admin-post 엔드포인트로 보낼 액션 이름 -->
                  <input type="hidden" name="action" value="book_cart_update">
                  <!-- CSRF 방지를 위한 nonce (보안 토큰) -->
                  <?php wp_nonce_field('book_cart', 'cart_nonce'); ?>
                  <!-- 어떤 책인지 식별용 -->
                  <input type="hidden" name="book_id" value="<?php echo (int)$book_id; ?>">
                  <!-- 수량은 현재 값에서 -1 (0 미만으로는 안 내려감) -->
                  <input type="hidden" name="qty" value="<?php echo max(0, (int)$qty - 1); ?>">
                  <!-- 제출 버튼: − 표시 -->
                  <button type="submit" class="btn-qty btn-qty-dec" aria-label="수량 감소">−</button>
                </form>

                <!-- 현재 수량 표시 -->
                <!-- 그냥 숫자 보여주는 역할 (읽기 전용) -->
                <span class="qty-number" aria-live="polite"><?php echo (int)$qty; ?></span>

                <!-- 수량 늘리기 버튼 -->
                <!-- 클릭하면 수량이 1 늘어난 값으로 서버에 전송됨 -->
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="qty-form">
                  <input type="hidden" name="action" value="book_cart_update">
                  <?php wp_nonce_field('book_cart', 'cart_nonce'); ?>
                  <input type="hidden" name="book_id" value="<?php echo (int)$book_id; ?>">
                  <!-- 수량은 현재 값에서 +1 (최대 99까지만 허용) -->
                  <input type="hidden" name="qty" value="<?php echo min(99, (int)$qty + 1); ?>">
                  <button type="submit" class="btn-qty btn-qty-inc" aria-label="수량 증가">+</button>
                </form>
              </div>
            </td>


              <!-- 소계(한 줄 가격 합계) -->
              <td class="td-subtotal">
                <span class="subtotal-amount"><?php echo number_format($line); ?>원</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

<!-- 장바구니 하단: 총 합계와 주문 버튼 영역 -->
<div class="cart-summary">
  <div class="summary-right">  
      <!-- 합계 금액 표시 -->
      <div class="total-row">
        <span class="total-label">합계</span>
        <!-- number_format 숫자 쉼표 찍어줌 -->
        <strong class="total-amount"><?php echo number_format($total); ?>원</strong>
      </div>

      <!-- 주문 버튼 영역 -->
      <div class="cart-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
        <!-- 선택한 상품만 주문하기 -->
        <!-- 체크박스로 고른 항목만 주문서로 넘김 -->
        <form id="cart-select-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <!-- 워드프레스 admin-post로 넘길 action 이름 -->
          <input type="hidden" name="action" value="cart_order_selected">
          <!-- 보안용 nonce (cart_order 그룹으로 검증) -->
          <?php wp_nonce_field('cart_order', 'order_nonce'); ?>
          <!-- 버튼 클릭 시 선택된 상품들만 주문 -->
          <button type="submit" class="btn-checkout">선택주문</button>
        </form>

        <!-- 장바구니 전체 상품 주문 -->
        <!-- 한 번에 모든 항목을 주문서로 넘김 -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="cart_order_all">
          <!-- 전체 주문용 nonce (cart_order_all 그룹) -->
          <?php wp_nonce_field('cart_order_all', 'order_all_nonce'); ?>
          <button type="submit" class="btn-checkout">전체주문</button>
        </form>
      </div>
    </div>
    <!-- 장바구니가 비어 있지 않은 경우에만 표시되도록 위쪽 if문에 묶여 있음 -->
    <?php endif; ?>
  </div>
</section>


<script>
// 전체선택 체크박스 제어
document.addEventListener('DOMContentLoaded', function () {
  const master = document.getElementById('select-all');
  const checks = document.querySelectorAll('.row-check');
  if (master) {
    master.addEventListener('change', function () {
      checks.forEach(c => { c.checked = master.checked; });
    });
  }
});
</script>

<?php get_footer(); ?>
