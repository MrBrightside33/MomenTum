const initializeSettings = () => {
    const root = document.querySelector('[data-settings-root]');

    if (!root) {
        return;
    }

    const avatarInput = root.querySelector('[data-avatar-input]');
    const avatarPreview = root.querySelector('[data-avatar-preview]');
    const avatarFallback = root.querySelector('[data-avatar-fallback]');
    const avatarName = root.querySelector('[data-avatar-name]');
    const avatarSelect = root.querySelector('[data-avatar-select]');
    const originalTheme = document.documentElement.dataset.theme ?? 'system';
    const preferenceForm = root.querySelector('[data-preferences-form]');
    const newPassword = root.querySelector('#password');
    const passwordConfirmation = root.querySelector('#password_confirmation');
    const passwordFeedback = root.querySelector('[data-password-feedback]');
    let previewObjectUrl = null;

    avatarSelect?.addEventListener('click', () => {
        if (avatarInput instanceof HTMLInputElement) {
            avatarInput.click();
        }
    });

    avatarInput?.addEventListener('change', () => {
        if (!(avatarInput instanceof HTMLInputElement) || !avatarInput.files?.[0]) {
            return;
        }

        if (avatarPreview instanceof HTMLImageElement) {
            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
            }

            previewObjectUrl = URL.createObjectURL(avatarInput.files[0]);
            avatarPreview.src = previewObjectUrl;
            avatarPreview.classList.remove('hidden');
        }

        avatarFallback?.classList.add('hidden');

        if (avatarName) {
            avatarName.textContent = avatarInput.files[0].name;
        }
    });

    root.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        if (!(toggle instanceof HTMLButtonElement)) {
            return;
        }

        const passwordInput = root.querySelector(`#${CSS.escape(toggle.dataset.passwordToggle ?? '')}`);
        const passwordIcons = toggle.querySelectorAll('svg');

        toggle.addEventListener('click', () => {
            if (!(passwordInput instanceof HTMLInputElement)) {
                return;
            }

            const isPasswordVisible = passwordInput.type === 'text';

            passwordInput.type = isPasswordVisible ? 'password' : 'text';
            passwordIcons[0]?.classList.toggle('hidden', !isPasswordVisible);
            passwordIcons[1]?.classList.toggle('hidden', isPasswordVisible);
            toggle.setAttribute('aria-label', isPasswordVisible ? 'Show password' : 'Hide password');
        });
    });

    const updatePasswordFeedback = () => {
        if (!(newPassword instanceof HTMLInputElement)
            || !(passwordConfirmation instanceof HTMLInputElement)
            || !passwordFeedback) {
            return;
        }

        if (passwordConfirmation.value.length === 0) {
            passwordFeedback.textContent = 'Use at least 8 characters. Your other active sessions will be signed out when you change your password.';
            passwordFeedback.classList.remove('text-emerald-600', 'text-red-600');
            passwordFeedback.classList.add('text-slate-500');

            return;
        }

        const passwordsMatch = newPassword.value === passwordConfirmation.value;

        passwordFeedback.textContent = passwordsMatch ? 'Passwords match.' : 'The new passwords do not match.';
        passwordFeedback.classList.toggle('text-emerald-600', passwordsMatch);
        passwordFeedback.classList.toggle('text-red-600', !passwordsMatch);
        passwordFeedback.classList.toggle('text-slate-500', false);
    };

    newPassword?.addEventListener('input', updatePasswordFeedback);
    passwordConfirmation?.addEventListener('input', updatePasswordFeedback);

    preferenceForm?.querySelectorAll('[data-appearance-option]').forEach((option) => {
        option.addEventListener('change', () => {
            if (option instanceof HTMLInputElement && option.checked) {
                document.documentElement.dataset.theme = option.value;
            }
        });
    });

    root.querySelectorAll('form[data-settings-form]').forEach((form) => {
        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                if (form === preferenceForm) {
                    document.documentElement.dataset.theme = originalTheme;
                }

                if (form.contains(avatarInput) && avatarPreview instanceof HTMLImageElement) {
                    const initialAvatar = avatarPreview.dataset.initialAvatar ?? '';

                    if (previewObjectUrl) {
                        URL.revokeObjectURL(previewObjectUrl);
                        previewObjectUrl = null;
                    }

                    if (initialAvatar) {
                        avatarPreview.src = initialAvatar;
                    } else {
                        avatarPreview.removeAttribute('src');
                    }
                    avatarPreview.classList.toggle('hidden', initialAvatar.length === 0);
                    avatarFallback?.classList.toggle('hidden', initialAvatar.length > 0);

                    if (avatarName) {
                        avatarName.textContent = 'JPG, PNG, or WebP up to 2 MB';
                    }
                }
            });
        });

        form.addEventListener('submit', (event) => {
            if (!(form instanceof HTMLFormElement) || form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');

            const submitButton = event.submitter;

            if (submitButton instanceof HTMLButtonElement) {
                submitButton.disabled = true;
                submitButton.textContent = submitButton.dataset.loadingLabel ?? 'Saving…';
            }
        });
    });
};

document.addEventListener('page:load', initializeSettings);
initializeSettings();
