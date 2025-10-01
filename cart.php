<?php
/**
 * Template Name: Cart
 */
get_header();

$cart = book_cart_get();
$total = 0;
?>

<section class="cart-page">
  <div class="container">
    <h1>장바구니</h1>

    <?php if (empty($cart)) : ?>
      <p>장바구니가 비어 있습니다.</p>
    <?php else : ?>
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
          <?php foreach ($cart as $book_id => $qty) :
            $title = get_the_title($book_id);
            $per   = book_cart_price($book_id);
            $line  = $per * $qty;
            $total += $line;

            $thumb = get_the_post_thumbnail($book_id, 'medium', [
              'alt'   => $title,
              'class' => 'cart-thumb'
            ]);

            $remove_url = wp_nonce_url(
              admin_url('admin-post.php?action=book_cart_remove&book_id=' . (int)$book_id),
              'book_cart',
              'cart_nonce'
            );
          ?>
            <tr>
              <td class="td-check">
                <input type="checkbox" class="row-check"
                  name="selected[]"
                  value="<?php echo (int)$book_id; ?>"
                  form="cart-select-form" />
              </td>

              <td class="cart-item">
                <a href="<?php echo get_permalink($book_id); ?>" class="thumb-wrap"><?php echo $thumb ?: ''; ?></a>
                <div class="cart-item-meta">
                  <a class="cart-item-title" href="<?php echo get_permalink($book_id); ?>"><?php echo esc_html($title); ?></a>
                  <div class="unit-price"><?php echo number_format($per); ?>원</div>
                </div>
                <a href="<?php echo esc_url($remove_url); ?>" class="item-remove" aria-label="삭제">×</a>
              </td>

              <td class="td-qty">
                <div class="qty-controls">
                  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="qty-form">
                    <input type="hidden" name="action" value="book_cart_update">
                    <?php wp_nonce_field('book_cart', 'cart_nonce'); ?>
                    <input type="hidden" name="book_id" value="<?php echo (int)$book_id; ?>">
                    <input type="hidden" name="qty" value="<?php echo max(0, (int)$qty - 1); ?>">
                    <button type="submit" class="btn-qty btn-qty-dec" aria-label="수량 감소">−</button>
                  </form>

                  <span class="qty-number" aria-live="polite"><?php echo (int)$qty; ?></span>

                  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="qty-form">
                    <input type="hidden" name="action" value="book_cart_update">
                    <?php wp_nonce_field('book_cart', 'cart_nonce'); ?>
                    <input type="hidden" name="book_id" value="<?php echo (int)$book_id; ?>">
                    <input type="hidden" name="qty" value="<?php echo min(99, (int)$qty + 1); ?>">
                    <button type="submit" class="btn-qty btn-qty-inc" aria-label="수량 증가">+</button>
                  </form>
                </div>
              </td>

              <td class="td-subtotal">
                <span class="subtotal-amount"><?php echo number_format($line); ?>원</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="cart-summary">
        <div class="summary-right">
          <div class="total-row">
            <span class="total-label">합계</span>
            <strong class="total-amount"><?php echo number_format($total); ?>원</strong>
          </div>

          <div class="cart-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
            <form id="cart-select-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <input type="hidden" name="action" value="cart_order_selected">
              <?php wp_nonce_field('cart_order', 'order_nonce'); ?>
              <button type="submit" class="btn-checkout">선택주문</button>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <input type="hidden" name="action" value="cart_order_all">
              <?php wp_nonce_field('cart_order_all', 'order_all_nonce'); ?>
              <button type="submit" class="btn-checkout">전체주문</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
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
