<?php

namespace Code_Snippets;

class Snippet_Files {

    public function register_hooks() {
        add_action( 'code_snippets/create_snippet', [ $this, 'handle_snippet' ], 10, 2 );
        add_action( 'code_snippets/update_snippet', [ $this, 'handle_snippet' ], 10, 2 );
        add_action( 'code_snippets/delete_snippet', [ $this, 'delete_snippet' ], 10, 2 );
        add_action( 'code_snippets/activate_snippet', [ $this, 'activate_snippet' ], 10, 2 );
        add_action( 'code_snippets/deactivate_snippet', [ $this, 'deactivate_snippet' ], 10, 2 );
    }

    public function handle_snippet( $snippet, $table ) {
        if ( 'php' !== $snippet->get_type() ) {
            return;
        }
        $base_dir = WP_CONTENT_DIR . '/code-snippets/' . $table;

        if ( ! is_dir( $base_dir ) ) {
            wp_mkdir_p( $base_dir );
        }

        $file_path = trailingslashit( $base_dir ) . $snippet->id . '.php';

        if ( $snippet->active ) {
            $content = "<?php\n\nif ( ! defined( 'ABSPATH' )) { return; }\n\n" . $snippet->code;

            file_put_contents( $file_path, $content );
        } else {
            @unlink( $file_path );
        }

        $index_file_path = trailingslashit( $base_dir ) . 'index.php';

        $active_snippets = is_file( $index_file_path ) ? require $index_file_path : [];

        if ( $snippet->active ) {
            $active_snippets[ $snippet->id ] = $snippet->get_fields();
        } else {
            unset( $active_snippets[ $snippet->id ] );
        }
    
        $index_content = "<?php\n\nif ( ! defined( 'ABSPATH' )) { return; }\n\nreturn " . var_export( $active_snippets, true ) . ";\n";
    
        file_put_contents( $index_file_path, $index_content );
    }

    public function delete_snippet( $snippet, $network ) {
        if ( 'php' !== $snippet->get_type() ) {
            return;
        }

        $table = code_snippets()->db->get_table_name( $network );

        $base_dir = WP_CONTENT_DIR . '/code-snippets/' . $table;

        $file_path = trailingslashit( $base_dir ) . $snippet->id . '.php';

        @unlink( $file_path );

        $index_file_path = trailingslashit( $base_dir ) . 'index.php';

        $active_snippets = is_file( $index_file_path ) ? require $index_file_path : [];

        unset( $active_snippets[ $snippet_id ] );

        $index_content = "<?php\n\nif ( ! defined( 'ABSPATH' )) { return; }\n\nreturn " . var_export( $active_snippets, true ) . ";\n";

        file_put_contents( $index_file_path, $index_content );
    }

    public function activate_snippet( $snippet, $network ) {
        if ( 'php' !== $snippet->get_type() ) {
            return;
        }

        $table = code_snippets()->db->get_table_name( $network );

        $base_dir = WP_CONTENT_DIR . '/code-snippets/' . $table;

        $file_path = trailingslashit( $base_dir ) . $snippet->id . '.php';

        $content = "<?php\n\nif ( ! defined( 'ABSPATH' )) { return; }\n\n" . $snippet->code;

        file_put_contents( $file_path, $content );

        $index_file_path = trailingslashit( $base_dir ) . 'index.php';

        $active_snippets = is_file( $index_file_path ) ? require $index_file_path : [];

        $active_snippets[ $snippet->id ] = $snippet->get_fields();

        $index_content = "<?php\n\nif ( ! defined( 'ABSPATH' )) { return; }\n\nreturn " . var_export( $active_snippets, true ) . ";\n";

        file_put_contents( $index_file_path, $index_content );
    }

    public function deactivate_snippet( $snippet_id, $network ) {
        $snippet = get_snippet( $snippet_id, $network );

        if ( 'php' !== $snippet->get_type() ) {
            return;
        }

        $table = code_snippets()->db->get_table_name( $network );

        $base_dir = WP_CONTENT_DIR . '/code-snippets/' . $table;

        $file_path = trailingslashit( $base_dir ) . $snippet->id . '.php';

        @unlink( $file_path );

        $index_file_path = trailingslashit( $base_dir ) . 'index.php';

        $active_snippets = is_file( $index_file_path ) ? require $index_file_path : [];

        unset( $active_snippets[ $snippet_id ] );

        $index_content = "<?php\n\nif ( ! defined( 'ABSPATH' )) { return; }\n\nreturn " . var_export( $active_snippets, true ) . ";\n";

        file_put_contents( $index_file_path, $index_content );
    }
}
