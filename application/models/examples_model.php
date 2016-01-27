<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Examples_model extends CI_Model {

    public function get_examples_all()
    {
        $this->db->select('*');
        $this->db->from('example_works');
        $query = $this->db->get();
        $result = $query->result_array();
        $result = $this->__add_photo($result);
        return $result;
    }
    public function get_examples($id)
    {
        $this->db->select('*');
        $this->db->from('example_works');
        $this->db->where("example_works.id = $id");
        $query = $this->db->get();
        $result = $query->result_array();
        $result = $this->__add_photo($result);
        return $result;
    }

    /**
     * @access private
     * 
     * Method replaces numbers of photo by their filenames, 
     * constructing them using example ids and number of photos
     * 
     */
    private function __add_photo($examples)
    {
        $new_examples = array();
        foreach ($examples as $item) {

            $num_before = $item['photo_before'];
            $num_after = $item['photo_after'];
            $id = $item['id'];
            $photo_after = $photo_before = array();

            for ($i=0; $i < $num_before; $i++) { 
                $photo_before[] = '/img/example/before/' 
                . $id . '_' . $i . '.jpg';    
            }

            for ($i=0; $i < $num_after; $i++) { 
                $photo_after[] = '/img/example/after/' 
                . $id . '_' . $i . '.jpg';    
            }

            $new_item = $item;
            $new_item['photo_before'] = $photo_before;
            $new_item['photo_after'] = $photo_after;
            $new_examples[] = $new_item;
        }
        return $new_examples;
    }
}
