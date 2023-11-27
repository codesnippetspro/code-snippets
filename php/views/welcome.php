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

<div class="wrap welcome-wrap">

    <div>
        <img class="cs-logo" width="50px" src="https://codesnippets.pro/wp-content/uploads/2023/11/code-snippets-pro-logo80h-copy-1.png" alt="Code Snippets Logo">    
    </div>

	<h1>Welcome to Code Snippets</h1>
    <p class="version">Version <?php echo code_snippets()->version  ?> </p>

	<!-- <p>Thank you for choosing Code Snippets for your code management needs. We hope you find our platform easy to use and helpful in organizing your code snippets.</p> -->

    <section class="section section-quicklinks">
        <h2>Useful links and resources:</h2>
        <ul class="list-quicklinks">
            <li><a href="admin.php?page=add-snippet" class="link-quicklink link-new-snippet">New Snippet <span class="dashicons dashicons-edit"></span></a></li>
            <li><a href="https://codesnippets.cloud/" class="link-quicklink link-cloud">Code Snippet Cloud <span class="dashicons dashicons-cloud"></span></a></li>
            <li><a href="https://codesnippets.pro/pricing/" class="link-quicklink link-pro">Go Pro <span class="dashicons dashicons-external"></span></a></li>
            <li><a href="https://help.codesnippets.pro/" class="link-quicklink link-resources">Learn More <span class="dashicons dashicons-welcome-learn-more"></span></a></li>
            <li><a href="https://www.facebook.com/groups/282962095661875/" class="link-quicklink link-community">Community Support <span class="dashicons dashicons-facebook"></span></a></li>
        </ul>
    </section>

    <section class="section section-news"> 
        <h2>What's New in version <?php echo code_snippets()->version  ?> </h2>
        <p>Key feature, fixes or improvements in this update:</p> 
        <ul class="changelog-list">
            <li>Fixed: Import error when initialising cloud sync configuration. (PRO)</li>
            <li>Improved: Added debug action for resetting snippets caches.</li>
        </ul>
    </section>
    
    <section class="section section-links"> 
        <h2>Useful links to guides, videos and more..</h2>
        
        <div class="cs-grid cs-grid-3"> 

            <?php
                $news_items = $this->load_welcome_data();

                foreach ( $news_items as $news_item ) {
                    
                    echo'<div class="news-item">
                        
                            <a href="'. $news_item['follow_url'] .'" class="news-item-link">
                                <img class="news-item-img" src="'. $news_item['image_url'] .'" alt="">
                            </a>
                            <p> ' . $news_item['title'] . ' </p>
                        </div>';
                }
            ?>
        </div>
    </section>

</div>

