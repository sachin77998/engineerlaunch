@once
<style>
.cat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.cat-card{display:flex;flex-direction:column;border:1px solid #dfe6f1;border-radius:16px;background:#fff;overflow:hidden;color:#0f1c2e;text-decoration:none;transition:transform .15s,box-shadow .15s,border-color .15s}
.cat-card:hover,.cat-card:focus-visible{transform:translateY(-3px);border-color:#f4b400;box-shadow:0 14px 30px rgba(11,37,69,.14);outline:0}
.cat-visual{position:relative;height:120px;background:linear-gradient(135deg,var(--accent),color-mix(in srgb,var(--accent) 55%,#f4b400));background-size:cover;background-position:center}
.cat-card.sub .cat-visual{height:96px}
.cat-icon{position:absolute;left:14px;bottom:-22px;display:grid;place-items:center;width:52px;height:52px;border-radius:14px;background:#fff;box-shadow:0 6px 16px rgba(11,37,69,.18);font-size:26px;font-weight:900;color:var(--accent)}
.cat-count{position:absolute;right:12px;top:12px;padding:4px 9px;border-radius:999px;background:rgba(255,255,255,.92);color:#0b2545;font-size:12px;font-weight:800}
.cat-body{display:flex;flex-direction:column;gap:8px;padding:30px 16px 16px;flex:1}
.cat-body h2{margin:0;font-size:17px;line-height:1.3;color:#0b2545}.cat-meta{margin:0;font-size:12.5px;color:#5b6b82}
.cat-chips{display:flex;flex-wrap:wrap;gap:5px}.cat-chips span{padding:3px 8px;border-radius:999px;background:#f4f6fb;font-size:11.5px;color:#334155}
.cat-logos{display:flex;gap:6px;margin-top:auto;padding-top:4px}
.cat-companies{list-style:none;margin:0;padding:0;display:grid;gap:6px}.cat-companies li{display:flex;align-items:center;gap:8px;font-size:13px;color:#1e2f4d}
.sl-logo{position:relative;display:inline-grid;place-items:center;flex:none;width:40px;height:40px;border-radius:10px;overflow:hidden;background:hsl(var(--hue) 55% 92%);color:hsl(var(--hue) 55% 30%);font-size:13px;font-weight:800;border:1px solid #e2e8f2}
.sl-logo.sm{width:28px;height:28px;border-radius:8px;font-size:10px}.sl-logo.lg{width:64px;height:64px;border-radius:16px;font-size:20px}
.sl-logo img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;background:#fff;padding:3px;box-sizing:border-box}
</style>
@endonce
