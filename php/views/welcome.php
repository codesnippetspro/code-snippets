<?php

use function Code_Snippets\code_snippets;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
?>

<div class="wrap welcome-wrap">

    <div>
        <img class="cs-logo" width="50px" src="https://codesnippets.pro/wp-content/uploads/2023/11/code-snippets-pro-logo80h-copy-1.png" alt="Code Snippets Logo">    
    </div>

	<h1>Welcome to Code Snippets</h1>
    <p class="version">Version <?php echo code_snippets()->version  ?> </p>

	<p>Thank you for choosing Code Snippets for your code management needs. We hope you find our platform easy to use and helpful in organizing your code snippets.</p>

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
        <h2>What's New</h2>
        <p>Some useful text here about whats new in this version ...maybe changelog or <similar class=""></similar></p>
        <p>Below are some useful links that we have curated to help you get the most from code snippets, just click on the image.</p>

        <div class="cs-grid cs-grid-3"> 

            <div class="news-item">
                <a href="https://youtu.be/5OqrJAm_pqU?si=hgJw2BgMy9dF07KY" class="news-item-link">
                    <img class="news-item-img" src="https://codesnippets.pro/wp-content/uploads/2023/11/Screenshot-2023-11-15-at-23.49.36.png" alt="">
                </a>
            </div>

            <div class="news-item">
                <a href="https://codesnippets.pro/2021/08/10/what-is-a-code-snippet/" class="news-item-link">
                    <img class="news-item-img" src="https://codesnippets.pro/wp-content/uploads/2023/11/feed.png" alt="">
                </a>
            </div>

            <div class="news-item">
                <a href="https://codesnippets.pro/pricing" class="news-item-link">
                    <img class="news-item-img" src="https://via.placeholder.com/300x200" alt="">
                </a>
            </div>

            <div class="news-item">
                <a href="https://codesnippets.pro/2021/08/10/what-is-a-code-snippet/" class="news-item-link">
                    <img class="news-item-img" src="https://via.placeholder.com/300x200" alt="">
                </a>
            </div>

            <div class="news-item">
                <a href="https://codesnippets.pro/2021/08/10/what-is-a-code-snippet/" class="news-item-link">
                    <img class="news-item-img" src="https://via.placeholder.com/300x200" alt="">
                </a>
            </div>

            <div class="news-item">
                <a href="https://codesnippets.pro/2021/08/10/what-is-a-code-snippet/" class="news-item-link">
                    <img class="news-item-img" src="https://via.placeholder.com/300x200" alt="">
                </a>
            </div>

        </div>
    </section>

</div>

