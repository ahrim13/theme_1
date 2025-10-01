<?php get_header(); ?>

<section class="book-detail-page">
  <div class="container">
    <div class="book-detail-container">

      <!-- 왼쪽: 표지 -->
      <div class="book-detail-left">
        <?php if (has_post_thumbnail()) : ?>
          <div class="book-cover"><?php the_post_thumbnail('large'); ?></div>
        <?php endif; ?>
      </div>

      <!-- 가운데: 책 정보 -->
      <div class="book-detail-right">
        <?php
          $author       = get_field('author_name') ?: '';
          $publisher    = get_field('publisher') ?: '';
          $publish_date = get_field('publish_date') ?: '';
          $price_raw    = get_field('price');
          $sale_raw     = get_field('sale_price');

          $price = is_numeric($price_raw) ? (int) $price_raw : 0;
          $sale  = is_numeric($sale_raw)  ? (int) $sale_raw  : 0;
          $percent = ($price > 0 && $sale > 0 && $sale < $price)
            ? round((($price - $sale) / $price) * 100)
            : 0;
        ?>

        <h1 class="book-title"><?php the_title(); ?></h1>

        <div class="book-subinfo">
          <?php if ($author): ?><span class="author"><?php echo esc_html($author); ?> 저자</span><?php endif; ?>
          <?php if ($author && $publisher): ?> · <?php endif; ?>
          <?php if ($publisher): ?><span class="publisher"><?php echo esc_html($publisher); ?></span><?php endif; ?>
          <?php if (($author || $publisher) && $publish_date): ?> · <?php endif; ?>
          <?php if ($publish_date): ?><span class="pub-date"><?php echo esc_html($publish_date); ?></span><?php endif; ?>
        </div>

        <div class="price-box">
          <?php if ($sale > 0 && $price > 0 && $sale < $price): ?>
            <div class="sale-percent"><?php echo esc_html($percent); ?>%</div>
            <div class="sale-price"><?php echo number_format($sale); ?>원</div>
            <div class="original-price"><del><?php echo number_format($price); ?>원</del></div>
          <?php elseif ($price > 0): ?>
            <div class="regular-price"><?php echo number_format($price); ?>원</div>
          <?php endif; ?>
        </div>

        <div class="extra-info">
          <p>도서 포함 15,000원 이상 무료배송</p>
          <p><strong>적립</strong>: 750P</p>
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
              <?php 
                $buy_url = wp_nonce_url(
                  admin_url('admin-post.php?action=book_cart_buy_now&book_id=' . get_the_ID() . '&qty=1'),
                  'book_cart',
                  'cart_nonce'
                );
              ?>
          
          <a href="<?php echo esc_url($buy_url); ?>" class="btn-buy">바로구매</a>
        </div>
      </div>

      <aside class="book-detail-aside">
        <h3 class="aside-title">종합 베스트</h3>
        <ol class="rank-list">
          <?php
            $current_id = get_the_ID();

            $rank_q = new WP_Query([
              'post_type'      => 'book',
              'posts_per_page' => 10,
              'tax_query'      => [[
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => ['bestseller'],
              ]],
              'meta_key'       => 'bestseller_rank',    
              'orderby'        => 'meta_value_num',
              'order'          => 'ASC',
              'meta_type'      => 'NUMERIC',           
              'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => 'bestseller_rank', 'compare' => 'EXISTS' ],
                [ 'key' => 'bestseller_rank', 'value' => '', 'compare' => '!=' ],
              ],
            ]);

            if ($rank_q->have_posts()):
              while ($rank_q->have_posts()): $rank_q->the_post();
                $rank_val = get_field('bestseller_rank');
                // $rank_val = get_post_meta(get_the_ID(), 'bestseller_rank', true);

                if ($rank_val === '' || $rank_val === null) { continue; }
                $rank_val = (int) $rank_val; 
                $active   = (get_the_ID() === $current_id) ? ' active' : '';
          ?>
            <li class="rank-item<?php echo $active; ?>">
              <span class="rank-badge"><?php echo esc_html($rank_val); ?></span>
              <a href="<?php the_permalink(); ?>" class="rank-title"><?php the_title(); ?></a>
            </li>
          <?php
              endwhile;
              wp_reset_postdata();
            else:
          ?>
            <li class="rank-empty">베스트셀러가 없습니다.</li>
          <?php endif; ?>
        </ol>
      </aside>

    </div>
    <?php
      $intro_img = get_field('intro_image'); // 소개 이미지
      $summary   = get_field('summary');     // 줄거리(소개)
      $one_liner = get_field('one_liner');   // 한줄평
    ?>

    <?php if ($intro_img || $summary || $one_liner): ?>
    <section class="book-detail-bottom">

      <?php if ($intro_img): ?>
        <div class="intro-media">
          <?php
            $intro_url = is_array($intro_img) ? $intro_img['url'] : $intro_img;
            $intro_alt = is_array($intro_img) && !empty($intro_img['alt'])
              ? $intro_img['alt'] : (get_the_title() . ' 소개 이미지');
          ?>
          <img src="<?php echo esc_url($intro_url); ?>"
              alt="<?php echo esc_attr($intro_alt); ?>" loading="lazy" decoding="async">
        </div>

        <div class="intro-divider" aria-hidden="true"></div>
      <?php endif; ?>



        <?php if ($summary): ?>
          <h2 class="intro-heading">책 소개</h2>
          <div class="summary-content">
            <?php
              $summary_field_type = 'textarea'; 

              if ($summary_field_type === 'wysiwyg') {
                echo apply_filters('the_content', $summary);
              } else {
                echo nl2br(esc_html($summary));
              }
            ?>
          </div>
        <?php endif; ?>

        <?php if ($one_liner): ?>
          <h2 class="intro-heading">출판사 서평</h2>
          <blockquote class="one-liner">
            “<?php echo nl2br(esc_html($one_liner)); ?>”
          </blockquote>
        <?php endif; ?>

      </div>
    </div> 
  </section>
  <?php endif; ?>

</section><!-- /.book-detail-page -->


<?php get_footer(); ?>
