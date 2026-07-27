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
    initModalAccessibility();
    initCenteredConfirm();
    initFilterBars();
    initDatetimePickers();
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

function initModalAccessibility() {
    const triggers = new WeakMap();
    let lastInteraction = null;

    const focusable = (modal) => {
        const visible = (element) => !element.closest('[hidden]') && element.getAttribute('aria-hidden') !== 'true';
        const meaningful = Array.from(modal.querySelectorAll(
            '[autofocus], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]):not(.acss-modal__close), a[href], [tabindex]:not([tabindex="-1"])',
        )).filter(visible);
        const close = Array.from(modal.querySelectorAll('.acss-modal__close:not([disabled])')).filter(visible);
        return [...meaningful, ...close];
    };

    const openModals = () => Array.from(document.querySelectorAll('.acss-modal:not([hidden])'));

    const activate = (modal) => {
        const trigger = document.activeElement instanceof HTMLElement && !modal.contains(document.activeElement)
            ? document.activeElement
            : lastInteraction;
        if (trigger instanceof HTMLElement && !modal.contains(trigger)) {
            triggers.set(modal, trigger);
        }

        queueMicrotask(() => {
            if (modal.hidden || modal.contains(document.activeElement)) return;
            const target = focusable(modal)[0];
            if (target) {
                target.focus();
                return;
            }
            modal.dataset.a11yFocusTarget = 'true';
            modal.tabIndex = -1;
            modal.focus();
        });
    };

    const deactivate = (modal) => {
        if (modal.dataset.a11yFocusTarget === 'true') {
            delete modal.dataset.a11yFocusTarget;
            modal.removeAttribute('tabindex');
        }
        const trigger = triggers.get(modal);
        triggers.delete(modal);
        if (trigger?.isConnected) queueMicrotask(() => trigger.focus());
    };

    const observeModal = (modal) => {
        if (!modal.hidden) activate(modal);
    };

    document.addEventListener('click', (event) => {
        if (event.target instanceof HTMLElement) lastInteraction = event.target.closest('button, a, [tabindex]') || event.target;
    }, true);

    document.addEventListener('keydown', (event) => {
        const modal = openModals().at(-1);
        if (!modal) return;

        if (event.key === 'Escape') {
            const closeButton = modal.querySelector('.acss-modal__close:not([disabled])');
            if (!closeButton || modal.matches('[aria-busy="true"], [data-modal-dismiss-disabled]')) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            closeButton.click();
            return;
        }

        if (event.key !== 'Tab') return;
        const items = focusable(modal);
        if (!items.length) {
            event.preventDefault();
            modal.tabIndex = -1;
            modal.focus();
            return;
        }
        const first = items[0];
        const last = items.at(-1);
        if (event.shiftKey && (document.activeElement === first || !modal.contains(document.activeElement))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !modal.contains(document.activeElement))) {
            event.preventDefault();
            first.focus();
        }
    }, true);

    new MutationObserver((records) => {
        records.forEach((record) => {
            if (record.type === 'attributes') {
                if (!record.target.matches('.acss-modal')) return;
                record.target.hidden ? deactivate(record.target) : activate(record.target);
                return;
            }
            record.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) return;
                if (node.matches('.acss-modal')) observeModal(node);
                node.querySelectorAll?.('.acss-modal').forEach(observeModal);
            });
        });
    }).observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['hidden'] });

    document.querySelectorAll('.acss-modal').forEach(observeModal);
}



function initCenteredConfirm() {
    if (window.taConfirm) {
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'acss-modal acss-confirm-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'confirm-modal-title');
    modal.hidden = true;
    modal.innerHTML = `
        <div class="acss-modal__backdrop" data-confirm-cancel></div>
        <div class="acss-modal__dialog acss-confirm-modal__dialog">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title" id="confirm-modal-title">Konfirmasi</h3>
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

    const defaultAcceptText = acceptButton.textContent;

    window.taConfirm = (message, acceptText = defaultAcceptText) => new Promise((resolve) => {
        resolver = resolve;
        messageNode.textContent = message || 'Anda yakin ingin melanjutkan tindakan ini?';
        acceptButton.textContent = acceptText;
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
    const searchBox = document.querySelector('.search-box[data-search-endpoint]');
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
                const title = document.createElement('strong');
                const meta = document.createElement('small');
                title.textContent = item.title ?? '-';
                meta.textContent = `${item.student_name ?? '-'} • ${item.nim ?? '-'}`;
                div.append(title, document.createElement('br'), meta);
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
        if (!event.target.closest('.search-box')) {
            hideSuggestions();
        }
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

function initDatetimePickers() {
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    document.querySelectorAll('[data-acss-datetime-picker]').forEach((wrapper) => {
        const trigger = wrapper.querySelector('[data-acss-datetime-trigger]');
        const valueSpan = wrapper.querySelector('[data-acss-datetime-value]');
        const input = wrapper.querySelector('[data-acss-datetime-input]');
        const panel = wrapper.querySelector('[data-acss-datetime-panel]');
        const prevBtn = wrapper.querySelector('[data-acss-datetime-prev]');
        const nextBtn = wrapper.querySelector('[data-acss-datetime-next]');
        const monthLabel = wrapper.querySelector('[data-acss-datetime-label]');
        const daysContainer = wrapper.querySelector('[data-acss-datetime-days]');
        const hourSelect = wrapper.querySelector('[data-acss-datetime-hour]');
        const minuteSelect = wrapper.querySelector('[data-acss-datetime-minute]');
        const applyBtn = wrapper.querySelector('[data-acss-datetime-apply]');

        if (!trigger || !valueSpan || !input || !panel || !prevBtn || !nextBtn || !monthLabel || !daysContainer || !hourSelect || !minuteSelect || !applyBtn) {
            return;
        }

        for (let i = 0; i < 24; i++) {
            const str = String(i).padStart(2, '0');
            hourSelect.add(new Option(str, str));
        }
        for (let i = 0; i < 60; i += 5) {
            const str = String(i).padStart(2, '0');
            minuteSelect.add(new Option(str, str));
        }

        const minVal = wrapper.dataset.min ? new Date(wrapper.dataset.min) : null;
        let currentVal = input.value ? new Date(input.value) : new Date();
        if (isNaN(currentVal.getTime())) {
            currentVal = new Date();
        }

        let viewingYear = currentVal.getFullYear();
        let viewingMonth = currentVal.getMonth();
        let selectedDate = new Date(currentVal.getFullYear(), currentVal.getMonth(), currentVal.getDate());

        const formatTriggerText = (date, hour, minute) => {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year} ${hour}:${minute}`;
        };

        const formatIsoString = (date, hour, minute) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}T${hour}:${minute}`;
        };

        hourSelect.value = String(currentVal.getHours()).padStart(2, '0');
        minuteSelect.value = String(Math.round(currentVal.getMinutes() / 5) * 5).padStart(2, '0');
        if (minuteSelect.value === '60') {
            minuteSelect.value = '55';
        }
        valueSpan.textContent = formatTriggerText(selectedDate, hourSelect.value, minuteSelect.value);

        panel.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        const renderCalendar = () => {
            monthLabel.textContent = `${monthNames[viewingMonth]} ${viewingYear}`;
            daysContainer.innerHTML = '';

            const firstDayIndex = new Date(viewingYear, viewingMonth, 1).getDay();
            const lastDayDate = new Date(viewingYear, viewingMonth + 1, 0).getDate();

            for (let i = 0; i < firstDayIndex; i++) {
                const span = document.createElement('span');
                span.className = 'acss-datetime-picker__day is-empty';
                daysContainer.appendChild(span);
            }

            for (let day = 1; day <= lastDayDate; day++) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'acss-datetime-picker__day';
                button.textContent = String(day);

                const thisDate = new Date(viewingYear, viewingMonth, day);
                thisDate.setHours(0, 0, 0, 0);

                if (minVal) {
                    const compMin = new Date(minVal.getFullYear(), minVal.getMonth(), minVal.getDate());
                    if (thisDate < compMin) {
                        button.disabled = true;
                    }
                }

                if (selectedDate && thisDate.getTime() === selectedDate.getTime()) {
                    button.classList.add('is-selected');
                }

                button.addEventListener('click', (event) => {
                    event.stopPropagation();
                    selectedDate = new Date(viewingYear, viewingMonth, day);
                    renderCalendar();
                });

                daysContainer.appendChild(button);
            }
        };

        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            if (wrapper.dataset.locked === 'true') {
                return;
            }
            const open = panel.hidden;
            document.querySelectorAll('[data-acss-datetime-panel]').forEach((item) => item.setAttribute('hidden', ''));
            document.querySelectorAll('[data-acss-datetime-picker]').forEach((item) => item.classList.remove('is-open'));

            if (open) {
                panel.removeAttribute('hidden');
                wrapper.classList.add('is-open');
                renderCalendar();
            } else {
                panel.setAttribute('hidden', '');
            }
        });

        prevBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            viewingMonth -= 1;
            if (viewingMonth < 0) {
                viewingMonth = 11;
                viewingYear -= 1;
            }
            renderCalendar();
        });

        nextBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            viewingMonth += 1;
            if (viewingMonth > 11) {
                viewingMonth = 0;
                viewingYear += 1;
            }
            renderCalendar();
        });

        applyBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            const hour = hourSelect.value;
            const minute = minuteSelect.value;
            const isoValue = formatIsoString(selectedDate, hour, minute);

            input.value = isoValue;
            valueSpan.textContent = formatTriggerText(selectedDate, hour, minute);
            panel.setAttribute('hidden', '');
            wrapper.classList.remove('is-open');
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                panel.setAttribute('hidden', '');
                wrapper.classList.remove('is-open');
            }
        });

        const editButton = wrapper.closest('form')?.querySelector('[data-sidang-schedule-toggle]');
        editButton?.addEventListener('click', (event) => {
            if (wrapper.dataset.locked !== 'true') {
                return;
            }

            event.preventDefault();
            wrapper.dataset.locked = 'false';
            wrapper.classList.remove('is-disabled');
            editButton.type = 'submit';
            editButton.textContent = 'Simpan Jadwal';
            trigger.focus();
        });
    });
}
