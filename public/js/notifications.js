// public/js/notifications.js
// Polling-based notifications UI: obtiene conteo y últimas notificaciones vía API
(function(){
    const POLL_INTERVAL = 8000; // 8s
    let lastCount = null; // null indica que aún no inicializamos

    function $(sel){ return document.querySelector(sel); }

    function playBeep(){
        try{
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.type = 'sine';
            o.frequency.value = 880;
            o.connect(g);
            g.connect(ctx.destination);
            o.start();
            g.gain.setValueAtTime(0.0001, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.1, ctx.currentTime + 0.01);
            setTimeout(()=>{ g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.15); o.stop(); ctx.close(); }, 200);
        }catch(e){/* no audio */}
    }

    function requestPermissionAndNotify(title, body, url){
        if (!('Notification' in window)) return;
        if (Notification.permission === 'granted'){
            const n = new Notification(title, { body: body });
            n.onclick = function(){ window.focus(); if (url) window.location.href = url; };
        } else if (Notification.permission !== 'denied'){
            Notification.requestPermission().then(permission => {
                if (permission === 'granted'){
                    const n = new Notification(title, { body: body });
                    n.onclick = function(){ window.focus(); if (url) window.location.href = url; };
                }
            });
        }
    }

    async function fetchJson(url){
        try{
            const res = await fetch(url, { credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        }catch(e){ console.error('fetchJson error', e); return null; }
    }

    async function updateCount(suppressNotify = false){
        const data = await fetchJson('./index.php?route=api/notifications/unread-count');
        if (!data || !data.success) return;
        const count = parseInt(data.count || 0, 10);
        const badge = $('#notifBadge');
        if (count > 0){
            badge.style.display = '';
            badge.textContent = count > 99 ? '99+' : count;
        } else {
            badge.style.display = 'none';
        }

        if (lastCount === null) {
            // Primera vez: inicializamos sin notificar
            lastCount = count;
            return;
        }

        if (count > lastCount && !suppressNotify){
            // nueva notificación
            playBeep();
            // obtener latest para mostrar
            const latest = await fetchLatest();
            if (latest && latest.length){
                const item = latest[0];
                requestPermissionAndNotify('Nueva notificación', item.mensaje || 'Tienes una nueva notificación', './index.php?route=user/notifications');
            }
        }
        lastCount = count;
    }

    async function fetchLatest(limit=5){
        const data = await fetchJson(`./index.php?route=api/notifications/latest&limit=${limit}`);
        if (!data || !data.success) return [];
        return data.notifications || [];
    }

    function renderNotifications(items){
        const list = $('#notifList');
        if (!list) return;
        list.innerHTML = '';
        if (!items || items.length === 0){
            list.innerHTML = '<div class="text-center text-muted py-3">No hay notificaciones recientes.</div>';
            return;
        }
        items.forEach(n => {
            const div = document.createElement('div');
            div.className = 'd-flex align-items-start p-2 border-bottom';
            if (!n.leido || n.leido == 0) div.style.background = '#fff7e6';
            div.innerHTML = `
                <div class="flex-grow-1">
                    <div class="fw-semibold">${escapeHtml(n.mensaje)}</div>
                    <div class="text-muted small">${formatDate(n.fecha_creacion)}</div>
                </div>
                <div class="ms-2 d-flex flex-column align-items-end">
                    ${n.leido == 0 ? `<button class="btn btn-sm btn-success mb-1 mark-read" data-id="${n.id_notificacion}"><i class="fas fa-check"></i></button>` : ''}
                    <a href="./index.php?route=user/notifications" class="btn btn-sm btn-link text-danger">Ver</a>
                </div>
            `;
            list.appendChild(div);
        });

        // attach handlers
        list.querySelectorAll('.mark-read').forEach(b=>{
            b.addEventListener('click', async function(ev){
                const id = this.getAttribute('data-id');
                if (!id) return;
                await fetchJson(`./index.php?route=api/notifications/mark-read&id=${encodeURIComponent(id)}`);
                await refreshAll();
            });
        });
    }

    function escapeHtml(s){ if (!s) return ''; return String(s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; }); }
    function formatDate(s){ try{ const d = new Date(s); return d.toLocaleString(); }catch(e){ return s || ''; } }

    async function refreshAll(){
        const latest = await fetchLatest(6);
        renderNotifications(latest);
        await updateCount();
    }

    document.addEventListener('DOMContentLoaded', function(){
        const bell = document.getElementById('notifBell');
        const dropdown = document.getElementById('notifDropdown');
        const markAllBtn = document.getElementById('notifMarkAll');

        if (!bell || !dropdown) return;

        bell.addEventListener('click', async function(e){
            e.preventDefault();
            if (dropdown.classList.contains('hidden')){
                dropdown.classList.remove('hidden');
                await refreshAll();
            } else {
                dropdown.classList.add('hidden');
            }
        });

        // Cerrar al hacer click fuera
        document.addEventListener('click', function(e){
            const wrapper = document.getElementById('notifWrapper');
            if (!wrapper) return;
            if (!wrapper.contains(e.target)){
                const dd = document.getElementById('notifDropdown');
                if (dd) dd.classList.add('hidden');
            }
        });

        if (markAllBtn){
            markAllBtn.addEventListener('click', async function(e){
                e.preventDefault();
                await fetchJson('./index.php?route=api/notifications/mark-all-read');
                await refreshAll();
            });
        }

        // Inicial: obtener y no notificar en la primera carga
        updateCount(true);
        // polling
        setInterval(updateCount, POLL_INTERVAL);
    });
})();
