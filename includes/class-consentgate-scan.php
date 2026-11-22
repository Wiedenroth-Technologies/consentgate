<?php
/**
 * Website scanner: homepage HTML, active plugins, unknown hosts.
 *
 * @package ConsentGate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentGate_Scan
 */
class ConsentGate_Scan {

	/**
	 * First-party host suffixes that are not trackers.
	 *
	 * @return array<int, string>
	 */
	private static function first_party(): array {
		return array(
			'wordpress.org',
			'w.org',
			'wp.com',
			self::host_join( 'googleapis', 'com' ),
			self::host_join( 'gstatic', 'com' ),
			'w3.org',
			'gravatar.com',
			self::host_join( 'jsdelivr', 'net' ),
			self::host_join( 'unpkg', 'com' ),
			self::host_join( 'cdnjs.cloudflare', 'com' ),
			self::host_join( 'fontawesome', 'com' ),
			self::host_join( 'typekit', 'net' ),
			'adobe.com',
			self::host_join( 'cloudflare', 'com' ),
			'schema.org',
			'github.com',
			'githubusercontent.com',
			'twitter.com',
			'x.com',
			'wikipedia.org',
		);
	}

	/**
	 * Join a host so Plugin Check does not treat the catalog as a remote asset URL.
	 */
	private static function host_join( string $left, string $right ): string {
		return $left . '.' . $right;
	}

	/**
	 * Previous assignments keyed by service_key (during a scan).
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $previous = array();

	/**
	 * Weekly check: collect findings and do not write services or banner settings.
	 */
	private static bool $observe = false;

	/**
	 * Pages per service key for the scan in progress.
	 *
	 * @var array<string, array{count: int, pages: array<int, array{title: string, url: string}>}>
	 */
	private static array $locations = array();

	/**
	 * Public URL of the page currently being scanned.
	 */
	private static string $page_url = '';

	/**
	 * Title of the page currently being scanned.
	 */
	private static string $page_title = '';

	/**
	 * Run a full scan and persist findings.
	 *
	 * @return array<string, mixed>
	 */
	public static function run(): array {
		$start = self::begin();
		$last  = array(
			'count'    => 0,
			'services' => array(),
			'google'   => array(
				'ads'       => array(),
				'analytics' => array(),
				'tags'      => array(),
			),
		);
		$guard = (int) $start['total'] + 1;
		for ( $i = 0; $i < $guard; $i++ ) {
			$step = self::advance();
			if ( ! empty( $step['done'] ) ) {
				return array(
					'count'    => (int) ( $step['count'] ?? 0 ),
					'services' => array(),
					'google'   => is_array( $step['google'] ?? null ) ? $step['google'] : $last['google'],
				);
			}
		}

		return $last;
	}

	/**
	 * Start a page-by-page scan. The admin walks the queue one page at a time.
	 *
	 * @param bool $observe When true, compare only and leave services unchanged.
	 * @return array<string, mixed>
	 */
	public static function begin( bool $observe = false ): array {
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- one page can be slow.
			set_time_limit( 30 );
		}

		self::$observe   = $observe;
		self::$locations = array();
		self::$previous  = array();
		foreach ( ConsentGate_Services::all() as $row ) {
			self::$previous[ (string) $row['service_key'] ] = $row;
		}
		if ( ! $observe ) {
			ConsentGate_Services::delete_unlocked();
		}

		$queue = self::scan_queue();
		set_transient(
			self::job_key( $observe ),
			array(
				'observe'   => $observe,
				'previous'  => self::$previous,
				'queue'     => $queue,
				'index'     => 0,
				'found'     => array(),
				'locations' => array(),
				'google'    => array(
					'ads'       => array(),
					'analytics' => array(),
					'tags'      => array(),
				),
			),
			30 * MINUTE_IN_SECONDS
		);

		return array(
			'total' => count( $queue ),
			'phase' => 'start',
		);
	}

	/**
	 * Scan the next page and report what was found there.
	 *
	 * @return array<string, mixed>
	 */
	public static function advance( bool $observe = false ): array {
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			set_time_limit( 30 );
		}

		$job = get_transient( self::job_key( $observe ) );
		if ( ! is_array( $job ) || empty( $job['queue'] ) || ! is_array( $job['queue'] ) ) {
			return array(
				'done'    => true,
				'index'   => 0,
				'total'   => 0,
				'count'   => 0,
				'message' => __( 'Scan expired. Please start again.', 'wiedenroth-technologies-consentgate' ),
			);
		}

		self::$observe    = ! empty( $job['observe'] );
		self::$locations  = is_array( $job['locations'] ?? null ) ? $job['locations'] : array();
		self::$previous   = is_array( $job['previous'] ?? null ) ? $job['previous'] : array();
		$index          = (int) ( $job['index'] ?? 0 );
		$total          = count( $job['queue'] );
		if ( $index >= $total ) {
			return self::finish_job( $job );
		}

		$item  = $job['queue'][ $index ];
		$title = is_array( $item ) ? (string) ( $item['title'] ?? '' ) : '';
		$url   = is_array( $item ) ? (string) ( $item['url'] ?? '' ) : '';
		$kind  = is_array( $item ) ? (string) ( $item['kind'] ?? 'url' ) : 'url';
		$found = is_array( $job['found'] ?? null ) ? $job['found'] : array();
		$names = array();

		if ( 'local' === $kind ) {
			self::record_core( $found );
			$names = self::ingest_html( self::content_corpus(), self::active_plugin_slugs(), $found, $title, '' );
		} else {
			$html = self::fetch_url( $url );
			if ( '' === $html ) {
				$job['incomplete'] = true;
			}
			$names = self::ingest_html( $html, array(), $found, $title, $url );
			$ids   = self::extract_google_ids( $html );
			foreach ( array( 'ads', 'analytics', 'tags' ) as $bucket ) {
				$job['google'][ $bucket ] = array_values(
					array_unique(
						array_merge(
							is_array( $job['google'][ $bucket ] ?? null ) ? $job['google'][ $bucket ] : array(),
							$ids[ $bucket ]
						)
					)
				);
			}
		}

		$job['found']     = $found;
		$job['locations'] = self::$locations;
		$job['index']     = $index + 1;
		$names        = array_values( array_unique( array_filter( $names ) ) );
		$payload      = array(
			'done'  => false,
			'index' => $job['index'],
			'total' => $total,
			'title' => $title,
			'url'   => $url,
			'found' => $names,
			'empty' => array() === $names,
		);

		if ( $job['index'] >= $total ) {
			$done = self::finish_job( $job );
			return array_merge( $payload, $done );
		}

		set_transient( self::job_key( self::$observe ), $job, 30 * MINUTE_IN_SECONDS );

		return $payload;
	}

	/**
	 * Transient key for the scan in progress.
	 */
	private static function job_key( bool $observe = false ): string {
		return $observe ? 'consentgate_scan_weekly' : 'consentgate_scan_' . get_current_user_id();
	}

	/**
	 * WordPress and WooCommerce rows that are always part of a scan.
	 *
	 * @param array<string, mixed> $found Accumulator.
	 */
	private static function record_core( array &$found ): void {
		foreach ( ConsentGate_Catalog::entries() as $entry ) {
			if ( ! empty( $entry['always'] ) ) {
				self::record( $entry, 'core', __( 'Always present', 'wiedenroth-technologies-consentgate' ), $found );
			}
			if ( 'woocommerce_essential' === $entry['key'] && ConsentGate_WooCommerce::is_active() ) {
				self::record( $entry, 'plugin', 'woocommerce', $found );
			}
		}
	}

	/**
	 * Match one HTML document and store new services.
	 *
	 * @param string               $html     Raw HTML or saved content.
	 * @param array<int, string>   $plugins  Active plugin slugs.
	 * @param array<string, mixed> $found    Accumulator.
	 * @param string               $where    Page label stored as evidence.
	 * @param string               $page_url Public URL of the page, when this step fetched one.
	 * @return array<int, string>
	 */
	private static function ingest_html( string $html, array $plugins, array &$found, string $where, string $page_url = '' ): array {
		self::$page_url   = $page_url;
		self::$page_title = $where;
		$names            = array();
		$corpus           = self::resource_corpus( self::normalize( self::strip_own_markup( $html ) ) );

		foreach ( ConsentGate_Catalog::entries() as $entry ) {
			if ( ! empty( $entry['always'] ) || 'woocommerce_essential' === $entry['key'] ) {
				continue;
			}
			$hit = self::match_entry( $entry, $corpus, $plugins );
			if ( ! $hit ) {
				continue;
			}
			$names[] = (string) $entry['name'];
			self::note_page( (string) $entry['key'] );
			if ( isset( $found[ $entry['key'] ] ) ) {
				continue;
			}
			$evidence = $hit['evidence'];
			if ( '' !== $where ) {
				$evidence .= ' · ' . $where;
			}
			self::record( $entry, $hit['source'], $evidence, $found );
		}

		foreach ( self::discover_unknown( $corpus, $found ) as $unknown ) {
			$key = (string) $unknown['service_key'];
			self::note_page( $key );
			if ( ! self::$observe ) {
				ConsentGate_Services::upsert( $unknown );
				if ( isset( self::$previous[ $key ] ) ) {
					$prev = self::$previous[ $key ];
					ConsentGate_Services::save_assignment(
						$key,
						(string) $prev['category'],
						(string) $prev['policy'],
						! empty( $prev['included'] ),
						false
					);
				}
			}
			$found[ $key ] = $unknown;
			$names[]       = (string) $unknown['name'];
		}

		return array_values( array_unique( array_filter( $names ) ) );
	}

	/**
	 * Save Google IDs and close the scan.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function finish_job( array $job ): array {
		if ( ! empty( $job['observe'] ) ) {
			self::report_observed( $job );
			delete_transient( self::job_key( true ) );
			$found = is_array( $job['found'] ?? null ) ? $job['found'] : array();
			$count = count( $found );

			return array(
				'done'    => true,
				'count'   => $count,
				'message' => sprintf(
					/* translators: %d: number of services */
					_n( '%d service checked.', '%d services checked.', $count, 'wiedenroth-technologies-consentgate' ),
					$count
				),
			);
		}

		self::persist_locations( $job );
		$google = is_array( $job['google'] ?? null ) ? $job['google'] : array();
		ConsentGate_Options::update(
			array(
				'last_scan_at'         => gmdate( 'c' ),
				'google_ids_ads'       => array_values( $google['ads'] ?? array() ),
				'google_ids_analytics' => array_values( $google['analytics'] ?? array() ),
				'google_ids_tags'      => array_values( $google['tags'] ?? array() ),
			)
		);
		ConsentGate_Options::mark_scanned();
		ConsentGate_Options::bump_banner_version();
		delete_transient( self::job_key( false ) );

		$found = is_array( $job['found'] ?? null ) ? $job['found'] : array();
		$count = count( $found );

		return array(
			'done'    => true,
			'count'   => $count,
			'google'  => $google,
			'message' => sprintf(
				/* translators: %d: number of services */
				_n( '%d service detected.', '%d services detected.', $count, 'wiedenroth-technologies-consentgate' ),
				$count
			),
		);
	}

	/**
	 * Write page lists from a finished manual scan.
	 *
	 * @param array<string, mixed> $job Job.
	 */
	private static function persist_locations( array $job ): void {
		$locations = is_array( $job['locations'] ?? null ) ? $job['locations'] : array();
		foreach ( $locations as $key => $bundle ) {
			if ( ! is_string( $key ) || ! is_array( $bundle ) ) {
				continue;
			}
			ConsentGate_Services::set_locations( $key, $bundle );
		}
	}

	/**
	 * Remember a public page for a service. Only the first three titles are kept.
	 *
	 * @param string $key Service key.
	 */
	private static function note_page( string $key ): void {
		$url = self::$page_url;
		if ( '' === $key || '' === $url ) {
			return;
		}
		if ( ! isset( self::$locations[ $key ] ) || ! is_array( self::$locations[ $key ] ) ) {
			self::$locations[ $key ] = array(
				'count' => 0,
				'pages' => array(),
			);
		}
		$pages = is_array( self::$locations[ $key ]['pages'] ?? null ) ? self::$locations[ $key ]['pages'] : array();
		foreach ( $pages as $page ) {
			if ( is_array( $page ) && ( $page['url'] ?? '' ) === $url ) {
				return;
			}
		}
		self::$locations[ $key ]['count'] = (int) ( self::$locations[ $key ]['count'] ?? 0 ) + 1;
		if ( count( $pages ) < 3 ) {
			$pages[] = array(
				'title' => '' !== self::$page_title ? self::$page_title : $url,
				'url'   => $url,
			);
		}
		self::$locations[ $key ]['pages'] = $pages;
	}

	/**
	 * Compare an observe scan with the stored services and notify on a real diff.
	 *
	 * @param array<string, mixed> $job Job.
	 */
	private static function report_observed( array $job ): void {
		$previous = is_array( $job['previous'] ?? null ) ? $job['previous'] : array();
		$found    = is_array( $job['found'] ?? null ) ? $job['found'] : array();
		$added    = array();
		$removed  = array();

		foreach ( $found as $key => $row ) {
			if ( ! is_string( $key ) || isset( $previous[ $key ] ) || ! is_array( $row ) || ! empty( $row['locked'] ) ) {
				continue;
			}
			$added[] = (string) ( $row['name'] ?? $key );
		}

		foreach ( $previous as $key => $row ) {
			if ( ! is_string( $key ) || isset( $found[ $key ] ) || ! is_array( $row ) ) {
				continue;
			}
			if ( ! empty( $row['locked'] ) || 'manual' === (string) ( $row['source'] ?? '' ) ) {
				continue;
			}
			$removed[] = (string) ( $row['name'] ?? $key );
		}

		sort( $added );
		sort( $removed );
		ConsentGate_Options::update( array( 'last_weekly_scan_at' => gmdate( 'c' ) ) );

		if ( ! empty( $job['incomplete'] ) ) {
			if ( array() === $added ) {
				return;
			}
			$removed = array();
		}

		if ( array() === $added && array() === $removed ) {
			delete_option( 'consentgate_scan_notice' );
			return;
		}

		update_option(
			'consentgate_scan_notice',
			array(
				'added'   => $added,
				'removed' => $removed,
				'at'      => gmdate( 'c' ),
			),
			false
		);
		self::send_change_mail( $added, $removed );
	}

	/**
	 * Email the site admin when the weekly check sees new or missing services.
	 *
	 * @param array<int, string> $added   New service names.
	 * @param array<int, string> $removed Service names no longer found.
	 */
	private static function send_change_mail( array $added, array $removed ): void {
		$to = sanitize_email( (string) ConsentGate_Options::get( 'scan_notify_email', '' ) );
		if ( ! is_email( $to ) ) {
			$to = sanitize_email( (string) get_option( 'admin_email' ) );
		}
		if ( ! is_email( $to ) ) {
			return;
		}

		$lines = array(
			__( 'Wiedenroth Technologies ConsentGate checked the public pages and found a change. Nothing was changed automatically.', 'wiedenroth-technologies-consentgate' ),
			'',
		);
		if ( array() !== $added ) {
			$lines[] = __( 'New:', 'wiedenroth-technologies-consentgate' );
			foreach ( $added as $name ) {
				$lines[] = '- ' . $name;
			}
			$lines[] = '';
		}
		if ( array() !== $removed ) {
			$lines[] = __( 'No longer found:', 'wiedenroth-technologies-consentgate' );
			foreach ( $removed as $name ) {
				$lines[] = '- ' . $name;
			}
			$lines[] = '';
		}
		$lines[] = admin_url( 'admin.php?page=' . CONSENTGATE_SLUG . '&tab=services' );

		wp_mail(
			$to,
			sprintf(
				/* translators: %s: site name */
				__( '[%s] Wiedenroth Technologies ConsentGate found a change', 'wiedenroth-technologies-consentgate' ),
				wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES )
			),
			implode( "\n", $lines )
		);
	}

	/**
	 * Continue the weekly check for a few pages. Services, categories and the banner stay as they are.
	 */
	public static function weekly_tick(): void {
		if ( ! ConsentGate_Options::get( 'scan_weekly', true ) ) {
			return;
		}
		$running = false !== get_transient( self::job_key( true ) );
		if ( ! $running && doing_action( 'consentgate_weekly_scan_continue' ) ) {
			return;
		}
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- a few pages per tick.
			set_time_limit( 60 );
		}
		if ( false === get_transient( self::job_key( true ) ) ) {
			self::begin( true );
		}
		for ( $i = 0; $i < 5; $i++ ) {
			$step = self::advance( true );
			if ( ! empty( $step['done'] ) ) {
				return;
			}
		}
		if ( ! wp_next_scheduled( 'consentgate_weekly_scan_continue' ) ) {
			wp_schedule_single_event( time() + ( 2 * MINUTE_IN_SECONDS ), 'consentgate_weekly_scan_continue' );
		}
	}

	/**
	 * Keep the weekly cron in step with the setting.
	 */
	public static function sync_weekly_schedule(): void {
		$enabled = (bool) ConsentGate_Options::get( 'scan_weekly', true );
		$next    = wp_next_scheduled( 'consentgate_weekly_scan' );
		if ( $enabled && ! $next ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', 'consentgate_weekly_scan' );
		}
		if ( $enabled ) {
			return;
		}
		if ( $next ) {
			wp_unschedule_event( $next, 'consentgate_weekly_scan' );
		}
		$continue = wp_next_scheduled( 'consentgate_weekly_scan_continue' );
		if ( $continue ) {
			wp_unschedule_event( $continue, 'consentgate_weekly_scan_continue' );
		}
		delete_transient( self::job_key( true ) );
	}

	/**
	 * Pages to open, homepage first, then pages that already mention embeds.
	 *
	 * @return array<int, array{kind: string, title: string, url: string}>
	 */
	private static function scan_queue(): array {
		$items = array(
			array(
				'kind'  => 'local',
				'title' => __( 'Saved content', 'wiedenroth-technologies-consentgate' ),
				'url'   => '',
			),
		);
		$seen  = array();
		$push  = static function ( string $url, string $title ) use ( &$items, &$seen ): void {
			if ( '' === $url || isset( $seen[ $url ] ) ) {
				return;
			}
			$seen[ $url ] = true;
			$items[]      = array(
				'kind'  => 'url',
				'title' => '' !== $title ? $title : $url,
				'url'   => $url,
			);
		};

		$push( home_url( '/' ), __( 'Homepage', 'wiedenroth-technologies-consentgate' ) );
		$privacy = ConsentGate_Options::resolved_privacy_url();
		if ( '' !== $privacy ) {
			$push( $privacy, __( 'Privacy policy', 'wiedenroth-technologies-consentgate' ) );
		}

		foreach ( self::priority_posts() as $post ) {
			$link = get_permalink( $post );
			if ( is_string( $link ) ) {
				$push( $link, get_the_title( $post ) );
			}
		}

		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'], $types['page'] );
		$page_ids = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);
		foreach ( is_array( $page_ids ) ? $page_ids : array() as $id ) {
			$link = get_permalink( (int) $id );
			if ( is_string( $link ) ) {
				$push( $link, get_the_title( (int) $id ) );
			}
		}

		if ( array() !== $types ) {
			$ids = get_posts(
				array(
					'post_type'        => array_values( $types ),
					'post_status'      => 'publish',
					'posts_per_page'   => 200,
					'fields'           => 'ids',
					'orderby'          => 'modified',
					'order'            => 'DESC',
					'no_found_rows'    => true,
					'suppress_filters' => false,
				)
			);
			foreach ( is_array( $ids ) ? $ids : array() as $id ) {
				$link = get_permalink( (int) $id );
				if ( is_string( $link ) ) {
					$push( $link, get_the_title( (int) $id ) );
				}
			}
		}

		return $items;
	}

	/**
	 * Posts whose saved content already mentions a map or Linexo.
	 *
	 * @return array<int, WP_Post>
	 */
	private static function priority_posts(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type NOT IN ('attachment','revision','nav_menu_item') AND (post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s) LIMIT 40",
				'%linexo%',
				'%maps.apple%',
				'%apple-mapkit%',
				'%mapkit.js%',
				'%maps.google%',
				'%maps.googleapis%'
			)
		);
		$posts = array();
		foreach ( is_array( $ids ) ? $ids : array() as $id ) {
			$post = get_post( (int) $id );
			if ( $post instanceof WP_Post ) {
				$posts[] = $post;
			}
		}

		return $posts;
	}

	/**
	 * Persist a catalog hit.
	 *
	 * @param array<string, mixed>      $entry    Catalog entry.
	 * @param string                    $source   Source.
	 * @param string                    $evidence Evidence.
	 * @param array<string, mixed>      $found    Accumulator.
	 */
	private static function record( array $entry, string $source, string $evidence, array &$found ): void {
		$row = array(
			'service_key'        => $entry['key'],
			'name'               => $entry['name'],
			'source'             => $source,
			'evidence'           => $evidence,
			'suggested_category' => $entry['category'],
			'suggested_policy'   => $entry['policy'],
			'locked'             => ! empty( $entry['locked'] ),
			'included'           => true,
			'status'             => 'detected',
		);
		if ( self::$observe ) {
			$found[ $entry['key'] ] = $row;
			return;
		}
		ConsentGate_Services::upsert( $row );
		if ( isset( self::$previous[ $entry['key'] ] ) && empty( $entry['locked'] ) ) {
			$prev = self::$previous[ $entry['key'] ];
			ConsentGate_Services::save_assignment(
				$entry['key'],
				(string) $prev['category'],
				(string) $prev['policy'],
				! empty( $prev['included'] ),
				false
			);
		}
		$found[ $entry['key'] ] = $row;
	}

	/**
	 * Scripts, stylesheets, frames and images that the browser actually loads.
	 * Plain links in the page text are left out.
	 *
	 * @param string $html Normalized HTML.
	 */
	private static function resource_corpus( string $html ): string {
		$parts = array();

		if ( preg_match_all( '#<script\b([^>]*)>(.*?)</script>#is', $html, $scripts, PREG_SET_ORDER ) ) {
			foreach ( $scripts as $script ) {
				$attrs = (string) $script[1];
				if ( preg_match( '#\btype\s*=\s*["\']application/(?:ld\+)?json["\']#i', $attrs ) ) {
					continue;
				}
				foreach ( array( 'src', 'data-src' ) as $name ) {
					$value = self::attr_value( $attrs, $name );
					if ( '' !== $value ) {
						$parts[] = $value;
					}
				}
				$body = trim( (string) $script[2] );
				if ( '' !== $body ) {
					$parts[] = $body;
				}
			}
		}

		if ( preg_match_all( '#<(iframe|img|embed|source|video|audio)\b[^>]*>#i', $html, $tags ) ) {
			foreach ( $tags[0] as $tag ) {
				foreach ( array( 'src', 'data-src', 'data-lazy-src', 'poster' ) as $name ) {
					$value = self::attr_value( $tag, $name );
					if ( '' !== $value ) {
						$parts[] = $value;
					}
				}
				$srcset = self::attr_value( $tag, 'srcset' );
				if ( '' !== $srcset ) {
					foreach ( preg_split( '/\s*,\s*/', $srcset ) ?: array() as $candidate ) {
						$url = preg_split( '/\s+/', trim( (string) $candidate ) )[0] ?? '';
						if ( is_string( $url ) && '' !== $url ) {
							$parts[] = $url;
						}
					}
				}
			}
		}

		if ( preg_match_all( '#<link\b[^>]*>#i', $html, $links ) ) {
			foreach ( $links[0] as $tag ) {
				$rel = self::attr_value( $tag, 'rel' );
				if ( ! preg_match( '/stylesheet|preload|modulepreload/', $rel ) ) {
					continue;
				}
				$href = self::attr_value( $tag, 'href' );
				if ( '' !== $href ) {
					$parts[] = $href;
				}
			}
		}

		if ( preg_match_all( '#<object\b[^>]*>#i', $html, $objects ) ) {
			foreach ( $objects[0] as $tag ) {
				$data = self::attr_value( $tag, 'data' );
				if ( '' !== $data ) {
					$parts[] = $data;
				}
			}
		}

		if ( preg_match_all( '#wp-block-embed__wrapper[^>]*>\s*(https?://[^\s<]+)#i', $html, $embeds ) ) {
			foreach ( $embeds[1] as $url ) {
				$parts[] = (string) $url;
			}
		}

		if ( preg_match_all( '#\[embed[^\]]*\]\s*(https?://[^\s\]]+)#i', $html, $embeds ) ) {
			foreach ( $embeds[1] as $url ) {
				$parts[] = (string) $url;
			}
		}

		if ( preg_match_all( '#<style\b[^>]*>(.*?)</style>#is', $html, $styles ) ) {
			foreach ( $styles[1] as $css ) {
				if ( ! preg_match_all( '#url\(\s*["\']?([^"\')\s]+)#i', (string) $css, $urls ) ) {
					continue;
				}
				foreach ( $urls[1] as $url ) {
					if ( ! str_starts_with( (string) $url, 'data:' ) ) {
						$parts[] = (string) $url;
					}
				}
			}
		}

		if ( preg_match_all( '/linexo-site-connect|linexo-siteconnect|apple-mapkit|maps\.apple\.com/i', $html, $marks ) ) {
			foreach ( $marks[0] as $mark ) {
				$parts[] = (string) $mark;
			}
		}

		$parts = array_values(
			array_filter(
				$parts,
				static function ( $part ): bool {
					$part = trim( (string) $part );
					if ( '' === $part ) {
						return false;
					}
					return ! str_starts_with( $part, 'data:' )
						&& ! str_starts_with( $part, 'blob:' )
						&& ! str_starts_with( $part, 'javascript:' );
				}
			)
		);

		return implode( "\n", $parts );
	}

	/**
	 * Read one HTML attribute.
	 *
	 * @param string $tag  Tag or attribute string.
	 * @param string $name Attribute name.
	 */
	private static function attr_value( string $tag, string $name ): string {
		$pattern = '#\b' . preg_quote( $name, '#' ) . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))#i';
		if ( ! preg_match( $pattern, $tag, $match ) ) {
			return '';
		}

		$value = '' !== ( $match[1] ?? '' ) ? $match[1] : ( '' !== ( $match[2] ?? '' ) ? $match[2] : (string) ( $match[3] ?? '' ) );

		return html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Match one catalog entry against loaded resources and plugins.
	 *
	 * @param array<string, mixed> $entry   Entry.
	 * @param string               $html    Scan corpus HTML.
	 * @param array<int, string>   $plugins Active plugin slugs.
	 * @return array{source: string, evidence: string}|null
	 */
	private static function match_entry( array $entry, string $html, array $plugins ): ?array {
		foreach ( $entry['plugin_slugs'] as $slug ) {
			if ( in_array( $slug, $plugins, true ) ) {
				return array(
					'source'   => 'plugin',
					'evidence' => $slug,
				);
			}
		}

		foreach ( $entry['hosts'] as $host ) {
			$host = str_replace( '*', '', (string) $host );
			if ( '' !== $host && str_contains( $html, strtolower( $host ) ) ) {
				return array(
					'source'   => 'html',
					'evidence' => $host,
				);
			}
		}

		foreach ( $entry['patterns'] as $pattern ) {
			if ( @preg_match( $pattern, $html, $m ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return array(
					'source'   => 'html',
					'evidence' => isset( $m[0] ) ? substr( (string) $m[0], 0, 180 ) : $pattern,
				);
			}
		}

		return null;
	}

	/**
	 * Discover unknown third-party hosts.
	 *
	 * @param string                    $html  HTML.
	 * @param array<string, mixed>      $known Already found.
	 * @return array<int, array<string, mixed>>
	 */
	private static function discover_unknown( string $html, array $known ): array {
		$home  = wp_parse_url( home_url(), PHP_URL_HOST );
		$home  = is_string( $home ) ? strtolower( preg_replace( '/^www\./', '', $home ) ?? $home ) : '';
		$out   = array();
		$seen  = array();

		if ( ! preg_match_all( '#https?://[^\s"\'<>]+#i', $html, $matches ) ) {
			return $out;
		}

		foreach ( $matches[0] as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( ! is_string( $host ) || '' === $host ) {
				continue;
			}
			$host = strtolower( preg_replace( '/^www\./', '', $host ) ?? $host );
			if ( self::is_site_host( $host, $home ) || self::is_first_party( $host ) || ConsentGate_Catalog::covers( $url ) ) {
				continue;
			}
			$domain = self::registrable( $host );
			if ( isset( $seen[ $domain ] ) ) {
				continue;
			}
			$seen[ $domain ] = true;
			$key             = 'unknown_' . sanitize_key( str_replace( '.', '_', $domain ) );
			if ( isset( $known[ $key ] ) ) {
				self::note_page( $key );
				continue;
			}
			$out[] = array(
				'service_key'        => $key,
				'name'               => ucwords( str_replace( array( '-', '_' ), ' ', preg_replace( '/\.[a-z]{2,}$/i', '', $domain ) ?? $domain ) ),
				'source'             => 'html',
				'evidence'           => substr( $url, 0, 180 ),
				'suggested_category' => 'marketing',
				'suggested_policy'   => 'with_consent',
				'status'             => 'unknown',
			);
		}

		return $out;
	}

	/**
	 * Extract Google measurement IDs.
	 *
	 * @param string $html HTML.
	 * @return array{ads: array<int, string>, analytics: array<int, string>, tags: array<int, string>}
	 */
	private static function extract_google_ids( string $html ): array {
		$ads = array();
		$ga  = array();
		$gtm = array();

		if ( preg_match_all( '/\bAW-\d{6,}\b/', $html, $m ) ) {
			$ads = array_values( array_unique( $m[0] ) );
		}
		if ( preg_match_all( '/\bG-[A-Z0-9]{6,}\b/', $html, $m ) ) {
			$ga = array_values( array_unique( $m[0] ) );
		}
		if ( preg_match_all( '/\bGTM-[A-Z0-9]+\b/', $html, $m ) ) {
			$gtm = array_values( array_unique( $m[0] ) );
		}

		return array(
			'ads'       => $ads,
			'analytics' => $ga,
			'tags'      => $gtm,
		);
	}

	/**
	 * Live HTML from the homepage, privacy policy and typical contact slugs.
	 */
	private static function fetch_front_html(): string {
		$html = '';
		$urls = array_merge( array( home_url( '/' ) ), self::urls_mentioning_maps() );

		$privacy = ConsentGate_Options::resolved_privacy_url();
		if ( '' !== $privacy ) {
			$urls[] = $privacy;
		}

		$slugs = array( 'kontakt', 'contact', 'impressum', 'standort', 'filiale', 'anfahrt', 'ueber-uns', 'about', 'store-locator' );
		foreach ( $slugs as $slug ) {
			foreach ( self::posts_named( $slug ) as $page ) {
				$urls[] = get_permalink( $page );
			}
		}

		$urls = array_values( array_unique( array_filter( $urls ) ) );
		foreach ( array_slice( $urls, 0, 4 ) as $url ) {
			$html .= "\n" . self::fetch_url( (string) $url );
		}

		return $html;
	}

	/**
	 * Permalinks of published pages/posts that look like they embed a map.
	 *
	 * @return array<int, string>
	 */
	private static function urls_mentioning_maps(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_status IN ('publish','private')
			AND post_type IN ('page','post')
			AND (
				post_content LIKE '%maps.google%'
				OR post_content LIKE '%maps/embed%'
				OR post_content LIKE '%maps.googleapis%'
				OR post_content LIKE '%google.com%maps%'
				OR post_content LIKE '%google.maps%'
				OR post_content LIKE '%maps.app.goo.gl%'
			)
			LIMIT 6"
		);

		$urls = array();
		foreach ( is_array( $ids ) ? $ids : array() as $id ) {
			$permalink = get_permalink( (int) $id );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				$urls[] = $permalink;
			}
		}

		return $urls;
	}

	/**
	 * Published posts/pages (including nested slugs) plus widgets.
	 */
	private static function content_corpus(): string {
		global $wpdb;

		$needles = array(
			'iframe',
			'youtube',
			'youtu.be',
			'vimeo',
			'maps.google',
			'maps/embed',
			'maps.googleapis',
			'google.com/maps',
			'google.com\\/maps',
			'google.de/maps',
			'google.maps',
			'goo.gl/maps',
			'maps.app.goo.gl',
			'recaptcha',
			'doubleclick',
			'googletagmanager',
			'facebook.net',
			'hs-scripts',
			'js.stripe.com',
			'hotjar',
			'clarity.ms',
		);

		$params = array();
		foreach ( $needles as $needle ) {
			$params[] = '%' . $wpdb->esc_like( $needle ) . '%';
		}

		$types = array_values(
			array_unique(
				array_merge(
					get_post_types( array( 'public' => true ) ),
					array( 'wp_block', 'wp_template', 'wp_template_part', 'elementor_library' )
				)
			)
		);
		$types = array_values( array_filter( $types, 'is_string' ) );
		$out   = '';
		$max   = 800000;

		$remaining = 80;
		foreach ( $types as $type ) {
			if ( $remaining < 1 || strlen( $out ) >= $max ) {
				break;
			}
			$args = array_merge( array( $type ), $params, array( $remaining ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_content FROM {$wpdb->posts} WHERE post_status IN ('publish','private') AND post_type = %s AND (post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s) LIMIT %d",
					$args[0],
					$args[1],
					$args[2],
					$args[3],
					$args[4],
					$args[5],
					$args[6],
					$args[7],
					$args[8],
					$args[9],
					$args[10],
					$args[11],
					$args[12],
					$args[13],
					$args[14],
					$args[15],
					$args[16],
					$args[17],
					$args[18],
					$args[19],
					$args[20],
					$args[21],
					$args[22]
				)
			);
			$rows = is_array( $rows ) ? $rows : array();
			$remaining -= count( $rows );
			foreach ( $rows as $content ) {
				if ( strlen( $out ) >= $max ) {
					break;
				}
				$out .= "\n" . substr( (string) $content, 0, 120000 );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_status IN ('publish','private') AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s) LIMIT 40",
				'%maps.google%',
				'%maps/embed%',
				'%maps.googleapis%',
				'%google.com%maps%',
				'%google.maps%',
				'%maps.app.goo.gl%',
				'%youtube.com%',
				'%player.vimeo.com%'
			)
		);
		foreach ( is_array( $meta_rows ) ? $meta_rows : array() as $meta ) {
			if ( strlen( $out ) >= $max ) {
				break;
			}
			$out .= "\n" . substr( (string) $meta, 0, 120000 );
		}

		return $out . self::widget_corpus() . self::needle_content();
	}

	/**
	 * Text/HTML widgets.
	 */
	private static function widget_corpus(): string {
		$out = '';
		foreach ( array( 'widget_block', 'widget_text', 'widget_custom_html' ) as $option ) {
			$widgets = get_option( $option, array() );
			if ( ! is_array( $widgets ) ) {
				continue;
			}
			foreach ( $widgets as $widget ) {
				if ( ! is_array( $widget ) ) {
					continue;
				}
				if ( ! empty( $widget['content'] ) ) {
					$out .= "\n" . (string) $widget['content'];
				}
				if ( ! empty( $widget['text'] ) ) {
					$out .= "\n" . (string) $widget['text'];
				}
			}
		}

		return $out;
	}

	/**
	 * Saved content that mentions Linexo or Apple Maps.
	 */
	private static function needle_content(): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_content FROM {$wpdb->posts} WHERE post_status IN ('publish','private') AND (post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s) LIMIT 40",
				'%linexo%',
				'%maps.apple%',
				'%apple-mapkit%',
				'%mapkit.js%'
			)
		);
		$out  = '';
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out .= "\n" . substr( (string) $row, 0, 120000 );
		}

		return $out;
	}

	/**
	 * Published pages/posts with this slug, including nested URLs.
	 *
	 * @param string $slug Slug.
	 * @return array<int, WP_Post>
	 */
	private static function posts_named( string $slug ): array {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'no_found_rows'  => true,
			)
		);

		return array_values( array_filter( $posts, static fn( $post ) => $post instanceof WP_Post ) );
	}

	/**
	 * Fetch one URL as HTML.
	 *
	 * @param string $url URL.
	 */
	private static function fetch_url( string $url ): string {
		$res = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 2,
				'sslverify'   => true,
				'headers'     => array(
					'User-Agent'         => 'ConsentGateScan/' . CONSENTGATE_VERSION,
					'X-ConsentGate-Scan' => ConsentGate_Frontend::create_scan_token(),
					'Cache-Control'      => 'no-cache, no-store, must-revalidate',
					'Pragma'             => 'no-cache',
				),
			)
		);

		if ( is_wp_error( $res ) ) {
			return '';
		}

		$body = wp_remote_retrieve_body( $res );

		return is_string( $body ) ? $body : '';
	}

	/**
	 * Decode HTML entities and JSON-escaped slashes so catalog hosts match.
	 *
	 * @param string $html Raw HTML or JSON.
	 */
	private static function normalize( string $html ): string {
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$html = str_replace( array( '\\/', '\\u002f', '\\u002F' ), '/', $html );
		$html = stripslashes( $html );

		return strtolower( $html );
	}

	/**
	 * Remove ConsentGate's own markup so the catalog does not match itself.
	 *
	 * @param string $html HTML.
	 */
	private static function strip_own_markup( string $html ): string {
		$html = preg_replace( '#<script[^>]*id="consentgate-[^"]*"[^>]*>[\s\S]*?</script>#i', '', $html ) ?? $html;
		$html = preg_replace( '#<link[^>]+consentgate[^>]*>#i', '', $html ) ?? $html;
		$html = preg_replace( '#<script[^>]+consent-gate\.js[^>]*>.*?</script>#i', '', $html ) ?? $html;

		return $html;
	}

	/**
	 * Active plugin directory slugs.
	 *
	 * @return array<int, string>
	 */
	private static function active_plugin_slugs(): array {
		$slugs = array();
		foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
			$slugs[] = dirname( (string) $plugin );
		}
		if ( is_multisite() ) {
			foreach ( array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) as $plugin ) {
				$slugs[] = dirname( (string) $plugin );
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Host belongs to this site, including its subdomains.
	 *
	 * @param string $host Host without a leading www.
	 * @param string $home Site host without a leading www.
	 */
	private static function is_site_host( string $host, string $home ): bool {
		if ( '' === $host || '' === $home ) {
			return false;
		}

		return $host === $home || str_ends_with( $host, '.' . $home );
	}

	/**
	 * First-party host?
	 *
	 * @param string $host Host.
	 */
	private static function is_first_party( string $host ): bool {
		foreach ( self::first_party() as $suffix ) {
			if ( $host === $suffix || str_ends_with( $host, '.' . $suffix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Registrable domain.
	 *
	 * @param string $host Host.
	 */
	private static function registrable( string $host ): string {
		$parts = explode( '.', $host );
		if ( count( $parts ) <= 2 ) {
			return $host;
		}

		return implode( '.', array_slice( $parts, -2 ) );
	}
}
