<div class="crbh-videos crbh-videos-shortcode">
	<?php  
	if ( isset($entries) && $entries->have_posts() ) {
		global $post;

		$enable_pagination 	= get_option( $this->prefix . 'include_pagination' )=='Yes';

		if ($enable_pagination) {
			$class 			= 'top-nav';
			include( $this->get_plugin_path('includes/shortcode-pagination.php') );
		}
		?>
		<div class="crbh-list">
			<?php
			$loop_id 		= 0;
			while ($entries->have_posts()) {
				$entries->the_post();

					include( $this->get_plugin_path('includes/shortcode-loop.php') );
					
				$loop_id++;
			}
			?>
		</div>
		<?php

		if ($enable_pagination) {
			$class 			= 'bot-nav';
			include( $this->get_plugin_path('includes/shortcode-pagination.php') );
		}
	}
	?>
	<div class="crbh-clearfix">&nbsp;</div>
</div>