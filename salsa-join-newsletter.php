<?php
/*
Plugin Name: Salsa Can Opener
Description: Adds email to salsa Newsletter via Widget
Author: Justin Foell
Version: 1.0
*/

define('SALSA_JOIN_DIR', dirname(__FILE__) . '/');
define('SALSA_JOIN_URL', get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__)) . '/');
require_once SALSA_JOIN_DIR . 'config.php';

class Salsa_Newsletter_Widget extends WP_Widget {

	//this method cannot be '__construct()' b/c wordpress is stupid
	public function Salsa_Newsletter_Widget() {
		parent::WP_Widget(false, $name = 'Salsa_Newsletter_Widget');	
	}

	function widget( $args, $instance ) {		
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
                  <form name="newsletter" method="post">
					   <input type="text" name="email" id="salsa-email" value="" />
					   <input type="button" name="send" id="salsa-send" value="Subscribe" />
				  </form>
              <?php echo $after_widget; ?>
        <?php
	}
		
	function update( $new_instance, $old_instance ) {		
		$instance = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title'] );		
		return $instance;		
	}
		
	function form( $instance ) {
		$title = esc_attr($instance['title']);
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <?php 
	}
	
}

class Salsa_Join {
	
	protected $curl;
	protected $url;
	
	public function onWidgetInit() {
		register_widget("Salsa_Newsletter_Widget");

		wp_enqueue_script( 'jquery' );
		wp_register_script( 'salsa_join_newsletter', SALSA_JOIN_URL . 'salsa-join-newsletter.js' );
		wp_enqueue_script( 'salsa_join_newsletter' );
	}

	public function onAjaxSubmit() {

		$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		if($email) { //email is valid
			$this->curlInit();
			$this->auth(SALSA_USER, SALSA_PASSWORD);
			$this->addSupporter($this->addEmail($email));
			//Close the connection
			curl_close($this->curl);
			echo 1;
		} else {
			//WP will return 0 / -1 on error, so we'll use -2
			echo -2;
		}

		die(); //required for correct ajax return
	}

	protected function curlInit() {
		$this->url = "http://" . SALSA_SUBDOMAIN;
		
		//Initialize CURL connection
		$this->curl = curl_init();

		//Set basic connection parameters:
		//      See http://us.php.net/curl_setopt for more information on these settings
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 100);

		//Set Parameters to maintain cookies across sessions
		//might have to make this a random file
		curl_setopt($this->curl, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/tmp/cookies_file');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/tmp/cookies_file');		
	}
	
	protected function auth($email, $password) {
		$fields = array('email' => $email, 
                'password' => $password,
						);
		curl_setopt($this->curl, CURLOPT_URL, "{$this->url}/api/authenticate.sjs");
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($fields));
		$auth = curl_exec($this->curl);		
	}

	protected function addEmail($email) {
		$fields = array('object' => 'supporter', 
                'Email' => $email,
                'xml' => 1
               );
		
		curl_setopt($this->curl, CURLOPT_URL, "{$this->url}/save");
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($fields));
		$result = curl_exec($this->curl);

		preg_match("/key=\"(.*)\"/", $result, $matches);
		return $matches[1];
	}

	protected function addSupporter($supporter_key) {
		$fields = array('object' => 'supporter_groups',
						'supporter_KEY' => $supporter_key,
						'groups_KEY' => '2076',
						'xml' => 1
						);

		curl_setopt($this->curl, CURLOPT_URL, "{$this->url}/save");
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($fields));
		return curl_exec($this->curl);
	}
}

$salsa = new Salsa_Join();

// register widget
add_action('widgets_init', array( $salsa, 'onWidgetInit' ) );
add_action('wp_ajax_salsa_join', array( $salsa, 'onAjaxSubmit' ) );
add_action('wp_ajax_nopriv_salsa_join', array( $salsa, 'onAjaxSubmit' ) );
