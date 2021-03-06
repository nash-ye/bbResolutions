<?php
/**
 * Recent topics widget class file.
 *
 * @package bbResolutions\Widgets
 * @since   0.4
 */
namespace bbResolutions\Widgets;

use WP_Query;
use WP_Widget;
use bbResolutions;
use bbResolutions\Manager;

/**
 * Recent topics widget class.
 *
 * @uses  WP_Widget
 * @since 0.4
 */
class Topics_Widget extends WP_Widget
{
    /**
     * Registers the topic widget
     *
     * @since 0.4
     */
    public function __construct()
    {
        parent::__construct(
            'bbr_topics_widget',
            __('(bbResolutions) Recent Topics', 'bbresolutions'),
            [
                'description' => __('A list of recent topics with an option to set the resolution.', 'bbresolutions'),
            ]
        );
    }

    /**
     * Displays the output, the topic list
     *
     * @since 0.4
     */
    public function widget($args = [], $instance = [])
    {
        $settings = $this->parse_settings($instance);

        $query_args = [
            'post_type'           => bbp_get_topic_post_type(),
            'posts_per_page'      => (int) $settings['max_shown'],
            'post_status'         => [bbp_get_public_status_id(), bbp_get_closed_status_id()],
            'post_parent'         => $settings['forum'],
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'order'               => 'DESC',
        ];

        $resolution = Manager::get_by_key($settings['resolution']);

        if (! empty($resolution)) {
            $query_args['meta_query'] = [
                [
                    'key'   => 'bbr_topic_resolution',
                    'value' => $resolution->value,
                ],
            ];
        }

        $query = new WP_Query($query_args);

        if (! $query->have_posts()) {
            return;
        }

        echo $args['before_widget'];

        $settings['title'] = apply_filters('widget_title', $settings['title'], $instance, $this->id_base);

        if (! empty($settings['title'])) {
            echo $args['before_title'] . $settings['title'] . $args['after_title'];
        } ?>

		<ul>
			<?php while ($query->have_posts()) : ?>
				<?php
					$query->the_post();
					$topic_id = bbp_get_topic_id($query->post->ID);
				?>
				<li>
					<a class="bbp-forum-title" href="<?php bbp_topic_permalink($topic_id) ?>"><?php bbp_topic_title($topic_id) ?></a>
					<?php
						if (! empty($settings['show_user'])) {
							$author_link = bbp_get_topic_author_link(
                                [
                                    'post_id' => $topic_id, 
                                    'type'    => 'both',
                                    'size'    => 14
                                ]
                            );
							printf(_x('by %1$s', 'widgets', 'bbresolutions'), '<span class="topic-author">' . $author_link . '</span>');
                        }
                    ?>
					<?php if (! empty($settings['show_date'])) : ?>
                    <div><?php bbp_topic_last_active_time($topic_id) ?></div>
					<?php endif; ?>
				</li>

			<?php endwhile; ?>
        </ul>

		<?php
		
		echo $args['after_widget'];

        // Reset the $post global
        wp_reset_postdata();
    }

    /**
     * Update the topic widget options
     *
     * @since 0.4
     */
    public function update($new_instance = [], $old_instance = [])
    {
        $instance = $old_instance;

        foreach (['title', 'forum', 'resolution'] as $field_name) {
            if (isset($new_instance[ $field_name ])) {
                $instance[ $field_name ] = strip_tags($new_instance[ $field_name ]);
            }
        }

        foreach (['show_user', 'show_date'] as $field_name) {
            $instance[ $field_name ] = isset($new_instance[ $field_name ]);
        }

        $instance['max_shown'] = (int) $new_instance['max_shown'];

        if (! is_numeric($instance['forum'])) {
            $instance['forum'] = 'any';
        }

        return $instance;
    }

    /**
     * Output the topic widget options form
     *
     * @since 0.4
     */
    public function form($instance = [])
    {
        $settings = $this->parse_settings($instance); ?>

		<p>
			<label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title:', 'bbresolutions') ?></label>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?>" value="<?php echo esc_attr($settings['title']) ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('forum') ?>"><?php _e('Forum ID:', 'bbresolutions') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('forum') ?>" name="<?php echo $this->get_field_name('forum') ?>" value="<?php echo esc_attr($settings['forum']) ?>" />
			<br />
			<small><?php _e('"0" to show only root - "any" to show all', 'bbresolutions') ?></small>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('resolution') ?>"><?php _e('Resolution:', 'bbresolutions') ?></label>
            <?php
                bbResolutions\resolutions_dropdown(
                    [
                        'id'        => $this->get_field_id('resolution'),
                        'name'      => $this->get_field_name('resolution'),
                        'selected'  => $settings['resolution'],
                        'show_none' => false,
                    ]
                );
            ?>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('max_shown') ?>"><?php _e('Maximum topics to show:', 'bbresolutions') ?></label>
			<input type="number" class="widefat" id="<?php echo $this->get_field_id('max_shown') ?>" name="<?php echo $this->get_field_name('max_shown') ?>" value="<?php echo esc_attr($settings['max_shown']) ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('show_user') ?>"><?php _e('Show topic author:', 'bbresolutions') ?></label>
			<input type="checkbox" id="<?php echo $this->get_field_id('show_user') ?>" name="<?php echo $this->get_field_name('show_user') ?>" <?php checked($settings['show_user']) ?> value="1" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('show_date') ?>"><?php _e('Show topic date:', 'bbresolutions') ?></label>
			<input type="checkbox" id="<?php echo $this->get_field_id('show_date') ?>" name="<?php echo $this->get_field_name('show_date') ?>" <?php checked($settings['show_date']) ?> value="1" />
		</p>

		<?php
    }

    /**
     * Merge the widget settings into defaults array.
     *
     * @since 0.4
     */
    public function parse_settings($instance = [])
    {
        return wp_parse_args(
            $instance,
            [
                'title'        => __('Recent Topics', 'bbresolutions'),
                'max_shown'    => 5,
                'show_date'    => false,
                'show_user'    => false,
                'forum'        => 'any',
                'resolution'   => '',
            ]
        );
    }
}
