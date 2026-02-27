(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function normalizeData(data) {
        var safeData = data || {};

        return {
            phone: (safeData.phone || '').replace(/\D+/g, ''),
            message: safeData.message || 'Hello, I need help.',
            text: safeData.text || 'Chat on WhatsApp',
            position: safeData.position === 'left' ? 'left' : 'right'
        };
    }

    function renderChat(data) {
        var config = normalizeData(data || window.awcwpData || {});

        var wrappers = document.querySelectorAll('.awcwp-wrap');
        if (!wrappers.length) {
            return;
        }

        wrappers.forEach(function (wrap) {
            wrap.classList.remove('awcwp-left', 'awcwp-right');
            wrap.classList.add('awcwp-' + config.position);

            var link = wrap.querySelector('.awcwp-button');
            var textNode = wrap.querySelector('.awcwp-text');

            if (textNode) {
                textNode.textContent = config.text;
            }

            if (!link) {
                return;
            }

            if (!config.phone) {
                link.setAttribute('href', '#');
                link.setAttribute('title', 'Add WhatsApp number in plugin settings.');
                return;
            }

            var url = 'https://wa.me/' + config.phone + '?text=' + encodeURIComponent(config.message);
            link.setAttribute('href', url);
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
            link.removeAttribute('title');
        });
    }

    window.awcwpRenderChat = renderChat;

    ready(function () {
        renderChat(window.awcwpData || {});
    });
})();
