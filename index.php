<?php get_header(); ?>

<section class="book-section">
  <h2 class="section-title">📚 에디터의 선택</h2>
  <p class="section-desc">이 달의 추천 도서!</p>

<?php
$categories = ['bestseller', 'new', 'discount'];
$exclude_ids = [];
foreach ($categories as $slug) {
  $cat = get_category_by_slug($slug);
  if ($cat) $exclude_ids[] = $cat->term_id;
}

$args = [
  'post_type' => 'post',
  'posts_per_page' => -1,
  'category__not_in' => $exclude_ids,
];
$loop = new WP_Query($args);
?>

  <div class="book-grid">
    <?php if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post(); 
      $blocks = parse_blocks(get_the_content());
      $paragraph = '';
      $quote = '';
      foreach ($blocks as $block) {
        if (empty($paragraph) && $block['blockName'] === 'core/paragraph') {
          $paragraph = render_block($block);
        }
        if (empty($quote) && $block['blockName'] === 'core/quote') {
          $quote = render_block($block);
        }
        if ($paragraph && $quote) break; 
      }
    ?>
      <article class="book-card">
        <div class="book-inner">
          <div class="book-front">
            <?php if (has_post_thumbnail()) : ?>
              <div class="book-thumb"><?php the_post_thumbnail('medium'); ?></div>
            <?php endif; ?>
            <h3 class="book-title"><?php the_title(); ?></h3>
            <div class="book-meta"><?php echo $paragraph; ?></div>
          </div>
          <div class="book-back"><?php echo $quote; ?></div>
        </div>
      </article>
    <?php endwhile; else : ?>
      <p>글 없음</p>
    <?php endif; wp_reset_postdata(); ?>
  </div>
</section>

<?php get_footer(); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
  if (window.innerWidth <= 768) {
    const cards = document.querySelectorAll(".book-card");
    cards.forEach(card => {
      const inner = card.querySelector(".book-inner");
      card.addEventListener("click", function (e) {
        e.stopPropagation();
        if (card.classList.contains("touched")) {
          card.classList.remove("touched");
          inner.classList.remove("touched");
        } else {
          document.querySelectorAll(".book-card.touched").forEach(other => {
            if (other !== card) {
              other.classList.remove("touched");
              other.querySelector(".book-inner").classList.remove("touched");
            }
          });
          card.classList.add("touched");
          inner.classList.add("touched");
        }
      });
    });
    document.body.addEventListener("click", function () {
      document.querySelectorAll(".book-card.touched").forEach(card => card.classList.remove("touched"));
      document.querySelectorAll(".book-inner.touched").forEach(inner => inner.classList.remove("touched"));
    });
  }
});
</script>
