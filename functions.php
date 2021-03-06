<?php 
# @link github.com/ryanve/action
# @link github.com/ryanve/action-child
# @link codex.wordpress.org/Child_Themes

# Most customizations can be made via action/filter hooks. 
# Use this file to add or remove actions/filters. It is also 
# possible to override parent theme files by copying them into 
# this folder and editing them here. For example, if you put a
# file here called branding.php, it will override the parent 
# theme's branding.php file.

# @link codex.wordpress.org/Plugin_API#Actions
# @link codex.wordpress.org/Plugin_API#Filters
# @link codex.wordpress.org/Plugin_API/Action_Reference

# @link codex.wordpress.org/Function_Reference/add_action
# @link codex.wordpress.org/Function_Reference/add_filter
# @link codex.wordpress.org/Function_Reference/wp_enqueue_style
# @link codex.wordpress.org/Function_Reference/wp_enqueue_script

'wp-login.php' === \basename($_SERVER['SCRIPT_FILENAME']) ? add_action('init', function() {
  $file = 'login.css';
  if (\is_file(trailingslashit(get_stylesheet_directory()) . $file)) {
    wp_deregister_style('wp-admin');
    $file = trailingslashit(get_stylesheet_directory_uri()) . $file;
    wp_enqueue_style('child-login', $file, [], null, 'screen,projection,print');
  }
}, 20) : add_action('after_setup_theme', is_admin() ? function() {
  if (is_user_logged_in() && !current_user_can('edit_posts')) {
    wp_redirect('wp-admin' === \trim($_SERVER['REQUEST_URI'], '/') ? wp_login_url() : home_url());
    exit;
  }
  # Prevent theme updates:
  # remove_action('load-update-core.php', 'wp_update_themes');
  # add_filter('pre_site_transient_update_themes', function() {});
} : function() {
  # CSS
  # handle, uri, deps, ver, media
  add_action('init', function() {
    $uri = rtrim(get_stylesheet_directory_uri(), '/');
    # Include dependencies like 'parent-base' or 'parent-style' as needed.
    wp_enqueue_style('child-base', "$uri/base.css", [], null, 'all');
    wp_enqueue_style('child-style', "$uri/style.css", [], null, 'screen,projection,print');
  }, 20); # << wait until parent theme styles are registered
  
  # JavaScript
  # handle, uri, deps, ver, in_footer
  //add_action('init', function() {
    # Start with Modernizr if applicable. Uncomment and change URI as needed:
    // $uri = 'http://airve.github.com/js/modernizr/modernizr_shiv.min.js';
    // wp_enqueue_script('modernizr', $uri, array(), null, false);
    
    # jQuery plugin example. List jQuery as a dependency and load in footer:
    // $uri = trailingslashit(get_stylesheet_directory_uri()) . 'js/example-plugin.js';
    // wp_enqueue_script('example-plugin', $uri, array('jquery'), null, true);
  //}, 1); # << prioritize early to ensure position in queue
  
  #temp Todo: @void
  //remove_all_filters('the_excerpt');
  //add_filter('the_excerpt', '__return_empty_string');
  
  $prime = function() {
    static $memo;
    if ($memo) return $memo;
    
    //gist.github.com/ryanve/3a6b6480993af3de492d
    $is_page = function($pages) {
      global $post;
      if (!is_page()) return false;
      if (is_page($pages)) return true;
      if (empty($post->post_parent)) return false;
      $parent = get_post($post->post_parent);
      if (!$parent || empty($parent->post_name)) return false;
      $slug = $parent->post_name;
      foreach ((array) $pages as $page) if ($slug === $page) return true;
      return false;
    };
    
    $member = current_user_can('read');
    if (!$member && $is_page('members')) return $memo = 'default';
    $plural = !is_singular();
    $categ = $plural ? 'is_category' : 'in_category';
    $logic = ['events' => [
      [$categ, 'menu-events'],
      ['is_singular', 'ai1ec_event'],
      ['is_tax', 'where']
    ], 'about' => [
      ['is_page', 'about', 'activities', 'locations', 'youth-and-family', 'membership'],
      [$categ, 'menu-about']
    ], 'members' => !$member ? [] : [
      ['is_page', 'members', 'tools', 'contribute'],
      [$categ, 'menu-members', 'classes', 'member-news']
    ], 'resources' => [
      ['is_page', 'resources', 'presentations', 'broadcasts', 'clergy'],
      ['is_page', 'workshops', 'speakers'], # these previously were categories
      ['is_tax', 'newsletter', 'public-newsletter'],
      [$categ, 'menu-resources', 'news']
    ], 'contact' => [
      ['is_page', 'contact', 'mailinglist'],
      [$categ, 'menu-contact']
    ], 'default' => []];
    $not = $plural ? ['is_page'] : ['is_category', 'is_tag', 'is_tax'];
    foreach ($logic as $case => $tests) {
      if (!$plural && $is_page($case)) break;
      foreach ($tests as $ar) if ($ar && !in_array($fn = array_shift($ar), $not) && $fn($ar)) break 2;
    }
    return $memo = $case;
  };
  
  # Run logic to determine the "prime-*" contextual class.
  add_filter('body_class', function($classes) use (&$prime) {
    $case = $prime();
    $classes[] = "prime-$case";
    return array_unique($classes);
  });
  
  add_action('@loop', function() {
    if (function_exists('bcn_display') && !is_home()) {
      echo '<div class="breadcrumbs">';
      bcn_display();
      echo '</div>'; 
    }
  }, 0);
  
  #update_option('@homie_types', ['bond']);
  update_option('@homie_terms', '1');
  
  add_action('pre_get_posts', function(&$query) use ($cpt) {
    # Conditional Tags are not available yet here. 
    # Props like `$query->is_singular` are usable.
    if ($query->is_main_query() && !empty($query->is_home)) {
      #($types = get_option('@homie_types')) and $query->set('post_type', $types);
      #($terms = get_option('@homie_terms')) and $query->set('cat', $terms);
      #$query->set('cat', '18');
      #print_r($query);
    }
  }, 100);
  
  /*add_filter('@thumbnail_size', function($size) use (&$iteration) {
    static $i;
    return !$i++ && is_home() ? 'large' : $size;
  });*/
  
  add_action('@loginorout', function() {
    $case = is_user_logged_in() ? 'out' : 'in';
    $url = call_user_func('wp_log' . $case . '_url', $_SERVER['REQUEST_URI']);
    echo "<a class='loginorout' href='$url'>Log$case</a>";
  });
  
  add_filter('wp_get_nav_menu_items', function($items) {
    foreach ($items as &$o) {
    if (is_object($o) && 'Login' === $o->title) {
      $case = is_user_logged_in() ? 'out' : 'in';
      $url = call_user_func('wp_log' . $case . '_url', $_SERVER['REQUEST_URI']);
      $o->url = $url;
      $o->title = "Log$case";
    }
    }
    return $items;
  });

  
  # Remove auto formats. See: wp-includes/default-filters.php
  # remove_filter('the_content', 'wpautop');
  # remove_filter('the_content', 'shortcode_unautop');
  
  add_filter('@content_mode', function($bool) {
    static $i;
    $i++;
    return $bool || is_tax('category', [2, 13, 29]) || 2 > $i && (is_home() || is_tax('newsletter'));
  });
  
  add_filter('@loop_data', function($data) {
    isset($data['name']) && is_tax('newsletter') and $data['name'] .= ' Newsletter';
    return $data;
  });
  
  add_shortcode('wp_login_form', 'wp_login_form');
  has_filter('widget_text', 'do_shortcode') or add_filter('widget_text', 'do_shortcode');
  add_filter('login_form_defaults', function($arr) {
    return \array_replace($arr, ['remember' => false]);
  });
  
  add_filter('login_form_defaults', function($defaults) {
    $defaults['remember'] = false;
    return $defaults;
  });
  
  //add_filter('@access:loop_start:granted', function($markup, $posts, $denies) {
  //  print_r(func_get_args());
  //}, 10, 3);
  
  add_filter('@chela_login_hint', function() {
    $hint = 'The members area of the site offers additional content—tools, classes, and news—to members of Eckankar. If you are a member of Eckankar please log in with the username and password information below. If you are interested in becoming a member, please read about <a href=/membership>membership</a> details.
   <ul><li>The username is chela.</li>
     <li>The <b>chela</b> password is the <b>last word in the title</b> of Sri Harold\'s front page article in the most recent <em>Mystic World</em>, <b>plus the publication year</b>.</li> 
     <li>Please use all lowercase letters with no spaces. Example: ****2016</li></ul>';
    return "<p class='hint'>$hint</p>";
  });
  
  add_filter('@chela_login_form', function() {
    return wp_login_form(array('echo' => 0, 'value_username' => 'chela', 'remember' => false));
  });
  
  //github.com/ryanve/access#hooks
  add_filter('@access:message:denied', function() {
    $in = is_user_logged_in();
    $url = $in ? home_url('members') : wp_logout_url();
    $form = $in ? "<a href='$url'>Logout</a>"
      : "<h1><a href='$url'>Member Login</a></h1>"
      . apply_filters('@chela_login_hint', null)
      . apply_filters('@chela_login_form', null);
    $class = $in ? 'loop-logout' : 'loop-login';
    return "\n<div class='$class'>$form</div>\n\n";
  });
  
  /*add_action('@header', function() {
    wp_login_form(array('remember' => false, 'value_remember' => true));
  }, 20);*/
  
  # disable ai1ec date icon clicks
  add_action('wp_footer', function() {
    echo '<script>!function(){[].some.call(document.querySelectorAll("[href].ai1ec-date-title,.ai1ec-date-title [href],.ai1ec-date-block-wrap [href]"),function(a){a.removeAttribute("href");a.addEventListener("click",function(a){a.preventDefault();a.stopPropagation()})})}();</script>' . "\n";
  }, 99);
});

#end
