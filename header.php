<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php bloginfo('name'); ?></title>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
  <div class="container">
    <h1 class="logo">
      <a href="<?php echo home_url('/'); ?>">
        <span class="pink">DREAM</span> BOOKS
      </a>
    </h1>
    <nav class="main-nav">
      <ul>
        <li><a href="<?php echo home_url('/'); ?>">에디터의 선택</a></li>
        <li><a href="<?php echo get_category_link(get_category_by_slug('bestseller')->term_id); ?>">베스트셀러</a></li>
        <li><a href="<?php echo get_category_link(get_category_by_slug('new')->term_id); ?>">신상품</a></li>
        <li><a href="<?php echo get_category_link(get_category_by_slug('discount')->term_id); ?>">이 달의 특가</a></li>
      </ul>
    </nav>
  </div>
</header>



