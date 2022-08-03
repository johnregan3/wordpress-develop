<?php

/**
 * @group user
 * @group post
 */
class Tests_User_CountUserPosts extends WP_UnitTestCase {
	public static $user_id;
	public static $post_ids = array();

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$user_id = $factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'count_user_posts_user',
				'user_email' => 'count_user_posts_user@example.com',
			)
		);

		self::$post_ids = $factory->post->create_many(
			4,
			array(
				'post_author' => self::$user_id,
				'post_type'   => 'post',
			)
		);
		self::$post_ids = array_merge(
			self::$post_ids,
			$factory->post->create_many(
				3,
				array(
					'post_author' => self::$user_id,
					'post_type'   => 'wptests_pt',
				)
			)
		);
		self::$post_ids = array_merge(
			self::$post_ids,
			$factory->post->create_many(
				2,
				array(
					'post_author' => 12345,
					'post_type'   => 'wptests_pt',
				)
			)
		);

		self::$post_ids[] = $factory->post->create(
			array(
				'post_author' => 12345,
				'post_type'   => 'wptests_pt',
			)
		);
	}

	public function set_up() {
		parent::set_up();
		register_post_type( 'wptests_pt' );
	}

	public function test_count_user_posts_post_type_should_default_to_post() {
		$this->assertEquals( 4, count_user_posts( self::$user_id ) );
	}

	/**
	 * @ticket 21364
	 */
	public function test_count_user_posts_post_type_post() {
		$this->assertEquals( 4, count_user_posts( self::$user_id, 'post' ) );
	}

	/**
	 * @ticket 21364
	 */
	public function test_count_user_posts_post_type_cpt() {
		$this->assertEquals( 3, count_user_posts( self::$user_id, 'wptests_pt' ) );
	}

	/**
	 * @ticket 32243
	 */
	public function test_count_user_posts_with_multiple_post_types() {
		$this->assertEquals( 7, count_user_posts( self::$user_id, array( 'wptests_pt', 'post' ) ) );
	}

	/**
	 * @ticket 32243
	 */
	public function test_count_user_posts_should_ignore_non_existent_post_types() {
		$this->assertEquals( 4, count_user_posts( self::$user_id, array( 'foo', 'post' ) ) );
	}

	/**
	 * Test the count_user_posts cache.
	 *
	 * @ticket 39242
	 *
	 * @dataProvider data_count_user_posts_cache
	 *
	 * Primarily, this tests that the cache is created when count_user_posts() is called.
	 * Additionally, this tests using both a single post type string and an array of post types as a parameter.
	 *
	 * This ticket adds a cache key string to the function so we check that it is generated as exptected.
	 *
	 * @param string|array $post_type       Used to test the first param in count_user_posts().
	 * @param string       $cache_key_lable Provides the expected cache key.
	 */
	public function test_count_user_posts_cache( $post_type, $cache_key_label) {
		// Validate the cache is empty.
		$cache = wp_cache_get( 'count_user_' . $cache_key_label . '_' . self::$user_id, 'user_posts_count' );
		$this->assertFalse( $cache );

		// Call the function.
		$count = count_user_posts( self::$user_id, $post_type );

		// Validate the cache is populated.
		$cache = wp_cache_get( 'count_user_' . $cache_key_label . '_' . self::$user_id, 'user_posts_count' );
		$this->assertEquals( $count, $cache );
	}

	/**
	 * @ticket 39242
	 */
	public function data_count_user_posts_cache() {
		return array (
			array(
				'post',
				'post'
			),
			array(
				array(
					'post',
					'wptests_pt',
				),
				'post_wptests_pt',
			),
		);
	}

	/**
	 * Test the count_user_posts cache gets updated.
	 *
	 * @ticket 39242
	 */
	public function test_count_user_posts_cache_updated() {
		$start = count_user_posts( self::$user_id, 'post' );

		$this->factory->post->create(
			array(
				'post_author' => self::$user_id,
				'post_type'   => 'post',
			)
		);

		$result = count_user_posts( self::$user_id, 'post' );
		$this->assertEquals( $result, $start + 1 );
	}
}
