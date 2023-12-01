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
?>

<div class="csp-wrap csp-welcome-wrap">

    <div class="csp-header-wrap">
        <img class="csp-logo" width="50px" src="https://codesnippets.pro/wp-content/uploads/2023/11/code-snippets-pro-logo80h-copy-1.png" alt="Code Snippets Logo">    
        <h1 class="csp-heading">
            <?php printf( __( 'Welcome, %s to Code Snippets', 'code-snippets' ), esc_html__( $current_user->display_name, 'code-snippets' ) );   ?>
        </h1>
    </div>

    <article class="csp-section-changes"> 
        <div class="csp-section-changes-header">
            <h2 class="csp-h2">
                <?php printf( __( 'What\'s new in Version %s', 'code-snippets' ), esc_html( code_snippets()->version ) ); ?>
            </h2>
            <a href="https://codesnippets.pro/changelog/" class="csp-link csp-link-changelog"><?php echo __('Full Changelog', 'code-snippets'); ?><span class="dashicons dashicons-external"></span></a>
        </div>
        <div class="csp-section-changes-log">
            <p><?php echo __( 'This update introduces significant improvements and bug fixes, with a focus on enhancing the current cloud sync and Code Snippets AI:', 'code-snippets' ) ?></p> 
            <ul class="csp-changelog-list">
                <li><?php echo __('<b>Bug Fix: </b>', 'code-snippets' );  echo __('Import error when initialising cloud sync configuration. (PRO)', 'code-snippets' ); ?></li>
                <li><?php echo __('<b>Improvement: </b>', 'code-snippets' );  echo __('Added debug action for resetting snippets caches', 'code-snippets' ); ?></li>
            </ul>         
        </div>
    </article>
    
    <section class="csp-section-nav">
        <h2 class="csp-h2 csp-section-links-heading"><?php echo __('Useful links and resources:', 'code-snippets'); ?></h2>
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
                    esc_url( admin_url( 'https://codesnippets.cloud/' ) ),
                    esc_html__( 'Cloud', 'code-snippets'),
                    esc_html__( 'Sync and transfer', 'code-snippets')
                );
                ?>
            </li>
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-pro"><span class="dashicons dashicons-external"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( admin_url( 'https://codesnippets.pro/pricing/' ) ),
                    esc_html__( 'Go Pro', 'code-snippets'),
                    esc_html__( 'See all features', 'code-snippets')
                );
                ?>
            </li>
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-resources"><span class="dashicons dashicons-welcome-learn-more"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( admin_url( 'https://help.codesnippets.pro/' ) ),
                    esc_html__( 'Learn', 'code-snippets'),
                    esc_html__( 'Become a pro', 'code-snippets')
                );
                ?>
            </li>
            <li>
                <?php 
                printf('<a href="%s" class="csp-link-nav csp-link-community"><span class="dashicons dashicons-facebook"></span><div class="csp-link-text"><span class="csp-link-text-top">%s</span><span class="csp-link-text-bottom">%s</span></div></a>', 
                    esc_url( admin_url( 'https://www.facebook.com/groups/282962095661875/' ) ),
                    esc_html__( 'Community', 'code-snippets'),
                    esc_html__( 'Join Facebook group', 'code-snippets')
                );
                ?>
            </li>
        </ul>
    </section>


    <section class="csp-section-links"> 
        <h2 class="csp-h2 csp-section-links-heading"><?php echo __('Helpful tips, hints and tricks:', 'code-snippets'); ?></h2>
        <div class="csp-grid csp-grid-3"> 
            <?php
                $news_items = $this->load_welcome_data();
                foreach ( $news_items as $news_item ) {
                    echo'<div class="csp-news-item">
                            <div class="csp-news-item-img-wrap">
                                <img class="csp-news-item-img" src="'. esc_url($news_item['image_url']) .'">
                            </div>
                            <div class="csp-news-item-content">
                            <p class="csp-h2 csp-news-item-title">'. esc_html__( $news_item['title'], 'code-snippets') .'</p>
                            <p class="csp-news-item-description">'. esc_html__($news_item['description'], 'code-snippets') .'</p>
                            </div>
                            <div class="csp-news-item-footer">
                                <p class="csp-news-item-category">'. esc_html__($news_item['category'], 'code-snippets') .'</p>
                                <a href="'. esc_url($news_item['follow_url']) .'" class="csp-link csp-link-changelog" target="_blank">Read more <span class="dashicons dashicons-external"></span></a>
                            </div>
                        </div>';
                }
            ?>
        </div>
    </section>

</div>

