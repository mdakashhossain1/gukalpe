{{--
    Shared English/Hindi translation engine - included from both
    layouts/app.blade.php and layouts/simple.blade.php. Requires the host
    layout's <body> tag to carry data-lang-base="{{ asset('lang') }}".

    Walks visible text nodes (and a fixed set of translatable attributes)
    under <body>, matching each trimmed value against the literal-English-key
    dictionaries in public/lang/{en,hi}.json. Only exact matches translate -
    dynamic content (names, amounts, DB-sourced titles) is left untouched.
--}}
<script>
    (function () {
        var STORAGE_KEY = 'gullak_lang';
        // Absolute-rooted '/lang/xx.json' breaks whenever the app is
        // reached through a mount prefix (e.g. XAMPP's
        // /gullakpe/gullakpe-laravel/) instead of the domain root - the
        // request would land on the wrong path and 404. asset('lang')
        // bakes in whatever base path this response actually resolved
        // under, the same way every other asset() call in the app does.
        var LANG_BASE = document.body.getAttribute('data-lang-base') || '/lang';
        var TRANSLATABLE_ATTRS = ['aria-label', 'placeholder', 'title', 'alt'];
        var dictionaries = {};
        var originalText = new WeakMap();

        function loadDictionary(lang) {
            if (dictionaries[lang]) return Promise.resolve(dictionaries[lang]);
            return fetch(LANG_BASE + '/' + lang + '.json')
                .then(function (res) { return res.ok ? res.json() : {}; })
                .then(function (json) { dictionaries[lang] = json; return json; })
                .catch(function () { return {}; });
        }

        function collectTextNodes(root) {
            var nodes = [];
            var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode: function (node) {
                    var parent = node.parentElement;
                    if (!parent) return NodeFilter.FILTER_REJECT;
                    if (['SCRIPT', 'STYLE', 'TEXTAREA', 'INPUT'].indexOf(parent.tagName) !== -1) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    if (!node.nodeValue || !node.nodeValue.trim()) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            });
            var current;
            while ((current = walker.nextNode())) nodes.push(current);
            return nodes;
        }

        // Icon-only buttons (history, translate, notifications, reset)
        // carry their user-facing label in aria-label/placeholder/title
        // instead of a text node - those need translating too, or an
        // entire class of "text" silently never gets linked to the
        // dictionaries. Original values are stashed in a data-orig-*
        // attribute on first run so toggling back to English is exact.
        function applyAttributeTranslations(lang, dict) {
            TRANSLATABLE_ATTRS.forEach(function (attr) {
                var origAttr = 'data-orig-' + attr;
                document.querySelectorAll('[' + attr + ']').forEach(function (el) {
                    if (!el.hasAttribute(origAttr)) {
                        el.setAttribute(origAttr, el.getAttribute(attr));
                    }
                    var original = el.getAttribute(origAttr);
                    if (lang === 'en') {
                        el.setAttribute(attr, original);
                        return;
                    }
                    var trimmed = original.trim();
                    if (dict && Object.prototype.hasOwnProperty.call(dict, trimmed)) {
                        el.setAttribute(attr, original.replace(trimmed, dict[trimmed]));
                    }
                });
            });
        }

        function applyLanguage(lang) {
            var nodes = collectTextNodes(document.body);
            nodes.forEach(function (node) {
                if (!originalText.has(node)) originalText.set(node, node.nodeValue);
            });

            if (lang === 'en') {
                nodes.forEach(function (node) { node.nodeValue = originalText.get(node); });
                applyAttributeTranslations('en', null);
                return Promise.resolve();
            }

            return loadDictionary(lang).then(function (dict) {
                nodes.forEach(function (node) {
                    var original = originalText.get(node);
                    var trimmed = original.trim();
                    if (Object.prototype.hasOwnProperty.call(dict, trimmed)) {
                        node.nodeValue = original.replace(trimmed, dict[trimmed]);
                    }
                });
                applyAttributeTranslations(lang, dict);
            });
        }

        function updateIndicators(lang) {
            document.querySelectorAll('[data-current-lang]').forEach(function (el) {
                el.textContent = lang === 'hi' ? 'हि' : 'EN';
            });
        }

        function setLanguage(lang) {
            document.body.style.transition = 'opacity 0.2s ease';
            document.body.style.opacity = '0';
            setTimeout(function () {
                applyLanguage(lang).then(function () {
                    localStorage.setItem(STORAGE_KEY, lang);
                    document.documentElement.setAttribute('lang', lang === 'hi' ? 'hi' : 'en');
                    updateIndicators(lang);
                    document.body.style.opacity = '1';
                });
            }, 150);
        }

        window.toggleLanguage = function () {
            var current = localStorage.getItem(STORAGE_KEY) || 'en';
            setLanguage(current === 'hi' ? 'en' : 'hi');
        };

        document.addEventListener('DOMContentLoaded', function () {
            var saved = localStorage.getItem(STORAGE_KEY) || 'en';
            document.documentElement.setAttribute('lang', saved === 'hi' ? 'hi' : 'en');
            updateIndicators(saved);
            if (saved !== 'en') applyLanguage(saved);
        });
    })();
</script>
