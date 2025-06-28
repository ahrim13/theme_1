<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-left">
      <h4 class="footer-logo">DREAM BOOKS</h4>
      <p>&copy; <?php echo date('Y'); ?> DREAM BOOKS. All rights reserved.</p>
    </div>

    <div class="footer-nav">
      <ul>
        <li><a href="<?php echo home_url('/'); ?>">홈</a></li>
        <li><a href="<?php echo get_category_link(get_category_by_slug('bestseller')->term_id); ?>">베스트셀러</a></li>
        <li><a href="<?php echo get_category_link(get_category_by_slug('new')->term_id); ?>">신상품</a></li>
        <li><a href="<?php echo get_category_link(get_category_by_slug('discount')->term_id); ?>">이 달의 특가</a></li>
      </ul>
    </div>

    <div class="footer-contact">
      <p>문의: contact@dreambooks.com</p>
      <p>주소: 서울시 @@구 @@로 123</p>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

