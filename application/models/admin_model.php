<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_model class.
 * 
 * @extends CI_Model
 */
class Admin_model extends CI_Model {

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() 
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Get data for main page of admin panel
	 */
	public function get_comments($limit)
	{
		//get comments
		$this->db->select('comments.id, users.login, comments.comment_text, comments.comment_created');
		$this->db->from('comments');
		$this->db->join('users', 'users.id = comments.user_id');
		$this->db->order_by('comments.comment_created', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();
		
		return $query->result_array();
	}

	public function get_requests($limit)
	{
		//get requests
		$this->db->select('order_for_assessment.id, order_for_assessment.order_text, order_for_assessment.order_created, users.login, mediafiles.media_link');
		$this->db->from('order_for_assessment');
		$this->db->join('users', 'users.id = order_for_assessment.user_id');
		$this->db->join('mediafiles', 'mediafiles.id = order_for_assessment.photo_id');
		$this->db->order_by('order_for_assessment.order_created', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();

        return $query->result_array();
	}

	public function get_avtos()
	{
		//get comments
		$this->db->from('avto');
		$query = $this->db->get();
		
		return $query->result_array();
	}

	public function create_content() 
	{	
		//add content
		$data = array(
			'title'   		=> $this->input->post('title'),
			'content_text'  => $this->input->post('text'),
			'meta'   		=> $this->input->post('meta'),
			'category'  	=> $this->input->post('category'),
			);
		
		return $this->db->insert('Content', $data);
	}

	public function delete_data($id, $table)
	{
		//delete data
		$this->db->from($table);
		$this->db->where('id', $id);
		$query = $this->db->delete();
		
		return TRUE;
	}

	//public function get_all($title, )
}