<?php
/**
 * চাকরি সার্কুলার — অ্যাডমিন AJAX POST
 */

if (!function_exists('admin_post_action')) {
    function admin_post_action(string $path): ?array {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST') return null;
        require_admin();
        $csrf=require_csrf(); if($csrf) return $csrf;

        // Bulk delete jobs
        if($path==='/admin/jobs' && ($_POST['action']??'')==='bulk_delete'){
            $ids=explode(',',$_POST['ids']??''); $ids=array_filter(array_map('intval',$ids));
            if(!$ids) return ['ok'=>false,'message'=>'কোনো আইডি পাওয়া যায়নি।'];
            $place=implode(',',array_fill(0,count($ids),'?'));
            // delete files
            $jobs=db_all("SELECT * FROM jobs WHERE id IN ($place)",$ids);
            foreach($jobs as $j){
                if(!empty($j['image'])){ $f=ROOT_PATH.'/'.$j['image']; if(is_file($f)) @unlink($f); }
                if(!empty($j['images'])){ $arr=json_decode($j['images'],true); if(is_array($arr)) foreach($arr as $im){ $f=ROOT_PATH.'/'.$im; if(is_file($f)) @unlink($f); } }
                if(!empty($j['pdf_file'])){ $f=ROOT_PATH.'/'.$j['pdf_file']; if(is_file($f)) @unlink($f); }
            }
            db_query("DELETE FROM jobs WHERE id IN ($place)",$ids);
            return ['ok'=>true,'message'=>'মুছে ফেলা হয়েছে।'];
        }

        // Job save
        if(preg_match('#^/admin/job/(new|[0-9]+)$#',$path,$m)){
            $id=$m[1]==='new'?0:(int)$m[1];
            return admin_save_job($id);
        }

        // Categories
        if($path==='/admin/categories' || preg_match('#^/admin/categories/([0-9]+)$#',$path,$m)){
            $id=isset($m[1])?(int)$m[1]:0;
            return admin_save_category($id);
        }

        // Banners
        if($path==='/admin/banners'){
            $action=$_POST['action']??'';
            if($action==='bulk_delete'){
                $ids=explode(',',$_POST['ids']??''); $ids=array_filter(array_map('intval',$ids));
                if($ids){ $place=implode(',',array_fill(0,count($ids),'?')); db_query("DELETE FROM banners WHERE id IN ($place)",$ids); }
                return ['ok'=>true,'message'=>'মুছে ফেলা হয়েছে।'];
            }
            return admin_save_banner();
        }

        // Notices
        if($path==='/admin/notices'){
            $action=$_POST['action']??'';
            if($action==='bulk_delete'){
                $ids=explode(',',$_POST['ids']??''); $ids=array_filter(array_map('intval',$ids));
                if($ids){ $place=implode(',',array_fill(0,count($ids),'?')); db_query("DELETE FROM notices WHERE id IN ($place)",$ids); }
                return ['ok'=>true,'message'=>'মুছে ফেলা হয়েছে।'];
            }
            return admin_save_notice();
        }

        // Settings
        if($path==='/admin/settings') return admin_save_settings();

        // Users
        if($path==='/admin/users' || preg_match('#^/admin/users/([0-9]+)$#',$path,$m)){
            $id=isset($m[1])?(int)$m[1]:0;
            return admin_save_user($id);
        }

        // Gallery
        if($path==='/admin/gallery') return admin_upload_gallery();

        // Password
        if($path==='/admin/change-password') return admin_change_password_action();

        return null;
    }
}

if (!function_exists('admin_save_job')) {
    function admin_save_job(int $id): array {
        $title=trim($_POST['title']??''); $slug=trim($_POST['slug']??''); $catId=(int)($_POST['category_id']??0);
        $short=trim($_POST['short_description']??''); $desc=trim($_POST['description']??'');
        $district=trim($_POST['district']??''); $locText=trim($_POST['location_text']??'');
        $deadline=trim($_POST['deadline']??''); $appLink=trim($_POST['application_link']??'');
        $applyText=trim($_POST['apply_button_text']??'আবেদন করুন'); $status=trim($_POST['status']??'published');
        $seoTitle=trim($_POST['seo_title']??''); $seoDesc=trim($_POST['seo_description']??''); $seoKeys=trim($_POST['seo_keywords']??'');
        $extraLinks=trim($_POST['extra_links']??'[]');

        if($title==='') return ['ok'=>false,'message'=>'শিরোনাম দিন।'];
        if($catId===0) return ['ok'=>false,'message'=>'ক্যাটাগরি সিলেক্ট করুন।'];
        if($desc==='') return ['ok'=>false,'message'=>'বিস্তারিত বিবরণ দিন।'];
        if(!in_array($status,['published','draft','expired','archived'],true)) $status='published';

        // slug
        if($slug==='') $slug=unique_slug($title,'jobs',$id);
        else { $slug=slugify($slug); $slug=unique_slug($slug,'jobs',$id); }

        // Validate extra_links JSON
        $extraJson='[]';
        if($extraLinks!==''){
            $decoded=json_decode($extraLinks,true);
            if(json_last_error()===JSON_ERROR_NONE && is_array($decoded)) $extraJson=json_encode($decoded,JSON_UNESCAPED_UNICODE);
            else $extraJson='[]';
        }

        $data=[
            'title'=>$title,'slug'=>$slug,'category_id'=>$catId,'short_description'=>$short,'description'=>$desc,
            'district'=>$district,'location_text'=>$locText,'deadline'=>$deadline?date('Y-m-d H:i:s',strtotime($deadline)):null,
            'application_link'=>$appLink,'apply_button_text'=>$applyText,'extra_links'=>$extraJson,'status'=>$status,
            'seo_title'=>$seoTitle,'seo_description'=>$seoDesc,'seo_keywords'=>$seoKeys,'updated_at'=>date('Y-m-d H:i:s')
        ];

        // Image upload with compression spec
        $existing=$id?db_fetch('SELECT * FROM jobs WHERE id=?',[$id]):null;
        $mainImage=$existing['image']??'';
        if(!empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])){
            $file=$_FILES['image']; $size=$file['size'];
            $res=save_uploaded_file($file,'jobs',5242880,['jpg','jpeg','png','webp']);
            if(!$res['ok']) return $res;
            // compress
            $src=ROOT_PATH.'/'.$res['path']; $dest=$src;
            compress_image_file($src,$dest,$size);
            $mainImage=$res['path'];
            if($existing && !empty($existing['image'])){ $old=ROOT_PATH.'/'.$existing['image']; if(is_file($old)) @unlink($old); }
        }
        $data['image']=$mainImage;

        // Multiple images
        $allImages=[];
        if($existing && !empty($existing['images'])){
            $arr=json_decode($existing['images'],true); if(is_array($arr)) $allImages=$arr;
        }
        if(!empty($_FILES['images']) && is_array($_FILES['images']['name'])){
            $count=count($_FILES['images']['name']);
            for($i=0;$i<$count;$i++){
                if($i>=5) break;
                if($_FILES['images']['error'][$i]!==UPLOAD_ERR_OK) continue;
                $tmpName=$_FILES['images']['tmp_name'][$i]; $origName=$_FILES['images']['name'][$i]; $size=$_FILES['images']['size'][$i];
                if($size>5242880) continue;
                $ext=strtolower(pathinfo($origName,PATHINFO_EXTENSION));
                if(!in_array($ext,['jpg','jpeg','png','webp'],true)) continue;
                $dir=UPLOAD_PATH.'/jobs'; if(!is_dir($dir)) mkdir($dir,0755,true);
                $newName=random_filename($ext); $dest=$dir.'/'.$newName;
                if(move_uploaded_file($tmpName,$dest)){
                    compress_image_file($dest,$dest,$size);
                    $allImages[]='uploads/jobs/'.$newName;
                }
            }
            // limit to 6 total including main? Keep 5 extra + main separate, but we store all extra in images field (excluding main)
            $allImages=array_slice($allImages,0,10);
        }
        $data['images']=json_encode($allImages,JSON_UNESCAPED_UNICODE);

        // PDF
        $pdfFile=$existing['pdf_file']??'';
        if(!empty($_FILES['pdf_file']) && is_uploaded_file($_FILES['pdf_file']['tmp_name'])){
            $file=$_FILES['pdf_file'];
            if($file['size']>10485760) return ['ok'=>false,'message'=>'PDF সাইজ 10MB এর বেশি।'];
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            if($ext!=='pdf') return ['ok'=>false,'message'=>'শুধু PDF ফাইল।'];
            $dir=UPLOAD_PATH.'/pdfs'; if(!is_dir($dir)) mkdir($dir,0755,true);
            $newName=random_filename('pdf'); $dest=$dir.'/'.$newName;
            if(move_uploaded_file($file['tmp_name'],$dest)){
                $pdfFile='uploads/pdfs/'.$newName;
                if($existing && !empty($existing['pdf_file'])){ $old=ROOT_PATH.'/'.$existing['pdf_file']; if(is_file($old)) @unlink($old); }
            }
        }
        $data['pdf_file']=$pdfFile;

        if($id){
            db_update('jobs',$data,'id=?',[$id]);
            return ['ok'=>true,'message'=>'চাকরি আপডেট হয়েছে।','data'=>['redirect'=>url('admin/jobs')]];
        } else {
            $data['views']=0; $data['impressions']=0; $data['created_at']=date('Y-m-d H:i:s');
            $newId=db_insert('jobs',$data);
            return ['ok'=>true,'message'=>'নতুন চাকরি প্রকাশিত হয়েছে।','data'=>['redirect'=>url('admin/jobs')]];
        }
    }
}

if (!function_exists('admin_save_category')) {
    function admin_save_category(int $id): array {
        $name=trim($_POST['name']??''); $slug=trim($_POST['slug']??''); $icon=trim($_POST['icon']??'fa-solid fa-briefcase');
        $color=trim($_POST['color']??'#0f766e'); $order=(int)($_POST['sort_order']??0); $status=(int)($_POST['status']??1);
        $seoTitle=trim($_POST['seo_title']??''); $seoDesc=trim($_POST['seo_description']??''); $seoKeys=trim($_POST['seo_keywords']??'');
        if($name==='') return ['ok'=>false,'message'=>'নাম দিন।'];
        if($slug==='') $slug=unique_slug($name,'categories',$id); else { $slug=slugify($slug); $slug=unique_slug($slug,'categories',$id); }

        $data=['name'=>$name,'slug'=>$slug,'icon'=>$icon,'color'=>$color,'sort_order'=>$order,'status'=>$status,'seo_title'=>$seoTitle,'seo_description'=>$seoDesc,'seo_keywords'=>$seoKeys,'created_at'=>date('Y-m-d H:i:s')];
        if($id){ unset($data['created_at']); db_update('categories',$data,'id=?',[$id]); return ['ok'=>true,'message'=>'ক্যাটাগরি আপডেট হয়েছে।']; }
        else { $nid=db_insert('categories',$data); return ['ok'=>true,'message'=>'নতুন ক্যাটাগরি যোগ হয়েছে।']; }
    }
}

if (!function_exists('admin_save_banner')) {
    function admin_save_banner(): array {
        $title=trim($_POST['title']??''); $link=trim($_POST['link']??''); $order=(int)($_POST['sort_order']??0);
        if(empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) return ['ok'=>false,'message'=>'ছবি নির্বাচন করুন।'];
        $file=$_FILES['image']; $size=$file['size'];
        if($size>5242880) return ['ok'=>false,'message'=>'ছবি 5MB এর বেশি।'];
        $res=save_uploaded_file($file,'banners',5242880,['jpg','jpeg','png','webp']);
        if(!$res['ok']) return $res;
        compress_image_file(ROOT_PATH.'/'.$res['path'],ROOT_PATH.'/'.$res['path'],$size);
        db_insert('banners',['title'=>$title,'image'=>$res['path'],'link'=>$link,'sort_order'=>$order,'status'=>1,'created_at'=>date('Y-m-d H:i:s')]);
        return ['ok'=>true,'message'=>'ব্যানার যোগ হয়েছে।'];
    }
}

if (!function_exists('admin_save_notice')) {
    function admin_save_notice(): array {
        $title=trim($_POST['title']??''); $content=trim($_POST['content']??''); $link=trim($_POST['link']??''); $imp=(int)($_POST['is_important']??0);
        if($title==='') return ['ok'=>false,'message'=>'শিরোনাম দিন।'];
        db_insert('notices',['title'=>$title,'content'=>$content,'link'=>$link,'is_important'=>$imp,'type'=>'general','created_at'=>date('Y-m-d H:i:s')]);
        return ['ok'=>true,'message'=>'নোটিশ যোগ হয়েছে।'];
    }
}

if (!function_exists('admin_save_settings')) {
    function admin_save_settings(): array {
        $fields=default_site_settings();
        foreach($fields as $key=>$def){
            if(isset($_POST[$key])) save_setting($key,trim((string)$_POST[$key]));
        }
        foreach(['site_logo_file'=>'site_logo','site_favicon_file'=>'site_favicon','site_meta_image_file'=>'site_meta_image','og_default_image_file'=>'og_default_image'] as $fileKey=>$setKey){
            if(!empty($_FILES[$fileKey]) && is_uploaded_file($_FILES[$fileKey]['tmp_name'])){
                $res=save_uploaded_file($_FILES[$fileKey],'settings',2097152,['jpg','jpeg','png','webp','svg']);
                if($res['ok']) save_setting($setKey,$res['path']);
            }
        }
        // Google verification html file
        if(!empty($_FILES['google_verification_file']) && is_uploaded_file($_FILES['google_verification_file']['tmp_name'])){
            $f=$_FILES['google_verification_file'];
            $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
            if($ext==='html'){
                $dest=ROOT_PATH.'/'.$f['name'];
                move_uploaded_file($f['tmp_name'],$dest);
            }
        }
        return ['ok'=>true,'message'=>'সেটিংস সংরক্ষণ হয়েছে।'];
    }
}

if (!function_exists('admin_save_user')) {
    function admin_save_user(int $id): array {
        $username=trim($_POST['username']??''); $name=trim($_POST['name']??''); $role=trim($_POST['role']??'moderator'); $pass=$_POST['password']??''; $perms=trim($_POST['permissions']??'');
        if($username==='') return ['ok'=>false,'message'=>'ইউজারনেম দিন।'];
        if(!in_array($role,['admin','moderator'],true)) $role='moderator';

        if($id){
            $data=['username'=>$username,'name'=>$name,'role'=>$role,'permissions'=>$perms];
            if($pass!==''){ $data['password_hash']=password_hash($pass,PASSWORD_DEFAULT); }
            // check duplicate
            $exists=db_fetch('SELECT id FROM users WHERE username=? AND id!=? LIMIT 1',[$username,$id]);
            if($exists) return ['ok'=>false,'message'=>'এই ইউজারনেম already আছে।'];
            db_update('users',$data,'id=?',[$id]);
            return ['ok'=>true,'message'=>'ইউজার আপডেট হয়েছে।'];
        } else {
            if($pass==='') return ['ok'=>false,'message'=>'পাসওয়ার্ড দিন।'];
            $exists=db_fetch('SELECT id FROM users WHERE username=? LIMIT 1',[$username]);
            if($exists) return ['ok'=>false,'message'=>'এই ইউজারনেম already আছে।'];
            db_insert('users',['username'=>$username,'password_hash'=>password_hash($pass,PASSWORD_DEFAULT),'name'=>$name,'role'=>$role,'permissions'=>$perms,'created_at'=>date('Y-m-d H:i:s')]);
            return ['ok'=>true,'message'=>'নতুন ইউজার যোগ হয়েছে।'];
        }
    }
}

if (!function_exists('admin_upload_gallery')) {
    function admin_upload_gallery(): array {
        if(empty($_FILES['gallery_file'])) return ['ok'=>false,'message'=>'ছবি নির্বাচন করুন।'];
        $res=save_uploaded_file($_FILES['gallery_file'],'gallery',2097152);
        if(!$res['ok']) return $res;
        db_insert('gallery',['title'=>trim($_POST['title']??''),'file'=>$res['path'],'created_at'=>date('Y-m-d H:i:s')]);
        return ['ok'=>true,'message'=>'আপলোড হয়েছে।'];
    }
}

if (!function_exists('admin_change_password_action')) {
    function admin_change_password_action(): array {
        $p=(string)($_POST['password']??''); $c=(string)($_POST['password_confirm']??'');
        if(strlen($p)<8) return ['ok'=>false,'message'=>'পাসওয়ার্ড কমপক্ষে ৮ অক্ষর।'];
        if($p!==$c) return ['ok'=>false,'message'=>'পাসওয়ার্ড মিলছে না।'];
        if(admin_change_password($p)) return ['ok'=>true,'message'=>'পাসওয়ার্ড পরিবর্তন হয়েছে।'];
        return ['ok'=>false,'message'=>'ব্যর্থ।'];
    }
}
