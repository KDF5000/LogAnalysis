<?php
/**
 * Created by PhpStorm.
 * User: Jie
 * Date: 15-12-6
 * Time: 6:31 pm
 */
require_once 'LogAnalysis.php';
function addInfo($param){
    if(empty($param)){
        return false;
    }

    $pre_sql = '';
    foreach($param as $key=>$value){
        if(is_float($value) or is_int($value)){
            $pre_sql .= '`'.$key.'` = '. $value . ',';
        }else{
            $pre_sql .= '`'.$key.'` = \''. $value . '\',';
        }
    }
//    echo $pre_sql;
    $pre_sql = rtrim($pre_sql,',');
    $sql = "INSERT INTO log_analysis SET".$pre_sql;
    $conn = mysql_connect('127.0.0.1','root','root123')  or die("Unable to connect to the MySQL!");
    mysql_select_db('log', $conn);
    mysql_query($sql);
    mysql_close($conn);
}

$data = $_POST;
$log_path = $data['log_file'];
$label = $data['label'];
if($log_path == null or !file_exists($log_path)){
    echo json_encode(array("code"=>100, 'data'=>'请指定文件'));
    exit;
}
//$label = ['AllIndicator'];
$analysis = new LogAnalysis($log_path);
$analysis->process();
$PV = $analysis->calPv();
$UV= $analysis->calUv();
// 调用下面几个函数前需要先调用process函数
$NumberOfVisit = $analysis->getNumberOfVisit();
$AvPagesOfVisit = $analysis->getAvPagesOfVisit();
$AvDurationOfVisit = $analysis->getAvDurationOfVisit();
$BounceRate = $analysis->getBounceRate();
$PercentNewVisit = $analysis->newVisitPercent();
$res = array(
    "PV"=>$PV,
    'UV'=>$UV,
    'NumberOfVisit'=>$NumberOfVisit,
    'AvPagesOfVisit'=>$AvPagesOfVisit,
    'AvDurationOfVisit'=>$AvDurationOfVisit,
    'BounceRate' => $BounceRate,
    'PercentNewVisit' => $PercentNewVisit,
);
// insert into db
$data = array(
    'file_name' => '',
    'page_view' => $PV,
    'unique_visitor' => $UV,
    'num_of_visitor' => $NumberOfVisit,
    'pages_per_visit' => $AvPagesOfVisit,
    'av_visit_duration' => $AvDurationOfVisit,
    'bounce_rate' => $BounceRate,
    'per_of_new_visit' => $PercentNewVisit,
    'add_time' => date("Y-m-d H:i:s")
);
addInfo($data);
if(in_array('AllIndicator', $label)){
    echo json_encode(array("code"=>200, 'data'=>$res));
}else{
    $temp = array();
    foreach($label as $val){
        $temp[$val] = $res[$val];
    }
    echo json_encode(array("code"=>200, 'data'=>$temp));
}

