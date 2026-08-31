<?php
/**
 * Server-rendered weekly event grid.
 *
 * @package TrinitySiteFunctionality
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo trinity_site_render_weekly_events_grid(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
