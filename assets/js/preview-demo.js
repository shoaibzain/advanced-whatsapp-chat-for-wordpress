(function () {
    var demoConfig = {
        title: 'Hi, how can we help?',
        intro: 'Choose a consultant and continue on WhatsApp.',
        members: [
            {
                name: 'Sara Ahmed',
                language: 'English, Arabic',
                number: '971501112233',
                predefinedText: 'Hi Sara, I need help with company setup in UAE.',
                formDetails: 'Tell us your requirement and we will guide you quickly.'
            },
            {
                name: 'Ali Khan',
                language: 'English, Urdu, Hindi',
                number: '971502224466',
                predefinedText: 'Hello Ali, I want a consultation for tax and accounting.',
                formDetails: 'Share your details so Ali can reply with the right package.'
            },
            {
                name: 'Mariam Noor',
                language: 'English, French',
                number: '971503337788',
                predefinedText: 'Hi Mariam, I need support for visa process.',
                formDetails: 'Add your message and Mariam will contact you shortly.'
            }
        ]
    };

    var state = {
        selectedMember: null
    };

    function getInitials(fullName) {
        var parts = String(fullName || '').trim().split(/\s+/);
        if (!parts.length || !parts[0]) {
            return 'WA';
        }
        if (parts.length === 1) {
            return parts[0].slice(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function buildMemberCard(member) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'floating-whatsapp__content-item-box';
        button.setAttribute('data-account-number', member.number);
        button.setAttribute('data-member-name', member.name);
        button.setAttribute('data-member-language', member.language);
        button.setAttribute('data-member-predefinedtext', member.predefinedText);
        button.setAttribute('data-form-details', member.formDetails);

        button.innerHTML =
            '<div class="floating-whatsapp__avatar">' + getInitials(member.name) + '</div>' +
            '<div class="floating-whatsapp__txt">' +
            '<div class="floating-whatsapp__member-name"></div>' +
            '<div class="floating-whatsapp__member-language"></div>' +
            '</div>';

        button.querySelector('.floating-whatsapp__member-name').textContent = member.name;
        button.querySelector('.floating-whatsapp__member-language').textContent = member.language;

        return button;
    }

    function resetChatBox(context) {
        context.memberBoxes.forEach(function (box) {
            box.classList.remove('selected', 'hidebox');
        });

        context.form.classList.remove('show-form');
        context.form.style.display = 'none';
        context.helper.textContent = '';
        state.selectedMember = null;
        context.formEl.reset();
    }

    function showFormForMember(context, memberBox) {
        context.memberBoxes.forEach(function (box) {
            box.classList.remove('selected');
            box.classList.add('hidebox');
        });

        memberBox.classList.remove('hidebox');
        memberBox.classList.add('selected');
        state.selectedMember = {
            number: String(memberBox.getAttribute('data-account-number') || '').replace(/\D+/g, ''),
            name: memberBox.getAttribute('data-member-name') || 'Consultant',
            language: memberBox.getAttribute('data-member-language') || '',
            predefinedText: memberBox.getAttribute('data-member-predefinedtext') || 'Hi, I need help.',
            formDetails: memberBox.getAttribute('data-form-details') || ''
        };

        context.formDetails.textContent = state.selectedMember.formDetails || 'Share your details and continue to WhatsApp.';
        context.form.classList.add('show-form');
        context.form.style.display = 'block';
    }

    function buildMessage(member, formValues) {
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
        lines.push('Source URL: ' + (window.location.href || ''));
        return lines.join('\n');
    }

    function createWhatsAppUrl(number, message) {
        var clean = String(number || '').replace(/\D+/g, '');
        if (!clean) {
            return '';
        }

        var base = 'https://wa.me/' + clean + '?text=' + encodeURIComponent(message || 'Hi');
        var sUrl = encodeURIComponent(window.location.href || '');
        return base + '&s_url=' + sUrl;
    }

    function init() {
        var chatBox = document.querySelector('.floating-whatsapp__chat-box');
        var toggleButton = document.querySelector('.floating-whatsapp__icon');
        var closeButton = document.querySelector('.floating-whatsapp__close-btn');
        var memberList = document.querySelector('.floating-whatsapp__content-list');
        var form = document.querySelector('.floating-whatsapp__form');
        var formDetails = document.getElementById('wa-form-details');
        var helper = document.getElementById('wa-helper-url');
        var formEl = document.getElementById('wa-lead-form');
        var backBtn = document.getElementById('wa-back');

        if (!chatBox || !toggleButton || !memberList || !form || !formEl || !backBtn) {
            return;
        }

        var titleEl = document.querySelector('.floating-whatsapp__title');
        var introEl = document.querySelector('.floating-whatsapp__intro');

        if (titleEl) {
            titleEl.textContent = demoConfig.title;
        }

        if (introEl) {
            introEl.textContent = demoConfig.intro;
        }

        memberList.innerHTML = '';
        demoConfig.members.forEach(function (member) {
            memberList.appendChild(buildMemberCard(member));
        });

        var memberBoxes = Array.prototype.slice.call(
            document.querySelectorAll('.floating-whatsapp__content-item-box')
        );

        var context = {
            chatBox: chatBox,
            toggleButton: toggleButton,
            memberBoxes: memberBoxes,
            form: form,
            formDetails: formDetails,
            helper: helper,
            formEl: formEl
        };

        toggleButton.addEventListener('click', function () {
            toggleButton.classList.toggle('active');
            chatBox.classList.toggle('active');
            chatBox.setAttribute('aria-hidden', chatBox.classList.contains('active') ? 'false' : 'true');
            if (chatBox.classList.contains('active')) {
                resetChatBox(context);
            }
        });

        if (closeButton) {
            closeButton.addEventListener('click', function () {
                toggleButton.classList.remove('active');
                chatBox.classList.remove('active');
                chatBox.setAttribute('aria-hidden', 'true');
                resetChatBox(context);
            });
        }

        memberBoxes.forEach(function (box) {
            box.addEventListener('click', function () {
                showFormForMember(context, box);
            });
        });

        backBtn.addEventListener('click', function () {
            resetChatBox(context);
        });

        formEl.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!state.selectedMember || !state.selectedMember.number) {
                helper.textContent = 'Please choose a team member first.';
                return;
            }

            var values = {
                name: document.getElementById('wa-name').value.trim(),
                email: document.getElementById('wa-email').value.trim(),
                phone: document.getElementById('wa-phone').value.trim(),
                message: document.getElementById('wa-message').value.trim()
            };

            var message = buildMessage(state.selectedMember, values);
            var url = createWhatsAppUrl(state.selectedMember.number, message);

            if (!url) {
                helper.textContent = 'Missing WhatsApp number in demo data.';
                return;
            }

            helper.textContent = url;
            window.open(url, '_blank', 'noopener,noreferrer');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                toggleButton.classList.remove('active');
                chatBox.classList.remove('active');
                chatBox.setAttribute('aria-hidden', 'true');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
