<?php get_header(); ?>

<section class="bestseller-page">
  <div class="container">
    <h2 class="section-title">🔥 베스트셀러</h2>
    <p class="section-desc">지금 가장 인기 있는 책들을 소개합니다!</p>

    <div class="bestseller-wrapper">

      <div class="bestseller-list">
        <?php
          $ranked_q = new WP_Query([
            'post_type'           => 'book',
            'posts_per_page'      => -1,
            'tax_query'           => [[
              'taxonomy' => 'category',
              'field'    => 'slug',
              'terms'    => ['bestseller'],
            ]],
            'meta_key'            => 'bestseller_rank',
            'orderby'             => 'meta_value_num',
            'order'               => 'ASC',
            'meta_type'           => 'NUMERIC',
            'meta_query'          => [
              'relation' => 'AND',
              [ 'key' => 'bestseller_rank', 'compare' => 'EXISTS' ],
              [ 'key' => 'bestseller_rank', 'value' => '', 'compare' => '!=' ],
            ],
            'ignore_sticky_posts' => 1,
            'no_found_rows'       => true,
          ]);

          $unranked_q = new WP_Query([
            'post_type'           => 'book',
            'posts_per_page'      => -1,
            'tax_query'           => [[
              'taxonomy' => 'category',
              'field'    => 'slug',
              'terms'    => ['bestseller'],
            ]],
            'orderby'             => 'date',
            'order'               => 'DESC',
            'meta_query'          => [
              'relation' => 'OR',
              [ 'key' => 'bestseller_rank', 'compare' => 'NOT EXISTS' ],
              [ 'key' => 'bestseller_rank', 'value' => '', 'compare' => '=' ],
            ],
            'ignore_sticky_posts' => 1,
            'no_found_rows'       => true,
          ]);

          $printed_any = false;

          function render_bestseller_card($rank_badge = null) {
            $author       = get_field('author_name') ?: '-';
            $publisher    = get_field('publisher') ?: '-';
            $publish_date = get_field('publish_date') ?: '-';
            $price_raw    = get_field('price');
            $sale_raw     = get_field('sale_price');
            $price        = is_numeric($price_raw) ? (int)$price_raw : null;
            $sale         = is_numeric($sale_raw)  ? (int)$sale_raw  : null;
            ?>
            <article class="bestseller-item">
              <div class="bestseller-thumb">
                <?php the_post_thumbnail('medium'); ?>
                <?php if ($rank_badge !== null): ?>
                  <div class="rank-badge"><?php echo esc_html($rank_badge); ?>위</div>
                <?php endif; ?>
              </div>
              <div class="bestseller-info">
                <div class="best-book-title">
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </div>
                <div class="best-book-meta">
                  <?php echo esc_html($author); ?> · <?php echo esc_html($publisher); ?> · <?php echo esc_html($publish_date); ?>
                </div>
                <div class="best-book-desc"><?php echo esc_html( wp_trim_words(get_the_excerpt(), 25) ); ?></div>
                <div class="book-price">
                  <?php if ($sale): ?>
                    <span class="sale"><?php echo number_format($sale); ?>원</span>
                  <?php endif; ?>
                  <?php if ($price): ?>
                    <span class="price"><?php echo number_format($price); ?>원</span>
                  <?php endif; ?>
                </div>
                <div class="book-actions">
                  <?php
                    $add_url = wp_nonce_url(
                      admin_url('admin-post.php?action=book_cart_add&book_id=' . get_the_ID() . '&qty=1'),
                      'book_cart',
                      'cart_nonce'
                    );
                  ?>
                  <a href="<?php echo esc_url($add_url); ?>" class="btn-cart">장바구니</a>
                  <a href="<?php the_permalink(); ?>" class="btn-buy">바로구매</a>
                </div>
              </div>
            </article>
            <?php
          }

          $max_rank_seen = 0; 
          if ($ranked_q->have_posts()):
            while ($ranked_q->have_posts()): $ranked_q->the_post();
              $rank_val = get_field('bestseller_rank');
              $rank_badge = (is_numeric($rank_val) && (int)$rank_val > 0) ? (int)$rank_val : null;
              if ($rank_badge !== null) {
                $max_rank_seen = max($max_rank_seen, $rank_badge);
              }
              render_bestseller_card($rank_badge);
              $printed_any = true;
            endwhile;
            wp_reset_postdata();
          endif;

          $virtual_rank = ($max_rank_seen > 0) ? $max_rank_seen + 1 : 1;
          if ($unranked_q->have_posts()):
            while ($unranked_q->have_posts()): $unranked_q->the_post();
              $rank_val = get_field('bestseller_rank');
              if (!empty($rank_val)) { continue; } // 안전
              render_bestseller_card($virtual_rank++);
              $printed_any = true;
            endwhile;
            wp_reset_postdata();
          endif;

          if (!$printed_any): ?>
            <p>베스트셀러가 없습니다.</p>
          <?php endif; ?>
      </div>

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
    </div>
  </div> 
</section>

<?php get_footer(); ?>
