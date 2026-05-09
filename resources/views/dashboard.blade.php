<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Safety Monitor</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Tajawal', sans-serif; }
    .en { font-family: 'Space Grotesk', sans-serif; }
    body { background: #0a0a0f; color: #e2e8f0; overflow-x: hidden; }
    .bg-grid {
      background-image: linear-gradient(rgba(99,102,241,0.05) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(99,102,241,0.05) 1px, transparent 1px);
      background-size: 40px 40px;
    }
    body.danger { background: #0f0505; }
    body.danger .bg-grid {
      background-image: linear-gradient(rgba(239,68,68,0.08) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(239,68,68,0.08) 1px, transparent 1px);
      animation: gridPulse 1s ease-in-out infinite;
    }
    @keyframes gridPulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }
    .card {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 20px;
      backdrop-filter: blur(10px);
      transition: all 0.4s ease;
    }
    .card:hover {
      background: rgba(255,255,255,0.05);
      border-color: rgba(255,255,255,0.12);
      transform: translateY(-2px);
    }
    .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .dot.safe { background: #10b981; box-shadow: 0 0 10px #10b981; animation: pulse-green 2s infinite; }
    .dot.danger { background: #ef4444; box-shadow: 0 0 10px #ef4444; animation: pulse-red 0.8s infinite; }
    @keyframes pulse-green {
      0%, 100% { box-shadow: 0 0 5px #10b981; }
      50% { box-shadow: 0 0 15px #10b981, 0 0 25px #10b981; }
    }
    @keyframes pulse-red {
      0%, 100% { box-shadow: 0 0 5px #ef4444; }
      50% { box-shadow: 0 0 20px #ef4444, 0 0 40px #ef4444; }
    }
    .danger-overlay {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 50;
      background: rgba(15,5,5,0.97);
      animation: dangerIn 0.3s ease;
    }
    .danger-overlay.show { display: flex; }
    @keyframes dangerIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .danger-border {
      border: 2px solid rgba(239,68,68,0.3);
      border-radius: 24px;
      animation: borderPulse 1s ease-in-out infinite;
    }
    @keyframes borderPulse {
      0%, 100% { border-color: rgba(239,68,68,0.3); }
      50% { border-color: rgba(239,68,68,0.9); box-shadow: 0 0 40px rgba(239,68,68,0.3); }
    }
    .alert-icon { animation: shake 0.5s ease-in-out infinite; }
    @keyframes shake {
      0%, 100% { transform: rotate(-5deg) scale(1); }
      50% { transform: rotate(5deg) scale(1.1); }
    }
    .gauge-bar {
      height: 6px;
      border-radius: 3px;
      background: rgba(255,255,255,0.1);
      overflow: hidden;
    }
    .gauge-fill {
      height: 100%;
      border-radius: 3px;
      transition: width 1s ease, background 0.5s ease;
    }
    .big-value {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 3.5rem;
      font-weight: 700;
      line-height: 1;
      letter-spacing: -2px;
    }
    .blink { animation: blink 0.8s step-end infinite; }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0; }
    }
    .history-list { max-height: 200px; overflow-y: auto; scrollbar-width: none; }
    .history-list::-webkit-scrollbar { display: none; }
    .conn-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #10b981;
      animation: pulse-green 2s infinite;
    }
    .conn-dot.offline { background: #6b7280; animation: none; }
  </style>
</head>
<body class="bg-grid min-h-screen">

  <!-- DANGER OVERLAY -->
  <div class="danger-overlay flex-col items-center justify-center p-6" id="dangerOverlay">
    <div class="danger-border p-10 text-center max-w-lg w-full mx-auto">
      <div class="alert-icon text-8xl mb-6" id="dangerIcon">🔥</div>
      <h1 class="text-5xl font-bold text-red-400 mb-3 blink" id="dangerTitle">تحذير خطر!</h1>
      <p class="text-red-300 text-xl mb-8" id="dangerDesc">تم اكتشاف لهب</p>
      <button onclick="dismissOverlay()" class="mt-8 px-6 py-3 bg-red-900 hover:bg-red-800 text-red-200 rounded-xl transition text-sm">
        إخفاء التحذير مؤقتاً
      </button>
    </div>
  </div>

  <!-- MAIN LAYOUT -->
  <div class="max-w-5xl mx-auto p-6">

    <!-- Header -->
    <div class="flex items-center justify-between mb-10">
      <div>
        <h1 class="text-3xl font-bold text-white mb-1">Smart Safety Monitor</h1>
        <p class="text-slate-400 text-sm">نظام مراقبة ذكي للسلامة</p>
      </div>
      <div class="flex items-center gap-3 card px-4 py-3">
        <div class="conn-dot" id="connDot"></div>
        <span class="text-sm text-slate-300" id="connStatus">متصل</span>
        <span class="text-slate-500 text-xs en" id="lastUpdate">--:--</span>
      </div>
    </div>

    <!-- Safe / Danger banner -->
    <div id="statusBanner" class="card p-5 mb-6 flex items-center gap-4">
      <div class="dot safe" id="mainDot"></div>
      <div>
        <p class="text-lg font-semibold" id="statusText">الوضع آمن</p>
        <p class="text-slate-400 text-sm" id="statusSub">جميع الأنظمة تعمل بشكل طبيعي</p>
      </div>
      <div class="mr-auto">
        <span class="en text-slate-500 text-xs" id="statusTime">--</span>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

      <!-- Temp -->
      <div class="card p-5 col-span-2 md:col-span-1">
        <p class="text-slate-400 text-xs mb-3">درجة الحرارة</p>
        <div class="big-value text-orange-400" id="tempVal">--</div>
        <span class="text-orange-300 text-sm en">°C</span>
        <div class="gauge-bar mt-4">
          <div class="gauge-fill bg-orange-400" id="tempBar" style="width: 0%"></div>
        </div>
      </div>

      <!-- Humidity -->
      <div class="card p-5 col-span-2 md:col-span-1">
        <p class="text-slate-400 text-xs mb-3">الرطوبة</p>
        <div class="big-value text-blue-400" id="humiVal">--</div>
        <span class="text-blue-300 text-sm en">%</span>
        <div class="gauge-bar mt-4">
          <div class="gauge-fill bg-blue-400" id="humiBar" style="width: 0%"></div>
        </div>
      </div>

      <!-- Flame -->
      <div class="card p-5" id="flameCard">
        <p class="text-slate-400 text-xs mb-3">كاشف اللهب</p>
        <div class="text-4xl mb-2" id="flameIcon">🟢</div>
        <p class="text-sm font-medium" id="flameStatus">آمن</p>
      </div>

      <!-- Gas -->
      <div class="card p-5" id="gasCard">
        <p class="text-slate-400 text-xs mb-3">كاشف الغاز</p>
        <div class="text-4xl mb-2" id="gasIcon">🟢</div>
        <p class="text-sm font-medium" id="gasStatus">آمن</p>
      </div>

    </div>

    <!-- History -->
    <div class="card p-5">
      <h2 class="text-sm text-slate-400 mb-4 font-medium">آخر الأحداث</h2>
      <div class="history-list space-y-2" id="historyList">
        <p class="text-slate-500 text-sm text-center py-4">لا توجد أحداث بعد</p>
      </div>
    </div>

    <p class="text-center text-slate-600 text-xs mt-6 en">ESP32 Smart Safety System • Auto-refresh every 3s</p>
  </div>

  <script>
    // مهم: الـ API Route تغيرت لـ /api/sensor (من api.php)
    const API_URL = "{{ url('/api/sensor') }}";

    let prevData = null;
    let overlayDismissed = false;
    const history = [];

    function formatTime(d = new Date()) {
      return d.toLocaleTimeString('ar-EG');
    }

    function updateUI(data) {
      const isDanger = data.flame || data.gas || data.temperature > 30;

      // Connection
      document.getElementById('connDot').className = 'conn-dot';
      document.getElementById('connStatus').textContent = 'متصل';
      document.getElementById('lastUpdate').textContent = formatTime();

      // Temp
      const temp = parseFloat(data.temperature || 0).toFixed(1);
      const humi = parseFloat(data.humidity || 0).toFixed(1);
      document.getElementById('tempVal').textContent = temp;
      document.getElementById('humiVal').textContent = humi;
      document.getElementById('tempBar').style.width = Math.min(temp / 60 * 100, 100) + '%';
      document.getElementById('tempBar').style.background = data.temperature > 30 ? '#ef4444' : '#f97316';
      document.getElementById('humiBar').style.width = humi + '%';

      // Flame
      document.getElementById('flameIcon').textContent = data.flame ? '🔴' : '🟢';
      document.getElementById('flameStatus').textContent = data.flame ? 'خطر!' : 'آمن';
      document.getElementById('flameStatus').className = 'text-sm font-medium ' + (data.flame ? 'text-red-400 blink' : 'text-emerald-400');
      document.getElementById('flameCard').style.borderColor = data.flame ? 'rgba(239,68,68,0.5)' : '';

      // Gas
      document.getElementById('gasIcon').textContent = data.gas ? '🔴' : '🟢';
      document.getElementById('gasStatus').textContent = data.gas ? 'خطر!' : 'آمن';
      document.getElementById('gasStatus').className = 'text-sm font-medium ' + (data.gas ? 'text-red-400 blink' : 'text-emerald-400');
      document.getElementById('gasCard').style.borderColor = data.gas ? 'rgba(239,68,68,0.5)' : '';

      // Status banner
      const dot = document.getElementById('mainDot');
      const banner = document.getElementById('statusBanner');
      if (isDanger) {
        dot.className = 'dot danger';
        document.getElementById('statusText').textContent = 'تحذير! تم اكتشاف خطر';
        document.getElementById('statusSub').textContent = getDangerDesc(data);
        banner.style.borderColor = 'rgba(239,68,68,0.4)';
        banner.style.background = 'rgba(239,68,68,0.05)';
        document.body.className = 'bg-grid min-h-screen danger';
      } else {
        dot.className = 'dot safe';
        document.getElementById('statusText').textContent = 'الوضع آمن';
        document.getElementById('statusSub').textContent = 'جميع الأنظمة تعمل بشكل طبيعي';
        banner.style.borderColor = '';
        banner.style.background = '';
        document.body.className = 'bg-grid min-h-screen';
        overlayDismissed = false;
      }

      document.getElementById('statusTime').textContent = formatTime();

      // Danger overlay
      if (isDanger && !overlayDismissed) {
        showDangerOverlay(data);
      } else if (!isDanger) {
        document.getElementById('dangerOverlay').classList.remove('show');
      }

      // History
      if (prevData) {
        const newFlame = data.flame && !prevData.flame;
        const newGas = data.gas && !prevData.gas;
        const newTemp = data.temperature > 30 && prevData.temperature <= 30;
        if (newFlame) addHistory('🔥', 'تم اكتشاف لهب', 'danger');
        if (newGas) addHistory('💨', 'تم اكتشاف غاز', 'danger');
        if (newTemp) addHistory('🌡️', `ارتفاع درجة الحرارة: ${temp}°C`, 'warning');
        if (prevData.flame && !data.flame) addHistory('✅', 'اختفى اللهب', 'safe');
        if (prevData.gas && !data.gas) addHistory('✅', 'اختفى الغاز', 'safe');
      }

      prevData = data;
    }

    function getDangerDesc(data) {
      const alerts = [];
      if (data.flame) alerts.push('لهب');
      if (data.gas) alerts.push('غاز');
      if (data.temperature > 30) alerts.push('حرارة مرتفعة');
      return 'تم اكتشاف: ' + alerts.join(' • ');
    }

    function showDangerOverlay(data) {
      const overlay = document.getElementById('dangerOverlay');
      overlay.classList.add('show');

      if (data.flame) {
        document.getElementById('dangerIcon').textContent = '🔥';
        document.getElementById('dangerTitle').textContent = 'تحذير! لهب مكتشف';
        document.getElementById('dangerDesc').textContent = 'تم اكتشاف لهب — اتخذ إجراءات فورية';
      } else if (data.gas) {
        document.getElementById('dangerIcon').textContent = '☁️';
        document.getElementById('dangerTitle').textContent = 'تحذير! غاز مكتشف';
        document.getElementById('dangerDesc').textContent = 'تم تفعيل الشفاط تلقائياً';
      } else {
        document.getElementById('dangerIcon').textContent = '🌡️';
        document.getElementById('dangerTitle').textContent = 'حرارة مرتفعة!';
        document.getElementById('dangerDesc').textContent = `درجة الحرارة: ${parseFloat(data.temperature).toFixed(1)}°C`;
      }
    }

    function dismissOverlay() {
      overlayDismissed = true;
      document.getElementById('dangerOverlay').classList.remove('show');
    }

    function addHistory(icon, msg, type) {
      const colors = { danger: 'text-red-400', safe: 'text-emerald-400', warning: 'text-amber-400' };
      history.unshift({ icon, msg, type, time: formatTime() });
      if (history.length > 20) history.pop();

      const list = document.getElementById('historyList');
      list.innerHTML = history.map(h => `
        <div class="flex items-center gap-3 py-2 border-b border-white/5">
          <span>${h.icon}</span>
          <span class="text-sm ${colors[h.type]} flex-1">${h.msg}</span>
          <span class="text-slate-500 text-xs en">${h.time}</span>
        </div>
      `).join('');
    }

    function setOffline() {
      document.getElementById('connDot').className = 'conn-dot offline';
      document.getElementById('connStatus').textContent = 'غير متصل';
    }

    async function fetchData() {
      try {
        const res = await fetch(API_URL);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (data) updateUI(data);
      } catch (e) {
        console.error('Fetch error:', e);
        setOffline();
      }
    }

    fetchData();
    setInterval(fetchData, 3000);
  </script>
</body>
</html>