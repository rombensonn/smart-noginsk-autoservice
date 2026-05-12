(() => {
    const doc = document;
    const body = doc.body;
    const nav = doc.querySelector('#site-nav');
    const burger = doc.querySelector('[data-menu-toggle]');
    const forms = [...doc.querySelectorAll('[data-smart-form]')];
    const serviceSelects = [...doc.querySelectorAll('[data-service-select]')];
    const params = new URLSearchParams(window.location.search);
    const utmNames = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    const normalizePhone = (value) => {
        let digits = String(value || '').replace(/\D/g, '');

        if (digits.length === 10) {
            digits = `7${digits}`;
        }

        if (digits.length === 11 && digits[0] === '8') {
            digits = `7${digits.slice(1)}`;
        }

        if (digits.length !== 11 || digits[0] !== '7') {
            return '';
        }

        return `+7 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9, 11)}`;
    };

    const setError = (form, name, message) => {
        const error = form.querySelector(`[data-error-for="${name}"]`);
        const field = form.querySelector(`[name="${name}"]`)?.closest('.field');

        if (error) {
            error.textContent = message || '';
        }

        if (field) {
            field.classList.toggle('has-error', Boolean(message));
        }
    };

    const clearErrors = (form) => {
        form.querySelectorAll('.field-error').forEach((error) => {
            error.textContent = '';
        });
        form.querySelectorAll('.field.has-error').forEach((field) => {
            field.classList.remove('has-error');
        });
    };

    const setStatus = (form, message, type) => {
        const status = form.querySelector('.form-status');
        if (!status) return;

        status.textContent = message || '';
        status.classList.remove('is-success', 'is-error');
        if (type) {
            status.classList.add(`is-${type}`);
        }
    };

    const updateHiddenFields = (form) => {
        const pageInput = form.querySelector('[name="page_url"]');
        if (pageInput) {
            pageInput.value = window.location.href;
        }

        utmNames.forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) {
                input.value = params.get(name) || '';
            }
        });
    };

    const updateCsrfTokens = (token) => {
        if (!token) return;
        forms.forEach((form) => {
            const input = form.querySelector('[name="csrf_token"]');
            if (input) {
                input.value = token;
                input.defaultValue = token;
            }
        });
    };

    const validateForm = (form) => {
        clearErrors(form);
        setStatus(form, '', '');

        const name = form.querySelector('[name="name"]');
        const phone = form.querySelector('[name="phone"]');
        const consentPersonalData = form.querySelector('[name="consent_personal_data"]');
        const consentPolicy = form.querySelector('[name="consent_policy"]');
        let valid = true;

        if (!name || name.value.trim().length < 2) {
            setError(form, 'name', 'РЈРєР°Р¶РёС‚Рµ РёРјСЏ РјРёРЅРёРјСѓРј РёР· 2 СЃРёРјРІРѕР»РѕРІ.');
            valid = false;
        }

        const formattedPhone = normalizePhone(phone?.value || '');
        if (!phone || !formattedPhone) {
            setError(form, 'phone', 'РЈРєР°Р¶РёС‚Рµ С‚РµР»РµС„РѕРЅ РІ СЂРѕСЃСЃРёР№СЃРєРѕРј С„РѕСЂРјР°С‚Рµ.');
            valid = false;
        } else {
            phone.value = formattedPhone;
        }

        if (!consentPersonalData?.checked) {
            setError(form, 'consent_personal_data', 'РќСѓР¶РЅРѕ РѕС‚РґРµР»СЊРЅРѕРµ СЃРѕРіР»Р°СЃРёРµ РЅР° РѕР±СЂР°Р±РѕС‚РєСѓ РїРµСЂСЃРѕРЅР°Р»СЊРЅС‹С… РґР°РЅРЅС‹С….');
            valid = false;
        }

        if (!consentPolicy?.checked) {
            setError(form, 'consent_policy', 'РќСѓР¶РЅРѕ РїРѕРґС‚РІРµСЂРґРёС‚СЊ РѕР·РЅР°РєРѕРјР»РµРЅРёРµ СЃ РїРѕР»РёС‚РёРєРѕР№.');
            valid = false;
        }

        if (!valid) {
            setStatus(form, 'РџСЂРѕРІРµСЂСЊС‚Рµ РїРѕР»СЏ С„РѕСЂРјС‹.', 'error');
            const firstError = form.querySelector('.field.has-error input, .field.has-error select, .field.has-error textarea, [name="consent_personal_data"], [name="consent_policy"]');
            firstError?.focus({ preventScroll: false });
        }

        return valid;
    };

    const setButtonLoading = (button, loading) => {
        if (!button) return;
        const label = button.querySelector('span');

        if (!button.dataset.defaultLabel && label) {
            button.dataset.defaultLabel = label.textContent || '';
        }

        button.disabled = loading;
        button.classList.toggle('is-loading', loading);

        if (label) {
            label.textContent = loading ? 'РћС‚РїСЂР°РІР»СЏРµРј...' : button.dataset.defaultLabel;
        }
    };

    forms.forEach((form) => {
        updateHiddenFields(form);

        form.querySelectorAll('[name="phone"]').forEach((input) => {
            input.addEventListener('blur', () => {
                const formatted = normalizePhone(input.value);
                if (formatted) input.value = formatted;
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            updateHiddenFields(form);

            if (!validateForm(form)) {
                return;
            }

            const button = form.querySelector('[type="submit"]');
            setButtonLoading(button, true);

            try {
                if (window.location.hostname.endsWith('github.io')) {
                    setStatus(form, 'Статический предпросмотр: на GitHub Pages PHP-обработчик не запускается. Для реальной заявки откройте сайт на PHP-хостинге.', 'success');
                    return;
                }

                const response = await fetch('/submit.php', {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(form),
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.success) {
                    clearErrors(form);
                    Object.entries(payload.errors || {}).forEach(([field, message]) => setError(form, field, message));
                    setStatus(form, payload.message || 'РќРµ СѓРґР°Р»РѕСЃСЊ РѕС‚РїСЂР°РІРёС‚СЊ Р·Р°СЏРІРєСѓ. РџРѕРїСЂРѕР±СѓР№С‚Рµ РїРѕР·Р¶Рµ.', 'error');
                    return;
                }

                const token = payload.csrf_token;
                form.reset();
                updateCsrfTokens(token);
                updateHiddenFields(form);
                setStatus(form, payload.message || 'Р—Р°СЏРІРєР° РѕС‚РїСЂР°РІР»РµРЅР°. РњС‹ СЃРІСЏР¶РµРјСЃСЏ СЃ РІР°РјРё РґР»СЏ СѓС‚РѕС‡РЅРµРЅРёСЏ РґРµС‚Р°Р»РµР№ Рё Р·Р°РїРёСЃРё.', 'success');
            } catch (error) {
                setStatus(form, 'РќРµ СѓРґР°Р»РѕСЃСЊ РѕС‚РїСЂР°РІРёС‚СЊ Р·Р°СЏРІРєСѓ. РџСЂРѕРІРµСЂСЊС‚Рµ СЃРІСЏР·СЊ РёР»Рё РїРѕР·РІРѕРЅРёС‚Рµ РЅР°Рј.', 'error');
            } finally {
                setButtonLoading(button, false);
            }
        });
    });

    const ensureSelectOption = (select, value) => {
        const exists = [...select.options].some((option) => option.value === value);
        if (!exists) {
            select.add(new Option(value, value));
        }
    };

    const selectService = (service, hint) => {
        if (!service) return;

        serviceSelects.forEach((select) => {
            ensureSelectOption(select, service);
            select.value = service;
            const form = select.closest('form');
            const hintNode = form?.querySelector('[data-service-hint]');
            if (hintNode && hint) {
                hintNode.textContent = hint;
            }
        });

        doc.querySelectorAll('[data-select-service]').forEach((card) => {
            card.classList.toggle('is-selected', card.dataset.selectService === service);
        });

        doc.querySelector('#lead-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    doc.querySelectorAll('[data-select-service]').forEach((button) => {
        button.addEventListener('click', () => {
            selectService(button.dataset.selectService, button.dataset.serviceHint);
        });
    });

    doc.querySelectorAll('[data-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter || 'all';
            doc.querySelectorAll('[data-filter]').forEach((item) => item.classList.toggle('is-active', item === button));
            doc.querySelectorAll('.service-card[data-category]').forEach((card) => {
                const categories = (card.dataset.category || '').split(/\s+/);
                card.classList.toggle('is-hidden', filter !== 'all' && !categories.includes(filter));
            });
        });
    });

    burger?.addEventListener('click', () => {
        const isOpen = !nav?.classList.contains('is-open');
        nav?.classList.toggle('is-open', isOpen);
        burger.classList.toggle('is-open', isOpen);
        burger.setAttribute('aria-expanded', String(isOpen));
        body.classList.toggle('menu-open', isOpen);
    });

    nav?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            nav.classList.remove('is-open');
            burger?.classList.remove('is-open');
            burger?.setAttribute('aria-expanded', 'false');
            body.classList.remove('menu-open');
        });
    });

    const mapButton = doc.querySelector('[data-load-map]');
    mapButton?.addEventListener('click', () => {
        const card = doc.querySelector('[data-map-card]');
        if (!card) return;

        const src = 'https://yandex.ru/map-widget/v1/?text=' + encodeURIComponent('РЎРјР°СЂС‚ Р°РІС‚РѕСЃРµСЂРІРёСЃ РќРѕРіРёРЅСЃРє СѓР». 8 РњР°СЂС‚Р°, 3');
        card.innerHTML = `<iframe title="РљР°СЂС‚Р° РїСЂРѕРµР·РґР° Рє Р°РІС‚РѕСЃРµСЂРІРёСЃСѓ РЎРјР°СЂС‚ РІ РќРѕРіРёРЅСЃРєРµ" loading="lazy" src="${src}" allowfullscreen></iframe>`;
    });

    const revealItems = [...doc.querySelectorAll('.reveal')];
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealItems.forEach((item) => observer.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }
})();

