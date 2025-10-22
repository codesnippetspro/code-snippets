<?php

namespace Code_Snippets\Tests;

use WP_REST_Request;

class Rest_Api_Test extends TestCase {

    public function test_snippets_rest_pagination() {
        // Create 15 snippets programmatically.
        $created_ids = [];
        for ( $i = 1; $i <= 15; $i++ ) {
            $snippet = new \Code_Snippets\Snippet();
            $snippet->name = "Snippet $i";
            $snippet->code = "// code $i";
            $result = \Code_Snippets\save_snippet( $snippet );
            $this->assertNotNull( $result );
            $created_ids[] = $result->id;
        }

        // Request page 1 with per_page=10
        $request1 = new WP_REST_Request( 'GET', '/code-snippets/v1/snippets' );
        $request1->set_param( 'per_page', 10 );
        $request1->set_param( 'page', 1 );
        $response1 = rest_get_server()->dispatch( $request1 );
        $this->assertEquals( 200, $response1->get_status() );
        $data1 = $response1->get_data();
        $this->assertCount( 10, $data1 );

        // Request page 2 with per_page=10
        $request2 = new WP_REST_Request( 'GET', '/code-snippets/v1/snippets' );
        $request2->set_param( 'per_page', 10 );
        $request2->set_param( 'page', 2 );
        $response2 = rest_get_server()->dispatch( $request2 );
        $this->assertEquals( 200, $response2->get_status() );
        $data2 = $response2->get_data();

        // Ensure page 2 has the remaining items and is different from page 1
        $this->assertGreaterThanOrEqual( 1, count( $data2 ) );
        $this->assertNotEquals( $data1, $data2 );

        // Check pagination headers
        $this->assertEquals( '15', $response1->get_headers()['X-WP-Total'] );
        $this->assertEquals( '2', $response1->get_headers()['X-WP-TotalPages'] );
    }
}
