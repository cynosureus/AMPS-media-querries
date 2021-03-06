<?php

/*
Plugin Name: AMPS Media Queries
Plugin URI: http://www.cynosure.com
Description: Objects to simplify querries for amps marketing materials
Version: 1.0
Author: Daniel Miller


*/

 

class Cynosure_Media_Query 
{
	function __construct($args) {

		$this->criteria = $args['criteria'];

	}

	function generateTaxArray($taxonomy, $term) {

		return array(

				'taxonomy' => $taxonomy,
				'field' => 'slug',
				'terms' => array($term)

			);

	}

	function generateTaxQuery() {

		$tax_query = array('relation' => 'AND');

		foreach ($this->criteria as $taxonomy => $term) {

			$tax = $this->generateTaxArray($taxonomy, $term);
			array_push($tax_query, $tax);

		}

		return $tax_query;

	}

	function generateMediaQueryArgs() {
		
		$args = array(

				'post_status' => 'publish',
				'post_type' => 'cynosure-material',
				'tax_query' => $this->generateTaxQuery()
			);



		return $args;

	}

	function runQuery() {

		$args = $this->generateMediaQueryArgs();

		$query = new WP_Query($args);

		return $query;
	}


}

class Cynosure_Material_Data_Set
{

	function __construct() {

		$post = get_post(get_the_ID());

		$this->product = $post->post_name;
		$this->treatments = get_terms('media-treatment');
		$this->categories = get_terms('media-category');
		$this->product_treatments = array();
		$this->treatment_categories = array();
		$this->product_categories = array();
		$this->subcategories = array();
		$this->materials = array();

		$this->get_product_treatments();
		$this->get_product_categories();

	}

	function get_all() {

		$get_all = new Cynosure_Media_Query(array('criteria' => array('media-product' => $this->product)));

		$get_all_query = $get_all->runQuery();
		
		if($get_all_query->have_posts()) {

			while ($get_all_query->have_posts()) {

				$get_all_query->the_post();

					array_push($this->materials, get_the_ID());

			}

			
		}

		wp_reset_postdata();
	}

	function get_product_treatments() {


		foreach($this->treatments as $treatment) {

			$treatment_media_query = new Cynosure_Media_Query(array('criteria' => array('media-product' => $this->product, 'media-treatment' => $treatment->slug)));
			$treatments_query = $treatment_media_query->runQuery();

			if($treatments_query->have_posts()) {

				array_push($this->product_treatments, $treatment);

			}

		}

		wp_reset_postdata();

	}

	function get_treatment_categories($treatment) {

		$this->treatment_categories = array();

		foreach($this->categories as $category) {

			if($category->parent == 0) {
				$categories_media_query = new Cynosure_Media_Query(array('criteria' => array('media-product' => $this->product, 'media-treatment' => $treatment->slug, 'media-category' => $category->slug)));
				$categories_query = $categories_media_query->runQuery();

				if($categories_query->have_posts()) {

					array_push($this->treatment_categories, $category);
				}
			}

		}

		wp_reset_postdata();

	}

	function get_product_categories() {

		foreach($this->categories as $category) {

			if($category->parent == 0) {
				$categories_media_query = new Cynosure_Media_Query(array('criteria' => array('media-product' => $this->product, 'media-category' => $category->slug)));
				$categories_query = $categories_media_query->runQuery();

				if($categories_query->have_posts()) {

					array_push($this->product_categories, $category);
				}
			}

		}

		wp_reset_postdata();

	}


	function has_subcategories($category) {

		$termchildren = get_term_children($category->term_id, 'media-category');
		if ($termchildren) {
			return true;
		}
	}

	function get_subcategories($category) {

		$termchildren = get_term_children($category->term_id, 'media-category');
	
		$this->subcategories = array();

		foreach($termchildren as $term_id) {
			$term = get_term($term_id, 'media-category');
			array_push($this->subcategories, $term);
		}
	}

	function get_materials($args) {

		$criteria = array('media-product' => $this->product);
		foreach($args as $key => $value) {

			$criteria['media-'.$key] = $value;

		}

		$this->materials = array();
		$categories_media_query = new Cynosure_Media_Query(array('criteria' => $criteria));

		$categories_query = $categories_media_query->runQuery();
		if ($categories_query->have_posts() ) {
			while($categories_query->have_posts()) {
				$categories_query->the_post();

				array_push($this->materials, get_the_ID() );
			}
		}

		wp_reset_postdata();



	}

}

class Cynosure_Paginator 
{
	function __construct($args) {

		$this->items = $args['items'];
		$this->items_count = count($args['items']);
		$this->items_per_page = $args['items_per_page'];
		$this->page = $args['page'];
		$this->num_pages = ceil($this->items_count / $this->items_per_page);

	}

	function get_start_point() {

		return $this->items_per_page * ($this->page-1);

	}

	function get_end_point() {

		return $this->get_start_point() + $this->items_per_page-1;

	}

	function items_on_page() {


		$start_point = $this->get_start_point();
		$end_point = $this->get_end_point();
		$items_on_page = array();



		for($i = $start_point; $i <= $end_point; $i++) {
			
			if($i < $this->items_count) {

				array_push($items_on_page, $this->items[$i]);

			}

		}


		return $items_on_page;

	}

	function display_pagination() {

		$pages = null;

		if ($this->num_pages > 1) {

			ob_start();
			
			
			?>	
				<div class = "pages-container">
					
					<?php if ($this->page != 1): ?>
						<span class = "pagination first"><a href = "<?= $this->build_url(1) ?>">First</a></span>
						<span class = "pagination previous"><a href = "<?= $this->build_url($this->page - 1) ?>">Previous</a></span>
					<?php endif; ?>

			

			<?php for($i=1; $i <= $this->num_pages; $i++): ?>

					<?php $class = $i == $this->page ? "pagination page-num active" : "pagination page-num"; ?>
					<span class = "<?= $class ?>">
						<a href = "<?= $this->build_url($i) ?>"><?= $i ?></a>
					</span>

				

			<?php endfor; ?>

			<?php if ($this->page != $this->num_pages): ?>

				<span class = "pagination last"><a href = "<?= $this->build_url($this->num_pages) ?>">Last</a></span>
				<span class = "pagination next"><a href = "<?= $this->build_url($this->page + 1) ?>">Next</a></span>

			<?php endif; ?>

			<?php 

			$pages = ob_get_contents();

			ob_end_clean();

		}

		return $pages;

	}

	private function build_url($page_num) {
		$post = get_post(get_the_ID());
		$slug = $post->post_name;
		$base_url = get_site_url();

		$query_string = $_SERVER['REDIRECT_QUERY_STRING'] ? '?' . $_SERVER['REDIRECT_QUERY_STRING'] : null;

		$url = $base_url . '/products/' . $slug . '/' . $page_num . '/' . $query_string;

		return $url;
	}

	



}