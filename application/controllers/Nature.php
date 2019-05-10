<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/27
 * Time: 16:58
 */
class Nature extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('nature_model');
    }

    /*添加nature*/
    public function add_nature(){
        if(empty($this->input->post('title'))){ //没有post过来数据，为view页
            $this->load->view('nature/add_nature.html');
        }else{

            $path = '/uploads/pdf/';
            $article_pdf = $path.$this->upload_pdf('article_pdf');
            $include_pdf = $path.$this->upload_pdf('include_pdf');
            $data = $this->input->post();
            $data['publish_time'] = strtotime($data['publish_time']);
            $data['article_pdf'] = $article_pdf; //文章电子版
            $data['include_pdf'] = $include_pdf; //收录证明PDF文件
            $data['add_time'] = time();
            $data['owner'] = $this->session->userdata('job_number');
            if($this->nature_model->insert('nature', $data)){
                echo $this->nature_model->get_json(200, '提交成功');
            }else{
                echo $this->nature_model->get_json(400, '服务器未知错误，请联系管理员！');
            }
        }
    }

    //上传pdf文件
    private function upload_pdf($name){
        $config = array(
            'upload_path' =>'./uploads/pdf/',
            'allowed_types' => 'pdf',
            'file_name' => md5(time().mt_rand(0, 9999))
        );
        $this->load->library('upload', $config);
        if(!$this->upload->do_upload($name)){
            $error = $this->upload->display_errors('', '');
            echo $this->nature_model->get_json(400, $error);
            exit;
        }else{
            return $this->upload->data('file_name');
        }
    }

    /* 我的nature */
    public function my_nature($type = 'all', $offset = 0){
        if($type == 'all' || !is_numeric($type)){
            $where_arr = array('owner'=>$this->session->userdata('job_number'));
        }else{
            $where_arr = array('owner'=>$this->session->userdata('job_number'), 'status' => $type);
        }
        $per_page = 10;
        $data['count'] = $this->nature_model->all_counts($where_arr);
        $data['nature'] = $this->nature_model->get_nature_list($where_arr, $offset, $per_page);
        $config = array(
            'base_url' => site_url('nature/my_nature/'.$type),
            'total_rows'=> $data['count'],
            'per_page' => $per_page,
        );
        $this->load->library('pagination', $config);
        $data['links'] = $this->pagination->create_links();
        $this->load->view('nature/list.html', $data);
    }

    /* nature信息 */
    public function nature_info($id){
        if($this->session->userdata('identity') == 1){
            $where_arr = array('id'=>$id);
        }else{
            $where_arr = array('id'=>$id, 'owner'=>$this->session->userdata('job_number'));
        }
        $nature = $this->nature_model->get_nature_info($where_arr);
        if(empty($nature)){
            $data['failure'] = true;
            $data['msg'] = '对不起，权限不足，无法查看！';
            $data['jumpUrl'] = site_url('nature/my_nature');
            $this->load->view('admin/tips.html', $data);
        }else{
			if($this->session->userdata('identity') == 1){ //管理员操作，查看录入人信息 
				$this->load->model('user_model', 'user');
				$owner_num = $nature[0]['owner']; //录入人的工号
				$owner = $this->user->get_user_info(array('job_number' => $owner_num));
				if(empty($owner)){
					msg_alert('非法操作！');
				}
				$data['owner'] = $owner[0]; 
			}
            $data['nature'] = $nature[0];
            $this->load->view('nature/info.html', $data);
        }
    }

    /* nature index 修改 */
    public function edit($id){
        //权限验证
        if ($this->session->userdata('identity') > 0) { //管理员修改，不需要验证录入人是否一致
            $where_arr = array('id' => $id);
        } else { // 用户修改nature信息，验证nature信息是否属于本人
            $where_arr = array('id' => $id, 'owner' => $this->session->userdata('job_number'));
        }

        $nature = $this->nature_model->get_nature_info($where_arr);
        if(empty($nature)) {
            $data['failure'] = true;
            $data['msg'] = '对不起，您不能修改他人文章！';
            $data['jumpUrl'] = site_url('nature/my_nature');
            $this->load->view('admin/tips.html', $data);
            return;
        }

        if($this->session->userdata('identity') == 0 && $nature[0]['status'] == 1){
            $data['failure'] = true;
            $data['msg'] = '对不起，该论文已过审，不能修改！';
            $data['jumpUrl'] = site_url('nature/my_nature');
            $this->load->view('admin/tips.html', $data);
            return;
        }

        if(empty($this->input->post('title'))) {
            //action == view
            $data = $nature[0];
            $this->load->view('nature/edit_nature.html', $data);
        }else{
            //action == do
            $path = '/uploads/pdf/';
            if(trim($this->input->post('pre_article_pdf')) != '电子版PDF文件'){ //文章电子版
                $data['article_pdf'] = $path.$this->upload_pdf('article_pdf');
                @unlink(substr($nature[0]['article_pdf']), 1, strlen(substr($nature[0]['article_pdf'])));
            }
            if(trim($this->input->post('pre_include_pdf')) != '收录证明'){ //收录证明PDF文件
                $data['include_pdf'] = $path.$this->upload_pdf('include_pdf');
                @unlink(substr($nature[0]['include_pdf']), 1, strlen(substr($nature[0]['include_pdf'])));
            }
            $data['status'] = 0; //重置审核状态
            $data['comment'] = ''; //重置审核意见
            $data = array_merge($data, $this->input->post());
            $data['publish_time'] = strtotime($data['publish_time']);

            unset($data['pre_article_pdf'], $data['pre_include_pdf']);
            if($this->nature_model->update('nature', $data, array('id'=>$id))){
                echo $this->nature_model->get_json(200, '修改成功');
            }else{
                echo $this->nature_model->get_json(400, '服务器未知错误，请联系管理员！');
            }
        }
    }
}