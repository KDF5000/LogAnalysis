<?php
/**
 * Created by PhpStorm.
 * User: Jie
 * Date: 15-12-6
 * Time: 6:31 pm
 */

class LogAnalysis{
    private $log_path = null;

    private $unique_visit = 0; //uv
    private $pages_visit = 0; //pv

    private $num_visit = 0; //number of visit(consider a visit which requests in one hour)
    private $duration_visit = 0;//duration all visits
    private $bounce_visit = 0; //visit on page

    function LogAnalysis($path){
//        $log_path = $argv[1];
        if($path != null && !is_file($path)){
            echo 'Please input a file!!';
            exit;
        }
        $this->log_path = $path;
    }

    /**
     *  calculate UV
     * @return int
     */
    public function calUv(){
        if($this->log_path == null){
            echo 'Please input a right path!!!';
            exit;
        }
        //uv
        $uv_cmd = "awk '{print $1}' ".$this->log_path."| sort | uniq |wc -l";
        $res = array();
        exec($uv_cmd, $res);
        if(!empty($res)){
            $this->unique_visit = $res[0];
            return intval($this->unique_visit);
        }else{
            print "Parse Error!";
            exit;
        }
    }

    /**
     * calculate pv
     * @return int
     */

    public function calPv(){
        if($this->log_path == null){
            echo 'Please input a right path!!!';
            exit;
        }
        //awk 'NF==31{if($8 !~/\.[icon|png|jpeg|jpg|js|css]$/)print $8}' access.log|wc -l
        //pv
        $pv_cmd = "awk 'NF==31{if($8 !~/\\.[icon|png|jpeg|jpg|js|css]$/)print $8}' ".$this->log_path."|wc -l";
        $res = array();
        exec($pv_cmd, $res);
        if(!empty($res)){
            $this->pages_visit = $res[0];
            return intval($this->pages_visit);
        }else{
            print "Parse Error!";
            exit;
        }
    }

    /**
     * sort log by ip
     * @param $log_path
     * @return string
     */
    private function sortLog($log_path){
        $tmp_file = 'tmp.log';
        $sort_cmd = "awk 'NF==31{if($8 !~/\\.[icon|png|jpeg|jpg|js|css]$/)print NR,$1,$4}' ".$log_path." |sort -n -k 2 -k 1 > ".$tmp_file;
        exec($sort_cmd);
        return $tmp_file;
    }

    /**
     * delete temp file
     * @param $file_name
     */
    private function rmFile($file_name){
        if($file_name==null){
            echo 'Please assign a file to delete';
            return;
        }
        @unlink($file_name);
    }
    /**
     * calculate several indicators
     */
    public function process(){
        $tmp_file = $this->sortLog($this->log_path);
        $fp = fopen($tmp_file, 'r');
        $current_ip = null;
        $last_time = null; //the closest time
        $pages_a_visit = 0; # pages in a visit
        while(!feof($fp)){
            $line = fgets($fp);
            $line_content = preg_split('/\s+/', $line);
            if(sizeof($line_content) < 3){
                continue;
            }
            $ip = $line_content[1];
            $time = substr($line_content[2], 1);
            if($current_ip == null){
                $current_ip = $ip;
                $last_time = $time;
                $this->num_visit++;
                $pages_a_visit++;
            }else{
                if($ip == $current_ip){
                    $duration = $this->minusTime($time, $last_time);
//                    print $duration.PHP_EOL;
                    if($duration <= 60){
                        // the pages visited in on hour belong to a visit
                        $this->duration_visit += $duration;
                        $pages_a_visit++;
                    }else{
                        // a new visit
                        if($pages_a_visit==1){
                            $this->bounce_visit++;
                        }
                        $pages_a_visit = 0;
                        $last_time = $time;
                        $this->num_visit++;
                    }
                }else{
                    // a new visit
                    if($pages_a_visit==1){
                        $this->bounce_visit++;
                    }
                    $pages_a_visit = 0;
                    $current_ip = $ip;
                    $last_time = $time;
                    $this->num_visit++;
                }
            }
            // a new visit
            if($pages_a_visit==1){
                $this->bounce_visit++;
            }
        }
        $this->rmFile($tmp_file);
    }

    /**
     * average pages of a visit
     * @return float|int
     */
    public function getAvPagesOfVisit(){
        if($this->num_visit != 0){
            return floatval($this->pages_visit/$this->num_visit);
        }
        return 0;
    }

    /**
     * average duration of a visit
     * @return float|int
     */
    public function getAvDurationOfVisit(){
        if($this->num_visit != 0){
            return floatval($this->duration_visit/($this->num_visit - $this->bounce_visit) * 60);
//            return floatval(($this->duration_visit/$this->num_visit) * 60); //转换为秒
        }
        return 0;
    }

    /**
     * Bounce Rate
     * @return float|int
     */
    public function getBounceRate(){
        if($this->num_visit != 0){
            return floatval($this->bounce_visit/$this->num_visit);
        }
        return 0;
    }

    /**
     * number of visit
     * @return int
     */
    public function getNumberOfVisit(){
        return intval($this->num_visit);
    }


    private function minusTime($time1, $time2){
        $pattern = '/\d{2}\/[a-zA-Z]{3}\/\d{4}:\d{2}:\d{2}:\d{2}/i';
        if(!preg_match($pattern, $time1) && !preg_match($pattern, $time2)){
            return null;
        }
        $time1[11] = ' ';
        $time2[11] = ' ';
        $time1_unix =  strtotime(str_replace('/', ' ', $time1));
        $time2_unix =  strtotime(str_replace('/', ' ', $time2));
        return ($time1_unix - $time2_unix)/60; //转化为分钟
    }

    /**
     * percentage of new visit
     * cat access.log |awk -F ' ' '{a[$1]+=1;}END{for(i in a){print a[i]" " i;}}' |sort -g <unix环境下运行这条命令试试>
     */
    public function newVisitPercent(){
        $prepare_res = array();
        $cmd = "cat {$this->log_path} |awk -F ' ' '{a[$1]+=1;}END{for(i in a){print a[i];}}' |sort -g";
        exec($cmd, $prepare_res);
        $new_visit_num = 0;//the count of new user
        $all_visit_num = 0;//all counts of users
        if(!empty($prepare_res)){
            foreach($prepare_res as $value){
                if($value == 1){
                    $new_visit_num++;
                }
                $all_visit_num += $value;
            }
            $new_visit_percent = bcdiv($new_visit_num, $all_visit_num, 9);
            return floatval($new_visit_percent);
        }
        return 0;
    }

    public function getPath(){
        return $this->log_path;
    }

}

//$test = new LogAnalysis('access.log');
//$test->process();
//print "PV:".$test->calPv().PHP_EOL;
//print "UV:".$test->calUv().PHP_EOL;
//
//# must call the function process before the following functions;
//print 'NumberOfVisit:'.$test->getNumberOfVisit().PHP_EOL;
//print 'AvPagesOfVisit:'.$test->getAvPagesOfVisit().PHP_EOL;
//print 'AvDurationOfVisit:'.$test->getAvDurationOfVisit().PHP_EOL;
//print 'BounceRate:'.$test->getBounceRate().PHP_EOL;
////exec('rm -f temp.log');
