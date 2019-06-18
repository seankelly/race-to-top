<?php
/**
 * @package race-to-top
 * @version 1.0
 */
/*
Plugin Name: Race to Top
Plugin URI:
Description: Widget to display race to top.
Author: Sean Kelly
Version: 1.0
Author URI:
*/

namespace WGOM;

defined('ABSPATH') or die("This file must be used with WordPress.");

class RaceToTop extends \WP_Widget {
	private $DISPLAY_COUNT = 3;

	public function __construct() {
		$widget_ops = array(
			'classname' => 'Race to Top',
			'description' => 'Display race to top countdown'
		);

		parent::__construct('race-to-top', 'Race to Top', $widget_ops);
	}

	public function form($instance) {
		$defaults = array(
			'title' => 'Race to Top',
			'count' => 0,
			'description' => '',
			'leaderboard' => ''
		);

		$instance = \wp_parse_args((array) $instance, $defaults);
		$leaderboard = $this->reverse_leaderboard($instance['leaderboard']);
	?>
	<p>
	<label for="<?php echo $this->get_field_id('title'); ?>">Title</label>
	<input id="<?php echo $this->get_field_id('title'); ?>" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
	</p>
	<p>
	<label for="<?php echo $this->get_field_id('count'); ?>">Count</label>
	<input id="<?php echo $this->get_field_id('count'); ?>" class="widefat" name="<?php echo $this->get_field_name('count'); ?>" value="<?php echo $instance['count']; ?>" />
	</p>
	<p>
	<label for="<?php echo $this->get_field_id('description'); ?>">Description</label>
	<input id="<?php echo $this->get_field_id('description'); ?>" class="widefat" name="<?php echo $this->get_field_name('description'); ?>" value="<?php echo $instance['description']; ?>" />
	</p>
	<p>Number,Description</p>
	<p>
	<textarea id="<?php echo $this->get_field_id('leaderboard'); ?>" name="<?php echo $this->get_field_name('leaderboard'); ?>" class="widefat" cols="15" rows="20"><?php echo esc_textarea($leaderboard); ?></textarea>
	<?php
	}

	public function update($new_instance, $old_instance) {
		$title = strip_tags($new_instance['title']);
		$count = intval(strip_tags($new_instance['count']));
		$description = sanitize_text_field($new_instance['description']);
		$csv_leaderboard = strip_tags($new_instance['leaderboard']);
		$leaderboard = $this->parse_leaderboard($csv_leaderboard);

		$instance = array(
			'title' => $title,
			'count' => $count,
			'description' => $description,
			'leaderboard' => $leaderboard
		);
		return $instance;
	}

	public function widget($args, $instance) {
		$title = apply_filters('widget_title', $instance['title']);
		$content = $this->generate($instance);

		extract($args, EXTR_SKIP);
		echo $before_widget;
		if ($title) {
			echo $before_title . $title . $after_title;
		}
		echo $content;
		echo $after_widget;
	}

	// Find up to five entries higher than current number.
	private function generate($instance) {
		$count = $instance['count'];
		$rleaderboard = array_reverse($instance['leaderboard']);

		$display_leaderboard = array();
		$include = false;
		$ranking = 1;
		$highest_count = -1;
		foreach ($rleaderboard as $rank) {
			if (!$include && $rank[0] > $count) {
				$include = true;
			}
			if ($include) {
				if (count($display_leaderboard) < $this->DISPLAY_COUNT) {
					$display_leaderboard[] = $rank;
					$highest_count = $rank[0];
				}
				else if ($rank[0] === $highest_count) {
					// If right at the edge, include any additional teams with the same count.
					$display_leaderboard[] = $rank;
				}
				$ranking++;
			}
		}

		$start_value = $ranking - count($display_leaderboard);
		$content = "<ol start=\"$start_value\">\n";
		$display_leaderboard = array_reverse($display_leaderboard);
		foreach ($display_leaderboard as $rank) {
			$content .= "<li>${rank[1]} (${rank[0]})</li>\n";
		}
		$content .= "<li>${instance['description']} ($count)</li>\n";
		$content .= "</ol>\n";
		return $content;
	}

	// Expand CSV leaderboard into an array.
	private function parse_leaderboard($csv_leaderboard) {
		$leaderboard = array();
		/*
		 * Input format:
		 *  N,Description
		 */
		$rows = str_getcsv($csv_leaderboard, "\n");
		foreach ($rows as &$row) {
			$len = strlen($row);
			if ($len === 0) {
				continue;
			}

			$fields = str_getcsv($row);
			$leaderboard[] = $fields;
		}
		return $leaderboard;
	}

	private function reverse_leaderboard($leaderboard) {
		$rows = array();
		foreach ($leaderboard as $rank) {
			$rows[] = "${rank[0]},${rank[1]}"; 
		}
		$csv = implode("\n", $rows);
		return $csv;
	}
}

\add_action('widgets_init', function(){
	\register_widget("WGOM\\RaceToTop");
});

?>
