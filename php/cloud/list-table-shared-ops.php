<?php
/**
 * Functions to perform snippet operations
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Cloud;

use function Code_Snippets\code_snippets;

/**
 * Build a hidden input field for a certain column and snippet value.
 *
 * @param string        $column_name Column name.
 * @param Cloud_Snippet $snippet     Column item.
 *
 * @return string
 */
function cloud_lts_build_column_hidden_input( $column_name, $snippet ) {
    return sprintf(
        '<input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
        esc_attr( $column_name ),
        esc_attr( $snippet->id ),
        esc_attr( $column_name ),
        esc_attr( esc_html( $snippet->$column_name ) )
    );
}


/**
 * Process the download snippet action
 *
 * @param string        $action    Action - 'download' or 'update.
 * @param string        $source    Source - 'search' or 'cloud'.
 * @param string        $snippet   Snippet ID.
 *
 * @return void
 */
function cloud_lts_process_download_action( $action, $source, $snippet ) {

    if( 'download' === $action || 'update' === $action  ){
        $result = code_snippets()->cloud_api->download_or_update_snippet(
            sanitize_key( $snippet ),
            sanitize_key( $source ),
            sanitize_key( $action )
        );
        if ( $result['success'] ) {
            if( $result['snippet_id'] ){
                //Redirect to edit snippet page
                wp_safe_redirect( code_snippets()->get_snippet_edit_url( (int) $result['snippet_id'] ) );
                exit;
            }
            wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result['action'] ) ) );
            exit;
        }
    }
    
}

/**
 * Build action links for snippet.
 *
 * @param Cloud_Snippet $snippet     Snippet/Column item.
 * @param string        $source      Source - 'search' or 'codevault'.
 *
 * @return string
 */
function cloud_lts_build_action_links( $snippet, $source ) {
    $lang = Cloud_API::get_type_from_scope( $snippet->scope );
    $link = code_snippets()->cloud_api->get_cloud_link( $snippet->id, 'cloud' );
    $update_available = $link && $link->update_available;
    $addtional_classes = $source == 'search' ? 'action-button-link' : '';

    if ( $link && ! $link->update_available ) {
        return sprintf(
            '<a href="%s" class="cloud-snippet-downloaded %s">%s</a>',
            esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) ),
            $addtional_classes,
            esc_html__( 'View', 'code-snippets' )
        );
    }

    if( $update_available ) {
        $action_link = sprintf(
            '<a class="cloud-snippet-update %s" href="%s">%s</a>',
            $addtional_classes,
            esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) . '/#updated-code' ),
            esc_html__( 'Update Available', 'code-snippets' )
        );
    }else{
        $download_url = add_query_arg(
            [
                'action'  => 'download',
                'snippet' => $snippet->id,
                'source'  => $source,
            ]
        );
        $action_link = sprintf(
            '<a class="cloud-snippet-download %s" href="%s">%s</a>',
                $addtional_classes,
                esc_url( $download_url ),
                esc_html__( 'Download', 'code-snippets' )
        );
    }

    $thickbox_url = '#TB_inline?&width=700&height=500&inlineId=show-code-preview';

    $thickbox_link = sprintf(
        '<a href="%s" class="cloud-snippet-preview cloud-snippet-preview-style thickbox %s" data-snippet="%s" data-lang="%s">%s</a>',
        esc_url( $thickbox_url ),
        $addtional_classes,
        esc_attr( $snippet->id ),
        esc_attr( $lang ),
        esc_html__( 'Preview', 'code-snippets' )
    );

    return $action_link . $thickbox_link;
}

/**
 * Build the pagination functionality
 *
 * @param string    $which          Context where the pagination will be displayed.
 * @param string    $source         Source - 'search' or 'cloud'.
 * @param int       $total_items    Total number of items.
 * @param int       $total_pages    Total number of pages.
 * @param int       $pagenum        Current page number.
 *
 * @return array
 */
function cloud_lts_pagination( $which, $source, $total_items, $total_pages, $pagenum ) {
    /* translators: %s: Number of items. */
    $num = sprintf( _n( '%s item', '%s items', $total_items, 'code-snippets' ), number_format_i18n( $total_items ) );
    $output = '<span class="displaying-num">' . $num . '</span>';

    $current = isset( $_REQUEST['cloud_page'] ) ? (int) $_REQUEST['cloud_page'] : $pagenum;
    $current_url = remove_query_arg( wp_removable_query_args() ). '#'. $source;

    $page_links = array();

    $total_pages_before = '<span class="paging-input">';
    $total_pages_after = '</span></span>';

    $disable_first = false;
    $disable_last = false;
    $disable_prev = false;
    $disable_next = false;

    if ( 1 === $current ) {
        $disable_first = true;
        $disable_prev = true;
    }

    if ( $total_pages === $current ) {
        $disable_last = true;
        $disable_next = true;
    }

    if ( $disable_first ) {
        $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
    } else {
        $page_links[] = sprintf(
            "<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
            esc_url( remove_query_arg( $source.'_page', $current_url ) ),
            __( 'First page' ),
            '&laquo;'
        );
    }

    if ( $disable_prev ) {
        $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
    } else {
        $page_links[] = sprintf(
            "<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
            esc_url( add_query_arg( $source.'_page', max( 1, $current - 1 ), $current_url ) ),
            __( 'Previous page' ),
            '&lsaquo;'
        );
    }

    if ( 'bottom' === $which ) {
        $html_current_page = $current;
        $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
    } 
    
    if ( 'top' === $which ) {
        $html_current_page = sprintf(
            "<label for='current-page-selector' class='screen-reader-text'>%s</label><input class='current-page-selector' id='current-page-selector' type='text' name='%s_page' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
            __( 'Current Page' ),
            $source,
            $current,
            strlen( $total_pages )
        );
    }

    $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

    /* translators: 1: Current page, 2: Total pages. */
    $current_html = _x( '%1$s of %2$s', 'paging', 'code-snippets' );
    $page_links[] = $total_pages_before . sprintf( $current_html, $html_current_page, $html_total_pages ) . $total_pages_after;

    if ( $disable_next ) {
        $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
    } else {
        $page_links[] = sprintf(
            "<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
            esc_url( add_query_arg( $source.'_page', min( $total_pages, $current + 1 ), $current_url ) ),
            __( 'Next page' ),
            '&rsaquo;'
        );
    }

    if ( $disable_last ) {
        $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
    } else {
        $page_links[] = sprintf(
            "<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
            esc_url( add_query_arg( $source.'_page', $total_pages, $current_url ) ),
            __( 'Last page' ),
            '&raquo;'
        );
    }

    $pagination_links_class = 'pagination-links';
    if ( ! empty( $infinite_scroll ) ) {
        $pagination_links_class .= ' hide-if-js';
    }

    $finaloutput = $output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

    return [
        'output'     => $finaloutput,
        'page_class' => $total_pages ? ( $total_pages < 2 ? ' one-page' : '' ) : ' no-pages',
    ];
}