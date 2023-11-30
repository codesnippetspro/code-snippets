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
?>

<div class="csp-wrap csp-welcome-wrap">

    <div class="csp-header-wrap">
        <img class="csp-logo" width="50px" src="https://codesnippets.pro/wp-content/uploads/2023/11/code-snippets-pro-logo80h-copy-1.png" alt="Code Snippets Logo">    
        <h1 class="csp-heading">Welcome to Code Snippets</h1>
    </div>

    <article class="csp-section-changes"> 
        <div class="csp-section-changes-header">
            <h2 class="csp-h2">Version <?php echo code_snippets()->version  ?> </h2>
            <a href="https://codesnippets.pro/changelog/" class="csp-link csp-link-changelog">Full Changelog <span class="dashicons dashicons-external"></span></a>
        </div>
        <div class="csp-section-changes-log">
            <p>This update introduces significant improvements and bug fixes, with a focus on enhancing the current cloud sync and Code Snippets AI:</p> 
            <ul class="csp-changelog-list">
                <li><strong>Bug Fix:</strong> Import error when initialising cloud sync configuration. (PRO)</li>
                <li><strong>Improvement:</strong>  Added debug action for resetting snippets caches.</li>
            </ul>         
        </div>
    </article>
    
    <section class="csp-section-nav">
        <h2 class="csp-h2 csp-section-links-heading">Useful links and resources:</h2>
        <ul class="csp-list-nav">
            <li><a href="admin.php?page=add-snippet" class="csp-link-nav csp-link-new-snippet"><span class="dashicons dashicons-edit"></span><div class="csp-link-text"><span class="csp-link-text-top">Create</span><span class="csp-link-text-bottom">Add new snippet</span></div></a></li>
            <li><a href="https://codesnippets.cloud/" class="csp-link-nav csp-link-cloud"><span class="dashicons dashicons-cloud"></span><div class="csp-link-text"><span class="csp-link-text-top">Cloud</span><span class="csp-link-text-bottom">Sync and transfer</span></div></a></li>
            <li><a href="https://codesnippets.pro/pricing/" class="csp-link-nav csp-link-pro"><span class="dashicons dashicons-external"></span><div class="csp-link-text"><span class="csp-link-text-top">Pro</span><span class="csp-link-text-bottom">See all features</span></div></a></li>
            <li><a href="https://help.codesnippets.pro/" class="csp-link-nav csp-link-resources"><span class="dashicons dashicons-welcome-learn-more"></span><div class="csp-link-text"><span class="csp-link-text-top">Learn</span><span class="csp-link-text-bottom">Become a pro</span></div></a></li>
            <li><a href="https://www.facebook.com/groups/282962095661875/" class="csp-link-nav csp-link-community"><span class="dashicons dashicons-facebook"></span><div class="csp-link-text"><span class="csp-link-text-top">Community</span><span class="csp-link-text-bottom">Join Facebook group</span></div></a></li>
        </ul>
    </section>


    <section class="csp-section-links"> 
        <h2 class="csp-h2 csp-section-links-heading">Helpful tips, hints and tricks:</h2>
        <div class="csp-grid csp-grid-3"> 
            <?php
                $news_items = $this->load_welcome_data();
                foreach ( $news_items as $news_item ) {
                    echo'<div class="csp-news-item">
                            <div class="csp-news-item-img-wrap">
                                <img class="csp-news-item-img" src="'. $news_item['image_url'] .'">
                            </div>
                            <div class="csp-news-item-content">
                            <p class="csp-h2 csp-news-item-title">'. $news_item['title'] .'</p>
                            <p class="csp-news-item-description">'. $news_item['description'] .'</p>
                            </div>
                            <div class="csp-news-item-footer">
                                <p class="csp-news-item-category">'. $news_item['category'] .'</p>
                                <a href="'. $news_item['follow_url'] .'" class="csp-link csp-link-changelog" target="_blank">Read more <span class="dashicons dashicons-external"></span></a>
                            </div>
                        </div>';
                }
            ?>
        </div>
    </section>

</div>

