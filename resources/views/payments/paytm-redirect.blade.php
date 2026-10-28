<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Redirecting to Paytm…</title>
<style>body{margin:0;display:grid;place-items:center;min-height:100vh;font:16px Inter,system-ui,sans-serif;color:#10213e;background:#f3f8ff}button{padding:12px 20px;border:0;border-radius:9px;background:#00b9f5;color:#fff;font-weight:700;cursor:pointer}</style></head>
<body><form method="post" action="{{ $url }}" id="paytm">
<input type="hidden" name="mid" value="{{ $mid }}"><input type="hidden" name="orderId" value="{{ $orderId }}"><input type="hidden" name="txnToken" value="{{ $token }}">
<p>Taking you to Paytm to complete the payment…</p><noscript><button>Continue to Paytm</button></noscript></form>
<script>document.getElementById('paytm').submit();</script></body></html>
