<?php get_header(); ?>

<section class="bestseller-page">
  <h2 class="section-title">🔥 베스트셀러</h2>
  <p class="section-desc">지금 가장 인기 있는 책들을 소개합니다!</p>

  <div class="bestseller-wrapper">
    <aside class="bestseller-sidebar">
      <h3>베스트셀러</h3>
      <ul class="sidebar-menu">
        <li><a href="#">종합 베스트</a></li>
        <li><a href="#">주간</a></li>
        <li><a href="#">월간</a></li>
        <li><a href="#">온라인 베스트</a></li>
        <li><a href="#">실시간 베스트</a></li>
        <li><a href="#">매장별 베스트</a></li>
      </ul>
    </aside>

    <div class="bestseller-list">
      <?php
        $args = [
          'post_type' => 'post',
          'posts_per_page' => -1,
          'category_name' => 'bestseller',
          'meta_key' => '_bestseller_rank',
          'orderby' => 'meta_value_num',
          'order' => 'ASC',
        ];
        $loop = new WP_Query($args);
        $rank = 1;
      ?>

      <?php if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post(); ?>
        <?php
          $author = get_field('author_name') ?: '-';
          $publisher = get_field('publisher') ?: '-';
          $publish_date = get_field('publish_date') ?: '-';
          $price = get_field('price');
          $sale = get_field('sale_price');
        ?>
        <article class="bestseller-item">
          <div class="bestseller-thumb">
            <?php the_post_thumbnail('medium'); ?>
            <div class="rank-badge"><?php echo $rank++; ?>위</div>
          </div>
          <div class="bestseller-info">
            <div class="best-book-title"><?php the_title(); ?></div>
            <div class="best-book-meta">
              <?php echo $author; ?> · <?php echo $publisher; ?> · <?php echo $publish_date; ?>
            </div>
            <div class="best-book-desc"><?php echo wp_trim_words(get_the_excerpt(), 25); ?></div>
            <div class="book-price">
              <?php if ($sale): ?>
                <span class="sale"><?php echo number_format($sale); ?>원</span>
              <?php endif; ?>
              <?php if ($price): ?>
                <span class="price"><?php echo number_format($price); ?>원</span>
              <?php endif; ?>
            </div>
            <div class="book-actions">
              <a href="#" class="btn-cart">장바구니</a>
              <a href="<?php the_permalink(); ?>" class="btn-buy">바로구매</a>
            </div>
          </div>
        </article>
      <?php endwhile; wp_reset_postdata(); else : ?>
        <p>베스트셀러가 없습니다.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
