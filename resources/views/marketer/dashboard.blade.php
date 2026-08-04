<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rute — Aplikasi Sales Motoris</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<meta name="csrf-token" content="{{ csrf_token() }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
  :root{
    --bg-outer:#0b0a08;
    --bg-app:#15130f;
    --surface:#201d17;
    --surface-2:#2a271f;
    --surface-3:#332f25;
    --border:#3c372a;
    --accent:#f2a93b;
    --accent-ink:#1c1509;
    --accent-soft:#3a2f18;
    --good:#8caa7e;
    --good-soft:#26301f;
    --danger:#c1502e;
    --danger-soft:#331c14;
    --text:#ede8dc;
    --text-muted:#a39a86;
    --text-faint:#726b58;
    --radius:14px;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html,body{height:100%;}
  body{
    background:var(--bg-outer);
    background-image:
      radial-gradient(circle at 20% 15%, #1a1712 0%, transparent 55%),
      radial-gradient(circle at 85% 80%, #171410 0%, transparent 50%);
    font-family:'Inter', sans-serif;
    color:var(--text);
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    min-height:100dvh;
    margin:0;
    padding:0;
  }
  .label-top{
    position:fixed; top:14px; left:50%; transform:translateX(-50%);
    font-family:'IBM Plex Mono', monospace; font-size:11px; letter-spacing:.14em;
    text-transform:uppercase; color:var(--text-faint); display: flex; align-items: center; gap: 8px;
    z-index: 100;
  }
  .label-top a {
    color: var(--accent);
    text-decoration: none;
  }
  .app-header {
    height: 6px;
    flex-shrink: 0;
  }
  .brand-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-app);
    flex-shrink: 0;
  }
  .brand-logo {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.05em;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .logo-dot {
    width: 8px;
    height: 8px;
    background: var(--accent);
    border-radius: 50%;
    box-shadow: 0 0 10px var(--accent);
  }
  .brand-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .mini-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--accent-soft);
    border: 1px solid var(--accent);
    color: var(--accent);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s;
  }
  .mini-avatar:active {
    transform: scale(0.95);
  }
  .appbar{
    flex-shrink:0; padding:6px 18px 16px; display:flex; align-items:center; gap:10px;
  }
  .appbar h1{ font-family:'Space Grotesk', sans-serif; font-size:19px; font-weight:600; letter-spacing:-.01em;}
  .back-btn{
    width:32px; height:32px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border);
    display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0;
  }
  .back-btn svg{width:16px;height:16px;stroke:var(--text);}
  .screen{ display:none; flex-direction:column; height:100%; overflow:hidden;}
  .screen.active{ display:flex; }
  .scroll{ flex:1; overflow-y:auto; padding:0 18px 24px; }
  .scroll::-webkit-scrollbar{width:0;}

  /* ---- shared elements ---- */
  .eyebrow{
    font-family:'IBM Plex Mono', monospace; font-size:10.5px; letter-spacing:.13em; text-transform:uppercase;
    color:var(--accent); margin-bottom:4px; display:block;
  }
  .card{
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
    padding:16px;
  }
  .chip{
    display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600;
    padding:4px 9px; border-radius:100px; border:1px solid var(--border); color:var(--text-muted);
    font-family:'IBM Plex Mono', monospace; letter-spacing:.01em;
  }
  .chip.baru{ color:var(--good); border-color:#3c4d33; background:var(--good-soft); }
  .chip.lama{ color:var(--text-muted); }
  .chip.partai{ color:var(--accent); border-color:var(--accent-soft); background:var(--accent-soft);}
  .chip.ecer{ color:var(--text-muted); }
  .btn{
    font-family:'Inter', sans-serif; font-weight:600; font-size:14.5px;
    border-radius:12px; border:none; padding:14px 18px; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:8px; width:100%;
  }
  .btn-primary{ background:var(--accent); color:var(--accent-ink); }
  .btn-primary:active{ transform:scale(.98); }
  .btn-primary:disabled{ background:var(--border); color:var(--text-faint); cursor:not-allowed; }
  .btn-outline{ background:transparent; color:var(--text); border:1px solid var(--border); }
  .btn-ghost{ background:var(--surface-2); color:var(--text); }
  .field{ margin-bottom:16px; }
  .field label{ display:block; font-size:12.5px; color:var(--text-muted); margin-bottom:7px; font-weight:500;}
  .field input[type=text], .field input[type=tel], .field textarea, .field select{
    width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:10px;
    padding:12px 13px; color:var(--text); font-family:'Inter', sans-serif; font-size:14.5px;
  }
  .field textarea{ resize:none; height:78px; }
  .radio-row{ display:flex; gap:8px; }
  .radio-opt{
    flex:1; text-align:center; padding:11px 6px; border-radius:10px; border:1px solid var(--border);
    background:var(--surface-2); font-size:13.5px; font-weight:600; color:var(--text-muted); cursor:pointer;
  }
  .radio-opt.sel{ background:var(--accent-soft); border-color:var(--accent); color:var(--accent); }
  .section-title{
    font-family:'Space Grotesk', sans-serif; font-size:15px; font-weight:600; margin:22px 0 10px;
  }

  /* ---- HOME ---- */
  .home-head{ padding:4px 18px 18px; }
  .home-head .greet{ font-family:'Space Grotesk', sans-serif; font-size:22px; font-weight:600; }
  .home-head .date{ font-size:12.5px; color:var(--text-muted); margin-top:2px; }
  .stat-row{ display:flex; gap:10px; margin-top:16px; }
  .stat-box{
    flex:1; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:13px;
  }
  .stat-box .num{ font-family:'Space Grotesk', sans-serif; font-size:22px; font-weight:700; }
  .stat-box .cap{ font-size:11px; color:var(--text-muted); margin-top:2px; }
  .quick-row{ display:flex; gap:10px; margin:16px 0 4px; }
  .visit-row{
    display:flex; align-items:center; gap:12px; padding:13px 0; border-bottom:1px solid var(--border);
    cursor:pointer;
  }
  .visit-row:last-child{ border-bottom:none; }
  .time-badge{
    font-family:'IBM Plex Mono', monospace; font-size:12px; color:var(--text-muted); width:44px; flex-shrink:0;
  }
  .visit-row .vname{ font-weight:600; font-size:14px; }
  .visit-row .vaddr{ font-size:12px; color:var(--text-muted); margin-top:1px; }
  .dot{ width:7px; height:7px; border-radius:50%; flex-shrink:0; }
  .dot.pending{ background:var(--text-faint); }
  .dot.done{ background:var(--good); }

  /* ---- CUSTOMER LIST ---- */
  .search-box{
    display:flex; align-items:center; gap:8px; background:var(--surface-2); border:1px solid var(--border);
    border-radius:12px; padding:10px 13px; margin:14px 0 12px;
  }
  .search-box input{ background:transparent; border:none; outline:none; color:var(--text); font-size:14px; flex:1; font-family:'Inter', sans-serif;}
  .search-box svg{ width:16px; height:16px; stroke:var(--text-faint); flex-shrink:0; }
  .filter-row{ display:flex; gap:7px; margin-bottom:14px; overflow-x:auto; }
  .filter-chip{
    flex-shrink:0; padding:7px 13px; border-radius:100px; border:1px solid var(--border); font-size:12.5px;
    font-weight:600; color:var(--text-muted); cursor:pointer; background:var(--surface);
  }
  .filter-chip.active{ background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
  .cust-card{
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:14px;
    margin-bottom:10px; cursor:pointer;
  }
  .cust-card .cname{ font-family:'Space Grotesk', sans-serif; font-weight:600; font-size:15px; }
  .cust-card .caddr{ font-size:12.5px; color:var(--text-muted); margin:3px 0 10px; }
  .cust-card .tags{ display:flex; gap:6px; flex-wrap:wrap; }
  .fab{
    position:absolute; right:18px; bottom:96px; width:52px; height:52px; border-radius:16px;
    background:var(--accent); color:var(--accent-ink); border:none; display:flex; align-items:center;
    justify-content:center; box-shadow:0 10px 24px -8px rgba(242,169,59,.5); cursor:pointer; z-index:40;
  }
  .fab svg{ width:22px; height:22px; stroke:var(--accent-ink); }

  /* ---- DETAIL ---- */
  .detail-hero{ padding:2px 18px 4px; }
  .detail-hero .cname{ font-family:'Space Grotesk', sans-serif; font-size:20px; font-weight:700; }
  .detail-hero .caddr{ font-size:13px; color:var(--text-muted); margin:4px 0 12px; }
  .info-line{ display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border); font-size:13.5px; }
  .info-line:last-child{ border-bottom:none; }
  .info-line .k{ color:var(--text-muted); }
  .info-line .v{ font-family:'IBM Plex Mono', monospace; font-size:12.5px; }
  .action-row{ display:flex; gap:10px; margin-top:18px; }
  .mini-visit{ font-size:12.5px; color:var(--text-muted); padding:8px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between;}
  .mini-visit:last-child{border-bottom:none;}

  /* ---- CHECK-IN / MAP ---- */
  .map-box{
    height:180px; border-radius:var(--radius); border:1px solid var(--border); position:relative; overflow:hidden;
    background:
      linear-gradient(var(--surface-2) 1px, transparent 1px) 0 0/22px 22px,
      linear-gradient(90deg, var(--surface-2) 1px, transparent 1px) 0 0/22px 22px,
      var(--surface);
  }
  .map-pin{
    position:absolute; top:50%; left:50%; transform:translate(-50%,-100%);
    display:flex; flex-direction:column; align-items:center;
  }
  .map-pin .head{ width:26px; height:26px; border-radius:50% 50% 50% 0; background:var(--accent); transform:rotate(-45deg); box-shadow:0 4px 10px -2px rgba(0,0,0,.5);}
  .map-pin .ring{ width:60px; height:60px; border-radius:50%; border:1px solid var(--accent); opacity:.35; position:absolute; top:14px; left:50%; transform:translateX(-50%); animation:pulse 2.2s ease-out infinite;}
  @keyframes pulse{ 0%{ transform:translateX(-50%) scale(.4); opacity:.6;} 100%{ transform:translateX(-50%) scale(1.5); opacity:0;} }
  .locating{ display:flex; align-items:center; gap:10px; padding:16px; font-size:13.5px; color:var(--text-muted); }
  .spinner{ width:16px; height:16px; border-radius:50%; border:2px solid var(--border); border-top-color:var(--accent); animation:spin .8s linear infinite; flex-shrink:0; }
  @keyframes spin{ to{ transform:rotate(360deg);} }
  .coord-card{ margin-top:12px; }
  .coord-grid{ display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px; }
  .coord-grid .item .k{ font-size:10.5px; color:var(--text-faint); text-transform:uppercase; letter-spacing:.08em; }
  .coord-grid .item .v{ font-family:'IBM Plex Mono', monospace; font-size:14px; margin-top:2px; }
  .photo-drop{
    border:1.5px dashed var(--border); border-radius:12px; padding:20px; text-align:center; color:var(--text-faint);
    font-size:12.5px; cursor:pointer;
  }
  .stamp-wrap{ flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:30px; text-align:center; }
  .stamp{
    border:2.5px dashed var(--good); color:var(--good); border-radius:16px; padding:18px 26px;
    transform:rotate(-4deg); font-family:'Space Grotesk', sans-serif; font-weight:700; font-size:22px;
    letter-spacing:.06em; margin-bottom:22px;
  }
  .stamp.order{ border-color:var(--accent); color:var(--accent); }
  .stamp-detail{ width:100%; text-align:left; }

  /* ---- ORDER FORM ---- */
  .prod-row{ display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border); }
  .prod-row:last-child{ border-bottom:none; }
  .prod-row .pname{ font-weight:600; font-size:14px; }
  .prod-row .punit{ font-size:11.5px; color:var(--text-faint); margin-top:1px; }
  .stepper{ display:flex; align-items:center; gap:10px; }
  .stepper button{
    width:28px; height:28px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2);
    color:var(--text); font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center;
  }
  .stepper .qty{ min-width:18px; text-align:center; font-family:'IBM Plex Mono', monospace; font-size:14px; }
  .order-bar{
    flex-shrink:0; padding:14px 18px; border-top:1px solid var(--border); background:var(--surface);
    display:flex; align-items:center; gap:12px;
  }
  .order-bar .sum{ font-size:12.5px; color:var(--text-muted); flex-shrink:0; }
  .order-bar .sum b{ color:var(--text); font-family:'IBM Plex Mono', monospace; }

  /* ---- HISTORY ---- */
  .hist-row{ padding:12px 0; border-bottom:1px solid var(--border); cursor:pointer; }
  .hist-row:last-child{border-bottom:none;}
  .hist-top{ display:flex; justify-content:space-between; align-items:baseline; }
  .hist-top .hname{ font-weight:600; font-size:14px; }
  .hist-top .hdate{ font-size:11px; color:var(--text-faint); font-family:'IBM Plex Mono', monospace;}
  .hist-note{ font-size:12.5px; color:var(--text-muted); margin-top:3px; }

  /* ---- PROFILE ---- */
  .profile-top{ display:flex; flex-direction:column; align-items:center; padding:10px 0 20px; }
  .avatar{ width:64px; height:64px; border-radius:50%; background:var(--accent-soft); border:1px solid var(--accent); display:flex; align-items:center; justify-content:center; font-family:'Space Grotesk', sans-serif; font-size:22px; font-weight:700; color:var(--accent); }
  .profile-top .pname{ font-family:'Space Grotesk', sans-serif; font-weight:700; font-size:17px; margin-top:10px; }
  .profile-top .parea{ font-size:12.5px; color:var(--text-muted); margin-top:2px; }

  /* ---- BOTTOM NAV ---- */
  .bottomnav{
    flex-shrink:0; display:flex; border-top:1px solid var(--border); background:var(--surface);
    padding:10px 6px 16px;
  }
  .navbtn{
    flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; background:none; border:none;
    color:var(--text-faint); font-size:10.5px; font-family:'Inter', sans-serif; font-weight:600; cursor:pointer;
  }
  .navbtn.active{ color:var(--accent); }
  .navbtn svg{ width:20px; height:20px; stroke:currentColor; }
  .empty-note{ text-align:center; color:var(--text-faint); font-size:12.5px; padding:30px 10px; }
</style>
</head>
<body>

<div id="app" class="w-full flex justify-center items-center min-h-dvh">
  <app-component 
    :initial-user="{{ json_encode($user) }}" 
    :initial-customers="{{ json_encode($customers) }}" 
    :initial-visits="{{ json_encode($visits) }}" 
    :initial-orders="{{ json_encode($orders) }}" 
    :initial-products="{{ json_encode($products) }}" 
    csrf-token="{{ csrf_token() }}">
  </app-component>
</div>
</body>
</html>
