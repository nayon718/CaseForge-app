# ওভিজোগ বিডি — Core PHP + MySQL

## ফিচার

- Clean URL: `domain.com`, `domain.com/report`, `domain.com/report/RPT-2026-0123`
- SPA ফিল: Header, Navbar, Sidebar, Footer একবার লোড; শুধু `#pageContent` ফেচ হয়
- Fetch API + History API + Back/Forward সরাসরি সাপোর্ট
- পেজ ক্যাশিং (JS `Map`), হোভার preload/prefetch, lazy load
- Smooth fade/slide, top progress bar, premium loader
- ছবি client-side compress (report ≤5MB, payment ≤2MB)
- Mobile bottom nav + PC sidebar menu
- বাংলা ইউনিকোড (utf8mb4), ফন্ট Awesome আইকন
- অ্যাডমিন প্যানেল: report approve/reject/edit, payment method, gallery, settings, analytics
- Safety: PDO prepared, CSRF, password hash, upload restriction, IP/device analytics

## সেটআপ (Share Hosting)

1. **হোস্টে ফোল্ডার আপলোড করুন** — `ovijog-bd/` এর সব ফাইল public_html বা desired folder-এ।

2. **কনফিগ ঢোকান**

   `config.sample.php` কপি করে নাম দিন `config.php` এবং বসান:

   ```php
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```

3. **ডাটাবেজ টেবিল তৈরি করুন**

   ব্রাউজারে খুলুন:

   ```
   https://your-domain.com/setup
   ```

   `ডাটাবেজ টেবিল তৈরি করুন` চাপলে `database/schema.sql` চলে।

4. **ডিফল্ট অ্যাডমিন**

   ```
   /admin/login
   ```

   - Username: `admin`
   - Password: `Admin@123`

   **লগইন করে অবশ্যই পাসওয়ার্ড বদলে নিন।**

5. **URL Clean (Apache)**

   `.htaccess` ফাইলটি থাকলেই হবে। যদি mod_rewrite না চলে, সহজ alternation:
   সব অনুরোধ `index.php`-এ যাবে।

## ফোল্ডার কাঠামো

```
ovijog-bd/
├── index.php              🔥 Front controller
├── .htaccess              Clean URL + security
├── config.sample.php      DB config template
├── database/
│   ├── schema.sql
│   └── setup_handler.php
├── includes/
│   ├── bootstrap.php
│   ├── db.php
│   ├── helpers.php
│   ├── auth.php
│   ├── router.php
│   ├── layout.php         Inline CSS + HTML shell
│   ├── actions/
│   │   ├── report_submit.php
│   │   ├── payment_submit.php
│   │   ├── search.php
│   │   ├── admin_login.php
│   │   └── admin_actions.php
│   └── pages/
│       ├── public.php
│       └── admin.php
├── assets/
│   ├── img/logo.png, favicon.png
│   └── js/app.js          SPA engine
└── uploads/
    ├── reports/
    ├── payments/
    └── gallery/
```

## নোট

- `.htaccess` নিয়মে `includes/`, `database/`, `logs/`, `config.php` সরাসরি ব্লক।
- Sharing host এ uploads ফোল্ডার permission `755` রাখুন।
- SEO: meta tags, Open Graph, Twitter Card, sitemap.xml, robots.txt আছে।
- Admin Settings থেকে site name, footer, favicon, logo, meta image পরিবর্তন করা যায়।
