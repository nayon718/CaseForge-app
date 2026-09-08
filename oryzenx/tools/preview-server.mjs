/**
 * Preview server — DEV ONLY.
 * PHP is not installable in this sandbox, so this tiny Node server renders the
 * exact same partials/ + assets/ with the same routing rules as index.php,
 * letting you preview the real site. Production runs on PHP + MySQL (index.php).
 */
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const site = JSON.parse(fs.readFileSync(path.join(ROOT, 'site.json'), 'utf8'));
const PORT = process.env.PORT || 3000;

const vars = () => ({
  base: '/',
  brand: site.brand, name: site.name, nameFull: site.nameFull,
  email: site.email, phone: site.phone, phoneIntl: site.phoneIntl, location: site.location,
  whatsappLink: `https://wa.me/${site.whatsapp}?text=${encodeURIComponent(site.whatsappText)}`,
  facebook: site.social.facebook, x: site.social.x,
  instagram: site.social.instagram, github: site.social.github,
});

const partial = (n) => {
  let html = fs.readFileSync(path.join(ROOT, 'partials', n + '.html'), 'utf8');
  for (const [k, v] of Object.entries(vars())) html = html.split(`{{${k}}}`).join(v);
  return html;
};

const MIME = {
  '.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.svg': 'image/svg+xml', '.json': 'application/json', '.pdf': 'application/pdf', '.webp': 'image/webp',
};

const ALIASES = ['', 'home', 'about', 'skills', 'services', 'projects', 'contact', 'location'];

http.createServer((req, res) => {
  const url = new URL(req.url, 'http://x');
  const p = url.pathname.replace(/^\/+|\/+$/g, '');

  if (p === 'api/contact' && req.method === 'POST') {
    let body = '';
    req.on('data', c => (body += c));
    req.on('end', () => {
      res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify({ ok: true, message: 'ধন্যবাদ! Your message has been sent — I will reply soon.' }));
    });
    return;
  }

  // static files
  const file = path.join(ROOT, p);
  if (p && fs.existsSync(file) && fs.statSync(file).isFile() && !p.endsWith('.php')) {
    res.writeHead(200, { 'Content-Type': MIME[path.extname(file)] || 'application/octet-stream' });
    return res.end(fs.readFileSync(file));
  }

  if (p === 'cv') {
    res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
    return res.end('<p style="font-family:sans-serif;padding:40px">CV not uploaded yet — place assets/AH-NAYON-CV.pdf</p>');
  }

  const pages = ['head', 'header', 'hero', 'about', 'skills', 'contact', 'footer'];
  let html = pages.map(partial).join('\n');
  if (p && p !== 'home' && ALIASES.includes(p)) {
    html += `<script>addEventListener("DOMContentLoaded",function(){var el=document.getElementById(${JSON.stringify(p)});if(el)el.scrollIntoView();});</script>`;
  }
  res.writeHead(ALIASES.includes(p) ? 200 : 404, { 'Content-Type': 'text/html; charset=utf-8' });
  res.end(html);
}).listen(PORT, '0.0.0.0', () => console.log('Preview running on http://0.0.0.0:' + PORT));
