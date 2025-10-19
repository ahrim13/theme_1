<?php get_header(); ?> 
<!-- 공통 헤더(header.php) 불러오기 -->

<section class="book-section">
  <!-- 메인 섹션 에디터의 선택 영역 -->
  <h2 class="section-title">📚 에디터의 선택</h2>
  <p class="section-desc">이 달의 추천 도서!</p>

<?php
// 1) 특정 카테고(bestseller/new/discount)를 제리외할 준비
//    이 세 카테고리에 속하지 않은 일반 글들만 뿌릴 목적
$categories = ['bestseller', 'new', 'discount']; // 제외할 카테고리 슬러그들
$exclude_ids = [];                                // 제외할 카테고리의 term_id를 담을 배열
foreach ($categories as $slug) {
  $cat = get_category_by_slug($slug);            // 슬러그로 카테고리 객체 찾기
  if ($cat) $exclude_ids[] = $cat->term_id;      // 있으면 term_id를 제외 목록에 추가
}

// 2) 메인 쿼리 설정
//    - post_type: 'post' (일반 글)
//    - posts_per_page: -1 (모든 글)  ※ 현재 구조 설명용
//    - category__not_in: 위에서 구한 제외 카테고리들
$args = [
  'post_type' => 'post',
  'posts_per_page' => -1,
  'category__not_in' => $exclude_ids,
];
$loop = new WP_Query($args); // 커스텀 루프 시작
?>

  <div class="book-grid">
    <?php if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post(); 
      // 3) 각 글의 본문을 Gutenberg 블록 단위로 파싱
      //    "첫 번째 문단"과 "첫 번째 인용"을 찾아 카드 앞/뒤면에 배치
      $blocks = parse_blocks(get_the_content()); // 현재 글의 콘텐츠를 블록 배열로 변환
      $paragraph = '';                           // 첫 문단 블록 HTML을 담을 변수
      $quote = '';                               // 첫 인용 블록 HTML을 담을 변수

      // 블록들을 순회하면서 원하는 타입을 처음 1개씩만 추출
      foreach ($blocks as $block) {
        // 첫 번째 문단(core/paragraph)을 찾으면 렌더링해서 저장
        if (empty($paragraph) && $block['blockName'] === 'core/paragraph') {
          $paragraph = render_block($block);     // 블록을 실제 HTML로 렌더
        }
        // 첫 번째 인용(core/quote)을 찾으면 렌더링해서 저장
        if (empty($quote) && $block['blockName'] === 'core/quote') {
          $quote = render_block($block);
        }
        // 둘 다 찾았으면 루프 종료
        if ($paragraph && $quote) break; 
      }
    ?>
      <!-- 4) 카드 컴포넌트
              - .book-card: 카드 1개 컨테이너
              - .book-inner: 앞/뒤면을 감싸는 래퍼(뒤집힘 애니메이션용)
              - .book-front: 앞면(썸네일, 제목, 첫 문단)
              - .book-back:  뒷면(첫 인용 블록) -->
      <article class="book-card">
        <div class="book-inner">
          <div class="book-front">
            <?php if (has_post_thumbnail()) : ?>
              <!-- 썸네일(책 표지 이미지 등) -->
              <div class="book-thumb"><?php the_post_thumbnail('medium'); ?></div>
            <?php endif; ?>

            <!-- 글 제목 -->
            <h3 class="book-title"><?php the_title(); ?></h3>

            <!-- 메타 영역: 첫 문단 블록 HTML -->
            <div class="book-meta"><?php echo $paragraph; ?></div>
          </div>

          <!-- 뒷면: 첫 인용 블록 HTML -->
          <div class="book-back"><?php echo $quote; ?></div>
        </div>
      </article>
    <?php endwhile; else : ?>
      <p>글 없음</p>
    <?php endif; wp_reset_postdata(); ?>
    <!-- 커스텀 루프 사용 후 전역 $post 등 상태 원복 -->
  </div>
</section>

<?php get_footer(); ?> 
<!-- 공통 푸터(footer.php) 불러오기 -->

<script>
/* 
   5) 모바일 전용 카드 인터랙션 스크립트
      - 화면 너비 768px 이하에서만 활성화
      - 카드 클릭 시 .touched 클래스를 토글해 앞/뒤면 전환
      - 다른 카드가 열려 있으면 닫고, 바깥 영역 클릭 시 모두 닫음
 */
document.addEventListener("DOMContentLoaded", function () {
  if (window.innerWidth <= 768) {
    const cards = document.querySelectorAll(".book-card");
    cards.forEach(card => {
      const inner = card.querySelector(".book-inner"); // 앞/뒤면 래퍼
      card.addEventListener("click", function (e) {
        e.stopPropagation(); // 카드 클릭이 문서 바깥으로 전파되어 즉시 닫히지 않게 방지

        // 이미 열린 카드면 닫기
        if (card.classList.contains("touched")) {
          card.classList.remove("touched");
          inner.classList.remove("touched");
        } else {
          // 다른 카드들 열림 상태 모두 닫기
          document.querySelectorAll(".book-card.touched").forEach(other => {
            if (other !== card) {
              other.classList.remove("touched");
              other.querySelector(".book-inner").classList.remove("touched");
            }
          });
          // 현재 카드 열기(앞/뒤면 전환)
          card.classList.add("touched");
          inner.classList.add("touched");
        }
      });
    });

    // 바디(빈 공간) 클릭 시 모든 카드 닫기
    document.body.addEventListener("click", function () {
      document.querySelectorAll(".book-card.touched").forEach(card => card.classList.remove("touched"));
      document.querySelectorAll(".book-inner.touched").forEach(inner => inner.classList.remove("touched"));
    });
  }
});
</script>
