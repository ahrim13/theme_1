<?php get_header(); ?>

<section class="discount-page">
  <h2 class="section-title">💰 이달의 특가</h2>
  <p class="section-desc">이번 달에만 만나는 할인 도서!</p>

   <?php
  $discount_cat = get_category_by_slug('discount');
  $discount_id = $discount_cat ? $discount_cat->term_id : 0;

  $args = [
    'post_type' => 'post',
    'posts_per_page' => -1,
    'cat' => $discount_id
  ];
  $loop = new WP_Query($args);

  if ($loop->have_posts()) {
    echo '<div class="discount-list">';
    while ($loop->have_posts()) {
      $loop->the_post();
      $price = get_field('price');
      $sale = get_field('sale_price');

      if ($price && $sale && $price > 0) {
        $discount = round((($price - $sale) / $price) * 100);

        echo '<article class="discount-item">';
        echo get_the_post_thumbnail(get_the_ID(), 'medium', ['class' => 'discount-thumb']);
        echo '<div class="discount-info">';
        echo "<div class=\"discount-badge\">-{$discount}%</div>";
        echo '<h3 class="book-title">' . get_the_title() . '</h3>';
        echo '<div class="book-price">';
        echo '<span class="sale">' . number_format($sale) . '원</span>';
        echo '<span class="price">' . number_format($price) . '원</span>';
        echo '</div>';
        echo '<div class="book-excerpt">' . get_the_excerpt() . '</div>';
        echo '</div>';
        echo '</article>';
      }
    }
    echo '</div>';
    wp_reset_postdata();
  } else {
    echo '<p>등록된 특가 도서가 없습니다.</p>';
  }
  ?>
</section>

<?php get_footer(); ?>
