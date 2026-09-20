import { initToasts, notify } from './toasts';
import {
    Accessibility,
    ArchiveRestore,
    BarChart3,
    Bot,
    Cake,
    CalendarDays,
    CalendarPlus,
    CalendarX,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    ClipboardList,
    ClipboardPlus,
    Clock,
    ContactRound,
    Eye,
    EyeOff,
    FileText,
    HeartPulse,
    History,
    Info,
    LockKeyhole,
    LoaderCircle,
    LogIn,
    LogOut,
    Mail,
    MapPin,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    Pencil,
    Phone,
    Plus,
    RotateCcw,
    Search,
    Send,
    ShieldCheck,
    SlidersHorizontal,
    TriangleAlert,
    Trash2,
    UserCog,
    UserRound,
    Users,
    X,
    createIcons,
} from 'lucide';

// Os campos Material são usados somente nas páginas de autenticação.
if (document.querySelector('md-filled-text-field')) {
    import('@material/web/textfield/filled-text-field.js');
}

const renderIcons = (root = document) => createIcons({
    root,
    icons: {
        Accessibility,
        ArchiveRestore,
        BarChart3,
        Bot,
        Cake,
        CalendarDays,
        CalendarPlus,
        CalendarX,
        Check,
        CheckCircle2,
        ChevronDown,
        ChevronLeft,
        ChevronRight,
        CircleAlert,
        ClipboardList,
        ClipboardPlus,
        Clock,
        ContactRound,
        Eye,
        EyeOff,
        FileText,
        HeartPulse,
        History,
        Info,
        LockKeyhole,
        LoaderCircle,
        LogIn,
        LogOut,
        Mail,
        MapPin,
        Menu,
        PanelLeftClose,
        PanelLeftOpen,
        Pencil,
        Phone,
        Plus,
        RotateCcw,
        Search,
        Send,
        ShieldCheck,
        SlidersHorizontal,
        TriangleAlert,
        Trash2,
        UserCog,
        UserRound,
        Users,
        X,
    },
});

renderIcons();
initToasts({ renderIcons });

document.querySelectorAll('[data-chatbot]').forEach((chatbot) => {
    const form = chatbot.querySelector('[data-chatbot-form]');
    const input = chatbot.querySelector('[data-chatbot-input]');
    const submitButton = chatbot.querySelector('[data-chatbot-submit]');
    const submitLabel = chatbot.querySelector('[data-chatbot-submit-label]');
    const submitLoading = chatbot.querySelector('[data-chatbot-submit-loading]');
    const messages = chatbot.querySelector('[data-chatbot-messages]');
    const loadingStatus = chatbot.querySelector('[data-chatbot-loading]');
    const errorBox = chatbot.querySelector('[data-chatbot-error]');
    const characterCount = chatbot.querySelector('[data-chatbot-character-count]');
    const suggestions = chatbot.querySelectorAll('[data-chatbot-suggestion]');

    if (!form || !input || !submitButton || !messages || !loadingStatus || !errorBox) {
        return;
    }

    let isSubmitting = false;
    let pendingRequest = null;

    window.addEventListener('pagehide', () => pendingRequest?.abort());

    const updateCharacterCount = () => {
        if (characterCount) {
            characterCount.textContent = `${input.value.length}/800`;
        }
    };

    const scrollMessagesToEnd = () => {
        messages.scrollTop = messages.scrollHeight;
    };

    const appendMessage = (author, text) => {
        const isUser = author === 'user';
        const article = document.createElement('article');
        const avatar = document.createElement('span');
        const content = document.createElement('div');
        const authorLabel = document.createElement('p');
        const body = document.createElement('p');

        article.className = `flex items-start gap-3 ${isUser ? 'justify-end' : ''}`;
        avatar.className = isUser
            ? 'order-2 inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-stone-800 text-[0.65rem] font-extrabold text-white'
            : 'inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[0.65rem] font-extrabold text-brand-800';
        content.className = isUser
            ? 'max-w-[85%] rounded-2xl rounded-tr-sm bg-stone-900 px-4 py-3 text-white shadow-sm sm:max-w-[75%]'
            : 'max-w-[85%] rounded-2xl rounded-tl-sm border border-brand-100 bg-brand-50/70 px-4 py-3 text-stone-800 sm:max-w-[75%]';
        authorLabel.className = isUser
            ? 'text-xs font-extrabold text-stone-200'
            : 'text-xs font-extrabold text-brand-800';
        body.className = `mt-1 whitespace-pre-wrap break-words text-sm leading-6 ${isUser ? 'text-white' : 'text-stone-700'}`;

        avatar.textContent = isUser ? 'VOCÊ' : 'IA';
        avatar.setAttribute('aria-hidden', 'true');
        authorLabel.textContent = isUser ? 'Você' : 'Chatbot';
        body.textContent = text;

        content.append(authorLabel, body);
        article.append(avatar, content);
        messages.append(article);
        scrollMessagesToEnd();
    };

    const showError = (message) => {
        errorBox.querySelector('[data-chatbot-error-message]').textContent = message;
        errorBox.classList.remove('hidden');
        errorBox.focus();
    };

    const hideError = () => {
        errorBox.classList.add('hidden');
    };

    const setLoading = (loading) => {
        isSubmitting = loading;
        submitButton.disabled = loading;
        input.disabled = loading;
        chatbot.setAttribute('aria-busy', String(loading));
        submitButton.setAttribute('aria-busy', String(loading));
        submitLabel?.classList.toggle('hidden', loading);
        submitLoading?.classList.toggle('hidden', !loading);
        loadingStatus.classList.toggle('hidden', !loading);
        loadingStatus.classList.toggle('flex', loading);
        loadingStatus.setAttribute('aria-hidden', String(!loading));
        suggestions.forEach((suggestion) => {
            suggestion.disabled = loading;
        });

        if (loading) {
            messages.append(loadingStatus);
            scrollMessagesToEnd();
        }
    };

    suggestions.forEach((suggestion) => {
        suggestion.addEventListener('click', () => {
            if (isSubmitting) {
                return;
            }

            input.value = suggestion.dataset.chatbotSuggestion ?? '';
            updateCharacterCount();
            input.focus();
        });
    });

    input.addEventListener('input', updateCharacterCount);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        const message = input.value.trim();

        hideError();

        if (message.length < 3) {
            showError('Digite uma pergunta com pelo menos 3 caracteres.');
            input.focus();
            return;
        }

        if (message.length > 800) {
            showError('A pergunta pode ter no máximo 800 caracteres.');
            input.focus();
            return;
        }

        appendMessage('user', message);
        setLoading(true);

        const formData = new FormData(form);
        formData.set('message', message);
        const controller = new AbortController();
        pendingRequest = controller;
        let timedOut = false;
        const timeout = window.setTimeout(() => {
            timedOut = true;
            controller.abort();
        }, 30000);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                signal: controller.signal,
            });

            let payload = {};

            try {
                payload = await response.json();
            } catch {
                payload = {};
            }

            if (controller.signal.aborted) {
                throw new DOMException('Requisição cancelada.', 'AbortError');
            }

            if (!response.ok) {
                const validationMessage = Object.values(payload.errors ?? {})
                    .flat()
                    .find((error) => typeof error === 'string');

                if (validationMessage) {
                    showError(validationMessage);
                    return;
                }

                throw new Error(
                    payload.message
                    ?? 'Não foi possível enviar sua pergunta. Tente novamente.'
                );
            }

            if (typeof payload.answer !== 'string' || payload.answer.trim() === '') {
                throw new Error('O chatbot retornou uma resposta inválida. Tente novamente.');
            }

            appendMessage('assistant', payload.answer.trim());
            input.value = '';
            updateCharacterCount();
        } catch (error) {
            if (controller.signal.aborted && !timedOut) {
                return;
            }
            notify({
                type: 'error',
                title: 'Não foi possível enviar a pergunta',
                message: timedOut
                    ? 'A resposta demorou mais que o esperado. Tente novamente em alguns instantes.'
                    : error instanceof Error
                    ? error.message
                    : 'Não foi possível enviar sua pergunta. Tente novamente.',
            });
        } finally {
            window.clearTimeout(timeout);
            pendingRequest = null;
            setLoading(false);
            input.focus();
        }
    });

    updateCharacterCount();
});

document.querySelectorAll('[data-password-field-toggle]').forEach((toggle) => {
    const inputId = toggle.dataset.passwordFieldToggle;
    const input = inputId ? document.getElementById(inputId) : null;
    const showIcon = toggle.querySelector('[data-password-show-icon]');
    const hideIcon = toggle.querySelector('[data-password-hide-icon]');

    if (!input) {
        return;
    }

    toggle.addEventListener('click', () => {
        const shouldShow = input.type === 'password';

        input.type = shouldShow ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(shouldShow));
        toggle.setAttribute(
            'aria-label',
            shouldShow ? toggle.dataset.passwordHideLabel : toggle.dataset.passwordShowLabel,
        );
        showIcon?.classList.toggle('hidden', shouldShow);
        hideIcon?.classList.toggle('hidden', !shouldShow);
        input.focus();
    });
});

document.querySelectorAll('[data-row-link]').forEach((row) => {
    const navigateToRowDetails = () => {
        if (row.dataset.rowLink) {
            window.location.assign(row.dataset.rowLink);
        }
    };

    row.addEventListener('click', (event) => {
        if (event.target.closest('a, button, input, select, textarea')) {
            return;
        }

        navigateToRowDetails();
    });

    row.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        navigateToRowDetails();
    });
});

const sidebar = document.querySelector('[data-sidebar]');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
const sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');
const sidebarLinks = document.querySelectorAll('[data-sidebar-link]');
const sidebarCollapseButton = document.querySelector('[data-sidebar-collapse]');
const sidebarCollapseIcon = document.querySelector('[data-sidebar-collapse-icon]');
const sidebarExpandIcon = document.querySelector('[data-sidebar-expand-icon]');
const sidebarLabels = document.querySelectorAll('[data-sidebar-label]');
const sidebarNav = document.querySelector('[data-sidebar-nav]');
const sidebarManagementList = document.querySelector('[data-sidebar-management-list]');
const sidebarGroupToggles = document.querySelectorAll('[data-sidebar-group-toggle]');
const sidebarGroupMenus = document.querySelectorAll('[data-sidebar-group-menu]');
const sidebarUserFooter = document.querySelector('[data-sidebar-user-footer]');
const sidebarUserCard = document.querySelector('[data-sidebar-user-card]');
const dashboardContent = document.querySelector('[data-dashboard-content]');

const setSidebarOpen = (isOpen) => {
    if (!sidebar || !sidebarOverlay) {
        return;
    }

    sidebar.classList.toggle('-translate-x-full', !isOpen);
    sidebar.classList.toggle('translate-x-0', isOpen);
    sidebarOverlay.classList.toggle('hidden', !isOpen);
    document.body.classList.toggle('overflow-hidden', isOpen && window.innerWidth < 1024);

    sidebarToggles.forEach((toggle) => {
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Fechar menu principal' : 'Abrir menu principal');
    });
};

sidebarToggles.forEach((toggle) => toggle.addEventListener('click', () => {
    setSidebarOpen(toggle.getAttribute('aria-expanded') !== 'true');
}));
sidebarCloseButtons.forEach((button) => button.addEventListener('click', () => setSidebarOpen(false)));
sidebarOverlay?.addEventListener('click', () => setSidebarOpen(false));
sidebarLinks.forEach((link) => link.addEventListener('click', () => setSidebarOpen(false)));

const setSidebarCollapsed = (isCollapsed, shouldPersist = true) => {
    if (!sidebar || !dashboardContent || !sidebarCollapseButton) {
        return;
    }

    sidebar.classList.toggle('lg:w-20', isCollapsed);
    sidebar.classList.toggle('lg:w-72', !isCollapsed);
    dashboardContent.classList.toggle('lg:pl-20', isCollapsed);
    dashboardContent.classList.toggle('lg:pl-72', !isCollapsed);
    sidebarNav?.classList.toggle('lg:px-2', isCollapsed);

    sidebarLabels.forEach((label) => label.classList.toggle('lg:hidden', isCollapsed));
    sidebarLinks.forEach((link) => link.classList.toggle('lg:justify-center', isCollapsed));
    sidebarGroupToggles.forEach((toggle) => toggle.classList.toggle('lg:justify-center', isCollapsed));
    sidebarGroupMenus.forEach((menu) => menu.classList.toggle('lg:hidden', isCollapsed));
    sidebarUserFooter?.classList.toggle('lg:p-2', isCollapsed);
    sidebarUserCard?.classList.toggle('lg:justify-center', isCollapsed);
    sidebarUserCard?.classList.toggle('lg:p-2', isCollapsed);

    sidebarManagementList?.classList.toggle('lg:mt-4', isCollapsed);
    sidebarManagementList?.classList.toggle('lg:border-t', isCollapsed);
    sidebarManagementList?.classList.toggle('lg:border-stone-100', isCollapsed);
    sidebarManagementList?.classList.toggle('lg:pt-4', isCollapsed);

    sidebarCollapseIcon?.classList.toggle('hidden', isCollapsed);
    sidebarExpandIcon?.classList.toggle('hidden', !isCollapsed);
    sidebarCollapseButton.setAttribute('aria-pressed', String(isCollapsed));
    sidebarCollapseButton.setAttribute('aria-label', isCollapsed ? 'Expandir menu lateral' : 'Recolher menu lateral');
    sidebarCollapseButton.setAttribute('title', isCollapsed ? 'Expandir menu lateral' : 'Recolher menu lateral');

    if (shouldPersist) {
        try {
            window.localStorage.setItem('dashboard-sidebar-collapsed', String(isCollapsed));
        } catch {
            // A preferência é opcional quando o armazenamento do navegador está indisponível.
        }
    }
};

let sidebarStartsCollapsed = false;

try {
    sidebarStartsCollapsed = window.localStorage.getItem('dashboard-sidebar-collapsed') === 'true';
} catch {
    sidebarStartsCollapsed = false;
}

setSidebarCollapsed(sidebarStartsCollapsed, false);
sidebarCollapseButton?.addEventListener('click', () => {
    setSidebarCollapsed(sidebarCollapseButton.getAttribute('aria-pressed') !== 'true');
});

const setSidebarGroupOpen = (toggle, isOpen) => {
    const menuId = toggle.getAttribute('aria-controls');
    const menu = menuId ? document.getElementById(menuId) : null;
    const chevron = toggle.querySelector('[data-sidebar-group-chevron]');

    if (!menu) {
        return;
    }

    menu.classList.toggle('hidden', !isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    chevron?.classList.toggle('rotate-180', isOpen);
};

sidebarGroupToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const sidebarIsCollapsed = window.innerWidth >= 1024
            && sidebarCollapseButton?.getAttribute('aria-pressed') === 'true';

        if (sidebarIsCollapsed) {
            setSidebarCollapsed(false);
            setSidebarGroupOpen(toggle, true);
            return;
        }

        setSidebarGroupOpen(toggle, toggle.getAttribute('aria-expanded') !== 'true');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setSidebarOpen(false);
    }
});

window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
    if (event.matches) {
        setSidebarOpen(false);
    }
});

document.querySelectorAll('[data-conditional-select]').forEach((select) => {
    const targetId = select.dataset.conditionalSelect;
    const target = targetId ? document.getElementById(targetId) : null;
    const field = target?.closest('[data-conditional-field]');

    if (!target || !field) {
        return;
    }

    const updateConditionalField = () => {
        const shouldShow = select.value === '1';

        field.classList.toggle('hidden', !shouldShow);
        field.setAttribute('aria-hidden', String(!shouldShow));
        target.disabled = !shouldShow;
        target.required = shouldShow;

        if (!shouldShow) {
            target.value = '';
            target.removeAttribute('aria-invalid');
        }
    };

    select.addEventListener('change', updateConditionalField);
    updateConditionalField();
});

const customSelects = Array.from(document.querySelectorAll('[data-custom-select]'));

const normalizeCustomSelectSearch = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('pt-BR')
    .replace(/[^a-z0-9]/g, '');

const getCustomSelectOptions = (select) => Array.from(
    select.querySelectorAll('[data-custom-select-option]')
);

const getVisibleCustomSelectOptions = (select) => getCustomSelectOptions(select)
    .filter((option) => !option.classList.contains('hidden'));

const filterCustomSelectOptions = (select) => {
    const searchInput = select.querySelector('[data-custom-select-search]');
    const emptyState = select.querySelector('[data-custom-select-empty]');
    const options = getCustomSelectOptions(select);

    if (!searchInput) {
        return options;
    }

    const searchTerm = normalizeCustomSelectSearch(searchInput.value);
    const visibleOptions = options.filter((option) => {
        const optionText = normalizeCustomSelectSearch(option.textContent);
        const isPlaceholder = (option.dataset.value ?? '') === '';
        const isVisible = searchTerm === '' || (!isPlaceholder && optionText.includes(searchTerm));

        option.classList.toggle('hidden', !isVisible);

        if (isVisible) {
            option.removeAttribute('aria-hidden');
        } else {
            option.setAttribute('aria-hidden', 'true');
        }

        return isVisible;
    });

    if (emptyState) {
        const hasResults = visibleOptions.length > 0;

        emptyState.classList.toggle('hidden', hasResults);
        emptyState.setAttribute('aria-hidden', String(hasResults));
    }

    return visibleOptions;
};

const resetCustomSelectSearch = (select) => {
    const searchInput = select?.querySelector('[data-custom-select-search]');

    if (!searchInput) {
        return;
    }

    searchInput.value = '';
    filterCustomSelectOptions(select);
};

const validateRequiredCustomSelect = (select, showError = false) => {
    const nativeSelect = select.querySelector('[data-custom-select-native]');
    const trigger = select.querySelector('[data-custom-select-trigger]');
    const requiredError = select.parentElement?.querySelector('[data-custom-select-required-error]');

    if (!nativeSelect || nativeSelect.dataset.customSelectRequired !== 'true') {
        return true;
    }

    const isValid = nativeSelect.value !== '';

    if (isValid) {
        trigger?.removeAttribute('aria-invalid');
        requiredError?.classList.add('hidden');
        requiredError?.classList.remove('flex');
    } else if (showError) {
        trigger?.setAttribute('aria-invalid', 'true');
        requiredError?.classList.remove('hidden');
        requiredError?.classList.add('flex');
    }

    return isValid;
};

const closeCustomSelect = (select, returnFocus = false) => {
    const trigger = select?.querySelector('[data-custom-select-trigger]');
    const optionsPanel = select?.querySelector('[data-custom-select-options]');
    const chevron = select?.querySelector('[data-custom-select-chevron]');

    if (!trigger || !optionsPanel || trigger.getAttribute('aria-expanded') !== 'true') {
        return;
    }

    resetCustomSelectSearch(select);
    optionsPanel.classList.add('hidden');
    trigger.setAttribute('aria-expanded', 'false');
    chevron?.classList.remove('rotate-180');
    select.classList.remove('z-40');

    if (returnFocus) {
        trigger.focus();
    }
};

const openCustomSelect = (select, focusDirection = null) => {
    customSelects.forEach((currentSelect) => {
        if (currentSelect !== select) {
            closeCustomSelect(currentSelect);
        }
    });

    const trigger = select.querySelector('[data-custom-select-trigger]');
    const optionsPanel = select.querySelector('[data-custom-select-options]');
    const chevron = select.querySelector('[data-custom-select-chevron]');
    const searchInput = select.querySelector('[data-custom-select-search]');
    const options = filterCustomSelectOptions(select);

    if (!trigger || !optionsPanel) {
        return;
    }

    optionsPanel.classList.remove('hidden');
    trigger.setAttribute('aria-expanded', 'true');
    chevron?.classList.add('rotate-180');
    select.classList.add('z-40');

    if (searchInput) {
        searchInput.focus();
        return;
    }

    if (focusDirection) {
        const selectedIndex = Math.max(0, options.findIndex((option) => option.getAttribute('aria-selected') === 'true'));
        const focusIndex = focusDirection === 'previous'
            ? (selectedIndex - 1 + options.length) % options.length
            : (selectedIndex + 1) % options.length;

        options[focusIndex]?.focus();
    }
};

const selectCustomOption = (select, option) => {
    const nativeSelect = select.querySelector('[data-custom-select-native]');
    const valueLabel = select.querySelector('[data-custom-select-value]');
    const options = getCustomSelectOptions(select);

    if (!nativeSelect || !valueLabel) {
        return;
    }

    nativeSelect.value = option.dataset.value ?? '';
    nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    valueLabel.textContent = option.querySelector('span')?.textContent?.trim() ?? '';

    options.forEach((currentOption) => {
        const isSelected = currentOption === option;
        currentOption.setAttribute('aria-selected', String(isSelected));
        currentOption.querySelector('[data-custom-select-check]')?.classList.toggle('hidden', !isSelected);
        currentOption.classList.toggle('bg-brand-50', isSelected);
        currentOption.classList.toggle('text-brand-800', isSelected);
        currentOption.classList.toggle('font-bold', isSelected);
        currentOption.classList.toggle('text-stone-700', !isSelected);
        currentOption.classList.toggle('font-medium', !isSelected);
    });

    validateRequiredCustomSelect(select, select.dataset.customSelectValidationAttempted === 'true');

    closeCustomSelect(select, true);
};

customSelects.forEach((select) => {
    const nativeSelect = select.querySelector('[data-custom-select-native]');
    const trigger = select.querySelector('[data-custom-select-trigger]');
    const searchInput = select.querySelector('[data-custom-select-search]');
    const options = getCustomSelectOptions(select);

    if (!nativeSelect || !trigger || options.length === 0) {
        return;
    }

    if (nativeSelect.required) {
        nativeSelect.dataset.customSelectRequired = 'true';
        nativeSelect.required = false;
        trigger.setAttribute('aria-required', 'true');
    }

    nativeSelect.classList.add('hidden');
    trigger.classList.remove('hidden');
    trigger.classList.add('flex');

    const selectedOption = options.find((option) => option.getAttribute('aria-selected') === 'true') ?? options[0];
    selectedOption?.classList.remove('text-stone-700', 'font-medium');
    selectedOption?.classList.add('bg-brand-50', 'text-brand-800', 'font-bold');

    trigger.addEventListener('click', () => {
        if (trigger.getAttribute('aria-expanded') === 'true') {
            closeCustomSelect(select);
            return;
        }

        openCustomSelect(select);
    });

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            openCustomSelect(select, event.key === 'ArrowUp' ? 'previous' : 'next');
        }
    });

    searchInput?.addEventListener('input', () => {
        filterCustomSelectOptions(select);
    });

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            const visibleOptions = getVisibleCustomSelectOptions(select);
            const selectedOption = visibleOptions.find(
                (option) => option.getAttribute('aria-selected') === 'true'
            );
            const optionToFocus = event.key === 'ArrowUp'
                ? visibleOptions[visibleOptions.length - 1]
                : selectedOption ?? visibleOptions[0];

            optionToFocus?.focus();
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();

            const visibleOptions = getVisibleCustomSelectOptions(select);
            const selectedOption = visibleOptions.find(
                (option) => option.getAttribute('aria-selected') === 'true'
            );
            const optionToSelect = normalizeCustomSelectSearch(searchInput.value) === ''
                ? selectedOption ?? visibleOptions[0]
                : visibleOptions[0];

            if (optionToSelect) {
                selectCustomOption(select, optionToSelect);
            }

            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeCustomSelect(select, true);
            return;
        }

        if (event.key === 'Tab') {
            closeCustomSelect(select);
        }
    });

    options.forEach((option) => {
        option.addEventListener('click', () => selectCustomOption(select, option));
        option.addEventListener('keydown', (event) => {
            let nextIndex = null;
            const visibleOptions = getVisibleCustomSelectOptions(select);
            const optionIndex = visibleOptions.indexOf(option);

            if (event.key === 'ArrowDown') {
                nextIndex = (optionIndex + 1) % visibleOptions.length;
            } else if (event.key === 'ArrowUp') {
                nextIndex = (optionIndex - 1 + visibleOptions.length) % visibleOptions.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = visibleOptions.length - 1;
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectCustomOption(select, option);
                return;
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeCustomSelect(select, true);
                return;
            } else if (event.key === 'Tab') {
                closeCustomSelect(select);
                return;
            }

            if (nextIndex !== null) {
                event.preventDefault();
                visibleOptions[nextIndex]?.focus();
            }
        });
    });
});

const customSelectForms = new Set(
    customSelects
        .map((select) => select.querySelector('[data-custom-select-native]')?.form)
        .filter(Boolean)
);

customSelectForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
        let firstInvalidSelect = null;

        customSelects.forEach((select) => {
            const nativeSelect = select.querySelector('[data-custom-select-native]');

            if (nativeSelect?.form !== form || nativeSelect.dataset.customSelectRequired !== 'true') {
                return;
            }

            select.dataset.customSelectValidationAttempted = 'true';

            if (!validateRequiredCustomSelect(select, true) && !firstInvalidSelect) {
                firstInvalidSelect = select;
            }
        });

        if (firstInvalidSelect) {
            event.preventDefault();
            firstInvalidSelect.querySelector('[data-custom-select-trigger]')?.focus();
        }
    });
});

document.addEventListener('click', (event) => {
    customSelects.forEach((select) => {
        if (!select.contains(event.target)) {
            closeCustomSelect(select);
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        customSelects.forEach((select) => closeCustomSelect(select));
    }
});
