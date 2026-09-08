# Oryzenx — AH NAYON Portfolio

Personal portfolio for **A.H Nayon** — Mechanical Engineering student, web developer & tech enthusiast.
Built by **Oryzenx**.

Light theme · one-page experience · clean URLs · PHP backend · MySQL database · no page reloads.

---

## Features

- **Header** — AH NAYON BIO logo, Home / About / Skills / Project / Contact + Download CV button (Font Awesome icons), scroll progress bar, sticky glass header, mobile menu.
- **Hero** — "Hello It's Me / AH NAYON", animated typing roles (Mechanical Engineering → Cybersecurity → Web Development → Programming → UI/UX), circular profile photo on a 3D mechanical + computer + programming background with rotating orbits, social icons (Facebook, X, Instagram, GitHub, Mail).
- **About** — bio in English + Bangla, quick facts, animated counters.
- **Skills** — auto-scrolling one-line marquees: Node.js, PHP, Java, JS, CSS, SQL, React, HTML, Git and the 9 professional skills; animated proficiency bars.
- **Services** — Web Development, UI/UX Design, Software Development.
- **Projects** — three showcase cards.
- **Contact** — mail & call cards, Bangla business CTA, WhatsApp button with a pre-filled salaam message, and an AJAX contact form (saved to MySQL, no reload).
- **Location** — premium Leaflet map of Sundarganj, Gaibandha with Street / Satellite / Terrain layers, recenter, fullscreen, radius circle, popup and **Get Directions**.
- **Footer** — brand blurb, Quick Links, Services, clickable Contact list, copyright.
- **Floating** — back-to-top button (right side) and a WhatsApp button.
- **Background** — lightweight custom canvas 3D animation (gears, chips, code brackets, nodes) with parallax; disabled for `prefers-reduced-motion`.

---

## Requirements

- PHP 8.0+
- MySQL / MariaDB 5.7+
- Apache with `mod_rewrite` (the included `.htaccess` gives clean URLs), or Nginx with the rule below.

## Installation

1. Upload the `oryzenx/` contents to your web root (e.g. `public_html`).
2. Create a MySQL database, then copy the config:
   ```bash
   cp config.sample.php config.php
   ```
   Edit `config.php` with your DB name, user and password.
3. Visit `https://yourdomain.com/setup` and click **Create database tables**.
4. Delete or protect `/setup` once done. Done — open `https://yourdomain.com/`.
5. Put your CV at `assets/AH-NAYON-CV.pdf` so the **Download CV** button works.

### Nginx clean URLs
```nginx
location / { try_files $uri $uri/ /index.php?$query_string; }
```

## Routes

| URL | Purpose |
|---|---|
| `/` | Home (full one-page site) |
| `/about`, `/skills`, `/services`, `/projects`, `/contact`, `/location` | Deep links to sections |
| `/cv` | CV download |
| `/api/contact` | Contact form endpoint (JSON) |
| `/setup` | Create DB tables |
| `/robots.txt`, `/sitemap.xml` | SEO |

## Database

`database/schema.sql` creates:
- `messages` — contact form submissions (name, email, phone, subject, message, ip, user agent, read flag, timestamp)
- `visits` — optional page-visit log

If the DB is unavailable, submissions fall back to `storage/messages.log` and email, so no lead is lost.

## Editing content

All names, links, phone, email and the WhatsApp message live in **`site.json`** — change once, updates everywhere.
Markup lives in `partials/*.html` (`{{placeholders}}` are filled by `includes/bootstrap.php`).

## Local preview without PHP

`tools/preview-server.mjs` is a dev-only Node server that renders the same partials with the same routes:

```bash
node tools/preview-server.mjs   # http://localhost:3000
```

Production always runs `index.php`.

## Structure

```
oryzenx/
├── index.php              front controller / router
├── .htaccess              clean URLs, caching, security headers
├── config.sample.php      copy to config.php
├── site.json              all editable content
├── includes/              bootstrap.php, db.php, contact.php
├── partials/              head, header, hero, about, skills, contact, footer
├── assets/css|js|img
├── database/schema.sql
└── tools/preview-server.mjs
```

## Contact

📧 mdnayon718@gmail.com · 📞 01757827996 · 💬 +8801757827996 · 📍 Sundarganj, Gaibandha, Bangladesh

© AH NAYON. Crafted by Oryzenx.
