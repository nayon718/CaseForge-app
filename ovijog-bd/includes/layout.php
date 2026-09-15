<?php
/**
 * চাকরি সার্কুলার — লেআউট (Professional Light Mode, SPA Shell, Inline CSS)
 */

if (!function_exists('render_html_document')) {
    function render_html_document(array $meta, string $content, bool $admin=false): string {
        $siteName=setting('site_name','চাকরি সার্কুলার');
        $subtitle=setting('site_subtitle','CakriCircular.com');
        $logo=setting('site_logo');
        $favicon=setting('site_favicon');
        $logoSrc=$logo?uploads_url($logo):asset_url('img/logo.png');
        $faviconSrc=$favicon?uploads_url($favicon):asset_url('img/favicon.png');
        $ogImage=setting('og_default_image')?:setting('site_meta_image');
        $ogImageUrl=$ogImage?uploads_url($ogImage):$logoSrc;

        $current=current_path();
        $m=$meta+default_meta();
        if($admin) $m['title']='অ্যাডমিন | '.$siteName;

        // Categories for scrollbar menu
        $categories=[];
        try{ $categories=db_all('SELECT * FROM categories WHERE status=1 ORDER BY sort_order ASC, id ASC'); }catch(Throwable $e){}

        // Banners for counting?
        $noticeCount=0; try{ $noticeCount=(int)db_value('SELECT COUNT(*) FROM notices',[],0); }catch(Throwable $e){}

        // Build scrollbar menu
        $scrollMenuHtml='';
        foreach($categories as $cat){
            $active=($current==='/category/'.$cat['slug'] || $current==='/'.$cat['slug']) ? ' active':'';
            $scrollMenuHtml.='<a href="'.e(url('category/'.$cat['slug'])).'" data-link class="scroll-link'.$active.'" style="--cat-color:'.e($cat['color']).'"><i class="'.e($cat['icon']).'"></i><span>'.e($cat['name']).'</span></a>';
        }

        // Admin nav
        $adminSidebar='';
        if($admin && is_admin_logged_in()){
            $adminItems=[
                'dashboard'=>['ড্যাশবোর্ড','fa-gauge-high'],
                'jobs'=>['চাকরি সমূহ','fa-briefcase'],
                'categories'=>['ক্যাটাগরি','fa-layer-group'],
                'banners'=>['ব্যানার','fa-images'],
                'notices'=>['নোটিশ','fa-bell'],
                'reports'=>['ভুল রিপোর্ট','fa-triangle-exclamation'],
                'analytics'=>['অ্যানালিটিকস','fa-chart-pie'],
                'users'=>['ইউজার','fa-users'],
                'settings'=>['সেটিংস','fa-gear'],
                'change-password'=>['পাসওয়ার্ড','fa-key'],
            ];
            foreach($adminItems as $k=>$v){
                $ac=(strpos($current,'/admin/'.$k)===0 || ($k==='dashboard'&&$current==='/admin'))?' active':'';
                $adminSidebar.='<a href="'.e(url('admin/'.$k)).'" data-link class="side-link'.$ac.'"><i class="fa-solid '.$v[1].'"></i><span>'.$v[0].'</span></a>';
            }
        }

        // Public sidebar
        $sideLinks='';
        $sideLinks.='<a href="'.e(url('')).'" data-link class="side-link'.($current==='/'?' active':'').'"><i class="fa-solid fa-house"></i><span>হোম</span></a>';
        foreach($categories as $cat){
            $active=($current==='/category/'.$cat['slug'] || $current==='/'.$cat['slug'])?' active':'';
            $sideLinks.='<a href="'.e(url('category/'.$cat['slug'])).'" data-link class="side-link'.$active.'"><i class="'.e($cat['icon']).'"></i><span>'.e($cat['name']).'</span></a>';
        }
        $sideLinks.='<hr><a href="'.e(url('trending')).'" data-link class="side-link'.(strpos($current,'/trending')===0?' active':'').'"><i class="fa-solid fa-fire"></i><span>ট্রেন্ডিং</span></a>';
        $sideLinks.='<a href="'.e(url('search')).'" data-link class="side-link'.(strpos($current,'/search')===0?' active':'').'"><i class="fa-solid fa-magnifying-glass"></i><span>খুঁজুন</span></a>';
        $sideLinks.='<a href="'.e(url('notices')).'" data-link class="side-link'.(strpos($current,'/notices')===0?' active':'').'"><i class="fa-solid fa-bell"></i><span>নোটিশ</span></a>';
        $sideLinks.='<hr><a href="'.e(url('about')).'" data-link class="side-link"><i class="fa-solid fa-circle-info"></i><span>আমাদের সম্পর্কে</span></a>';
        $sideLinks.='<a href="'.e(url('privacy')).'" data-link class="side-link"><i class="fa-solid fa-shield-halved"></i><span>গোপনীয়তা</span></a>';
        $sideLinks.='<a href="'.e(url('report-wrong')).'" data-link class="side-link"><i class="fa-solid fa-flag"></i><span>ভুল রিপোর্ট</span></a>';
        $sideLinks.='<a href="'.e(url('contact')).'" data-link class="side-link"><i class="fa-solid fa-envelope"></i><span>যোগাযোগ</span></a>';

        // Bottom nav (mobile)
        $bottomNav='';
        if($admin && is_admin_logged_in()){
            $bottomNav.='<a href="'.e(url('admin/dashboard')).'" data-link class="bottom-link'.(strpos($current,'/admin/dashboard')===0||$current==='/admin'?' active':'').'"><i class="fa-solid fa-gauge-high"></i><span>ড্যাশবোর্ড</span></a>';
            $bottomNav.='<a href="'.e(url('admin/jobs')).'" data-link class="bottom-link'.(strpos($current,'/admin/jobs')===0?' active':'').'"><i class="fa-solid fa-briefcase"></i><span>চাকরি</span></a>';
            $bottomNav.='<a href="'.e(url('admin/banners')).'" data-link class="bottom-link"><i class="fa-solid fa-images"></i><span>ব্যানার</span></a>';
            $bottomNav.='<a href="'.e(url('admin/notices')).'" data-link class="bottom-link"><i class="fa-solid fa-bell"></i><span>নোটিশ</span></a>';
            $bottomNav.='<a href="'.e(url('admin/settings')).'" data-link class="bottom-link"><i class="fa-solid fa-gear"></i><span>সেটিংস</span></a>';
        } else {
            $bottomNav.='
                <a href="'.e(url('')).'" data-link class="bottom-link'.($current==='/'?' active':'').'"><i class="fa-solid fa-house"></i><span>হোম</span></a>
                <a href="'.e(url('trending')).'" data-link class="bottom-link'.(strpos($current,'/trending')===0?' active':'').'"><i class="fa-solid fa-fire"></i><span>ট্রেন্ডিং</span></a>
                <a href="'.e(url('search')).'" data-link class="bottom-link fab-center'.(strpos($current,'/search')===0?' active':'').'"><span class="fab-circle"><i class="fa-solid fa-magnifying-glass"></i></span><span>খুঁজুন</span></a>
                <a href="'.e(url('notices')).'" data-link class="bottom-link'.(strpos($current,'/notices')===0?' active':'').'"><i class="fa-solid fa-bell"></i><span>নোটিশ</span><em class="notice-badge" id="noticeBadge" hidden>0</em></a>
                <a href="#" data-open-sidebar class="bottom-link"><i class="fa-solid fa-bars"></i><span>মেনু</span></a>
            ';
        }

        $footerSlogan=setting('site_slogan','বাংলাদেশের সরকারি-বেসরকারি চাকরির সার্কুলার, ভর্তি বিজ্ঞপ্তি ও পরীক্ষার ফলাফল — প্রতিদিন হালনাগাদ, এক জায়গায়।');
        $footerText=setting('site_footer',$footerSlogan);
        $copyright=setting('site_copyright','© ২০২৬ চাকরি সার্কুলার। সর্বস্বত্ব সংরক্ষিত।');
        $contactEmail=setting('contact_email',setting('site_email'));
        $location=setting('site_location','Bangladesh');
        $fb=setting('facebook_url'); $tw=setting('twitter_url'); $tg=setting('telegram_url'); $yt=setting('youtube_url');

        // Maintenance check (public only)
        $isMaintenance=setting('maintenance_mode','0')==='1' && !$admin;
        if($isMaintenance && $current!=='/admin/login' && $current!=='/v2admin/login' && !is_admin_logged_in()){
            $content='<section class="maintenance"><div class="maint-card"><i class="fa-solid fa-screwdriver-wrench"></i><h1>রক্ষণাবেক্ষণ চলছে</h1><p>'.e(setting('maintenance_message','সাইটটি রক্ষণাবেক্ষণের জন্য সাময়িক বন্ধ আছে।')).'</p></div></section>';
        }

        return '<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0f766e">
<link rel="icon" type="image/png" href="'.e($faviconSrc).'">
'.seo_meta($m).'
'.($admin?'':'<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">').'
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
'.($admin?'':'<script>window.CAKRI={base:"'.e(BASE_URL).'",siteName:'.json_encode($siteName,JSON_UNESCAPED_UNICODE).'}</script>').'
<style>
:root{--bg:#f8fafc;--surface:#ffffff;--surface2:#f1f5f9;--text:#0f172a;--muted:#64748b;--line:#e2e8f0;--primary:#0f766e;--primary2:#14b8a6;--primary-dark:#0f5a52;--accent:#f59e0b;--danger:#dc2626;--success:#16a34a;--radius:16px;--radius-lg:22px;--shadow:0 8px 24px -10px rgba(15,23,42,.12);--shadow-lg:0 20px 50px -20px rgba(15,23,42,.22);--font-bn:"Hind Siliguri",system-ui,sans-serif;--font-en:"Inter",system-ui,sans-serif}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font-bn);font-size:15px;line-height:1.75;min-height:100vh;-webkit-font-smoothing:antialiased}
body.menu-open{overflow:hidden}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
button,input,select,textarea{font:inherit;color:inherit}
button,[role=button]{cursor:pointer}

/* Progress & Loader */
#topProgress{position:fixed;top:0;left:0;right:0;height:3px;background:transparent;z-index:10000}
#topProgress .bar{height:100%;width:0;background:linear-gradient(90deg,var(--primary),var(--accent));box-shadow:0 0 12px rgba(20,184,166,.6);transition:width .25s ease, opacity .2s}
#appLoader{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(248,250,252,.75);backdrop-filter:blur(6px);z-index:9990}
#appLoader.show{display:flex}
.loader-spinner{width:48px;height:48px;border:3px solid rgba(15,118,110,.15);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* Header */
.site-header{position:sticky;top:0;z-index:900;background:rgba(255,255,255,.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--line);box-shadow:0 2px 20px rgba(15,23,42,.04)}
.header-in{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 16px}
.brand{display:flex;align-items:center;gap:12px;min-width:0}
.brand img{width:44px;height:44px;border-radius:12px;object-fit:cover;background:linear-gradient(135deg,var(--primary),var(--primary2));box-shadow:var(--shadow)}
.brand-text{min-width:0}
.brand-title{font-size:18px;font-weight:700;line-height:1.2;white-space:nowrap}
.brand-sub{font-size:12px;color:var(--muted);font-family:var(--font-en);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.header-right{display:flex;align-items:center;gap:8px}
.icon-btn{width:42px;height:42px;border:0;border-radius:12px;background:var(--surface2);display:flex;align-items:center;justify-content:center;color:var(--primary);box-shadow:var(--shadow);transition:.2s}
.icon-btn:hover{background:var(--primary);color:#fff;transform:translateY(-1px)}
@media(min-width:768px){.header-in{padding:14px 28px}.brand img{width:48px;height:48px}.brand-title{font-size:20px}}

/* Scrollbar menu (category chips) */
.scrollbar-menu{position:sticky;top:66px;z-index:850;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);overflow:hidden}
.scrollbar-in{max-width:1280px;margin:0 auto;display:flex;gap:8px;padding:10px 16px;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.scrollbar-in::-webkit-scrollbar{display:none}
.scroll-link{flex:none;display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:999px;background:var(--surface2);border:1px solid var(--line);font-size:13.5px;font-weight:600;color:var(--text);transition:.2s;white-space:nowrap}
.scroll-link i{color:var(--cat-color,var(--primary));font-size:14px}
.scroll-link:hover{background:var(--surface);transform:translateY(-1px);box-shadow:var(--shadow)}
.scroll-link.active{background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;border-color:var(--primary)}
.scroll-link.active i{color:#fff}
@media(min-width:768px){.scrollbar-menu{top:74px}.scrollbar-in{padding:12px 28px}}

/* Sidebar */
.site-sidebar,.admin-sidebar{position:fixed;inset:0;width:min(340px,86vw);background:var(--surface);border-right:1px solid var(--line);z-index:960;transform:translateX(-105%);transition:transform .34s cubic-bezier(.2,.8,.2,1);display:flex;flex-direction:column;box-shadow:var(--shadow-lg)}
.site-sidebar.open,.admin-sidebar.open{transform:translateX(0)}
.side-head{display:flex;align-items:center;gap:12px;padding:18px 16px;border-bottom:1px solid var(--line);background:linear-gradient(135deg,var(--surface),var(--surface2))}
.side-head img{width:44px;height:44px;border-radius:12px;object-fit:cover}
.side-head strong{display:block;font-size:16px}
.side-head small{color:var(--muted);font-size:12px;display:block;font-family:var(--font-en)}
.side-close{margin-left:auto;width:36px;height:36px;border:0;border-radius:10px;background:var(--surface2);color:var(--muted)}
.side-nav{padding:12px;overflow-y:auto;flex:1}
.side-link{display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:12px;color:var(--text);font-weight:500;transition:.2s;margin-bottom:2px}
.side-link i{width:22px;text-align:center;color:var(--muted);font-size:15px}
.side-link:hover{background:var(--surface2);transform:translateX(2px)}
.side-link.active{background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;box-shadow:var(--shadow)}
.side-link.active i{color:#fff}
.side-nav hr{border:0;height:1px;background:var(--line);margin:10px 4px}
.side-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.38);backdrop-filter:blur(3px);z-index:-1;opacity:0;pointer-events:none;transition:.25s}
.open .side-backdrop{opacity:1;pointer-events:auto}

/* Main */
main{max-width:1280px;margin:0 auto;padding:0 0 90px;width:100%}
#appMain{animation:pageIn .36s cubic-bezier(.2,.8,.2,1) both}
@keyframes pageIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
#appMain.fade-out{animation:pageOut .18s ease both}
@keyframes pageOut{to{opacity:0;transform:translateY(-6px)}}
.page-holder{padding:18px 16px}
@media(min-width:768px){.page-holder{padding:26px 28px} main{padding-bottom:0}}

/* Banner carousel */
.banner-section{margin-bottom:22px;position:relative}
.banner-track{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding:4px 2px 10px;scrollbar-width:none}
.banner-track::-webkit-scrollbar{display:none}
.banner-card{flex:none;width:min(92vw, 760px);scroll-snap-align:center;border-radius:18px;overflow:hidden;position:relative;background:var(--surface);border:1px solid var(--line);box-shadow:var(--shadow);aspect-ratio:16/7}
.banner-card img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.banner-card:hover img{transform:scale(1.03)}
.banner-card a{position:absolute;inset:0}
.banner-dots{display:flex;gap:6px;justify-content:center;margin-top:8px}
.banner-dot{width:8px;height:8px;border-radius:50%;background:var(--line);border:0;transition:.2s}
.banner-dot.active{background:var(--primary);width:22px;border-radius:999px}
.banner-nav{position:absolute;top:50%;transform:translateY(-50%);width:36px;height:36px;border:0;border-radius:50%;background:rgba(255,255,255,.9);box-shadow:var(--shadow);display:flex;align-items:center;justify-content:center;z-index:2}
.banner-prev{left:8px}.banner-next{right:8px}
@media(min-width:768px){.banner-card{width:100%} .banner-track{scroll-snap-type:none;overflow:hidden}}

/* Job cards */
.section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:22px 0 14px}
.section-title{font-size:20px;font-weight:700;display:flex;align-items:center;gap:8px}
.section-title i{color:var(--primary)}
.section-desc{color:var(--muted);font-size:13px;margin-top:2px}
.job-list{display:grid;gap:12px}
.job-card{display:flex;gap:14px;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:14px;box-shadow:var(--shadow);transition:transform .2s, box-shadow .2s;position:relative;overflow:hidden}
.job-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}
.job-thumb{width:72px;height:72px;border-radius:14px;overflow:hidden;background:var(--surface2);flex:none;border:1px solid var(--line)}
.job-thumb img{width:100%;height:100%;object-fit:cover}
.job-main{min-width:0;flex:1}
.job-top{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px}
.job-cat{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:999px;background:color-mix(in srgb,var(--cat-color) 12%, white);color:var(--cat-color);border:1px solid color-mix(in srgb,var(--cat-color) 20%, transparent)}
.job-time{font-size:11.5px;color:var(--muted);display:flex;align-items:center;gap:4px}
.job-title{font-size:16px;font-weight:700;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.job-title a:hover{color:var(--primary)}
.job-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;font-size:12.5px;color:var(--muted)}
.job-meta span{display:inline-flex;align-items:center;gap:4px;background:var(--surface2);border:1px solid var(--line);padding:3px 8px;border-radius:999px}
.job-meta .expired{background:#fee2e2;color:#b91c1c;border-color:#fecaca}
.job-foot{display:flex;align-items:center;justify-content:space-between;margin-top:10px}
.job-read{font-size:13px;font-weight:600;color:var(--primary);display:inline-flex;gap:6px;align-items:center}
.job-read:hover{gap:8px}
.share-btn{width:34px;height:34px;border:1px solid var(--line);background:var(--surface2);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--muted);transition:.2s}
.share-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}

/* Share popup */
.share-popup{position:absolute;right:12px;top:54px;z-index:30;background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow-lg);padding:14px;width:min(320px,92%);animation:popIn .22s ease}
@keyframes popIn{from{opacity:0;transform:translateY(6px) scale(.98)}to{opacity:1;transform:none}}
.share-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-weight:700}
.share-close{width:28px;height:28px;border:0;border-radius:8px;background:var(--surface2)}
.share-title{font-size:13px;color:var(--muted);margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.share-linkbox{display:flex;gap:6px;margin-bottom:12px}
.share-linkbox input{flex:1;padding:8px 10px;border-radius:10px;border:1px solid var(--line);background:var(--surface2);font-size:12px}
.share-linkbox button{padding:8px 12px;border-radius:10px;border:0;background:var(--primary);color:#fff;font-weight:600;font-size:12px}
.share-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.share-grid a{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;background:var(--surface2);border:1px solid var(--line);font-size:13px;font-weight:600;transition:.2s}
.share-grid a:hover{transform:translateY(-1px)}
.sh-fb{color:#1877f2}.sh-tw{color:#000}.sh-wa{color:#25d366}.sh-tg{color:#229ed9}

/* Job detail */
.job-detail{max-width:900px;margin:0 auto}
.detail-head{margin-bottom:18px}
.detail-title{font-size:clamp(22px,4vw,30px);font-weight:800;line-height:1.35;margin-bottom:10px}
.detail-meta{display:flex;flex-wrap:wrap;gap:8px;color:var(--muted);font-size:13px}
.detail-meta span{display:inline-flex;gap:6px;align-items:center;background:var(--surface);border:1px solid var(--line);padding:5px 10px;border-radius:999px}
.detail-gallery{margin:18px 0;display:grid;gap:10px}
.detail-gallery-main{border-radius:18px;overflow:hidden;border:1px solid var(--line);background:var(--surface);aspect-ratio:16/9;position:relative;cursor:zoom-in}
.detail-gallery-main img{width:100%;height:100%;object-fit:cover}
.detail-thumbs{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px}
.detail-thumbs img{width:84px;height:64px;border-radius:12px;object-fit:cover;border:2px solid transparent;cursor:pointer;flex:none}
.detail-thumbs img.active{border-color:var(--primary)}
.detail-content{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-lg);padding:20px;box-shadow:var(--shadow);line-height:1.9}
.detail-content h2,.detail-content h3{margin:18px 0 10px;font-weight:700}
.detail-content p{margin-bottom:12px}
.detail-content ul{margin:10px 0 16px 18px}
.detail-content a{color:var(--primary);text-decoration:underline}
.detail-links{margin-top:18px;display:grid;gap:10px}
.detail-link-card{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:14px;background:var(--surface2);border:1px solid var(--line);transition:.2s}
.detail-link-card:hover{border-color:var(--primary);transform:translateY(-1px)}
.apply-btn{width:100%;margin-top:18px;padding:14px 20px;border-radius:14px;border:0;background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;font-weight:700;font-size:16px;box-shadow:0 10px 24px -10px rgba(15,118,110,.5);display:flex;align-items:center;justify-content:center;gap:8px;transition:.2s}
.apply-btn:hover{transform:translateY(-1px);box-shadow:0 14px 28px -12px rgba(15,118,110,.6)}
.related-section{margin-top:28px}
.related-grid{display:grid;gap:12px}
@media(min-width:700px){.related-grid{grid-template-columns:1fr 1fr}}

/* Pagination */
.pagination{display:flex;gap:6px;justify-content:center;margin-top:24px;flex-wrap:wrap}
.pagination a,.pagination span{min-width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--surface);border:1px solid var(--line);font-weight:600;transition:.2s}
.pagination a:hover,.pagination a.active{background:var(--primary);color:#fff;border-color:var(--primary);transform:translateY(-1px)}
.pg-dots{background:none!important;border:none!important}

/* Notice */
.notice-list{display:grid;gap:12px}
.notice-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:16px;box-shadow:var(--shadow);display:flex;gap:14px;align-items:flex-start;transition:.2s}
.notice-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}
.notice-icon{width:44px;height:44px;border-radius:12px;background:#fef3c7;color:#b45309;display:flex;align-items:center;justify-content:center;flex:none}
.notice-card.important{border-color:#fde68a;background:#fffbeb}
.notice-title{font-weight:700;font-size:15px}
.notice-time{font-size:12px;color:var(--muted);margin-top:4px}
.notice-content{color:#334155;font-size:14px;margin-top:6px}

/* Search */
.search-hero{text-align:center;margin-bottom:20px}
.search-box{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-lg);padding:20px;box-shadow:var(--shadow)}
.search-input-wrap{position:relative}
.search-input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted)}
.search-input-wrap input{padding-left:42px}
.filter-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.chip{padding:6px 12px;border-radius:999px;background:var(--surface2);border:1px solid var(--line);font-size:13px;color:var(--muted);cursor:pointer;transition:.2s}
.chip:hover,.chip.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.top-search{margin-top:18px}
.top-search h3{font-size:14px;margin-bottom:8px;color:var(--muted)}

/* Footer */
.site-footer{border-top:1px solid var(--line);background:var(--surface);padding:36px 16px 24px;margin-top:30px}
.footer-grid{max-width:1280px;margin:0 auto;display:grid;gap:28px;grid-template-columns:1fr}
@media(min-width:700px){.footer-grid{grid-template-columns:1.4fr .8fr 1fr}}
.footer-brand{display:flex;gap:12px;align-items:center;margin-bottom:12px}
.footer-brand img{width:48px;height:48px;border-radius:12px}
.footer-brand strong{display:block;font-size:18px}
.footer-brand small{color:var(--muted);font-size:13px}
.footer-desc{color:var(--muted);font-size:14px;line-height:1.7}
.footer-title{font-size:15px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.footer-links{display:grid;gap:8px}
.footer-links a{color:var(--muted);font-size:14px;transition:.2s}
.footer-links a:hover{color:var(--primary);transform:translateX(2px)}
.social-links{display:flex;gap:8px;margin-top:12px}
.social-links a{width:38px;height:38px;border-radius:10px;background:var(--surface2);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;color:var(--muted);transition:.2s}
.social-links a:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.footer-bottom{max-width:1280px;margin:28px auto 0;padding-top:18px;border-top:1px solid var(--line);text-align:center;color:var(--muted);font-size:13px}

/* Bottom nav */
.bottom-nav{position:fixed;left:0;right:0;bottom:0;height:72px;display:grid;grid-template-columns:repeat(5,1fr);background:rgba(255,255,255,.96);border-top:1px solid var(--line);backdrop-filter:blur(16px);z-index:880;padding-bottom:env(safe-area-inset-bottom);box-shadow:0 -8px 24px rgba(15,23,42,.08)}
.bottom-link{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:var(--muted);font-size:11px;font-weight:600;position:relative;transition:.2s}
.bottom-link i{font-size:18px;transition:.2s}
.bottom-link.active{color:var(--primary)}
.bottom-link.active i{transform:translateY(-2px)}
.fab-center{position:relative}
.fab-circle{width:54px;height:54px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px -8px rgba(15,118,110,.6);margin-top:-18px;border:4px solid var(--bg);transition:.25s}
.fab-center.active .fab-circle{transform:scale(1.05) translateY(-2px)}
.notice-badge{position:absolute;top:6px;right:18px;background:var(--danger);color:#fff;font-size:10px;min-width:18px;height:18px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 5px;font-weight:700}
@media(min-width:768px){.bottom-nav{display:none}}

/* Toast & Lightbox */
#toastBox{position:fixed;right:16px;left:16px;top:78px;z-index:11000;display:grid;gap:10px;pointer-events:none}
@media(min-width:600px){#toastBox{left:auto;width:380px}}
.toast{background:var(--surface);border:1px solid var(--line);box-shadow:var(--shadow-lg);border-radius:14px;padding:13px 15px;display:flex;gap:11px;align-items:center;animation:toastIn .25s ease both;pointer-events:auto}
.toast.error{border-color:#fecaca;background:#fff7f7}
.toast.success{border-color:#bbf7d0;background:#f7fef9}
.toast i{font-size:18px}
.toast.error i{color:var(--danger)}
.toast.success i{color:var(--success)}
.toast-close{margin-left:auto;border:0;background:none;color:var(--muted)}
@keyframes toastIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
#lightbox{position:fixed;inset:0;background:rgba(2,6,23,.92);z-index:12000;display:none;align-items:center;justify-content:center;padding:20px}
#lightbox.show{display:flex}
#lightbox img{max-height:82vh;max-width:92vw;border-radius:14px;box-shadow:var(--shadow-lg)}
.lb-close{position:absolute;top:16px;right:16px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);border:0;color:#fff}
.lb-nav{position:absolute;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.12);border:0;color:#fff;display:flex;align-items:center;justify-content:center}
.lb-prev{left:12px}.lb-next{right:12px}

/* Cards, forms */
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:20px}
@media(min-width:768px){.card{padding:24px}}
.form-group{margin-bottom:14px}
.form-group label{display:flex;align-items:center;gap:6px;font-weight:600;font-size:14px;margin-bottom:6px}
.req{color:var(--danger)}
input,select,textarea{width:100%;border:1.5px solid var(--line);background:var(--surface);border-radius:12px;padding:11px 13px;font-size:15px;transition:.2s;outline:0}
input:focus,select:focus,textarea:focus{border-color:var(--primary2);box-shadow:0 0 0 4px rgba(20,184,166,.12)}
textarea{resize:vertical;min-height:90px}
.btn{border:0;border-radius:12px;padding:11px 16px;font-weight:600;font-size:14px;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:.2s}
.btn:hover{transform:translateY(-1px)}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;box-shadow:0 8px 18px -8px rgba(15,118,110,.6)}
.btn-ghost{background:var(--surface2);border:1px solid var(--line);color:var(--text)}
.btn-soft{background:#ecfdf5;color:var(--primary)}
.btn-danger{background:#fee2e2;color:var(--danger)}
.btn-sm{padding:7px 11px;font-size:12px;border-radius:10px}
.btn-lg{padding:13px 22px;font-size:16px}
.empty-state{text-align:center;padding:40px 20px;color:var(--muted)}
.empty-state i{font-size:48px;color:#cbd5e1;margin-bottom:10px;display:block}
.maintenance{display:flex;align-items:center;justify-content:center;min-height:60vh;text-align:center}
.maint-card{background:var(--surface);border:1px solid var(--line);border-radius:22px;padding:36px;box-shadow:var(--shadow-lg);max-width:420px;margin:auto}
.maint-card i{font-size:48px;color:var(--primary);margin-bottom:12px}

/* Admin */
.admin-content{max-width:1200px;margin:0 auto}
.admin-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.stat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:20px}
@media(min-width:760px){.stat-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:1100px){.stat-grid{grid-template-columns:repeat(5,1fr)}}
.stat-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:16px;display:flex;gap:12px;align-items:center;box-shadow:var(--shadow)}
.stat-card i{width:44px;height:44px;border-radius:12px;background:#ccfbf1;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:18px;flex:none}
.stat-card strong{display:block;font-size:18px}
.stat-card span{color:var(--muted);font-size:12px}
.admin-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:18px;margin-bottom:20px}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:10px 8px;text-align:left;border-bottom:1px solid var(--line);white-space:nowrap}
th{color:var(--muted);font-size:12px}
.admin-thumb{width:44px;height:44px;object-fit:cover;border-radius:10px}
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
.upload-line{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.upload-line>span{width:120px;font-weight:600;font-size:14px}
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px}
.gallery-item{position:relative;border-radius:12px;overflow:hidden;background:var(--surface2);border:1px solid var(--line)}
.gallery-item img{width:100%;height:90px;object-fit:cover}
.g-item-actions{position:absolute;inset:auto 0 0 0;background:linear-gradient(transparent,rgba(0,0,0,.6));padding:6px;display:flex;gap:6px;justify-content:flex-end}
.badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:3px 8px;border-radius:999px;background:var(--surface2);border:1px solid var(--line);font-weight:600}
.badge.published{background:#dcfce7;color:#166534;border-color:#bbf7d0}
.badge.draft{background:#fef3c7;color:#92400e}
.badge.expired{background:#fee2e2;color:#b91c1c}
</style>
'.organization_schema().website_schema().'
</head>
<body class="'.($admin&&is_admin_logged_in()?'admin-body':'public-body').'">
<div id="topProgress"><div class="bar" id="topProgressBar"></div></div>
<div id="appLoader"><div class="loader-spinner"></div></div>
<div id="toastBox"></div>
<div id="lightbox" role="dialog" aria-modal="true"><button class="lb-close" data-lb-close><i class="fa-solid fa-xmark"></i></button><button class="lb-nav lb-prev" data-lb-prev><i class="fa-solid fa-chevron-left"></i></button><img id="lightboxImg" alt="ছবি"><button class="lb-nav lb-next" data-lb-next><i class="fa-solid fa-chevron-right"></i></button></div>

<header class="site-header">
  <div class="header-in">
    <a class="brand" href="'.e(url('')).'" data-link>
      <img src="'.e($logoSrc).'" alt="'.e($siteName).'">
      <div class="brand-text"><div class="brand-title">'.e($siteName).'</div><div class="brand-sub">'.e($subtitle).'</div></div>
    </a>
    <div class="header-right">
      <a href="'.e(url('search')).'" data-link class="icon-btn" aria-label="খুঁজুন"><i class="fa-solid fa-magnifying-glass"></i></a>
      <button type="button" class="icon-btn" id="menuTrigger" aria-label="মেনু"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>
'.($admin&&is_admin_logged_in()?'':'<div class="scrollbar-menu"><div class="scrollbar-in">'.$scrollMenuHtml.'</div></div>').'

'.($admin&&is_admin_logged_in()?'<aside class="admin-sidebar" id="adminSidebar"><div class="side-head"><img src="'.e($logoSrc).'" alt=""><div><strong>অ্যাডমিন</strong><small>'.e($siteName).'</small></div><button class="side-close" data-close-side><i class="fa-solid fa-xmark"></i></button></div><nav class="side-nav">'.$adminSidebar.'<hr><a href="'.e(url('')).'" data-link class="side-link"><i class="fa-solid fa-house"></i><span>ওয়েবসাইট</span></a><a href="'.e(url('admin/logout')).'" class="side-link"><i class="fa-solid fa-right-from-bracket"></i><span>লগআউট</span></a></nav><div class="side-backdrop" data-close-side></div></aside>':'<aside class="site-sidebar" id="siteSidebar"><div class="side-head"><img src="'.e($logoSrc).'" alt=""><div><strong>'.e($siteName).'</strong><small>'.e($subtitle).'</small></div><button class="side-close" data-close-side><i class="fa-solid fa-xmark"></i></button></div><nav class="side-nav">'.$sideLinks.'</nav><div class="side-backdrop" data-close-side></div></aside>').'

<main id="appMain"><div id="pageContent" class="page-holder">'.$content.'</div></main>

'.($admin?'':'<footer class="site-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-brand"><img src="'.e($logoSrc).'" alt=""><div><strong>'.e($siteName).'</strong><small>'.e($subtitle).'</small></div></div>
      <p class="footer-desc">'.e($footerText).'</p>
      <div class="social-links">
        '.($fb?'<a href="'.e($fb).'" target="_blank" rel="noopener"><i class="fa-brands fa-facebook"></i></a>':'').'
        '.($tw?'<a href="'.e($tw).'" target="_blank" rel="noopener"><i class="fa-brands fa-x-twitter"></i></a>':'').'
        '.($tg?'<a href="'.e($tg).'" target="_blank" rel="noopener"><i class="fa-brands fa-telegram"></i></a>':'').'
        '.($yt?'<a href="'.e($yt).'" target="_blank" rel="noopener"><i class="fa-brands fa-youtube"></i></a>':'').'
      </div>
    </div>
    <div>
      <h3 class="footer-title"><i class="fa-solid fa-envelope"></i> যোগাযোগ করুন</h3>
      <div class="footer-links"><a><i class="fa-solid fa-at"></i> '.e($contactEmail).'</a><a><i class="fa-solid fa-location-dot"></i> '.e($location).'</a></div>
      <h3 class="footer-title" style="margin-top:16px"><i class="fa-solid fa-link"></i> দ্রুত লিংক</h3>
      <div class="footer-links"><a href="'.e(url('')).'" data-link>হোম</a><a href="'.e(url('trending')).'" data-link>ট্রেন্ডিং</a><a href="'.e(url('search')).'" data-link>খুঁজুন</a><a href="'.e(url('notices')).'" data-link>নোটিশ</a></div>
    </div>
    <div>
      <h3 class="footer-title"><i class="fa-solid fa-circle-info"></i> Information</h3>
      <div class="footer-links"><a href="'.e(url('about')).'" data-link>আমাদের সম্পর্কে</a><a href="'.e(url('privacy')).'" data-link>গোপনীয়তা ও নিরাপত্তা</a><a href="'.e(url('report-wrong')).'" data-link>তথ্যের ভুল রিপোর্ট করুন</a><a href="'.e(url('contact')).'" data-link>যোগাযোগ</a></div>
    </div>
  </div>
  <div class="footer-bottom">'.e($copyright).' | CakriCircular.com — Trusted Bangladesh Job Portal</div>
</footer>').'

<nav class="bottom-nav">'.$bottomNav.'</nav>

<script>window.CAKRI={base:"'.e(BASE_URL).'",isAdmin:'.($admin?'true':'false').'};</script>
<script src="'.e(asset_url('js/app.js')).'" defer></script>
</body>
</html>';
    }
}
if (!function_exists('current_page_active')) {
    function current_page_active(string $current, string $target): bool {
        $target=trim($target,'/'); $current=trim($current,'/');
        if($target==='') return $current===''||$current==='home';
        return $current===$target;
    }
}
