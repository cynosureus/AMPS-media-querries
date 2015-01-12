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



	}

}