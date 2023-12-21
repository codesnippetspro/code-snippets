<?php

/**
 * HTML for the Manage Snippets page.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

/**
 * Loaded from the Manage_Menu class.
 *
 * @var Manage_Menu $this
 */

if ( ! defined( 'ABSPATH' ) ) {
    return;
}

use function Code_Snippets\code_snippets;

$current_user = wp_get_current_user();
$welcome_data = $this->load_welcome_data();
$hero = $welcome_data['hero-item'][0];
$features = $welcome_data['features'];
$partners = $welcome_data['partners'];
$item_limit = 4;
?>

<div class="csp-wrap csp-welcome-wrap">
    <!-- Header Section -->
    <div class="csp-header-wrap">
        <img class="csp-logo" width="50px" src="<?php echo esc_url('https://codesnippets.pro/wp-content/uploads/2023/11/code-snippets-pro-logo80h-copy-1.png'); ?>" alt="Code Snippets Logo">    
        <h1 class="csp-heading">
            <?php printf( __( 'Welcome, <span>%s</span> to Code Snippets', 'code-snippets' ), 
                    esc_html__( $current_user->display_name, 'code-snippets' ) 
                    );  
            ?>
        </h1>
    </div>
    <!-- Changelog and Key Feature -->
    <article class="csp-section-changes">
        <div class="csp-section-changes-body">

            <div class="csp-section-changes-left-col csp-section-changes-col">
                <!-- Hero Image/GIF -->
                <div class="csp-section-changes-right-col-bottom csp-card-white">
                    <div class="csp-section-changes-header">
                            <h2 class="csp-h2">
                                <?php echo esc_attr__( $hero['name'], 'code-snippets' ); ?> 🚀
                            </h2>
                            <?php printf( '<a href="%s" class="csp-link csp-img-link">%s<span class="dashicons dashicons-external"></span></a>', 
                                        esc_url( $hero['follow_url'] ), 
                                        __( 'Read more', 'code-snippets' )  
                                );  
                            ?>
                    </div>
                    <div class="csp-section-changes-img-wrap">
                        <div id="csp-loading-spinner" class="csp-loading-spinner"></div>
                        <img id="csp-changes-img" onload="hideLoadingAnimation()" class="csp-section-changes-img" src="<?php echo esc_url( $hero['image_url'] ) ?>" alt="Code Snippets AI">
                    </div> 
                </div> 
            </div>

            <div class="csp-section-changes-right-col csp-section-changes-col">
                <!-- Changelog - handle for pro? -->
                <div class="csp-section-changes-right-col-top csp-card-white">
                    <div class="csp-section-changes-header">
                        <h2 class="csp-h2">
                            <?php printf( __( 'What\'s new in Version %s', 'code-snippets' ), esc_html__( code_snippets()->version ) ); ?>
                        </h2>
                        <?php printf( '<a href="%s" class="csp-link">%s<span class="dashicons dashicons-external"></span></a>', 
                                        esc_url( 'https://codesnippets.pro/changelog/' ), 
                                        __( 'Full Changelog', 'code-snippets' )  
                                );  
                        ?>
                    </div>
                    <div class="csp-section-changes-log">
                        <p><?php echo __( 'This update introduces significant improvements and bug fixes, with a focus on enhancing the current cloud sync and Code Snippets AI:', 'code-snippets' ) ?></p> 
                        <ul class="csp-changelog-list">
                            <li><?php printf('<b>%s</b> %s',
                                        __( 'Bug Fix: ', 'code-snippets' ),
                                        __( 'Import error when initialising cloud sync configuration.', 'code-snippets' )
                                    ); 
                                ?>
                            </li>
                            <li><?php printf('<b>%s</b> %s',
                                        __( 'Improvement: ', 'code-snippets' ),
                                        __( 'Added debug action for resetting snippets caches', 'code-snippets' )
                                    ); 
                                ?>
                            </li>
                        </ul>         
                    </div>
                </div>        
            </div>

        </div>
    </article>
    <!-- Helpful tips and tricks -->
    <section class="csp-section-links"> 
        <h2 class="csp-h2 csp-section-links-heading"><?php echo __('Highlighted features.. ', 'code-snippets'); ?>🎉</h2>
        <div class="csp-grid csp-grid-4"> 
            <?php
                for( $i = 0; $i < $item_limit; $i++ ) {
                    echo'<div class="csp-card-item">
                            <div class="csp-card-item-img-wrap">
                                <img class="csp-card-item-img csp-card-img-square" src="'. esc_url( $features[$i]['image_url'] ) .'">
                            </div>
                            <div class="csp-card-item-content">
                            <p class="csp-h2 csp-card-item-title">'. esc_html__( $features[$i]['title'], 'code-snippets') .'</p>
                            <p class="csp-card-item-description">'. esc_html__( $features[$i]['description'], 'code-snippets') .'</p>
                            </div>
                            <div class="csp-card-item-footer">
                                <p class="csp-card-item-category">'. esc_html__( $features[$i]['category'], 'code-snippets') .'</p>
                                <a href="'. esc_url( $features[$i]['follow_url'] )  .'" class="csp-link" target="_blank">Read more <span class="dashicons dashicons-external"></span></a>
                            </div>
                        </div>';
                }
            ?>
        </div>
    </section>
    <!-- Partners and apps -->
    <section class="csp-section-links csp-section-partners"> 
        <h2 class="csp-h2 csp-section-links-heading"><?php echo __('Partners and Apps..', 'code-snippets'); ?>🔥</h2>
        <div class="csp-grid csp-grid-4"> 
            <?php
                for( $i = 0; $i < $item_limit; $i++ ) {
                    echo'<div class="csp-card-item">
                            <div class="csp-card-item-img-wrap">
                                <img class="csp-card-item-img" src="'. esc_url( $partners[$i]['image_url'] ) .'">
                            </div>
                            <div class="csp-card-item-content">
                                <p class="csp-h2 csp-card-item-title">'. esc_html__( $partners[$i]['title'], 'code-snippets') .'</p>
                            </div>
                            <div class="csp-partner-item-footer">
                                <a href="'. esc_url( $partners[$i]['follow_url'] )  .'" class="csp-link" target="_blank">
                                '. __('Go to Partner', 'code-snippets') .'
                                <span class="dashicons dashicons-external"></span></a>
                            </div>
                        </div>';
                }
            ?>
        </div>
    </section>
    <!-- Quicklinks Section -->
    <section class="csp-section-nav">
        <ul class="csp-list-nav">
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-new-snippet"><span class="dashicons dashicons-edit"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( admin_url( 'admin.php?page=add-snippet' ) ),
                    esc_html__( 'Create', 'code-snippets'),
                    esc_html__( 'Add new snippet', 'code-snippets')
                );
                ?>
            </li>
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-cloud"><span class="dashicons dashicons-cloud"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( 'https://codesnippets.cloud/' ),
                    esc_html__( 'Cloud', 'code-snippets'),
                    esc_html__( 'Sync and transfer', 'code-snippets')
                );
                ?>
            </li>
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-pro"><span class="dashicons dashicons-external"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( 'https://codesnippets.pro/pricing/' ),
                    esc_html__( 'Go Pro', 'code-snippets'),
                    esc_html__( 'See all features', 'code-snippets')
                );
                ?>
            </li>
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-resources"><span class="dashicons dashicons-welcome-learn-more"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( 'https://help.codesnippets.pro/' ),
                    esc_html__( 'Learn', 'code-snippets'),
                    esc_html__( 'Become a pro', 'code-snippets')
                );
                ?>
            </li>
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-community"><span class="dashicons dashicons-facebook"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( 'https://www.facebook.com/groups/282962095661875/' ),
                    esc_html__( 'Community', 'code-snippets'),
                    esc_html__( 'Join Facebook group', 'code-snippets')
                );
                ?>
            </li>
        </ul>
    </section>
</div>

<script type="text/javascript">
    function hideLoadingAnimation() {
        document.getElementById('csp-loading-spinner').style.display = 'none';
        document.getElementById('csp-changes-img').style.display = 'block';
    }
</script>