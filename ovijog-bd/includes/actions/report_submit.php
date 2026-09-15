<?php
/**
 * ভুল তথ্য রিপোর্ট জমা
 */
if (!function_exists('report_wrong_action')) {
    function report_wrong_action(): array {
        $csrf=require_csrf(); if($csrf) return $csrf;

        $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $title=trim($_POST['title']??''); $details=trim($_POST['details']??'');
        $jobId=(int)($_POST['job_id']??0);

        if($name===''||$email===''||$title===''||$details===''){
            return ['ok'=>false,'message'=>'সব ফিল্ড পূরণ করুন।'];
        }
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            return ['ok'=>false,'message'=>'সঠিক ইমেইল দিন।'];
        }
        if(mb_strlen($details)<10){
            return ['ok'=>false,'message'=>'বিস্তারিত কমপক্ষে ১০ অক্ষরের হতে হবে।'];
        }

        db_insert('wrong_reports',[
            'job_id'=>$jobId,'name'=>$name,'email'=>$email,'title'=>$title,'details'=>$details,
            'status'=>'pending','ip_address'=>client_ip(),'created_at'=>date('Y-m-d H:i:s')
        ]);

        return ['ok'=>true,'message'=>'আপনার রিপোর্ট জমা হয়েছে। আমরা দ্রুত যাচাই করব।'];
    }
}
