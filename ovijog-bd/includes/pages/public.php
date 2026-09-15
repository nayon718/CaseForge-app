<?php
/**
 * চাকরি সার্কুলার — পাবলিক পেজ রেন্ডারার
 */

if (!function_exists('get_categories')) {
    function get_categories(): array { return db_all('SELECT * FROM categories WHERE status=1 ORDER BY sort_order ASC, id ASC'); }
}
if (!function_exists('get_banners')) {
    function get_banners(): array { return db_all('SELECT * FROM banners WHERE status=1 ORDER BY sort_order ASC, id DESC LIMIT 10'); }
}
if (!function_exists('get_jobs')) {
    function get_jobs(int $limit, int $offset, string $where='status="published"', array $params=[], string $order='created_at DESC, id DESC'): array {
        $sql="SELECT j.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon FROM jobs j LEFT JOIN categories c ON c.id=j.category_id WHERE $where ORDER BY $order LIMIT $limit OFFSET $offset";
        return db_all($sql,$params);
    }
}
if (!function_exists('count_jobs')) {
    function count_jobs(string $where='status="published"', array $params=[]): int {
        return (int)db_value("SELECT COUNT(*) FROM jobs WHERE $where",$params,0);
    }
}

if (!function_exists('page_home')) {
    function page_home(): string {
        $perPage=max(10,(int)setting('items_per_page','20'));
        $current=max(1,(int)($_GET['page']??1));
        $total=count_jobs();
        $pg=paginate($total,$perPage,$current);
        $jobs=get_jobs($perPage,$pg['offset']);

        $banners=get_banners();
        $bannerHtml='';
        if($banners){
            $bannerHtml.='<section class="banner-section"><div class="banner-track" id="bannerTrack">';
            foreach($banners as $b){
                $img=uploads_url($b['image']);
                $link=$b['link']?e($b['link']):'#';
                $bannerHtml.='<div class="banner-card"><img src="'.e($img).'" alt="'.e($b['title']).'" loading="lazy">'.($b['link']?'<a href="'.e($b['link']).'" target="_blank" rel="noopener"></a>':'').'</div>';
            }
            $bannerHtml.='</div><div class="banner-dots" id="bannerDots"></div>';
            if(count($banners)>1){
                $bannerHtml.='<button class="banner-nav banner-prev" id="bannerPrev"><i class="fa-solid fa-chevron-left"></i></button><button class="banner-nav banner-next" id="bannerNext"><i class="fa-solid fa-chevron-right"></i></button>';
            }
            $bannerHtml.='</section>';
        }

        $h1=setting('home_seo_h1','চাকরি সার্কুলার ও আজকের চাকরির খবর');
        $html='<h1 class="section-title" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">'.e($h1).'</h1>';
        $html.=$bannerHtml;

        $html.='<section class="section-block"><div class="section-head"><div><h2 class="section-title"><i class="fa-solid fa-briefcase"></i> সর্বশেষ চাকরি</h2><p class="section-desc">মোট '.bn_number($total).' টি চাকরি • '.bn_number($pg['pages']).' টি পৃষ্ঠা</p></div><a href="'.e(url('jobs')).'" data-link class="btn btn-soft btn-sm">সব দেখুন <i class="fa-solid fa-arrow-right"></i></a></div>';
        if(!$jobs){
            $html.='<div class="empty-state"><i class="fa-regular fa-folder-open"></i><h3>এখনো কোনো চাকরি প্রকাশিত হয়নি</h3><p>অ্যাডমিন প্যানেল থেকে প্রথম চাকরি যোগ করুন।</p></div>';
        } else {
            $html.='<div class="job-list">'.implode('',array_map(function($j){ return job_card_html($j,['name'=>$j['category_name']??'','color'=>$j['category_color']??'#0f766e','icon'=>$j['category_icon']??'fa-solid fa-briefcase']); },$jobs)).'</div>';
            $html.=paginate_links($pg['pages'],$pg['current'],'');
        }
        $html.='</section>';

        // Schema for homepage
        $html.=breadcrumb_schema([['name'=>'হোম','url'=>site_url().'/']]);

        return $html;
    }
}

if (!function_exists('page_category')) {
    function page_category(string $slug): string {
        $cat=db_fetch('SELECT * FROM categories WHERE slug=? LIMIT 1',[$slug]);
        if(!$cat) return page_404();
        $perPage=max(10,(int)setting('items_per_page','20'));
        $current=max(1,(int)($_GET['page']??1));
        $total=count_jobs('status="published" AND category_id=?',[$cat['id']]);
        $pg=paginate($total,$perPage,$current);
        $jobs=get_jobs($perPage,$pg['offset'],'status="published" AND category_id=?',[$cat['id']]);

        $html='<section class="section-block"><div class="section-head"><div><h2 class="section-title"><i class="'.e($cat['icon']).'"></i> '.e($cat['name']).' সমূহ</h2><p class="section-desc">মোট '.bn_number($total).' টি • '.bn_number($pg['pages']).' পৃষ্ঠা</p></div></div>';
        if(!$jobs) $html.='<div class="empty-state"><i class="fa-solid fa-briefcase"></i><h3>এই ক্যাটাগরিতে কোনো চাকরি নেই</h3></div>';
        else {
            $html.='<div class="job-list">'.implode('',array_map(fn($j)=>job_card_html($j,$cat),$jobs)).'</div>';
            $html.=paginate_links($pg['pages'],$pg['current'],'category/'.$cat['slug']);
        }
        $html.='</section>';
        $html.=breadcrumb_schema([['name'=>'হোম','url'=>site_url().'/'],['name'=>$cat['name'],'url'=>site_url().'/category/'.$cat['slug']]]);
        return $html;
    }
}

if (!function_exists('page_jobs')) {
    function page_jobs(): string {
        $perPage=max(10,(int)setting('items_per_page','20'));
        $current=max(1,(int)($_GET['page']??1));
        $total=count_jobs();
        $pg=paginate($total,$perPage,$current);
        $jobs=get_jobs($perPage,$pg['offset']);
        $html='<section class="section-block"><div class="section-head"><div><h2 class="section-title"><i class="fa-solid fa-list"></i> সকল চাকরি</h2><p class="section-desc">মোট '.bn_number($total).' টি</p></div></div>';
        $html.='<div class="job-list">'.implode('',array_map(fn($j)=>job_card_html($j), $jobs)).'</div>';
        $html.=paginate_links($pg['pages'],$pg['current'],'jobs');
        $html.='</section>';
        return $html;
    }
}

if (!function_exists('page_job_detail')) {
    function page_job_detail(string $slug): string {
        $job=db_fetch('SELECT j.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon, c.slug AS category_slug FROM jobs j LEFT JOIN categories c ON c.id=j.category_id WHERE j.slug=? LIMIT 1',[$slug]);
        if(!$job || $job['status']!=='published') return page_404();
        track_job_view((int)$job['id']);

        $cat=['name'=>$job['category_name']??'চাকরি','color'=>$job['category_color']??'#0f766e','icon'=>$job['category_icon']??'fa-solid fa-briefcase','slug'=>$job['category_slug']??'chakri'];
        $imgs=get_job_images($job);
        $deadlineInfo=deadline_countdown($job['deadline']??'');
        $related=get_jobs(6,0,'status="published" AND category_id=? AND id!=?',[$job['category_id'],$job['id']],'views DESC, created_at DESC');

        $extraLinks=[];
        if(!empty($job['extra_links'])){ $decoded=json_decode($job['extra_links'],true); if(is_array($decoded)) $extraLinks=$decoded; }

        $html='<section class="job-detail">';
        $html.='<div class="detail-head"><a href="'.e(url('category/'.$cat['slug'])).'" data-link class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> '.e($cat['name']).'</a>';
        $html.='<h1 class="detail-title">'.e($job['title']).'</h1>';
        $html.='<div class="detail-meta"><span><i class="fa-regular fa-clock"></i> '.e(time_ago($job['created_at'])).' • '.e(format_date_bn($job['created_at'])).'</span><span><i class="fa-solid fa-location-dot"></i> '.e($job['district']?:$job['location_text']?:'বাংলাদেশ').'</span><span class="'.($deadlineInfo['expired']?'expired':'').'"><i class="fa-solid fa-hourglass-half"></i> '.e($deadlineInfo['text']).'</span>'.($job['deadline']?'<span><i class="fa-regular fa-calendar"></i> শেষ: '.e(format_date_bn($job['deadline'])).'</span>':'').'</div></div>';

        // Gallery
        if($imgs){
            $html.='<div class="detail-gallery"><div class="detail-gallery-main" id="detailMainImg"><img src="'.e(uploads_url($imgs[0])).'" alt="'.e($job['title']).'" id="detailMain"></div>';
            if(count($imgs)>1){
                $html.='<div class="detail-thumbs" id="detailThumbs">';
                foreach($imgs as $i=>$im){ $html.='<img src="'.e(uploads_url($im)).'" data-full="'.e(uploads_url($im)).'" class="'.($i===0?'active':'').'" loading="lazy" alt="ছবি '.bn_number($i+1).'">'; }
                $html.='</div>';
            }
            $html.='</div>';
        }

        // PDF
        if(!empty($job['pdf_file'])){
            $html.='<div class="card" style="margin:16px 0;display:flex;align-items:center;justify-content:space-between;gap:12px"><div><i class="fa-solid fa-file-pdf" style="color:var(--danger)"></i> <strong>PDF ডাউনলোড করুন</strong><div style="font-size:12px;color:var(--muted)">আবেদন ফরম ও বিস্তারিত</div></div><a href="'.e(uploads_url($job['pdf_file'])).'" target="_blank" class="btn btn-danger btn-sm"><i class="fa-solid fa-download"></i> ডাউনলোড</a></div>';
        }

        // Description
        $html.='<div class="detail-content">'.$job['description'].'</div>';

        // Extra links
        if($extraLinks){
            $html.='<div class="detail-links">';
            foreach($extraLinks as $lnk){
                $label=$lnk['label']??'লিংক খুলুন'; $urlL=$lnk['url']??'#';
                $html.='<a href="'.e($urlL).'" target="_blank" rel="noopener" class="detail-link-card"><span><i class="fa-solid fa-link"></i> '.e($label).'</span><span class="btn btn-soft btn-sm">Open <i class="fa-solid fa-arrow-up-right-from-square"></i></span></a>';
            }
            $html.='</div>';
        }

        // Apply button
        if(!empty($job['application_link'])){
            $btnText=$job['apply_button_text']?:'আবেদন করুন';
            $html.='<a href="'.e($job['application_link']).'" target="_blank" rel="noopener" class="apply-btn"><i class="fa-solid fa-paper-plane"></i> '.e($btnText).'</a>';
        }

        // Share
        $jobUrl=site_url().'/job/'.$job['slug'];
        $html.='<div class="card" style="margin-top:18px"><div style="display:flex;justify-content:space-between;align-items:center"><strong><i class="fa-solid fa-share-nodes"></i> শেয়ার করুন</strong><button class="share-btn" data-share data-url="'.e($jobUrl).'" data-title="'.e($job['title']).'"><i class="fa-solid fa-share"></i></button></div>'.render_share_popup($jobUrl,$job['title']).'</div>';

        // Related
        if($related){
            $html.='<div class="related-section"><h3 class="section-title"><i class="fa-solid fa-link"></i> সম্পর্কিত পোস্ট সমূহ</h3><div class="related-grid">'.implode('',array_map(fn($r)=>job_card_html($r,$cat),$related)).'</div></div>';
        }

        $html.='</section>';
        $html.=jobposting_schema($job,$cat);
        $html.=breadcrumb_schema([['name'=>'হোম','url'=>site_url().'/'],['name'=>$cat['name'],'url'=>site_url().'/category/'.$cat['slug']],['name'=>$job['title'],'url'=>$jobUrl]]);

        return $html;
    }
}

if (!function_exists('page_search')) {
    function page_search(): string {
        $q=trim($_GET['q']??'');
        $catId=(int)($_GET['cat']??0);
        $district=trim($_GET['district']??'');
        $html='<section class="search-page"><div class="search-hero"><h1 class="section-title" style="justify-content:center;font-size:26px"><i class="fa-solid fa-magnifying-glass"></i> খুজুন</h1><p class="section-desc" style="text-align:center">কিওয়ার্ড, ক্যাটাগরি, জেলা দিয়ে ফিল্টার করুন</p></div>';
        $html.='<div class="search-box"><form method="get" action="'.e(url('search')).'" data-link-form><div class="search-input-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" name="q" value="'.e($q).'" placeholder="চাকরির নাম, প্রতিষ্ঠান, কিওয়ার্ড লিখুন..." id="searchInput"></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px"><select name="cat"><option value="0">সব ক্যাটাগরি</option>';
        foreach(get_categories() as $c){ $html.='<option value="'.$c['id'].'"'.($catId==$c['id']?' selected':'').'>'.e($c['name']).'</option>'; }
        $html.='</select><select name="district"><option value="">সব জেলা</option>';
        try{ foreach(db_all('SELECT name_bn FROM districts WHERE type="district" ORDER BY name_bn ASC') as $d){ $html.='<option value="'.e($d['name_bn']).'"'.($district===$d['name_bn']?' selected':'').'>'.e($d['name_bn']).'</option>'; } }catch(Throwable $e){}
        $html.='</select></div><button class="btn btn-primary" style="width:100%;margin-top:12px"><i class="fa-solid fa-search"></i> খুঁজুন</button></form>';

        // Top search
        try{
            $top=db_all('SELECT keyword, count FROM search_logs ORDER BY count DESC LIMIT 8');
            if($top){ $html.='<div class="top-search"><h3><i class="fa-solid fa-fire"></i> টপ সার্চ</h3><div class="filter-chips">'; foreach($top as $t){ $html.='<a href="'.e(url('search?q='.urlencode($t['keyword']))).'" data-link class="chip">'.e($t['keyword']).' ('.bn_number($t['count']).')</a>'; } $html.='</div></div>'; }
        }catch(Throwable $e){}

        $html.='</div>';

        if($q!==''||$catId||$district!==''){
            if($q!=='') log_search($q);
            $where='status="published"'; $params=[];
            if($q!==''){ $where.=' AND (title LIKE ? OR description LIKE ? OR short_description LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
            if($catId){ $where.=' AND category_id=?'; $params[]=$catId; }
            if($district!==''){ $where.=' AND (district LIKE ? OR location_text LIKE ?)'; $params[]='%'.$district.'%'; $params[]='%'.$district.'%'; }
            $total=count_jobs($where,$params);
            $perPage=max(10,(int)setting('items_per_page','20'));
            $current=max(1,(int)($_GET['page']??1));
            $pg=paginate($total,$perPage,$current);
            $jobs=get_jobs($perPage,$pg['offset'],$where,$params);
            $html.='<div class="section-block" style="margin-top:20px"><div class="section-head"><h3 class="section-title">ফলাফল: '.bn_number($total).' টি</h3></div>';
            if(!$jobs) $html.='<div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><h3>কোনো ফলাফল পাওয়া যায়নি</h3></div>';
            else { $html.='<div class="job-list">'.implode('',array_map(fn($j)=>job_card_html($j),$jobs)).'</div>'; $html.=paginate_links($pg['pages'],$pg['current'],'search?q='.urlencode($q).'&cat='.$catId.'&district='.urlencode($district)); }
            $html.='</div>';
        }

        $html.='</section>';
        return $html;
    }
}

if (!function_exists('page_trending')) {
    function page_trending(): string {
        $perPage=max(10,(int)setting('items_per_page','20'));
        $current=max(1,(int)($_GET['page']??1));
        $total=count_jobs('status="published"');
        $pg=paginate($total,$perPage,$current);
        $jobs=get_jobs($perPage,$pg['offset'],'status="published"',[],'impressions DESC, views DESC, created_at DESC');
        $html='<section class="section-block"><div class="section-head"><div><h2 class="section-title"><i class="fa-solid fa-fire"></i> ট্রেন্ডিং</h2><p class="section-desc">বেশি দেখা চাকরি • ইমপ্রেশন অনুযায়ী</p></div></div>';
        $html.='<div class="job-list">'.implode('',array_map(fn($j)=>job_card_html($j),$jobs)).'</div>';
        $html.=paginate_links($pg['pages'],$pg['current'],'trending');
        $html.='</section>';
        return $html;
    }
}

if (!function_exists('page_notices')) {
    function page_notices(): string {
        $notices=db_all('SELECT * FROM notices ORDER BY created_at DESC, id DESC LIMIT 100');
        $html='<section class="section-block"><div class="section-head"><h2 class="section-title"><i class="fa-solid fa-bell"></i> নোটিশ বোর্ড</h2></div><div class="notice-list">';
        if(!$notices) $html.='<div class="empty-state"><i class="fa-solid fa-bell-slash"></i><h3>কোনো নোটিশ নেই</h3></div>';
        else foreach($notices as $n){
            $html.='<div class="notice-card'.($n['is_important']?' important':'').'"><div class="notice-icon"><i class="fa-solid fa-bell"></i></div><div style="flex:1"><div class="notice-title">'.e($n['title']).'</div><div class="notice-time">'.e(time_ago($n['created_at'])).' • '.e(format_date_bn($n['created_at'])).'</div>'.($n['content']?'<div class="notice-content">'.e($n['content']).'</div>':'').($n['link']?'<a href="'.e($n['link']).'" target="_blank" class="btn btn-soft btn-sm" style="margin-top:8px">বিস্তারিত <i class="fa-solid fa-arrow-up-right-from-square"></i></a>':'').'</div></div>';
        }
        $html.='</div></section>';
        return $html;
    }
}
if (!function_exists('page_notice_detail')) {
    function page_notice_detail(string $slug): string {
        $id=(int)$slug; $n=db_fetch('SELECT * FROM notices WHERE id=? LIMIT 1',[$id]); if(!$n) return page_404();
        $html='<section class="section-block"><div class="notice-card important" style="flex-direction:column"><div class="notice-title" style="font-size:20px">'.e($n['title']).'</div><div class="notice-time">'.e(format_date_bn($n['created_at'])).'</div><div class="notice-content" style="margin-top:12px;font-size:15px">'.nl2br(e($n['content'])).'</div></div><a href="'.e(url('notices')).'" data-link class="btn btn-ghost" style="margin-top:16px"><i class="fa-solid fa-arrow-left"></i> সব নোটিশ</a></section>';
        return $html;
    }
}

if (!function_exists('page_about')) {
    function page_about(): string {
        return '<section class="card" style="max-width:800px;margin:auto"><h1 style="font-size:24px;margin-bottom:12px"><i class="fa-solid fa-circle-info"></i> আমাদের সম্পর্কে</h1><p>'.e(setting('site_footer')).'</p><p style="margin-top:12px">CakriCircular.com বাংলাদেশের একটি বিশ্বস্ত চাকরির পোর্টাল। সরকারি, বেসরকারি, ব্যাংক, শিক্ষক নিয়োগসহ সকল চাকরির খবর প্রতিদিন হালনাগাদ করা হয়।</p></section>';
    }
}
if (!function_exists('page_privacy')) {
    function page_privacy(): string {
        return '<section class="card" style="max-width:800px;margin:auto"><h1><i class="fa-solid fa-shield-halved"></i> গোপনীয়তা ও নিরাপত্তা</h1><p style="margin-top:12px">আমরা ব্যবহারকারীর তথ্য গোপন রাখি। কোনো ব্যক্তিগত তথ্য তৃতীয় পক্ষের সাথে শেয়ার করা হয় না। সাইটে প্রকাশিত তথ্য যাচাই করে প্রকাশ করা হয়, তবে আবেদন করার আগে অফিসিয়াল বিজ্ঞপ্তি দেখে নিন।</p><ul style="margin:16px 0 0 18px"><li>ব্যবহারকারীর ব্রাউজার, ডিভাইস তথ্য শুধুমাত্র অ্যানালিটিকসের জন্য ব্যবহার হয়।</li><li>ভুল তথ্য রিপোর্ট করলে আমরা দ্রুত সংশোধন করি।</li><li>কোনো ফি প্রদানের আগে অফিসিয়াল সোর্স যাচাই করুন।</li></ul></section>';
    }
}
if (!function_exists('page_contact')) {
    function page_contact(): string {
        $email=setting('contact_email'); $addr=setting('contact_address'); $phone=setting('contact_phone');
        return '<section class="card" style="max-width:600px;margin:auto"><h1><i class="fa-solid fa-envelope"></i> যোগাযোগ করুন</h1><div style="margin-top:16px;display:grid;gap:10px"><div><i class="fa-solid fa-at"></i> '.e($email).'</div><div><i class="fa-solid fa-location-dot"></i> '.e($addr).'</div>'.($phone?'<div><i class="fa-solid fa-phone"></i> '.e($phone).'</div>':'').'</div><div class="social-links" style="margin-top:16px">'.(setting('facebook_url')?'<a href="'.e(setting('facebook_url')).'" target="_blank"><i class="fa-brands fa-facebook"></i></a>':'').(setting('telegram_url')?'<a href="'.e(setting('telegram_url')).'" target="_blank"><i class="fa-brands fa-telegram"></i></a>':'').'</div></section>';
    }
}
if (!function_exists('page_report_wrong')) {
    function page_report_wrong(): string {
        return '<section class="card" style="max-width:640px;margin:auto"><h1><i class="fa-solid fa-flag"></i> তথ্যের ভুল রিপোর্ট করুন</h1><p style="color:var(--muted);margin:8px 0 16px">কোনো তথ্য ভুল মনে হলে নিচের ফর্ম পূরণ করুন।</p><form id="wrongReportForm" data-ajax-form data-url="'.e(url('api/report-wrong')).'"><div class="form-group"><label>আপনার নাম</label><input name="name" required></div><div class="form-group"><label>ইমেইল</label><input type="email" name="email" required></div><div class="form-group"><label>শিরোনাম</label><input name="title" required placeholder="কোন চাকরির ভুল"></div><div class="form-group"><label>বিস্তারিত</label><textarea name="details" rows="5" required placeholder="ভুলটি বিস্তারিত লিখুন"></textarea></div>'.csrf_field().'<button class="btn btn-primary btn-lg" style="width:100%"><i class="fa-solid fa-paper-plane"></i> রিপোর্ট পাঠান</button></form></section>';
    }
}
if (!function_exists('page_404')) {
    function page_404(): string {
        return '<section class="notfound-page" style="text-align:center;padding:40px 20px"><div style="font-size:80px;font-weight:800;color:var(--primary);opacity:.15">৪০৪</div><div style="font-size:48px;margin-top:-40px"><i class="fa-solid fa-ghost"></i></div><h1 style="margin-top:12px">পেজটি খুঁজে পাওয়া যায়নি</h1><p style="color:var(--muted)">আপনি যে পেজটি খুঁজছেন সেটি সরানো হয়েছে বা ভুল URL।</p><div style="display:flex;gap:10px;justify-content:center;margin-top:18px;flex-wrap:wrap"><a href="'.e(url('')).'" data-link class="btn btn-primary"><i class="fa-solid fa-house"></i> হোম</a><a href="'.e(url('search')).'" data-link class="btn btn-soft">খুঁজুন</a></div></section>';
    }
}
