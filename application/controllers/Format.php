<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/13
 * Time: 13:50
 */
class Format extends MY_Controller{

    public  function  replace()
    {
        $this->load->model('format_model','fm');
        $arr=$this->fm->search_all();
        //p($data);die;
        foreach($arr as $k=>$item)
        {
            $search = array(', ');
            $replace = ' ';
            $result=str_replace($search,$replace,$item['author']);
            $data=array(
                'author_del'=>$result
            );
            $this->fm->add($item['accession_number'],$data);
        }
    }


}