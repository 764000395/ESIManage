<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/18
 * Time: 11:21
 */
class User_model extends CI_Model{

    function __construct(){
        parent::__construct();
        if(!empty($this->session->userdata('right')) && $this->session->userdata('right') != 'root'){
            $this->db->where(array('academy'=>$this->session->userdata('academy')));
        }
    }

    public function get_user_info($where_arr){
        return $this->db->get_where('user', $where_arr)
                         ->result_array();
    }

    //查询所有的学院
    public function get_user_academy(){
        $sql = "SELECT DISTINCT academy FROM `user`;";
        return $this->db->query($sql)->result_array();
    }
    //查找所有教师
    public function get_user_list($where_arr, $offset, $per_page, $order_str){
        return $this->db->order_by($order_str)
                         ->get_where('user', $where_arr, $per_page, $offset)
                         ->result_array();
    }
    //添加教师
    public function add_teacher($data_arr)
    {
         $this->db->insert('user',$data_arr);
         $aff_rows=$this->db->affected_rows();
         return $aff_rows;

    }
    //按工号,姓名查找教师
    public function search_teacher($col,$key)
    {
        $data['user']=$this->db->get_where('user',array($col=>$key))->result_array();
        $data['count']=$this->db->from('user')->where($col,$key)->count_all_results();
        return $data;
    }
    //按照学院查找教师
    public function search_teacher_byacademy($col,$key){
        $this->db->where_in($col,$key);
        $this->db->order_by('academy','desc');
        $this->db->order_by('identity','desc');
        $data['user']=$this->db->get_where('user')->result_array();
        $data['count']=$this->db->from('user')->where_in($col,$key)->count_all_results();
        return $data;
    }
    //删除教师
    public function del_teacher($job_num)
    {
        $this->db->delete('user',array('job_number'=>$job_num));
        $aff_rows=$this->db->affected_rows();
        return $aff_rows;

    }
    //管理员重置密码
    public function  reset_password($data_arr,$where_arr)
    {
         $this->db->update('user',$data_arr,$where_arr);
         $aff_rows=$this->db->affected_rows();
         return $aff_rows;
    }
   //完善个人信息，修改密码
    public function change_user_info($data_arr,$where_arr)
    {
       $this->db->update('user',$data_arr,$where_arr);
       $aff_rows=$this->db->affected_rows();
       return $aff_rows;
    }

    //这里原来的两个方法，del_article 和 reset_claim 现在放到aricle_model 中--二级管理员

    public function set_full_spell($full_spell, $where_arr){
        return $this->db->update('user', array('full_spell'=>$full_spell), $where_arr);
    }
}