/* AgroRed Hidalgo - utilidades JS compartidas (mapas Leaflet, API, UI) */
const AR = {
  base: (document.querySelector('link[href*="agrored.css"]')?.href || '').replace(/assets\/css\/agrored\.css.*$/, ''),
  centro: [20.14, -99.05],          // Centro aproximado del Valle del Mezquital / Pachuca
  esc: s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])),

  /** Crea un mapa Leaflet con capa de OpenStreetMap. */
  mapa(id, zoom = 9) {
    const m = L.map(id, { scrollWheelZoom: false }).setView(this.centro, zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18, attribution: '&copy; Colaboradores de <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(m);
    m.on('click', () => m.scrollWheelZoom.enable());     // el scroll solo actúa tras hacer clic (mejor en móvil)
    return m;
  },
  /** Ícono de huerto (pin verde con hoja). */
  pin(sel = false) {
    return L.divIcon({ className: '', html: `<div class="pin-huerto ${sel ? 'sel' : ''}"><i class="bi bi-flower1"></i></div>`,
      iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -30] });
  },
  async json(url) {
    const r = await fetch(AR.base + url, { headers: { Accept: 'application/json' } });
    const d = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(d.error || 'Error ' + r.status);
    return d;
  },
  estrellas(n) {
    n = Math.round(n || 0);
    return Array.from({ length: 5 }, (_, i) => `<i class="bi ${i < n ? 'bi-star-fill' : 'bi-star'} text-warning"></i>`).join('');
  },
  popup(h) {
    const aprox = h.aproximada ? '<div class="small text-muted"><i class="bi bi-shield-lock"></i> Ubicación aproximada (inicia sesión para ver la exacta)</div>' : '';
    return `<strong>${AR.esc(h.nombre)}</strong><br>
      <span class="text-muted small">${AR.esc(h.colonia)}, ${AR.esc(h.municipio)}</span><br>
      ${AR.estrellas(h.promedio)} <span class="small">(${h.total_resenas})</span>${aprox}
      <div class="mt-2"><a class="btn btn-sm btn-ar" href="${AR.base}pages/huertos/compra.php?id=${h.id}">Ver / comprar</a></div>`;
  },
  /** Dibuja todos los huertos en un mapa; devuelve los marcadores por id. */
  async marcadores(mapa, params = '') {
    const marcas = {};
    try {
      const hs = await this.json('api/huertos.php' + params);
      const pts = [];
      hs.forEach(h => {
        const mk = L.marker([h.lat, h.lng], { icon: this.pin() }).addTo(mapa).bindPopup(this.popup(h));
        marcas[h.id] = mk; pts.push([h.lat, h.lng]);
      });
      if (pts.length > 1) mapa.fitBounds(pts, { padding: [40, 40] });
    } catch (e) { console.warn(e.message); }
    return marcas;
  }
};

/* Contador de caracteres para textareas con data-contador="#idDestino" */
document.querySelectorAll('textarea[data-contador]').forEach(t => {
  const d = document.querySelector(t.dataset.contador), max = t.maxLength;
  const act = () => d.textContent = `${t.value.length} / ${max}`;
  t.addEventListener('input', act); act();
});
/* Mostrar / ocultar contraseña: botón con data-ver="#idInput" */
document.querySelectorAll('[data-ver]').forEach(b => b.addEventListener('click', () => {
  const i = document.querySelector(b.dataset.ver), v = i.type === 'password';
  i.type = v ? 'text' : 'password';
  b.innerHTML = `<i class="bi ${v ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
}));
