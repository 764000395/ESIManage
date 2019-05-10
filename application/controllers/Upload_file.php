<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/21
 * Time: 16:26
 */
class Upload_file extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
        if($this->session->userdata('identity') < 1){
            msg_alert('该操作不被允许');
        }
    }
    /**
    * 加载、更新新的ESI文章
    */
    public function index()
    {
        $data['url'] = site_url().'upload_file/upload_excle';
        $this->load->view('admin/upload_file.html',$data);
    }
    public function upload_excle()
    {
        set_time_limit(0);
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'xlsx|xls';
        $config['max_size'] = '10000';
        $config['file_name']=time().mt_rand(1000,9999);


        $this->load->library('upload',$config);

        $this->upload->do_upload('excle');
        $wrong=$this->upload->display_errors();
        if($wrong)
        {
          $arr = array('code'=>400, 'msg'=>$wrong);
        }else{
            $info=$this->upload->data();
            //存session 文件名，和后缀名
            $info_arr=array(
                'path'=>$config['upload_path'].$info['file_name'],
                'ext'=>$info['file_ext']
            );
            $this->session->set_userdata( $info_arr);
            $this->info_import($this->session->userdata('path'),ltrim($this->session->userdata('ext'),'.'));
            $arr = array('code'=>200, 'msg'=>'导入成功');
        }
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    }
    public function info_import($filename,$ext='xls')
    {
        $this->load->model('article_model','am');
        $this->load->library('PHPExcel');  //载入PHPExcel类库
        $PHPExcel=new PHPExcel();
        $PHPReader = new PHPExcel_Reader_Excel2007();   //建立reader对象
        if(!$PHPReader->canRead($filename)){
            $PHPReader = new PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($filename)){
                die('No Excel!');
            }
        }

        $PHPExcel=$PHPReader->load($filename);      //装载工作表

        $currentSheet=$PHPExcel->getSheet(0);
        $allColumn=$currentSheet->getHighestColumn();            //获取总列数
        $allRow=$currentSheet->getHighestRow();                  //获取总行数

        for($currentRow=2;$currentRow<=$allRow;$currentRow++)
        {
            for($currentColumn='A';$currentColumn<=$allColumn;$currentColumn++)
            {
                $address=$currentColumn.$currentRow; //数据坐标

                $data[$currentColumn]=$currentSheet->getCell($address)->getValue();
            }
            $this->save_data($data,$currentRow);
        }

        $this->session->set_userdata('import', 'success');
    }

    public function save_data($data,$currentRow)
    {

        $accession_number=$data['A'];
        $address=$data['B'];
        $doi=$data['C'];
        $title=$data['D'];
        $author=$data['E'];
        $source=$data['F'];
        $subject=$data['G'];
        $roll=$data['H'];
        $period=$data['I'];
        $page=$data['J'];
        $year=$data['K'];
        $times_cited=$data['L'];
        $impact_factor=$data['M'];
        $increase_cited=$data['N'];

        $author_del= str_replace(', ',' ', preg_replace('/([a-z|A-Z]{2,})(, | )([a-z|A-Z]{2,})( |-)([a-z|A-Z]{2,})(;|])/', '${1} ${3}${5}${6}', $address));//格式化作者字段

        $history=s_year() .":" .$increase_cited ."-";  //设置每年新增的引用次数


        $where_arr=array(
            'accession_number'=> $accession_number
        );

        $article=$this->am->get_article_info_for_upload_file($where_arr);//查找是否有这条记录
        if($article)//存在
        {
            if(strpos($article[0]['history'],s_year() .":")===false)// 该记录存在且history未被更新
            {
                $info_arr2=array(             //更新信息
                    'times_cited'=>$times_cited,
                    'history'=>$article[0]['history'].$history  //history字段追加每年的引用次数
                );
            }else  //该记录存在history已被更新，（上传失败重新上传）
            {
                $info_arr2=array(             //更新信息
                    'times_cited'=>$times_cited
                );
            }

            $status=$this->db->update("thesis",$info_arr2,$where_arr);//对引用次数进行更新
        }else{ //不存在
            $info_arr1=array(                //插入信息
                'accession_number'=> $accession_number,
                'address'=>$address,
                'doi'=>$doi,
                'title'=>$title,
                'author'=>$author,
                'source'=>$source,
                'subject'=>$subject,
                'roll'=>$roll,
                'period'=>$period,
                'page'=>$page,
                'year'=>$year,
                'times_cited'=>$times_cited,
                'impact_factor'=>$impact_factor,
                'author_del'=> $author_del,
                'history'=>$history,              //history字段追 每年的引用次数
                'save'=>1                         //新添加的文章，默认是1（开放），认领后0（封存）
            );

            $status=$this->db->insert('thesis',$info_arr1);           //对新数据进行插入
        }
        //p($info_arr);

        if(!$status)
        {
            $data['failure']=true;
            $data['msg']="第".$currentRow."行操作失败,请更改数据后重新上传";
            $data['jumpUrl']= site_url().'upload_file/index';
            $data['waitSecond']=3;
            $this->load->view('admin/tips.html',$data);
        }
    }

    /**
    * 批量添加新的用户
     */ 
    public function user_add_view(){
        $data['url'] = site_url().'upload_file/upload_excle_user';
        $this->load->view('admin/upload_file.html',$data);
    }

    //上传添加用户的表格
    public function upload_excle_user(){
        set_time_limit(0);
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'xlsx|xls';
        $config['max_size'] = '10000';
        $config['file_name']=time().mt_rand(1000,9999);


        $this->load->library('upload',$config);

        $this->upload->do_upload('excle');
        $wrong=$this->upload->display_errors();
        if($wrong)
        {
          $arr = array('code'=>400, 'msg'=>$wrong);
        }else{
            $info=$this->upload->data();
            //存session 文件名，和后缀名
            $info_arr=array(
                'path'=>$config['upload_path'].$info['file_name'],
                'ext'=>$info['file_ext']
            );
            
            $this->session->set_userdata( $info_arr);
            $this->info_import_user($this->session->userdata('path'),ltrim($this->session->userdata('ext'),'.'));
            $arr = array('code'=>200, 'msg'=>'导入成功');
        }
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    //为user添加PHPExcel
    public function info_import_user($filename,$ext='xls'){
        $this->load->library('PHPExcel');
        // $PHPExcel = new PHPExcel();
        // $filename = "./uploads/user.xls";

        //判断文件是符合标准，是否存在
        $PHPReader = new PHPExcel_Reader_Excel2007();   //建立reader对象
        if(!$PHPReader->canRead($filename)){
            $PHPReader = new PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($filename)){
                die('No Excel!');
            }
        }
        // 加载文件
        $PHPExcel = $PHPReader->load($filename);
        

        $currentSheet = $PHPExcel->getSheet(0);         //只获取第一个sheet
        $allColumn = $currentSheet->getHighestColumn(); //获取列号 返回对象为A- Z的对象
        $allRow = $currentSheet->getHighestRow();       //获取行号
        // p($allColumn);die;
        
        //连接Redis
        $redis = new Redis();
        $redis->connect('127.0.0.1','6379') or die("Redis connecting fail");
        if($redis->exists('job_number_set')){
            $redis->delete('job_number_set');
        }
        //维护一个无序集合为了不重复添加
        $job_number_arr = $this->db->select('job_number')->from('user')->get()->result_array();
        // p($this->db->from('user')->count_all_results());
        foreach ($job_number_arr as $key => $value) {
            if($redis->sismember('job_number_set',$value['job_number'])){
                continue;
            }
            $redis->sadd('job_number_set',$value['job_number']);
        }
        $redis->pexpire('job_number_set','259200000');       //设置job_number_set集合的过期时间为3天
        for($currentRow=2;$currentRow<=$allRow;$currentRow++){
            for($currentColumn='A';$currentColumn<=$allColumn;$currentColumn++){
                $address=$currentColumn.$currentRow; //数据坐标
                $data[$currentColumn] = $currentSheet->getCell($address)->getValue();
            }

            if($redis->sismember('job_number_set',$data['A'])){ // 判断数据是否存在于无序集合中
                continue;
            }else{
                $redis->sadd('job_number_set',$data['A']);      //将新添加的数据放到一个集合中
                $redis->lPush('job_number_link',$data['A']);    //维护一个链表用来数据入库
            }

            //将读取出来的数据放进Redis
            $job_number = $data['A'];
            $data['K']=null;
            $data['L']='a'.$job_number;
            $data['M']= 0;
            for($currentColumn='A';$currentColumn<='M';$currentColumn++){
                $redis->set('user:job_number:'.$job_number.':'.$currentColumn,$data[$currentColumn]);
                $redis->pexpire('user:job_number:'.$job_number.':'.$currentColumn,'259200000');       //设置每个键的过期时间为3天
            }


        }

        $this->add_mysql_user($redis);

        $this->session->set_userdata('import', 'success');
        $redis->close();

    }

    public function add_redis($data,$job_number,$redis){
        $data['L']='a'.$data['A'];
        $data['M']= 0;
        for($currentColumn='A';$currentColumn<='M';$currentColumn++){
            $redis->set('user:job_number:'.$job_number.':'.$currentColumn,$data[$currentColumn]);
            $redis->pexpire('user:job_number:'.$job_number.':'.$currentColumn,'259200000');       //设置每个键的过期时间为3天
        }
    }

    public function add_mysql_user($redis){
        //连接Redis
        // $redis = new Redis();
        // $redis->connect('127.0.0.1','6379') or die("fail");
        $add_user_count = $redis->llen('job_number_link'); 
        $data = null;
        for($i = 0;$i<$add_user_count;$i++){
            $job_number = $redis->rpop('job_number_link');
            
            $data[$i] = array(
                    'job_number'       => $redis->get('user:job_number:'.$job_number.':A'),
                    'name'             => $redis->get('user:job_number:'.$job_number.':B'),
                    'gender'           => $redis->get('user:job_number:'.$job_number.':C'),
                    'academy'          => $redis->get('user:job_number:'.$job_number.':D'),
                    'birthday'         => $redis->get('user:job_number:'.$job_number.':E'),
                    'edu_background'   => $redis->get('user:job_number:'.$job_number.':F'),
                    'degree'           => $redis->get('user:job_number:'.$job_number.':G'),
                    'job_title'        => $redis->get('user:job_number:'.$job_number.':H'),
                    'job_title_rank'   => $redis->get('user:job_number:'.$job_number.':I'),
                    'job_title_series' => $redis->get('user:job_number:'.$job_number.':J'),
                    'full_spell'       => $redis->get('user:job_number:'.$job_number.':K'),
                    'password'         => $redis->get('user:job_number:'.$job_number.':L'),
                    'identity'         => $redis->get('user:job_number:'.$job_number.':M')
            );
            
        }
        if(!empty($data)){
            $status = $this->db->insert_batch('user',$data);
        }else{
            $status = 1;
        }
        

        if(!$status)
        {
            $data['failure']=true;
            $data['msg']="工号为".$job_number."的用户操作失败,请更改数据后重新上传";
            $data['jumpUrl']= site_url().'upload_file/user_add_view';
            $data['waitSecond']=3;
            $this->load->view('admin/tips.html',$data);
        }
    }

    public function show_redis(){
        //连接Redis
        $redis = new Redis();
        $redis->connect('127.0.0.1','6379') or die("fail");
        $add_user_count = $redis->llen('job_number_link'); 
        for($i = 0;$i<$add_user_count;$i++){
            $job_number = $redis->rpop('job_number_link');
            for($currentColumn='A';$currentColumn<='M';$currentColumn++){
                // echo $redis->get('user:job_number:'.$job_number.':'.$currentColumn)." ";
                
            }
         echo "<br/>";
        }
    }


    //判断是否完全导入成功 
    public function import_status(){
        if($this->session->userdata('import') != 'success'){
            $data = array(
                'code'=>400,
                'msg'=>'未执行完成'
            );
        }else{
            $this->session->unset_userdata('import');
            $data = array(
                'code'=>200,
                'msg'=>'执行成功',
            );
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

}