<?php
/**
 * Template Name: 신상품
 */
get_header();
?>

<section class="newbooks-hero">
  <div class="hero-inner">
    <h1>📕 따끈따끈한 신간 도서</h1>
    <p>지금 막 도착한 감성 가득한 책들을 만나보세요.</p>
    <a href="#newbook-list" class="btn-hero">지금 확인하기</a>
  </div>
</section>

<?php
// 추천 신간 1권
$highlight = new WP_Query([
  'post_type' => 'post',
  'posts_per_page' => 1,
  'category_name' => 'new',
  'orderby' => 'date',
  'order' => 'DESC',
]);
if ($highlight->have_posts()) :
  while ($highlight->have_posts()) : $highlight->the_post(); ?>
    <section class="highlighted-newbook">
      <div class="highlighted-wrapper">
        <div class="highlighted-left">
          <div class="highlighted-badge">🔥 추천 신간</div>
          <?php the_post_thumbnail('large'); ?>
        </div>
        <div class="highlighted-right">
          <h2 class="highlighted-title"><?php the_title(); ?></h2>
          <?php
            $meta = [
              get_field('author_name'),
              get_field('publisher'),
              get_field('publish_date')
            ];
            foreach ($meta as $m) {
              if ($m) echo '<p class="meta">' . esc_html($m) . '</p>';
            }
          ?>
          <p class="highlighted-desc"><?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?></p>
          <a href="<?php the_permalink(); ?>" class="btn-highlighted">📘 자세히 보기</a>
        </div>
        <div class="book-info-block">
          <?php if (get_field('book_intro')): ?>
            <blockquote class="book_intro"><?php echo nl2br(get_field('book_intro')); ?></blockquote>
          <?php endif; ?>
          <?php if (get_field('author_comment')): ?>
            <blockquote class="author_comment"><?php echo nl2br(get_field('author_comment')); ?></blockquote>
          <?php endif; ?>
          <?php if (get_field('highlight_quote')): ?>
            <blockquote class="highlighted-quote">“<?php echo nl2br(get_field('highlight_quote')); ?>”</blockquote>
          <?php endif; ?>
        </div>
      </div>
    </section>
<?php
  endwhile; wp_reset_postdata();
endif;
?>

<section id="newbook-list" class="newbooks-grid-section">
  <h3 class="grid-title">📚 더 많은 신상품</h3>
  <div class="newbooks-grid">
    <?php
    // 두 번째 포스트부터 8개
    $query = new WP_Query([
      'post_type' => 'post',
      'posts_per_page' => 8,
      'offset' => 1,
      'category_name' => 'new',
      'orderby' => 'date',
      'order' => 'DESC',
    ]);
    if ($query->have_posts()) :
      while ($query->have_posts()) : $query->the_post(); ?>
        <div class="newbook-card">
          <div class="newbook-thumb">
            <div class="new-badge">NEW</div>
            <?php the_post_thumbnail('medium'); ?>
          </div>
          <div class="newbook-info">
            <h4><?php the_title(); ?></h4>
            <?php
              $meta = [
                get_field('author_name'),
                get_field('publisher'),
                get_field('publish_date')
              ];
              foreach ($meta as $m) {
                if ($m) echo '<p class="meta">' . esc_html($m) . '</p>';
              }
            ?>
            <a href="<?php the_permalink(); ?>" class="read-more">→</a>
          </div>
        </div>
      <?php endwhile;
      wp_reset_postdata();
    endif;
    ?>
  </div>
</section>

<?php get_footer(); ?>
