(function () {
    'use strict';

    // Share link copy on landing page (swaps only the label, keeps the icon)
    document.querySelectorAll('[data-copy-url]').forEach(function (btn) {
        var label = btn.querySelector('[data-copy-text]') || btn;
        var originalText = label.textContent;
        var copiedText = btn.getAttribute('data-copied-label') || 'Copied!';

        function showCopied() {
            label.textContent = copiedText;
            setTimeout(function () { label.textContent = originalText; }, 2200);
        }

        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-copy-url') || window.location.href;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(showCopied);
            } else {
                var tmp = document.createElement('input');
                tmp.value = url;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                document.body.removeChild(tmp);
                showCopied();
            }
        });
    });

    // Social share: native share sheet on touch devices, otherwise the link list
    var touchDevice = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
    document.querySelectorAll('[data-native-share]').forEach(function (box) {
        var toggle = box.querySelector('summary');
        if (!toggle || !navigator.share || !touchDevice) return;

        toggle.addEventListener('click', function (ev) {
            ev.preventDefault();
            navigator.share({
                title: box.getAttribute('data-share-title') || document.title,
                text: box.getAttribute('data-share-text') || '',
                url: box.getAttribute('data-share-url') || window.location.href
            }).catch(function (err) {
                // User cancelled: do nothing. Anything else: fall back to the list.
                if (!err || err.name !== 'AbortError') box.open = true;
            });
        });
    });

    // Animated browser mockups: play once when scrolled into view
    var mocks = document.querySelectorAll('[data-observe]');
    if (mocks.length) {
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-playing');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.35 });
            mocks.forEach(function (el) {
                el.classList.add('is-armed');
                io.observe(el);
            });
        } else {
            mocks.forEach(function (el) { el.classList.add('is-playing'); });
        }
    }

    // Print buttons and auto-submit selects (no inline handlers: CSP blocks them)
    document.querySelectorAll('[data-print]').forEach(function (btn) {
        btn.addEventListener('click', function () { window.print(); });
    });
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (el.form) el.form.submit();
        });
    });

    // Confirmation prompts
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            var msg = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                ev.preventDefault();
            }
        });
    });

    // Dynamic order item rows + live total calculation
    var itemsContainer = document.getElementById('order-items-container');
    var addItemBtn = document.getElementById('add-order-item-btn');
    var rowTemplate = document.getElementById('order-item-row-template');
    var grandTotalEl = document.getElementById('order-grand-total');

    function formatEuro(val) {
        return 'EUR ' + val.toFixed(2).replace('.', ',');
    }

    function recalculateTotals() {
        if (!itemsContainer) return;
        var grand = 0;
        itemsContainer.querySelectorAll('.item-row').forEach(function (row) {
            var qtyInput = row.querySelector('[data-field="qty"]');
            var priceInput = row.querySelector('[data-field="price"]');
            var lineEl = row.querySelector('[data-field="line-total"]');
            var qty = parseFloat((qtyInput ? qtyInput.value : '0').replace(',', '.')) || 0;
            var price = parseFloat((priceInput ? priceInput.value : '0').replace(',', '.')) || 0;
            var line = Math.max(0, qty * price);
            grand += line;
            if (lineEl) {
                lineEl.textContent = formatEuro(line);
            }
        });
        if (grandTotalEl) {
            grandTotalEl.textContent = formatEuro(grand);
        }
    }

    function bindRow(row) {
        var selectEl = row.querySelector('[data-field="product-select"]');
        var nameInput = row.querySelector('[data-field="name"]');
        var unitInput = row.querySelector('[data-field="unit"]');
        var priceInput = row.querySelector('[data-field="price"]');
        var qtyInput = row.querySelector('[data-field="qty"]');
        var removeBtn = row.querySelector('[data-action="remove-row"]');

        if (selectEl) {
            selectEl.addEventListener('change', function () {
                var opt = selectEl.options[selectEl.selectedIndex];
                if (!opt || !opt.value) return;
                if (nameInput) nameInput.value = opt.getAttribute('data-name') || '';
                if (unitInput) unitInput.value = opt.getAttribute('data-unit') || 'stuk';
                if (priceInput) priceInput.value = opt.getAttribute('data-price') || '0.00';
                recalculateTotals();
            });
        }

        if (qtyInput) qtyInput.addEventListener('input', recalculateTotals);
        if (priceInput) priceInput.addEventListener('input', recalculateTotals);

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                var rows = itemsContainer.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    row.remove();
                    recalculateTotals();
                } else if (nameInput && qtyInput && priceInput) {
                    nameInput.value = '';
                    qtyInput.value = '1';
                    priceInput.value = '0.00';
                    if (selectEl) selectEl.value = '';
                    recalculateTotals();
                }
            });
        }
    }

    if (itemsContainer) {
        itemsContainer.querySelectorAll('.item-row').forEach(bindRow);
        recalculateTotals();
    }

    if (addItemBtn && itemsContainer && rowTemplate) {
        addItemBtn.addEventListener('click', function () {
            var clone = rowTemplate.content.firstElementChild.cloneNode(true);
            itemsContainer.appendChild(clone);
            bindRow(clone);
            recalculateTotals();
        });
    }
})();
