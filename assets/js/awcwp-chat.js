(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var data = window.awcwpData || {};
        var phone = (data.phone || '').replace(/\D+/g, '');
        var message = data.message || 'Hello, I need help.';
        var text = data.text || 'Chat on WhatsApp';
        var position = data.position === 'left' ? 'left' : 'right';

        var wrappers = document.querySelectorAll('.awcwp-wrap');
        if (!wrappers.length) {
            return;
        }

        wrappers.forEach(function (wrap) {
            wrap.classList.add('awcwp-' + position);

            var link = wrap.querySelector('.awcwp-button');
            var textNode = wrap.querySelector('.awcwp-text');

            if (textNode) {
                textNode.textContent = text;
            }

            if (!link) {
                return;
            }

            if (!phone) {
                link.setAttribute('href', '#');
                link.setAttribute('title', 'Add WhatsApp number in plugin settings.');
                return;
            }

            var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
            link.setAttribute('href', url);
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    });
})();
