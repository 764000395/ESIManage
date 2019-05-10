<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/11
 * Time: 11:23
 */
class Article_model extends  CI_Model{
    private $time_limit;  //设置时间限制
    private $s_year;

    private $only_show_no_claim; //只显示未认领
    private $manage_see;
    public function __construct()
    {
        parent::__construct();
        $this->s_year=s_year();
        $this->time_limit=array("year >"=> $this->s_year-10);
        $this->only_show_no_claim = true; // 只显示未认领。值为 false 时全部显示
        $this->manage_see = false; //管理员能不能看之前的，false 不能看；

        //管理员能看之前的而普通用户不能看
        /*if(strtolower($this->uri->segment(1)) !== 'article'){
            $this->manage_see = true;
        }*/
    }

    //统计数量
    public function get_count($where_arr=array(),$full_spell=false)
    {
        $where_arr=array_merge($where_arr,$this->time_limit);

        //只显示未认领 管理员不能看之前的
        if($this->only_show_no_claim && !$this->manage_see){
            $where_arr['save'] = 1;
        }

        if($full_spell)
        {
            $this->db->like('author_del',$full_spell);
        }
        return $this->db->get_where('thesis',$where_arr)->num_rows();
    }

    //查找所有文章
    public function get_all_article($where_arr=array(), $offset, $per_page, $order_str,$full_spell=false){
        $where_arr=array_merge($where_arr,$this->time_limit);

        //只显示未认领 管理员不能看之前的
        if($this->only_show_no_claim && !$this->manage_see){
            $where_arr['save'] = 1;
        }

        if($full_spell)
        {
           $this->db->like('author_del',$full_spell);
        }
        return $this->db->order_by($order_str,'DESC')->get_where('thesis',$where_arr,$per_page,$offset)->result_array();
    }

    //单个查找
    public function get_article_info($where_arr=array()){
        $where_arr=array_merge($where_arr,$this->time_limit);

        //只显示未认领 管理员不能看之前的
        if($this->only_show_no_claim && !$this->manage_see){
            $where_arr['save'] = 1;
        }

        return $this->db->get_where('thesis', $where_arr)
            ->result_array();
    }
    //单个查找 / 更新数据时使用，不受save的影响
    public function get_article_info_for_upload_file($where_arr=array()){
        $where_arr=array_merge($where_arr,$this->time_limit);

        return $this->db->get_where('thesis', $where_arr)
            ->result_array();
    }
    //检测作者字段里有没有登录者的姓名
    public function check_authoe_del($key,$full_spell)
    {
        return  $this->db->like('author_del',$full_spell)->get_where('thesis',array('accession_number'=>$key, 'owner is null'=>null))->result_array();
    }
    //标记被认领过的文章，$key为数据库中的accession_number
    public  function mark_article($key,$data)
    {
       $this->db->update('thesis',$data,array('accession_number'=>$key));
    }
    //-----下面的这两个方法是从user_model中换到这里的
    //重置文章认领者
    public function reset_claim($data_arr,$where_arr)
    {
        $this->db->update('thesis',$data_arr,$where_arr);
        $aff_rows=$this->db->affected_rows();
        return $aff_rows;
    }
    //删除文章
    public function del_article($id)
    {
        $this->db->delete('thesis',array('accession_number'=>$id));
        $aff_rows=$this->db->affected_rows();
        return $aff_rows;
    }
    
    //thesis表中搜索文章，按文章标题，作者姓名，学科领域,教师工号
    public function select_article($col_name,$key,$where_arr=array())
   {
		$where_arr=array_merge($this->time_limit,$where_arr);

       //只显示未认领 管理员不能看之前的
       if($this->only_show_no_claim && !$this->manage_see){
           $where_arr['save'] = 1;
       }

		if(array_key_exists('owner is not null', $where_arr)){
            $this->db->order_by('claim_time DESC');
        }
		$data = $this->db->like($col_name,$key)->get_where('thesis',$where_arr)->result_array();
		return $data;
       //Molecular characteristics
       //echo $this->db->last_query();die();
   }
   //thesis表与user表联立搜索，按学院单位
    public function select_thesis_user($key,$where_arr=array())
    {
        $where_arr=array_merge($where_arr,$this->time_limit);

        //只显示未认领 管理员不能看之前的
        if($this->only_show_no_claim && !$this->manage_see){
            $where_arr['save'] = 1;
        }

		$this->db->order_by('claim_time DESC');
        return $this->db->join('thesis','thesis.owner = user.job_number')->like('academy',$key)->get_where('user',$where_arr)->result_array();
    }

   //根据文章编号查找文章
    public function select_article_id($id)
    {
        //$where_arr=array(,$this->time_limit);

        //只显示未认领 管理员不能看之前的
        if($this->only_show_no_claim && !$this->manage_see){
            $where_arr['save'] = 1;
        }

        $data=$this->db->get_where('thesis',array('accession_number'=>$id))->result_array();
        return $data;
    }
	
	//检查认领人与取消人是否一致 改
    function check_owner($id){
        return $this->db->get_where('thesis', array('accession_number'=>$id, 'owner'=>$this->session->userdata('job_number')))->result_array();
    }
   //取消认领
    public function  remove_claim($id,$data)
    {
        $this->db->update('thesis',$data,array('accession_number'=>$id));
        $aff_rows=$this->db->affected_rows();
        return $aff_rows;
    }
    /*论文认领次数相关查询*/

   //按学院单位查找
    public function select_article_claim($begin_time,$like_str)
    {
        $where_arr= $where_arr=$this->time_limit;

        //只显示未认领 管理员不能看之前的
        if($this->only_show_no_claim && !$this->manage_see){
            $where_arr['save'] = 1;
        }

        $article=$this->db->join('thesis','user.job_number=thesis.owner')->like("history",$begin_time.":")->like('academy',$like_str)->get_where("user",$where_arr)->result_array();
        return $article;
    }
    //按工号查找
    public function select_article_claim_num($end_time,$like_str)
    {
         $where_arr=$this->time_limit;

        //只显示未认领 管理员不能看之前的
        if($this->only_show_no_claim && !$this->manage_see){
            $where_arr['save'] = 1;
        }

         $article=$this->db->join('thesis','user.job_number=thesis.owner')->like("history",$end_time.":")->like("job_number",$like_str,"right")->get_where("user",$where_arr)->result_array();
        return $article;
    }
    public  function  claim_time($where_arr)
    {
        $where_arr=array_merge($where_arr,$this->time_limit);
        $article= $this->db->get_where('thesis',$where_arr)->result_array();
        $update_time=$this->s_year;//信息更新时间
        return $this->deal_claim_time($article,$update_time);
    }
    public function deal_claim_time($article,$update_time)//处理history字段
    {
        $count=0;
        foreach ($article as &$v)      //循环处理每条记录里的history
        {
            $increase_claim=0;
            $claim_time=explode("-",$v['history']);//将history字段分解为数组
            foreach ($claim_time as $c) {     //对数组进行查找
                if(substr($c,0,strpos($c, ':'))==$update_time)// 截取数组中每个元素 “:”前的字符串，如果与查找条件$update_time相等
                {                                                              //则截取“:”后的字符串赋给$begin_claim
                    $increase_claim=substr($c,strpos($c, ':')+1);
                    $count+=$increase_claim;
                }
            }
        }
        return $count;
    }
}