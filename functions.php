<?php
function theme_enqueue_styles() {
  wp_enqueue_style('header-style', get_template_directory_uri() . '/css/header.css', [], filemtime(get_template_directory() . '/css/header.css'));
  wp_enqueue_style('footer-style', get_template_directory_uri() . '/css/footer.css', [], filemtime(get_template_directory() . '/css/footer.css'));

  $is_cart = is_page_template('cart.php');
  if (!$is_cart) {
    $cart_page = get_page_by_path('cart');
    if ($cart_page) { $is_cart = is_page($cart_page->ID); }
  }
  if ($is_cart) {
    wp_enqueue_style('cart-style', get_template_directory_uri() . '/css/cart.css', [], filemtime(get_template_directory() . '/css/cart.css'));
    return;
  }

  if (is_singular('book')) {
    wp_enqueue_style('single-book-style', get_template_directory_uri() . '/css/single-book.css', [], filemtime(get_template_directory() . '/css/single-book.css'));
    return;
  }

  if (is_front_page() || is_home()) {
    wp_enqueue_style('main-style', get_template_directory_uri() . '/css/main.css', [], filemtime(get_template_directory() . '/css/main.css'));
    return;
  }

  if (is_category('bestseller') || is_page_template('category-bestseller.php')) {
    wp_enqueue_style('bestseller-style', get_template_directory_uri() . '/css/bestseller.css', [], filemtime(get_template_directory() . '/css/bestseller.css'));
    return;
  }

  if (is_category('new') || is_page_template('category-new.php')) {
    wp_enqueue_style('new-style', get_template_directory_uri() . '/css/new.css', [], filemtime(get_template_directory() . '/css/new.css'));
    return;
  }

  if (is_page('order')) {
    wp_enqueue_style('order-style', get_template_directory_uri() . '/css/order.css', [], filemtime(get_template_directory() . '/css/order.css'));
    return;
  }

  if (is_page('order-complete')) {
    wp_enqueue_style('order-complete-style', get_template_directory_uri() . '/css/order-complete.css', [], filemtime(get_template_directory() . '/css/order-complete.css'));
    return;
  }

  wp_enqueue_style('main-style', get_template_directory_uri() . '/css/main.css', [], filemtime(get_template_directory() . '/css/main.css'));
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles', 20);

add_theme_support('post-thumbnails');

function arim_add_bestseller_rank_meta_box() {
  add_meta_box(
    'bestseller_rank_meta',
    '베스트셀러 순위',
    'arim_bestseller_rank_callback',
    ['book','post'],
    'side',
    'default'
  );
}
add_action('add_meta_boxes', 'arim_add_bestseller_rank_meta_box');

function arim_bestseller_rank_callback($post) {
  $value = get_post_meta($post->ID, '_bestseller_rank', true);
  echo '<label for="bestseller_rank">1부터 순위를 입력하세요</label>';
  echo '<input type="number" name="bestseller_rank" id="bestseller_rank" value="' . esc_attr($value) . '" style="width:100%; margin-top:8px;">';
}

function arim_save_bestseller_rank($post_id) {
  if (array_key_exists('bestseller_rank', $_POST)) {
    update_post_meta($post_id, '_bestseller_rank', intval($_POST['bestseller_rank']));
  }
}
add_action('save_post', 'arim_save_bestseller_rank');

function register_book_post_type() {
  register_post_type('book', [
    'label' => '책',
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'rewrite' => ['slug' => 'book'],
    'taxonomies' => ['category', 'post_tag'],
  ]);
}
add_action('init', 'register_book_post_type');

add_action('init', function () {
  if (!session_id()) { session_start(); }
}, 1);

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
  if (!empty($pages)) {
    return get_permalink($pages[0]->ID);
  }
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
  if (!empty($pages)) {
    return get_permalink($pages[0]->ID);
  }
  return home_url('/order/');
}

function book_cart_get() {
  return (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
}
function book_cart_set($cart) { $_SESSION['cart'] = $cart; }

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

function book_cart_count() { return array_sum(book_cart_get()); }

function book_cart_validate($book_id, $qty) {
  $book_id = (int) $book_id;
  $qty     = max(1, min(99, (int) $qty));
  if ($book_id <= 0 || get_post_type($book_id) !== 'book' || get_post_status($book_id) !== 'publish') {
    wp_die('Invalid product.');
  }
  return [$book_id, $qty];
}

function cart_session_commit() {
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }
}

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
add_action('admin_post_book_cart_update', 'book_cart_update_action');
add_action('admin_post_nopriv_book_cart_update', 'book_cart_update_action');

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

function book_cart_empty_action() {
  check_admin_referer('book_cart', 'cart_nonce');
  book_cart_set([]);
  cart_session_commit();
  wp_safe_redirect(get_cart_url());
  exit;
}
add_action('admin_post_nopriv_book_cart_empty', 'book_cart_empty_action');
add_action('admin_post_book_cart_empty', 'book_cart_empty_action');

function order_items_get() {
  return (isset($_SESSION['order_items']) && is_array($_SESSION['order_items'])) ? $_SESSION['order_items'] : [];
}
function order_items_set($items) { $_SESSION['order_items'] = $items; }
function order_items_clear() { unset($_SESSION['order_items']); }

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


add_filter('template_include', function ($template) {
  $map = [
    'cart'           => 'cart.php',
    'order'          => 'order.php',
    'order-complete' => 'order-complete.php',
  ];
  foreach ($map as $slug => $file) {
    if (is_page($slug)) {
      $custom = get_stylesheet_directory() . '/' . $file;
      if (file_exists($custom)) return $custom;
    }
  }
  return $template;
}, 20);
