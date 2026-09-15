<?php
/**
 * চাকরি সার্কুলার — হেল্পার ফাংশন (utf8mb4, SEO, Security, Compression)
 */

if (!function_exists('e')) {
    function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}

if (!function_exists('setting')) {
    function setting(string $key, $default=''): string {
        static $cache=null;
        if ($cache===null) {
            $cache=[];
            try { foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) $cache[$row['setting_key']]=$row['setting_value']; } catch(Throwable $e){ $cache=[]; }
        }
        return isset($cache[$key]) && $cache[$key]!=='' ? $cache[$key] : (string)$default;
    }
}
if (!function_exists('save_setting')) {
    function save_setting(string $key, $value): void {
        if (db_count('settings','setting_key=?',[$key])>0) db_update('settings',['setting_value'=>(string)$value,'updated_at'=>date('Y-m-d H:i:s')],'setting_key=?',[$key]);
        else db_insert('settings',['setting_key'=>$key,'setting_value'=>(string)$value,'updated_at'=>date('Y-m-d H:i:s')]);
    }
}
if (!function_exists('csrf_token')) { function csrf_token(): string { if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; } }
if (!function_exists('csrf_field')) { function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; } }
if (!function_exists('verify_csrf')) { function verify_csrf(): bool { $token=$_POST['csrf_token']??$_SERVER['HTTP_X_CSRF_TOKEN']??''; return hash_equals(csrf_token(),(string)$token); } }
if (!function_exists('require_csrf')) { function require_csrf(): ?array { if(!verify_csrf()){ http_response_code(419); return ['ok'=>false,'message'=>'সেশন মেয়াদ শেষ। পেজ রিফ্রেশ করুন।']; } return null; } }

if (!function_exists('asset_url')) { function asset_url(string $path): string { return rtrim(BASE_URL,'/').'/assets/'.ltrim($path,'/'); } }
if (!function_exists('uploads_url')) { function uploads_url(string $path): string { return rtrim(BASE_URL,'/').'/'.ltrim($path,'/'); } }
if (!function_exists('url')) { function url(string $path='/'): string { $base=rtrim(BASE_URL,'/'); return $base.'/'.ltrim($path,'/'); } }
if (!function_exists('site_url')) { function site_url(): string { $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http'; $host=$_SERVER['HTTP_HOST']??'localhost'; return $scheme.'://'.$host.rtrim(BASE_URL,'/'); } }
if (!function_exists('current_path')) {
    function current_path(): string {
        $uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)??'/';
        $base=parse_url(BASE_URL?:'',PHP_URL_PATH)?:'';
        if($base && strpos($uri,$base)===0) $uri=substr($uri,strlen($base));
        return '/'.ltrim($uri,'/');
    }
}
if (!function_exists('is_ajax')) { function is_ajax(): bool { $x=$_SERVER['HTTP_X_REQUESTED_WITH']??''; return !empty($_GET['_ajax'])||strtolower($x)==='xmlhttprequest'||strtolower($x)==='ajax'; } }

if (!function_exists('slugify')) {
    function slugify(string $text): string {
        $text=trim(mb_strtolower($text));
        $text=preg_replace('/[^a-z0-9\p{Bengali}\s-]/u','',$text);
        $text=preg_replace('/[\s-]+/','-',$text);
        $text=trim($text,'-');
        if(mb_strlen($text)<3) $text.='-'.bin2hex(random_bytes(2));
        return $text;
    }
}
if (!function_exists('unique_slug')) {
    function unique_slug(string $base, string $table='jobs', int $ignoreId=0): string {
        $slug=slugify($base);
        $orig=$slug; $i=1;
        while(true){
            $row=db_fetch("SELECT id FROM $table WHERE slug=? AND id!=? LIMIT 1",[$slug,$ignoreId]);
            if(!$row) break;
            $slug=$orig.'-'.$i; $i++;
            if($i>100) { $slug=$orig.'-'.time(); break; }
        }
        return $slug;
    }
}

if (!function_exists('client_info')) {
    function client_info(): array {
        $ua=$_SERVER['HTTP_USER_AGENT']??''; $ip=client_ip();
        $browser='Unknown'; $os='Unknown'; $device='Desktop';
        foreach(['Edg/'=>'Edge','OPR/'=>'Opera','Chrome/'=>'Chrome','Firefox'=>'Firefox','Safari'=>'Safari'] as $k=>$v) if(stripos($ua,$k)!==false){ $browser=$v; break; }
        foreach(['Windows NT'=>'Windows','Android'=>'Android','iPhone'=>'iOS','iPad'=>'iOS','Mac OS X'=>'macOS','Linux'=>'Linux'] as $k=>$v) if(stripos($ua,$k)!==false){ $os=$v; break; }
        if(preg_match('/Mobile|Android|iPhone|iPad/',$ua)) $device='Mobile';
        return ['ip'=>$ip,'user_agent'=>$ua,'browser'=>$browser,'os'=>$os,'device'=>$device];
    }
}
if (!function_exists('track_visit')) {
    function track_visit(string $page): void {
        try{
            $info=client_info();
            if(empty($_COOKIE['cakri_visitor'])){ $vid=bin2hex(random_bytes(8)); setcookie('cakri_visitor',$vid,['expires'=>time()+86400*365,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); $_COOKIE['cakri_visitor']=$vid; }
            $vid=$_COOKIE['cakri_visitor']??'anon';
            $date=date('Y-m-d');
            $exists=db_fetch('SELECT id FROM analytics WHERE visitor_id=? AND page=? AND visit_date=? LIMIT 1',[$vid,$page,$date]);
            if(!$exists){
                // simple location detection from district list or IP (mock)
                $location=''; $division=''; $country='Bangladesh';
                // Try to guess division from page? Keep simple
                db_insert('analytics',[
                    'visitor_id'=>$vid,'ip_address'=>$info['ip'],'user_agent'=>$info['user_agent'],'browser'=>$info['browser'],'os'=>$info['os'],'device'=>$info['device'],
                    'location'=>$location,'division'=>$division,'country'=>$country,'page'=>$page,'page_title'=>setting('site_name','চাকরি সার্কুলার'),'visit_date'=>$date,'created_at'=>date('Y-m-d H:i:s')
                ]);
            }
        }catch(Throwable $e){}
    }
}
if (!function_exists('track_job_view')) {
    function track_job_view(int $jobId): void {
        try{
            if(empty($_COOKIE['cakri_visitor'])){ $vid=bin2hex(random_bytes(8)); setcookie('cakri_visitor',$vid,['expires'=>time()+86400*365,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); $_COOKIE['cakri_visitor']=$vid; }
            $vid=$_COOKIE['cakri_visitor']??'anon';
            $ip=client_ip();
            $today=date('Y-m-d');
            $exists=db_fetch('SELECT id FROM job_views WHERE job_id=? AND visitor_id=? AND DATE(created_at)=? LIMIT 1',[$jobId,$vid,$today]);
            if(!$exists){
                db_insert('job_views',['job_id'=>$jobId,'visitor_id'=>$vid,'ip_address'=>$ip,'created_at'=>date('Y-m-d H:i:s')]);
                db_query('UPDATE jobs SET views=views+1, impressions=impressions+1 WHERE id=?',[$jobId]);
            } else {
                db_query('UPDATE jobs SET impressions=impressions+1 WHERE id=?',[$jobId]);
            }
        }catch(Throwable $e){}
    }
}
if (!function_exists('log_search')) {
    function log_search(string $keyword): void {
        $keyword=trim(mb_strtolower($keyword)); if($keyword==='') return;
        try{
            $row=db_fetch('SELECT id, count FROM search_logs WHERE keyword=? LIMIT 1',[$keyword]);
            if($row) db_update('search_logs',['count'=>$row['count']+1,'last_searched_at'=>date('Y-m-d H:i:s')],'id=?',[$row['id']]);
            else db_insert('search_logs',['keyword'=>$keyword,'count'=>1,'last_searched_at'=>date('Y-m-d H:i:s')]);
        }catch(Throwable $e){}
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string {
        $time=strtotime($datetime); if(!$time) return $datetime; $diff=time()-$time;
        if($diff<60) return 'এখনই';
        if($diff<3600) return floor($diff/60).' মিনিট আগে';
        if($diff<86400) return floor($diff/3600).' ঘণ্টা আগে';
        if($diff<604800) return floor($diff/86400).' দিন আগে';
        if($diff<2592000) return floor($diff/604800).' সপ্তাহ আগে';
        return date('d M Y', $time);
    }
}
if (!function_exists('deadline_countdown')) {
    function deadline_countdown(?string $deadline): array {
        if(!$deadline) return ['text'=>'সময় উল্লেখ নেই','expired'=>false,'days'=>null];
        $ts=strtotime($deadline); if(!$ts) return ['text'=>'সময় উল্লেখ নেই','expired'=>false,'days'=>null];
        $diff=$ts-time();
        if($diff<=0) return ['text'=>'সময় শেষ','expired'=>true,'days'=>0];
        $days=floor($diff/86400);
        $hours=floor(($diff%86400)/3600);
        if($days>0) $text=$days.' দিন বাকি';
        elseif($hours>0) $text=$hours.' ঘণ্টা বাকি';
        else $text='আজ শেষ';
        return ['text'=>$text,'expired'=>false,'days'=>$days,'hours'=>$hours];
    }
}
if (!function_exists('bn_number')) { function bn_number($num): string { $map=['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']; return strtr((string)$num,$map); } }
if (!function_exists('format_date_bn')) { function format_date_bn(string $datetime): string { $ts=strtotime($datetime); if(!$ts) return $datetime; return date('d M Y, h:i A',$ts); } }

if (!function_exists('paginate')) {
    function paginate(int $total, int $perPage, int $current): array {
        $pages=max(1,(int)ceil($total/$perPage)); $current=max(1,min($current,$pages)); $offset=($current-1)*$perPage;
        return ['pages'=>$pages,'current'=>$current,'offset'=>$offset,'total'=>$total,'perPage'=>$perPage];
    }
}
if (!function_exists('paginate_links')) {
    function paginate_links(int $pages, int $current, string $basePath=''): string {
        if($pages<=1) return '';
        $html='<nav class="pagination" aria-label="Pagination">';
        $start=max(1,$current-2); $end=min($pages,$start+4); $start=max(1,$end-4);
        if($current>1) $html.='<a href="'.e(url($basePath.'?page='.($current-1))).'" data-link aria-label="আগের"><i class="fa-solid fa-chevron-left"></i></a>';
        for($i=$start;$i<=$end;$i++){ $cls=$i===$current?' class="active"':''; $html.='<a'.$cls.' href="'.e(url($basePath.'?page='.$i)).'" data-link>'.bn_number($i).'</a>'; }
        if($end<$pages){
            if($end<$pages-1) $html.='<span class="pg-dots">…</span>';
            $html.='<a href="'.e(url($basePath.'?page='.$pages)).'" data-link>'.bn_number($pages).'</a>';
        }
        if($current<$pages) $html.='<a href="'.e(url($basePath.'?page='.($current+1))).'" data-link aria-label="পরের"><i class="fa-solid fa-chevron-right"></i></a>';
        $html.='</nav>';
        return $html;
    }
}

if (!function_exists('is_image_file')) { function is_image_file(string $name): bool { $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION)); return in_array($ext,['jpg','jpeg','png','gif','webp','bmp','svg'],true); } }
if (!function_exists('random_filename')) { function random_filename(string $ext): string { return date('YmdHis').'_'.bin2hex(random_bytes(8)).'.'.strtolower($ext); } }

if (!function_exists('save_uploaded_file')) {
    function save_uploaded_file(array $file, string $dir, int $maxSize, array $allowed=['jpg','jpeg','png','gif','webp','svg']): array {
        if(empty($file['error'])&&$file['error']!==UPLOAD_ERR_OK) return ['ok'=>false,'message'=>'ফাইল আপলোড ব্যর্থ।'];
        if(!is_uploaded_file($file['tmp_name'])) return ['ok'=>false,'message'=>'অবৈধ আপলোড।'];
        if((int)$file['size']>$maxSize) return ['ok'=>false,'message'=>'ফাইল সাইজ বেশি। সর্বোচ্চ '.round($maxSize/1048576,2).'MB।'];
        $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,$allowed,true)) return ['ok'=>false,'message'=>'এই ফরম্যাট অনুমোদিত নয়।'];
        $fullDir=UPLOAD_PATH.'/'.$dir; if(!is_dir($fullDir)) mkdir($fullDir,0755,true);
        $name=random_filename($ext); $dest=$fullDir.'/'.$name;
        if(!move_uploaded_file($file['tmp_name'],$dest)) return ['ok'=>false,'message'=>'ফাইল সংরক্ষণ করা যায়নি।'];
        return ['ok'=>true,'path'=>'uploads/'.$dir.'/'.$name,'file'=>$name];
    }
}
if (!function_exists('save_base64_image')) {
    function save_base64_image(string $data, string $dir, int $maxSize=5242880): array {
        $data=trim($data);
        if(preg_match('/^data:image\/([a-zA-Z0-9+.-]+);base64,(.+)$/',$data,$m)){
            $ext=strtolower($m[1]); $map=['jpeg'=>'jpg','pjpeg'=>'jpg','x-png'=>'png','png'=>'png','gif'=>'gif','webp'=>'webp','bmp'=>'bmp']; $ext=$map[$ext]??'jpg';
            if(!in_array($ext,['jpg','png','gif','webp','bmp'],true)) return ['ok'=>false,'message'=>'ছবির ফরম্যাট সাপোর্টেড নয়।'];
            $binary=base64_decode($m[2],true); if($binary===false||strlen($binary)>$maxSize) return ['ok'=>false,'message'=>'ছবি বড় হয়ে গেছে।'];
            $fullDir=UPLOAD_PATH.'/'.$dir; if(!is_dir($fullDir)) mkdir($fullDir,0755,true);
            $name=random_filename($ext); file_put_contents($fullDir.'/'.$name,$binary);
            return ['ok'=>true,'path'=>'uploads/'.$dir.'/'.$name,'file'=>$name];
        }
        return ['ok'=>false,'message'=>'ছবির ডেটা সঠিক নয়।'];
    }
}
if (!function_exists('compress_image_file')) {
    // Server-side compression following spec: 5mb=450kb, 4mb=400kb, etc (~90kb per MB)
    function compress_image_file(string $srcPath, string $destPath, int $originalSize): bool {
        if(!extension_loaded('gd')) { copy($srcPath,$destPath); return true; }
        $info=getimagesize($srcPath); if(!$info) { copy($srcPath,$destPath); return true; }
        $mime=$info['mime'];
        $img=null;
        if($mime==='image/jpeg') $img=imagecreatefromjpeg($srcPath);
        elseif($mime==='image/png') $img=imagecreatefrompng($srcPath);
        elseif($mime==='image/gif') $img=imagecreatefromgif($srcPath);
        elseif($mime==='image/webp') $img=imagecreatefromwebp($srcPath);
        else { copy($srcPath,$destPath); return true; }
        if(!$img) { copy($srcPath,$destPath); return true; }
        $w=imagesx($img); $h=imagesy($img);
        $maxDim=1600;
        if(max($w,$h)>$maxDim){
            $scale=$maxDim/max($w,$h); $nw=(int)($w*$scale); $nh=(int)($h*$scale);
            $tmp=imagecreatetruecolor($nw,$nh);
            if($mime==='image/png'){ imagealphablending($tmp,false); imagesavealpha($tmp,true); $transparent=imagecolorallocatealpha($tmp,255,255,255,127); imagefilledrectangle($tmp,0,0,$nw,$nh,$transparent); }
            imagecopyresampled($tmp,$img,0,0,0,0,$nw,$nh,$w,$h);
            imagedestroy($img); $img=$tmp;
        }
        // Target quality based on spec
        $mb=$originalSize/1048576;
        if($mb>=5) $quality=62;
        elseif($mb>=4) $quality=65;
        elseif($mb>=3) $quality=68;
        elseif($mb>=2) $quality=72;
        elseif($mb>=1) $quality=78;
        else $quality=82;
        $ok=false;
        if($mime==='image/png'){
            // convert to jpg for size saving unless transparent needed? Keep png but compress
            imagejpeg($img,$destPath,$quality);
            $ok=true;
        } else {
            imagejpeg($img,$destPath,$quality);
            $ok=true;
        }
        imagedestroy($img);
        return $ok;
    }
}
if (!function_exists('get_job_images')) {
    function get_job_images(array $job): array {
        $imgs=[];
        if(!empty($job['image'])) $imgs[]=$job['image'];
        if(!empty($job['images'])){
            $decoded=json_decode($job['images'],true);
            if(is_array($decoded)) foreach($decoded as $f) if($f && !in_array($f,$imgs)) $imgs[]=$f;
        }
        return $imgs;
    }
}
if (!function_exists('job_card_html')) {
    function job_card_html(array $job, array $cat=null): string {
        $cat=$cat??['name'=>$job['category_name']??'','color'=>$job['category_color']??'#0f766e','icon'=>$job['category_icon']??'fa-solid fa-briefcase'];
        $imgs=get_job_images($job);
        $thumb=$imgs[0]??'';
        $thumbUrl=$thumb ? uploads_url($thumb) : (setting('site_logo')?uploads_url(setting('site_logo')):asset_url('img/logo.png'));
        $title=e($job['title']);
        $short=mb_substr(strip_tags($job['short_description']?:$job['description']),0,120);
        $timeAgo=time_ago($job['created_at']);
        $deadlineInfo=deadline_countdown($job['deadline']??'');
        $district=e($job['district']?:$job['location_text']?:'বাংলাদেশ');
        $jobUrl=url('job/'.$job['slug']);
        $shareTitle='Share This '.e($cat['name']?:'চাকরি');
        $isExpired=$deadlineInfo['expired'];
        $deadlineText=$deadlineInfo['text'];
        $deadlineClass=$isExpired?'expired':'';
        return '<article class="job-card" data-job="'.$job['id'].'">
            <div class="job-thumb"><a href="'.e($jobUrl).'" data-link><img src="'.e($thumbUrl).'" alt="'.$title.'" loading="lazy" onerror="this.src=\''.e(asset_url('img/logo.png')).'\'"></a></div>
            <div class="job-main">
                <div class="job-top"><span class="job-cat" style="--cat-color:'.e($cat['color']).'"><i class="'.e($cat['icon']).'"></i> '.e($cat['name']).'</span><span class="job-time"><i class="fa-regular fa-clock"></i> '.$timeAgo.'</span></div>
                <h2 class="job-title"><a href="'.e($jobUrl).'" data-link>'.$title.'</a></h2>
                <div class="job-meta"><span class="meta-loc"><i class="fa-solid fa-location-dot"></i> '.$district.'</span><span class="meta-deadline '.$deadlineClass.'"><i class="fa-solid fa-hourglass-half"></i> '.$deadlineText.'</span>'.($job['deadline']?'<span class="meta-date"><i class="fa-regular fa-calendar"></i> '.e(format_date_bn($job['deadline'])).'</span>':'').'</div>
                <div class="job-foot"><a href="'.e($jobUrl).'" data-link class="job-read">বিস্তারিত <i class="fa-solid fa-arrow-right"></i></a><button class="share-btn" data-share data-url="'.e($jobUrl).'" data-title="'.e($job['title']).'"><i class="fa-solid fa-share-nodes"></i></button></div>
            </div>
        </article>';
    }
}
if (!function_exists('render_share_popup')) {
    function render_share_popup(string $link, string $title): string {
        $encLink=rawurlencode($link); $encTitle=rawurlencode($title);
        return '<div class="share-popup" hidden>
            <div class="share-head"><strong><i class="fa-solid fa-share-nodes"></i> Share This</strong><button class="share-close" data-share-close><i class="fa-solid fa-xmark"></i></button></div>
            <div class="share-title">'.e($title).'</div>
            <div class="share-linkbox"><input value="'.e($link).'" readonly><button data-copy="'.e($link).'"><i class="fa-solid fa-copy"></i> কপি</button></div>
            <div class="share-grid">
                <a href="https://www.facebook.com/sharer/sharer.php?u='.$encLink.'" target="_blank" rel="noopener" class="sh-fb"><i class="fa-brands fa-facebook"></i> Facebook</a>
                <a href="https://twitter.com/intent/tweet?url='.$encLink.'&text='.$encTitle.'" target="_blank" rel="noopener" class="sh-tw"><i class="fa-brands fa-x-twitter"></i> Twitter</a>
                <a href="https://wa.me/?text='.$encTitle.'%20'.$encLink.'" target="_blank" rel="noopener" class="sh-wa"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                <a href="https://t.me/share/url?url='.$encLink.'&text='.$encTitle.'" target="_blank" rel="noopener" class="sh-tg"><i class="fa-brands fa-telegram"></i> Telegram</a>
            </div>
        </div>';
    }
}
if (!function_exists('seo_meta')) {
    function seo_meta(array $args): string {
        $title=$args['title']??setting('home_seo_title','চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি');
        $description=$args['description']??setting('home_meta_description','চাকরি সার্কুলার - বাংলাদেশের সবচেয়ে বিশ্বস্ত চাকরির পোর্টাল।');
        $keywords=$args['keywords']??setting('home_meta_keywords','চাকরি সার্কুলার, আজকের চাকরির খবর');
        $image=$args['image']??setting('og_default_image')?:setting('site_meta_image');
        $url=$args['url']??site_url().current_path();
        $type=$args['type']??'website';
        $canonical=$args['canonical']??$url;
        $html=[];
        $html[]='<title>'.e($title).'</title>';
        $html[]='<meta name="description" content="'.e($description).'">';
        $html[]='<meta name="keywords" content="'.e($keywords).'">';
        $html[]='<link rel="canonical" href="'.e($canonical).'">';
        $html[]='<meta property="og:site_name" content="'.e(setting('site_name','চাকরি সার্কুলার')).'">';
        $html[]='<meta property="og:type" content="'.e($type).'">';
        $html[]='<meta property="og:title" content="'.e($title).'">';
        $html[]='<meta property="og:description" content="'.e($description).'">';
        $html[]='<meta property="og:url" content="'.e($url).'">';
        $html[]='<meta property="og:image" content="'.e($image?uploads_url($image):asset_url('img/logo.png')).'">';
        $html[]='<meta name="twitter:card" content="summary_large_image">';
        $html[]='<meta name="twitter:title" content="'.e($title).'">';
        $html[]='<meta name="twitter:description" content="'.e($description).'">';
        $html[]='<meta name="twitter:image" content="'.e($image?uploads_url($image):asset_url('img/logo.png')).'">';
        if(setting('google_verification')) $html[]='<meta name="google-site-verification" content="'.e(setting('google_verification')).'">';
        return implode("\n",$html);
    }
}
if (!function_exists('organization_schema')) {
    function organization_schema(): string {
        $logo=setting('site_logo')?uploads_url(setting('site_logo')):asset_url('img/logo.png');
        $data=[
            "@context"=>"https://schema.org",
            "@type"=>"Organization",
            "name"=>"চাকরি সার্কুলার",
            "alternateName"=>["CakriCircular","CakriCircular.com","চাকরির সার্কুলার","চাকরির খবর","আজকের চাকরি"],
            "url"=>site_url(),
            "logo"=>$logo,
            "sameAs"=>array_values(array_filter([setting('facebook_url'),setting('twitter_url'),setting('telegram_url'),setting('youtube_url')]))
        ];
        return '<script type="application/ld+json">'.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
    }
}
if (!function_exists('website_schema')) {
    function website_schema(): string {
        $data=[
            "@context"=>"https://schema.org",
            "@type"=>"WebSite",
            "name"=>setting('site_name','চাকরি সার্কুলার'),
            "alternateName"=>["CakriCircular","CakriCircular.com"],
            "url"=>site_url(),
            "potentialAction"=>[
                "@type"=>"SearchAction",
                "target"=>site_url()."/search?q={search_term_string}",
                "query-input"=>"required name=search_term_string"
            ]
        ];
        return '<script type="application/ld+json">'.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
    }
}
if (!function_exists('breadcrumb_schema')) {
    function breadcrumb_schema(array $items): string {
        $list=[];
        $pos=1;
        foreach($items as $it){
            $list[]=["@type"=>"ListItem","position"=>$pos,"name"=>$it['name'],"item"=>$it['url']??site_url()];
            $pos++;
        }
        $data=["@context"=>"https://schema.org","@type"=>"BreadcrumbList","itemListElement"=>$list];
        return '<script type="application/ld+json">'.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
    }
}
if (!function_exists('jobposting_schema')) {
    function jobposting_schema(array $job, array $category=null): string {
        $catName=$category['name']??'চাকরি';
        $data=[
            "@context"=>"https://schema.org",
            "@type"=>"JobPosting",
            "title"=>$job['title'],
            "description"=>strip_tags($job['description']),
            "datePosted"=>date('c',strtotime($job['created_at'])),
            "validThrough"=>$job['deadline']?date('c',strtotime($job['deadline'])):null,
            "employmentType"=>"FULL_TIME",
            "hiringOrganization"=>[
                "@type"=>"Organization",
                "name"=>"চাকরি সার্কুলার",
                "sameAs"=>site_url(),
                "logo"=>setting('site_logo')?uploads_url(setting('site_logo')):asset_url('img/logo.png')
            ],
            "jobLocation"=>[
                "@type"=>"Place",
                "address"=>[
                    "@type"=>"PostalAddress",
                    "addressLocality"=>$job['district']?:'Bangladesh',
                    "addressCountry"=>"BD"
                ]
            ],
            "baseSalary"=>null
        ];
        // remove null
        $data=array_filter($data,function($v){return $v!==null;});
        return '<script type="application/ld+json">'.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
    }
}
if (!function_exists('get_categories')) {
    function get_categories(): array {
        try { return db_all('SELECT * FROM categories WHERE status=1 ORDER BY sort_order ASC, id ASC'); } catch(Throwable $e){ return []; }
    }
}
if (!function_exists('get_all_categories')) {
    function get_all_categories(): array {
        try { return db_all('SELECT * FROM categories ORDER BY sort_order ASC, id ASC'); } catch(Throwable $e){ return []; }
    }
}
if (!function_exists('default_site_settings')) {
    function default_site_settings(): array {
        return [
            'site_name'=>'চাকরি সার্কুলার',
            'site_subtitle'=>'CakriCircular.com',
            'site_slogan'=>'বাংলাদেশের সরকারি-বেসরকারি চাকরির সার্কুলার, ভর্তি বিজ্ঞপ্তি ও পরীক্ষার ফলাফল — প্রতিদিন হালনাগাদ, এক জায়গায়।',
            'site_description'=>'চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি, ব্যাংক চাকরি, শিক্ষক নিয়োগ, ভর্তি বিজ্ঞপ্তি ও ফলাফল প্রতিদিন হালনাগাদ।',
            'site_keywords'=>'চাকরি সার্কুলার, আজকের চাকরির খবর, সরকারি চাকরি, বেসরকারি চাকরি, ব্যাংক চাকরি, শিক্ষক নিয়োগ, নিয়োগ বিজ্ঞপ্তি, CakriCircular, চাকরির খবর',
            'site_footer'=>'বাংলাদেশের সরকারি-বেসরকারি চাকরির সার্কুলার, ভর্তি বিজ্ঞপ্তি ও পরীক্ষার ফলাফল — প্রতিদিন হালনাগাদ, এক জায়গায়।',
            'site_copyright'=>'© ২০২৬ চাকরি সার্কুলার। সর্বস্বত্ব সংরক্ষিত।',
            'site_logo'=>'','site_favicon'=>'','site_meta_image'=>'','og_default_image'=>'',
            'items_per_page'=>'20','google_verification'=>'','facebook_url'=>'','twitter_url'=>'','telegram_url'=>'','youtube_url'=>'',
            'contact_email'=>'cakricircular.support@gmail.com','contact_phone'=>'','contact_address'=>'Bangladesh',
            'maintenance_mode'=>'0','maintenance_message'=>'সাইটটি রক্ষণাবেক্ষণের জন্য সাময়িক বন্ধ আছে।',
            'home_seo_title'=>'চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি',
            'home_seo_h1'=>'চাকরি সার্কুলার ও আজকের চাকরির খবর',
            'home_meta_description'=>'চাকরি সার্কুলার - বাংলাদেশের সবচেয়ে বিশ্বস্ত চাকরির পোর্টাল। সরকারি চাকরি, বেসরকারি চাকরি, ব্যাংক চাকরি, শিক্ষক নিয়োগ, ভর্তি বিজ্ঞপ্তি ও পরীক্ষার ফলাফল প্রতিদিন হালনাগাদ।',
            'home_meta_keywords'=>'চাকরি সার্কুলার, আজকের চাকরির খবর, সরকারি চাকরি, বেসরকারি চাকরি, CakriCircular, চাকরির সার্কুলার',
        ];
    }
}
