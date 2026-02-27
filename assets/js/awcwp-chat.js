(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function addQueryParam(url, key, value) {
        var safeUrl = String(url || '');
        if (!safeUrl) {
            return safeUrl;
        }

        var glue = safeUrl.indexOf('?') > -1 ? '&' : '?';
        return safeUrl + glue + key + '=' + value;
    }

    function appendSUrlToUrl(url) {
        try {
            if (!url) {
                return url;
            }

            var safeUrl = String(url);
            var isWhatsApp = safeUrl.indexOf('https://wa.me') === 0 || safeUrl.indexOf('https://api.whatsapp.com') === 0;
            if (!isWhatsApp) {
                return safeUrl;
            }

            if (safeUrl.indexOf('s_url=') !== -1) {
                return safeUrl;
            }

            var sourceUrl = encodeURIComponent(window.location.href || document.location);
            return safeUrl + (safeUrl.indexOf('?') > -1 ? '&' : '?') + 's_url=' + sourceUrl;
        } catch (error) {
            return url;
        }
    }

    function patchWhatsAppLinks() {
        if (window.awcwpSUrlPatchApplied) {
            return;
        }

        window.awcwpSUrlPatchApplied = true;

        var originalOpen = window.open;
        window.open = function (url, name, specs) {
            var patched = appendSUrlToUrl(url);
            return originalOpen.call(window, patched, name, specs);
        };

        document.addEventListener('click', function (event) {
            var node = event.target;
            while (node && node !== document.body) {
                if (node.tagName && node.tagName.toLowerCase() === 'a' && node.href) {
                    if (node.href.indexOf('https://wa.me') === 0 || node.href.indexOf('https://api.whatsapp.com') === 0) {
                        node.href = appendSUrlToUrl(node.href);
                    }
                    break;
                }
                node = node.parentElement;
            }
        }, true);
    }

    function addUtmParams(formUrl) {
        var ifrmSrc = String(formUrl || '');

        try {
            if (typeof window.ZFAdvLead !== 'undefined' && typeof window.zfutm_zfAdvLead !== 'undefined') {
                for (var i = 0; i < window.ZFAdvLead.utmPNameArr.length; i += 1) {
                    var utmPm = window.ZFAdvLead.utmPNameArr[i];
                    if (window.ZFAdvLead.isSameDomain && window.ZFAdvLead.utmcustPNameArr.indexOf(utmPm) === -1) {
                        utmPm = 'zf_' + utmPm;
                    }
                    var utmValA = window.zfutm_zfAdvLead.zfautm_gC_enc(window.ZFAdvLead.utmPNameArr[i]);
                    if (typeof utmValA !== 'undefined' && utmValA !== '') {
                        ifrmSrc = addQueryParam(ifrmSrc, utmPm, utmValA);
                    }
                }
            }

            if (typeof window.ZFLead !== 'undefined' && typeof window.zfutm_zfLead !== 'undefined') {
                for (var j = 0; j < window.ZFLead.utmPNameArr.length; j += 1) {
                    var utmPmLead = window.ZFLead.utmPNameArr[j];
                    var utmValB = window.zfutm_zfLead.zfutm_gC_enc(window.ZFLead.utmPNameArr[j]);
                    if (typeof utmValB !== 'undefined' && utmValB !== '') {
                        ifrmSrc = addQueryParam(ifrmSrc, utmPmLead, utmValB);
                    }
                }
            }
        } catch (error) {
            return ifrmSrc;
        }

        return ifrmSrc;
    }

    function ensureSUrlInput(container) {
        if (!container) {
            return;
        }

        var sourceUrl = window.location.href || document.location;
        var loadedForm = container.querySelector('form');

        if (loadedForm) {
            var sInput = loadedForm.querySelector('input[name="s_url"], input#s_url');
            if (!sInput) {
                sInput = document.createElement('input');
                sInput.type = 'hidden';
                sInput.name = 's_url';
                sInput.id = 's_url';
                loadedForm.appendChild(sInput);
            }
            sInput.value = sourceUrl;
            return;
        }

        var existing = container.querySelector('input[name="s_url"], input#s_url');
        if (!existing) {
            existing = document.createElement('input');
            existing.type = 'hidden';
            existing.name = 's_url';
            existing.id = 's_url';
            container.appendChild(existing);
        }
        existing.value = sourceUrl;
    }

    function decodePredefinedMessage(value) {
        var raw = String(value || '');
        if (!raw) {
            return 'Hi, I need help.';
        }

        try {
            return decodeURIComponent(raw.replace(/\+/g, '%20'));
        } catch (error) {
            return raw;
        }
    }

    function getMemberData(memberBox) {
        return {
            name: memberBox.getAttribute('data-member-name') || 'Support Team',
            number: String(memberBox.getAttribute('data-account-number') || '').replace(/\D+/g, ''),
            predefinedText: decodePredefinedMessage(memberBox.getAttribute('data-member-predefinedtext')),
            formDetails: memberBox.getAttribute('data-form-details') || '',
            formUrl: memberBox.getAttribute('data-form-url') || ''
        };
    }

    function buildFallbackFormHtml(member, note) {
        var intro = note || 'Share your details and continue to WhatsApp.';

        return [
            '<form class="awcwp-fallback-form">',
            '<p>', intro, '</p>',
            '<div class="form-row"><input type="text" name="name" placeholder="Your Name" required></div>',
            '<div class="form-row"><input type="email" name="email" placeholder="Email Address"></div>',
            '<div class="form-row"><input type="text" name="phone" placeholder="Phone Number"></div>',
            '<div class="form-row"><textarea name="message" placeholder="Message"></textarea></div>',
            '<div class="form-row"><button type="submit" class="awcwp-send-btn">Continue to WhatsApp</button></div>',
            '<div class="awcwp-helper-url" aria-live="polite"></div>',
            '</form>'
        ].join('');
    }

    function buildFallbackMessage(member, formValues) {
        var lines = [member.predefinedText || 'Hi, I need help.'];

        if (formValues.name) {
            lines.push('Name: ' + formValues.name);
        }
        if (formValues.email) {
            lines.push('Email: ' + formValues.email);
        }
        if (formValues.phone) {
            lines.push('Phone: ' + formValues.phone);
        }
        if (formValues.message) {
            lines.push('Message: ' + formValues.message);
        }

        lines.push('Source URL: ' + (window.location.href || document.location));

        return lines.join('\n');
    }

    function bindFallbackSubmit(container, member, strings) {
        var form = container.querySelector('.awcwp-fallback-form');
        if (!form || form.dataset.awcwpBound === '1') {
            return;
        }

        form.dataset.awcwpBound = '1';

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!member.number) {
                var noPhoneMessage = strings.missingPhone || 'Selected member has no WhatsApp number.';
                var helper = form.querySelector('.awcwp-helper-url');
                if (helper) {
                    helper.textContent = noPhoneMessage;
                }
                return;
            }

            var formValues = {
                name: (form.querySelector('[name="name"]') || {}).value || '',
                email: (form.querySelector('[name="email"]') || {}).value || '',
                phone: (form.querySelector('[name="phone"]') || {}).value || '',
                message: (form.querySelector('[name="message"]') || {}).value || ''
            };

            var text = buildFallbackMessage(member, formValues);
            var waUrl = 'https://wa.me/' + member.number + '?text=' + encodeURIComponent(text);
            waUrl = appendSUrlToUrl(waUrl);

            var helperUrl = form.querySelector('.awcwp-helper-url');
            if (helperUrl) {
                helperUrl.textContent = waUrl;
            }

            window.open(waUrl, '_blank', 'noopener,noreferrer');
        });
    }

    function loadMemberForm(member, container, strings) {
        if (!container) {
            return;
        }

        var formUrl = String(member.formUrl || '').trim();
        var formDetails = String(member.formDetails || '').trim();

        if (formUrl && formUrl !== 'null' && formUrl !== 'undefined') {
            var src = addUtmParams(formUrl);
            src = addQueryParam(src, 'zf_rszfm', '1');

            if (src.indexOf('s_url=') === -1) {
                src = addQueryParam(src, 's_url', encodeURIComponent(window.location.href || document.location));
            }

            container.innerHTML = '';

            var iframeWrapper = document.createElement('div');
            iframeWrapper.className = 'awcwp-iframe-wrap';

            var iframe = document.createElement('iframe');
            iframe.src = src;
            iframe.style.border = 'none';
            iframe.style.height = '500px';
            iframe.style.width = '100%';
            iframe.style.transition = 'all 0.5s ease';
            iframe.setAttribute('aria-label', 'WhatsApp Form');

            iframeWrapper.appendChild(iframe);
            container.appendChild(iframeWrapper);
            return;
        }

        if (formDetails && formDetails !== 'null' && formDetails !== 'undefined') {
            container.innerHTML = formDetails;

            if (!container.querySelector('form')) {
                container.innerHTML = buildFallbackFormHtml(member, formDetails);
            }

            ensureSUrlInput(container);
            bindFallbackSubmit(container, member, strings);
            return;
        }

        container.innerHTML = buildFallbackFormHtml(member, 'Share your details and continue to WhatsApp.');
        ensureSUrlInput(container);
        bindFallbackSubmit(container, member, strings);
    }

    function setAriaState(chatBox, isOpen) {
        if (!chatBox) {
            return;
        }

        chatBox.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    function initAutoResizeListener() {
        if (window.awcwpIframeResizeBound) {
            return;
        }

        window.awcwpIframeResizeBound = true;

        window.addEventListener('message', function (event) {
            var data = event.data;
            if (!data || data.constructor !== String) {
                return;
            }

            var parts = data.split('|');
            if (parts.length !== 2 && parts.length !== 3) {
                return;
            }

            var permalink = parts[0];
            var newHeight = (parseInt(parts[1], 10) + 15) + 'px';
            var iframes = document.querySelectorAll('.floating-whatsapp__form-content iframe');

            Array.prototype.forEach.call(iframes, function (iframe) {
                if (!iframe || iframe.src.indexOf('formperma') <= 0 || iframe.src.indexOf(permalink) <= 0) {
                    return;
                }

                var shouldScroll = parts.length === 3;
                if (shouldScroll) {
                    iframe.scrollIntoView();
                }

                if (iframe.style.height !== newHeight) {
                    if (shouldScroll) {
                        setTimeout(function () {
                            iframe.style.height = newHeight;
                        }, 500);
                    } else {
                        iframe.style.height = newHeight;
                    }
                }
            });
        }, false);
    }

    function initWidget(widget, config) {
        var chatBox = widget.querySelector('.floating-whatsapp__chat-box');
        var toggleButton = widget.querySelector('.floating-whatsapp__icon');
        var closeButton = widget.querySelector('.floating-whatsapp__close-btn');
        var memberBoxes = Array.prototype.slice.call(widget.querySelectorAll('.floating-whatsapp__content-item-box'));
        var formWrap = widget.querySelector('.floating-whatsapp__form');
        var formContainer = widget.querySelector('.floating-whatsapp__form-content');

        if (!chatBox || !toggleButton || !formWrap || !formContainer) {
            return;
        }

        var headingTitle = widget.querySelector('.floating-whatsapp__title');
        var headingIntro = widget.querySelector('.floating-whatsapp__intro');

        if (headingTitle && config.title) {
            headingTitle.textContent = config.title;
        }

        if (headingIntro && config.intro) {
            headingIntro.textContent = config.intro;
        }

        widget.classList.remove('awcwp-pos-left', 'awcwp-pos-right');
        widget.classList.add(config.position === 'left' ? 'awcwp-pos-left' : 'awcwp-pos-right');

        function resetChatBox() {
            memberBoxes.forEach(function (box) {
                box.classList.remove('selected');
                box.classList.remove('hidebox');
                box.style.display = '';
                box.style.transitionDelay = '';
            });

            formWrap.style.display = 'none';
            formWrap.classList.remove('show-form');
        }

        function selectMember(memberBox, showForm) {
            memberBoxes.forEach(function (box) {
                box.classList.remove('selected');
            });

            memberBox.classList.add('selected');

            if (showForm) {
                memberBoxes.forEach(function (box) {
                    box.classList.add('hidebox');
                });

                formWrap.style.display = 'block';
                formWrap.classList.add('show-form');
            }

            var member = getMemberData(memberBox);
            loadMemberForm(member, formContainer, config.strings || {});
        }

        toggleButton.addEventListener('click', function () {
            toggleButton.classList.toggle('active');
            chatBox.classList.toggle('active');

            var isOpen = chatBox.classList.contains('active');
            setAriaState(chatBox, isOpen);

            if (isOpen) {
                resetChatBox();
            }
        });

        toggleButton.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleButton.click();
            }
        });

        if (closeButton) {
            closeButton.addEventListener('click', function () {
                toggleButton.classList.remove('active');
                chatBox.classList.remove('active');
                setAriaState(chatBox, false);
                resetChatBox();
            });
        }

        memberBoxes.forEach(function (box) {
            box.addEventListener('click', function () {
                selectMember(box, true);
            });
        });

        if (memberBoxes.length > 0) {
            selectMember(memberBoxes[0], false);
        }
    }

    ready(function () {
        patchWhatsAppLinks();
        initAutoResizeListener();

        var config = window.awcwpData || {};
        var widgets = document.querySelectorAll('.floating-whatsapp');

        Array.prototype.forEach.call(widgets, function (widget) {
            initWidget(widget, {
                title: config.title || '',
                intro: config.intro || '',
                position: config.position === 'left' ? 'left' : 'right',
                strings: config.strings || {}
            });
        });
    });
})();
