<?php
/**
 * 00. 기본 설정/공통
 * 01. 스타일 로딩(페이지별)
 * 02. 세션 & 공통 유틸
 * 03. 커스텀 포스트 타입(CPT)
 * 04. 라우팅/템플릿 매핑 & URL 헬퍼
 * 05. 장바구니(세션/유틸/액션)
 * 06. 주문 이동(선택/전체/바로구매)
 * 07. 주문 생성/조회 헬퍼
 * 08. 주문 검색(책 제목)
 */

if (!defined('ABSPATH')) exit;

/*
 * 00. 기본 설정/공통
 * - 썸네일 사용
 * - 도서 포스트 타입 등록(book)
 * - 베스트셀러 순위 메타박스
 */
add_theme_support('post-thumbnails'); // 썸네일 기능 활성화

// 책(도서) 포스트 타입 등록
function register_book_post_type() {
  register_post_type('book', [
    'label'       => '책',
    'public'      => true,
    'has_archive' => true,
    'supports'    => ['title', 'editor', 'thumbnail'],
    'rewrite'     => ['slug' => 'book'],
    'taxonomies'  => ['category', 'post_tag'],
  ]);
}
add_action('init', 'register_book_post_type');

// 베스트셀러 순위 메타박스 (book/post 공용)
function add_bestseller_rank_meta_box() {
  add_meta_box(
    'bestseller_rank_meta',
    '베스트셀러 순위',
    'bestseller_rank_callback',
    ['book', 'post'],
    'side',
    'default'
  );
}
add_action('add_meta_boxes', 'add_bestseller_rank_meta_box');

// 책 편집할 때 오른쪽에 뜨는 순위 입력칸을 만들어주는 롤백
function bestseller_rank_callback($post) {
  // 저장값 로드 (둘 중 하나라도 있으면 표시)
  $value = get_post_meta($post->ID, '_bestseller_rank', true);
  if ($value === '' || $value === null) {
    $value = get_post_meta($post->ID, 'bestseller_rank', true);
  }

  // 저장 시 검증용 nonce
  wp_nonce_field('bestseller_rank_meta_save', 'bestseller_rank_meta_nonce');

  echo '<label for="bestseller_rank">1부터 순위를 입력하세요</label>';
  echo '<input type="number" name="bestseller_rank" id="bestseller_rank" value="' . esc_attr($value) . '" min="1" style="width:100%; margin-top:8px;">';
}

// 베스트셀러 순위 메타박스 저장
function save_bestseller_rank($post_id) {
  // 자동저장/리비전 패스
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;

  // 권한 체크
  if (!current_user_can('edit_post', $post_id)) return;

  // nonce 검증
  if (!isset($_POST['bestseller_rank_meta_nonce']) || !wp_verify_nonce($_POST['bestseller_rank_meta_nonce'], 'bestseller_rank_meta_save')) {
    return;
  }

  if (!array_key_exists('bestseller_rank', $_POST)) return;

  $rank = (int) $_POST['bestseller_rank'];
  $store = $rank > 0 ? $rank : '';

  // ACF/쿼리 호환을 위해 두 키 모두 업데이트
  update_post_meta($post_id, '_bestseller_rank', $store);
  update_post_meta($post_id, 'bestseller_rank',  $store);
}
add_action('save_post', 'save_bestseller_rank'); // 글이 저장될 때마다 위 로직 자동 실행


/*
 * 01. 스타일 로딩(페이지별)
 * - 파일 변경 감지(filemtime) 기반 버전
 * - 한 페이지에 하나의 메인 CSS만 로딩되도록 return으로 조기 종료
*/
function theme_enqueue_styles() {
  // 헤더/푸터는 항상
  wp_enqueue_style('header-style', get_template_directory_uri() . '/css/header.css', [], filemtime(get_template_directory() . '/css/header.css'));
  wp_enqueue_style('footer-style', get_template_directory_uri() . '/css/footer.css', [], filemtime(get_template_directory() . '/css/footer.css'));

  // cart: 페이지 템플릿/슬러그 모두 대응
  $is_cart = is_page_template('cart.php');
  if (!$is_cart) {
    $cart_page = get_page_by_path('cart');
    if ($cart_page) { $is_cart = is_page($cart_page->ID); }
  }
  if ($is_cart) {
    wp_enqueue_style('cart-style', get_template_directory_uri() . '/css/cart.css', [], filemtime(get_template_directory() . '/css/cart.css'));
    return;
  }

  // 단일 도서 상세
  if (is_singular('book')) {
    wp_enqueue_style('single-book-style', get_template_directory_uri() . '/css/single-book.css', [], filemtime(get_template_directory() . '/css/single-book.css'));
    return;
  }

  // 메인/블로그 목록
  if (is_front_page() || is_home()) {
    wp_enqueue_style('main-style', get_template_directory_uri() . '/css/main.css', [], filemtime(get_template_directory() . '/css/main.css'));
    return;
  }

  // 카테고리: 베스트셀러/신규
  if (is_category('bestseller') || is_page_template('category-bestseller.php')) {
    wp_enqueue_style('bestseller-style', get_template_directory_uri() . '/css/bestseller.css', [], filemtime(get_template_directory() . '/css/bestseller.css'));
    return;
  }
  if (is_category('new') || is_page_template('category-new.php')) {
    wp_enqueue_style('new-style', get_template_directory_uri() . '/css/new.css', [], filemtime(get_template_directory() . '/css/new.css'));
    return;
  }

  // 주문 플로우
  if (is_page('order')) {
    wp_enqueue_style('order-style', get_template_directory_uri() . '/css/order.css', [], filemtime(get_template_directory() . '/css/order.css'));
    return;
  }

  if (is_page('order-complete')) {
    wp_enqueue_style('order-complete-style', get_template_directory_uri() . '/css/order-complete.css', [], filemtime(get_template_directory() . '/css/order-complete.css'));
    return;
  }

  if (is_page('my-order')) {
    wp_enqueue_style('my-order-style', get_template_directory_uri() . '/css/my-order.css', [], filemtime(get_template_directory() . '/css/my-order.css'));
    return;
  }

  // 기본 (메인)
  wp_enqueue_style('main-style', get_template_directory_uri() . '/css/main.css', [], filemtime(get_template_directory() . '/css/main.css'));
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles', 20);


/*
 * 02. 세션 & 공통 유틸
 * - WordPress는 기본적으로 세션을 안 쓰므로 필요한 경우 직접 start
 */
add_action('init', function () {
  if (!session_id()) { session_start(); }
}, 1);

function cart_session_commit() {
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }
}


/*
 * 03. 커스텀 포스트 타입(CPT) — 주문
 * - public=false: 프론트 노출 X, 관리자 UI로만 관리
 */
add_action('init', function () {
  if (post_type_exists('shop_order')) return;
  register_post_type('shop_order', [
    'label'    => '주문',
    'public'   => false,
    'show_ui'  => true,
    'supports' => ['title', 'custom-fields'],
    'menu_icon'=> 'dashicons-cart',
  ]);
});


/*
 * 04. 라우팅/템플릿 매핑 & URL 헬퍼
*/
// 페이지 슬러그 → 템플릿 파일 매핑
add_filter('template_include', function ($template) {
  $map = [
    'cart'           => 'cart.php',
    'order'          => 'order.php',
    'order-complete' => 'order-complete.php',
    'my-order'       => 'my-order.php',
  ];
  foreach ($map as $slug => $file) {
    if (is_page($slug)) {
      $custom = locate_template($file, false, false);
      if (!empty($custom) && file_exists($custom)) {
        return $custom;
      }
    }
  }
  return $template;
}, 20);

// URL 헬퍼 (페이지가 없을 경우 대비)
function get_cart_url() {
  $candidates = ['cart', '장바구니'];
  foreach ($candidates as $slug) {
    $p = get_page_by_path($slug);
    if ($p) return get_permalink($p->ID);
  }
  $pages = get_pages([
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'cart.php',
    'number'     => 1,
  ]);
  if (!empty($pages)) return get_permalink($pages[0]->ID);
  return home_url('/cart/');
}

function get_order_url() {
  $candidates = ['order', '주문서'];
  foreach ($candidates as $slug) {
    $p = get_page_by_path($slug);
    if ($p) return get_permalink($p->ID);
  }
  $pages = get_pages([
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'order.php',
    'number'     => 1,
  ]);
  if (!empty($pages)) return get_permalink($pages[0]->ID);
  return home_url('/order/');
}


/*
 * 05. 장바구니(세션/유틸/액션)
 * - 세션 구조: $_SESSION['cart'] = [ book_id => qty, ... ]
*/
// (세션) 장바구니 get/set/count
function book_cart_get() {
  return (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
}
function book_cart_set($cart) { $_SESSION['cart'] = $cart; }
function book_cart_count() { return array_sum(book_cart_get()); }

// 가격 계산(ACF 필드: price, discount_price|sale_price)
function book_cart_price($book_id) {
  $price = get_field('price', $book_id);
  $sale  = get_field('discount_price', $book_id);
  if ($sale === '' || $sale === null) {
    $sale = get_field('sale_price', $book_id);
  }
  $price = (int) preg_replace('/[^\d]/', '', (string) $price);
  $sale  = (int) preg_replace('/[^\d]/', '', (string) $sale);
  if ($price <= 0) return 0;
  return ($sale > 0 && $sale < $price) ? $sale : $price;
}

// 상품/수량 검증
function book_cart_validate($book_id, $qty) {
  $book_id = (int) $book_id;
  $qty     = max(1, min(99, (int) $qty));
  if ($book_id <= 0 || get_post_type($book_id) !== 'book' || get_post_status($book_id) !== 'publish') {
    wp_die('Invalid product.');
  }
  return [$book_id, $qty];
}

// 액션: 담기
function book_cart_add_action() {
  check_admin_referer('book_cart', 'cart_nonce');
  [$book_id, $qty] = book_cart_validate($_REQUEST['book_id'] ?? 0, $_REQUEST['qty'] ?? 1);
  $cart = book_cart_get();
  if (!isset($cart[$book_id])) { $cart[$book_id] = 0; }
  $cart[$book_id] = max(1, min(99, $cart[$book_id] + $qty));
  book_cart_set($cart);
  cart_session_commit();
  wp_safe_redirect(get_cart_url());
  exit;
}
add_action('admin_post_nopriv_book_cart_add', 'book_cart_add_action');
add_action('admin_post_book_cart_add', 'book_cart_add_action');

// 액션: 수량 변경/삭제(0)
function book_cart_update_action() {
  check_admin_referer('book_cart', 'cart_nonce');
  $book_id = (int) ($_POST['book_id'] ?? 0);
  $qty_raw = (int) ($_POST['qty'] ?? 1);
  if ($book_id <= 0 || get_post_type($book_id) !== 'book' || get_post_status($book_id) !== 'publish') {
    wp_die('Invalid product.');
  }
  $cart = book_cart_get();
  if ($qty_raw <= 0) {
    unset($cart[$book_id]);
  } else {
    $cart[$book_id] = max(1, min(99, $qty_raw));
  }
  book_cart_set($cart);
  cart_session_commit();
  wp_safe_redirect(get_cart_url());
  exit;
}
add_action('admin_post_nopriv_book_cart_update', 'book_cart_update_action');
add_action('admin_post_book_cart_update', 'book_cart_update_action');

// 액션: 항목 제거
function book_cart_remove_action() {
  check_admin_referer('book_cart', 'cart_nonce');
  $book_id = (int) ($_GET['book_id'] ?? 0);
  $cart = book_cart_get();
  unset($cart[$book_id]);
  book_cart_set($cart);
  cart_session_commit();
  wp_safe_redirect(get_cart_url());
  exit;
}
add_action('admin_post_nopriv_book_cart_remove', 'book_cart_remove_action');
add_action('admin_post_book_cart_remove', 'book_cart_remove_action');

// 액션: 장바구니 비우기
function book_cart_empty_action() {
  check_admin_referer('book_cart', 'cart_nonce');
  book_cart_set([]);
  cart_session_commit();
  wp_safe_redirect(get_cart_url());
  exit;
}
add_action('admin_post_nopriv_book_cart_empty', 'book_cart_empty_action');
add_action('admin_post_book_cart_empty', 'book_cart_empty_action');


/*
 * 06. 주문 이동(선택/전체/바로구매) — 세션 order_items
 * - 세션 구조: $_SESSION['order_items'] = [ book_id => qty, ... ]
 */
// 세션 order_items
function order_items_get() {
  return (isset($_SESSION['order_items']) && is_array($_SESSION['order_items'])) ? $_SESSION['order_items'] : [];
}
function order_items_set($items) { $_SESSION['order_items'] = $items; }
function order_items_clear() { unset($_SESSION['order_items']); }

// 선택주문 → /order
function cart_order_selected_action() {
  check_admin_referer('cart_order', 'order_nonce');
  $selected = isset($_POST['selected']) ? array_map('intval', (array)$_POST['selected']) : [];
  $cart = book_cart_get();
  $items = [];
  foreach ($selected as $bid) {
    if (isset($cart[$bid])) { $items[$bid] = (int)$cart[$bid]; }
  }
  if (empty($items)) { wp_safe_redirect(get_cart_url()); exit; }
  order_items_set($items);
  cart_session_commit();
  wp_safe_redirect(get_order_url());
  exit;
}
add_action('admin_post_nopriv_cart_order_selected', 'cart_order_selected_action');
add_action('admin_post_cart_order_selected', 'cart_order_selected_action');

// 전체주문 → /order
function cart_order_all_action() {
  check_admin_referer('cart_order_all', 'order_all_nonce');
  $cart = book_cart_get();
  if (empty($cart)) { wp_safe_redirect(get_cart_url()); exit; }
  order_items_set($cart);
  cart_session_commit();
  wp_safe_redirect(get_order_url());
  exit;
}
add_action('admin_post_nopriv_cart_order_all', 'cart_order_all_action');
add_action('admin_post_cart_order_all', 'cart_order_all_action');

// 바로구매 → /order
function book_cart_buy_now_action() {
  check_admin_referer('book_cart', 'cart_nonce');
  [$book_id, $qty] = book_cart_validate($_REQUEST['book_id'] ?? 0, $_REQUEST['qty'] ?? 1);
  order_items_set([$book_id => (int)$qty]);
  cart_session_commit();
  wp_safe_redirect(get_order_url());
  exit;
}
add_action('admin_post_nopriv_book_cart_buy_now', 'book_cart_buy_now_action');
add_action('admin_post_book_cart_buy_now', 'book_cart_buy_now_action');


/*
 * 07. 주문 생성/조회 헬퍼
 * - shop_order 생성/메타 저장
 * - 세션 last_order_id, my_orders[] 관리
 */
// 주문 생성: shop_order + 메타 저장 + 세션 기록
if (!function_exists('shop_order_create')) {
  function shop_order_create($args) {
    $defaults = [
      'customer' => [
        'name' => '', 'email' => '', 'phone' => '',
        'postcode' => '', 'road' => '', 'jibun' => '',
        'extra' => '', 'detail' => '', 'memo' => '',
      ],
      'items'   => [], // [book_id => qty]
      'amounts' => [
        'subtotal'       => 0,
        'discount_total' => 0, // ← 통일
        'shipping'       => 0,
        'coupon'         => 0,
        'points'         => 0,
        'total'          => 0,
      ],
    ];
    $data = wp_parse_args($args, $defaults);

    $order_id = wp_insert_post([
      'post_title'  => '주문 ' . date_i18n('Ymd-His', current_time('timestamp')),
      'post_type'   => 'shop_order',
      'post_status' => 'publish',
      'post_date'   => current_time('mysql'),
    ]);
    if (is_wp_error($order_id) || !$order_id) return 0;

    add_post_meta($order_id, '_order_customer', $data['customer']);
    add_post_meta($order_id, '_order_items',    $data['items']);
    add_post_meta($order_id, '_order_amounts',  $data['amounts']);

    if (!session_id()) { session_start(); }
    $_SESSION['last_order_id'] = $order_id;
    if (!isset($_SESSION['my_orders']) || !is_array($_SESSION['my_orders'])) {
      $_SESSION['my_orders'] = [];
    }
    array_unshift($_SESSION['my_orders'], $order_id);

    return $order_id;
  }
}

// 금액 포맷
if (!function_exists('fmt_price')) {
  function fmt_price($amount) {
    return number_format((int)$amount) . '원';
  }
}

// 주문 메타 번들 조회(과거 discount → discount_total 보정 포함)
if (!function_exists('shop_order_get_meta')) {
  function shop_order_get_meta($order_id) {
    $customer = get_post_meta($order_id, '_order_customer', true);
    $items    = get_post_meta($order_id, '_order_items', true);
    $amounts  = get_post_meta($order_id, '_order_amounts', true);

    // 보정: 과거 키명 'discount'를 'discount_total'로 흡수
    if (is_array($amounts)) {
      if (!isset($amounts['discount_total']) && isset($amounts['discount'])) {
        $amounts['discount_total'] = (int)$amounts['discount'];
      }
    }

    return [
      'customer' => is_array($customer) ? $customer : [],
      'items'    => is_array($items) ? $items : [],
      'amounts'  => is_array($amounts) ? $amounts : [],
    ];
  }
}

// 세션 기반 '나의 주문' 목록(최신순)
if (!function_exists('my_orders_get')) {
  function my_orders_get() {
    if (!session_id()) { session_start(); }
    $ids = isset($_SESSION['my_orders']) && is_array($_SESSION['my_orders'])
      ? array_values(array_unique(array_map('intval', $_SESSION['my_orders'])))
      : [];
    rsort($ids); // 최신 먼저
    return $ids;
  }
}


/*
 * 08. 주문 검색(책 제목) — my-order 페이지에서 사용
 * - 최근 $limit건 shop_order 훑어, _order_items의 book_id 제목 부분 일치 검사
 * - 대량 주문 환경에서는 인덱싱(별도 테이블) 고려
*/
// 책 제목으로 주문 검색 (부분 일치)
if (!function_exists('shop_orders_find_by_book_title')) {
  function shop_orders_find_by_book_title($keyword, $limit = 200) {
    $keyword = trim((string)$keyword);
    if ($keyword === '') return [];

    $q = new WP_Query([
      'post_type'      => 'shop_order',
      'posts_per_page' => $limit,
      'orderby'        => 'date',
      'order'          => 'DESC',
      'no_found_rows'  => true,
      'fields'         => 'ids',
      'meta_query'     => [
        [
          'key'     => '_order_items',
          'compare' => 'EXISTS',
        ],
      ],
    ]);

    $hits = [];
    if (!empty($q->posts)) {
      foreach ($q->posts as $oid) {
        $items = get_post_meta($oid, '_order_items', true);
        if (!is_array($items) || empty($items)) continue;

        foreach ($items as $book_id => $row) {
          $title = get_the_title((int)$book_id);
          if ($title && stripos($title, $keyword) !== false) {
            $hits[] = (int)$oid;
            break; // 이 주문은 매칭 됨 → 다음 주문으로
          }
        }
      }
    }
    $hits = array_values(array_unique(array_map('intval', $hits)));
    rsort($hits); // 최신 먼저
    return $hits;
  }
}
