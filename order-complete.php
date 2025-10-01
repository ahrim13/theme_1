<?php
/**
 * Template Name: Order Complete
 */
get_header();
?>
<section class="order-complete-page">
  <div class="container">
    <h1>🎉 주문이 완료되었습니다</h1>
    <p>감사합니다. 주문이 정상적으로 접수되었습니다.</p>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">홈으로</a>
  </div>
</section>
<?php get_footer(); ?>
