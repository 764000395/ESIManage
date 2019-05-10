<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/18
 * Time: 14:52
 */
class Manage extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
        if($this->session->userdata('identity') < 1){
            msg_alert('该操作不被允许');
        }
        //所有关于用户的操作，放进这个数组的方法是允许二级管理员操作的
        $user_operating = array('index','add_teacher','add','select_teacher','del_teacher','reset_pwd','fill_info','fill');
        $operating = $this->uri->segment(2);  //获取当前要执行的操作

        
        if (!in_array($operating,$user_operating)) {
            if($this->session->userdata('job_number') != 10130004 && $this->session->userdata('job_number') != 16101210 && $this->session->userdata('job_number') != 17101211){
                msg_alert('操作非法');
            }
        }

    }

    public function index()
    {
        $this->load->model('User_model','um');
        //设置查询参数
        $offset=$this->uri->segment(3);
        $per_page=20;
        //查询数据库
        $data['user']= $this->um->get_user_list(array(),$offset,$per_page,null);
        if($this->session->userdata('right') != 'root'){
            $this->db->where(array('academy'=>$this->session->userdata('academy')));
        }
        $data['count']=$this->db->count_all_results('user');
        //获取所有学院单位的名称 ----为了添加按照学院查询教师
        $data['academy'] = $this->um->get_user_academy();
        //设置分页
        $this->load->library('pagination');
        $this->load->helper('paging');
        $url='manage/index';
        $uri_seg=3;
        $config=separate_page($per_page,$url,$data['count'],$uri_seg);
        $this->pagination->initialize($config);
        $data['links']= $this->pagination->create_links();

        $this->load->view('admin/people_manage.html',$data);
    }
    //加载添加教师页面
    public  function  add_teacher()
    {

        $this->load->view('admin/add_teacher.html');

    }
    //添加教师
    public  function add()
    {
        $this->load->library('pwdhash'); //载入phpass加密类
        $this->load->model('User_model','um');

        $job_number=$this->input->post('job_number');

        $user = $this->um->get_user_info(array('job_number' => $job_number));
        if(empty($user))
        {
            $data_arr = $this->input->post();
            $data_arr['password']=$this->pwdhash->HashPassword('a'.$job_number);

            $statu=$this->um->add_teacher($data_arr);
            if($statu==1)
            {
                $data['jumpUrl']= site_url().'manage/index';
                $data['waitSecond']=1;
                $data['msg']="添加成功！";
                $this->load->view('admin/tips.html',$data);
            }else{
                $data['failure']=true;
                $data['msg']="添加失败，请重试！";
                $data['jumpUrl']= site_url().'manage/add_teacher';
                $data['waitSecond']=3;
                $this->load->view('admin/tips.html',$data);
            }

        }else{
            $data['failure']=true;
            $data['msg']="添加失败:该教师信息已存在！";
            $data['jumpUrl']= site_url().'manage/add_teacher';
            $data['waitSecond']=3;
            $this->load->view('admin/tips.html',$data);
        }
    }

    //查询教师
    public  function select_teacher()
    {
        $this->load->model('User_model','um');
        $key=$this->input->get_post('keywords');
        $select=$this->input->get_post("select_mothod");

        $column_name0='job_number';
        $column_name1='name';
        $column_name2='academy';

        if($select==0)
        {
            $data=$this->um->search_teacher( $column_name0,$key);
        }else if($select==1)
        {
            $data=$this->um->search_teacher( $column_name1,$key);
        }else if($select==2){
            $key = $this->input->get_post('check_academy');
            $data = $this->um->search_teacher_byacademy($column_name2,$key);
        }
        $data['links']= null;
        //获取所有学院单位的名称 ----为了添加按照学院查询教师
        $data['academy'] = $this->um->get_user_academy();
        $this->load->view('admin/people_manage.html',$data);
    }
    //删除教师
    public  function  del_teacher()
    {
        $job_num=$this->uri->segment(3);
        $this->load->model('User_model','um');
        $statu=$this->um->del_teacher($job_num);
        if($statu==1)
        {
            $data['msg']="删除成功";
            $data['jumpUrl']= site_url().'manage/index';
            $data['waitSecond']=1;
            $this->load->view('admin/tips.html',$data);

        }else{
            $data['failure']=true;
            $data['msg']="删除失败";
            $data['jumpUrl']= site_url().'manage/index';
            $data['waitSecond']=3;
            $this->load->view('admin/tips.html',$data);
        }
    }
    //重置密码
    public function  reset_pwd()
    {
        $this->load->library('pwdhash');
        $job_num=$this->uri->segment(3);
        $passwd='a'.$job_num;
        $data_arr=array(
            'password'=>$this->pwdhash->HashPassword($passwd)
        );
        $where_arr=array(
            'job_number'=>$job_num
        );
        $this->load->model('User_model','um');
        $statu=$this->um->reset_password($data_arr,$where_arr);
        if($statu==1)
        {
            msg_alert("重置成功");
        }else{
            msg_alert("重置失败");
        }
    }
    //完善教师信息
    public function fill_info()
    {
        $this->load->model('user_model', 'user');
       $where_arr=array(
         'job_number'=>$this->uri->segment(3)
       );
        $data = $this->user->get_user_info($where_arr)[0];
        $this->load->view('admin/edit_teacher.html',$data);
    }
    //完善
    public function  fill()
    {
        $this->load->model('User_model','um');

        $data_arr = $this->input->post();

        $where_arr=array('job_number'=>$this->uri->segment(3));

        $aff_rows=$this->um->change_user_info($data_arr,$where_arr);
        if($aff_rows==1)
        {
           msg_alert("修改成功");
        }else{
           msg_alert("修改失败");
        }

    }

    //管理ESI论文
    public function article_manage()
    {

        $this->load->model('article_model','art');
        //设置查询参数
        $where_arr=array(
            'owner'=>null
        );
        $offset=$this->uri->segment(3);
        $per_page=20;
        $order_str='year';
        //查询数据库


        $article=$this->art->get_all_article(array(),$offset,$per_page,$order_str);
        $data=$this->deal_claim_time($article,s_year());
        $data['count']=$this->art->get_count(array());
        $data['unclaimed']=$this->art->get_count($where_arr);
        //设置分页
        $this->load->library('pagination');
        $this->load->helper('paging');
        $url='manage/article_manage';
        $uri_seg=3;
        $config=separate_page($per_page,$url,$data['count'],$uri_seg);
        $this->pagination->initialize($config);
        $data['links']= $this->pagination->create_links();
        $data['key']=null;
        $this->load->view('admin/article_manage.html',$data);
    }

    //搜索文章
    public  function search_article()
    {
        $this->load->model('article_model','art');
        $year=$this->input->get('year');
        $status=$this->input->get('status');
        $type=$this->input->get('select_mothod');
        $key=trim($this->input->get('keywords'));

        $column_arr=array('author_del','title','subject','owner','academy','accession_number');

       if($column_arr[$type]===null)//检测查询条件是否错误
       {
          error_alert("参数错误");
          return;
       }
       if($year!="—")
       {
           $year_arr=array(
               'year'=>$year
           );
       }else
       {
           $year_arr=array();
       }

        if($status==1)//设置查询条件（是否认领）
        {
            $where_arr=array(
                'owner is not null'=>null,
            );
            $where_arr=array_merge($year_arr,$where_arr);
        }elseif ($status==2)
        {
            $where_arr=array(
                'owner is null'=>null,
            );
            $where_arr=array_merge($year_arr,$where_arr);
        }else
        {
            $where_arr=array();
            $where_arr=array_merge($year_arr,$where_arr);
        }

        //查询数据库
        if($type=='4')
        {
            $where_arr=$year_arr;
            $article=$this->art->select_thesis_user($key,$where_arr);
        }else
        {
            $article=$this->art->select_article($column_arr[$type], $key,$where_arr);
        }
        $data=$this->deal_claim_time($article,s_year());
        $data['count']=count($data['article']);
        $data['links']=null;
        $data['key']=null;
        $data['status']=$status;
        $data['type']=$type;
        $data['keywords']=$key;
        $data['year']=$year;
        $this->load->view('admin/article_manage.html',$data);

    }
    //删除文章
    public function del_article()
    {
        $this->load->model('Article_model','am');
        $id=$this->uri->segment(3);
        $aff_rows=$this->am->del_article($id);
        if($aff_rows==1)
        {
            $data['msg']="删除成功";
            $data['jumpUrl']= site_url().'manage/article_manage';
            $data['waitSecond']=1;
            $this->load->view('admin/tips.html',$data);
        }else{
            $data['failure']=true;
            $data['msg']="删除失败";
            $data['jumpUrl']= site_url().'manage/article_manage';
            $data['waitSecond']=3;
            $this->load->view('admin/tips.html',$data);
        }

    }
    //重置文章认领者
    public function reset_claim()
    {
        $this->load->model('Article_model','am');
        $id=$this->uri->segment(3);
        $data_arr=array(
            'owner'=>null,
            'claim_time'=>null
        );
        $where_arr=array(
            'accession_number'=>$id
        );
        $aff_rows=$this->am->reset_claim($data_arr,$where_arr);
        if($aff_rows==1)
        {
            reset_msg("重置成功");
        }else{
            reset_msg("重置失败");
        }
    }
    //批量重置
    public function reset_claim_much()
    {

        $this->load->model('article_model','art');
        if($this->uri->segment(3)!=null)
        {
            $keys=array($this->uri->segment(3));
        }else{
            $keys=$this->input->post('checkbox');
        }
        if(!isset($keys))
        {
            msg_alert("请选择文章");
        }
        $data=array(
            'owner'=>null,
            'claim_time'=>null
        );
        foreach($keys as $item=>$key)
        {
            $this->art->mark_article($key,$data);
        }
        reset_msg("重置成功");
    }
    //查看文章
    public  function  check_article()
    {
        $id=$this->uri->segment(3);
        $this->load->model('article_model','art');
        $data=$this->deal_claim_time($this->art->select_article_id($id),s_year());
        $job_number=$data['article'][0]['owner'];
        if( $job_number!=null)
        {
            $name_info= $this->db->from('user')->where('job_number',$job_number)->get()->result_array();
            $data['article']= array_merge($data['article'],$name_info);
        }
        $this->load->view('admin/article_info1.html',$data);
    }
	
	//文章指派 根据工号指派文章
	public function ESI_assign(){
		$owner = $this->input->post('assign_owner'); //被指派着工号（认领人）
		$accession_number = $this->input->post('accession_number'); //论文入藏号
		//判断工号是否符合规范 必须位数字且为8位 判断入藏号是否符合规范 WOS:数字
		if(!is_numeric($owner) || strlen($owner) != 8 || !preg_match('/^WOS:\d+$/i', $accession_number)){
			msg_alert('请输入正确的工号或入藏号！');
		}
		$this->load->model('article_model', 'article');
		$is_claim = $this->article->get_article_info(array('accession_number' => $accession_number));
		if(empty($is_claim)){ //检查要指派的文章是否存在
			msg_alert('该论文不存在！');
		}
		if(!empty($is_claim[0]['owner'])){ //检查要指派的文章是否已经被认领
			msg_alert('该文章已被认领，请重置后再指派！');
		}
		$status = $this->db->update('thesis', array('owner' => $owner, 'claim_time' => time()), array('accession_number' => $accession_number));
		if($status){
			msg_alert('指派成功！');
		}else{
			msg_alert('指派失败！');
		}
    }
    
       //文章批量指派 根据工号一次指派多篇文章
       public function ESI_assign_multiple(){
        $owner = $this->input->post('assign_owner'); //被指派着工号（认领人）
        if(empty($owner)){
            msg_alert("工号不能为空，请填写工号！");
        }

        $accession_number_array = $this->input->post('checkbox'); //论文入藏号
        if(empty($accession_number_array)){
            msg_alert("未选择要指派的文章，请选择要指派的文章！");
        }
        foreach($accession_number_array as $accession_number){
            // //判断工号是否符合规范 必须位数字且为8位 判断入藏号是否符合规范 WOS:数字
            if(!is_numeric($owner) || strlen($owner) != 8 || !preg_match('/^WOS:\d+$/i', $accession_number)){
                msg_alert('请输入正确的工号或入藏号！');
            }
            $this->load->model('article_model', 'article');
            $is_claim = $this->article->get_article_info(array('accession_number' => $accession_number));
            if(empty($is_claim)){ //检查要指派的文章是否存在
                msg_alert('该论文不存在！');
            }
            if(!empty($is_claim[0]['owner'])){ //检查要指派的文章是否已经被认领
                msg_alert('存在已被认领的文章，请检查重置后再指派！');
            }
        }
        $access_all = 0;
        $access_success = 0;
        $access_fail = 0;
        foreach($accession_number_array as $accession_number){
		    $status = $this->db->update('thesis', array('owner' => $owner, 'claim_time' => time()), array('accession_number' => $accession_number));
            $access_all++;
            if($status){
                $access_success++;
            }else{
                $access_fail++;
            }
        }
        $msg = "共指派".$access_all."篇文章，成功".$access_success."篇，失败".$access_fail."篇！";
        msg_alert($msg);
        // if($status){
		// 	msg_alert('指派成功！');
		// }else{
		// 	msg_alert('指派失败！');
		// }
    }
	
	
	//人员信息
	public function get_user_json($owner){
		$this->load->model('user_model', 'user'); //载入user_model
		$data = $this->user->get_user_info(array('job_number' => $owner)); //获取教师信息
		if(empty($data)){
			$return_data = array(
				'code' => 400,
				'msg' => '未找到该教师信息！请检查工号是否正确！',
			);
		}else{
			$array = array(
				'job_number' => $data[0]['job_number'],
				'name' => $data[0]['name'],
				'academy' => $data[0]['academy'],
			);
			$return_data = array(
				'code' => 200,
				'msg' => '获取成功',
				'data' => $array,
			);
		}
		echo json_encode($return_data, JSON_UNESCAPED_UNICODE);
	}

    /* Nature相关 */
    //nature列表
    public function nature_list($type = 'all', $offset = 0){
        if($type == 'all' || !is_numeric($type)){
            $where_arr = array();
        }else{
            $where_arr = array('status' => $type);
        }
        $per_page = 20;
        $this->load->model('nature_model');
        $data['count'] = $this->nature_model->all_counts($where_arr);
        $data['nature'] = $this->nature_model->get_nature_list($where_arr, $offset, $per_page);
        $config = array(
            'base_url' => site_url('manage/nature_list/'.$type),
            'total_rows'=> $data['count'],
            'per_page' => $per_page,
        );
        $this->load->library('pagination', $config);
        $data['links'] = $this->pagination->create_links();
        $data['status']=$type;
        $this->load->view('admin/nature_list.html', $data);
    }

    //nature 审核
    public function nature_check($id){
        $this->load->model('nature_model');
        $data = array(
            'status' => $this->input->post('status'),
            'comment' => $this->input->post('comment')
        );
        if($this->nature_model->update('nature', $data, array('id'=>$id))){
            echo $this->nature_model->get_json(200, '操作成功！');
        }else{
            echo $this->nature_model->get_json(200, '操作失败！');
        }
    }

    //nature搜索
    public function nature_search($type, $keywords=null, $status = 'all'){
        $keywords = urldecode($keywords);
        //echo $type, $keywords;die;
        $type_arr = array('first_author', 'corresponding_author', 'title', 'periodical', 'owner');
        if($type_arr[$type] === null){
            error_alert('参数错误');
            return;
        }
        $where_arr = array();
        if($status != 'all'){
            $where_arr = array('status'=>$status);
        }
        $this->load->model('nature_model');
        $data['nature'] = $this->nature_model->nature_search($type_arr[$type], $keywords, $where_arr);
        $data['count'] = count($data['nature']);
        $data['keywords'] = $keywords;
        $data['type'] = $type;
        $data['status']=$status;
        $this->load->view('admin/nature_list.html', $data);
    }
    //nature删除
    public function nature_delete($id){
        $data['jumpUrl']= site_url('manage/nature_list');
        $data['waitSecond']=3;


        if($this->db->delete('nature', array('id'=>$id))){
            $data['msg']="删除成功！";
        }else{
            $data['msg'] = '删除失败';
            $data['failure'] = true;
        }
        $this->load->view('admin/tips.html',$data);
    }
    //nature批量删除
    public function nature_delete_mutilate(){
        $nature_array = $this->input->post('checkbox_nature');
        $all = 0;
        $success = 0;
        $fail = 0;
        foreach($nature_array as $id)
        {
            $all++;
            if($this->db->delete('nature', array('id'=>$id))){
                $success++;
            }else{
                $fail++;
            }
        }
        $show = "总共删除".$all."篇，成功".$success."篇，失败".$fail."篇！";
        $data['msg'] = $show;
        // msg_alert($show);
        $data['jumpUrl']= site_url('manage/nature_list');
        $data['waitSecond']=8;


        // if($this->db->delete('nature', array('id'=>$id))){
        //     $data['msg']="删除成功！";
        // }else{
        //     $data['msg'] = '删除失败';
        //     $data['failure'] = true;
        // }
        $this->load->view('admin/tips.html',$data);
    }
    //nature index 论文管理导出表格
    public function export_excel_nature()
    {
      $status=$this->input->get('status');
      $type=$this->input->get('type');
      $keywords=$this->input->get('keywords');

        $title=array('论文标题','第一作者','通讯作者','全部作者','第一单位','发表期刊','发表时间','卷','期','页','作者工号','作者姓名','作者学院/单位','添加时间','审核状态','审核意见');
        $fileName="";
        $savePath="";
        $isDown=true;

     if($keywords==null)
     {
        $data['article']=$this->nature_list_export($status);
     }else
     {
         $data['article']=$this->nature_search_export($type,$keywords,$status);
     }
        $this->load->model('format_model');
        $this->format_model->export_nature($title,$data['article'],$fileName, $savePath,$isDown);
    }
    //导出表格时使用的搜索方法 搜索框无值时
    public function nature_list_export($status='all')
    {
        if($status == 'all' || !is_numeric($status)){
            $where_arr = array();
        }else{
            $where_arr = array('status' => $status);
        }
        $this->load->model('nature_model');
        $data = $this->nature_model->get_nature_list($where_arr, $offset=0, $per_page=0);
        return $data;

    }
    //导出表格时使用的搜索方法  搜索框有值时
    public function  nature_search_export($type, $keywords, $status = 'all')
    {
        $keywords = urldecode($keywords);
        //echo $type, $keywords;die;
        $type_arr = array('first_author', 'corresponding_author', 'title', 'periodical', 'owner');
        if($type_arr[$type] === null){
            error_alert('参数错误');
            return;
        }
        $where_arr = array();
        if($status != 'all'){
            $where_arr = array('status'=>$status);
        }
        $this->load->model('nature_model');
        $data= $this->nature_model->nature_search($type_arr[$type], $keywords, $where_arr);
        return $data;
    }

     //导出excel ESI论文管理
    public function export_excel()
    {

        $title=array('入藏号','地址','DOI','论文标题','作者全称','来源','学科领域','卷','期','页','出版年份','被引频次','期刊影响因子','更新时间','新增引用次数','认领人','认领时间','所在单位/学院');
        $fileName="";
        $savePath="";
        $isDown=true;

        $this->load->model('article_model','art');
        $year=$this->input->get('year');
        $status=$this->input->get('status');
        $type=$this->input->get('select_mothod');
        $key=trim($this->input->get('keywords'));

        $column_arr=array('author_del','title','subject','owner','academy');

        if($column_arr[$type]===null)//检测查询条件是否错误
        {
            error_alert("参数错误");
            return;
        }
        if($year!="—")
        {
            $year_arr=array(
                'year'=>$year
            );
        }else
        {
            $year_arr=array();
        }

        if($status==1)//设置查询条件（是否认领）
        {
            $where_arr=array(
                'owner is not null'=>null,
            );
            $where_arr=array_merge($year_arr,$where_arr);
        }elseif($status==2)
        {
            $where_arr=array(
                'owner is null'=>null,
            );
            $where_arr=array_merge($year_arr,$where_arr);
        }else
        {
            $where_arr=array();
            $where_arr=array_merge($year_arr,$where_arr);
        }

        //查询数据库
        if($type=='4')
        {
            $where_arr=$year_arr;
            $article=$this->art->select_thesis_user($key,$where_arr);
        }else
        {
            $article=$this->art->select_article($column_arr[$type], $key,$where_arr);
        }
            $data=$this->deal_claim_time($article,s_year());
            $this->load->model('format_model');
            $this->format_model->export($title,$data['article'],$fileName, $savePath,$isDown);
    }

    /*claim_time 查询相关*/

    //加载视图，数据
    public function claim_list()
    {

        $this->load->view('admin/claim_time.html');
    }

    //搜索文章
    public function search_article_claim()
    {

       $this->load->model("article_model","am");

       $update_time=trim($this->input->post('begin_time'));
       $select_mothod=$this->input->post('select_mothod');
       $keywords=trim($this->input->post('keywords'));

       $data=null;
       if($select_mothod=="0")  //搜索方式为按学院单位
       {

           $article=$this->am->select_article_claim($update_time,$keywords); //所有符合条件的记录

           $data=$this->deal_claim_time($article,$update_time); //调用下面函数


       }elseif ($select_mothod=="1")
       {
          $article=$this->am->select_article_claim_num($update_time,$keywords);
          $data=$this->deal_claim_time($article,$update_time);//调用下面函数
       }else
       {
           error_alert("暂不支持该搜索方式");
       }

        $data['begin_time']=$update_time;//保存表单数据
        $data['select_mothod']=$select_mothod;
        $data['keywords']=$keywords;
        $this->load->view("admin/claim_time.html",$data);

    }

    public function deal_claim_time($article,$update_time)//处理history字段
    {
        $count=0;//统计查询条数
        $up_time=0;
        foreach ($article as &$v)      //循环处理每条记录里的history
        {
            $increase_claim=0;
            $claim_time=explode("-",$v['history']);//将history字段分解为数组
            foreach ($claim_time as $c) {     //对数组进行查找

                if(substr($c,0,strpos($c, ':'))==$update_time)// 截取数组中每个元素 “:”前的字符串，如果与查找条件$begin_time相等
                {                                                              //则截取“:”后的字符串赋给$begin_claim
                    $increase_claim=substr($c,strpos($c, ':')+1);
                   $up_time=substr($c,0,strpos($c, ':'));
                }
            }

            $v['increase_claim']=$increase_claim;
            $v['update_time']=$up_time;

          $count++;    //文章记录加一
        }

        $data['article']=$article;
        $data['count']=$count;
        return $data;
    }

    /*导出被引用次数表格*/
   public function export_excel_claim()  //导出表格
   {

       $this->load->model("article_model","am");
       $begin_time=trim($this->input->post('begin_time'));
       $select_mothod=$this->input->post('select_mothod');
       $keywords=trim($this->input->post('keywords'));

       //设置表格
       $title=array('入藏号','地址','DOI','论文标题','作者全称','来源','学科领域','卷','期','页','出版年','被引频次',$begin_time.'新增引用频次','期刊影响因子','认领人','所在单位/学院','认领时间');
       $fileName="";
       $savePath="";
       $isDown=true;

       $data=null;
       if($select_mothod=="0")  //搜索方式为按学院单位
       {

           $article=$this->am->select_article_claim($begin_time,$keywords); //所有符合条件的记录

           $data=$this->deal_claim_time($article,$begin_time); //调用下面函数


       }elseif ($select_mothod=="1")
       {
           $article=$this->am->select_article_claim_num($begin_time,$keywords);
           $data=$this->deal_claim_time($article,$begin_time,$begin_time);//调用下面函数
       }else
       {
           error_alert("暂不支持该搜索方式");
       }

       $this->load->model('format_model');
       $this->format_model->export_claim($title,$data['article'],$fileName, $savePath,$isDown);
   }
   
}