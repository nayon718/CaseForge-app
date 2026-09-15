<?php
/**
 * চাকরি সার্কুলার — সার্চ AJAX
 */
if (!function_exists('search_action')) {
    function search_action(): array {
        $q=trim($_POST['q']??$_GET['q']??'');
        $cat=(int)($_POST['cat']??$_GET['cat']??0);
        $district=trim($_POST['district']??$_GET['district']??'');

        if($q===''&&!$cat&&$district===''){
            return ['ok'=>false,'message'=>'অনুগ্রহ করে একটি কিওয়ার্ড বা ফিল্টার দিন।'];
        }

        $where='status="published"'; $params=[];
        if($q!==''){ $where.=' AND (title LIKE ? OR description LIKE ? OR short_description LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; log_search($q); }
        if($cat){ $where.=' AND category_id=?'; $params[]=$cat; }
        if($district!==''){ $where.=' AND (district LIKE ? OR location_text LIKE ?)'; $params[]='%'.$district.'%'; $params[]='%'.$district.'%'; }

        $total=count_jobs($where,$params);
        if($total===0) return ['ok'=>false,'message'=>'কোনো চাকরি পাওয়া যায়নি।'];

        $perPage=max(10,(int)setting('items_per_page','20'));
        $page=max(1,(int)($_POST['page']??$_GET['page']??1));
        $pg=paginate($total,$perPage,$page);
        $rows=get_jobs($perPage,$pg['offset'],$where,$params);

        $cards=''; foreach($rows as $r) $cards.=job_card_html($r);
        $html='<div class="search-summary" style="margin-bottom:12px;color:var(--muted)"><i class="fa-solid fa-circle-check"></i> '.bn_number($total).' টি চাকরি পাওয়া গেছে</div><div class="job-list">'.$cards.'</div>'.paginate_links($pg['pages'],$pg['current'],'search?q='.urlencode($q).'&cat='.$cat.'&district='.urlencode($district));

        return ['ok'=>true,'message'=>'সার্চ সম্পন্ন','html'=>$html,'data'=>['html'=>$html,'total'=>$total]];
    }
}
