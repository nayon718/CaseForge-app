# চাকরি সার্কুলার — CakriCircular.com — Core PHP + MySQL (SPA Feel)

প্রফেশনাল লাইট মুড, মোবাইল রেসপন্সিভ, ন্যানো-সেকেন্ড SPA ফিল, Core PHP ব্যাকএন্ড, MySQL, Shared Hosting Ready.

## ✨ মূল ফিচার

- **SPA ইঞ্জিন**: Header, Navbar, Sidebar, Footer একবার লোড; শুধু `#pageContent` Fetch API দিয়ে লোড, History API (pushState/replaceState), Back/Forward সাপোর্ট, Smooth Fade/Slide, Premium Progress Bar + Loader, JS `Map` Cache, Hover Preload/Prefetch, Lazy Load, Duplicate Listener Guard.
- **Clean URL**: `domain.com`, `domain.com/category/chakri`, `domain.com/job/slug`, `domain.com/search`, `domain.com/trending`, `domain.com/notices` — কোন `.php` নেই।
- **ব্যানার**: ১-১০ টা, অটো ফিক্স সাইজ, অটো মুভ + ম্যানুয়াল ড্র্যাগ/স্ক্রল, লিংক সাপোর্ট, আপলোড কমপ্রেসর `5mb=450kb, 4mb=400kb, 3mb=300kb, 2mb=200kb, 1mb=100kb`।
- **চাকরি লিস্টিং**: এক লাইনে একটা, ছোট ছবি (ডিফল্ট সাইট লোগো), ২ লাইন টাইটেল, `১ মিনিট আগে` টাইম, ডেডলাইন কাউন্টডাউন `৩ দিন বাকি / সময় শেষ`, জেলা লোকেশন, শেয়ার বাটন (Share This + Title + Link + Copy + FB/Twitter/WhatsApp/Telegram), OG Card।
- **ডিটেইল পেজ**: পুরো টাইটেল, বাংলাদেশ টাইম `d M Y, h:i A`, PDF ডাউনলোড (কমপ্রেস), জেলা, ডেডলাইন কাউন্টডাউন, ব্যানার টাইপ ছবি ১-৫টা (ক্লিক → পপআপ জুম), বিস্তারিত অটো ডিজাইন HTML/CSS, একাধিক লিংক বক্স, Apply Button, শেয়ার Meta Card, Related Posts।
- **SEO**: Organization Schema (`চাকরি সার্কুলার`, alternateName: CakriCircular, CakriCircular.com...), Website Schema, JobPosting Schema (dynamic), BreadcrumbList, Unique SEO Title/Meta/Canonical/Keywords, Clean URL, XML Sitemap (jobs + categories + notices), Optimized robots.txt, Google Search Console Ready, Semantic HTML, Mobile-first, Core Web Vitals।
- **Homepage SEO**: Title `চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি`, H1 `চাকরি সার্কুলার ও আজকের চাকরির খবর`, Meta Description Keywords relevant, no competitor keyword stuffing.
- **ফুটার**: লোগো, সাইট নাম, স্লোগান (এডমিন editable), যোগাযোগ (mail, location), FB/Twitter/Telegram লিংক আইকনসহ, দ্রুত লিংক, Information (আমাদের সম্পর্কে, গোপনীয়তা, ভুল রিপোর্ট)।
- **মোবাইল বটম মেনু**: শুধু মোবাইল, App Look Vibe, Circle Button Animation, Home (চাকরি), Trending (Impression based, public views hidden, admin analytics only), Search (filter keyword, category, district, top search, recent), Notice (admin push line by line, sound + digit badge when unread)।
- **অ্যাডমিন**: সব কন্ট্রোল — Site Name, Logo/Favicon (png,svg,jpg), OG Default, SEO Meta (Home Title H1, Description, Keywords, Google Verification HTML upload), Footer Mail/Location/Slogan/Copyright, Social Links, Per Page 10/20/30/50/70/100, Maintenance Mode, 2nd Admin + Permission, Moderator, Job Post (category wise, filter, search, edit, delete, bulk delete), Banners Add/Edit/Delete, Notices Push + Bulk Delete, Wrong Reports (mail box, title, detail) serial, Analytics (visitor one device once, total page visitor, division + country, which page), Password Change, High Security (PDO, CSRF, Hash), 404।
- **Performance**: Image Cache 30 days (`.htaccess`), Client + Server Compression, Lazy Load, Minimal JS, Professional Light Colors.

## 📁 ফোল্ডার স্ট্রাকচার

```
ovijog-bd/
├── index.php              Front Controller (SPA + SEO + Sitemap + Robots)
├── .htaccess              Clean URL + Security + 30d Cache + Compression
├── config.sample.php      DB config template
├── database/
│   ├── schema.sql         Full schema (utf8mb4, Bengali support)
│   └── setup_handler.php
├── includes/
│   ├── bootstrap.php
│   ├── db.php
│   ├── helpers.php        SEO, compression, job_card, share, time_ago, countdown
│   ├── auth.php
│   ├── router.php         Clean routing (category slug, job slug, etc)
│   ├── layout.php         Inline CSS (professional light) + HTML shell + Schemas
│   ├── actions/
│   │   ├── admin_login.php
│   │   ├── admin_actions.php (jobs, categories, banners, notices, settings, users)
│   │   ├── search.php
│   │   └── report_submit.php (wrong info)
│   └── pages/
│       ├── public.php     home, category, jobs, job-detail, search, trending, notices, about, privacy, contact, report-wrong, 404
│       └── admin.php      dashboard, jobs, categories, banners, notices, reports, analytics, users, settings, gallery, password
├── assets/
│   ├── img/logo.png, favicon.png, logo.svg
│   └── js/app.js          SPA Engine (cache, preload, progress, banner, lightbox, share, search, notices)
└── uploads/
    ├── jobs/, banners/, settings/, pdfs/, gallery/
```

## 🚀 সেটআপ (Share Hosting)

1. `ovijog-bd/` এর সব ফাইল public_html এ আপলোড করুন।
2. `config.sample.php` কপি করে `config.php` নাম দিন, DB তথ্য বসান:
   ```php
   define('DB_NAME','your_db');
   define('DB_USER','your_user');
   define('DB_PASS','your_pass');
   ```
3. ব্রাউজারে `https://your-domain.com/setup` খুলে `ডাটাবেজ টেবিল তৈরি করুন` চাপুন।
4. ডিফল্ট অ্যাডমিন:
   - URL: `/admin/login` বা `/v2admin/login`
   - Username: `admin`
   - Password: `Admin@123` (লগইন করে অবশ্যই বদলান)
5. `.htaccess` থাকলেই Clean URL কাজ করবে। `uploads/` permission `755`।

## 🔒 নিরাপত্তা

- PDO Prepared, CSRF Token, Password Hash, Upload Restriction (image/pdf), IP/device analytics, `uploads/` এ PHP execution block, Security Headers.

## 🎨 ডিজাইন

- Light Mode Professional: `#0f766e` primary, `#f8fafc` bg, `#ffffff` surface, FontAwesome Icons, Hind Siliguri + Inter fonts, Smooth Animation, No Lag.

## 📈 SEO Setup for CakriCircular.com

- Organization: Name `চাকরি সার্কুলার`, alternateName `CakriCircular`, `CakriCircular.com`, `চাকরির সার্কুলার`, `চাকরির খবর`, `আজকের চাকরি`
- Website Schema + Organization Schema + JobPosting Schema + BreadcrumbList
- Homepage Title `চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি`
- H1 `চাকরি সার্কুলার ও আজকের চাকরির খবর`
- Category SEO: সরকারি চাকরি, বেসরকারি চাকরি, ব্যাংক চাকরি, শিক্ষক নিয়োগ, চাকরির পরীক্ষা, ফলাফল
- Sitemap + Robots + Canonical + OG + Twitter Card

## 📱 Mobile

- Bottom Nav শুধু মোবাইল, PC তে Full Display, App Look Vibe, Circle FAB Animation, Smooth Transition.

---

© ২০২৬ চাকরি সার্কুলার — CakriCircular.com — Trusted Bangladesh Job Portal
