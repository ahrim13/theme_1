<?php
function arim_theme_enqueue_styles() {
  wp_enqueue_style('header-style', get_template_directory_uri() . '/css/header.css');
  wp_enqueue_style('main-style', get_template_directory_uri() . '/css/main.css');
  wp_enqueue_style('footer-style', get_template_directory_uri() . '/css/footer.css');
}
add_action('wp_enqueue_scripts', 'arim_theme_enqueue_styles');
add_theme_support('post-thumbnails');

function arim_add_bestseller_rank_meta_box() {
  add_meta_box(
    'bestseller_rank_meta',
    '베스트셀러 순위',
    'arim_bestseller_rank_callback',
    'post',
    'side',
    'default'
  );
}
add_action('add_meta_boxes', 'arim_add_bestseller_rank_meta_box');

function arim_bestseller_rank_callback($post) {
  $value = get_post_meta($post->ID, '_bestseller_rank', true);
  echo '<label for="bestseller_rank">1부터 순위를 입력하세요</label>';
  echo '<input type="number" name="bestseller_rank" id="bestseller_rank" value="' . esc_attr($value) . '" style="width:100%; margin-top:8px;">';
}

function arim_save_bestseller_rank($post_id) {
  if (array_key_exists('bestseller_rank', $_POST)) {
    update_post_meta($post_id, '_bestseller_rank', intval($_POST['bestseller_rank']));
  }
}
add_action('save_post', 'arim_save_bestseller_rank');