<?php get_header(); ?>

<section class="book-section">
  <h2 class="section-title">📚 에디터의 선택</h2>
  <p class="section-desc">이 달의 추천 도서!</p>

<?php
$bestseller_id = get_category_by_slug('bestseller')->term_id;
$new_id = get_category_by_slug('new')->term_id;
$discount_id = get_category_by_slug('discount')->term_id;

$args = [
  'post_type' => 'post',
  'posts_per_page' => -1,
  'category__not_in' => [$bestseller_id, $new_id, $discount_id]
];
$loop = new WP_Query($args);
?>

    <div class="book-grid">
    <?php if ($loop->have_posts()) : ?>
      <?php while ($loop->have_posts()) : $loop->the_post(); ?>
        <?php
        $blocks = parse_blocks(get_the_content());
        $paragraph = '';
        $quote = '';

        foreach ($blocks as $block) {
          if ($block['blockName'] === 'core/paragraph' && $paragraph === '') {
            $paragraph = render_block($block); 
          }
          if ($block['blockName'] === 'core/quote' && $quote === '') {
            $quote = render_block($block);
          }
        }
        ?>

        <article class="book-card">
          <div class="book-inner">
            <div class="book-front">
              <?php if (has_post_thumbnail()) : ?>
                <div class="book-thumb"><?php the_post_thumbnail('medium'); ?></div>
              <?php endif; ?>
              <h3 class="book-title"><?php the_title(); ?></h3>
              <div class="book-meta">
                <?php echo $paragraph; ?>
              </div>
            </div>
            <div class="book-back">
              <?php echo $quote; ?>
            </div>
          </div>
        </article>
      <?php endwhile; ?>
    <?php else : ?>
      <p>글 없음</p>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>
  </div>

</section>

<?php get_footer(); ?>
