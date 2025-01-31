<?php
/*
Plugin Name: Remove All Post Slug
Description: Removes the slug from permalink on all post. also work with Polylang plugin.
Version: 1.0.0
Author: Saeed Ullah Khan
Author URI: http://www.arhamsoft.com/
*/

class ASOFT_RemoveSlugPostType{

	static $_s = null;
	private $htaccess_tag = 'REMOVE SLUG POST TYPE RULES';

	public function __construct() {
		$this->rewrite_rules();

		add_action('admin_menu', array(&$this, 'menu'));
		add_action('wp_insert_post', array(&$this, 'post_save'));

		add_filter('post_type_link', array(&$this, 'remove_slug'), 10, 3);
		add_filter('redirect_canonical', array(&$this, 'cancel_redirect_canonical'));

	}

	function cancel_redirect_canonical($redirect_url) {
		$suffix = get_option('asoft_permalink_customtype_suffix');
		if (empty($suffix))
			return $redirect_url;
		global $wp_post_types;
		foreach ($wp_post_types as $type=>$custom_post) {
		    $hit = get_query_var($custom_post->query_var);
		    if (!empty($hit))
		        return false;
		}
	}

	public function menu() {
		add_options_page( 'Permalink custom post type', 'Custom post type', 'manage_options', __FILE__, array(&$this, 'menu_page'));
	}

	public function menu_page() {
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		$opt_name = 'asoft_permalink_customtype_suffix';
		$hidden_field_name = 'asoft_submit_hidden';
		$opt_val = get_option($opt_name);

		if(isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {
			$opt_val = preg_replace('/[^0-9a-z]+/i', '', $_POST[$opt_name]);
			update_option($opt_name, $opt_val);
?>
			<div class="updated"><p><strong><?php _e('settings saved.' ); ?></strong></p></div>
<?php
			$this->rewrite_rules(true);
			$this->add_rules_htaccess();
		}

		echo '<div class="wrap">';
		echo "<h2>Permalink custom post type</h2>";
?>
<form name="form1" method="post" action="">
	<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
	<p>Permalink custom type suffix:
		<input type="text" name="<?php echo $opt_name; ?>" value="<?php echo $opt_val; ?>" size="20">
		<br />
		<em>Use this option if you want your custom post type posts to have a suffix, e.g. ".html", leave empty if you want normal structure.</em>
	</p>
	<hr />
	<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
	</p>
</form>
</div>
<?php
	}

	public function request($input) {
		global $wp_post_types;
		$array_slug[] = "wp_posts.post_type = 'post'";
		foreach ($wp_post_types as $type=>$custom_post) {
			if ($custom_post->_builtin == false)
				$array_slug[] = "wp_posts.post_type = '" . trim($type, '/') . "'";
		}
		$types = '(' . implode(' || ', $array_slug) . ')';
		$input = str_replace("wp_posts.post_type = 'post'", $types, $input);
		return $input;
	}

	static public function init() {
		if (self::$_s == null) {
			self::$_s = new self();
		}
		return self::$_s;
	}

	static public function flush_rewrite_rules() {
		$asoft_o = self::init();
		$asoft_o->rewrite_rules(true);
		$asoft_o->add_rules_htaccess();
	}

	public function post_save($post_id) {
		global $wp_post_types;
		$post_type = get_post_type($post_id);
		foreach ($wp_post_types as $type=>$custom_post) {
			if ($custom_post->_builtin == false && $type == $post_type) {
				$this->rewrite_rules(true);
				$this->add_rules_htaccess();
			}
		}
	}

	public function remove_slug($permalink, $post, $leavename) {
		global $wp_post_types;
		$suffix = get_option('asoft_permalink_customtype_suffix');
		foreach ($wp_post_types as $type=>$custom_post) {
			if ($custom_post->_builtin == false && $type == $post->post_type) {
				$custom_post->rewrite['slug'] = trim($custom_post->rewrite['slug'], '/');
				$permalink = str_replace(get_bloginfo('url') . '/' . $custom_post->rewrite['slug'] . '/', get_bloginfo('url') . "/", $permalink);
				if (!empty($suffix))
					$permalink = substr($permalink, 0, -1) . ".{$suffix}";
			}
		}

		return $permalink;
	}

	public function rewrite_rules($flash = false) {
		global $wp_post_types, $wpdb;
		$suffix = get_option('asoft_permalink_customtype_suffix');
                $res = (get_option('_transient_pll_languages_list'));
                $allLang = '';
                    foreach($res as $ln)
                    {
                        $arr[] = $ln['slug'];
                    }

                    if(count($arr) > 0)
                    {
                        $allLang = '('.implode('|',$arr).')/';
                    }

		foreach ($wp_post_types as $type=>$custom_post) {
			if ($custom_post->_builtin == false) {
				$querystr = "SELECT {$wpdb->posts}.post_name
								FROM {$wpdb->posts}
								WHERE {$wpdb->posts}.post_status = 'publish'
    									AND {$wpdb->posts}.post_type = '{$type}'
    									AND {$wpdb->posts}.post_date < NOW()";
				$posts = $wpdb->get_results($querystr, OBJECT);
				foreach ($posts as $post) {


					$regex = (!empty($suffix)) ? "{$allLang}/\\{$post->post_name}\\.{$suffix}\$" : "{$allLang}{$post->post_name}\$";
					add_rewrite_rule($regex, "index.php?{$custom_post->query_var}={$post->post_name}", 'top');
				}
			}
		}
		if ($flash == true)
			flush_rewrite_rules(false);
	}

	private function add_rules_htaccess() {
		global $wp_post_types;

                $res = (get_option('_transient_pll_languages_list'));
                $allLang = '';
                    foreach($res as $ln)
                    {
                        $arr[] = $ln['slug'];
                    }

                    if(count($arr) > 0)
                    {
                        $allLang = '('.implode('|',$arr).')';
                    }

		$suffix = get_option('asoft_permalink_customtype_suffix');
		$write = array();
		$htaccess_filename = ABSPATH . '/.htaccess';
		if (is_readable($htaccess_filename)) {
			$htaccess = fopen($htaccess_filename, 'r');
			$content = fread($htaccess, filesize($htaccess_filename));
			foreach ($wp_post_types as $type=>$custom_post) {

                            $query_var = $custom_post->query_var <> ''? $allLang.'/'.$custom_post->query_var:$custom_post->query_var;
				$rewrite_rule = (!empty($suffix))
							? "RewriteRule ^{$query_var}/(.+)/\$ {$allLang}/\$1\.{$suffix} [R=301,l]"
							: "RewriteRule ^{$query_var}/(.+)/\$ {$allLang}/\$1 [R=301,L]";
				if (strpos($content, $rewrite_rule) == false && $custom_post->_builtin == false)
					$write[] = $rewrite_rule;
			}
			fclose($htaccess);
		}
		else {
			add_action('admin_notices', array(&$this, 'compatibility_notice'));
			return;
		}

		if (!empty($write) && is_writable($htaccess_filename)) {
			$new_rules = '# BEGIN ' . $this->htaccess_tag . PHP_EOL;
			$new_rules .= str_replace('$', '\\$', implode(PHP_EOL, $write)) . PHP_EOL;
			$new_rules .= '# END ' . $this->htaccess_tag;
			if (strpos($content, "# BEGIN {$this->htaccess_tag}") === false) {
				file_put_contents($htaccess_filename, $new_rules . PHP_EOL . PHP_EOL . $content);
			}
			else {
				$pattern = "/# BEGIN {$this->htaccess_tag}.*?# END {$this->htaccess_tag}/ims";
				$content = preg_replace($pattern, $new_rules, $content);
				file_put_contents($htaccess_filename, $content);
			}
		}
		else
			add_action('admin_notices', array(&$this, 'compatibility_notice'));
	}

	public function compatibility_notice() {
		global $wp_post_types;
		$rules = '';
                $res = get_option('_transient_pll_languages_list');
                $allLang = '';
                    foreach($res as $ln)
                    {
                        $arr[] = $ln['slug'];
                    }
                    if(count($arr) > 0)
                    {
                        $allLang = '('.implode('|',$arr).')';
                    }
		foreach ($wp_post_types as $type=>$custom_post) {
			if ($custom_post->_builtin == false) {
				$slug = str_replace('/', '', $custom_post->rewrite['slug']);
                                $newSlug = $slug <> '' ? $allLang.'/'.$slug:$slug;
				$rules .= 'RewriteRule ^'. $newSlug . '/(.+)$ /$1 [R=301,L]<br />';
			}
		}

		echo '<div class="error fade" style="background-color:red;"><p><strong>Remove Slug Custom post type error!</strong><br />.htaccess is not writable, please add following lines to complete your installation: <br />'.$rules.'</p></div>';
	}
}

add_filter('request', function( $vars ) {

        $varpage = explode('/',$vars['category_name']);
        if(count($varpage) > 1)
            $varpage = $varpage[count($varpage)-1];
            else
                $varpage = $varpage[0];

	global $wpdb;
	if( ! empty( $varpage ) || ! empty( $vars['category_name'] ) || ! empty( $vars['name'] ) || ! empty( $vars['attachment'] )) {


		$slug = $vars['name'];

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT t.term_id FROM $wpdb->terms t LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'product_cat' AND t.slug = %s" ,array( $slug )));
		if( $exists ){
			$old_vars = $vars;
			$vars = array('product_cat' => $slug );
			if ( !empty( $old_vars['paged'] ) || !empty( $old_vars['page'] ) )
				$vars['paged'] = ! empty( $old_vars['paged'] ) ? $old_vars['paged'] : $old_vars['page'];
			if ( !empty( $old_vars['orderby'] ) )
	 	        	$vars['orderby'] = $old_vars['orderby'];
      			if ( !empty( $old_vars['order'] ) )
 			        $vars['order'] = $old_vars['order'];
		}
	}

	return $vars;
});


add_action('init', array('ASOFT_RemoveSlugPostType', 'init'), 99);
register_activation_hook( __FILE__, array('ASOFT_RemoveSlugPostType', 'flush_rewrite_rules') );
register_deactivation_hook( __FILE__, array('ASOFT_RemoveSlugPostType', 'flush_rewrite_rules') );