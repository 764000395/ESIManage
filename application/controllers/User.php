<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29
 * Time: 18:56
 */
defined('BASEPATH') OR exit('No direct script access allowed');
class User extends MY_Controller{
    //个人信息展示
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model', 'um'); //载入model
    }

    public function per_msg()
    {
        $where_arr=array('job_number'=>$this->session->userdata('job_number'));
        $data = $this->um->get_user_info($where_arr)[0];
        $this->load->view('admin/user_info.html',$data);

    }
    //修改密码
    public function change_passwd()
    {
        $data['job_number']=$this->session->userdata('job_number');
        $data['status']=true;
        $this->load->view('admin/pass.html',$data);
    }
    public function change()
    {
        $this->load->library('pwdhash'); //载入phpass加密类


        $mpass=$this->input->get_post('mpass');
        $newpass= $this->pwdhash->HashPassword($this->input->get_post('newpass'));



        $data['job_number']=$this->session->userdata('job_number');

        $user= $this->um->get_user_info(array('job_number' => $data['job_number']));
        $status = $this->pwdhash->CheckPassword($mpass, $user[0]['password']);
        if($status)
        {
            $data_arr=array(
                'password'=>$newpass
            );
            $where_arr=array(
                'job_number'=>$data['job_number']
            );
            $aff_rows=$this->um->change_user_info($data_arr,$where_arr);
            if($aff_rows==1)
            {
                $data['msg']="修改成功";
                $data['jumpUrl']= site_url().'login/index';
                $data['waitSecond']=1;
                $this->load->view('admin/tips.html',$data);
                session_destroy();
            }else{
                $data['failure']=true;
                $data['msg']="修改失败";
                $data['jumpUrl']= site_url().'user/change_passwd';
                $data['waitSecond']=3;
                $this->load->view('admin/tips.html',$data);
            }
        }else{
            $data['status']=false;
            $this->load->view('admin/pass.html',$data);
        }
    }

    //设置英文名
    public function set_full_spell(){
        $full_spell = trim($this->input->post('full_spell'));
        if($this->um->set_full_spell($full_spell, array('job_number'=>$this->session->userdata('job_number')))){
            $this->session->set_userdata('full_spell', $full_spell);
            $data['msg']="设置成功";
            $data['waitSecond']=1;
        }else{
            $data['failure']=true;
            $data['msg']="设置失败，请重试";

        }
        $data['jumpUrl']= site_url('welcome/index');
        $this->load->view('admin/tips.html',$data);
    }
}