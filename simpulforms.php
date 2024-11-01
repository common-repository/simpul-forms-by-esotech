<?php
/**
 * @package Simpul
 */
/*
Plugin Name: Simpul Forms by Esotech
Plugin URI: http://www.esotech.org
Description: This plugin is designed to provide the ability to create simple contact forms that send inquiries to an e-mail address.
Version: 1.03
Author: Alexander and Gregory Conroy
Author URI: http://www.esotech.org/about/people/
License: This code is released under the GPL licence version 3 or later, available here http://www.gnu.org/licenses/gpl.txt
*/

require_once( __DIR__ . "/includes/wp_mail_smtp.php");

$simpulForms = new simpulForms();

if( is_admin() ):
	add_action('init', array(&$simpulForms, 'execute') );
else:
	add_shortcode('simpul_forms', array(&$simpulForms, 'createShortCode') );
endif;

/**
 * Plugin Outline:
 * 1. Construct/Initialize
 * 2. Prepare E-mail
 * 3. Execute (Send)
 * 4. Backend
 * 5. Backend Scrubbing
 * 6. Styles and Scripts
 * 7. Helpers
 */

class simpulForms
{

	const PHONE_MAX_DIGITS = 15;
	const PHONE_MIN_DIGITS = 10;
	
	/////////////////////////////
	// 1. Construct/Initialize //
	/////////////////////////////
	
	public function __construct()
	{
		
		
		$this->prefix = "simpul_forms_";
		
		$this->options = array( 'to' 		=> array( 'type' => 'email',
														'atts' => array('class' => 'simpul-input') ),
								'cc' 		=> array( 'type' => 'email',
														'atts' => array('class' => 'simpul-input') ),
								'bcc' 		=> array( 'type' => 'email',
														'atts' => array('class' => 'simpul-input') ),
								'subject' 	=> array( 'type' => 'text',
														'atts' => array('class' => 'simpul-input') ),
								'body' 		=> array( 'type' => 'textarea',
														'atts' => array('class' => 'simpul-input', 'rows' => '5', 'cols' => '50') )
								);
			
	}
	
	public function initMenu()
	{
		
		if (function_exists('add_submenu_page')) 
		{
			
			add_options_page(__('Forms Configuration', 'simpulforms'),
							 __('Forms', 'simpulforms'),
							 'manage_options',
							 'simpulforms',
							 array( &$this, 'adminMenu' ) );
						 
		}
			
	}
	
	///////////////////////
	// 2. Prepare E-mail //
	///////////////////////
	
	public function getHeaders()
	{
		
		$this->fields = get_option( $this->prefix . 'fields', TRUE );
		
		// Grabs basic options.
		foreach($this->options as $field => $option) :
			
			$this->$field = get_option( $this->prefix . $field );
			
		endforeach;
		
		$this->fields = get_option( $this->prefix . 'fields', TRUE);
		
		// We're using the $this->fields array to check the type of each item in the $_POST array.
		foreach($this->fields as $field => $option) :
			
			// $value = $_POST[$this->prefix . $option['name']];
			
			// If there is a valid value, grab and store it.
			if( strlen($_POST[$this->prefix . $option['name']]) > 0 ) :
			
				$this->in[$option['name']] = $_POST[$this->prefix . $option['name']];
					
			endif;
			
		endforeach;
		
		$this->headers = 'Reply-To: ' . $this->in_from . "\r\n";
		if($this->cc) $this->headers .= 'Cc: ' . $this->cc . "\r\n";
		if($this->bcc) $this->headers .= 'Bcc: ' . $this->bcc . "\r\n";
		$this->headers .= 'content-type: text/html' . "\r\n";
		
	}
	
	public function getBody()
	{
		
		$this->number_of_fields = get_option( $this->prefix . 'number_of_fields', TRUE);
		
		$this->fields = get_option( $this->prefix . 'fields', TRUE);
		
		if($this->body)
			$this->message .= $this->body . '<br /><br />';
		
		foreach($this->in as $in_field => $in_value) :
			
				for($i = 0; $i < $this->number_of_fields; $i++):
				
					if( $this->fields[$i]['name'] == $in_field ) :
						$type = $this->fields[$i]['type'];
						break; // Once you find it, stop the loop to save effort.
					endif;
					
				endfor;
				
			if($type == 'textarea') :
				$this->message .= "(" . self::getLabel($in_field) . ") They left this message: <br /><em>" . $in_value . "</em><br /><br />";
			elseif($in_value == 1):
				$this->message .= self::getLabel($in_field) . ": Yes<br /><br />";
			else:
				$this->message .= self::getLabel($in_field) . ": " . self::getLabel($in_value) . "<br /><br />";
			endif;

		endforeach;
		
	}

	
	///////////////////////
	// 3. Execute (Send) //
	///////////////////////

	public function sendMail()
	{

		if( self::allRequiredFieldsSet() !== FALSE && self::validate() !== FALSE ) :
			self::getHeaders();
			self::getBody();
			wp_mail( $this->to, $this->subject, $this->message, $this->headers, $attachments ); // the actual function.
			
			unset($_POST);
			$_POST = array();
		
			return true;
		else :
			return false;
		endif;
	
	}
	
	public function execute()
	{
		
		add_action( 'admin_print_scripts', array( &$this, 'registerScripts' ) );
		
		add_action( 'admin_menu',array( &$this, 'initMenu' ) );
		
	}
		
	////////////////
	// 4. Backend //
	////////////////
	
	public function redirectTo( $page )
	{
		
		
		
	}
	
	public function createForm( )
	{
		
		$default_form = "";
		$default_form .= '<form action="" method="post"><table>';
		
		$this->number_of_fields = get_option( $this->prefix . 'number_of_fields', TRUE);
		
		$this->fields = get_option( $this->prefix . 'fields', TRUE);
		
		for($i = 0; $i < $this->number_of_fields; $i++):
			
			if( strpos($this->fields[$i]['values'], ',') !== FALSE ) :
				$options = explode(',', $this->fields[$i]['values']);
			endif;
			
			if( isset($this->fields[$i]['required']) ) $required = ' *'; else $required = '';
			$default_form .= self::createInput( $this->fields[$i]['type'], $this->prefix . $this->fields[$i]['name'], $_POST[$this->prefix . $this->fields[$i]['name']], '', $options, $required );
			
			
		endfor;
		
		$default_form .= self::createInput( 'submit', $this->prefix . 'submit', 'Submit' );
		
		$default_form .= "</table></form>";
	
		$default_form .= "<br />* denotes a required field";
		
		return $default_form;
			
	}
	
	public function adminMenu()
	{
		
		if($_POST['submit']) :
			self::saveAdminMenu();
			self::saveFields();
		endif;			
		?>
		<div class="wrap">
			<?php screen_icon(); echo "<h2>simpulForms Options</h2>"; ?>
		<h2>General Options</h2>
			<form method="post" action="">
				<?php
		//Create Basic Options
				echo '<table class="widefat" style="width: 800px;">';
				foreach($this->options as $name => $option) :
					$field = $this->prefix . $name;
					echo self::createInput( $option['type'], $field, get_option( $field ), $option['atts']);
				endforeach;
				
				echo self::createInput( 'text', $this->prefix . 'thank_you', get_option( $this->prefix . 'thank_you' ) );
				
				echo '</table>';
		//Create Form Options
				?>
		<h2>Form Options</h2>
				<table class="widefat" style="width: 800px;">
				
				<?php echo self::formFields(); ?>
				
				</table>
		</div>
		
		<?php
		echo self::createInput( 'submit', 'submit', 'Update');
		
		?></form><?php
		
	}
	public function formFields()
	{
		
		$this->number_of_fields = get_option( $this->prefix . 'number_of_fields', TRUE );
		
		$this->fields = get_option( $this->prefix . 'fields', TRUE );
		
		$form .= '<th>Number of Fields</th><td colspan="10">' . '<input type="text" size="4" name="' . $this->prefix . 'number_of_fields" value="' . $this->number_of_fields . '"></td>';
		
		for($i = 0; $i < $this->number_of_fields; $i++):
			$form .= '<tr>
						<th>Name</th><td>' . '<input type="text" size="8" name="' . $this->prefix . 'fields[' . $i . '][name]" value="' . $this->fields[$i]['name'] . '"></td>' .
						'<th>Field Type</th><td>';
					
					$form .= '<select class="simpul-form-type" id="simpul-form-type-' . $i. '" name="' . $this->prefix . 'fields[' . $i . '][type]">';
					
					$type_values = array('text','email','checkbox','dropdown','radio', 'textarea', 'phone');
					
					foreach($type_values as $value):
						
						if( $value == $this->fields[$i]['type'] ) $selected = 'selected'; else $selected = '';
						
						$form .= '<option value="' . $value . '" ' . $selected . '>' . self::getLabel( $value ) . '</option>';
						 
					endforeach;
					 
					$form .='</select></td>';
					
			$form .= '<th>Values</th><td><input class="simpul-form-values" id="simpul-form-values-' . $i . '" type="text" name="' . $this->prefix . 'fields[' . $i . '][values]" value="' . $this->fields[$i]['values'] . '"></td>';
			
			if($this->fields[$i]['required']) $checked = "checked"; else $checked = "";
			$form .= '<td><input type="checkbox" value="1" name="' . $this->prefix . 'fields[' . $i . '][required]" ' . $checked . '> Required</td>' .   
					'
					</tr>';
		endfor;
		
		return $form;
		
	}
	public function saveFields()
	{
		
		$fields = (array) $_POST[$this->prefix . 'fields'];
		update_option($this->prefix . 'fields', $fields );
		
	}
	public function saveAdminMenu()
	{
		
		foreach($this->options as $field => $option):
			$field = $this->prefix . $field;
			
			if($option['type'] == 'email') 
				$newValue = trim( str_replace(" ", "", $_POST[$field] ) );
			else 
				$newValue = $_POST[$field];
			
			if($option['type'] == 'email') :
				
				$arrayValue = array();
				if(strpos($newValue, ',') !== FALSE):
					$arrayValue = explode(',',$newValue);
				else :
					$arrayValue = (array) $newValue;
				endif;
				
				$newArrayValue = array();
				
				foreach($arrayValue as $value) :
					
					$result = filter_var($value, FILTER_VALIDATE_EMAIL);
				
					if($result) :
						$newArrayValue[] = $result;
					elseif( $value === ""):
						$newArrayValue[] = "";
					else :
						$errorArray[] = self::getLabel($field) . " : " . $value . ' is not a valid E-mail. ';
					endif;
					
				endforeach;
				
				$newValue = implode(',', $newArrayValue);
				// In case there is a trailing comma, remove it.
				$newValue = rtrim($newValue, ',');
				
			endif; // if($type == 'email') :
			
			update_option($field, $newValue);
			
		endforeach; // foreach($this->options as $option => $type) :
		
		if($errorArray) :
					
			echo '<div class="simpul-forms-notice">';
			foreach($errorArray as $error) :
				echo $error;
			endforeach;
			echo '</div>';
			
		endif;
		
		update_option($this->prefix . 'thank_you', $_POST[$this->prefix . 'thank_you']);
		
		update_option($this->prefix . 'number_of_fields', $_POST[$this->prefix . 'number_of_fields']);

	}

	//////////////////////////
	// 5. Backend Scrubbing //
	//////////////////////////
	
	public function allRequiredFieldsSet()
	{
		
		$this->fields = get_option( $this->prefix . 'fields', TRUE);
			
		// We're using the $this->fields array to check the type of each item in the $_POST array.
		foreach($this->fields as $field => $option) :
			
			// $value = $_POST[$this->prefix . $option['name']];
			
			if( isset($_POST[$this->prefix .$option['name']]) && $option['required'] && strlen($_POST[$this->prefix . $option['name']]) < 1 ) :
			
				echo '<div class="simpul-forms-notice">Not all required fields are filled out!</div>';
				return false;
					
			endif;
			
		endforeach;
		
		return true;
		
	}
	
	public function validate()
	{
		
		$this->fields = get_option( $this->prefix . 'fields', TRUE);
			
		// We're using the $this->fields array to check the type of each item in the $_POST array.
		foreach($this->fields as $field => $option) :
			
			// $value = $_POST[$this->prefix . $option['name']];
			
			switch($option['type']) :
				case 'email' :
					
					if(strpos($_POST[$this->prefix . $option['name']], ',') !== FALSE) :
						$errorArray[] = "Only one E-mail is allowed.";
					endif;
						
					$result = filter_var($_POST[$this->prefix . $option['name']], FILTER_VALIDATE_EMAIL);
				
					if($result) :
						$_POST[$this->prefix . $option['name']] = $result;
						$isValid = true;
					elseif( $value === "") :
						$errorArray[] = "You must enter an E-mail.";
						unset($_POST[$this->prefix . $option['name']]);
						$isValid = false;
					else :
						$errorArray[] = 'Error: "' . $_POST[$this->prefix . $option['name']] . '" is not a valid E-mail.';
						unset($_POST[$this->prefix . $option['name']]);
						$isValid = false;
					endif;
					
					// In case there is a trailing comma, remove it.
					$_POST[$this->prefix . $option['name']] = rtrim($_POST[$this->prefix . $option['name']], ',');
					break;
					
				case 'phone' :
					
					// Strip tags for safety.
					strip_tags(	$_POST[$this->prefix . $option['name']]	);
					
					// Purge all non-numbers.
					$_POST[$this->prefix . $option['name']] = preg_replace( "/[^0-9]/","", $_POST[$this->prefix . $option['name']] );
					
					// If not a valid phone number, set false and place error in array.
					if ( $_POST[$this->prefix . $option['name']] == "" || strlen($_POST[$this->prefix . $option['name']]) > self::PHONE_MAX_DIGITS || strlen($_POST[$this->prefix . $option['name']]) < self::PHONE_MIN_DIGITS ) :
						$isValid = false;
						unset($_POST[$this->prefix . $option['name']]);
						$errorArray[] = "Phone numbers are between " . self::PHONE_MAX_DIGITS . " and " . self::PHONE_MIN_DIGITS . " digits.";
					else:
						$isValid = true;
					endif;
					
					break;
					
				default:
					strip_tags($_POST[$this->prefix . $option['name']]);
					break;
					
			endswitch;

		endforeach;
		
		echo '<div class="error">';
		foreach($errorArray as $error) :
			echo $error;
		endforeach;
		echo '</div>';
		
		return $isValid;
		
	}

	public function antiSpam( )
	{
		
	}
	
	////////////////////////////
	/// 6. Styles and Scripts //
	////////////////////////////
	
	public function registerStyle( ) 
	{
		
	}
	public function registerScripts( )
	{
		if( !wp_script_is('jquery') ):
			wp_enqueue_script( 'jquery' ); // Make sure jQuery is Enqueued
		endif;
		
		wp_deregister_script('simpulforms');
		wp_enqueue_script( 'simpulforms', plugins_url( '/js/simpulforms.js', __FILE__ ), array('jquery') );
	}
	public function createShortCode()
	{
		$success = self::checkPostAndSend();
		
		if($success === FALSE):
			return self::createForm();
		else :
			echo $success;
						
		endif;
		
	}
	////////////////
	// 7. Helpers //
	////////////////
	
	public function checkPostAndSend()
	{
		//For some reaosn on Image Submit, Internet Explorer and Firefox Send Cartesian Coords _x and _y instead of just the Submit Button
		if( ( isset($_POST[$this->prefix . "submit"]) || isset($_POST[$this->prefix . "submit_x"]) || isset($_POST[$this->prefix . "submit_y"]) ) && !is_admin()):
		
		$mail = self::sendMail();
			if($mail !== FALSE):
					return '<div class="simpul-forms-notice">' . get_option( $this->prefix . 'thank_you' ) . '</div>';
			else:
					return FALSE;
			endif;
		endif;	
		return FALSE;
	}
	
	public function createInput( $type, $field, $value, $args = array(), $options = array(), $required = false ) 
	{
			
		foreach((array)$args as $attribute => $attribute_value):
			$attr .= $attribute . '="' . $attribute_value . '"';
		endforeach;
		switch($type):
			case "email":
				return '<tr><th>' . self::getLabel($field) . '</th><td>' . ' <input name="' . $field . '"  
									 ' . $attr . '
									type="text" 
									value="' . $value . '"
									/>' . $required . '</td></tr>';
				break;
			case "textarea":
				return '<tr><th>' . self::getLabel($field) . '</th><td><textarea name="' . $field . '" 
									 ' . $attr . ' >'. $value . '</textarea>' . $required . '</td></tr>';
				break;
			case "radio":
				foreach($options as $option):
					if( $value == $option ): $checked = "checked"; else: $checked = ""; endif;						
						'<tr><td colspan="2">' . $radio .= '<input name="' . $field . '" 
									 ' . $attr . '
									type="radio" 
									value="' . $option . '"
									' . $checked . ' /> ' . self::getLabel($field) . $required . '</td></tr>';
				endforeach;
				break;
			case "checkbox":
				if( $value != $option ): $checked = "checked"; else: $checked = ""; endif;						
						$checkbox .= '<tr><th>' . self::getLabel($field) . '</th><td>' . '<input name="' . $field . '"
									type="checkbox" 
									value="1"
									' . $checked . ' />' . $required . '</td></tr>';
				return $checkbox;
				break;
			case "multicheckbox":
				foreach($options as $option):
					if( in_array( $option, $value ) ): $checked = "checked"; else: $checked = ""; endif;						
						$checkbox .= '<tr><th>' . self::getLabel($field) . '</th><td>' . '<input name="' . $field . '[]" 
									 ' . $attr . '
									type="checkbox" 
									value="' . $option . '"
									' . $checked . ' />' . $required . '</td></tr>';
				endforeach;
				return $checkbox;
				break;
			case "dropdown":
				$dropdown .= '<tr>
										<th>
										' . self::getLabel($field) . '
										</th>
										<td>
										<select name="' . $field . '" 
									 ' . $attr . ' />' . $required . ' ';
					
				foreach($options as $option):
					if( in_array( $option, $value ) ): $selected = "selected"; else: $selected = ""; endif;						
					$dropdown .= '<option value="' . $option . '">' . self::getLabel($option) . '</option>';				
				endforeach;
				
				$dropdown .= '</td></tr>';
				
				return $dropdown;
				break;
			case "submit":
				return '<tr><td colspan="2">' . '<button name="' . $field . '" 
									 ' . $attr . '
									type="submit" 
									value="1"
									/>' . $value . '</button></td></tr>';
				break;
			default:
				return '<tr><th>' . self::getLabel($field) . '</th><td><input name="' . $field . '" 
									 ' . $attr . '
									type="text" 
									value="' . $value . '"
									/>' . $required . '</td></tr>';
				break;
		endswitch;
		
	}
	
	public function getLabel($key)
	{
		
		$glued = array();
		$key = str_replace($this->prefix, "", $key); 
		if( strpos( $key, "_" ) ) $pieces = explode( "_" , $key );
		elseif( strpos( $key, "-" ) ) $pieces = explode( "-", $key );
		else $pieces = explode(" ", $key);
		foreach($pieces as $piece):
			if($piece == "id"):
				$glued[] = strtoupper($piece);
			else:
				$glued[] = ucfirst($piece);
			endif;
		endforeach;
		
		return implode(" ", (array) $glued);
		
	}
	
}
