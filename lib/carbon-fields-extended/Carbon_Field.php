<?php 

if ( class_exists('Carbon_Field_Select') ) {
	class Carbon_Field_Select_Extended_VH extends Carbon_Field_Select {

		protected $tooltip = null;

		function tooltip( $tooltip = '' ) {
			$this->tooltip = $tooltip;
			return $this;
		}

		function get_tooltip() {
			return $this->tooltip;
		}

		function render() {

			if ( empty($this->options) ) {
				echo '<em>' . __('no options', 'crb') . '</em>';
				return;
			}

			echo '<select id="' . $this->get_id() . '" name="' . $this->get_name() . '" ' . ($this->required ? 'data-carbon-required="true"': '') . '>';

			foreach ($this->options as $key => $value) {
				echo '<option value="' . htmlentities($key, ENT_COMPAT, 'UTF-8') . '"';

				if ($this->value == $key) {
					echo ' selected="selected"';
				}

				echo '>' . htmlentities($value, ENT_COMPAT, 'UTF-8') . '</option>';
			}

			echo '</select>';

			if ( $this->get_tooltip() ) {
				echo '<span class="crbh-tooltips-btn">?</span>';

				echo '<div class="crbh-tooltips crbh-hidden"><div>' . $this->get_tooltip() . '</div></div>';
			}
		}
	}
}
