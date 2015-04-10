<?php 
/**
 * The template for Plan Promotion purpose
 * @author : Bobcares 
 */
?>
<?php get_header(); ?>

<?php 

// Let's get the data we need
?>

  <div id="container">
    <div id="content">
    <?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

      <div class="resource">
        <h1 class="entry-title"><?php //the_title(); ?></h1>
        <div class="entry-meta">
        </div>

        <div class="entry-content">
          <?php the_content(); ?>
        </div>
      </div>

    <?php endwhile; ?>
    </div>
  </div>

<?php get_footer(); ?>
