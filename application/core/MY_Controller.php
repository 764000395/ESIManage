<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/18
 * Time: 15:16
 */
class MY_Controller extends CI_Controller{
    public function __construct()
    {
        parent::__construct();
        $job_number = $this->session->userdata('job_number');
        $name = $this->session->userdata('name');
        $identity = $this->session->userdata('identity');
        if(empty($job_number) || strlen($job_number) != 8 || empty($name) || !is_numeric($identity)){
            header('location:'.site_url('login'));exit;
        }
		
		//认领结束关闭普通用户登录
        if($identity != 1){
            header('location:'.site_url('login/ended'));exit;
        }
    }
}