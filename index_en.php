<?php
// DYNAMISCHE ZAHLEN (11.11.2026): Universum/Handelbarkeit live aus Stockbot-DB, 1h-Cache.
$__c='/tmp/sp_stats_cache_en.json';
$__st=@json_decode((string)@file_get_contents($__c),true);
if(!is_array($__st)||((int)($__st['t']??0))<time()-3600){
  $__n=9400; $__tr=4500;
  try{
    $__db=new PDO('sqlite:/opt/stockbot/stockbot.db');
    $__n=(int)$__db->query('SELECT COUNT(*) FROM ticker_meta')->fetchColumn();
    $__tr=(int)$__db->query('SELECT COUNT(*) FROM ticker_tradable WHERE tradable_eu=1')->fetchColumn();
    $__sig=(int)$__db->query('SELECT COUNT(*) FROM swing_signals')->fetchColumn();
  }catch(Throwable $e){}
  $__st=['t'=>time(),'n'=>$__n,'tr'=>$__tr,'sig'=>$__sig];
  @file_put_contents($__c,json_encode($__st));
}
// Marktampel IDENTISCH zur App (sb_marktampel: SMA200/Vol/VIX/ATH + akuter Override)
$__amp=['#64748B','OUTDATED']; $__ampS=''; $__ampW='';
if(!isset($__st['ampv'])){
  $hex='#64748B'; $lab='OUTDATED'; $sub=''; $warn=[];
  try{
    $__db2=new PDO('sqlite:/opt/stockbot/stockbot.db');
    $spx=[]; foreach($__db2->query("SELECT d,wert FROM market_history WHERE reihe='GSPC' ORDER BY d DESC LIMIT 900") as $r){ $spx[$r['d']]=(float)$r['wert']; }
    ksort($spx);
    $w=array_values($spx); $n=count($w); $kurs=$n?$w[$n-1]:0.0;
    $sma200 = $n>=200 ? array_sum(array_slice($w,-200))/200 : null;
    $hoch=(float)$__db2->query("SELECT MAX(wert) FROM market_history WHERE reihe='GSPC'")->fetchColumn();
    $dd = $hoch>0 ? ($kurs/$hoch-1)*100 : 0.0;
    $vol=null;
    if($n>21){ $rr=[]; for($i=$n-20;$i<$n;$i++){ $rr[]=$w[$i]/$w[$i-1]-1; }
      $m=array_sum($rr)/count($rr); $q=0.0; foreach($rr as $x){ $q+=($x-$m)**2; }
      $vol=sqrt($q/(count($rr)-1))*sqrt(252)*100; }
    $vix=(float)$__db2->query("SELECT wert FROM market_history WHERE reihe='VIX' ORDER BY d DESC LIMIT 1")->fetchColumn();
    $maxp=0.0; $warn=[];
    if($sma200!==null){ if($kurs<$sma200){$warn[]='S&P unter 200T-Linie';$maxp=max($maxp,27.4);} else $maxp=max($maxp,9.5); }
    if($vol!==null){ if($vol>20){$warn[]='Volatilit&auml;t &uuml;ber 20%';$maxp=max($maxp,33.9);} elseif($vol<12){$maxp=max($maxp,7.4);} }
    if($vix>0){ if($vix>30){$warn[]='VIX &uuml;ber 30';$maxp=max($maxp,25.1);} elseif($vix>20){$warn[]='VIX &uuml;ber 20';$maxp=max($maxp,21.5);} elseif($vix<15){$maxp=max($maxp,3.9);} }
    if($dd>-2){$maxp=max($maxp,6.2);} elseif($dd<-5){$warn[]='Drawdown &uuml;ber 5%';$maxp=max($maxp,19.6);}
    if($maxp>=25||count($warn)>=2){$hex='#DC2626';$lab='STORM';}
    elseif($maxp>=19||count($warn)>=1){$hex='#D97706';$lab='UNSETTLED';}
    else{$hex='#16A34A';$lab='CALM';}
    // akuter Override (market_pulse, nur frisch <45 min)
    $mr=$__db2->query("SELECT regime,updated_at FROM market_regime WHERE id=1")->fetch(PDO::FETCH_ASSOC);
    if($mr && !empty($mr['updated_at'])){ $ts=strtotime($mr['updated_at'].' UTC');
      if($ts!==false && (time()-$ts)<=2700 && in_array($mr['regime'],['Risk-Off','Vorsicht','Caution'])){
        if($mr['regime']==='Risk-Off'){$hex='#DC2626';$lab='STORM';}else{$hex='#D97706';$lab='UNSETTLED';}
        $warn[]='akut: '.$mr['regime'];
      } }
    $sub=($vol!==null?'Vol 20T '.number_format($vol,1,',','.').'%':'').($vix>0?' &middot; VIX '.number_format($vix,1,',','.'):'');
  }catch(Throwable $e){ $hex='#64748B'; $lab='UNKNOWN'; }
  $__st['ampv']=1; $__st['hex']=$hex; $__st['lab']=$lab; $__st['sub']=$sub; $__st['warn']=$warn; $__st['kz']=[
      ['l'=>'VOL 20D','v'=>($vol!==null?number_format($vol,1,',','.').'%':'-'),'w'=>($vol!==null&&$vol>20)],
      ['l'=>'VIX','v'=>($vix>0?number_format($vix,1,',','.'):'-'),'w'=>($vix>30||($vix>20&&$vix<=30))],
      ['l'=>'S&P / SMA200','v'=>($sma200!==null?($kurs<$sma200?'darunter':'dar&uuml;ber'):'-'),'w'=>($sma200!==null&&$kurs<$sma200)],
      ['l'=>'FROM ATH','v'=>number_format($dd,1,',','.').'%','w'=>($dd<-5)],
    ];
  @file_put_contents($__c,json_encode($__st));
}
$__amp=[$__st['hex']??'#64748B',$__st['lab']??'UNKNOWN'];
$__ampS=(string)($__st['sub']??'');
$__ampW=implode(' &middot; ',(array)($__st['warn']??[]));
$__n=number_format((int)$__st['n'],0,',','.');
$__tr=number_format((int)$__st['tr'],0,',','.');
ob_start();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>StockPilot AI · Your market compass. Honestly calculated.</title>
<meta name="description" content="StockPilot AI re-rates thousands of stocks every night across five disciplines. Every signal documented to the end. Free beta. Not investment advice.">
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="https://stockpilot.gemivo.de/">
<meta property="og:url" content="https://stockpilot.gemivo.de/">
<meta property="og:type" content="website"><meta property="og:site_name" content="Gemivo">
<script type="application/ld+json">{"@context": "https://schema.org", "@type": "SoftwareApplication", "name": "StockPilot AI", "url": "https://stockpilot.gemivo.de/", "description": "StockPilot AI re-rates thousands of stocks every night across five disciplines. Daily market brief at 10 a.m. Free beta. Not investment advice.", "applicationCategory": "Finanzen", "operatingSystem": "Web", "offers": {"@type": "Offer", "price": "0", "priceCurrency": "EUR"}, "publisher": {"@type": "Organization", "name": "Gemivo", "url": "https://gemivo.de/"}}</script>
<meta property="og:title" content="StockPilot AI · Your market compass. Honestly calculated.">
<meta property="og:description" content="AI ranking of thousands of stocks, openly documented signal history. Daily market brief in plain language. Free beta, no installation.">
<meta property="og:image" content="https://stockpilot.gemivo.de/assets/stockbot.jpg">
<link rel="icon" href="/assets/icons/stockbot.png">
<script>(function(){try{var t=localStorage.getItem('nlTheme');if(t==='light')document.documentElement.dataset.theme='light';}catch(e){}})();</script>
<style>
/* ============================================================
   STOCKPILOT · GEMIVO MARKETS TERMINAL
   Eine Design-Sprache: dunkles Broker-Terminal, Haarlinien,
   Gold-Akzente, Mono für Daten, Serif für Headlines.
   ============================================================ */
:root{
  color-scheme:dark;
  --bg:#070b14; --bg2:#0a101e;
  --panel:linear-gradient(178deg,rgba(14,20,36,.86),rgba(8,12,24,.72));
  --panel-solid:#0d1322;
  --ink:#e9edf6; --mut:#a9b3c9; --faint:#77829c; --dim:#59647f;
  --hair:rgba(255,255,255,.09); --hair2:rgba(255,255,255,.055);
  --gridline:rgba(255,255,255,.022);
  --gold:#c8a86a; --gold-tx:#e0c98f; --gold-hi:#f0d9a8;
  --up:#34d399; --dn:#f87171; --amber:#fbbf24;
  --chart-line:#e8cf9a; --chart-fill:#e0c98f; --vol:rgba(139,149,173,.16);
  --glow:rgba(200,168,106,.10);
  --panel-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 18px 44px rgba(3,6,14,.4);
  --serif:Georgia,'Times New Roman',ui-serif,serif;
  --mono:ui-monospace,'SF Mono',SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace;
  --sans:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;
}
:root[data-theme="light"]{
  color-scheme:light;
  --bg:#f1eee5; --bg2:#eae6d9;
  --panel:linear-gradient(178deg,#fcfbf7,#f7f4eb);
  --panel-solid:#fbfaf5;
  --ink:#171c28; --mut:#454d60; --faint:#697082; --dim:#8a8f9e;
  --hair:rgba(23,28,40,.18); --hair2:rgba(23,28,40,.10);
  --gridline:rgba(23,28,40,.045);
  --gold:#8a6a2f; --gold-tx:#7d6028; --gold-hi:#6e5323;
  --up:#177245; --dn:#b0453a; --amber:#8f6400;
  --chart-line:#9a7a34; --chart-fill:#a8843c; --vol:rgba(23,28,40,.12);
  --glow:rgba(138,106,47,.08);
  --panel-shadow:inset 0 1px 0 rgba(255,255,255,.8),0 14px 34px rgba(23,28,40,.10);
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
body{margin:0;background:var(--bg);color:var(--ink);line-height:1.6;font-family:var(--sans);
  -webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;overflow-x:hidden}
body::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
  background:
    radial-gradient(90% 55% at 18% -6%,var(--glow),transparent 62%),
    linear-gradient(var(--gridline) 1px,transparent 1px),
    linear-gradient(90deg,var(--gridline) 1px,transparent 1px);
  background-size:100% 100%,56px 56px,56px 56px}
main,header,footer{position:relative;z-index:1}
a{color:inherit;text-decoration:none}
img,svg,video{max-width:100%}
:focus-visible{outline:2px solid var(--gold-hi);outline-offset:2px;border-radius:3px}
.mono{font-family:var(--mono);font-variant-numeric:tabular-nums}
.serif{font-family:var(--serif)}
.wrap{max-width:1240px;margin:0 auto;padding:0 clamp(16px,3.4vw,44px)}
.up{color:var(--up)}.dn{color:var(--dn)}

/* ---------- Status-Zeile (echte Daten aus nl-status.json) ---------- */
.status{border-bottom:1px solid var(--hair2);background:var(--bg2)}
.status .wrap{display:flex;gap:10px 24px;align-items:center;flex-wrap:wrap;padding-top:8px;padding-bottom:8px;
  font-family:var(--mono);font-variant-numeric:tabular-nums;font-size:11px;letter-spacing:.1em}
.status .l{color:var(--dim)}
.status b{font-weight:600;color:var(--mut)}
.status .st-up{color:var(--up)}.status .st-warn{color:var(--amber)}.status .st-bad{color:var(--dn)}
.status a{margin-left:auto;color:var(--gold-tx);border-bottom:1px solid transparent;padding-bottom:1px;white-space:nowrap}
.status a:hover{border-color:var(--gold-tx)}
@media(max-width:640px){.status .wrap{gap:8px 14px;font-size:10px}.status a{margin-left:0}}

/* ---------- Masthead ---------- */
.mast{position:sticky;top:0;z-index:40;border-bottom:1px solid var(--hair);
  background:color-mix(in srgb,var(--bg) 82%,transparent);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)}
.mast .wrap{display:flex;align-items:center;gap:18px;padding-top:13px;padding-bottom:13px}
.brand{display:flex;align-items:center;gap:12px;min-width:0}
.brand img{width:38px;height:38px;border-radius:9px;border:1px solid var(--hair)}
.brand .k{display:block;font-family:var(--mono);font-size:9px;letter-spacing:.28em;color:var(--gold);font-weight:600}
.brand .n{display:block;font-family:var(--serif);font-size:20px;font-weight:700;letter-spacing:-.01em;line-height:1.15;color:var(--ink);white-space:nowrap}
.nav{display:flex;gap:20px;align-items:center;margin-left:auto;
  font-family:var(--mono);font-size:11px;letter-spacing:.14em;color:var(--faint)}
.nav a{padding:4px 0;border-bottom:1px solid transparent;transition:color .15s,border-color .15s;white-space:nowrap}
.nav a:hover{color:var(--gold-tx);border-color:var(--gold-tx)}
.mastr{display:flex;align-items:center;gap:12px;margin-left:22px}
.tbtn{background:transparent;border:1px solid var(--hair);color:var(--mut);width:34px;height:34px;border-radius:50%;
  cursor:pointer;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;transition:border-color .15s,color .15s}
.tbtn:hover{border-color:var(--gold);color:var(--gold-tx)}
@media(max-width:900px){.nav{display:none}.mastr{margin-left:auto}}
@media(max-width:480px){
  .mast .wrap{gap:8px;padding-top:10px;padding-bottom:10px}
  .brand{gap:9px}.brand img{width:31px;height:31px}
  .brand .k{font-size:8px;letter-spacing:.22em}.brand .n{font-size:16.5px}
  .mastr{gap:8px}.mastr .btn.sm{padding:8px 12px;font-size:10.5px}
  .tbtn{width:31px;height:31px}}

/* ---------- Buttons ---------- */
.btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--mono);font-size:12.5px;letter-spacing:.04em;
  font-weight:700;padding:13px 22px;border-radius:9px;border:1px solid var(--hair);color:var(--ink);
  cursor:pointer;transition:transform .15s,box-shadow .2s,border-color .15s,background .15s;white-space:nowrap}
.btn.ghost:hover{border-color:var(--gold);color:var(--gold-tx)}
.btn.gold{position:relative;overflow:hidden;border:1px solid rgba(240,217,168,.45);color:#241804;
  background:linear-gradient(172deg,#f2dcae,#dcb76d 52%,#c1974a);
  box-shadow:0 10px 26px rgba(200,155,70,.24),0 2px 6px rgba(200,155,70,.26),inset 0 1px 0 rgba(255,255,255,.55),inset 0 -1px 0 rgba(120,80,20,.35)}
.btn.gold::after{content:"";position:absolute;top:-40%;bottom:-40%;left:-70%;width:46%;
  background:linear-gradient(105deg,transparent,rgba(255,255,255,.55) 50%,transparent);transform:skewX(-18deg);transition:left .55s ease;pointer-events:none}
.btn.gold:hover{transform:translateY(-1px);box-shadow:0 14px 32px rgba(200,155,70,.34),0 3px 8px rgba(200,155,70,.28),inset 0 1px 0 rgba(255,255,255,.55),inset 0 -1px 0 rgba(120,80,20,.35)}
.btn.gold:hover::after{left:135%}
.btn.gold:active{transform:translateY(0)}
.btn.sm{padding:9px 16px;font-size:11.5px}

/* ---------- Ticker (Beispieldaten) ---------- */
.tick{border-bottom:1px solid var(--hair2);background:var(--bg2);position:relative;
  font-family:var(--mono);font-variant-numeric:tabular-nums;font-size:12px;white-space:nowrap;padding:8px 0;color:var(--mut)}
.tick-msk{overflow:hidden;mask-image:linear-gradient(90deg,transparent,#000 28px,#000 calc(100% - 150px),transparent calc(100% - 84px));
  -webkit-mask-image:linear-gradient(90deg,transparent,#000 28px,#000 calc(100% - 150px),transparent calc(100% - 84px))}
.tick-in{display:inline-block;animation:tickScroll 64s linear infinite;will-change:transform}
@keyframes tickScroll{to{transform:translateX(-50%)}}
.tick .u{color:var(--up)}.tick .d{color:var(--dn)}
.tick .sym{color:var(--gold-tx);font-weight:600}
.tick-demo{position:absolute;top:50%;transform:translateY(-50%);right:14px;font-size:9px;letter-spacing:.16em;color:var(--dim)}

/* ---------- Terminal-Chrome-Zeile (Marktstatus, live) ---------- */
.chrome{border-bottom:1px solid var(--hair);background:var(--bg2)}
.chrome .wrap{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding-top:9px;padding-bottom:9px;
  font-family:var(--mono);font-variant-numeric:tabular-nums;font-size:11px;letter-spacing:.14em;color:var(--faint)}
.chrome .tag{color:var(--gold);font-weight:600}
.chrome .live{display:inline-flex;align-items:center;gap:7px;color:var(--mut)}
.dot{width:6px;height:6px;border-radius:50%;background:var(--up);box-shadow:0 0 6px rgba(52,211,153,.8);animation:pulse 2.6s cubic-bezier(.4,0,.6,1) infinite}
.dot.closed{background:var(--amber);box-shadow:0 0 6px rgba(245,158,11,.7)}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.45}}
.chrome .cd{color:var(--dim)}
.chrome .clock{margin-left:auto;display:inline-flex;align-items:center;gap:14px;color:var(--dim)}
.chrome .clock b{font-weight:400;color:var(--mut)}
@media(max-width:640px){.chrome .clock span{display:none}.chrome .wrap{gap:10px;font-size:10px}}

/* ---------- Panels (die eine Material-Sprache) ---------- */
.panel{background:var(--panel);border:1px solid var(--hair);border-radius:12px;box-shadow:var(--panel-shadow);overflow:hidden}
.panel-h{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 16px;border-bottom:1px solid var(--hair2);
  font-family:var(--mono);font-size:10px;letter-spacing:.16em;color:var(--gold)}
.panel-h b{color:var(--dim);font-weight:400;letter-spacing:.14em;white-space:nowrap}

/* ---------- Sektions-Header ---------- */
.sh{display:flex;align-items:center;gap:14px;margin:clamp(48px,7vw,76px) 0 22px;scroll-margin-top:86px}
.sh .no{font-family:var(--mono);font-size:11px;letter-spacing:.2em;color:var(--gold);font-weight:600}
.sh h2{font-family:var(--mono);font-size:12px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;margin:0;color:var(--mut)}
.sh .ln{flex:1;border-top:1px solid var(--hair)}

/* ---------- Hero ---------- */
.hero{border-bottom:1px solid var(--hair);position:relative;overflow:hidden}
.hero::after{content:"";position:absolute;inset:auto 0 0 0;height:1px;
  background:linear-gradient(90deg,transparent,var(--gold) 30%,var(--gold-hi) 50%,var(--gold) 70%,transparent);opacity:.55;pointer-events:none}
.hero .wrap{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:clamp(28px,4vw,56px);align-items:center;
  padding-top:clamp(36px,6vw,72px);padding-bottom:clamp(36px,6vw,72px)}
.eyebrow{display:flex;align-items:center;gap:10px;margin:0 0 18px;font-family:var(--mono);font-size:10px;letter-spacing:.24em;color:var(--gold)}
.eyebrow .r{flex:0 0 30px;height:1px;background:linear-gradient(90deg,var(--gold),transparent)}
.hero h1{font-family:var(--serif);font-weight:400;font-size:clamp(34px,4.6vw,58px);line-height:1.06;letter-spacing:-.012em;margin:0;
  color:var(--ink);text-wrap:balance}
.hero h1 em{font-style:italic;color:var(--gold-tx)}
.hero .sub{font-size:16px;color:var(--mut);margin:18px 0 0;max-width:54ch;line-height:1.65}
.hero .ctas{display:flex;gap:12px;margin-top:28px;flex-wrap:wrap}
.hero .fine{font-family:var(--mono);font-size:11px;letter-spacing:.12em;color:var(--dim);margin-top:18px;line-height:1.9}
/* Video-Panel */
.shot{position:relative;text-align:center;min-width:0}
.phone{position:relative;width:min(288px,80vw);margin:0 auto;border-radius:26px;overflow:hidden;
  border:1px solid var(--hair);background:#000;
  box-shadow:0 0 0 1px rgba(200,168,106,.14),0 34px 90px rgba(2,5,12,.6),0 8px 26px rgba(2,5,12,.45)}
:root[data-theme="light"] .phone{box-shadow:0 0 0 1px rgba(138,106,47,.2),0 30px 70px rgba(23,28,40,.28)}
.phone::after{content:"";position:absolute;inset:0 0 auto 0;height:2px;
  background:linear-gradient(90deg,transparent,var(--gold) 30%,var(--gold-hi) 50%,var(--gold) 70%,transparent);opacity:.8;pointer-events:none}
.phone video{display:block;width:100%;height:auto;cursor:pointer}
.sndbtn{position:absolute;right:12px;bottom:12px;background:rgba(7,11,20,.85);color:#f0d9a8;border:1px solid rgba(240,217,168,.35);
  width:42px;height:42px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:border-color .15s}
.sndbtn:hover{border-color:#f0d9a8}
.shot .cap{font-family:var(--mono);font-size:10.5px;letter-spacing:.12em;color:var(--dim);margin-top:12px}

/* ---------- Kennzahlen-Band ---------- */
.band{border-bottom:1px solid var(--hair);background:var(--bg2)}
.band .wrap{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));padding:0}
.band .c{padding:22px 26px;border-left:1px solid var(--hair2);min-width:0}
.band .c:first-child{border-left:0}
.band .v{font-family:var(--mono);font-variant-numeric:tabular-nums;font-size:26px;font-weight:600;color:var(--ink);letter-spacing:.01em;white-space:nowrap}
.band .v.up{color:var(--up)}
.band .t{font-size:12px;color:var(--faint);margin-top:4px;line-height:1.5}
@media(max-width:860px){.band .wrap{grid-template-columns:1fr 1fr}
  .band .c{padding:16px 18px}.band .c:nth-child(3){border-left:0}.band .c:nth-child(n+3){border-top:1px solid var(--hair2)}
  .band .v{font-size:21px}}

/* ---------- Funktions-Karten ---------- */
.grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.fcard{padding:22px 24px 24px;display:flex;flex-direction:column;transition:border-color .2s,transform .2s}
.fcard:hover{border-color:rgba(200,168,106,.4)}
.fcard .ic{color:var(--gold);margin-bottom:14px}
.fcard h3{font-family:var(--serif);font-weight:400;font-size:19px;letter-spacing:-.01em;margin:0 0 8px;color:var(--ink)}
.fcard p{font-size:13.5px;color:var(--mut);margin:0;line-height:1.65}
.chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:16px}
.chip{font-family:var(--mono);font-size:9.5px;letter-spacing:.12em;color:var(--gold-tx);
  border:1px solid rgba(200,168,106,.32);border-radius:5px;padding:4px 8px;white-space:nowrap}
:root[data-theme="light"] .chip{border-color:rgba(138,106,47,.4)}
@media(max-width:900px){.grid3{grid-template-columns:1fr}}

/* ---------- Terminal-Sektion (market brief) ---------- */
.bnx-grid{display:grid;grid-template-columns:minmax(0,1.04fr) minmax(0,.96fr);gap:clamp(26px,3.4vw,40px);align-items:center}
.bnx-h{margin:0 0 14px;font-family:var(--serif);font-weight:400;font-size:clamp(30px,4.6vw,44px);line-height:1.08;letter-spacing:-.01em;color:var(--ink);text-wrap:balance}
.bnx-h em{font-style:italic;color:var(--gold-tx)}
.bnx-sub{margin:0 0 20px;max-width:52ch;font-size:15px;line-height:1.62;color:var(--mut)}
.bnx-form{display:flex;gap:10px;flex-wrap:wrap;max-width:560px}
.bnx-form input{flex:1 1 220px;min-width:0;padding:15px 17px;border-radius:9px;border:1px solid var(--hair);
  background:var(--bg2);color:var(--ink);font-size:16px;font-family:var(--sans);
  box-shadow:inset 0 2px 6px rgba(0,0,0,.25);transition:border-color .2s,box-shadow .2s}
:root[data-theme="light"] .bnx-form input{box-shadow:inset 0 2px 5px rgba(23,28,40,.08);background:#fff}
.bnx-form input::placeholder{color:var(--dim)}
.bnx-form input:hover{border-color:var(--gold)}
.bnx-form input:focus{outline:none;border-color:var(--gold);
  box-shadow:inset 0 2px 6px rgba(0,0,0,.2),0 0 0 3px rgba(200,168,106,.16),0 0 22px rgba(200,168,106,.10)}
.bnx-form .btn.gold{font-size:14px;padding:15px 26px}
.bnx-form .btn.gold:disabled{opacity:.6;cursor:wait;transform:none}
.bnx-msg{margin-top:10px;font-size:14px;font-weight:600;min-height:18px}
.bnx-track{margin:20px 0 0;max-width:560px;border-top:1px solid var(--hair2);padding-top:14px}
.bnx-track-cap{display:flex;align-items:center;gap:8px;margin:0 0 10px;font-family:var(--mono);font-size:9.5px;letter-spacing:.18em;color:var(--faint)}
.bnx-track-cap b{color:var(--gold);font-weight:600}
.bnx-track-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.bnx-tstat .n{font-family:var(--mono);font-variant-numeric:tabular-nums;font-size:17px;color:var(--ink);letter-spacing:.01em;white-space:nowrap}
.bnx-tstat .n.g{color:var(--up)}.bnx-tstat .n.r{color:var(--dn)}
.bnx-tstat .l{margin-top:3px;font-size:10px;line-height:1.4;letter-spacing:.05em;color:var(--faint)}
.bnx-fine{margin:18px 0 0;font-size:12px;line-height:1.6;color:var(--faint)}
.bnx-fine a{color:var(--gold-tx);border-bottom:1px solid rgba(200,168,106,.35);padding-bottom:1px}
.bnx-fine a:hover{color:var(--gold-hi);border-color:var(--gold-hi)}
/* Markt-Panel rechts */
.bnx-panel{display:flex;flex-direction:column;gap:16px;min-width:0}
.bnx-ch-head{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;padding:13px 16px 6px;font-family:var(--mono);font-variant-numeric:tabular-nums}
.bnx-ch-sym{font-size:11px;letter-spacing:.16em;color:var(--gold);font-weight:600}
.bnx-badge{font-size:9px;letter-spacing:.14em;color:var(--dim);border:1px solid var(--hair);border-radius:4px;padding:2px 6px;line-height:1.3}
.bnx-ch-px{margin-left:auto;font-size:19px;color:var(--ink)}
.bnx-ch-dx{font-size:12px;color:var(--up)}
.bnx-chart{position:relative;height:186px;margin:2px 0 0;cursor:crosshair}
.bnx-gl{position:absolute;left:0;right:0;height:1px;background:var(--hair2);pointer-events:none}
.bnx-gl i{position:absolute;right:6px;top:-13px;font-style:normal;font-family:var(--mono);font-variant-numeric:tabular-nums;font-size:9px;letter-spacing:.08em;color:var(--dim);pointer-events:none}
.bnx-base{position:absolute;left:0;right:0;height:0;border-top:1px dashed rgba(200,168,106,.35);pointer-events:none}
.bnx-base i{position:absolute;left:6px;top:-13px;font-style:normal;font-family:var(--mono);font-variant-numeric:tabular-nums;font-size:9px;letter-spacing:.1em;color:var(--gold-tx);opacity:.75;pointer-events:none}
.bnx-chart svg{position:absolute;inset:0;width:100%;height:100%;display:block}
.bnx-vol rect{fill:var(--vol)}
.bnx-line{stroke:var(--chart-line)}
#bnxFill stop{stop-color:var(--chart-fill)}
.bnx-pill{position:absolute;right:0;top:23%;transform:translateY(-50%);font-family:var(--mono);font-variant-numeric:tabular-nums;
  font-size:10px;letter-spacing:.04em;color:#241804;background:linear-gradient(172deg,#f0d9a8,#d9b46a);
  padding:3px 7px 3px 9px;border-radius:5px 0 0 5px;box-shadow:0 2px 10px rgba(200,155,70,.35),inset 0 1px 0 rgba(255,255,255,.5);pointer-events:none}
.bnx-pill::before{content:"";position:absolute;left:-5px;top:50%;margin-top:-4px;border:4px solid transparent;border-right:5px solid #ecd29c}
.bnx-lastdot{position:absolute;right:-2px;top:23%;width:7px;height:7px;margin-top:-3.5px;border-radius:50%;background:var(--gold-hi);
  box-shadow:0 0 0 3px rgba(240,217,168,.16),0 0 12px rgba(240,217,168,.5);animation:glowDot 2.6s ease-in-out infinite}
@keyframes glowDot{0%,100%{box-shadow:0 0 0 3px rgba(240,217,168,.16),0 0 12px rgba(240,217,168,.5)}50%{box-shadow:0 0 0 5px rgba(240,217,168,.07),0 0 16px rgba(240,217,168,.65)}}
.bnx-xh{position:absolute;top:0;bottom:22px;width:1px;background:rgba(200,168,106,.4);opacity:0;pointer-events:none;transition:opacity .15s}
.bnx-xh i{position:absolute;bottom:-19px;left:50%;transform:translateX(-50%);font-style:normal;font-family:var(--mono);font-variant-numeric:tabular-nums;
  font-size:9px;letter-spacing:.08em;color:var(--gold-tx);background:var(--panel-solid);border:1px solid var(--hair2);padding:1px 5px;border-radius:3px;white-space:nowrap}
.bnx-ch-axis{display:flex;justify-content:space-between;padding:6px 12px 10px;font-family:var(--mono);font-variant-numeric:tabular-nums;
  font-size:9.5px;letter-spacing:.1em;color:var(--dim);border-top:1px solid var(--hair2)}
@media(prefers-reduced-motion:no-preference){
  .bnx-line{stroke-dasharray:1;stroke-dashoffset:1;animation:chDraw 1.5s cubic-bezier(.4,0,.2,1) .15s forwards}
  @keyframes chDraw{to{stroke-dashoffset:0}}
  .bnx-area{opacity:0;animation:chFade .9s ease .75s forwards}
  @keyframes chFade{to{opacity:1}}
  .bnx-vol{transform:scaleY(0);transform-origin:0 188px;animation:chRise .8s cubic-bezier(.2,.7,.3,1) .4s forwards}
  @keyframes chRise{to{transform:scaleY(1)}}
  .bnx-pill,.bnx-lastdot{opacity:0;animation:chFade .5s ease 1.45s forwards}
}
/* Watchlist */
.bnx-wl{font-family:var(--mono);font-variant-numeric:tabular-nums}
.bnx-q{display:grid;grid-template-columns:minmax(0,1fr) 88px 84px 74px;gap:12px;align-items:center;padding:9px 14px;
  border-bottom:1px solid var(--hair2);font-size:12.5px;transition:background .15s}
.bnx-q:hover{background:rgba(200,168,106,.05)}
.bnx-q:last-child{border-bottom:0}
.bnx-q .s{color:var(--ink);font-weight:600;letter-spacing:.04em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bnx-q .s small{display:block;font-weight:400;font-size:9.5px;letter-spacing:.1em;color:var(--dim);margin-top:1px}
.bnx-q svg{display:block;width:88px;height:26px}
.bnx-q svg .ln-u{stroke:var(--up)}.bnx-q svg .ln-d{stroke:var(--dn)}
.bnx-q svg .ar-u{fill:rgba(52,211,153,.09)}.bnx-q svg .ar-d{fill:rgba(248,113,113,.09)}
:root[data-theme="light"] .bnx-q svg .ar-u{fill:rgba(23,114,69,.10)}
:root[data-theme="light"] .bnx-q svg .ar-d{fill:rgba(176,69,58,.10)}
.bnx-q .p{text-align:right;color:var(--mut)}
.bnx-q .c{text-align:right;white-space:nowrap}
.bnx-q .c.u{color:var(--up)}.bnx-q .c.d{color:var(--dn)}
/* Briefing-Streifen */
.bnx-strip{margin-top:16px}
.bnx-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));font-family:var(--mono);font-variant-numeric:tabular-nums}
.bnx-tile{padding:13px 16px 14px;border-left:1px solid var(--hair2);min-width:0}
.bnx-tile:first-child{border-left:0}
.bnx-tile .k{font-size:9.5px;letter-spacing:.16em;color:var(--faint);margin-bottom:6px}
.bnx-tile .v{font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bnx-tile .v.amber{color:var(--amber)}
.bnx-tile .v.gold{color:var(--gold-tx);font-style:italic;font-family:var(--serif)}
.bnx-foot{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-top:16px;
  font-family:var(--serif);font-size:12.5px;font-style:italic;color:var(--faint)}
.bnx-rule{flex:0 0 26px;height:1px;background:rgba(200,168,106,.4)}
@media(max-width:960px){.bnx-grid{grid-template-columns:minmax(0,1fr)}
  .bnx-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}
  .bnx-tile:nth-child(3){border-left:0}.bnx-tile:nth-child(n+3){border-top:1px solid var(--hair2)}}
@media(max-width:560px){
  .bnx-ch-head{gap:8px;padding:11px 12px 4px}.bnx-ch-sym{font-size:10px;letter-spacing:.12em}
  .bnx-badge{font-size:8px;padding:2px 5px}.bnx-ch-px{font-size:16px}.bnx-chart{height:150px}
  .bnx-track-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 12px}.bnx-tstat .n{font-size:15px}
  .bnx-q{grid-template-columns:minmax(0,1fr) 56px 72px 60px;gap:8px;padding:9px 11px;font-size:11.5px}
  .bnx-q svg{width:56px}
  .bnx-tile{padding:11px 12px 12px}.bnx-tile .v{white-space:normal;overflow:visible;text-overflow:clip;line-height:1.45}
  .panel-h{font-size:9px;letter-spacing:.1em}.panel-h span,.panel-h b{white-space:nowrap}
  .bnx-foot{font-size:11.5px;gap:10px}.bnx-rule{flex-basis:16px}}
@media(max-width:400px){.bnx-q{grid-template-columns:minmax(0,1fr) 48px 66px 56px;gap:6px}.bnx-q svg{width:48px}}

/* ---------- Galerie ---------- */
.gal{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.gal figure{margin:0;display:flex;flex-direction:column}
.gal .shotwrap{background:var(--bg2)}
.gal img{width:100%;aspect-ratio:9/14;object-fit:cover;object-position:top;display:block}
.gal figcaption{font-size:12.5px;color:var(--faint);padding:12px 16px 14px;line-height:1.55;border-top:1px solid var(--hair2)}
@media(max-width:860px){.gal{grid-template-columns:1fr}}

/* ---------- Bilanz ---------- */
.trust{display:grid;grid-template-columns:auto minmax(0,1fr);gap:clamp(20px,4vw,44px);align-items:center;padding:26px 30px}
.trust .ev{font-family:var(--serif);font-size:clamp(38px,5vw,52px);font-weight:400;color:var(--up);white-space:nowrap;line-height:1}
.trust .ev b{font-weight:400}
.trust .ev span{display:block;font-family:var(--mono);font-size:10px;color:var(--faint);letter-spacing:.18em;font-weight:500;margin-top:8px}
.trust p{margin:0;font-size:13.5px;color:var(--mut);max-width:90ch;line-height:1.7}
@media(max-width:700px){.trust{grid-template-columns:1fr;gap:16px;padding:20px 18px}}

/* ---------- Preis ---------- */
.price{display:flex;gap:clamp(20px,4vw,40px);align-items:center;flex-wrap:wrap;padding:26px 30px}
.price .p{font-family:var(--serif);font-size:clamp(36px,5vw,46px);font-weight:400;color:var(--ink);line-height:1;white-space:nowrap}
.price .p small{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.18em;color:var(--gold);margin-top:8px}
.price p{margin:0;font-size:13.5px;color:var(--mut);max-width:62ch;flex:1 1 320px;line-height:1.7}
@media(max-width:700px){.price{padding:20px 18px}}

/* ---------- FAQ ---------- */
.faq details{border-top:1px solid var(--hair2);padding:15px 4px}
.faq details:last-child{border-bottom:1px solid var(--hair2)}
.faq summary{cursor:pointer;font-family:var(--serif);font-weight:400;font-size:16.5px;list-style:none;display:flex;justify-content:space-between;gap:14px;color:var(--ink)}
.faq summary::-webkit-details-marker{display:none}
.faq summary::after{content:"+";color:var(--gold);font-family:var(--mono);font-weight:600;flex:0 0 auto}
.faq details[open] summary::after{content:"−"}
.faq summary:hover{color:var(--gold-tx)}
.faq p{margin:10px 0 0;font-size:13.5px;color:var(--mut);max-width:90ch;line-height:1.7}

/* ---------- Footer ---------- */
.foot{margin-top:clamp(48px,7vw,80px);border-top:1px solid var(--hair);padding:26px 0 48px;font-size:12px;color:var(--dim);line-height:1.75}
.foot .brandline{font-family:var(--serif);font-size:14px;color:var(--mut);margin-bottom:10px}
.foot b{color:var(--mut);font-weight:600}
.foot a{color:var(--faint);border-bottom:1px solid var(--hair);padding-bottom:1px}
.foot a:hover{color:var(--gold-tx);border-color:var(--gold-tx)}
.foot .links{margin-top:14px;font-family:var(--mono);font-size:11px;letter-spacing:.08em}

/* ---------- Responsive Hero ---------- */
@media(max-width:960px){.hero .wrap{grid-template-columns:1fr}
  .phone{width:min(260px,74vw)}}
@media(prefers-reduced-motion:reduce){.tick-in,.dot,.bnx-lastdot{animation:none}}

/* Marktampel-Panel (11.11.) */
.spamp-dot{width:22px;height:22px;border-radius:50%;position:relative;flex:none}
.spamp-dot::after{content:"";position:absolute;inset:-7px;border-radius:50%;border:1.5px solid currentColor;opacity:.55;animation:spampPulse 2.4s ease-out infinite}
@keyframes spampPulse{0%{transform:scale(.65);opacity:.6}75%{transform:scale(1.5);opacity:0}100%{transform:scale(1.5);opacity:0}}
@media (prefers-reduced-motion:reduce){.spamp-dot::after{animation:none;opacity:.25}}
.spamp-stage{font-size:clamp(21px,2.7vw,27px);font-weight:800;letter-spacing:.05em;line-height:1}
.spamp-grid{display:flex;gap:8px;flex-wrap:wrap}
.spamp-kz{font-family:var(--mono);font-size:9.5px;letter-spacing:.12em;color:var(--mut);border:1px solid var(--hair);border-radius:6px;padding:6px 10px;display:flex;gap:8px;align-items:baseline;background:rgba(255,255,255,.02)}
.spamp-kz b{color:var(--ink);font-size:12.5px;letter-spacing:.02em;font-weight:600}
.spamp-kz.w b{color:#f0b357}
.spamp-sig{font-family:var(--mono);font-size:10px;letter-spacing:.14em;color:var(--gold-tx);text-transform:uppercase}
.spamp-claim{margin:8px 0 0;font-size:15px;line-height:1.55;color:var(--ink);max-width:640px}
.spamp-claim em{color:var(--gold-tx);font-style:normal}

/* Language switch */
.langsw{position:absolute;top:14px;right:clamp(16px,3.4vw,44px);z-index:50;display:flex;gap:0;border:1px solid rgba(200,168,106,.32);border-radius:6px;overflow:hidden;font-family:var(--mono);font-size:10px;letter-spacing:.14em}
.langsw a{padding:5px 10px;color:var(--gold-tx);text-decoration:none}
.langsw a.on{background:rgba(200,168,106,.18);color:var(--gold-hi)}
.langsw a:not(.on):hover{background:rgba(255,255,255,.05)}
@media(max-width:640px){.langsw{top:10px;right:14px}}
header{position:relative}
</style>
<link rel="alternate" hreflang="en" href="https://stockpilot.gemivo.de/en/"><link rel="alternate" hreflang="de" href="https://stockpilot.gemivo.de/"></head>
<body>

<header>
<div class="langsw" aria-label="Language"><a href="/">DE</a><a class="on" href="/en/">EN</a></div>
  <!-- Live-Statuszeile: echte Daten aus nl-status.json -->
  <div class="status">
    <div class="wrap" id="status">
      <span class="l">TODAY'S BRIEFING</span>
      <span id="st-markt"><span class="l">MARKET STATE</span> <b>…</b></span>
      <span id="st-fragil"><span class="l">FUNDAMENTALS</span> <b>…</b></span>
      <span id="st-trump"><span class="l">POLITICS</span> <b>…</b></span>
      <a href="/nl-public.html">Read today's edition&nbsp;→</a>
    </div>
  </div>

  <!-- Masthead -->
  <div class="mast">
    <div class="wrap">
      <a class="brand" href="/">
        <img src="/assets/icons/stockbot.png" alt="StockPilot">
        <span><span class="k">GE·MI·VO&nbsp;MARKETS</span><span class="n">StockPilot&nbsp;AI</span></span>
      </a>
      <nav class="nav" aria-label="Hauptnavigation">
        <a href="#funktionen">FEATURES</a>
        <a href="#briefing">MARKET BRIEF</a>
        <a href="#einblicke">APP</a>
        <a href="#bilanz">TRACK RECORD</a>
        <a href="#preis">PRICING</a>
        <a href="#faq">FAQ</a>
      </nav>
      <div class="mastr">
        <a class="btn gold sm" href="https://gemivo.de/konto/login.php?login=1&next=https%3A%2F%2Fstockbot.gemivo.de%2F">Open app</a>
        <button id="themeBtn" class="tbtn" aria-label="Hell- oder Dunkelmodus">
          <svg id="ic-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>
          <svg id="ic-sun" style="display:none" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Terminal-Chrome: Marktstatus + Berlin-Uhr (live gerechnet) -->
  <div class="chrome">
    <div class="wrap">
      <span class="tag">GEMIVO&nbsp;MARKETS</span>
      <span class="live"><span class="dot" id="bnxDot"></span><span id="bnxStatus">XETRA</span></span>
      <span class="cd" id="bnxCd"></span>
      <span class="clock"><span>NYSE&nbsp;·&nbsp;XETRA&nbsp;·&nbsp;NASDAQ</span><b id="bnxClock"></b></span>
    </div>
  </div>

  <!-- Ticker (Beispieldaten, klar markiert) -->
  <div class="tick" aria-hidden="true">
    <div class="tick-msk"><div class="tick-in" id="bnxTick"></div></div>
    <span class="tick-demo" data-bx="demo">BEISPIEL</span>
  </div>
</header>

<main>

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="wrap">
    <div>
      <p class="eyebrow"><span class="r"></span>AI STOCK ANALYSIS · MADE IN GERMANY</p>
      <h1>Your market compass.<br><em>Honestly calculated.</em></h1>
      <p class="sub">Every night StockPilot AI rates thousands of stocks across five disciplines. Every signal is documented to the very end, nothing gets deleted. Not investment advice.</p>
<section class="panel" style="margin:22px 0 10px;max-width:860px">
<div class="panel-h"><span>MARKET PULSE · LIVE FROM THE SCREENING</span><span><?=date('d.m., H:i')?> UHR</span></div>
<div style="padding:18px 20px 16px;display:flex;align-items:center;gap:18px;flex-wrap:wrap">
  <span class="spamp-dot" style="background:<?=htmlspecialchars($__amp[0])?>;color:<?=htmlspecialchars($__amp[0])?>"></span>
  <span class="spamp-stage" style="color:<?=htmlspecialchars($__amp[0])?>"><?=htmlspecialchars($__amp[1])?></span>
  <div class="spamp-grid"><?php foreach((array)($__st['kz']??[]) as $kz): ?><span class="spamp-kz<?=!empty($kz['w'])?' w':''?>"><?=htmlspecialchars($kz['l'])?> <b><?=$kz['v']?></b></span><?php endforeach; ?></div>
  <div style="flex-basis:100%;border-top:1px solid var(--hair2);margin-top:14px;padding-top:12px">
    <?php if($__ampW): ?><div class="spamp-sig">Signals: <?=$__ampW?></div><?php endif; ?>
    <p class="spamp-claim">The right strategy for every market state: <em>StockPilot keeps developing them and always has them ready.</em></p>
  </div>
</div>
</section>

      <div class="ctas">
        <a class="btn gold" href="https://gemivo.de/konto/login.php?login=1&next=https%3A%2F%2Fstockbot.gemivo.de%2F">Start free&nbsp;→</a>
        <a class="btn ghost" href="/nl-public.html">Today's market briefing</a>
      </div>
      <p class="fine">WEB APP, NO INSTALL · SERVERS IN GERMANY · ANDROID APP IN PREPARATION</p>
    </div>
    <div class="shot">
      <div class="phone">
        <video id="reel" src="/assets/stockpilot_reel.mp4" poster="/stockpilot/assets/reel_poster.jpg" autoplay muted loop playsinline></video>
        <button class="sndbtn" id="sndbtn" aria-label="Ton an/aus">
          <svg id="ic-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H2v6h4l5 4z"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
          <svg id="ic-on" style="display:none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H2v6h4l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9.5 9.5 0 0 1 0 13"/></svg>
        </button>
      </div>
      <p class="cap">STOCKPILOT IN 30 SECONDS · SOUND ON</p>
    </div>
  </div>
</section>

<!-- ============ KENNZAHLEN-BAND ============ -->
<section class="band" aria-label="Kennzahlen">
  <div class="wrap">
    <div class="c"><div class="v">1&#8239;800</div><div class="t">stocks re-rated every night</div></div>
    <div class="c"><div class="v up">10:00</div><div class="t">a.m.: market briefing every trading day</div></div>
    <div class="c"><div class="v" id="st-n">1&#8239;445</div><div class="t" id="st-nstand">signals documented to the end, as of 09/2026</div></div>
    <div class="c"><div class="v">10:00</div><div class="t">a.m.: daily market brief in plain language</div></div>
  </div>
</section>

<div class="wrap">

  <!-- ============ 01 FEATURES ============ -->
  <div class="sh" id="funktionen"><span class="no">01</span><h2>What makes StockPilot different</h2><span class="ln"></span></div>
  <div class="grid3">
    <div class="panel fcard">
      <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 15.5a8 8 0 1 1 15 0"/><path d="M12 15l4.2-4.6"/></svg></div>
      <h3>StockPilot AI instead of gut feeling</h3>
      <p>Five disciplines per stock: they produce grades from A+ to F and a daily ranking, cross-checked against professional analyst price targets.</p>
      <div class="chips" aria-label="Die fünf Disziplinen">
        <span class="chip">VALUATION</span><span class="chip">GROWTH</span><span class="chip">PROFITABILITY</span><span class="chip">MOMENTUM</span><span class="chip">ANALYST TREND</span>
      </div>
    </div>
    <div class="panel fcard">
      <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.6-3.1 8-7.5 9.5-4.4-1.5-7.5-4.9-7.5-9.5V6z"/><path d="M8.8 12l2.3 2.3 4.2-4.3"/></svg></div>
      <h3>Only what you can actually buy</h3>
      <p>Many US tools recommend stocks you can barely trade in Europe. StockPilot checks every name daily for real tradability in euros. If it fails the test, it never gets recommended. Period.</p>
    </div>
    <div class="panel fcard">
      <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M6 6l12 12"/></svg></div>
      <h3>Warns you instead of just selling</h3>
      <p>A portfolio coach with clear to-dos, "stay away" warnings for weak balance sheets, and a market status that tells you when holding back beats buying. That is part of an honest picture, too.</p>
    </div>
  </div>

  <!-- ============ 02 MARKET BRIEF (Terminal) ============ -->
  <div class="sh" id="briefing"><span class="no">02</span><h2>The daily market brief</h2><span class="ln"></span></div>
  <section id="boersen-nl" aria-label="GEMIVO Markets Briefing">
    <div class="bnx-grid">
      <div class="bnx-main">
        <p class="eyebrow"><span class="r"></span><span data-bx="eyebrow">THE&nbsp;DAILY&nbsp;MARKET BRIEFING</span></p>
        <h3 class="bnx-h" data-bx-html="h">The market.<br><em>In three minutes.</em></h3>
        <p class="bnx-sub" data-bx="sub">Every trading day at 10 a.m. in your inbox: the state of the market in one word, plain-language takeaways and notable names with reasons. Understandable for beginners, with numbers for pros.</p>
        <form id="bnxForm" class="bnx-form" novalidate>
          <input type="email" id="bnxEmail" required autocomplete="email" inputmode="email" placeholder="deine@email.de" aria-label="E-Mail-Adresse">
          <button type="submit" id="bnxBtn" class="btn gold" data-bx="btn">Get the briefing</button>
        </form>
        <div id="bnxMsg" class="bnx-msg" role="status" aria-live="polite"></div>
        <div class="bnx-track">
          <p class="bnx-track-cap"><b data-bx="tr_cap1">SIGNAL HISTORY</b><span data-bx="tr_cap2">SINCE MAY 2026 · AS OF SEPT 2026</span></p>
          <div class="bnx-track-row">
            <div class="bnx-tstat"><div class="n" id="st-n2">1&#8239;445</div><div class="l" data-bx="tr1">signals documented to the end</div></div>
            <div class="bnx-tstat"><div class="n">1&#8239;800</div><div class="l" data-bx="tr2">stocks screened every night</div></div>
            <div class="bnx-tstat"><div class="n">5</div><div class="l" data-bx="tr3">disciplines per stock</div></div>
            <div class="bnx-tstat"><div class="n">0&nbsp;€</div><div class="l" data-bx="tr4">in beta</div></div>
          </div>
        </div>
        <p class="bnx-fine" data-bx-html="fine">Click the confirmation e-mail, done. Unsubscribe anytime, no data sharing. Not investment advice. <a href="/boersen-news.php">Learn more&nbsp;→</a> · <a href="/nl-public.html">Today's edition&nbsp;→</a></p>
      </div>
      <div class="bnx-panel" aria-label="Illustrative Marktübersicht (Beispieldaten)">
        <div class="panel">
          <div class="bnx-ch-head">
            <span class="bnx-ch-sym">DAX&nbsp;·&nbsp;INTRADAY</span>
            <span class="bnx-badge" data-bx="demo2">BEISPIEL</span>
            <span class="bnx-ch-px" id="bnxPx">24&#8239;186,42</span>
            <span class="bnx-ch-dx">▲&nbsp;+0,31&#8239;%</span>
          </div>
          <div class="bnx-chart" id="bnxChart" aria-hidden="true">
            <div class="bnx-gl" style="top:8.1%"><i>24&#8239;240</i></div>
            <div class="bnx-gl" style="top:30.4%"><i>24&#8239;160</i></div>
            <div class="bnx-gl" style="top:52.8%"><i>24&#8239;080</i></div>
            <div class="bnx-gl" style="top:75.1%"><i>24&#8239;000</i></div>
            <div class="bnx-base" style="top:43.9%"><i data-bx="prev">VORTAG&nbsp;24&#8239;111,72</i></div>
            <svg viewBox="0 0 560 190" preserveAspectRatio="none" role="presentation">
              <defs>
                <linearGradient id="bnxFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0" stop-opacity=".30"/>
                  <stop offset=".5" stop-opacity=".08"/>
                  <stop offset="1" stop-opacity="0"/>
                </linearGradient>
              </defs>
              <g class="bnx-vol"><rect x="1.4" y="166.3" width="9.6" height="21.7"/><rect x="13.8" y="168.2" width="9.6" height="19.8"/><rect x="26.3" y="176.1" width="9.6" height="11.9"/><rect x="38.7" y="163.8" width="9.6" height="24.2"/><rect x="51.2" y="162.0" width="9.6" height="26.0"/><rect x="63.6" y="176.3" width="9.6" height="11.7"/><rect x="76.1" y="175.4" width="9.6" height="12.6"/><rect x="88.5" y="166.7" width="9.6" height="21.3"/><rect x="101.0" y="177.7" width="9.6" height="10.3"/><rect x="113.4" y="172.3" width="9.6" height="15.7"/><rect x="125.8" y="175.2" width="9.6" height="12.8"/><rect x="138.3" y="178.2" width="9.6" height="9.8"/><rect x="150.7" y="172.7" width="9.6" height="15.3"/><rect x="163.2" y="181.7" width="9.6" height="6.3"/><rect x="175.6" y="179.0" width="9.6" height="9.0"/><rect x="188.1" y="174.3" width="9.6" height="13.7"/><rect x="200.5" y="176.6" width="9.6" height="11.4"/><rect x="213.0" y="177.8" width="9.6" height="10.2"/><rect x="225.4" y="181.6" width="9.6" height="6.4"/><rect x="237.8" y="176.7" width="9.6" height="11.3"/><rect x="250.3" y="181.1" width="9.6" height="6.9"/><rect x="262.7" y="178.9" width="9.6" height="9.1"/><rect x="275.2" y="178.5" width="9.6" height="9.5"/><rect x="287.6" y="179.7" width="9.6" height="8.3"/><rect x="300.1" y="183.3" width="9.6" height="4.7"/><rect x="312.5" y="177.5" width="9.6" height="10.5"/><rect x="325.0" y="176.6" width="9.6" height="11.4"/><rect x="337.4" y="178.5" width="9.6" height="9.5"/><rect x="349.8" y="175.0" width="9.6" height="13.0"/><rect x="362.3" y="179.7" width="9.6" height="8.3"/><rect x="374.7" y="176.1" width="9.6" height="11.9"/><rect x="387.2" y="173.5" width="9.6" height="14.5"/><rect x="399.6" y="174.5" width="9.6" height="13.5"/><rect x="412.1" y="178.6" width="9.6" height="9.4"/><rect x="424.5" y="170.6" width="9.6" height="17.4"/><rect x="437.0" y="174.0" width="9.6" height="14.0"/><rect x="449.4" y="168.0" width="9.6" height="20.0"/><rect x="461.8" y="177.8" width="9.6" height="10.2"/><rect x="474.3" y="175.1" width="9.6" height="12.9"/><rect x="486.7" y="166.0" width="9.6" height="22.0"/><rect x="499.2" y="177.1" width="9.6" height="10.9"/><rect x="511.6" y="169.3" width="9.6" height="18.7"/><rect x="524.1" y="176.0" width="9.6" height="12.0"/><rect x="536.5" y="162.0" width="9.6" height="26.0"/><rect x="549.0" y="173.1" width="9.6" height="14.9"/></g>
              <path class="bnx-area" fill="url(#bnxFill)" d="M0.0,83.4L6.2,83.9L12.4,85.5L18.7,87.5L24.9,88.5L31.1,88.7L37.3,90.6L43.6,96.1L49.8,103.7L56.0,111.0L62.2,116.3L68.4,119.3L74.7,121.1L80.9,123.1L87.1,126.0L93.3,129.1L99.6,131.8L105.8,134.1L112.0,135.3L118.2,134.8L124.4,132.9L130.7,129.3L136.9,124.8L143.1,121.8L149.3,122.0L155.6,123.5L161.8,122.6L168.0,116.9L174.2,109.7L180.4,106.1L186.7,104.8L192.9,102.7L199.1,101.5L205.3,102.2L211.6,103.4L217.8,105.2L224.0,107.7L230.2,109.9L236.4,111.2L242.7,111.3L248.9,109.0L255.1,104.7L261.3,99.6L267.6,95.3L273.8,93.9L280.0,95.0L286.2,96.0L292.4,96.8L298.7,97.8L304.9,97.9L311.1,97.4L317.3,97.4L323.6,96.3L329.8,92.5L336.0,86.8L342.2,82.1L348.4,79.6L354.7,77.8L360.9,75.7L367.1,73.3L373.3,70.1L379.6,66.0L385.8,63.0L392.0,62.3L398.2,63.6L404.4,66.5L410.7,68.9L416.9,69.6L423.1,69.8L429.3,68.5L435.6,64.1L441.8,59.3L448.0,56.2L454.2,53.4L460.4,50.1L466.7,47.8L472.9,46.6L479.1,43.8L485.3,39.6L491.6,36.6L497.8,35.3L504.0,35.7L510.2,38.4L516.4,42.1L522.7,44.7L528.9,45.7L535.1,45.7L541.3,44.9L547.6,43.6L553.8,43.0L560.0,43.7L560,190L0,190Z"/>
              <path class="bnx-line" pathLength="1" fill="none" stroke-width="1.8" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" d="M0.0,83.4L6.2,83.9L12.4,85.5L18.7,87.5L24.9,88.5L31.1,88.7L37.3,90.6L43.6,96.1L49.8,103.7L56.0,111.0L62.2,116.3L68.4,119.3L74.7,121.1L80.9,123.1L87.1,126.0L93.3,129.1L99.6,131.8L105.8,134.1L112.0,135.3L118.2,134.8L124.4,132.9L130.7,129.3L136.9,124.8L143.1,121.8L149.3,122.0L155.6,123.5L161.8,122.6L168.0,116.9L174.2,109.7L180.4,106.1L186.7,104.8L192.9,102.7L199.1,101.5L205.3,102.2L211.6,103.4L217.8,105.2L224.0,107.7L230.2,109.9L236.4,111.2L242.7,111.3L248.9,109.0L255.1,104.7L261.3,99.6L267.6,95.3L273.8,93.9L280.0,95.0L286.2,96.0L292.4,96.8L298.7,97.8L304.9,97.9L311.1,97.4L317.3,97.4L323.6,96.3L329.8,92.5L336.0,86.8L342.2,82.1L348.4,79.6L354.7,77.8L360.9,75.7L367.1,73.3L373.3,70.1L379.6,66.0L385.8,63.0L392.0,62.3L398.2,63.6L404.4,66.5L410.7,68.9L416.9,69.6L423.1,69.8L429.3,68.5L435.6,64.1L441.8,59.3L448.0,56.2L454.2,53.4L460.4,50.1L466.7,47.8L472.9,46.6L479.1,43.8L485.3,39.6L491.6,36.6L497.8,35.3L504.0,35.7L510.2,38.4L516.4,42.1L522.7,44.7L528.9,45.7L535.1,45.7L541.3,44.9L547.6,43.6L553.8,43.0L560.0,43.7"/>
            </svg>
            <span class="bnx-xh" id="bnxXh"><i id="bnxXhT">12:00</i></span>
            <span class="bnx-lastdot"></span>
            <span class="bnx-pill">24&#8239;186,42</span>
          </div>
          <div class="bnx-ch-axis"><span>09:00</span><span>11:00</span><span>13:00</span><span>15:00</span><span>17:30</span></div>
        </div>
        <div class="panel bnx-wl">
          <div class="panel-h"><span>WATCHLIST</span><b data-bx="demo3">SAMPLE VIEW</b></div>
          <div class="bnx-q" aria-hidden="true">
            <span class="s">SAP<small>XETRA</small></span>
            <svg viewBox="0 0 96 28" preserveAspectRatio="none" role="presentation"><path class="ar-u" d="M0.0,23.5L3.7,24.5L7.4,22.8L11.1,21.7L14.8,22.9L18.5,23.3L22.2,21.3L25.8,18.8L29.5,18.2L33.2,19.4L36.9,18.6L40.6,15.5L44.3,12.9L48.0,11.0L51.7,10.5L55.4,11.0L59.1,9.1L62.8,6.6L66.5,7.5L70.2,8.9L73.8,8.0L77.5,6.0L81.2,4.9L84.9,4.7L88.6,4.1L92.3,3.5L96.0,3.6L96,28L0,28Z"/><path class="ln-u" fill="none" stroke-width="1.4" vector-effect="non-scaling-stroke" stroke-linejoin="round" d="M0.0,23.5L3.7,24.5L7.4,22.8L11.1,21.7L14.8,22.9L18.5,23.3L22.2,21.3L25.8,18.8L29.5,18.2L33.2,19.4L36.9,18.6L40.6,15.5L44.3,12.9L48.0,11.0L51.7,10.5L55.4,11.0L59.1,9.1L62.8,6.6L66.5,7.5L70.2,8.9L73.8,8.0L77.5,6.0L81.2,4.9L84.9,4.7L88.6,4.1L92.3,3.5L96.0,3.6"/></svg>
            <span class="p">247,85</span><span class="c u">▲&nbsp;+0,8&#8239;%</span>
          </div>
          <div class="bnx-q" aria-hidden="true">
            <span class="s">NVDA<small>NASDAQ</small></span>
            <svg viewBox="0 0 96 28" preserveAspectRatio="none" role="presentation"><path class="ar-u" d="M0.0,24.0L3.7,20.9L7.4,23.4L11.1,23.9L14.8,23.7L18.5,24.5L22.2,23.7L25.8,22.0L29.5,19.8L33.2,16.9L36.9,15.0L40.6,14.0L44.3,12.6L48.0,12.2L51.7,12.6L55.4,13.1L59.1,14.3L62.8,13.7L66.5,10.5L70.2,7.9L73.8,6.2L77.5,6.4L81.2,8.2L84.9,8.5L88.6,6.4L92.3,3.5L96.0,4.0L96,28L0,28Z"/><path class="ln-u" fill="none" stroke-width="1.4" vector-effect="non-scaling-stroke" stroke-linejoin="round" d="M0.0,24.0L3.7,20.9L7.4,23.4L11.1,23.9L14.8,23.7L18.5,24.5L22.2,23.7L25.8,22.0L29.5,19.8L33.2,16.9L36.9,15.0L40.6,14.0L44.3,12.6L48.0,12.2L51.7,12.6L55.4,13.1L59.1,14.3L62.8,13.7L66.5,10.5L70.2,7.9L73.8,6.2L77.5,6.4L81.2,8.2L84.9,8.5L88.6,6.4L92.3,3.5L96.0,4.0"/></svg>
            <span class="p">1&#8239;184,20</span><span class="c u">▲&nbsp;+1,2&#8239;%</span>
          </div>
          <div class="bnx-q" aria-hidden="true">
            <span class="s">S&amp;P&nbsp;500<small>INDEX</small></span>
            <svg viewBox="0 0 96 28" preserveAspectRatio="none" role="presentation"><path class="ar-d" d="M0.0,3.5L3.7,4.3L7.4,6.1L11.1,8.4L14.8,7.9L18.5,6.6L22.2,8.3L25.8,10.0L29.5,10.5L33.2,10.9L36.9,11.5L40.6,13.8L44.3,15.7L48.0,14.6L51.7,13.7L55.4,16.7L59.1,17.3L62.8,15.6L66.5,18.4L70.2,21.3L73.8,21.4L77.5,21.1L81.2,22.7L84.9,24.1L88.6,22.3L92.3,22.2L96.0,24.5L96,28L0,28Z"/><path class="ln-d" fill="none" stroke-width="1.4" vector-effect="non-scaling-stroke" stroke-linejoin="round" d="M0.0,3.5L3.7,4.3L7.4,6.1L11.1,8.4L14.8,7.9L18.5,6.6L22.2,8.3L25.8,10.0L29.5,10.5L33.2,10.9L36.9,11.5L40.6,13.8L44.3,15.7L48.0,14.6L51.7,13.7L55.4,16.7L59.1,17.3L62.8,15.6L66.5,18.4L70.2,21.3L73.8,21.4L77.5,21.1L81.2,22.7L84.9,24.1L88.6,22.3L92.3,22.2L96.0,24.5"/></svg>
            <span class="p">6&#8239;512,30</span><span class="c d">▼&nbsp;−0,3&#8239;%</span>
          </div>
        </div>
      </div>
    </div>
    <div class="panel bnx-strip" aria-label="Beispiel einer Briefing-Ausgabe">
      <div class="panel-h"><span data-bx="st_h">MARKET BRIEFING&nbsp;·&nbsp;HEUTE</span><b data-bx="demo4">SAMPLE VIEW</b></div>
      <div class="bnx-tiles">
        <div class="bnx-tile"><div class="k" data-bx="t1k">LAGE</div><div class="v amber" data-bx="t1v">RUHIG</div></div>
        <div class="bnx-tile"><div class="k" data-bx="t2k">TAKEAWAY</div><div class="v gold" data-bx="t2v">»Let winners run.«</div></div>
        <div class="bnx-tile"><div class="k" data-bx="t3k">NOTABLE</div><div class="v" data-bx="t3v">3 names, with reasons</div></div>
        <div class="bnx-tile"><div class="k" data-bx="t4k">FORMAT</div><div class="v"><span class="v amber" style="display:inline">3&#8239;MIN</span> <span data-bx="t4v">· free</span></div></div>
      </div>
    </div>
    <div class="bnx-foot">
      <span data-bx="f1">Curated in Lübeck</span><span class="bnx-rule"></span><span data-bx="f2">Daily 10:00 CET</span><span class="bnx-rule"></span><span>Est. 2026</span>
    </div>
  </section>

  <!-- ============ 03 BLICK IN DIE APP ============ -->
  <div class="sh" id="einblicke"><span class="no">03</span><h2>Inside the app</h2><span class="ln"></span></div>
  <div class="gal">
    <figure class="panel">
      <div class="panel-h"><span>PORTFOLIO COACH</span><b>LIVE APP</b></div>
      <div class="shotwrap"><img src="/assets/app-start.png" alt="StockPilot Depot-Coach" loading="lazy"></div>
      <figcaption>The portfolio coach: your positions, daily moves and open to-dos, risk first.</figcaption>
    </figure>
    <figure class="panel">
      <div class="panel-h"><span>PILOT</span><b>LIVE APP</b></div>
      <div class="shotwrap"><img src="/assets/app-pilot.png" alt="StockPilot Pilot" loading="lazy"></div>
      <figcaption>The pilot: market pulse, fragility and what to do now.</figcaption>
    </figure>
    <figure class="panel">
      <div class="panel-h"><span>MARKET BRIEFING</span><b>E-MAIL&nbsp;+&nbsp;WEB</b></div>
      <div class="shotwrap"><img src="/assets/briefing.jpg" alt="Das Market Briefing" loading="lazy"></div>
      <figcaption>The daily market briefing, in the browser and by e-mail.</figcaption>
    </figure>
  </div>

  <!-- ============ 04 TRACK RECORD ============ -->
  <div class="sh" id="bilanz"><span class="no">04</span><h2>The open signal history</h2><span class="ln"></span></div>
  <div class="panel">
    <div class="panel-h"><span>SIGNAL HISTORY</span><b>FROM REAL DATA</b></div>
    <div class="trust">
      <div class="ev"><b id="st-n3">1&#8239;445</b><span>SIGNALE&nbsp;DOKUMENTIERT</span></div>
      <p id="st-trust">Since May 2026 every signal is documented to the very end, including the ones that did not work out. Nothing is deleted or glossed over. The history ships with every market briefing. Past performance is not indicative of future results. Not investment advice.</p>
    </div>
  </div>

  <!-- ============ 05 PRICING ============ -->
  <div class="sh" id="preis"><span class="no">05</span><h2>Preis</h2><span class="ln"></span></div>
  <div class="panel">
    <div class="panel-h"><span>TERMS</span><b>OPEN BETA</b></div>
    <div class="price">
      <div class="p">0&nbsp;€<small>IN BETA</small></div>
      <p>StockPilot ist aktuell in der offenen Beta und komplett kostenlos. Danach kostet die App 0,99&nbsp;€ im Monat, monatlich kündbar. Kein Depotzugriff, keine Provisionen, keine versteckten Kosten: StockPilot verdient an dir, nicht an deinen Trades.</p>
      <a class="btn gold" href="https://gemivo.de/konto/login.php?login=1&next=https%3A%2F%2Fstockbot.gemivo.de%2F">Start free&nbsp;→</a>
    </div>
  </div>

  <!-- ============ 06 FAQ ============ -->
  <div class="sh" id="faq"><span class="no">06</span><h2>Frequently asked questions</h2><span class="ln"></span></div>
  <div class="faq">
    <details><summary>Is this investment advice?</summary>
      <p>No. StockPilot delivers journalism-grade standardized analyses of price, analyst and model data. The decision is always yours, StockPilot never trades for you.</p></details>
    <details><summary>Does StockPilot need access to my portfolio?</summary>
      <p>No. No brokerage access, no bank connection, no commissions. You enter your positions yourself, StockPilot uses them for the portfolio coach and the warnings.</p></details>
    <details><summary>What does StockPilot cost?</summary>
      <p>In der offenen Beta ist alles kostenlos. Danach kostet die App 0,99&nbsp;€ im Monat und ist monatlich kündbar. The daily market brief bleibt kostenlos.</p></details>
    <details><summary>Where does the data come from?</summary>
      <p>Prices and analyst estimates come from Yahoo Finance, Onvista and Tradegate. Every night the StockPilot AI re-rates thousands of stocks on top of them.</p></details>
    <details><summary>How transparent is the signal history?</summary>
      <p>Every signal is documented to the end, nothing is deleted or changed afterwards. The history ships with every briefing edition, generated automatically from real data. Past performance is not indicative of future results.</p></details>
    <details><summary>Is there an Android app?</summary>
      <p>StockPilot runs instantly as a web app in the browser, no installation needed. The Android app is in preparation, Play Store review is underway.</p></details>
  </div>

  <!-- ============ FOOTER ============ -->
  <footer class="foot">
    <p class="brandline">StockPilot is a brand by GE·MI·VO, built by Kay Conrad in Germany. One login for all GE·MI·VO apps.</p>
    <b>Important notice:</b> StockPilot liefert journalistisch-standardisierte Auswertungen von Kurs-, Analysten- und Modelldaten und This is not individual investment advice. Past performance is not indicative of future results. Investieren birgt Verlustrisiken. Der Herausgeber hält teilweise selbst Positionen in besprochenen Titeln; Offenlegung in jeder Briefing-Ausgabe.
    <div class="links"><a href="https://gemivo.de">gemivo.de</a> · <a href="https://gemivo.de/impressum.html">Imprint</a> · <a href="https://gemivo.de/datenschutz.html">Privacy</a></div>
  </footer>

</div><!-- /wrap -->
</main>

<script>
(function(){
  var EN=(function(){try{var s=localStorage.getItem('gemivo_lang');if(s)return s==='en';}catch(_){ }
    return ((navigator.language||'de').toLowerCase().indexOf('de')!==0);})();
  /* EN-Texte (Terminal-Sektion folgt dem Sprachumschalter der Plattform) */
  if(EN){
    var D={demo:'SAMPLE',demo2:'SAMPLE',demo3:'SAMPLE VIEW',demo4:'SAMPLE VIEW',
      eyebrow:'THE DAILY MARKET BRIEFING',btn:'Get the briefing',
      sub:'Every trading day at 10:00 in your inbox: the market in one word, plain-language takeaways and notable stocks with reasoning. Clear for beginners, with numbers for pros.',
      tr_cap1:'SIGNAL HISTORY',tr_cap2:'SINCE MAY 2026 · AS OF SEP 2026',
      tr1:'signals documented to the end',tr2:'stocks analysed every night',tr3:'disciplines per stock',tr4:'during the beta',
      prev:'PREV CLOSE 24 111.72',st_h:'MARKET BRIEFING · TODAY',
      t1k:'REGIME',t1v:'CALM',t2k:'TAKEAWAY',t2v:'»Let your winners run.«',t3k:'NOTABLE TODAY',t3v:'3 names, with reasoning',t4k:'FORMAT',t4v:'· free',
      f1:'Curated in Lübeck, Germany',f2:'Daily 10:00 CET'};
    var H={h:'The markets.<br><em>In three minutes.</em>',
      fine:'Click the confirmation e-mail, done. Unsubscribe anytime, no data sharing. Not investment advice. <a href="/boersen-news.php">Learn more →</a> · <a href="/nl-public.html">Today&#39;s issue →</a>'};
    document.querySelectorAll('[data-bx]').forEach(function(e){var v=D[e.getAttribute('data-bx')];if(v!=null)e.textContent=v;});
    document.querySelectorAll('[data-bx-html]').forEach(function(e){var v=H[e.getAttribute('data-bx-html')];if(v!=null)e.innerHTML=v;});
    var inp0=document.getElementById('bnxEmail');if(inp0){inp0.placeholder='your@email.com';inp0.setAttribute('aria-label','E-mail address');}
    document.querySelectorAll('.bnx-pill,.bnx-q .p,.bnx-q .c,.bnx-ch-dx').forEach(function(e){e.textContent=e.textContent.replace(',','.');});
  }
  /* Ticker (illustrative Beispiel-Deltas, keine Echtkurse) */
  var T=[['AAPL','+0,6',1],['NVDA','+1,2',1],['^GDAXI','−0,3',0],['MSFT','+0,4',1],['SAP','+0,8',1],['^NDX','+0,7',1],['SIE','−0,2',0],['AMZN','+0,9',1],['^SPX','+0,3',1],['ALV','−0,1',0]];
  var el=document.getElementById('bnxTick');
  if(el){
    var h=T.map(function(t){var d=EN?t[1].replace(',','.'):t[1];return '<span class="sym">'+t[0]+'</span> <span class="'+(t[2]?'u':'d')+'">'+(t[2]?'▲':'▼')+' '+d+'%</span>&nbsp;&nbsp;·&nbsp;&nbsp;';}).join('');
    el.innerHTML=h+h; /* exakt verdoppelt -> nahtloser -50%-Loop */
  }
  var motionOK=!(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches);
  /* Count-up der Kennzahl (24 186,42) */
  var px=document.getElementById('bnxPx');
  if(px&&motionOK&&window.requestAnimationFrame){
    var END=24186.42,START=END-320,T0=null,DUR=1300;
    var fmt=function(v){var s=v.toFixed(2),i=s.slice(0,-3),d=s.slice(-2);return i.replace(/\B(?=(\d{3})+$)/g,' ')+(EN?'.':',')+d;};
    var step=function(ts){if(T0===null)T0=ts;var p=Math.min(1,(ts-T0)/DUR);var e=1-Math.pow(1-p,3);
      px.textContent=fmt(START+(END-START)*e);
      if(p<1)requestAnimationFrame(step);};
    requestAnimationFrame(step);
  }
  /* Crosshair (nur Zeiger-Geräte) */
  var ch=document.getElementById('bnxChart'),xh=document.getElementById('bnxXh'),xt=document.getElementById('bnxXhT');
  if(ch&&xh&&window.matchMedia&&matchMedia('(hover:hover) and (pointer:fine)').matches){
    ch.addEventListener('pointermove',function(e){
      var r=ch.getBoundingClientRect(),x=Math.max(0,Math.min(r.width-1,e.clientX-r.left));
      var mins=540+(x/r.width)*510,hh=Math.floor(mins/60),mm=Math.floor(mins%60);
      xh.style.left=x+'px';xh.style.opacity='1';
      xt.textContent=(hh<10?'0':'')+hh+':'+(mm<10?'0':'')+mm;
    });
    ch.addEventListener('pointerleave',function(){xh.style.opacity='0';});
  }
  /* Marktstatus + Countdown + Uhr (Xetra Mo-Fr 09:00-17:30 Europe/Berlin) */
  function berlin(){
    var p=new Intl.DateTimeFormat('en-GB',{timeZone:'Europe/Berlin',weekday:'short',hour:'2-digit',minute:'2-digit',hour12:false}).formatToParts(new Date()),o={};
    p.forEach(function(x){o[x.type]=x.value;});
    return o;
  }
  function pad(n){return (n<10?'0':'')+n;}
  function tick(){
    try{
      var o=berlin(),wdI=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].indexOf(o.weekday);
      var m=parseInt(o.hour,10)*60+parseInt(o.minute,10);
      var wd=wdI>=0&&wdI<5,open=wd&&m>=540&&m<1050;
      var st=document.getElementById('bnxStatus'),dot=document.getElementById('bnxDot'),cd=document.getElementById('bnxCd'),ck=document.getElementById('bnxClock');
      if(st) st.textContent=open?(EN?'XETRA · OPEN':'XETRA · OFFEN'):(EN?'XETRA · CLOSED':'XETRA · GESCHLOSSEN');
      if(dot) dot.className=open?'dot':'dot closed';
      if(ck) ck.textContent=o.hour+':'+o.minute+' BERLIN';
      if(cd){
        var t;
        if(open){var r=1050-m;t=(EN?'CLOSE IN ':'SCHLUSS IN ')+Math.floor(r/60)+':'+pad(r%60)+' H';}
        else if(wd&&m<540){var r2=540-m;t=(EN?'OPEN IN ':'START IN ')+Math.floor(r2/60)+':'+pad(r2%60)+' H';}
        else{t=(EN?'OPENS ':'START ')+(wdI===5||wdI===6||wdI===4?(EN?'MON':'MO'):(EN?'TOMORROW':'MORGEN'))+' 09:00';}
        cd.textContent=t;
      }
    }catch(_){}
  }
  tick();setInterval(tick,30000);
  /* Newsletter-Formular -> bnews-subscribe.php */
  var f=document.getElementById('bnxForm'); if(!f) return;
  var msg=document.getElementById('bnxMsg'),btn=document.getElementById('bnxBtn'),inp=document.getElementById('bnxEmail');
  var UP=getComputedStyle(document.documentElement).getPropertyValue('--up')||'#34d399';
  var DN=getComputedStyle(document.documentElement).getPropertyValue('--dn')||'#f87171';
  f.addEventListener('submit',async function(e){
    e.preventDefault();
    var email=inp.value.trim();
    if(!email||email.indexOf('@')<1){msg.textContent=EN?'Please enter a valid e-mail address.':'Bitte gib eine gültige E-Mail-Adresse ein.';msg.style.color=DN;return;}
    btn.disabled=true;msg.style.color='';msg.textContent='…';
    try{
      var r=await fetch('/gemivo-api/bnews-subscribe.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'email='+encodeURIComponent(email)});
      var j=await r.json();
      if(j.ok){msg.textContent=j.msg||(EN?'Almost done! Please confirm the e-mail.':'Fast geschafft! Bitte bestätige die E-Mail.');msg.style.color=UP;f.reset();}
      else{msg.textContent=j.error||(EN?'Error. Please try again.':'Fehler. Bitte erneut.');msg.style.color=DN;}
    }catch(_){msg.textContent=EN?'Network error. Please try again.':'Netzwerkfehler. Bitte erneut.';msg.style.color=DN;}
    btn.disabled=false;
  });
})();
/* Live-Status aus nl-status.json */
fetch('/nl-status.json').then(r=>r.json()).then(s=>{
  const cls={ruhig:'st-up',gesund:'st-up',stabil:'st-up',niedrig:'st-up',unruhig:'st-warn',angespannt:'st-warn','erhöht':'st-warn',Sturm:'st-bad','brüchig':'st-bad',hoch:'st-bad'};
  const set=(id,label,v)=>{const el=document.getElementById(id); if(el&&v&&v.l)el.innerHTML='<span class="l">'+label+'</span> <b class="'+(cls[v.l]||'')+'">'+v.l.toUpperCase()+'</b>';};
  set('st-markt','MARKET STATE',s.markt); set('st-fragil','FUNDAMENTALS',s.fragil); set('st-trump','POLITICS',s.trump);
}).catch(()=>{const e=document.getElementById('status');if(e)e.style.display='none'});
/* Video-Ton */
const reel=document.getElementById('reel'),sndbtn=document.getElementById('sndbtn');
function sndToggle(){ reel.muted=!reel.muted;
  document.getElementById('ic-off').style.display=reel.muted?'':'none';
  document.getElementById('ic-on').style.display=reel.muted?'none':'';
  if(!reel.muted) reel.play(); }
if(reel){ reel.addEventListener('click',sndToggle); sndbtn.addEventListener('click',sndToggle); }
/* Theme-Umschalter (dunkles Terminal ist Standard) */
(function(){
  var tb=document.getElementById('themeBtn'); if(!tb)return;
  function cur(){ return document.documentElement.dataset.theme==='light'?'light':'dark'; }
  function icon(){ var d=cur()==='dark';
    document.getElementById('ic-sun').style.display=d?'':'none';
    document.getElementById('ic-moon').style.display=d?'none':''; }
  tb.addEventListener('click',function(){ var nx=cur()==='dark'?'light':'dark';
    document.documentElement.dataset.theme=nx; try{localStorage.setItem('nlTheme',nx);}catch(e){} icon(); });
  icon();
})();
/* Echte Bilanz-Zahlen aus nl-stats.json */
fetch('/nl-stats.json').then(r=>r.json()).then(s=>{
  const de=n=>n.toLocaleString('de-DE');
  const nb=n=>de(n).replace(/\./g,' '); /* 1 477 mit schmalem Leerzeichen, wie im Layout */
  const set=(id,v)=>{const e=document.getElementById(id); if(e)e.textContent=v;};
  set('st-exp','+'+s.exp+' %'); set('st-ev','+'+s.exp+' %');
  
  set('st-n',nb(s.n)); set('st-nstand','signals documented to the end, Stand '+s.stand);
  set('st-n2',nb(s.n)); set('st-rate',s.rate+' %');
  set('st-avgwin','Ø +'+s.avg_win+' %'); set('st-avgloss','Ø '+s.avg_loss+' %');
  set('st-trust','Seit '+s.since+' wird jedes Signal bis zum Ende dokumentiert ('+de(s.n)+' bis '+s.stand+'), auch die, die nicht aufgegangen sind. Die Historie steht in jeder Ausgabe des Market Briefings. Wertentwicklungen der Vergangenheit sind kein Indikator für die Zukunft. Keine Anlageberatung.');
}).catch(()=>{});
</script>
</body>
</html>
<?php $__o=ob_get_clean();
$__nN=(string)(int)$__st['n'];
$__sigN=(string)(int)($__st['sig']??0);
$__o=str_replace('1.800',$__n,$__o);
$__o=str_replace('1&#8239;800',$__nN,$__o);
$__o=str_replace('1&#8239;445',$__sigN,$__o);
$__o=str_replace('Stand 09/2026','Stand '.date('m/Y'),$__o);
$__o=str_replace('signals documented to the end','Signale erfasst und protokolliert',$__o);
$__o=str_replace('4.500',$__tr,$__o);
echo $__o;