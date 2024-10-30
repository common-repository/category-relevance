<?php
/*
Plugin Name: Category Relevance
Plugin URI: 
Description: This plugin is to set category relevance
Usage: 
Version: 1.0
Author: DjZoNe
Author URI: http://djz.hu
License: GPL
Download URL: 
*/

class CategoryRelevance
{
	function Enable()
	{
		add_action('admin_menu', array("CategoryRelevance", 'AdminMenu'));
		
		add_action('edit_post', array("CategoryRelevance", 'PostMetaTags'));
		add_action('publish_post', array("CategoryRelevance", 'PostMetaTags'));
		add_action('save_post', array("CategoryRelevance", 'PostMetaTags'));
		add_action('edit_page_form', array("CategoryRelevance", 'PostMetaTags'));
 //remove_filter('getarchives_join','ace_getarchives_join');
	}
	
	function AdminHead()
	{ 
    remove_filter('posts_join',array("CategoryRelevance", 'PostsJoin'));
    remove_filter('posts_fields',array("CategoryRelevance", 'PostsFields'));
    remove_filter('posts_orderby',array("CategoryRelevance", 'PostsOrderby'));   
  }
	
	function PostsJoin($join)
  {
    global $wpdb, $wp_query;
  
    if (!is_category()) return $join;    
  
    $category = $wp_query->query_vars['category__in'][0];  
    
    if (empty($category) && !empty($wp_query->query) && !empty($wp_query->query['category_name']))
    {
	$catdata = is_term($wp_query->query['category_name'],'category');
	
	$category = $catdata['term_id'];
    }

    if (empty($category)) return $join;
    
  
    $key = 'relevance_'.$category;
  
    $join .= " INNER JOIN ".$wpdb->postmeta. " ON (".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND ".$wpdb->postmeta.".meta_key = '".$key."' )";
    
    //echo $join;
    //die;
  
    return $join;
  } 

	function PostsFields($fields)
  {
    global $wpdb;
  
    if (!is_category()) return $fields;    

    $fields .= ', '.$wpdb->postmeta.".meta_value as post_relevance";
  
    return $fields;
  }

	function PostsOrderby($order)
  {
    global $wpdb;
  
    if (!is_category()) return ' '.$order.' ';    

    $order = ' post_relevance DESC, '. $order .' ';  
  
    return $order;
  }

	function AdminMenu()
	{	
		if(function_exists('add_meta_box'))
		{
			add_meta_box('category-relevance','Category relevance', array("CategoryRelevance", 'PostMeta'), 'post', 'side');
		}
	}

	function PostMetaTags($id) 
	{
    $category_relevance_edit = $_POST["category_relevance_edit"];
    
    if (isset($category_relevance_edit) && !empty($category_relevance_edit)) 
    {
      $post_id = $_POST['post_ID'];
      $categories = get_the_terms( $post_id, 'category' );
      
      foreach ($categories as $category)
      {
        $_name = 'relevance_'.$category->term_id;
        $relevance = intval($_POST[$_name]);
        
        if (isset($relevance) && !empty($relevance))
        {
          delete_post_meta($post_id, $_name);
          add_post_meta($post_id, $_name, $relevance);
        }
      }
    }
	}
	
	function PostMeta()
	{
	    global $post;
	    $post_id = $post;
		
	    if (is_object($post_id))
      {
        $post_id = $post_id->ID;
	    }
      $categories = get_categories('show_empty=1');
?>
		<input value="category_relevance_edit" type="hidden" name="category_relevance_edit" />
		<br />
			<?php foreach ($categories as $category): ?>
			<?php
			
			$relevance = get_post_meta($post_id,'relevance_'.$category->term_id);
			
      ?>
			<strong><?php echo $category->name; ?>:</strong>
			<input type="text" class="relevance" name="relevance_<?php echo $category->term_id; ?>" style="width:40px" size="3" maxlength="3" value="<?php echo $relevance[0]; ?>"><br /><br />
			<?php endforeach; ?>			
<br />
<script type="text/javascript">
jQuery(document).ready(function($) 
{
  $('#post').submit(function()
  {
    var allEmpty = true;
    
    $('input.relevance').each(function(i,e)
    {
      if (e.value != '' && e.value > 0) allEmpty = false;
    });
    
    if (allEmpty)
    {
      alert('Relevance settings are all empty!');
      return false;
    }
  });
  
});
</script>
<?php	
	}
}

if(defined('ABSPATH') && defined('WPINC')) 
{
	add_action("init",array("CategoryRelevance","Enable"),1000,0);
}

add_filter('posts_join',array("CategoryRelevance", 'PostsJoin'));
add_filter('posts_fields',array("CategoryRelevance", 'PostsFields'));
add_filter('posts_orderby',array("CategoryRelevance", 'PostsOrderby'));

add_action('admin_head', array("CategoryRelevance", 'AdminHead'));