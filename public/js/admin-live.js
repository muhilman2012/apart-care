(() => {
    document.querySelector('[data-print-page]')?.addEventListener('click', () => window.print());

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const center = document.querySelector('.notification-center');

    if (center) {
        const button = center.querySelector('.notification-button');
        const panel = center.querySelector('.notification-panel');
        const count = center.querySelector('.notification-count');
        const list = center.querySelector('.notification-list');

        button.addEventListener('click', () => {
            panel.hidden = !panel.hidden;
            button.setAttribute('aria-expanded', String(!panel.hidden));
        });

        document.addEventListener('click', (event) => {
            if (!center.contains(event.target)) {
                panel.hidden = true;
                button.setAttribute('aria-expanded', 'false');
            }
        });

        const renderNotifications = (payload) => {
            count.textContent = payload.count > 99 ? '99+' : payload.count;
            count.hidden = payload.count === 0;
            list.replaceChildren();

            if (!payload.notifications.length) {
                const empty = document.createElement('div');
                empty.className = 'notification-empty';
                empty.textContent = 'Tidak ada notifikasi baru.';
                list.append(empty);
                return;
            }

            payload.notifications.forEach((notification) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'notification-item';
                item.innerHTML = `<span></span><div><strong></strong><p></p><small></small></div>`;
                item.querySelector('strong').textContent = notification.title;
                item.querySelector('p').textContent = notification.message;
                item.querySelector('small').textContent = `${notification.ticket_number} · ${notification.time}`;
                item.addEventListener('click', async () => {
                    item.disabled = true;
                    const response = await fetch(notification.read_url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                    if (response.ok) {
                        const result = await response.json();
                        window.location.assign(result.url);
                    } else {
                        item.disabled = false;
                    }
                });
                list.append(item);
            });
        };

        const refreshNotifications = async () => {
            try {
                const response = await fetch(center.dataset.indexUrl, { headers: { 'Accept': 'application/json' } });
                if (response.ok) renderNotifications(await response.json());
            } catch (_) {}
        };

        refreshNotifications();
        window.setInterval(refreshNotifications, 10000);
    }

    const dashboard = document.querySelector('#dashboard-live');
    if (dashboard) {
        const refreshDashboard = async () => {
            if (document.hidden) return;
            try {
                const response = await fetch(dashboard.dataset.feedUrl, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const payload = await response.json();
                dashboard.innerHTML = payload.html;
                const updated = document.querySelector('#dashboard-updated');
                if (updated) updated.textContent = `Diperbarui ${payload.updated_at}`;
            } catch (_) {}
        };

        window.setInterval(refreshDashboard, 5000);
    }
})();
