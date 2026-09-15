<?php
/**
 * চাকরি সার্কুলার — রাউটার (Clean URL, SEO Friendly)
 */

if (!function_exists('resolve_route')) {
    function resolve_route(): array {
        $path=current_path();
        $segments=array_values(array_filter(explode('/',$path)));
        $seg0=$segments[0]??'';

        // Admin
        if($seg0==='admin' || $seg0==='v2admin'){
            $sub=$segments[1]??'dashboard';
            $id=$segments[2]??null;
            $adminRoutes=['login','logout','dashboard','jobs','job','categories','category','banners','banner','notices','notice','reports','analytics','settings','users','user','gallery','change-password'];
            if(in_array($sub,$adminRoutes,true)) return ['type'=>'admin','page'=>$sub,'id'=>$id,'path'=>$path];
            return ['type'=>'404','path'=>$path];
        }

        // Static system routes
        switch($seg0){
            case '': case 'home': return ['type'=>'public','page'=>'home','path'=>$path];
            case 'search': case 'khujun': return ['type'=>'public','page'=>'search','path'=>$path];
            case 'trending': return ['type'=>'public','page'=>'trending','path'=>$path];
            case 'notices': case 'notice': 
                return empty($segments[1]) ? ['type'=>'public','page'=>'notices','path'=>$path] : ['type'=>'public','page'=>'notice-detail','slug'=>$segments[1],'path'=>$path];
            case 'about': case 'about-us': return ['type'=>'public','page'=>'about','path'=>$path];
            case 'privacy': case 'privacy-policy': return ['type'=>'public','page'=>'privacy','path'=>$path];
            case 'contact': return ['type'=>'public','page'=>'contact','path'=>$path];
            case 'report-wrong': case 'report': return ['type'=>'public','page'=>'report-wrong','path'=>$path];
            case 'sitemap.xml': return ['type'=>'sitemap','page'=>'sitemap','path'=>$path];
            case 'robots.txt': return ['type'=>'robots','page'=>'robots','path'=>$path];
            case 'api':
                $a=$segments[1]??'';
                if($a==='search') return ['type'=>'public','page'=>'search','path'=>$path];
                if($a==='report-wrong') return ['type'=>'public','page'=>'report-wrong','path'=>$path];
                if($a==='track') return ['type'=>'api','page'=>'track','path'=>$path];
                return ['type'=>'404','path'=>$path];
            case 'job': case 'jobs': case 'circular': case 'chakri':
                if(empty($segments[1])) return ['type'=>'public','page'=>'jobs','path'=>$path];
                return ['type'=>'public','page'=>'job-detail','slug'=>$segments[1],'path'=>$path];
            case 'category': case 'categories':
                if(empty($segments[1])) return ['type'=>'public','page'=>'home','path'=>$path];
                return ['type'=>'public','page'=>'category','slug'=>$segments[1],'path'=>$path];
            default:
                // Check if slug is category
                try{
                    $cat=db_fetch('SELECT id, slug FROM categories WHERE slug=? AND status=1 LIMIT 1',[$seg0]);
                    if($cat) return ['type'=>'public','page'=>'category','slug'=>$seg0,'path'=>$path];
                    // Check if slug is job
                    $job=db_fetch('SELECT slug FROM jobs WHERE slug=? AND status="published" LIMIT 1',[$seg0]);
                    if($job) return ['type'=>'public','page'=>'job-detail','slug'=>$seg0,'path'=>$path];
                }catch(Throwable $e){}
                return ['type'=>'404','path'=>$path];
        }
    }
}

if (!function_exists('handle_post_actions')) {
    function handle_post_actions(): ?array {
        $path=current_path();
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST') return null;

        if(in_array($path,['/search','/khujun','/api/search'],true)){
            require_once __DIR__.'/actions/search.php';
            return search_action();
        }
        if(in_array($path,['/report-wrong','/report','/api/report-wrong'],true)){
            require_once __DIR__.'/actions/report_submit.php';
            return report_wrong_action();
        }
        if($path==='/admin/login' || $path==='/v2admin/login'){
            require_once __DIR__.'/actions/admin_login.php';
            return admin_login_action();
        }
        return null;
    }
}

if (!function_exists('render_public_page')) {
    function render_public_page(array $route): array {
        require_once __DIR__.'/pages/public.php';
        $page=$route['page']??'home';
        $meta=default_meta();
        $content='';

        switch($page){
            case 'home':
                $content=page_home();
                $meta=['title'=>setting('home_seo_title','চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি'),'description'=>setting('home_meta_description'),'keywords'=>setting('home_meta_keywords'),'canonical'=>site_url().'/', 'url'=>site_url().'/', 'type'=>'website'];
                break;
            case 'category':
                $slug=$route['slug']??'';
                $content=page_category($slug);
                $meta=category_meta($slug);
                break;
            case 'jobs':
                $content=page_jobs();
                $meta=['title'=>'সকল চাকরি — '.setting('site_name'),'description'=>'সকল চাকরির সার্কুলার এক জায়গায়','keywords'=>setting('site_keywords'),'canonical'=>site_url().'/jobs','url'=>site_url().'/jobs'];
                break;
            case 'job-detail':
                $slug=$route['slug']??'';
                $content=page_job_detail($slug);
                $meta=job_detail_meta($slug);
                break;
            case 'search':
                $content=page_search();
                $meta=['title'=>'চাকরি খুঁজুন — '.setting('site_name'),'description'=>'কিওয়ার্ড, জেলা, ক্যাটাগরি দিয়ে চাকরি খুঁজুন','keywords'=>setting('site_keywords'),'canonical'=>site_url().'/search'];
                break;
            case 'trending':
                $content=page_trending();
                $meta=['title'=>'ট্রেন্ডিং চাকরি — '.setting('site_name'),'description'=>'সবচেয়ে বেশি দেখা চাকরির সার্কুলার','canonical'=>site_url().'/trending'];
                break;
            case 'notices':
                $content=page_notices();
                $meta=['title'=>'নোটিশ বোর্ড — '.setting('site_name'),'description'=>'গুরুত্বপূর্ণ নোটিশ ও বিজ্ঞপ্তি','canonical'=>site_url().'/notices'];
                break;
            case 'notice-detail':
                $slug=$route['slug']??'';
                $content=page_notice_detail($slug);
                $meta=notice_meta($slug);
                break;
            case 'about':
                $content=page_about();
                $meta=['title'=>'আমাদের সম্পর্কে — '.setting('site_name'),'description'=>setting('site_footer'),'canonical'=>site_url().'/about'];
                break;
            case 'privacy':
                $content=page_privacy();
                $meta=['title'=>'গোপনীয়তা ও নিরাপত্তা — '.setting('site_name'),'description'=>'গোপনীয়তা নীতি','canonical'=>site_url().'/privacy'];
                break;
            case 'contact':
                $content=page_contact();
                $meta=['title'=>'যোগাযোগ — '.setting('site_name'),'canonical'=>site_url().'/contact'];
                break;
            case 'report-wrong':
                $content=page_report_wrong();
                $meta=['title'=>'তথ্যের ভুল রিপোর্ট করুন — '.setting('site_name'),'canonical'=>site_url().'/report-wrong'];
                break;
            default:
                $content=page_404();
                $meta=['title'=>'৪০৪ — পেজ পাওয়া যায়নি','description'=>'পেজটি খুঁজে পাওয়া যায়নি','canonical'=>site_url().current_path()];
        }
        if($page==='404') http_response_code(404);
        return ['type'=>'public','page'=>$page,'meta'=>$meta,'content'=>$content];
    }
}

if (!function_exists('render_admin_page')) {
    function render_admin_page(array $route): array {
        require_once __DIR__.'/pages/admin.php';
        $page=$route['page']??'dashboard';
        $id=$route['id']??null;
        $content=admin_page($page,$id);
        $meta=['title'=>'অ্যাডমিন | '.setting('site_name'),'description'=>'অ্যাডমিন প্যানেল'];
        return ['type'=>'admin','page'=>$page,'meta'=>$meta,'content'=>$content,'admin_page'=>$page];
    }
}

if (!function_exists('default_meta')) {
    function default_meta(): array {
        return [
            'title'=>setting('home_seo_title','চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি'),
            'description'=>setting('home_meta_description'),
            'keywords'=>setting('home_meta_keywords'),
            'canonical'=>site_url().current_path(),
            'url'=>site_url().current_path(),
        ];
    }
}
if (!function_exists('category_meta')) {
    function category_meta(string $slug): array {
        $cat=db_fetch('SELECT * FROM categories WHERE slug=? LIMIT 1',[$slug]);
        if(!$cat) return default_meta();
        $title=$cat['seo_title']?:$cat['name'].' — '.setting('site_name');
        $desc=$cat['seo_description']?:$cat['name'].' এর সকল চাকরির সার্কুলার';
        return ['title'=>$title,'description'=>$desc,'keywords'=>$cat['seo_keywords']?:setting('site_keywords'),'canonical'=>site_url().'/category/'.$cat['slug'],'url'=>site_url().'/category/'.$cat['slug'],'type'=>'website'];
    }
}
if (!function_exists('job_detail_meta')) {
    function job_detail_meta(string $slug): array {
        $job=db_fetch('SELECT * FROM jobs WHERE slug=? LIMIT 1',[$slug]);
        if(!$job) return default_meta();
        $image=$job['meta_image']?:$job['image']?:'';
        if(!$image && !empty($job['images'])){ $d=json_decode($job['images'],true); if(is_array($d)&&!empty($d[0])) $image=$d[0]; }
        $title=$job['seo_title']?:$job['title'].' | '.setting('site_name');
        $desc=$job['seo_description']?:mb_substr(strip_tags($job['description']),0,160);
        return ['title'=>$title,'description'=>$desc,'keywords'=>$job['seo_keywords']?:setting('site_keywords'),'image'=>$image,'url'=>site_url().'/job/'.$job['slug'],'canonical'=>site_url().'/job/'.$job['slug'],'type'=>'article'];
    }
}
if (!function_exists('notice_meta')) {
    function notice_meta(string $slug): array {
        $id=(int)$slug; $row=db_fetch('SELECT * FROM notices WHERE id=? LIMIT 1',[$id]);
        if(!$row) return default_meta();
        return ['title'=>$row['title'].' — '.setting('site_name'),'description'=>mb_substr(strip_tags($row['content']),0,160),'canonical'=>site_url().'/notice/'.$row['id']];
    }
}
