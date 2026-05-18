import './bootstrap';

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-password-target');
        const input = targetId ? document.getElementById(targetId) : null;

        if (!input) {
            return;
        }

        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        button.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const chartBars = document.querySelectorAll('[data-chart-bar]');

    if (chartBars.length) {
        setTimeout(() => {
            chartBars.forEach((bar) => {
                const val = parseFloat(bar.getAttribute('data-value') || 0);
                const max = parseFloat(bar.getAttribute('data-max') || 1);
                bar.style.width = `${(val / max) * 100}%`;
            });
        }, 100);
    }

    initNotifications();
    initTopbarSkripsiSearch();
    initLoginShortcut();
    initCenteredConfirm();
    initFilterBars();
});

function initNotifications() {
    const shell = document.querySelector('[data-notification-shell]');
    const button = document.querySelector('[data-notification-button]');
    const badge = document.querySelector('[data-notification-badge]');
    const dropdown = document.querySelector('[data-notification-dropdown]');
    const list = document.querySelector('[data-notification-list]');
    const summary = document.querySelector('[data-notification-summary]');
    const readAllButton = document.querySelector('[data-notification-read-all]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const userId = document.querySelector('meta[name="auth-user-id"]')?.getAttribute('content');

    if (!shell || !button || !badge || !dropdown || !list || !summary || !readAllButton || !csrfToken || !userId) {
        return;
    }

    const indexUrl = button.dataset.indexUrl;
    const readAllUrl = button.dataset.readAllUrl;

    let loaded = false;

    const setUnreadCount = (value) => {
        const unreadCount = Math.max(0, Number.parseInt(value, 10) || 0);
        button.dataset.unreadCount = String(unreadCount);
        badge.textContent = String(unreadCount);
        badge.hidden = unreadCount === 0;
        button.classList.toggle('has-unread', unreadCount > 0);
        summary.textContent = unreadCount === 0 ? 'No unread notifications' : `${unreadCount} unread`;
    };

    const renderEmpty = (message) => {
        list.innerHTML = `<p class="notification-dropdown__empty">${message}</p>`;
    };

    const renderItems = (items) => {
        if (!items.length) {
            renderEmpty('Belum ada notifikasi.');
            return;
        }

        list.innerHTML = items.map((item) => {
            const unreadClass = item.read_at ? '' : ' is-unread';
            const href = item.url ?? '#';
            const actor = item.actor ? `<small>${escapeHtml(item.actor)} • ${escapeHtml(item.created_at_human ?? '')}</small>` : `<small>${escapeHtml(item.created_at_human ?? '')}</small>`;

            return `
                <a class="notification-item${unreadClass}" href="${href}" data-notification-item data-notification-id="${item.id}">
                    <strong>${escapeHtml(item.title)}</strong>
                    <p>${escapeHtml(item.message)}</p>
                    ${actor}
                </a>
            `;
        }).join('');

        list.querySelectorAll('[data-notification-item]').forEach((item) => {
            item.addEventListener('click', async (event) => {
                const notificationId = item.dataset.notificationId;

                if (!notificationId) {
                    return;
                }

                try {
                    const response = await fetch(`/notifications/${notificationId}/read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    if (response.ok) {
                        const payload = await response.json();
                        item.classList.remove('is-unread');
                        setUnreadCount(payload.unread_count ?? 0);
                    }
                } catch (_error) {
                    event.preventDefault();
                }
            });
        });
    };

    const fetchNotifications = async () => {
        if (!indexUrl) {
            return;
        }

        const response = await fetch(indexUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            renderEmpty('Gagal memuat notifikasi.');
            return;
        }

        const payload = await response.json();
        setUnreadCount(payload.unread_count ?? 0);
        renderItems(payload.items ?? []);
        loaded = true;
    };

    const prependRealtimeItem = (payload) => {
        const existing = payload.id ? list.querySelector(`[data-notification-id="${payload.id}"]`) : null;

        if (existing) {
            existing.remove();
        }

        const item = document.createElement('a');
        item.className = 'notification-item is-unread';
        item.href = payload.url ?? '#';
        item.dataset.notificationItem = 'true';
        if (payload.id) {
            item.dataset.notificationId = payload.id;
        }
        item.innerHTML = `
            <strong>${escapeHtml(payload.title ?? 'Notifikasi')}</strong>
            <p>${escapeHtml(payload.message ?? '')}</p>
            <small>${escapeHtml(payload.actor ?? 'Sistem')} • baru saja</small>
        `;

        item.addEventListener('click', async () => {
            if (!payload.id) {
                return;
            }

            const response = await fetch(`/notifications/${payload.id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                item.classList.remove('is-unread');
                setUnreadCount(data.unread_count ?? 0);
            }
        });

        const empty = list.querySelector('.notification-dropdown__empty');
        if (empty) {
            empty.remove();
        }
        list.prepend(item);
    };

    button.addEventListener('click', async () => {
        const isOpen = !dropdown.hidden;
        dropdown.hidden = isOpen;
        button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');

        if (!isOpen) {
            window.dispatchEvent(new CustomEvent('ta-cloud:dropdown-open', {
                detail: { type: 'notification' },
            }));
        }

        if (!isOpen && !loaded) {
            await fetchNotifications();
        }
    });


    window.addEventListener('ta-cloud:dropdown-open', (event) => {
        const source = event?.detail?.type;
        if (source === 'notification') {
            return;
        }

        dropdown.hidden = true;
        button.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', (event) => {
        if (!shell.contains(event.target)) {
            dropdown.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        }
    });

    readAllButton.addEventListener('click', async () => {
        if (!readAllUrl) {
            return;
        }

        const response = await fetch(readAllUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        setUnreadCount(payload.unread_count ?? 0);
        list.querySelectorAll('.notification-item').forEach((item) => item.classList.remove('is-unread'));
    });

    setUnreadCount(button.dataset.unreadCount || 0);

    if (window.Echo) {
        window.Echo.private(`users.${userId}`).listen('.notification.created', (payload) => {
            setUnreadCount(payload.unread_count ?? Number.parseInt(button.dataset.unreadCount || '0', 10) + 1);
            prependRealtimeItem(payload);

            window.dispatchEvent(new CustomEvent('ta-cloud:notification-received', {
                detail: payload,
            }));
        });
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}



function initCenteredConfirm() {
    if (window.taConfirm) {
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'acss-modal acss-confirm-modal';
    modal.hidden = true;
    modal.innerHTML = `
        <div class="acss-modal__backdrop" data-confirm-cancel></div>
        <div class="acss-modal__dialog acss-confirm-modal__dialog">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Konfirmasi</h3>
                </div>
                <button type="button" class="acss-modal__close" data-confirm-cancel aria-label="Tutup">×</button>
            </div>
            <div class="acss-form-stack-tight acss-confirm-modal__body">
                <p class="acss-confirm-modal__message"></p>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-confirm-cancel>Batal</button>
                    <button type="button" class="button button--danger button--inline" data-confirm-accept>Hapus</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const messageNode = modal.querySelector('.acss-confirm-modal__message');
    const acceptButton = modal.querySelector('[data-confirm-accept]');
    const cancelButtons = modal.querySelectorAll('[data-confirm-cancel]');
    let resolver = null;

    const close = (result) => {
        modal.hidden = true;
        document.body.classList.remove('acss-modal-open');
        if (resolver) {
            resolver(result);
            resolver = null;
        }
    };

    cancelButtons.forEach((button) => button.addEventListener('click', () => close(false)));
    acceptButton.addEventListener('click', () => close(true));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            close(false);
        }
    });

    window.taConfirm = (message) => new Promise((resolve) => {
        resolver = resolve;
        messageNode.textContent = message || 'Anda yakin ingin melanjutkan tindakan ini?';
        modal.hidden = false;
        document.body.classList.add('acss-modal-open');
    });

    document.querySelectorAll('form[onsubmit*="confirm("]').forEach((form) => {
        const raw = form.getAttribute('onsubmit') || '';
        const match = raw.match(/confirm\((['"])(.*?)\)/);
        if (!match) return;
        form.dataset.confirmMessage = match[2];
        form.removeAttribute('onsubmit');
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const message = form.dataset.confirmMessage;
        if (!message) return;
        if (form.dataset.confirmApproved === '1') {
            delete form.dataset.confirmApproved;
            return;
        }
        event.preventDefault();
        const confirmed = await window.taConfirm(message);
        if (!confirmed) return;
        form.dataset.confirmApproved = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(event.submitter || undefined);
        } else {
            form.submit();
        }
    }, true);
}

initTopbarUserMenu();
initTopbarSkripsiSearch();

function initTopbarUserMenu() {
    const shell = document.querySelector('[data-user-menu-shell]');
    if (!shell) return;

    const trigger = shell.querySelector('[data-user-menu-trigger]');
    const dropdown = shell.querySelector('[data-user-dropdown]');
    if (!trigger || !dropdown) return;

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const isHidden = dropdown.hasAttribute('hidden');
        if (isHidden) {
            dropdown.removeAttribute('hidden');
            trigger.setAttribute('aria-expanded', 'true');
            window.dispatchEvent(new CustomEvent('ta-cloud:dropdown-open', {
                detail: { type: 'user-menu' },
            }));
        } else {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    window.addEventListener('ta-cloud:dropdown-open', (event) => {
        if (event?.detail?.type === 'user-menu') {
            return;
        }
        dropdown.setAttribute('hidden', '');
        trigger.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', (event) => {
        if (!shell.contains(event.target)) {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });
}

function initTopbarSkripsiSearch() {
    const searchBox = document.querySelector('.top-bar__actions .search-box[data-search-endpoint]');
    const input = document.getElementById('ta-search');
    const suggestions = document.getElementById('topbar-ta-suggestions');
    if (!searchBox || !input || !suggestions) return;

    const endpoint = searchBox.dataset.searchEndpoint;
    const resultsUrl = searchBox.dataset.searchResultsUrl || endpoint;
    let debounceTimer;

    const hideSuggestions = () => {
        suggestions.style.display = 'none';
    };

    const openIndexResult = (item) => {
        if (item?.url) {
            window.location.href = item.url;
            return;
        }

        window.location.href = `${resultsUrl}?q=${encodeURIComponent(item.title ?? '')}`;
    };

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        if (query.length < 2) {
            suggestions.innerHTML = '';
            hideSuggestions();
            return;
        }

        debounceTimer = setTimeout(async () => {
            const response = await fetch(`${endpoint}?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                hideSuggestions();
                return;
            }

            const payload = await response.json();
            suggestions.innerHTML = '';

            (payload.suggestions || []).forEach((item) => {
                const div = document.createElement('div');
                div.className = 'skripsi-suggestion acss-topbar-suggestion';
                div.innerHTML = `<strong>${item.title ?? '-'}</strong><br><small>${item.student_name ?? '-'} • ${item.nim ?? '-'}</small>`;
                div.addEventListener('click', () => {
                    input.value = item.title ?? '';
                    hideSuggestions();
                    openIndexResult(item);
                });
                suggestions.appendChild(div);
            });

            suggestions.style.display = suggestions.children.length ? 'block' : 'none';
        }, 250);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const first = suggestions.querySelector('.skripsi-suggestion');
        if (first) {
            first.click();
            return;
        }
        if (input.value.trim() !== '') {
            window.location.href = `${resultsUrl}?q=${encodeURIComponent(input.value.trim())}`;
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.top-bar__actions .search-box')) {
            hideSuggestions();
        }
    });
}


function initLoginShortcut() {
    const shortcut = document.getElementById('login-shortcut-select');
    const email = document.getElementById('email');
    const password = document.getElementById('password');

    if (!shortcut || !email || !password) return;

    shortcut.addEventListener('change', () => {
        const option = shortcut.options[shortcut.selectedIndex];
        if (!option || !option.dataset.email) return;

        email.value = option.dataset.email || '';
        password.value = option.dataset.password || '';
        password.focus();
    });
}


function initFilterBars() {
    document.querySelectorAll('.filter-bar').forEach((bar) => {
        bar.classList.remove('filter-bar--count-1', 'filter-bar--count-2', 'filter-bar--count-3', 'filter-bar--count-4');

        const fields = Array.from(bar.children).filter((child) => {
            if (!(child instanceof HTMLElement)) return false;
            if (child.tagName === 'STYLE' || child.hidden) return false;
            if (!child.matches('label, .form-field, div')) return false;

            const computed = window.getComputedStyle(child);
            return computed.display !== 'none' && computed.visibility !== 'hidden';
        });

        const count = Math.max(1, Math.min(fields.length, 4));
        bar.classList.add(`filter-bar--count-${count}`);
    });
}
