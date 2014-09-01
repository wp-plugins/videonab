<?php  
$max_pages 		= $entries->max_num_pages;
$current_page	= isset($_GET['video_page']) ? $_GET['video_page'] : 1;

$current_order 	= isset($_GET['crbh_orderby']) ? $_GET['crbh_orderby'] : false;
$toggle_link 	= remove_query_arg( 'video_page', add_query_arg(array('crbh_orderby' => ($current_order=='popular' ? 'default' : 'popular'))) );

if ( $max_pages > 1 ) : ?>
	<div class="crbh-row crbh-row-pagination <?php echo $class ?> <?php echo $max_pages > 1 ? '' : 'crbh-no-pagination' ?>">

		<div class="crbh-pagination <?php echo ($current_page < $max_pages && $current_page>1) ? 'two-buttons' : 'one-buttons' ?>">
			<ul>
				<?php
				for ($i=4; $i >= 1; $i--) { 
					if ( ($current_page-$i)>0 ) {
						echo '<li><a href="' . add_query_arg('video_page', ($current_page-$i) ) . '"><span>' . ($current_page-$i) . '</span></a></li>';
					}	
				}

				echo '<li class="active"><a><span>' . $current_page . '</span></a></li>';

				for ($i=1; $i <= 4; $i++) { 
					if ( ($current_page+$i)<=$max_pages ) {
						echo '<li><a href="' . add_query_arg('video_page', ($current_page+$i) ) . '"><span>' . ($current_page+$i) . '</span></a></li>';
					}	
				}
				?>
			</ul>
		</div>

		<?php  
		if ($current_page>1) {
			echo '<a class="crbh-prev" href="' . add_query_arg('video_page', ($current_page-1) ) . '" >Previous</a>';
		}
		?>

		<?php  
		if ($current_page < $max_pages) {
			echo '<a class="crbh-next" href="' . add_query_arg('video_page', ($current_page+1) ) . '" >Next</a>';
		}
		?>
	</div>
<?php endif; ?>