<?php 
$id = $post->ID;
$link = get_permalink($id);
$video_data = VHub_Video::get_video_data($id);
?>
<div class="crbh-row <?php echo ($loop_id+1)%2==0 ? 'dd' : '' ?>" data-href="<?php echo $link ?>" >
	<div class="crbh-copy" >
		<?php  
		if ($video_data['thumbnail']) {
			$time = $video_data['time'];
			?>
			<div class="crbh-img">
				<a href="<?php echo $link ?>">
					<img src="<?php echo $video_data['thumbnail'] ?>" alt="" />
				</a>
				<span>
					<?php  
					if ($time['hours'] > 0) {
						echo ($time['hours']<10 ? '0' . $time['hours'] : $time['hours']) . ':';
					}


					if ($time['minutes'] > 0) {
						echo ($time['minutes']<10 ? '0' . $time['minutes'] : $time['minutes']) . ':';
					}else{
						echo '00:';
					}

					echo $time['seconds']<10 ? '0' . $time['seconds'] : $time['seconds'];
					?>
				</span>
			</div>
			<?php
		}
		echo '<p><a href="' . $link . '"><strong>' . $video_data['title']  . '</strong></a></p>';

		echo wpautop( $video_data['short_description'] );

		// add block video button
		include( $this->get_plugin_path('includes/block_video_link.php') );
		?>

		<span 
			class="st_sharethis_custom crbh-sharehis" 
			st_title="<?php echo esc_attr( $post->post_title ); ?>" 
			st_url="<?php echo $link; ?>" 
			displayText="<?php echo esc_attr( $video_data['short_description'] ); ?>" 
		>ShareThis</span>
	</div>
	<div class="crbh-clearfix">&nbsp;</div>
</div>