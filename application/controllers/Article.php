<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/11
 * Time: 11:12
 */
class Article extends  MY_Controller{
    public function __construct()
    {
        parent::__construct();
    }
    //加载文章认领页面，
    public  function article_claim()
    {

        //删除session
        $array_items=array('col','key');
        $this->session->unset_userdata($array_items);

        $this->load->model('article_model','art');
        //设置查询参数
        $where_arr=array(
          'owner'=>null,
        );
        $full_spell=$this->session->userdata('full_spell');
        $offset=$this->uri->segment(3);
        $per_page=20;
        $order_str='year';
        //查询数据库

        $article=$this->art->get_all_article($where_arr,$offset,$per_page,$order_str,$full_spell);
        $data=$this->deal_claim_time($article,s_year());
        $data['count']=$this->art->get_count($where_arr,$full_spell);
        //设置分页
        $this->load->library('pagination');
        $this->load->helper('paging');
        $url='article/article_claim';
        $uri_seg=3;
        $config=separate_page($per_page,$url, $data['count'],$uri_seg);
        $this->pagination->initialize($config);
        $data['links']= $this->pagination->create_links();
        $data['key']=null;

        $this->load->view('admin/list.html',$data);
    }

    //文章认领动作，
     public function claim()
    {
		if(strlen($this->session->userdata('job_number')) != 8){
			reset_msg('非法操作');
			return;
		}
        $this->load->model('article_model','art');

        if($this->uri->segment(3)!=null)
        {
            $keys=array($this->uri->segment(3));
        }else{
            $keys=$this->input->post('checkbox');
        }
        $data=array(
          'owner'=>$this->session->userdata('job_number'),
          'claim_time'=>time()
        );

        $full_spell=$this->session->userdata('full_spell');
        if (!isset($keys)){
            reset_msg("请选择文章");
        }
        if(count($keys)==1)
        {
            foreach($keys as $item=>$key)
            {
                // 改 返回空值 不一定是null
                if(empty($this->art->check_authoe_del($key,$full_spell)))
                {
                    msg_alert("您不能认领！请确认作者");
                    return;
                }else
                {
                    $this->art->mark_article($key,$data);
                    reset_msg("认领成功");
                }
            }

        }elseif (count($keys)>1)
        {
            $count=0;
            $all=count($keys);
            foreach($keys as $item=>$key)
            {
                //改 返回空值  不是null
                if(!empty($this->art->check_authoe_del($key,$full_spell))) {
                    $this->art->mark_article($key, $data);
                    $count++;
                }
            }
            reset_msg("您共选择".$all."篇，成功认领".$count.'篇');
        }else{
            reset_msg("请选择文章");
        }
    }


    //加载我的文章页面
     public function  my_article()
     {
         $this->load->model('article_model','art');
         $job_number=$this->session->userdata('job_number');

         //分页
         //设置查询参数
         $where_arr=array(
             'owner'=> $job_number
         );
         $offset=$this->uri->segment(3);
         $per_page=20;
         $order_str='year';
         //查询数据库

         $article=$this->art->get_all_article($where_arr,$offset,$per_page,$order_str);
         $data=$this->deal_claim_time($article,s_year());
         $data['count']=$this->art->get_count($where_arr);
         $data['count_claim']=$this->art-> claim_time($where_arr);
         //设置分页
         $this->load->library('pagination');
         $this->load->helper('paging');
         $url='article/my_article';
         $uri_seg=3;
         $config=separate_page($per_page,$url,$data['count'],$uri_seg);
         $this->pagination->initialize($config);
         $data['links']= $this->pagination->create_links();
         $this->load->view('admin/my_article.html',$data);
     }
     //搜索文章
    public  function search_article()
     {
         $this->load->model('article_model','art');

         $type=$this->input->get('select_mothod');
         $keyw=trim($this->input->get('keywords'));

         $column_name0='author_del';   //按作者姓名查找
         $column_name1='title';         //按文章标题查找
         $column_name2='subject';       //按学科领域查找

         //查询数据库
         if( $type==0)
          {
              $article=$this->art->select_article($column_name0, $keyw);
          }else if( $type==1)
          {
              $article=$this->art->select_article($column_name1, $keyw);
          }else if( $type==2)
          {
              $article=$this->art->select_article($column_name2, $keyw);
          }
         $data=$this->deal_claim_time($article,s_year());
         $data['links']=null;
         $data['key']=null;
         $data['count']=count($data['article']);
         $data['keyw']=$keyw;
         $data['type']=$type;
         $this->load->view('admin/list.html',$data);

     }
     //查看文章
     public  function  check_article()
     {
         $id=$this->uri->segment(3);
         $this->load->model('article_model','art');
         $data=$this->deal_claim_time($this->art->select_article_id($id),s_year());
         $this->load->view('admin/article_info.html',$data);
     }
	 
   //取消认领 改
    public function remove_article()
    {
        $this->load->model('article_model','art');
        $id=$this->uri->segment(3);
        // 入藏号格式不对，不是自己认领的不准取消
        if(!preg_match('/^WOS:\d+$/i', $id) || empty($this->art->check_owner($id))){
            reset_msg('非法操作');
            return;
        }

        $data=array(
            'owner'=>null,
            'claim_time'=>null
        );
        $aff_rows=$this->art-> remove_claim($id,$data);
        if($aff_rows==1)
        {
             $data['msg']="退选成功！";
             $data['jumpUrl']= site_url().'article/my_article';
             $data['waitSecond']=1;
             $this->load->view('admin/tips.html',$data);
        }
        else{
            $data['failure']=true;
            $data['msg']="退选失败！";
            $data['jumpUrl']= site_url().'article/my_article';
            $data['waitSecond']=3;
            $this->load->view('admin/tips.html',$data);
        }
    }
	
    public function deal_claim_time($article,$update_time)//处理history字段
    {
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

        }

        $data['article']=$article;
        return $data;
    }
}