<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Mesa {{ $mesaNumero }} · {{ $restaurantName }}</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: #020617;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    padding: 24px;
  }

  .page {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 28px;
    width: 100%;
    max-width: 680px;
  }

  /* HEADER */
  .header {
    text-align: center;
  }
  .header .restaurant {
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #ffd7aa;
    margin-bottom: 8px;
  }
  .header h1 {
    font-size: 2.2rem;
    font-weight: 800;
    color: #e2e8f0;
    letter-spacing: -.01em;
  }
  .header .subtitle {
    color: #64748b;
    font-size: .9rem;
    margin-top: 6px;
  }

  /* QR GRID */
  .qr-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    width: 100%;
  }

  .qr-card {
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(148, 163, 184, 0.15);
    border-radius: 24px;
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    text-align: center;
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .qr-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 50px rgba(2, 6, 23, 0.6);
  }

  .qr-card.wifi {
    border-color: rgba(96, 165, 250, 0.25);
    background: linear-gradient(160deg, rgba(15,23,42,.95), rgba(23,37,84,.6));
  }
  .qr-card.carta {
    border-color: rgba(255, 215, 170, 0.25);
    background: linear-gradient(160deg, rgba(15,23,42,.95), rgba(92,38,20,.4));
  }

  .qr-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
  }
  .qr-card.wifi .qr-icon { background: rgba(96,165,250,.15); }
  .qr-card.carta .qr-icon { background: rgba(255,215,170,.12); }

  .qr-label {
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
  }
  .qr-card.wifi .qr-label { color: #93c5fd; }
  .qr-card.carta .qr-label { color: #ffd7aa; }

  .qr-box {
    background: #ffffff;
    border-radius: 16px;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 30px rgba(0,0,0,.4);
  }

  .qr-desc {
    font-size: .82rem;
    color: #64748b;
    line-height: 1.5;
    max-width: 180px;
  }

  .qr-no-wifi {
    background: rgba(156,32,48,.12);
    border: 1px dashed rgba(255,215,170,.3);
    border-radius: 12px;
    padding: 20px;
    font-size: .82rem;
    color: #ffd7aa;
    line-height: 1.6;
  }

  /* FOOTER */
  .footer {
    text-align: center;
    color: #1e293b;
    font-size: .75rem;
  }

  /* PRINT */
  @media print {
    body { background: #fff; padding: 0; }
    .qr-card { background: #fff !important; border: 2px solid #e2e8f0 !important; break-inside: avoid; }
    .qr-card.wifi { border-color: #bfdbfe !important; }
    .qr-card.carta { border-color: #fed7aa !important; }
    .qr-label { color: #1e40af !important; }
    .qr-card.carta .qr-label { color: #92400e !important; }
    .header .restaurant { color: #92400e !important; }
    .header h1 { color: #0f172a !important; }
    .qr-desc { color: #475569 !important; }
    .print-btn { display: none !important; }
  }

  .print-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #9c2030, #7a1522);
    color: #fff;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(156,32,48,.3);
    transition: all .2s;
  }
  .print-btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
</style>
</head>
<body>
<div class="page">

  <div class="header">
    <p class="restaurant">{{ $restaurantName }}</p>
    <h1>Mesa {{ $mesaNumero }}</h1>
    <p class="subtitle">Escanea los códigos para conectarte y ver el menú</p>
  </div>

  <div class="qr-grid">

    {{-- QR WIFI --}}
    <div class="qr-card wifi">
      <div class="qr-icon">📶</div>
      <span class="qr-label">WiFi</span>
      @if($wifiSsid)
        <div class="qr-box">
          <div id="qrWifi"></div>
        </div>
        <p class="qr-desc">Escanea para conectarte automáticamente a la red del restaurante</p>
      @else
        <div class="qr-no-wifi">
          ⚠️ WiFi no configurado.<br>
          <a href="/admin/config" style="color:#ffd7aa;">Configurar →</a>
        </div>
      @endif
    </div>

    {{-- QR CARTA --}}
    <div class="qr-card carta">
      <div class="qr-icon">🍽️</div>
      <span class="qr-label">Carta Digital</span>
      <div class="qr-box">
        <div id="qrCarta"></div>
      </div>
      <p class="qr-desc">Escanea para ver el menú y hacer tu pedido desde esta mesa</p>
    </div>

  </div>

  <button class="print-btn" onclick="window.print()">🖨️ Imprimir QR</button>

  <div class="footer">ODER EASY · {{ $restaurantName }}</div>

</div>

<script>
  // QR Carta
  new QRCode(document.getElementById('qrCarta'), {
    text: '{{ $cartaUrl }}',
    width: 180,
    height: 180,
    colorDark: '#000000',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });

  @if($wifiSsid)
  // QR WiFi
  new QRCode(document.getElementById('qrWifi'), {
    text: '{{ $wifiStr }}',
    width: 180,
    height: 180,
    colorDark: '#000000',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });
  @endif
</script>
</body>
</html>