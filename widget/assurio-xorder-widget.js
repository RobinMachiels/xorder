(function () {
    'use strict';

    var STORAGE_KEY = 'assurio_xorder_dismissed_until';
    var ROOT_ID = 'assurio-xorder-sticky-root';

    var COPY = {
        nl: {
            badge: 'Feestseizoen 2026 · Gratis',
            pill: 'Traiteur? Beheer je feestbestellingen gratis',
            title: 'Feestbestellingen zonder gedoe, en in alle zekerheid',
            desc: 'Vanaf nu voor traiteurs, slagers en bakkers: 100% gratis dit seizoen met 1-klik lokale Excel-backup op je eigen pc.',
            cta: 'Ontdek XOrder by AssurIO',
            closeLabel: 'Verberg melding'
        },
        fr: {
            badge: 'Fetes 2026 · Gratuit',
            pill: 'Traiteur ? Gerez vos commandes de fetes gratuitement',
            title: 'Commandes de fetes sans tracas, en toute serenite',
            desc: 'Pour traiteurs, bouchers et boulangers : 100% gratuit cette saison avec backup Excel local en 1 clic sur votre PC.',
            cta: 'Decouvrir XOrder by AssurIO',
            closeLabel: 'Masquer'
        }
    };

    function getDefaultConfig() {
        var userCfg = window.AssurioXOrderConfig || {};
        return {
            startDate: userCfg.startDate || '2026-10-15',
            endDate: userCfg.endDate || '2027-01-05',
            targetUrl: userCfg.targetUrl || 'https://xorder.assurio.be/',
            lang: userCfg.lang || 'auto',
            position: userCfg.position || 'bottom-right',
            dismissDays: typeof userCfg.dismissDays === 'number' ? userCfg.dismissDays : 14,
            forceShow: Boolean(userCfg.forceShow),
            simulateOutsideSeason: Boolean(userCfg.simulateOutsideSeason)
        };
    }

    function resolveLang(langCfg) {
        if (langCfg === 'nl' || langCfg === 'fr') {
            return langCfg;
        }
        var docLang = (document.documentElement.getAttribute('lang') || 'nl').toLowerCase();
        return docLang.indexOf('fr') === 0 ? 'fr' : 'nl';
    }

    function isWithinSeason(startStr, endStr) {
        var now = new Date();
        var today = now.toISOString().slice(0, 10);
        return today >= startStr && today <= endStr;
    }

    function isDismissed() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) return false;
            var until = parseInt(raw, 10);
            return !isNaN(until) && Date.now() < until;
        } catch (e) {
            return false;
        }
    }

    function dismissFor(days) {
        try {
            var until = Date.now() + (Math.max(1, days) * 86400 * 1000);
            window.localStorage.setItem(STORAGE_KEY, String(until));
        } catch (e) {
            // Ignore storage restrictions
        }
    }

    function unmount() {
        var existing = document.getElementById(ROOT_ID);
        if (existing && existing.parentNode) {
            existing.parentNode.removeChild(existing);
        }
    }

    function mount(overrideConfig) {
        unmount();

        var cfg = Object.assign({}, getDefaultConfig(), overrideConfig || {});
        if (cfg.simulateOutsideSeason) {
            return false;
        }
        if (!cfg.forceShow && !isWithinSeason(cfg.startDate, cfg.endDate)) {
            return false;
        }
        if (!cfg.forceShow && isDismissed()) {
            return false;
        }

        var lang = resolveLang(cfg.lang);
        var text = COPY[lang] || COPY.nl;

        var wrap = document.createElement('div');
        wrap.id = ROOT_ID;
        wrap.className = 'assurio-xorder-wrap' + (cfg.position === 'bottom-left' ? ' assurio-xorder-pos-left' : '');
        wrap.setAttribute('role', 'region');
        wrap.setAttribute('aria-label', 'XOrder by AssurIO');

        var card = document.createElement('div');
        card.className = 'assurio-xorder-card';

        var cardHead = document.createElement('div');
        cardHead.className = 'assurio-xorder-card-head';

        var badge = document.createElement('span');
        badge.className = 'assurio-xorder-badge';
        badge.textContent = text.badge;

        var closeCardBtn = document.createElement('button');
        closeCardBtn.type = 'button';
        closeCardBtn.className = 'assurio-xorder-close-card';
        closeCardBtn.setAttribute('aria-label', text.closeLabel);
        closeCardBtn.textContent = '×';
        closeCardBtn.addEventListener('click', function () {
            wrap.classList.remove('is-expanded');
        });

        cardHead.appendChild(badge);
        cardHead.appendChild(closeCardBtn);

        var title = document.createElement('h3');
        title.className = 'assurio-xorder-card-title';
        title.textContent = text.title;

        var desc = document.createElement('p');
        desc.className = 'assurio-xorder-card-desc';
        desc.textContent = text.desc;

        var cta = document.createElement('a');
        cta.className = 'assurio-xorder-card-cta';
        cta.href = cfg.targetUrl + (cfg.targetUrl.indexOf('?') === -1 ? '?lang=' + lang : '&lang=' + lang);
        cta.textContent = text.cta + ' ->';

        card.appendChild(cardHead);
        card.appendChild(title);
        card.appendChild(desc);
        card.appendChild(cta);

        var pillRow = document.createElement('div');
        pillRow.className = 'assurio-xorder-pill-row';

        var pillBtn = document.createElement('button');
        pillBtn.type = 'button';
        pillBtn.className = 'assurio-xorder-pill-btn';

        var icon = document.createElement('span');
        icon.className = 'assurio-xorder-pill-icon';
        icon.textContent = '★';

        var label = document.createElement('span');
        label.textContent = text.pill;

        pillBtn.appendChild(icon);
        pillBtn.appendChild(label);
        pillBtn.addEventListener('click', function () {
            wrap.classList.toggle('is-expanded');
        });

        var dismissBtn = document.createElement('button');
        dismissBtn.type = 'button';
        dismissBtn.className = 'assurio-xorder-dismiss';
        dismissBtn.setAttribute('aria-label', text.closeLabel);
        dismissBtn.setAttribute('title', text.closeLabel);
        dismissBtn.textContent = '×';
        dismissBtn.addEventListener('click', function (ev) {
            ev.stopPropagation();
            dismissFor(cfg.dismissDays);
            unmount();
        });

        pillRow.appendChild(pillBtn);
        pillRow.appendChild(dismissBtn);

        wrap.appendChild(card);
        wrap.appendChild(pillRow);
        document.body.appendChild(wrap);
        return true;
    }

    window.AssurioXOrderWidget = {
        mount: mount,
        unmount: unmount,
        resetDismiss: function () {
            try {
                window.localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                // Ignore
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            mount();
        });
    } else {
        mount();
    }
})();
