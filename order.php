<?php
if (!session_id()) { session_start(); }

/*강제 로그 설정 (임시 디버깅용)*/
@ini_set('log_errors', '1');
@ini_set('error_reporting', E_ALL);
/* 우선 wp-content/order-debug.log 로 시도 */
@ini_set('error_log', ABSPATH . 'wp-content/order-debug.log');
error_log('[ORDER] bootstrap ' . date('c'));
/* wp-content에 못 쓸 때(권한/경로 문제) 대비한 2차 경로: 시스템 임시폴더 */
if (!file_exists(ABSPATH . 'wp-content/order-debug.log')) {
  $fallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'order-debug.log';
  @ini_set('error_log', $fallback);
  error_log('[ORDER] fallback logging to ' . $fallback);
}
/* 강제 로그 설정 끝 */

/**
 * Template Name: Order
 */

/**
 *  장바구니/선택주문 소스 결정
 *  - 선택주문 세션(order_items)이 있으면 우선 사용
 *  - 둘 다 비면(주문할 게 없으면) GET 요청에서만 장바구니로 돌려보냄
 *    (POST일 때는 디버깅/검증 위해 그대로 진행)
 *-*/
$cart_session = function_exists('book_cart_get')  ? book_cart_get()  : [];
$order_set    = function_exists('order_items_get')? order_items_get(): [];
$cart_source  = !empty($order_set) ? $order_set : $cart_session;

/* 진입 상황 로깅 */
error_log('[ORDER] method=' . ($_SERVER['REQUEST_METHOD'] ?? ''));
error_log('[ORDER] sid=' . session_id());
error_log('[ORDER] cart_count=' . (is_array($cart_session)?count($cart_session):0));
error_log('[ORDER] order_items_count=' . (is_array($order_set)?count($order_set):0));

if (empty($cart_source) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  wp_safe_redirect( home_url('/cart/') );
  exit;
}

/**
 *  주문 제출 처리
 *-*/
if ( ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
     && isset($_POST['order_nonce'])
     && wp_verify_nonce($_POST['order_nonce'], 'order_submit') ) {

  error_log('[ORDER] POST in — nonce=Y');
  error_log('[ORDER] verify=OK');
  error_log('[ORDER] cart_source_count=' . count($cart_source));
  error_log('[ORDER] will_call_shop_order_create=' . (function_exists('shop_order_create')?'Y':'N'));

  // 합계 계산
  $subtotal = 0; $discount_total = 0; $shipping_fee = 0; $coupon_discount = 0; $point_use = 0;
  foreach ($cart_source as $book_id => $qty) {
    $qty      = (int) $qty;
    $regular  = (float) preg_replace('/[^\d.]/', '', (string) get_field('price', $book_id));
    $sale_raw = get_field('discount_price', $book_id);
    if ($sale_raw === '' || $sale_raw === null) { $sale_raw = get_field('sale_price', $book_id); }
    $sale     = (float) preg_replace('/[^\d.]/', '', (string) $sale_raw);
    $unit     = ($sale > 0 && $sale < $regular) ? $sale : $regular;

    $subtotal       += $unit * $qty;
    $discount_total += max(0, ($regular - $unit)) * $qty;
  }
  $total = max(0, $subtotal + $shipping_fee - $discount_total - $coupon_discount - $point_use);

  // 고객 정보
  $customer = [
    'name'     => sanitize_text_field($_POST['name'] ?? ''),
    'email'    => sanitize_email($_POST['email'] ?? ''),
    'phone'    => sanitize_text_field($_POST['phone'] ?? ''),
    'postcode' => sanitize_text_field($_POST['postcode'] ?? ''),
    'road'     => sanitize_text_field($_POST['road_address'] ?? ''),
    'jibun'    => sanitize_text_field($_POST['jibun_address'] ?? ''),
    'extra'    => sanitize_text_field($_POST['extra_address'] ?? ''),
    'detail'   => sanitize_text_field($_POST['address_detail'] ?? ''),
    'memo'     => sanitize_text_field($_POST['memo'] ?? ''),
  ];

  // 저장
  $order_id = 0;
  if (function_exists('shop_order_create')) {
    $order_id = shop_order_create([
      'customer' => $customer,
      'items'    => array_map('intval', $cart_source),
      'amounts'  => [
        'subtotal'       => $subtotal,
        'discount_total' => $discount_total,
        'shipping'       => $shipping_fee,
        'coupon'         => $coupon_discount,
        'points'         => $point_use,
        'total'          => $total,
      ],
    ]);
  }

  // 생성 직후 로그
  error_log('[ORDER] created order_id=' . (int)$order_id);

  // 선택/바로구매 세트 정리
  if (function_exists('order_items_clear')) { order_items_clear(); }

  // 완료 페이지 URL
  $complete     = get_page_by_path('order-complete');
  $complete_url = $complete ? get_permalink($complete->ID) : home_url('/order-complete/');
  if (!empty($order_id)) {
    $complete_url = add_query_arg('order_id', (int)$order_id, $complete_url);
  }

  // 리다이렉트 직전 로그
  error_log('[ORDER] redirect_to=' . $complete_url);

  // POST → GET 표준 리다이렉트 (캐시/프록시 호환성 ↑)
  wp_safe_redirect( esc_url_raw($complete_url), 303 );
  exit;
}
/* POST 분기 실패한 경우(예: nonce 오류)도 원인 파악 로그 남기기 */
if (('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) && empty($_POST['order_nonce'])) {
  error_log('[ORDER] POST but nonce missing');
} elseif (('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) && !wp_verify_nonce($_POST['order_nonce'] ?? '', 'order_submit')) {
  error_log('[ORDER] POST but verify=FAIL');
}

get_header();

/*
 *  화면 표시용 합계
 */
$items_count = 0; $subtotal = 0; $discount_total = 0;
foreach ($cart_source as $book_id => $qty) {
  $qty = (int)$qty; $items_count += $qty;
  $regular = (float) preg_replace('/[^\d.]/', '', (string) get_field('price', $book_id));
  $sale    = get_field('discount_price', $book_id);
  if ($sale === '' || $sale === null) { $sale = get_field('sale_price', $book_id); }
  $sale    = (float) preg_replace('/[^\d.]/', '', (string) $sale);
  $unit    = ($sale > 0 && $sale < $regular) ? $sale : $regular;
  $subtotal += $unit * $qty;
  $discount_total += max(0, ($regular - $unit)) * $qty;
}
$shipping_fee = 0; $coupon_discount = 0; $point_use = 0;
$grand_total  = max(0, $subtotal + $shipping_fee - $discount_total - $coupon_discount - $point_use);
?>

<section class="order-page">
  <div class="container">
    <h1>주문서</h1>

    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
      <?php wp_nonce_field('order_submit', 'order_nonce'); ?>
      <input type="hidden" name="action" value="place_order">

      <div class="checkout-grid">
        <div class="left-col">
          <div class="card section-shipping">
            <div class="card-title">배송지 정보</div>

            <div class="form-grid">
              <label>이름 <input type="text" name="name" required></label>
              <label>연락처 <input type="tel" name="phone" required></label>

              <label>우편번호
                <div style="display:flex;gap:8px;">
                  <input type="text" name="postcode" id="postcode" placeholder="우편번호" readonly required style="flex:1;">
                  <button type="button" class="btn-secondary" onclick="openDaumPostcode()">주소 찾기</button>
                </div>
              </label>

              <label class="full">도로명 주소
                <input type="text" name="road_address" id="road_address" placeholder="예) 서울특별시 중구 세종대로 110" readonly required>
              </label>
              <label class="full">지번 주소
                <input type="text" name="jibun_address" id="jibun_address" placeholder="예) 서울특별시 중구 태평로1가 31" readonly>
              </label>
              <label class="full">참고항목(동/건물명)
                <input type="text" name="extra_address" id="extra_address" placeholder="예) (OO아파트 101동)">
              </label>
              <label class="full">상세주소
                <input type="text" name="address_detail" id="address_detail" placeholder="예) 101동 1203호" required>
              </label>

              <label class="full">이메일 <input type="email" name="email" required></label>
            </div>

            <label class="full mt12">배송요청사항
              <input type="text" name="memo" placeholder="배송 시 요청사항 / 미입력 시 일반 배송">
            </label>

            <div class="entrance mt12">
              <div class="label">공동현관 출입방법</div>
              <label class="radio"><input type="radio" name="entrance" value="pin" checked> 공동현관 비밀번호</label>
              <label class="radio"><input type="radio" name="entrance" value="free"> 자유출입 가능</label>
              <div class="pin-wrap"><input type="text" name="entrance_pin" placeholder="예: #1234*" autocomplete="off"></div>
            </div>
          </div>

          <div class="card section-items">
            <div class="card-title">주문상품 <span class="muted">총 <?php echo (int)$items_count; ?>개</span></div>
            <ul class="item-list">
              <?php foreach ($cart_source as $book_id => $qty) :
                $title = get_the_title($book_id);
                $per_regular = (float) preg_replace('/[^\d.]/', '', (string) get_field('price', $book_id));
                $per_sale    = get_field('discount_price', $book_id);
                if ($per_sale === '' || $per_sale === null) { $per_sale = get_field('sale_price', $book_id); }
                $per_sale    = (float) preg_replace('/[^\d.]/', '', (string) $per_sale);
                $unit        = ($per_sale > 0 && $per_sale < $per_regular) ? $per_sale : $per_regular;
                $line        = $unit * (int)$qty;
                $thumb       = get_the_post_thumbnail($book_id, 'book-thumb-nocrop', ['class'=>'item-thumb','alt'=>$title]);
              ?>
              <li class="item-row">
                <a class="thumb" href="<?php echo esc_url(get_permalink($book_id)); ?>"><?php echo $thumb; ?></a>
                <div class="item-meta">
                  <a class="item-title" href="<?php echo esc_url(get_permalink($book_id)); ?>"><?php echo esc_html($title); ?></a>
                  <div class="item-sub">
                    <span class="price">
                      <?php if ($per_sale > 0 && $per_sale < $per_regular): ?>
                        <em class="sale"><?php echo number_format_i18n($per_sale); ?>원</em>
                        <s class="regular"><?php echo number_format_i18n($per_regular); ?>원</s>
                      <?php else: ?>
                        <em class="sale"><?php echo number_format_i18n($per_regular); ?>원</em>
                      <?php endif; ?>
                    </span>
                    <span class="qty">수량 <?php echo (int)$qty; ?>개</span>
                  </div>
                </div>
                <div class="item-line"><?php echo number_format_i18n($line); ?>원</div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <aside class="right-col">
          <div class="card section-summary">
            <div class="card-title">결제 금액</div>
            <dl class="price-rows">
              <div class="row"><dt>상품 금액</dt><dd><?php echo number_format_i18n($subtotal); ?>원</dd></div>
              <div class="row"><dt>배송비</dt><dd><?php echo number_format_i18n($shipping_fee); ?>원</dd></div>
              <div class="row minus"><dt>상품 할인</dt><dd>- <?php echo number_format_i18n($discount_total); ?>원</dd></div>
              <div class="row minus"><dt>쿠폰 할인</dt><dd>- <?php echo number_format_i18n($coupon_discount); ?>원</dd></div>
              <div class="row minus"><dt>포인트 사용</dt><dd>- <?php echo number_format_i18n($point_use); ?>원</dd></div>
              <div class="row total"><dt>최종 결제 금액</dt><dd><?php echo number_format_i18n($grand_total); ?>원</dd></div>
            </dl>

            <div class="agreements">
              <label class="check"><input type="checkbox" name="agree_order" required> 주문 상품 정보 동의</label>
              <label class="check"><input type="checkbox" name="agree_privacy" required> 개인정보 수집 및 이용동의</label>
            </div>

            <div class="order-submit stack">
              <button type="submit" class="btn-primary">결제하기</button>
              <a href="<?php echo esc_url(home_url('/cart/')); ?>" class="btn-secondary">장바구니로 돌아가기</a>
            </div>
          </div>
        </aside>
      </div>
    </form>

    <!-- 다음 우편번호 레이어 -->
    <div id="postcode-layer"
         style="display:none;position:fixed;overflow:hidden;z-index:9999;-webkit-overflow-scrolling:touch;padding-top:48px;">
      <button id="postcode-layer-close" type="button" title="닫기"
              style="position:absolute;right:8px;top:8px;width:32px;height:32px;border-radius:50%;
                     background:#111;color:#fff;border:0;display:flex;align-items:center;justify-content:center;
                     cursor:pointer;line-height:1;font-size:18px;z-index:2;">×</button>
    </div>
  </div>
</section>

<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
  const layerEl = document.getElementById('postcode-layer');
  const closeBtn = document.getElementById('postcode-layer-close');

  function closeDaumPostcode() {
    if (!layerEl) return;
    layerEl.style.display = 'none';
  }
  function centerLayer(w, h) {
    layerEl.style.width  = w + 'px';
    layerEl.style.height = h + 'px';
    layerEl.style.left   = ((window.innerWidth  - w) / 2) + 'px';
    layerEl.style.top    = ((window.innerHeight - h) / 2) + 'px';
    layerEl.style.background   = '#fff';
    layerEl.style.border       = '1px solid #ddd';
    layerEl.style.borderRadius = '12px';
    layerEl.style.boxShadow    = '0 10px 30px rgba(0,0,0,.2)';
  }
  function openDaumPostcode() {
    if (!layerEl) return;
    const w = Math.min(window.innerWidth - 24, 480);
    const h = Math.min(window.innerHeight - 24, 520);
    centerLayer(w, h);
    layerEl.style.display = 'block';

    new daum.Postcode({
      oncomplete: function(data) {
        const road  = data.roadAddress;
        const jibun = data.jibunAddress;
        let extra   = '';
        if (data.bname && /[동|로|가]$/g.test(data.bname)) { extra += data.bname; }
        if (data.buildingName && data.apartment === 'Y') { extra += (extra ? ', ' + data.buildingName : data.buildingName); }
        if (extra) extra = '(' + extra + ')';
        document.getElementById('postcode').value      = data.zonecode || '';
        document.getElementById('road_address').value  = road || '';
        document.getElementById('jibun_address').value = jibun || '';
        document.getElementById('extra_address').value = extra || '';
        document.getElementById('address_detail').focus();
        closeDaumPostcode();
      },
      width: '100%', height: '100%'
    }).embed(layerEl);

    if (closeBtn && closeBtn.parentNode !== layerEl) {
      layerEl.appendChild(closeBtn);
    }
  }
  if (closeBtn) closeBtn.addEventListener('click', closeDaumPostcode);
  window.addEventListener('resize', () => {
    if (layerEl && layerEl.style.display === 'block') {
      const w = Math.min(window.innerWidth - 24, 480);
      const h = Math.min(window.innerHeight - 24, 520);
      centerLayer(w, h);
    }
  });
</script>

<?php get_footer(); ?>
