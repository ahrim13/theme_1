<?php
/**
 * Template Name: Order Complete
 */
get_header();

// 주소창에서 주문번호(order_id) 가져오기
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
?>

<section class="order-complete">
  <div class="container">
    <?php 
    // 주문번호가 있고 shop_order 타입일 때만 상세정보 출력
    if ($order_id && get_post_type($order_id) === 'shop_order'):
      // 주문 메타데이터 불러오기 (고객정보, 상품목록, 결제금액 등)
      $meta     = shop_order_get_meta($order_id);
      $customer = $meta['customer'];
      $items    = $meta['items'];
      $amounts  = $meta['amounts'];
    ?>

      <div class="oc-card success">
        <div class="oc-success-head">
          <div class="oc-icon" aria-hidden="true">✓</div>
          <div>
            <h1>주문이 완료되었습니다</h1>
          </div>
        </div>
        <!-- 주문번호 표시 -->
        <div class="oc-orderid">주문번호 <strong>#<?php echo esc_html($order_id); ?></strong></div>
      </div>

      <div class="oc-grid">
        <!-- 왼쪽 영역: 상품과 고객정보 -->
        <div class="oc-left">
          <!-- 주문 상품 목록 -->
          <div class="oc-card">
            <h2 class="oc-h2">주문 상품</h2>
            <ul class="oc-items">
              <?php 
              // 주문된 도서들 반복 출력
              foreach ((array)$items as $book_id => $row):
                $qty   = max(1, (int)($row['qty'] ?? 1)); // 수량 없으면 기본 1
                $price = (int)($row['sale_price'] ?? $row['price'] ?? 0); // 할인가 우선
                $line  = $price * $qty; // 개당 가격 × 수량
                $title = get_the_title($book_id); // 책 제목
                $link  = get_permalink($book_id); // 상세페이지 링크
                $thumb = get_the_post_thumbnail_url($book_id, 'thumbnail'); // 썸네일 이미지
              ?>
              <li class="oc-item">
                <!-- 책 썸네일 -->
                <a class="oc-thumbwrap" href="<?php echo esc_url($link); ?>">
                  <?php if ($thumb): ?>
                    <img class="oc-thumb" src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                  <?php else: ?>
                    <span class="oc-thumb oc-thumb--placeholder">📘</span>
                  <?php endif; ?>
                </a>
                <!-- 제목, 수량 -->
                <div class="oc-meta">
                  <a class="oc-title" href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                  <div class="oc-qty">수량 <?php echo (int)$qty; ?>개</div>
                </div>
                <!-- 한 줄 금액 -->
                <div class="oc-lineprice"><?php echo fmt_price($line); ?></div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- 주문자 및 배송지 정보 -->
          <div class="oc-card">
            <h2 class="oc-h2">주문자 & 배송지</h2>

            <div class="oc-two">
              <div>
                <h3 class="oc-h3">주문자 정보</h3>
                <ul class="oc-info">
                  <li><strong>이름</strong><span><?php echo esc_html($customer['name'] ?? ''); ?></span></li>
                  <li><strong>연락처</strong><span><?php echo esc_html($customer['phone'] ?? ''); ?></span></li>
                  <li><strong>이메일</strong><span><?php echo esc_html($customer['email'] ?? ''); ?></span></li>
                </ul>
              </div>

              <div>
                <h3 class="oc-h3">배송지</h3>
                <ul class="oc-info">
                  <li><strong>우편번호</strong><span><?php echo esc_html($customer['postcode'] ?? ''); ?></span></li>
                  <li><strong>주소</strong>
                    <span><?php echo esc_html(($customer['road'] ?? $customer['jibun'] ?? '').' '.($customer['detail'] ?? '')); ?></span>
                  </li>
                  <?php if (!empty($customer['extra'])): ?>
                    <li><strong>참고</strong><span><?php echo esc_html($customer['extra']); ?></span></li>
                  <?php endif; ?>
                  <?php if (!empty($customer['memo'])): ?>
                    <li><strong>요청사항</strong><span><?php echo esc_html($customer['memo']); ?></span></li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- 오른쪽 영역: 결제 요약 -->
        <aside class="oc-right">
          <div class="oc-card">
            <h2 class="oc-h2">결제 금액</h2>
            <div class="oc-rows">
              <div class="row"><dt>상품합계</dt><dd><?php echo fmt_price($amounts['subtotal'] ?? 0); ?></dd></div>
              <div class="row"><dt>배송비</dt><dd><?php echo fmt_price($amounts['shipping'] ?? 0); ?></dd></div>
              <div class="row minus"><dt>할인합계</dt><dd>- <?php echo fmt_price($amounts['discount_total'] ?? 0); ?></dd></div>
              <?php if (!empty($amounts['coupon'])): ?>
                <div class="row minus"><dt>쿠폰</dt><dd>- <?php echo fmt_price($amounts['coupon']); ?></dd></div>
              <?php endif; ?>
              <?php if (!empty($amounts['points'])): ?>
                <div class="row minus"><dt>포인트</dt><dd>- <?php echo fmt_price($amounts['points']); ?></dd></div>
              <?php endif; ?>
              <div class="row total"><dt>최종 결제금액</dt><dd><?php echo fmt_price($amounts['total'] ?? 0); ?></dd></div>
            </div>

            <!-- 버튼: 내 주문 보기 / 메인으로 -->
            <div class="oc-actions">
              <a class="btn-primary" href="<?php echo esc_url( home_url('/my-order/') ); ?>">내 주문 확인</a>
              <a class="btn-secondary" href="<?php echo esc_url( home_url('/') ); ?>">메인으로</a>
            </div>
          </div>
        </aside>
      </div>

    <?php else: ?>
      <!-- order_id가 없거나 잘못된 경우 -->
      <div class="oc-card">
        <h1>주문이 완료되었습니다</h1>
        <p>감사합니다. 주문이 정상적으로 접수되었습니다.</p>
        <div class="oc-actions">
          <a class="btn-primary" href="<?php echo esc_url( home_url('/') ); ?>">홈으로</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>
