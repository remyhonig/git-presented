/**
 * Git Presentations - Main JavaScript
 * Theme switcher, mode switcher, fullscreen, click-to-copy, and HTMX handling
 */

// =====================================================
// THEME SWITCHER
// =====================================================
(function() {
    // Get default theme from HTML data attribute (set by server from .env)
    const DEFAULT_THEME = document.documentElement.dataset.defaultTheme || 'light';

    // Available themes with display names and icons
    const THEMES = [
        { id: 'light', name: 'Light', icon: '☀️', hljs: 'light' },
        { id: 'dark', name: 'Dark', icon: '🌙', hljs: 'dark' },
        { id: 'presentation', name: 'Presentation', icon: '🎭', hljs: 'dark' },
        { id: 'schiphol', name: 'Schiphol', icon: '✈️', hljs: 'dark' },
        { id: 'laravel', name: 'Laravel', icon: '🔺', hljs: 'light' },
        { id: 'symfony', name: 'Symfony', icon: '🎼', hljs: 'light' },
        { id: 'microsoft', name: 'Microsoft', icon: '🪟', hljs: 'light' },
        { id: 'catppuccin-latte', name: 'Latte', icon: '🐱', hljs: 'light' },
        { id: 'catppuccin-frappe', name: 'Frappé', icon: '🐱', hljs: 'dark' },
        { id: 'catppuccin-macchiato', name: 'Macchiato', icon: '🐱', hljs: 'dark' },
        { id: 'catppuccin-mocha', name: 'Mocha', icon: '🐱', hljs: 'dark' },
    ];

    let currentThemeIndex = 0;
    let toastTimeout = null;

    // Initialize theme index from saved theme
    function initTheme() {
        const savedTheme = localStorage.getItem('presentation-theme') || DEFAULT_THEME;
        currentThemeIndex = THEMES.findIndex(t => t.id === savedTheme);
        if (currentThemeIndex === -1) currentThemeIndex = 0;
        applyTheme(THEMES[currentThemeIndex], false);
    }

    // Apply theme
    function applyTheme(theme, showToast = true) {
        document.documentElement.setAttribute('data-theme', theme.id);
        localStorage.setItem('presentation-theme', theme.id);

        // Switch highlight.js stylesheet
        const lightSheet = document.getElementById('hljs-light');
        const darkSheet = document.getElementById('hljs-dark');
        if (lightSheet && darkSheet) {
            if (theme.hljs === 'dark') {
                lightSheet.disabled = true;
                darkSheet.disabled = false;
            } else {
                lightSheet.disabled = false;
                darkSheet.disabled = true;
            }
        }

        if (showToast) {
            showThemeToast(theme);
        }

        // Update settings indicator
        updateThemeIndicator(theme);
    }

    // Update theme indicator (nav buttons)
    function updateThemeIndicator(theme) {
        const iconEl = document.getElementById('nav-theme-icon');
        const labelEl = document.getElementById('nav-theme-label');
        if (iconEl) iconEl.textContent = theme.icon;
        if (labelEl) labelEl.textContent = theme.name;
    }

    // Show toast notification
    function showThemeToast(theme) {
        const toast = document.getElementById('theme-toast');
        const iconEl = document.getElementById('theme-toast-icon');
        const textEl = document.getElementById('theme-toast-text');

        if (toast && iconEl && textEl) {
            iconEl.textContent = theme.icon;
            textEl.textContent = theme.name;
            toast.classList.add('show');

            // Clear existing timeout
            if (toastTimeout) {
                clearTimeout(toastTimeout);
            }

            // Hide after 2 seconds
            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 2000);
        }
    }

    // Cycle to next theme
    function cycleTheme() {
        currentThemeIndex = (currentThemeIndex + 1) % THEMES.length;
        applyTheme(THEMES[currentThemeIndex]);
    }

    // Get current theme
    function getCurrentTheme() {
        return THEMES[currentThemeIndex];
    }

    // Set specific theme by ID
    function setTheme(themeId) {
        const index = THEMES.findIndex(t => t.id === themeId);
        if (index !== -1) {
            currentThemeIndex = index;
            applyTheme(THEMES[currentThemeIndex]);
        }
    }

    // Listen for Ctrl+K to cycle themes
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            cycleTheme();
        }
    });

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

    // Add click handler for theme nav button
    function initThemeButton() {
        const themeBtn = document.getElementById('nav-theme-btn');
        if (themeBtn && !themeBtn.dataset.init) {
            themeBtn.dataset.init = 'true';
            themeBtn.addEventListener('click', cycleTheme);
        }
        updateThemeIndicator(THEMES[currentThemeIndex]);
    }

    document.addEventListener('DOMContentLoaded', initThemeButton);
    document.addEventListener('htmx:afterSwap', initThemeButton);

    // Expose API globally
    window.ThemeSwitcher = {
        cycle: cycleTheme,
        set: setTheme,
        current: getCurrentTheme,
        themes: THEMES
    };
})();

// =====================================================
// MODE SWITCHER (Browser/Beamer)
// =====================================================
(function() {
    // Get default mode from HTML data attribute (set by server from .env)
    const DEFAULT_MODE = document.documentElement.dataset.defaultMode || 'browser';

    const MODES = [
        { id: 'browser', name: 'Browser', icon: '💻' },
        { id: 'beamer', name: 'Beamer', icon: '📽️' },
    ];

    let currentModeIndex = 0;
    let modeToastTimeout = null;

    // Initialize mode from saved preference
    function initMode() {
        const savedMode = localStorage.getItem('presentation-mode') || DEFAULT_MODE;
        currentModeIndex = MODES.findIndex(m => m.id === savedMode);
        if (currentModeIndex === -1) currentModeIndex = 0;
        applyMode(MODES[currentModeIndex], false);
    }

    // Apply mode
    function applyMode(mode, showToast = true) {
        document.documentElement.setAttribute('data-mode', mode.id);
        localStorage.setItem('presentation-mode', mode.id);

        if (showToast) {
            showModeToast(mode);
        }

        // Update settings indicator
        updateModeIndicator(mode);
    }

    // Update mode indicator (nav buttons)
    function updateModeIndicator(mode) {
        const iconEl = document.getElementById('nav-mode-icon');
        const labelEl = document.getElementById('nav-mode-label');
        if (iconEl) iconEl.textContent = mode.icon;
        if (labelEl) labelEl.textContent = mode.name;
    }

    // Show toast notification
    function showModeToast(mode) {
        const toast = document.getElementById('mode-toast');
        const iconEl = document.getElementById('mode-toast-icon');
        const textEl = document.getElementById('mode-toast-text');

        if (toast && iconEl && textEl) {
            iconEl.textContent = mode.icon;
            textEl.textContent = mode.name;
            toast.classList.add('show');

            if (modeToastTimeout) {
                clearTimeout(modeToastTimeout);
            }

            modeToastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 2000);
        }
    }

    // Toggle between modes
    function toggleMode() {
        currentModeIndex = (currentModeIndex + 1) % MODES.length;
        applyMode(MODES[currentModeIndex]);
    }

    // Set specific mode
    function setMode(modeId) {
        const index = MODES.findIndex(m => m.id === modeId);
        if (index !== -1) {
            currentModeIndex = index;
            applyMode(MODES[currentModeIndex]);
        }
    }

    // Listen for Ctrl+L to toggle mode
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            toggleMode();
        }
    });

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMode);
    } else {
        initMode();
    }

    // Add click handler for mode nav button
    function initModeButton() {
        const modeBtn = document.getElementById('nav-mode-btn');
        if (modeBtn && !modeBtn.dataset.init) {
            modeBtn.dataset.init = 'true';
            modeBtn.addEventListener('click', toggleMode);
        }
        updateModeIndicator(MODES[currentModeIndex]);
    }

    document.addEventListener('DOMContentLoaded', initModeButton);
    document.addEventListener('htmx:afterSwap', initModeButton);

    // Expose API globally
    window.ModeSwitcher = {
        toggle: toggleMode,
        set: setMode,
        current: () => MODES[currentModeIndex],
        modes: MODES
    };
})();

// =====================================================
// FULLSCREEN TOGGLE
// =====================================================
(function() {
    function isFullscreen() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    }

    function toggleFullscreen() {
        if (isFullscreen()) {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        } else {
            const elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        }
    }

    function updateFullscreenIcon() {
        const iconEl = document.getElementById('nav-fullscreen-icon');
        const labelEl = document.getElementById('nav-fullscreen-label');
        if (iconEl) {
            iconEl.textContent = isFullscreen() ? '⛶' : '⛶';
        }
        if (labelEl) {
            labelEl.textContent = isFullscreen() ? 'Exit' : 'Fullscreen';
        }
    }

    // Listen for F key to toggle fullscreen
    document.addEventListener('keydown', function(e) {
        if (e.key === 'f' || e.key === 'F') {
            // Don't trigger if typing in an input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            e.preventDefault();
            toggleFullscreen();
        }
    });

    // Update icon when fullscreen state changes
    document.addEventListener('fullscreenchange', updateFullscreenIcon);
    document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
    document.addEventListener('mozfullscreenchange', updateFullscreenIcon);
    document.addEventListener('MSFullscreenChange', updateFullscreenIcon);

    // Initialize
    function initFullscreen() {
        updateFullscreenIcon();
        const fullscreenBtn = document.getElementById('nav-fullscreen-btn');
        if (fullscreenBtn && !fullscreenBtn.dataset.init) {
            fullscreenBtn.dataset.init = 'true';
            fullscreenBtn.addEventListener('click', toggleFullscreen);
        }
    }

    document.addEventListener('DOMContentLoaded', initFullscreen);
    document.addEventListener('htmx:afterSwap', initFullscreen);

    // Expose API globally
    window.FullscreenToggle = {
        toggle: toggleFullscreen,
        isFullscreen: isFullscreen
    };
})();

// =====================================================
// CLICK-TO-COPY
// =====================================================
(function() {
    // Copy text to clipboard and show checkmark
    async function copyToClipboard(element, text) {
        try {
            await navigator.clipboard.writeText(text);
            showCopied(element);
        } catch (err) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showCopied(element);
            } catch (e) {
                // Silent fail
            }
            document.body.removeChild(textarea);
        }
    }

    // Trigger spring animation
    function showCopied(element) {
        // Remove and re-add class to retrigger animation
        element.classList.remove('copied');
        void element.offsetWidth; // Force reflow
        element.classList.add('copied');
        setTimeout(() => element.classList.remove('copied'), 300);
    }

    // Initialize click-to-copy on elements
    function initClickToCopy() {
        // Select inline code (not inside pre), pre blocks, and emphasized text
        const selectors = [
            '.prose code:not(pre code)',  // Inline code
            '.prose pre',                  // Code blocks
            '.prose-title code',           // Code in titles
        ];

        document.querySelectorAll(selectors.join(', ')).forEach(el => {
            // Skip if already initialized
            if (el.dataset.copyInit) return;
            el.dataset.copyInit = 'true';

            // Add copyable styling
            el.classList.add('copyable');

            el.addEventListener('click', function(e) {
                e.stopPropagation();
                const text = this.textContent.trim();
                copyToClipboard(this, text);
            });
        });
    }

    // Run on DOM ready and after HTMX swaps
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClickToCopy);
    } else {
        initClickToCopy();
    }

    document.addEventListener('htmx:afterSwap', initClickToCopy);
})();

// =====================================================
// HTMX NAVIGATION HANDLING
// =====================================================
(function() {
    // Scroll to top before HTMX swap to prevent glitch
    document.addEventListener('htmx:beforeSwap', function() {
        window.scrollTo(0, 0);
    });

    // Reinitialize page-specific scripts after HTMX swap
    document.addEventListener('htmx:afterSwap', function() {
        // Ensure we're at top after swap
        window.scrollTo(0, 0);

        // Give DOM time to settle, then call page init if it exists
        setTimeout(function() {
            if (typeof initPresentationStep === 'function') {
                initPresentationStep();
            }
            if (typeof initStepPage === 'function') {
                initStepPage();
            }
        }, 10);
    });
})();
