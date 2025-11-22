<?php
/**
 * Core tests for Easy English WP.
 *
 * @package easy-english-wp
 */

class EEWP_Core_Tests extends WP_UnitTestCase {

	public function test_plugin_loaded() {
		$this->assertTrue( function_exists( 'eewp_core' ), 'eewp_core() should exist.' );
		$this->assertTrue( class_exists( 'EEWP_Loader' ), 'EEWP_Loader should exist.' );
	}

	public function test_default_meta_empty_on_new_post() {
		$post_id = self::factory()->post->create();
		$this->assertSame( '', get_post_meta( $post_id, 'eewp_enabled', true ) );
		$this->assertSame( array(), get_post_meta( $post_id, 'eewp_rows', true ) );
	}

	public function test_limit_helper_counts_enabled() {
		$loader = EEWP_Loader::instance();
		$loader->post_types = array( 'post', 'page' );

		// Create 5 published posts with eewp_enabled = yes.
		$post_ids = self::factory()->post->create_many( 5, array( 'post_status' => 'publish' ) );
		foreach ( $post_ids as $id ) {
			update_post_meta( $id, $loader->enabled_key, 'yes' );
		}

		$this->assertSame( 5, $loader->count_enabled_posts(), 'Should count five enabled posts.' );
		$this->assertTrue( $loader->has_reached_limit(), 'Limit should be reached at five.' );

		// Add a 6th; counting should still reflect limit logic.
		$sixth = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		update_post_meta( $sixth, $loader->enabled_key, 'yes' );

		$this->assertGreaterThanOrEqual( 6, $loader->count_enabled_posts(), 'Should count six when six are enabled.' );
		$this->assertTrue( $loader->has_reached_limit(), 'Limit should be reached when above five.' );
	}
}
