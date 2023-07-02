<?php
/**
 * HTML for the cloud search tab
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;
    
    $search_query = isset( $_REQUEST['cloud_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cloud_search'] ) ) : '';

?>
    
<p class="text-justify">
    <?php echo __('Use the search bar below to search cloud snippets by entering either the name of a codevault 
    (Important : codevault name is case and spelling sensitive and only public snippets will be shown) or by keyword(s).'); ?>
</p>

<form method="get" action="" id="cloud-search-form">
    <?php List_Table::required_form_fields( 'search_box' ); ?>
    <label class="screen-reader-text" for="cloud_search">
        <?php esc_html_e( 'Search cloud snippets', 'code-snippets' ); ?>
    </label>
    <?php 
        if( isset($_REQUEST['type'] ) ){
            echo '<input type="hidden" name="type" value="' . sanitize_text_field( esc_attr( $_REQUEST['type' ] ) ) . '">';
        }
    ?>
    <div class="heading-box"> 
        <p class="cloud-search-heading"><?php echo __('Search Cloud'); ?></p>
    </div>
    <div class="input-group">
        <select id="cloud-select-prepend" class="select-prepend" name="cloud_select">
            <option value="term"><?php echo __('Search by Keyword(s)'); ?></option>
            <option value="codevault"><?php echo __(' Name of Codevault'); ?> </option>
        </select>
        <input type="text" id="cloud_search" name="cloud_search" class="cloud_search"
            value="<?php echo esc_html( $search_query ); ?>"
            placeholder="<?php esc_html_e( 'e.g. Remove unused JavaScript…', 'code-snippets' ); ?>">
        <button type="submit" id="cloud-search-submit" class="button"><?php echo __('Search Cloud'); ?><span class="dashicons dashicons-search cloud-search"></span></button>
    </div>
</form>
<form method="post" action="" id="cloud-search-results">
    <input type="hidden" id="code_snippets_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">
    <?php
        List_Table::required_form_fields();
        //Check if url has a search query called cloud_search
        if( isset( $_REQUEST['cloud_search'] ) ){
            //If it does, then we want to display the cloud search table
            $this->cloud_search_list_table->display();
        }			
echo '</form>';