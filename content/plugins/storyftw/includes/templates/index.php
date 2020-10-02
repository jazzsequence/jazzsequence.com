<?php $story_view = $this; // friendly variable ?>
<!DOCTYPE html>
<html lang="en" id="storyftw-story">
<head>
  <meta charset="utf-8">
  <title><?php echo esc_attr( get_the_title( $story_view->post->ID ) ); ?></title>

  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

  <?php do_action( 'storyftw_head' ); ?>
  <?php if ( ! $story_view->story_meta( 'disable_wp_head') ) : ?>
  <?php wp_head(); ?>
  <?php endif; ?>

</head>

<body <?php body_class(); ?>>
  <div class="sftw-view bg-dynamic" <?php $story_view->set_to_first_background_color(); ?>>

    <?php do_action( 'storyftw_top', $story_view ); ?>

    <div id="menu" class="shifty">
      <div class="shifty-inner">
        <div class="shifty-content">
          <div class="storybook no-select">

            <?php do_action( 'storyftw_before_loop', $story_view ); ?>

            <?php foreach ( $story_view->get_story_pages() as $page_number => $post ) : setup_postdata( $post ); $page_number = $page_number + 1; ?>

            <?php do_action( 'storyftw_before_story_page', $page_number, $story_view ); ?>

            <div id="<?php $this->story_page_slug(); ?>" class="<?php $story_view->story_page_classes(); ?>"<?php $story_view->story_page_attributes(); ?>>

                <?php do_action( 'storyftw_before_wrap', $page_number, $story_view ); ?>

                <div class="<?php $story_view->story_page_inner_wrap_classes(); ?>">

                  <?php do_action( 'storyftw_inside_wrap', $page_number, $story_view ); ?>

                  <?php if ( $story_view->story_page_has_bg_video() ) : ?>

                    <?php do_action( 'storyftw_bg_video', $page_number, $story_view ); ?>

                    <div class="bg-video-wrap bg-video-center"></div>

                  <?php endif; ?>

                  <div class="story-content <?php $story_view->story_page_content_wrap_classes(); ?>">

                    <?php do_action( 'storyftw_before_content', $page_number, $story_view ); ?>

                    <?php $story_view->content(); ?>

                    <?php do_action( 'storyftw_after_content', $page_number, $story_view ); ?>

                  </div>

                  <?php do_action( 'storyftw_inside_wrap_after', $page_number, $story_view ); ?>

                </div>

                <?php do_action( 'storyftw_after_wrap', $page_number, $story_view ); ?>

                  <div class="story-page-footer light">

                    <?php do_action( 'storyftw_page_footer', $page_number, $story_view ); ?>

                  </div>

                <?php $story_view->photo_credit(); ?>

                <?php do_action( 'storyftw_after_footer', $page_number, $story_view ); ?>

            </div>

            <?php do_action( 'storyftw_after_story_page', $page_number, $story_view ); ?>

            <?php endforeach; wp_reset_postdata(); ?>

            <?php do_action( 'storyftw_after_loop', $story_view ); ?>

          </div>


          <div class="js-story-page-previous rail rail-left p1 clickable no-select mobile-hide hide">
            <?php do_action( 'storyftw_nav_prev', $story_view ); ?>
          </div>
          <div class="js-story-page-next rail rail-right right-align p1 clickable no-select mobile-hide">
            <?php do_action( 'storyftw_nav_next', $story_view ); ?>
          </div>
        </div><!-- .shifty-content -->

        <div class="shifty-menu scrolly menu bg-white-85">
          <div class="py4">

            <?php do_action( 'storyftw_before_toc', $story_view ); ?>

            <?php do_action( 'storyftw_toc', $story_view ); ?>

            <?php do_action( 'storyftw_after_toc', $story_view ); ?>

          </div>
        </div><!-- .shifty-menu -->

        </div><!-- .shifty-inner -->
      </div><!-- .shifty -->

    <div class="sftw-navbar bg-dynamic-a no-select light <?php $story_view->maybe_hide_footer(); ?>" <?php $story_view->set_to_first_background_color( '.75' ); ?>>

      <div class="sftw-navbar-left left">
        <?php do_action( 'storyftw_navbar_left', $story_view ); ?>
      </div>

      <div class="sftw-navbar-right right right-align">
        <?php do_action( 'storyftw_navbar_right', $story_view ); ?>
      </div>

      <?php do_action( 'storyftw_footer_title', $story_view ); ?>

    </div>

  </div><!-- .view -->

  <?php do_action( 'storyftw_footer' ); ?>
  <?php if ( ! $story_view->story_meta( 'disable_wp_footer') ) : ?>
  <?php wp_footer(); ?>
  <?php endif; ?>

</body>
</html>
