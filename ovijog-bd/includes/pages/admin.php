<?php
/**
 * চাকরি সার্কুলার — অ্যাডমিন প্যানেল
 */

if (!function_exists('admin_stats')) {
    function admin_stats(): array {
        return [
            'jobs_total'=>db_count('jobs'),
            'jobs_published'=>db_count('jobs','status="published"'),
            'jobs_draft'=>db_count('jobs','status="draft"'),
            'jobs_expired'=>db_count('jobs','status="expired"'),
            'categories'=>db_count('categories'),
            'banners'=>db_count('banners'),
            'notices'=>db_count('notices'),
            'wrong_pending'=>db_count('wrong_reports','status="pending"'),
            'visitors_today'=>db_count('analytics','visit_date=CURDATE()'),
            'visitors_total'=>db_count('analytics'),
            'search_total'=>db_count('search_logs'),
        ];
    }
}
if (!function_exists('admin_login_page')) {
    function admin_login_page(): string {
        return '<section style="min-height:80vh;display:flex;align-items:center;justify-content:center"><div class="card" style="width:min(420px,100%);text-align:center"><div style="width:64px;height:64px;margin:0 auto 12px;background:linear-gradient(135deg,var(--primary),var(--primary2));border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px"><i class="fa-solid fa-lock"></i></div><h1 style="font-size:22px">অ্যাডমিন লগইন</h1><p style="color:var(--muted);font-size:13px;margin:8px 0 16px">ডিফল্ট: <code>admin</code> / <code>Admin@123</code></p><form id="adminLoginForm" data-ajax-form data-url="'.e(url('admin/login')).'"><div class="form-group" style="text-align:left"><label>ইউজারনেম</label><input name="username" required></div><div class="form-group" style="text-align:left"><label>পাসওয়ার্ড</label><input type="password" name="password" required></div>'.csrf_field().'<button class="btn btn-primary btn-lg" style="width:100%"><i class="fa-solid fa-right-to-bracket"></i> লগইন</button></form><a href="'.e(url('')).'" data-link style="display:inline-flex;gap:6px;margin-top:14px;color:var(--primary);font-weight:600"><i class="fa-solid fa-house"></i> সাইটে ফিরে যান</a></div></section>';
    }
}
if (!function_exists('admin_page')) {
    function admin_page(string $page, $id=null): string {
        if($page==='login') return admin_login_page();
        if(!is_admin_logged_in()){ header('Location: '.url('admin/login')); exit; }

        switch($page){
            case 'dashboard': return admin_dashboard();
            case 'jobs': return admin_jobs();
            case 'job':
                if($id==='new') return admin_job_form();
                if(is_numeric($id)) return admin_job_form((int)$id);
                return admin_jobs();
            case 'categories': return admin_categories();
            case 'banners': return admin_banners();
            case 'notices': return admin_notices();
            case 'reports': return admin_wrong_reports();
            case 'analytics': return admin_analytics();
            case 'users': return admin_users();
            case 'settings': return admin_settings();
            case 'gallery': return admin_gallery();
            case 'change-password': return admin_change_password_page();
            case 'logout': admin_logout(); header('Location: '.url('admin/login')); exit;
            default: return admin_dashboard();
        }
    }
}
if (!function_exists('admin_dashboard')) {
    function admin_dashboard(): string {
        $s=admin_stats();
        $recent=db_all('SELECT j.*, c.name AS cat_name FROM jobs j LEFT JOIN categories c ON c.id=j.category_id ORDER BY j.id DESC LIMIT 8');
        $rows='';
        foreach($recent as $r){ $rows.='<tr><td>'.e(mb_strimwidth($r['title'],0,40,'...')).'</td><td>'.e($r['cat_name']??'—').'</td><td><span class="badge '.$r['status'].'">'.e($r['status']).'</span></td><td>'.bn_number($r['views']).'</td><td>'.e(time_ago($r['created_at'])).'</td><td><a href="'.e(url('admin/job/'.$r['id'])).'" data-link class="btn btn-soft btn-sm"><i class="fa-solid fa-pen"></i></a></td></tr>'; }
        return '<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-gauge-high"></i> ড্যাশবোর্ড</h1><a href="'.e(url('admin/job/new')).'" data-link class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> নতুন চাকরি</a></div>
            <div class="stat-grid">
                <div class="stat-card"><i class="fa-solid fa-briefcase"></i><div><strong>'.bn_number($s['jobs_total']).'</strong><span>মোট চাকরি</span></div></div>
                <div class="stat-card"><i class="fa-solid fa-circle-check" style="background:#dcfce7;color:#166534"></i><div><strong>'.bn_number($s['jobs_published']).'</strong><span>প্রকাশিত</span></div></div>
                <div class="stat-card"><i class="fa-solid fa-hourglass-half" style="background:#fef3c7;color:#b45309"></i><div><strong>'.bn_number($s['jobs_draft']).'</strong><span>ড্রাফট</span></div></div>
                <div class="stat-card"><i class="fa-solid fa-users" style="background:#ede9fe;color:#6d28d9"></i><div><strong>'.bn_number($s['visitors_today']).'</strong><span>আজকের ভিজিটর</span></div></div>
                <div class="stat-card"><i class="fa-solid fa-flag" style="background:#fee2e2;color:#b91c1c"></i><div><strong>'.bn_number($s['wrong_pending']).'</strong><span>ভুল রিপোর্ট</span></div></div>
            </div>
            <div class="admin-card"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><h2><i class="fa-solid fa-clock-rotate-left"></i> সাম্প্রতিক চাকরি</h2><a href="'.e(url('admin/jobs')).'" data-link class="btn btn-soft btn-sm">সব দেখুন</a></div><div class="table-wrap"><table><thead><tr><th>শিরোনাম</th><th>ক্যাটাগরি</th><th>স্ট্যাটাস</th><th>ভিউ</th><th>সময়</th><th></th></tr></thead><tbody>'.($rows?:'<tr><td colspan="6">কোনো চাকরি নেই</td></tr>').'</tbody></table></div></div>
        </div>';
    }
}
if (!function_exists('admin_jobs')) {
    function admin_jobs(): string {
        $q=trim($_GET['q']??''); $cat=(int)($_GET['cat']??0); $status=$_GET['status']??'';
        $where='1=1'; $params=[];
        if($q!==''){ $where.=' AND (j.title LIKE ? OR j.slug LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
        if($cat){ $where.=' AND j.category_id=?'; $params[]=$cat; }
        if(in_array($status,['published','draft','expired','archived'],true)){ $where.=' AND j.status=?'; $params[]=$status; }
        $perPage=20; $current=max(1,(int)($_GET['page']??1));
        $total=(int)db_value("SELECT COUNT(*) FROM jobs j WHERE $where",$params,0);
        $pg=paginate($total,$perPage,$current);
        $rows=db_all("SELECT j.*, c.name AS cat_name FROM jobs j LEFT JOIN categories c ON c.id=j.category_id WHERE $where ORDER BY j.id DESC LIMIT $perPage OFFSET {$pg['offset']}",$params);

        $catOptions='<option value="0">সব ক্যাটাগরি</option>'; foreach(get_categories() as $c){ $catOptions.='<option value="'.$c['id'].'"'.($cat==$c['id']?' selected':'').'>'.e($c['name']).'</option>'; }

        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-briefcase"></i> চাকরি সমূহ</h1><div style="display:flex;gap:8px"><a href="'.e(url('admin/job/new')).'" data-link class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> নতুন</a><button class="btn btn-danger btn-sm" id="bulkDeleteJobs"><i class="fa-solid fa-trash"></i> মুছুন</button></div></div>';
        $html.='<form class="filter-bar" method="get" action="'.e(url('admin/jobs')).'"><input type="text" name="q" placeholder="শিরোনাম খুঁজুন" value="'.e($q).'"><select name="cat">'.$catOptions.'</select><select name="status"><option value="">সব স্ট্যাটাস</option><option value="published"'.($status==='published'?' selected':'').'>প্রকাশিত</option><option value="draft"'.($status==='draft'?' selected':'').'>ড্রাফট</option><option value="expired"'.($status==='expired'?' selected':'').'>মেয়াদ শেষ</option></select><button class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> ফিল্টার</button></form>';
        $html.='<div class="admin-card"><div class="table-wrap"><table><thead><tr><th><input type="checkbox" id="checkAll"></th><th>শিরোনাম</th><th>ক্যাটাগরি</th><th>জেলা</th><th>ডেডলাইন</th><th>স্ট্যাটাস</th><th>ভিউ</th><th></th></tr></thead><tbody>';
        foreach($rows as $r){
            $html.='<tr><td><input type="checkbox" class="chk" value="'.$r['id'].'"></td><td>'.e(mb_strimwidth($r['title'],0,45,'...')).'</td><td>'.e($r['cat_name']??'—').'</td><td>'.e($r['district']?:'—').'</td><td>'.e($r['deadline']?format_date_bn($r['deadline']):'—').'</td><td><span class="badge '.$r['status'].'">'.e($r['status']).'</span></td><td>'.bn_number($r['views']).'</td><td><a href="'.e(url('admin/job/'.$r['id'])).'" data-link class="btn btn-soft btn-sm"><i class="fa-solid fa-pen"></i></a> <a href="'.e(url('job/'.$r['slug'])).'" target="_blank" class="btn btn-ghost btn-sm"><i class="fa-solid fa-eye"></i></a></td></tr>';
        }
        $html.='</tbody></table></div>'.paginate_links($pg['pages'],$pg['current'],'admin/jobs?q='.urlencode($q).'&cat='.$cat.'&status='.$status).'</div></div>';
        $html.='<script>document.addEventListener("DOMContentLoaded",function(){var ca=document.getElementById("checkAll"); if(ca) ca.addEventListener("change",function(){document.querySelectorAll(".chk").forEach(function(c){c.checked=ca.checked})}); var bd=document.getElementById("bulkDeleteJobs"); if(bd) bd.addEventListener("click",function(){var ids=[]; document.querySelectorAll(".chk:checked").forEach(function(c){ids.push(c.value)}); if(!ids.length){ alert("কোনো চাকরি সিলেক্ট করুন"); return;} if(!confirm("নির্বাচিত "+ids.length+" টি চাকরি মুছতে চান?")) return; fetch("'.e(url('admin/jobs')).'",{method:"POST",headers:{"X-Requested-With":"XMLHttpRequest","Content-Type":"application/x-www-form-urlencoded"},body:"action=bulk_delete&ids="+encodeURIComponent(ids.join(","))+"&csrf_token='.e(csrf_token()).'"}).then(r=>r.json()).then(j=>{ if(j.ok){ location.reload(); } else { alert(j.message||"ব্যর্থ"); } })})})</script>';
        return $html;
    }
}
if (!function_exists('admin_job_form')) {
    function admin_job_form(int $id=0): string {
        $job=$id?db_fetch('SELECT * FROM jobs WHERE id=? LIMIT 1',[$id]):null;
        $isEdit=(bool)$job;
        $cats=get_categories();
        $catOptions=''; foreach($cats as $c){ $sel=($job&&$job['category_id']==$c['id'])?' selected':''; $catOptions.='<option value="'.$c['id'].'"'.$sel.'>'.e($c['name']).'</option>'; }
        $distOptions=''; try{ foreach(db_all('SELECT name_bn FROM districts WHERE type="district" ORDER BY name_bn ASC') as $d){ $sel=($job&&$job['district']===$d['name_bn'])?' selected':''; $distOptions.='<option value="'.e($d['name_bn']).'"'.$sel.'>'.e($d['name_bn']).'</option>'; } }catch(Throwable $e){}

        $title=$job['title']??''; $slug=$job['slug']??''; $short=$job['short_description']??''; $desc=$job['description']??''; $district=$job['district']??''; $locText=$job['location_text']??''; $deadline=$job['deadline']??''; $appLink=$job['application_link']??''; $applyText=$job['apply_button_text']??'আবেদন করুন'; $status=$job['status']??'published'; $seoTitle=$job['seo_title']??''; $seoDesc=$job['seo_description']??''; $seoKeys=$job['seo_keywords']??''; $extraLinks=$job['extra_links']??'[]';
        $images=get_job_images($job??[]);

        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-'.($isEdit?'pen':'plus').'"></i> '.($isEdit?'চাকরি এডিট':'নতুন চাকরি').'</h1><a href="'.e(url('admin/jobs')).'" data-link class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> ফিরে যান</a></div>';
        $html.='<form class="admin-card" data-ajax-form data-url="'.e(url('admin/job/'.($isEdit?$id:'new'))).'" enctype="multipart/form-data" method="post">'.csrf_field().'
            <div style="display:grid;grid-template-columns:1fr;gap:16px">
            <div class="form-group"><label>শিরোনাম <span class="req">*</span></label><input name="title" value="'.e($title).'" required placeholder="যেমন: বাংলাদেশ সেনাবাহিনীতে সৈনিক পদে নিয়োগ ২০২৬"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>Slug (SEO URL)</label><input name="slug" value="'.e($slug).'" placeholder="auto generate if empty"></div><div class="form-group"><label>ক্যাটাগরি <span class="req">*</span></label><select name="category_id" required><option value="">সিলেক্ট করুন</option>'.$catOptions.'</select></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>জেলা</label><select name="district"><option value="">সিলেক্ট করুন</option>'.$distOptions.'</select></div><div class="form-group"><label>লোকেশন টেক্সট</label><input name="location_text" value="'.e($locText).'" placeholder="যেমন: ঢাকা, সারা বাংলাদেশ"></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>ডেডলাইন (আবেদনের শেষ তারিখ)</label><input type="datetime-local" name="deadline" value="'.($deadline?e(date('Y-m-d\TH:i',strtotime($deadline))):'').'"></div><div class="form-group"><label>স্ট্যাটাস</label><select name="status"><option value="published"'.($status==='published'?' selected':'').'>প্রকাশিত</option><option value="draft"'.($status==='draft'?' selected':'').'>ড্রাফট</option><option value="expired"'.($status==='expired'?' selected':'').'>মেয়াদ শেষ</option><option value="archived"'.($status==='archived'?' selected':'').'>আর্কাইভ</option></select></div></div>
            <div class="form-group"><label>সংক্ষিপ্ত বিবরণ</label><textarea name="short_description" rows="2" placeholder="১-২ লাইনের সংক্ষিপ্ত বিবরণ">'.e($short).'</textarea></div>
            <div class="form-group"><label>বিস্তারিত বিবরণ (HTML সমর্থিত) <span class="req">*</span></label><textarea name="description" rows="12" required placeholder="বিস্তারিত লিখুন, HTML ব্যবহার করতে পারেন">'.e($desc).'</textarea><small style="color:var(--muted)">বাংলা লিখলে অটো সুন্দর ডিজাইন হবে। HTML, লিস্ট, লিংক ব্যবহার করতে পারেন।</small></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>প্রধান ছবি</label><input type="file" name="image" accept="image/*"><small>সাইজ অটো কমপ্রেস: 5mb=450kb</small>'.($images?'<div style="margin-top:8px"><img src="'.e(uploads_url($images[0])).'" class="admin-thumb"></div>':'').'</div><div class="form-group"><label>অতিরিক্ত ছবি (১-৫টি ব্যানার টাইপ)</label><input type="file" name="images[]" accept="image/*" multiple><small>একাধিক ছবি সিলেক্ট করুন</small></div></div>
            <div class="form-group"><label>PDF ফাইল (ঐচ্ছিক)</label><input type="file" name="pdf_file" accept="application/pdf"><small>সাইজ অটো কমপ্রেস হবে</small>'.(!empty($job['pdf_file'])?'<div style="margin-top:6px"><a href="'.e(uploads_url($job['pdf_file'])).'" target="_blank" class="btn btn-soft btn-sm"><i class="fa-solid fa-file-pdf"></i> PDF দেখুন</a></div>':'').'</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>আবেদন লিংক</label><input type="url" name="application_link" value="'.e($appLink).'" placeholder="https://..."></div><div class="form-group"><label>বাটন টেক্সট</label><input name="apply_button_text" value="'.e($applyText).'"></div></div>
            <div class="form-group"><label>অতিরিক্ত লিংক (JSON: [{label,url}])</label><textarea name="extra_links" rows="3" placeholder=\'[{"label":"অফিসিয়াল নোটিশ","url":"https://..."}]\'>'.e($extraLinks).'</textarea></div>
            <div class="card" style="background:var(--surface2)"><h3 style="margin-bottom:10px"><i class="fa-solid fa-magnifying-glass"></i> SEO</h3><div class="form-group"><label>SEO Title</label><input name="seo_title" value="'.e($seoTitle).'" placeholder="Unique SEO title"></div><div class="form-group"><label>SEO Description</label><textarea name="seo_description" rows="2">'.e($seoDesc).'</textarea></div><div class="form-group"><label>SEO Keywords</label><input name="seo_keywords" value="'.e($seoKeys).'"></div></div>
            <button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk"></i> '.($isEdit?'আপডেট করুন':'প্রকাশ করুন').'</button>
            </div></form></div>';
        return $html;
    }
}

if (!function_exists('admin_categories')) {
    function admin_categories(): string {
        $cats=db_all('SELECT * FROM categories ORDER BY sort_order ASC, id ASC');
        $edit=null; if(!empty($_GET['id'])) $edit=db_fetch('SELECT * FROM categories WHERE id=?',[(int)$_GET['id']]);
        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-layer-group"></i> ক্যাটাগরি</h1></div><div style="display:grid;grid-template-columns:1fr;gap:20px"><div class="admin-card"><div class="table-wrap"><table><thead><tr><th>নাম</th><th>Slug</th><th>আইকন</th><th>রং</th><th>অর্ডার</th><th>স্ট্যাটাস</th><th></th></tr></thead><tbody>';
        foreach($cats as $c){ $html.='<tr><td>'.e($c['name']).'</td><td>'.e($c['slug']).'</td><td><i class="'.e($c['icon']).'"></i></td><td><span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:'.e($c['color']).'"></span></td><td>'.bn_number($c['sort_order']).'</td><td><span class="badge '.($c['status']?'published':'draft').'">'.($c['status']?'সক্রিয়':'নিষ্ক্রিয়').'</span></td><td><a href="'.e(url('admin/categories?id='.$c['id'])).'" data-link class="btn btn-soft btn-sm">এডিট</a></td></tr>'; }
        $html.='</tbody></table></div></div>';
        $html.='<div class="admin-card"><h2>'.($edit?'<i class="fa-solid fa-pen"></i> এডিট':'<i class="fa-solid fa-plus"></i> নতুন ক্যাটাগরি').'</h2><form data-ajax-form data-url="'.e(url('admin/categories'.($edit?'/'.$edit['id']:''))).'">'.csrf_field().'
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>নাম <span class="req">*</span></label><input name="name" value="'.e($edit['name']??'').'" required></div><div class="form-group"><label>Slug</label><input name="slug" value="'.e($edit['slug']??'').'" placeholder="auto"></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px"><div class="form-group"><label>আইকন (fa fa)</label><input name="icon" value="'.e($edit['icon']??'fa-solid fa-briefcase').'"></div><div class="form-group"><label>রং</label><input type="color" name="color" value="'.e($edit['color']??'#0f766e').'"></div><div class="form-group"><label>অর্ডার</label><input type="number" name="sort_order" value="'.e($edit['sort_order']??'0').'"></div></div>
            <div class="form-group"><label>স্ট্যাটাস</label><select name="status"><option value="1"'.(($edit['status']??1)==1?' selected':'').'>সক্রিয়</option><option value="0"'.(($edit['status']??1)==0?' selected':'').'>নিষ্ক্রিয়</option></select></div>
            <div class="card" style="background:var(--surface2)"><h4>SEO</h4><div class="form-group"><label>SEO Title</label><input name="seo_title" value="'.e($edit['seo_title']??'').'"></div><div class="form-group"><label>SEO Description</label><textarea name="seo_description" rows="2">'.e($edit['seo_description']??'').'</textarea></div><div class="form-group"><label>SEO Keywords</label><input name="seo_keywords" value="'.e($edit['seo_keywords']??'').'"></div></div>
            <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> সংরক্ষণ</button></form></div></div></div>';
        return $html;
    }
}

if (!function_exists('admin_banners')) {
    function admin_banners(): string {
        $banners=db_all('SELECT * FROM banners ORDER BY sort_order ASC, id DESC');
        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-images"></i> ব্যানার (১-১০)</h1></div><div style="display:grid;grid-template-columns:1fr;gap:20px"><div class="admin-card"><div class="gallery-grid">';
        foreach($banners as $b){ $html.='<div class="gallery-item"><img src="'.e(uploads_url($b['image'])).'" alt=""><div class="g-item-actions"><span class="badge">'.bn_number($b['sort_order']).'</span><a href="'.e(url('admin/banners?delete='.$b['id'])).'" data-link class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></a></div><div style="padding:8px;font-size:12px">'.e($b['title']).'<br><a href="'.e($b['link']).'" target="_blank" style="color:var(--primary)">'.e(mb_strimwidth($b['link'],0,30,'...')).'</a></div></div>'; }
        $html.='</div></div><div class="admin-card"><h2><i class="fa-solid fa-plus"></i> নতুন ব্যানার</h2><form data-ajax-form data-url="'.e(url('admin/banners')).'" enctype="multipart/form-data">'.csrf_field().'<div class="form-group"><label>শিরোনাম</label><input name="title" placeholder="ব্যানার শিরোনাম"></div><div class="form-group"><label>ছবি <span class="req">*</span></label><input type="file" name="image" accept="image/*" required><small>সাইজ অটো ফিক্স, কমপ্রেস: 5mb=450kb</small></div><div class="form-group"><label>লিংক (ঐচ্ছিক)</label><input type="url" name="link" placeholder="https://..."></div><div class="form-group"><label>অর্ডার</label><input type="number" name="sort_order" value="0"></div><button class="btn btn-primary"><i class="fa-solid fa-upload"></i> আপলোড</button></form></div></div></div>';
        return $html;
    }
}

if (!function_exists('admin_notices')) {
    function admin_notices(): string {
        $notices=db_all('SELECT * FROM notices ORDER BY id DESC LIMIT 100');
        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-bell"></i> নোটিশ</h1><button class="btn btn-danger btn-sm" id="bulkDelNotice"><i class="fa-solid fa-trash"></i> মুছুন</button></div><div class="admin-card"><form data-ajax-form data-url="'.e(url('admin/notices')).'">'.csrf_field().'<div class="form-group"><label>শিরোনাম <span class="req">*</span></label><input name="title" required placeholder="যেমন: ২০২৬ সালের HSC পরীক্ষার সময়সূচি প্রকাশিত"></div><div class="form-group"><label>বিস্তারিত</label><textarea name="content" rows="3"></textarea></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>লিংক (ঐচ্ছিক)</label><input type="url" name="link"></div><div class="form-group"><label>গুরুত্বপূর্ণ?</label><select name="is_important"><option value="0">সাধারণ</option><option value="1">গুরুত্বপূর্ণ</option></select></div></div><button class="btn btn-primary"><i class="fa-solid fa-plus"></i> নোটিশ যোগ করুন</button></form></div><div class="admin-card"><div class="table-wrap"><table><thead><tr><th><input type="checkbox" id="checkAllN"></th><th>শিরোনাম</th><th>গুরুত্ব</th><th>সময়</th><th></th></tr></thead><tbody>';
        foreach($notices as $n){ $html.='<tr><td><input type="checkbox" class="chkN" value="'.$n['id'].'"></td><td>'.e(mb_strimwidth($n['title'],0,50,'...')).'</td><td>'.($n['is_important']?'<span class="badge" style="background:#fef3c7;color:#b45309">গুরুত্বপূর্ণ</span>':'<span class="badge">সাধারণ</span>').'</td><td>'.e(time_ago($n['created_at'])).'</td><td><a href="'.e(url('admin/notices?delete='.$n['id'])).'" data-link class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></a></td></tr>'; }
        $html.='</tbody></table></div></div></div><script>document.addEventListener("DOMContentLoaded",function(){var ca=document.getElementById("checkAllN"); if(ca) ca.addEventListener("change",function(){document.querySelectorAll(".chkN").forEach(function(c){c.checked=ca.checked})}); var bd=document.getElementById("bulkDelNotice"); if(bd) bd.addEventListener("click",function(){var ids=[]; document.querySelectorAll(".chkN:checked").forEach(function(c){ids.push(c.value)}); if(!ids.length){ alert("সিলেক্ট করুন"); return;} if(!confirm(ids.length+" টি মুছবেন?")) return; fetch("'.e(url('admin/notices')).'",{method:"POST",headers:{"X-Requested-With":"XMLHttpRequest","Content-Type":"application/x-www-form-urlencoded"},body:"action=bulk_delete&ids="+encodeURIComponent(ids.join(","))+"&csrf_token='.e(csrf_token()).'"}).then(r=>r.json()).then(j=>{ if(j.ok) location.reload(); else alert(j.message)})})})</script>';
        return $html;
    }
}

if (!function_exists('admin_wrong_reports')) {
    function admin_wrong_reports(): string {
        $rows=db_all('SELECT w.*, j.title AS job_title FROM wrong_reports w LEFT JOIN jobs j ON j.id=w.job_id ORDER BY w.id DESC LIMIT 200');
        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-flag"></i> ভুল রিপোর্ট</h1></div><div class="admin-card"><div class="table-wrap"><table><thead><tr><th>চাকরি</th><th>নাম</th><th>ইমেইল</th><th>শিরোনাম</th><th>বিস্তারিত</th><th>স্ট্যাটাস</th><th>সময়</th><th></th></tr></thead><tbody>';
        foreach($rows as $r){ $html.='<tr><td>'.e(mb_strimwidth($r['job_title']??'—',0,30,'...')).'</td><td>'.e($r['name']).'</td><td>'.e($r['email']).'</td><td>'.e($r['title']).'</td><td>'.e(mb_strimwidth($r['details'],0,50,'...')).'</td><td><span class="badge '.($r['status']==='pending'?'draft':'published').'">'.e($r['status']).'</span></td><td>'.e(time_ago($r['created_at'])).'</td><td><a href="'.e(url('admin/reports?resolve='.$r['id'])).'" data-link class="btn btn-soft btn-sm">সমাধান</a></td></tr>'; }
        $html.='</tbody></table></div></div></div>';
        return $html;
    }
}

if (!function_exists('admin_analytics')) {
    function admin_analytics(): string {
        $s=admin_stats();
        $today=db_all('SELECT * FROM analytics WHERE visit_date=CURDATE() ORDER BY id DESC LIMIT 30');
        $byPage=db_all('SELECT page, COUNT(*) total FROM analytics GROUP BY page ORDER BY total DESC LIMIT 20');
        $byDiv=db_all('SELECT division, country, COUNT(*) total FROM analytics GROUP BY division, country ORDER BY total DESC LIMIT 20');
        $pageHtml=''; foreach($byPage as $r) $pageHtml.='<tr><td>'.e($r['page']).'</td><td>'.bn_number($r['total']).'</td></tr>';
        $divHtml=''; foreach($byDiv as $r) $divHtml.='<tr><td>'.e(($r['division']?:$r['country']?:'অজানা')).'</td><td>'.bn_number($r['total']).'</td></tr>';
        $rowHtml=''; foreach($today as $r) $rowHtml.='<tr><td>'.e($r['visitor_id']).'</td><td>'.e($r['device']).'</td><td>'.e($r['browser']).'</td><td>'.e($r['page']).'</td><td>'.e($r['created_at']).'</td></tr>';
        return '<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-chart-pie"></i> অ্যানালিটিকস</h1></div><div class="stat-grid"><div class="stat-card"><i class="fa-solid fa-eye"></i><div><strong>'.bn_number($s['visitors_total']).'</strong><span>মোট ভিজিট</span></div></div><div class="stat-card"><i class="fa-solid fa-user"></i><div><strong>'.bn_number($s['visitors_today']).'</strong><span>আজকের ভিজিটর</span></div></div><div class="stat-card"><i class="fa-solid fa-briefcase"></i><div><strong>'.bn_number($s['jobs_total']).'</strong><span>মোট চাকরি</span></div></div></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:20px"><div class="admin-card"><h2>পেজ ভিউ</h2><div class="table-wrap"><table><thead><tr><th>পেজ</th><th>ভিউ</th></tr></thead><tbody>'.$pageHtml.'</tbody></table></div></div><div class="admin-card"><h2>লোকেশন</h2><div class="table-wrap"><table><thead><tr><th>বিভাগ/দেশ</th><th>ভিজিটর</th></tr></thead><tbody>'.$divHtml.'</tbody></table></div></div></div><div class="admin-card"><h2>আজকের ভিজিটর</h2><div class="table-wrap"><table><thead><tr><th>Visitor</th><th>ডিভাইস</th><th>ব্রাউজার</th><th>পেজ</th><th>সময়</th></tr></thead><tbody>'.$rowHtml.'</tbody></table></div></div></div>';
    }
}

if (!function_exists('admin_users')) {
    function admin_users(): string {
        $users=db_all('SELECT * FROM users ORDER BY id ASC');
        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-users"></i> ইউজার / অ্যাডমিন</h1></div><div class="admin-card"><div class="table-wrap"><table><thead><tr><th>ইউজারনেম</th><th>নাম</th><th>রোল</th><th>শেষ লগইন</th><th></th></tr></thead><tbody>';
        foreach($users as $u){ $html.='<tr><td>'.e($u['username']).'</td><td>'.e($u['name']).'</td><td>'.e($u['role']).'</td><td>'.e($u['last_login_at']?:'—').'</td><td><a href="'.e(url('admin/users?id='.$u['id'])).'" data-link class="btn btn-soft btn-sm">এডিট</a></td></tr>'; }
        $html.='</tbody></table></div></div>';
        $edit=null; if(!empty($_GET['id'])) $edit=db_fetch('SELECT * FROM users WHERE id=?',[(int)$_GET['id']]);
        $html.='<div class="admin-card"><h2>'.($edit?'এডিট ইউজার':'নতুন অ্যাডমিন/মডারেটর').'</h2><form data-ajax-form data-url="'.e(url('admin/users'.($edit?'/'.$edit['id']:''))).'">'.csrf_field().'<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>ইউজারনেম</label><input name="username" value="'.e($edit['username']??'').'" required></div><div class="form-group"><label>নাম</label><input name="name" value="'.e($edit['name']??'').'"></div></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label>পাসওয়ার্ড '.($edit?'(খালি রাখলে পরিবর্তন হবে না)':'').'</label><input type="password" name="password" '.($edit?'':'required').'></div><div class="form-group"><label>রোল</label><select name="role"><option value="admin"'.(($edit['role']??'')==='admin'?' selected':'').'>Admin</option><option value="moderator"'.(($edit['role']??'')==='moderator'?' selected':'').'>Moderator</option></select></div></div><div class="form-group"><label>পারমিশন (JSON, ঐচ্ছিক)</label><textarea name="permissions" rows="2" placeholder=\'{"jobs":true,"banners":true}\'>'.e($edit['permissions']??'').'</textarea></div><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> সংরক্ষণ</button></form></div></div>';
        return $html;
    }
}

if (!function_exists('admin_settings')) {
    function admin_settings(): string {
        $fields=default_site_settings();
        $groups=[
            'সাধারণ'=>['site_name','site_subtitle','site_slogan','site_footer','site_copyright','items_per_page'],
            'SEO'=>['home_seo_title','home_seo_h1','home_meta_description','home_meta_keywords','site_description','site_keywords','google_verification'],
            'যোগাযোগ'=>['contact_email','contact_phone','contact_address','site_location'],
            'সোশ্যাল'=>['facebook_url','twitter_url','telegram_url','youtube_url'],
            'সিস্টেম'=>['maintenance_mode','maintenance_message'],
        ];
        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-gear"></i> সেটিংস</h1></div><form data-ajax-form data-url="'.e(url('admin/settings')).'" enctype="multipart/form-data">'.csrf_field();
        foreach($groups as $gTitle=>$keys){
            $html.='<div class="admin-card"><h2>'.e($gTitle).'</h2><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">';
            foreach($keys as $k){
                $val=setting($k,$fields[$k]??''); $type='text';
                if(in_array($k,['site_slogan','site_footer','home_meta_description','site_description','maintenance_message'],true)) $type='textarea';
                if($k==='items_per_page') $type='number';
                if($k==='maintenance_mode') $type='select';
                $html.='<div class="form-group"'.($type==='textarea'?' style="grid-column:1/-1"':'').'><label>'.e(str_replace('_',' ',$k)).'</label>';
                if($type==='textarea') $html.='<textarea name="'.e($k).'" rows="3">'.e($val).'</textarea>';
                elseif($type==='select') $html.='<select name="'.e($k).'"><option value="0"'.($val==='0'?' selected':'').'>Off</option><option value="1"'.($val==='1'?' selected':'').'>On</option></select>';
                else $html.='<input type="'.e($type).'" name="'.e($k).'" value="'.e($val).'">';
                $html.='</div>';
            }
            $html.='</div></div>';
        }
        $logo=setting('site_logo'); $favicon=setting('site_favicon'); $meta=setting('site_meta_image'); $og=setting('og_default_image');
        $html.='<div class="admin-card"><h2><i class="fa-solid fa-image"></i> লোগো, ফেভিকন, OG ইমেজ</h2>
            <div class="upload-line"><span>লোগো (png,svg,jpg)</span><input type="file" name="site_logo_file" accept="image/*,.svg">'.($logo?'<img src="'.e(uploads_url($logo)).'" class="admin-thumb">':'').'</div>
            <div class="upload-line"><span>ফেভিকন</span><input type="file" name="site_favicon_file" accept="image/*">'.($favicon?'<img src="'.e(uploads_url($favicon)).'" class="admin-thumb">':'').'</div>
            <div class="upload-line"><span>মেটা ইমেজ</span><input type="file" name="site_meta_image_file" accept="image/*">'.($meta?'<img src="'.e(uploads_url($meta)).'" class="admin-thumb">':'').'</div>
            <div class="upload-line"><span>OG ডিফল্ট</span><input type="file" name="og_default_image_file" accept="image/*">'.($og?'<img src="'.e(uploads_url($og)).'" class="admin-thumb">':'').'</div>
            <div class="form-group"><label>Google Site Verification (HTML file upload বা meta code)</label><input type="file" name="google_verification_file" accept=".html"><input type="text" name="google_verification" value="'.e(setting('google_verification')).'" placeholder="meta code" style="margin-top:8px"></div>
        </div>';
        $html.='<button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk"></i> সংরক্ষণ</button></form></div>';
        return $html;
    }
}

if (!function_exists('admin_gallery')) {
    function admin_gallery(): string {
        $rows=db_all('SELECT * FROM gallery ORDER BY id DESC LIMIT 200');
        $html='<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-images"></i> গ্যালারি</h1></div><div class="admin-card"><form data-ajax-form data-url="'.e(url('admin/gallery')).'" enctype="multipart/form-data">'.csrf_field().'<div style="display:flex;gap:10px;flex-wrap:wrap"><input type="text" name="title" placeholder="শিরোনাম"><input type="file" name="gallery_file" accept="image/*" required><button class="btn btn-primary"><i class="fa-solid fa-upload"></i> আপলোড</button></div></form></div><div class="gallery-grid">';
        foreach($rows as $r){ $html.='<div class="gallery-item"><img src="'.e(uploads_url($r['file'])).'" alt=""><div class="g-item-actions"><button class="btn btn-soft btn-sm" data-copy="'.e(uploads_url($r['file'])).'"><i class="fa-solid fa-link"></i></button><a href="'.e(url('admin/gallery?delete='.$r['id'])).'" data-link class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></a></div></div>'; }
        $html.='</div></div>';
        return $html;
    }
}

if (!function_exists('admin_change_password_page')) {
    function admin_change_password_page(): string {
        return '<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-key"></i> পাসওয়ার্ড পরিবর্তন</h1></div><div class="admin-card" style="max-width:480px"><form data-ajax-form data-url="'.e(url('admin/change-password')).'">'.csrf_field().'<div class="form-group"><label>নতুন পাসওয়ার্ড</label><input type="password" name="password" required minlength="8"></div><div class="form-group"><label>পুনরায়</label><input type="password" name="password_confirm" required></div><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> পরিবর্তন</button></form></div></div>';
    }
}
