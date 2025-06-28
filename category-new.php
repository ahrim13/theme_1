<?php get_header(); ?>

<section class="book-section">
  <h2 class="section-title">
    카테고리: <?php single_cat_title(); ?>
  </h2>
  <div class="book-grid">
    <?php if (have_posts()) : ?>
      <?php while (have_posts()) : the_post(); ?>
        <article class="book-card">
          <?php if (has_post_thumbnail()) : ?>
            <div class="book-thumb"><?php the_post_thumbnail('medium'); ?></div>
          <?php endif; ?>
          <h3 class="book-title"><?php the_title(); ?></h3>
          <p class="book-meta"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
        </article>
      <?php endwhile; ?>
    <?php else : ?>
      <p>해당 카테고리에 글이 없습니다.</p>
    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>