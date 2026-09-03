<?php
/**
 * সাইট লেআউট — header, sidebar, footer, CSS ও SPA JavaScript একসাথে
 */

if (!function_exists('render_html_document')) {
    function render_html_document(array $meta, string $content, bool $admin = false): string
    {
        $siteName = setting('site_name', 'বাংলাদেশ নাগরিক');
        $subtitle = setting('site_subtitle', 'Ovijog BD · স্বচ্ছতার প্ল্যাটফর্ম');
        $logo = setting('site_logo');
        $favicon = setting('site_favicon');
        $logoSrc = $logo ? uploads_url($logo) : asset_url('img/logo.png');
        $faviconSrc = $favicon ? uploads_url($favicon) : asset_url('img/favicon.png');

        $current = current_path();
        $m = $meta + default_meta();
        if ($admin) {
            $m['title'] = 'অ্যাডমিন | ' . $siteName;
        }

        $navItems = [
            'home' => ['', 'ফা-১', 'fa-house', 'হোম'],
            'report' => ['report', 'ফা-২', 'fa-plus', 'রিপোর্ট'],
            'search' => ['search', 'ফা-৩', 'fa-magnifying-glass', 'খুঁজুন'],
            'leaderboard' => ['leaderboard', 'ফা-৪', 'fa-trophy', 'লিডার বোর্ড'],
            'donation' => ['donation', 'ফা-৫', 'fa-hand-holding-heart', 'অনুদান'],
        ];

        $navHtml = '';
        foreach ($navItems as $nav) {
            $path = '/'.trim($nav[1],'/');
            $active = current_page_active($current, $nav[1]);
            $navHtml .= '<a href="' . e(url($nav[1])) . '" data-link class="side-link' . ($active?' active':'') . '"><i class="fa-solid ' . e($nav[2]) . '"></i><span>' . e($nav[3]) . '</span></a>';
        }
        $bottomNavHtml = '';
        foreach ($navItems as $nav) {
            $active = current_page_active($current, $nav[1]);
            $bottomNavHtml .= '<a href="' . e(url($nav[1])) . '" data-link class="bottom-link' . ($active?' active':'') . '"><i class="fa-solid ' . e($nav[2]) . '"></i><span>' . e($nav[3]) . '</span></a>';
        }

        $adminNav = '';
        $adminActive = strpos($current, '/admin') === 0;
        $adminSidebar = '';
        if ($admin && is_admin_logged_in()) {
            $adminItems = [
                'dashboard'=>'ড্যাশবোর্ড', 'reports'=>'রিপোর্ট', 'payments'=>'পেমেন্ট',
                'payment-methods'=>'পেমেন্ট মেথড', 'analytics'=>'অ্যানালিটিকস',
                'gallery'=>'গ্যালারি', 'settings'=>'সেটিংস', 'change-password'=>'পাসওয়ার্ড',
            ];
            $adminBottom = '';
            foreach ($adminItems as $k=>$v) {
                $ac = '';
                if (($k==='reports' && (strpos($current,'/admin/report')===0)) || (strpos($current,'/admin/'.$k)===0)) $ac=' active';
                $icon = ['dashboard'=>'fa-gauge-high','reports'=>'fa-file-lines','payments'=>'fa-money-bill-transfer','payment-methods'=>'fa-wallet','analytics'=>'fa-chart-pie','gallery'=>'fa-images','settings'=>'fa-gear','change-password'=>'fa-key'][$k] ?? 'fa-circle';
                $adminSidebar .= '<a href="' . e(url('admin/'.$k)) . '" data-link class="side-link'.$ac.'"><i class="fa-solid '.$icon.'"></i><span>'.$v.'</span></a>';
            }
            foreach (array_slice($adminItems,0,5) as $k=>$v) {
                $ac = '';
                if (strpos($current,'/admin/'.$k)===0) $ac=' active';
                $icon = ['dashboard'=>'fa-gauge-high','reports'=>'fa-file-lines','payments'=>'fa-money-bill-transfer','payment-methods'=>'fa-wallet','analytics'=>'fa-chart-pie'][$k] ?? 'fa-circle';
                $adminBottom .= '<a href="' . e(url('admin/'.$k)) . '" data-link class="bottom-link'.$ac.'"><i class="fa-solid '.$icon.'"></i><span>'.$v.'</span></a>';
            }
            if (isset($adminItems[$current]) === false && strpos($current,'/admin/') === 0) {
                // keep current nav active if matched above
            }
        }

        $footer = setting('site_footer');
        $slogan = setting('site_slogan');
        $contactEmail = setting('contact_email', setting('site_email'));
        $location = setting('site_location', 'Bangladesh');
        $year = bn_number((int)date('Y'));

        $navbar = ($admin && is_admin_logged_in())
            ? '<aside class="admin-sidebar" id="adminSidebar"><div class="side-head"><img src="' . e($logoSrc). '" alt="logo"><div><strong>অ্যাডমিন</strong><small>'.$siteName.'</small></div><button class="side-close" data-close-side><i class="fa-solid fa-xmark"></i></button></div><nav class="side-nav">'.$adminSidebar.'<hr><a href="'.e(url('')).'" data-link class="side-link"><i class="fa-solid fa-house"></i><span>ওয়েবসাইট</span></a><a href="'.e(url('admin/logout')).'" data-link class="side-link"><i class="fa-solid fa-right-from-bracket"></i><span>লগআউট</span></a></nav><div class="side-backdrop" data-close-side></div></aside>'
            : '<aside class="site-sidebar" id="siteSidebar"><div class="side-head"><img src="' . e($logoSrc). '" alt="logo"><div><strong>'.$siteName.'</strong><small>'.$subtitle.'</small></div><button class="side-close" data-close-side><i class="fa-solid fa-xmark"></i></button></div><nav class="side-nav">'.$navHtml.'<hr><a href="'.e(url('admin/login')).'" data-link class="side-link"><i class="fa-solid fa-user-shield"></i><span>অ্যাডমিন</span></a></nav><div class="side-backdrop" data-close-side></div></aside>';

        $headerExtra = $admin && is_admin_logged_in() ? '<a href="' . e(url('admin/logout')) . '" data-link class="header-admin-logout"><i class="fa-solid fa-right-from-bracket"></i></a>' : '';

        $trackScript = 'trackVisit(' . json_encode(current_path()) . ');';

        return '<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0f766e">
<link rel="icon" type="image/png" href="' . e($faviconSrc) . '">
' . seo_meta($m) . '
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--bg:#f1f5f9;--surface:#ffffff;--surface2:#f8fafc;--text:#0f172a;--muted:#64748b;--line:#e2e8f0;--primary:#0f766e;--primary2:#14b8a6;--primary-dark:#115e59;--accent:#f59e0b;--danger:#dc2626;--success:#16a34a;--radius:18px;--shadow:0 10px 30px -12px rgba(15,23,42,.14);--shadow-lg:0 24px 60px -24px rgba(15,23,42,.25);--font-bn:"Hind Siliguri",sans-serif;--font-en:"Inter",sans-serif}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font-bn);font-size:15px;line-height:1.75;min-height:100vh;padding-bottom:78px}
body.menu-open,body.admin-menu-open{overflow:hidden}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}
button,input,select,textarea{font:inherit;color:inherit}
button,[type=button],[type=submit],[role=button]{cursor:pointer}
main{max-width:1200px;margin:0 auto;padding:18px 16px 36px;width:100%}
@media(min-width:768px){main{padding:26px 28px 42px}}

/* Progress + loader */
#topProgress{position:fixed;top:0;left:0;right:0;height:3px;background:transparent;z-index:10000;border-radius:0 4px 4px 0}
#topProgress .bar{height:100%;width:0;background:linear-gradient(90deg,var(--primary2),var(--accent));box-shadow:0 0 16px rgba(20,184,166,.7);transition:width .25s ease}
#appLoader{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(241,245,249,.72);backdrop-filter:blur(5px);z-index:9990}
#appLoader.show{display:flex}
.loader-spinner{width:52px;height:52px;border:4px solid rgba(15,118,110,.16);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* Header */
.site-header{position:sticky;top:0;z-index:900;background:rgba(255,255,255,.86);backdrop-filter:blur(14px);border-bottom:1px solid var(--line);box-shadow:0 1px 10px rgba(15,23,42,.05)}
.header-in{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 16px}
.brand{display:flex;align-items:center;gap:12px;min-width:0}
.brand img,.brand .brand-fallback{width:46px;height:46px;border-radius:14px;object-fit:cover;background:linear-gradient(135deg,var(--primary),var(--primary2));display:flex;align-items:center;justify-content:center;color:#fff;padding:8px;box-shadow:var(--shadow)}
.brand-text{min-width:0}
.brand-title{font-size:17px;font-weight:700;line-height:1.25;white-space:nowrap}
.brand-sub{font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.header-right{display:flex;align-items:center;gap:8px}
.menu-trigger{width:46px;height:46px;border:0;border-radius:14px;background:var(--surface2);box-shadow:var(--shadow);color:var(--primary);font-size:18px;display:flex;align-items:center;justify-content:center;transition:.2s}
.menu-trigger:hover{background:var(--primary);color:#fff}
.header-admin-logout{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--muted)}
@media(min-width:768px){.header-in{padding:13px 28px}.brand-title{font-size:19px}.brand img,.brand .brand-fallback{width:50px;height:50px}}

/* Sidebar */
.site-sidebar,.admin-sidebar{position:fixed;inset:0;width:min(330px,88vw);background:var(--surface);border-right:1px solid var(--line);z-index:950;transform:translateX(-105%);transition:transform .3s cubic-bezier(.2,.8,.2,1);display:flex;flex-direction:column;box-shadow:var(--shadow-lg)}
.site-sidebar.open,.admin-sidebar.open{transform:translateX(0)}
.side-head{display:flex;align-items:center;gap:12px;padding:20px;border-bottom:1px solid var(--line);background:linear-gradient(135deg,var(--surface),var(--surface2))}
.side-head img{width:48px;height:48px;border-radius:14px;object-fit:cover}
.side-head strong{display:block;font-size:16px}
.side-head small{color:var(--muted);font-size:12px;display:block}
.side-close{margin-left:auto;width:36px;height:36px;border:0;border-radius:10px;background:var(--surface2);color:var(--muted)}
.side-nav{padding:14px;overflow-y:auto;flex:1}
.side-link{display:flex;align-items:center;gap:13px;padding:12px 14px;border-radius:13px;color:var(--text);font-weight:500;transition:.2s;margin-bottom:4px}
.side-link i{width:24px;text-align:center;color:var(--muted);font-size:16px}
.side-link:hover{background:var(--surface2);transform:translateX(3px)}
.side-link.active{background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;box-shadow:var(--shadow)}
.side-link.active i{color:#fff}
.side-nav hr{border:0;height:1px;background:var(--line);margin:12px 4px}
.side-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.4);backdrop-filter:blur(2px);z-index:-1;opacity:0;pointer-events:none;transition:.25s}
.open .side-backdrop{opacity:1;pointer-events:auto}

/* Main content transition */
#appMain{animation:pageIn .34s ease both}
@keyframes pageIn{from{opacity:0;transform:translateY(9px) scale(.998)}to{opacity:1;transform:none}}
#appMain.fade-out{animation:pageOut .16s ease both}
@keyframes pageOut{to{opacity:0;transform:translateY(-5px)}}

/* Cards */
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:20px}
@media(min-width:768px){.card{padding:28px}}

/* Hero */
.hero-page{margin-bottom:28px;padding:26px 20px;border-radius:26px;color:#fff;background:linear-gradient(135deg,#0f766e 0%,#14b8a6 55%,#0ea5e9 100%);box-shadow:var(--shadow-lg);position:relative;overflow:hidden}
.hero-page:before{content:"";position:absolute;width:260px;height:260px;right:-60px;top:-60px;border-radius:50%;background:rgba(255,255,255,.12)}
.hero-page:after{content:"";position:absolute;width:160px;height:160px;left:-40px;bottom:-60px;border-radius:50%;background:rgba(255,255,255,.09)}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(0,0,0,.16);padding:6px 14px;border-radius:999px;font-size:13px;margin-bottom:16px}
.hero-title{font-size:clamp(22px,4vw,38px);line-height:1.35;font-weight:700;max-width:720px}
.hero-sub{max-width:680px;opacity:.92;margin-top:10px;font-size:clamp(14px,2.4vw,17px)}
.hero-stats{display:grid;grid-template-columns:1fr;gap:12px;margin-top:24px;position:relative;z-index:1}
.stat-box{display:flex;gap:14px;align-items:center;background:rgba(255,255,255,.95);color:var(--text);border-radius:18px;padding:14px 16px;box-shadow:var(--shadow)}
.stat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:#d1fae5;color:var(--primary);font-size:20px;flex:none}
.stat-icon.money{background:#fef3c7;color:#b45309}
.stat-icon.category{background:#e0f2fe;color:#0284c7}
.stat-value{font-weight:700;font-size:18px;line-height:1.3}
.stat-label{color:var(--muted);font-size:13px}
@media(min-width:720px){.hero-stats{grid-template-columns:repeat(3,1fr)}}

/* sections */
.section-block{margin-bottom:30px}
.section-head{display:flex;align-items:center;gap:12px;justify-content:space-between;margin:24px 0 18px}
.section-title{font-size:22px;font-weight:700}
.section-title i{color:var(--primary);margin-right:6px}
.section-desc{color:var(--muted);font-size:14px;margin-top:2px}

/* report cards */
.reports-list{display:grid;gap:18px}
@media(min-width:900px){.reports-list{grid-template-columns:repeat(2,1fr)}}
.report-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px;position:relative;transition:transform .25s,box-shadow .25s;overflow:visible}
.report-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg)}
.report-head{display:flex;gap:14px;align-items:flex-start}
.report-avatar,.gallery-main{width:82px;height:82px;border-radius:50%;overflow:hidden;background:#f1f5f9;flex:none;position:relative;cursor:zoom-in}
.report-avatar{display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:38px;background:linear-gradient(135deg,#d1fae5,#e0f2fe)}
.gallery-main img{width:100%;height:100%;object-fit:cover}
.gallery-expand{position:absolute;right:5px;bottom:5px;width:24px;height:24px;background:rgba(0,0,0,.55);color:#fff;font-size:10px;border-radius:8px;display:flex;align-items:center;justify-content:center;z-index:2}
.report-meta{min-width:0}
.report-name{font-size:17px;font-weight:700;line-height:1.35;overflow-wrap:anywhere}
.report-name i{color:var(--primary);margin-right:5px}
.report-id{color:var(--muted);font-size:13px;font-family:var(--font-en)}
.report-tags{display:flex;flex-wrap:wrap;gap:7px;margin-top:9px}
.badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;background:var(--surface2);border:1px solid var(--line);border-radius:999px;padding:3px 10px;font-weight:600}
.badge-district{color:#0369a1;background:#e0f2fe;border-color:#bae6fd}
.badge-money{color:#b45309;background:#fef3c7;border-color:#fde68a}
.badge-status{color:#166534;background:#dcfce7;border-color:#bbf7d0}
.badge-approved{background:#dcfce7;color:#166534}
.badge-pending{background:#fef3c7;color:#b45309}
.badge-rejected{background:#fee2e2;color:#b91c1c}
.report-details{margin-top:14px;padding-top:14px;border-top:1px solid var(--line);display:grid;gap:6px}
.report-details.collapsed .detail-row:last-child{max-height:42px;overflow:hidden;position:relative}
.report-details.collapsed .detail-row:last-child:after{content:"...";position:absolute;right:0;bottom:0;background:var(--surface);padding:0 5px;font-weight:700}
.detail-row{font-size:14px;color:#334155;overflow-wrap:anywhere}
.detail-row span{font-weight:700;color:var(--text)}
.read-more-btn{background:none;border:0;color:var(--primary);font-weight:600;font-size:13px;margin-top:8px;display:inline-flex;gap:5px;align-items:center}
.read-more-btn.open i{transform:rotate(180deg)}
.report-foot{display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:12px;border-top:1px solid var(--line)}
.report-date{color:var(--muted);font-size:13px}
.share-btn{border:1px solid var(--line);background:var(--surface2);border-radius:11px;padding:7px 12px;font-size:13px;font-weight:600;color:var(--primary)}
.share-popup{position:absolute;right:16px;top:58px;z-index:30;background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow-lg);padding:11px;width:min(250px,88%)}
.share-popup[hidden]{display:none}
.share-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.share-grid a,.share-copy{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;padding:9px 10px;border-radius:10px;background:var(--surface2);border:1px solid var(--line);color:var(--text);justify-content:flex-start}
.share-facebook,.share-facebook span{color:#1877f2}
.share-whatsapp,.share-whatsapp span{color:#25d366}
.share-twitter,.share-twitter span{color:#000}
.share-copy{grid-column:1/-1;color:var(--primary)}
.gallery-thumbs{display:flex;flex-wrap:wrap;}
.report-gallery .gallery-thumb{width:38px;height:38px;border:2px solid var(--surface);border-radius:11px;overflow:hidden;cursor:pointer;object-fit:cover}
.report-gallery .gallery-thumb img{width:100%;height:100%;object-fit:cover}
.report-gallery .gallery-thumb{display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow);flex:none}
.report-gallery{flex:none;display:flex;flex-direction:column;gap:7px;align-items:center;width:82px}
.report-gallery .gallery-thumb.active{border-color:var(--primary)}
@media(min-width:768px){.report-gallery{width:86px}.gallery-thumbs{flex-direction:column;gap:6px}}
/* detail */
.detail-page{max-width:920px;margin:0 auto}
.detail-topbar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:18px}
.detail-title{font-weight:700;font-size:18px}
.detail-footer{display:block;text-align:center;margin:20px auto 0;max-width:260px}
/* forms */
.form-page,.search-page,.leaderboard-page,.donation-page,.static-page,.notfound-page{max-width:980px;margin:0 auto}
.form-hero,.search-hero,.donation-header{text-align:center;margin-bottom:22px}
.form-badge,.donation-badge{display:inline-flex;align-items:center;gap:8px;color:var(--primary);background:#ccfbf1;border:1px solid #99f6e4;border-radius:999px;padding:6px 14px;font-weight:600;font-size:13px}
.form-title{font-size:clamp(21px,4vw,30px);margin-top:10px;font-weight:700}
.form-title i{color:var(--primary)}
.form-sub{color:var(--muted);margin-top:8px;font-size:15px;max-width:720px;margin-left:auto;margin-right:auto}
.form-grid{display:grid;gap:14px}
.form-grid.two{grid-template-columns:1fr}
@media(min-width:700px){.form-grid.two{grid-template-columns:1fr 1fr}.form-grid .ctwo{grid-column:span 2}}
.form-group{margin-bottom:14px}
.form-group label{display:flex;align-items:center;gap:6px;font-weight:600;font-size:14px;margin-bottom:7px}
.form-group label small{color:var(--muted);font-weight:400;font-size:12px}
.req{color:var(--danger)}
input,select,textarea{width:100%;border:1.5px solid var(--line);background:var(--surface);border-radius:13px;padding:11px 13px;font-size:15px;transition:.2s;outline:0}
input:focus,select:focus,textarea:focus{border-color:var(--primary2);box-shadow:0 0 0 4px rgba(20,184,166,.12)}
textarea{resize:vertical;min-height:74px}
.hint{color:var(--muted);font-size:12px}
.upload-zone{border:2px dashed #cbd5e1;border-radius:16px;background:var(--surface2);padding:22px;text-align:center;position:relative;transition:.2s}
.upload-zone:hover{border-color:var(--primary2);background:#f0fdfa}
.upload-zone input{position:absolute;inset:0;opacity:0;cursor:pointer;z-index:3}
.upload-empty{pointer-events:none}
.upload-empty i{font-size:34px;color:var(--primary)}
.upload-empty p{margin-top:6px;font-weight:600}
.upload-empty small{color:var(--muted)}
.upload-preview{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.upload-preview .up-thumb{width:66px;height:66px;border-radius:12px;overflow:hidden;position:relative;border:2px solid var(--line)}
.upload-preview .up-thumb img{width:100%;height:100%;object-fit:cover}
.upload-preview .up-remove{position:absolute;top:-4px;right:-4px;width:20px;height:20px;border:0;border-radius:50%;background:var(--danger);color:#fff;font-size:10px;z-index:4}
.declaration-box{margin:16px 0 18px;padding:15px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:14px}
.check-line{display:flex;gap:10px;align-items:flex-start;cursor:pointer;position:relative;padding-left:31px;font-size:14px;line-height:1.6}
.check-line input{position:absolute;opacity:0;width:22px;height:22px;left:0;top:1px;cursor:pointer}
.checkmark{position:absolute;left:0;top:3px;width:20px;height:20px;border:2px solid #94a3b8;border-radius:6px;background:#fff}
.check-line input:checked + .checkmark{background:var(--primary);border-color:var(--primary)}
.check-line input:checked + .checkmark:after{content:"\f00c";font-family:"Font Awesome 6 Free";font-weight:900;color:#fff;font-size:11px;position:absolute;left:4px;top:0}
.form-action-buttons{display:grid;gap:10px}
@media(min-width:760px){.form-action-buttons{grid-template-columns:1fr 1fr 1fr}}
.btn{border:0;border-radius:12px;padding:11px 16px;font-weight:600;font-size:14px;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:.2s;font-family:inherit}
.btn:hover{transform:translateY(-1px)}
.btn:active{transform:translateY(0)}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;box-shadow:0 8px 18px -8px rgba(15,118,110,.6)}
.btn-ghost{background:var(--surface2);border:1px solid var(--line);color:var(--text)}
.btn-soft{background:#ecfdf5;color:var(--primary)}
.btn-danger{background:#fee2e2;color:var(--danger)}
.btn-lg{padding:13px 22px;font-size:16px}
.btn-sm{padding:7px 11px;font-size:12px;border-radius:10px}
/* search */
.search-options{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:15px}
.chip{background:var(--surface);border:1px solid var(--line);border-radius:999px;padding:5px 12px;font-size:12.5px;color:var(--muted)}
.search-actions{display:flex;gap:10px;justify-content:center;margin-top:16px}
#searchResults{margin-top:24px}
.results-empty,.empty-state{text-align:center;padding:40px 20px;color:var(--muted)}
.results-empty i,.empty-state i{font-size:52px;color:#cbd5e1;margin-bottom:10px}
.results-empty h3,.empty-state h3{color:var(--text);margin-bottom:6px}
/* leaderboard */
.leaderboard-top{display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:18px}
@media(min-width:760px){.leaderboard-top{grid-template-columns:repeat(3,1fr)}}
.lb-medal{background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:18px;text-align:center;box-shadow:var(--shadow);position:relative}
.lb-medal::before{position:absolute;right:12px;top:10px;font-weight:800;font-size:23px}
.lb-medal.first::before{content:"🥇";color:#f59e0b}
.lb-medal.second::before{content:"🥈";color:#94a3b8}
.lb-medal.third::before{content:"🥉";color:#b45309}
.lb-medal i{font-size:34px;color:var(--primary);margin-bottom:6px}
.lb-medal .rank{font-weight:800;font-size:19px;display:block}
.lb-medal .lb-name{display:block;font-weight:600}
.lb-medal .lb-cat{color:var(--muted);font-size:12.5px}
.leaderboard-list .leaderboard-row{display:flex;align-items:center;gap:11px;padding:12px;border-bottom:1px solid var(--line)}
.leaderboard-row:last-child{border-bottom:0}
.lb-index{width:32px;height:32px;border-radius:10px;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--muted);flex:none}
.top-three .lb-index{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff}
.lb-icon{width:38px;height:38px;border-radius:11px;color:#fff;display:flex;align-items:center;justify-content:center;flex:none}
.lb-main{min-width:0;flex:1}
.lb-user{font-weight:600;font-size:13.5px}
.lb-id{color:var(--muted);font-size:11px;font-family:var(--font-en)}
.lb-cat2{color:var(--muted);font-size:12px}
.lb-date{font-size:12px;color:var(--muted);white-space:nowrap}
.lb-view{width:32px;height:32px;border-radius:10px;background:var(--surface2);display:flex;align-items:center;justify-content:center;color:var(--primary)}
/* donation */
.donation-grid{display:grid;gap:20px}
@media(min-width:900px){.donation-grid{grid-template-columns:.9fr 1.1fr}}
.donation-reasons h2,.payment-section h2,.payment-methods h2{padding-bottom:12px;margin-bottom:16px;border-bottom:1px solid var(--line)}
.reason-item{display:flex;gap:12px;margin-bottom:16px}
.reason-item i{color:var(--primary);font-size:24px;flex:none;margin-top:3px}
.reason-item strong{display:block}
.reason-item p{color:var(--muted);font-size:13.5px}
.pay-list{display:grid;gap:10px}
.pay-card{display:flex;align-items:center;gap:13px;border:1px solid var(--line);border-radius:14px;padding:11px 13px;background:var(--surface2);transition:.2s}
.pay-card:hover{border-color:var(--primary)}
.pay-logo{width:46px;height:46px;border-radius:13px;background:var(--brand,#10b981);color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;overflow:hidden;flex:none}
.pay-logo img{width:100%;height:100%;object-fit:cover}
.pay-info{min-width:0;flex:1}
.pay-info h3{font-size:15px}
.pay-address{color:var(--muted);font-size:12.5px;font-family:var(--font-en);word-break:break-all}
.selected-payment{display:none;margin:0 0 14px;padding:12px;border-radius:13px;background:#ecfdf5;border:1px solid #a7f3d0;font-size:14px;font-weight:600}
.selected-payment.show{display:block}
/* static/404 */
.static-card{background:var(--surface);border-radius:20px;box-shadow:var(--shadow);padding:30px;border:1px solid var(--line);max-width:780px;margin:30px auto}
.static-card h1{font-size:24px;margin-bottom:14px}
.static-card p,.static-card li{color:#334155;margin-bottom:10px}
.notfound-page{text-align:center;padding-top:42px}
.nf-code{font-size:clamp(64px,18vw,140px);font-weight:800;line-height:1;color:var(--primary);opacity:.12}
.nf-icon{font-size:56px;color:var(--primary);margin-top:-48px}
.notfound-page h1{font-size:clamp(23px,5vw,32px);margin:10px 0}
.notfound-page p{color:var(--muted)}
.nf-actions{display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap}
/* pagination */
.pagination{display:flex;gap:7px;justify-content:center;margin-top:24px;flex-wrap:wrap}
.pagination a{min-width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:var(--surface);border:1px solid var(--line);font-weight:600}
.pagination a:hover,.pagination a.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.empty-inline{padding:14px;color:var(--muted)}
.text-center{text-align:center}
/* bottom nav */
.bottom-nav{position:fixed;left:0;right:0;bottom:0;height:66px;display:grid;grid-template-columns:repeat(5,1fr);background:rgba(255,255,255,.94);border-top:1px solid var(--line);backdrop-filter:blur(14px);z-index:880;padding-bottom:env(safe-area-inset-bottom);box-shadow:0 -8px 24px rgba(15,23,42,.08)}
.bottom-link{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:var(--muted);font-size:11.5px;font-weight:600}
.bottom-link i{font-size:18px}
.bottom-link.active{color:var(--primary)}
.bottom-link.active i{transform:translateY(-1px)}
@media(min-width:768px){.bottom-nav{display:none}body{padding-bottom:0}}
/* toast */
#toastBox{position:fixed;right:16px;left:16px;top:80px;z-index:11000;display:grid;gap:10px;pointer-events:none}
@media(min-width:600px){#toastBox{left:auto;width:380px}}
.toast{background:var(--surface);border:1px solid var(--line);box-shadow:var(--shadow-lg);border-radius:14px;padding:13px 15px;display:flex;gap:11px;align-items:center;animation:toastIn .25s ease both;pointer-events:auto}
.toast.error{border-color:#fecaca;background:#fff7f7}
.toast.success{border-color:#bbf7d0;background:#f7fef9}
.toast i{font-size:18px}
.toast.error i{color:var(--danger)}
.toast.success i{color:var(--success)}
.toast-close{margin-left:auto;border:0;background:none;color:var(--muted)}
@keyframes toastIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
/* lightbox */
#lightbox{position:fixed;inset:0;background:rgba(2,6,23,.9);z-index:12000;display:none;align-items:center;justify-content:center;padding:20px}
#lightbox.show{display:flex}
#lightbox .lb-body{max-width:900px;width:100%;text-align:center}
#lightbox img{max-height:82vh;margin:auto;border-radius:14px;box-shadow:var(--shadow-lg)}
#lightbox .lb-controls{display:flex;gap:12px;justify-content:center;margin-top:14px}
.lb-btn{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:9px 16px;font-weight:600}
.lb-nav{position:absolute;top:50%;transform:translateY(-50%);width:42px;height:42px;border:0;border-radius:50%;background:rgba(255,255,255,.1);color:#fff}
.lb-prev{left:12px}.lb-next{right:12px}
.lb-close{position:absolute;top:16px;right:16px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);border:0;color:#fff}
/* admin */
.admin-login-page{min-height:80vh;display:flex;align-items:center;justify-content:center}
.login-card{width:min(440px,100%);background:var(--surface);border:1px solid var(--line);border-radius:22px;box-shadow:var(--shadow-lg);padding:28px;text-align:center}
.login-logo img{width:72px;height:72px;object-fit:cover;border-radius:18px;margin:0 auto 12px;background:var(--surface2)}
.login-card h1{font-size:24px}
.login-card p{color:var(--muted);font-size:13px;margin:8px 0 18px}
.login-card code{background:var(--surface2);padding:2px 6px;border-radius:6px}
.back-link{display:inline-flex;gap:6px;margin-top:14px;color:var(--primary);font-weight:600}
.admin-content{max-width:1180px;margin:0 auto}
.admin-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.admin-heading h1{font-size:24px}
.admin-heading p{color:var(--muted)}
.stat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:20px}
@media(min-width:760px){.stat-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:1100px){.stat-grid{grid-template-columns:repeat(5,1fr)}}
.stat-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:16px;display:flex;gap:12px;align-items:center;box-shadow:var(--shadow)}
.stat-card i{width:44px;height:44px;border-radius:13px;background:#ccfbf1;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:20px;flex:none}
.stat-card.pending i{background:#fef3c7;color:#b45309}.stat-card.approved i{background:#dcfce7;color:#166534}.stat-card.money i{background:#e0f2fe;color:#0369a1}.stat-card.visitors i{background:#ede9fe;color:#6d28d9}
.stat-card strong{display:block;font-size:19px}
.stat-card span{color:var(--muted);font-size:12px}
.admin-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:18px;margin-bottom:20px}
.admin-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
.admin-card h2{font-size:17px;margin-bottom:12px}
.admin-grid{display:grid;grid-template-columns:1fr;gap:20px}
@media(min-width:1000px){.admin-grid{grid-template-columns:1.5fr 1fr}}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:10px 8px;text-align:left;border-bottom:1px solid var(--line);white-space:nowrap}
th{color:var(--muted);font-size:12px}
td{max-width:240px;overflow:hidden;text-overflow:ellipsis}
.pay-thumb{width:38px;height:38px;object-fit:cover;border-radius:9px}
.admin-thumb{width:46px;height:46px;object-fit:cover;border-radius:9px;vertical-align:middle}
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.filter-bar input{max-width:260px}
.filter-bar select{max-width:180px}
.inline-upload{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.inline-upload input[name=title]{max-width:220px}
.inline-upload input[type=file]{max-width:300px}
.upload-line{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.upload-line>span{width:140px;font-weight:600;font-size:14px}
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px}
.gallery-item,.gallery-thumb{position:relative;border-radius:12px;overflow:hidden;background:var(--surface2);border:1px solid var(--line)}
.gallery-item img,.gallery-thumb img{width:100%;height:100px;object-fit:cover}
.g-item-actions{position:absolute;inset:auto 0 0 0;background:linear-gradient(transparent,rgba(0,0,0,.65));padding:8px;display:flex;gap:6px;justify-content:flex-end}
.g-item-actions .btn{background:#fff}
.pm-show img{width:34px;height:34px;border-radius:8px;object-fit:cover}
.narrow{max-width:480px}
@media(max-width:700px){.admin-sidebar .side-close{display:flex}}
</style>
</head>
<body class="' . ($admin && is_admin_logged_in() ? 'admin-body':'public-body') . '">
<div id="topProgress"><div class="bar" id="topProgressBar"></div></div>
<div id="appLoader"><div class="loader-spinner"></div></div>
<div id="toastBox"></div>
<div id="lightbox" role="dialog" aria-modal="true">
  <button class="lb-close" data-lb-close><i class="fa-solid fa-xmark"></i></button>
  <button class="lb-nav lb-prev" data-lb-prev><i class="fa-solid fa-chevron-left"></i></button>
  <div class="lb-body"><img id="lightboxImg" alt="ছবি"><div class="lb-controls"><button class="lb-btn" data-lb-prev><i class="fa-solid fa-chevron-left"></i></button><button class="lb-btn" data-lb-close>বন্ধ করুন</button><button class="lb-btn" data-lb-next><i class="fa-solid fa-chevron-right"></i></button></div></div>
  <button class="lb-nav lb-next" data-lb-next><i class="fa-solid fa-chevron-right"></i></button>
</div>

<header class="site-header">
  <div class="header-in">
    <a class="brand" href="' . e(url('')) . '" data-link>
      <img src="' . e($logoSrc) . '" alt="' . e($siteName) . '">
      <div class="brand-text"><div class="brand-title">' . e($siteName) . '</div><div class="brand-sub">' . e($subtitle) . '</div></div>
    </a>
    <div class="header-right">' . $headerExtra . '
      <button type="button" class="menu-trigger" id="menuTrigger" aria-label="মেনু খুলুন"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>
' . $navbar . '
<main id="appMain"><div id="pageContent" class="page-holder">' . $content . '</div></main>

<footer class="site-footer" style="border-top:1px solid var(--line);background:var(--surface);padding:34px 16px 26px;margin-top:20px">
  <div class="footer-grid" style="max-width:1200px;margin:0 auto;display:grid;gap:22px;grid-template-columns:1fr">
    <div>
      <div class="footer-brand" style="display:flex;gap:10px;align-items:center;margin-bottom:10px"><img src="' . e($logoSrc) . '" style="width:48px;height:48px;border-radius:13px" alt=""><div><strong style="display:block">' . e($siteName) . '</strong><small style="color:var(--muted)">' . e($subtitle) . '</small></div></div>
      <p style="color:var(--muted);font-size:14px">' . e($footer) . '</p>
      <p style="color:var(--primary);font-weight:700;margin-top:10px">' . e($slogan) . '</p>
    </div>
    <div style="color:var(--muted)">
      <h3 style="color:var(--text);font-size:17px;margin-bottom:12px"><i class="fa-solid fa-envelope"></i> যোগাযোগ করুন</h3>
      <p><i class="fa-solid fa-at"></i> Mail: ' . e($contactEmail) . '</p>
      <p><i class="fa-solid fa-location-dot"></i> Location: ' . e($location) . '</p>
      <p><i class="fa-solid fa-phone"></i> Phone: ' . e(setting('contact_phone') ?: '—') . '</p>
    </div>
    <div>
      <h3 style="color:var(--text);font-size:17px;margin-bottom:12px"><i class="fa-solid fa-link"></i> দ্রুত লিংক</h3>
      <div style="display:grid;gap:7px"><a href="'.e(url('')).'" data-link>হোম</a><a href="'.e(url('report')).'" data-link>রিপোর্ট জমা দিন</a><a href="'.e(url('search')).'" data-link>রিপোর্ট খুঁজুন</a><a href="'.e(url('leaderboard')).'" data-link>লিডার বোর্ড</a><a href="'.e(url('donation')).'" data-link>অনুদান</a><a href="'.e(url('privacy')).'" data-link>গোপনীয়তা নীতি</a></div>
    </div>
  </div>
  <div style="max-width:1200px;margin:24px auto 0;padding-top:16px;border-top:1px solid var(--line);text-align:center;color:var(--muted);font-size:13.5px">© ' . $year . ' ' . e($siteName) . '। সর্বস্বত্ব সংরক্ষিত।</div>
</footer>

<nav class="bottom-nav">' . ($admin && is_admin_logged_in() ? ($adminBottom ?? '') : $bottomNavHtml) . '</nav>

<script>
window.OVJOG_APP={base:"",isAdmin:' . ($admin ? 'true':'false') . '};
</script>
<script src="' . e(asset_url('js/app.js')) . '" defer></script>
</body>
</html>';
    }
}

if (!function_exists('current_page_active')) {
    function current_page_active(string $current, string $target): bool
    {
        $target = trim($target, '/');
        $current = trim($current, '/');
        if ($target === '' ) return $current === '' || $current === 'home';
        return $current === $target;
    }
}
