<?php
/**
 * HTML for the all snippets and codevault list table
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;
?>

<form method="get" action="">
    <?php
        List_Table::required_form_fields( 'search_box' );
        $this->list_table->search_box( __( 'Search Snippets', 'code-snippets' ), 'search_id' );
    ?>
</form>

<form method="post" action="">
    <input type="hidden" id="code_snippets_ajax_nonce"
        value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">
    <?php
        List_Table::required_form_fields();
        $this->list_table->display();   
    
    ?>
</form>
<div class="cloud-key">
    <p><b><u>Cloud Sync Guide</u></b></p>
    <p><span class="dashicons dashicons-cloud cloud-icon cloud-downloaded"></span>Snippet Downloaded from Cloud but not synced with Codevault</p>
    <p><span class="dashicons dashicons-cloud cloud-icon cloud-synced-legend "></span>Snippet downloaded and in sync with Codevault<span class="go-pro-badge">Pro</span></p>
    <p><span class="dashicons dashicons-cloud cloud-icon cloud-not-downloaded"></span>Snippet in Codevault but not downloaded to local site<span class="go-pro-badge">Pro</span></p>
    <p><span class="dashicons dashicons-cloud cloud-icon cloud-update"></span>Snippet Update available<span class="go-pro-badge">Pro</span></p>
</div>