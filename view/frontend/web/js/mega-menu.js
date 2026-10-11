/**
 * CommerceGears Mega Menu: one script for Luma and Hyvä (no jQuery, RequireJS or Alpine needed).
 * Disclosure navigation pattern: links stay links, every dropdown has a button with aria-expanded.
 */
(function () {
    'use strict';

    var OPEN_DELAY = 90;
    var CLOSE_DELAY = 280;

    function init(nav) {
        if (nav.cgMegaMenu) {
            return;
        }
        nav.cgMegaMenu = true;
        nav.classList.add('commercegears-mm--js');

        var bp = parseInt(nav.getAttribute('data-bp'), 10) || 1024;
        var openOn = nav.getAttribute('data-open-on') || 'hover';
        var mobileMode = nav.getAttribute('data-mobile') || 'drilldown';
        var theme = nav.getAttribute('data-theme') || 'luma';
        var mq = window.matchMedia('(min-width: ' + bp + 'px)');
        var drawer = nav.querySelector('.commercegears-mm__drawer');
        var body = nav.querySelector('.commercegears-mm__body');
        var burger = nav.querySelector('.commercegears-mm__burger');
        var allButton = nav.querySelector('.commercegears-mm__all');
        var tops = Array.prototype.slice.call(nav.querySelectorAll('.commercegears-mm__top'));
        var timers = new WeakMap();
        var inertStack = [];
        var lastPointer = 'mouse';

        function isDesktop() {
            return mq.matches;
        }

        function toggleOf(li) {
            var row = li.querySelector(':scope > .commercegears-mm__row');
            return row ? row.querySelector('.commercegears-mm__toggle') : null;
        }

        function submenuOf(li) {
            return li.querySelector(':scope > .commercegears-mm__panel, :scope > .commercegears-mm__sub');
        }

        function firstFocusable(el) {
            if (!el) {
                return null;
            }
            var found = Array.prototype.filter.call(el.querySelectorAll('a[href], button:not([disabled])'), function (item) {
                return item.getClientRects().length > 0;
            });
            return found[0] || null;
        }

        function setExpanded(li, open) {
            li.classList.toggle('is-open', open);
            var toggle = toggleOf(li);
            if (toggle) {
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        /* ---- lazy panel content ---- */
        function loadLazy(li) {
            var box = li.querySelector(':scope > .commercegears-mm__panel .commercegears-mm__content[data-commercegears-mm-lazy]');
            if (!box || box.cgLoading) {
                return;
            }
            box.cgLoading = true;
            box.setAttribute('aria-busy', 'true');
            fetch(box.getAttribute('data-commercegears-mm-lazy'), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error(String(response.status));
                    }
                    return response.text();
                })
                .then(function (html) {
                    box.innerHTML = html;
                    box.removeAttribute('data-commercegears-mm-lazy');
                    if (window.jQuery) {
                        window.jQuery(box).trigger('contentUpdated'); // Luma: initialise widgets in the content
                    }
                })
                .catch(function () {
                    box.innerHTML = '';
                    box.cgLoading = false;
                })
                .then(function () {
                    box.removeAttribute('aria-busy');
                });
        }

        /* ---- drill-down helpers: hide covered levels from keyboard and screen readers ---- */
        function coverLevel(li) {
            var level = li.parentElement.closest('.commercegears-mm__panel, .commercegears-mm__sub, .commercegears-mm__body');
            var list = [];
            var node = li;
            var row = li.querySelector(':scope > .commercegears-mm__row');
            if (row) {
                list.push(row);
            }
            while (node && node !== level) {
                Array.prototype.forEach.call(node.parentElement.children, function (sibling) {
                    if (sibling !== node && !sibling.inert) {
                        list.push(sibling);
                    }
                });
                node = node.parentElement;
            }
            list.forEach(function (el) {
                el.inert = true;
            });
            inertStack.push({li: li, list: list});
            var sub = submenuOf(li);
            if (sub) {
                sub.scrollTop = 0;
                body.style.minHeight = sub.scrollHeight + 'px';
            }
        }

        function uncoverLevel(li) {
            for (var i = inertStack.length - 1; i >= 0; i--) {
                if (inertStack[i].li === li) {
                    inertStack[i].list.forEach(function (el) {
                        el.inert = false;
                    });
                    inertStack.splice(i, 1);
                }
            }
            var parentLevel = inertStack.length ? submenuOf(inertStack[inertStack.length - 1].li) : null;
            body.style.minHeight = parentLevel ? parentLevel.scrollHeight + 'px' : '';
        }

        function resetLevels() {
            while (inertStack.length) {
                inertStack.pop().list.forEach(function (el) {
                    el.inert = false;
                });
            }
            body.style.minHeight = '';
        }

        /* ---- open and close ---- */
        function open(li, focusFirst) {
            if (isDesktop() && li.classList.contains('commercegears-mm__top')) {
                tops.forEach(function (other) {
                    if (other !== li) {
                        setExpanded(other, false);
                    }
                });
            }
            setExpanded(li, true);
            if (li.classList.contains('commercegears-mm__top')) {
                loadLazy(li);
            }
            if (!isDesktop() && mobileMode === 'drilldown') {
                coverLevel(li);
                var back = submenuOf(li) && submenuOf(li).querySelector(':scope > .commercegears-mm__mhead .commercegears-mm__back');
                if (back) {
                    window.setTimeout(function () {
                        back.focus();
                    }, 30);
                }
                return;
            }
            if (focusFirst) {
                var target = firstFocusable(submenuOf(li));
                if (target) {
                    target.focus();
                }
            }
        }

        function close(li, focusToggle) {
            if (!li.classList.contains('is-open')) {
                return;
            }
            Array.prototype.forEach.call(li.querySelectorAll('.is-open'), function (child) {
                setExpanded(child, false);
            });
            setExpanded(li, false);
            if (!isDesktop() && mobileMode === 'drilldown') {
                uncoverLevel(li);
            }
            if (focusToggle) {
                var toggle = toggleOf(li);
                if (toggle) {
                    toggle.focus();
                }
            }
        }

        function closeAll() {
            tops.forEach(function (li) {
                close(li, false);
            });
            resetLevels();
        }

        function cancelTimer(li) {
            if (timers.has(li)) {
                window.clearTimeout(timers.get(li));
                timers.delete(li);
            }
        }

        /* ---- desktop hover ---- */
        tops.forEach(function (li) {
            if (!li.classList.contains('commercegears-mm__has-panel')) {
                return;
            }
            li.addEventListener('pointerenter', function (event) {
                lastPointer = event.pointerType || 'mouse';
                if (!isDesktop() || event.pointerType !== 'mouse') {
                    return;
                }
                cancelTimer(li);
                var box = li.querySelector('.commercegears-mm__content[data-commercegears-mm-lazy]');
                if (box) {
                    loadLazy(li); // start loading while the pointer rests
                }
                if (openOn !== 'hover') {
                    return;
                }
                var anyOpen = tops.some(function (other) {
                    return other !== li && other.classList.contains('is-open');
                });
                timers.set(li, window.setTimeout(function () {
                    open(li, false);
                }, anyOpen ? 0 : OPEN_DELAY));
            });
            li.addEventListener('pointerleave', function (event) {
                if (!isDesktop() || event.pointerType !== 'mouse' || openOn !== 'hover') {
                    return;
                }
                cancelTimer(li);
                timers.set(li, window.setTimeout(function () {
                    close(li, false);
                }, CLOSE_DELAY));
            });
            li.addEventListener('focusout', function (event) {
                if (isDesktop() && !li.contains(event.relatedTarget)) {
                    close(li, false);
                }
            });
        });

        nav.addEventListener('pointerdown', function (event) {
            lastPointer = event.pointerType || 'mouse';
        });

        /* ---- clicks ---- */
        nav.addEventListener('click', function (event) {
            var toggle = event.target.closest('.commercegears-mm__toggle');
            if (toggle && nav.contains(toggle)) {
                var li = toggle.closest('li');
                event.preventDefault();
                if (li.classList.contains('is-open')) {
                    close(li, false);
                } else {
                    open(li, false);
                }
                return;
            }
            var back = event.target.closest('.commercegears-mm__back');
            if (back) {
                var owner = back.closest('li');
                if (owner) {
                    close(owner, true);
                }
                return;
            }
            var link = event.target.closest('.commercegears-mm__top > .commercegears-mm__row > a.commercegears-mm__toplink');
            if (link && isDesktop()) {
                var top = link.closest('.commercegears-mm__top');
                // Click mode, or a touch screen with the desktop menu: the first tap opens the dropdown.
                if (top.classList.contains('commercegears-mm__has-panel') && !top.classList.contains('is-open')
                    && (openOn === 'click' || lastPointer === 'touch' || lastPointer === 'pen')) {
                    event.preventDefault();
                    open(top, false);
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (!isDesktop() || nav.contains(event.target)) {
                return;
            }
            closeAll();
            setAllOpen(false);
        });

        /* ---- keyboard ---- */
        function topFocusables() {
            return tops.filter(function (li) {
                return li.offsetParent !== null;
            }).map(function (li) {
                return firstFocusable(li.querySelector(':scope > .commercegears-mm__row'));
            }).filter(Boolean);
        }

        nav.addEventListener('keydown', function (event) {
            var key = event.key;
            if (key === 'Escape' || key === 'Esc') {
                var openLi = event.target.closest('li.is-open') || nav.querySelector('.commercegears-mm__top.is-open');
                if (openLi) {
                    event.preventDefault();
                    close(openLi, true);
                    return;
                }
                if (allButton && nav.classList.contains('commercegears-mm--all-open')) {
                    setAllOpen(false);
                    allButton.focus();
                    return;
                }
                if (nav.classList.contains('commercegears-mm--drawer-open')) {
                    event.preventDefault();
                    setDrawer(false);
                }
                return;
            }
            if (!isDesktop()) {
                if (key === 'Tab' && nav.classList.contains('commercegears-mm--drawer-open')) {
                    trapFocus(event);
                }
                return;
            }
            var row = event.target.closest('.commercegears-mm__top > .commercegears-mm__row');
            if (!row) {
                return;
            }
            var li = row.parentElement;
            var vertical = nav.classList.contains('commercegears-mm--desk-hamburger');
            var nextKey = vertical ? 'ArrowDown' : 'ArrowRight';
            var prevKey = vertical ? 'ArrowUp' : 'ArrowLeft';
            var openKey = vertical ? 'ArrowRight' : 'ArrowDown';
            if (key === nextKey || key === prevKey || key === 'Home' || key === 'End') {
                var list = topFocusables();
                var index = list.indexOf(firstFocusable(row));
                var target = key === 'Home' ? 0 : key === 'End' ? list.length - 1
                    : (index + (key === nextKey ? 1 : -1) + list.length) % list.length;
                if (list[target]) {
                    event.preventDefault();
                    list[target].focus();
                }
            } else if (key === openKey && li.classList.contains('commercegears-mm__has-panel')) {
                event.preventDefault();
                open(li, true);
            }
        });

        /* ---- mobile drawer (Hyvä) and Luma's menu button ---- */
        function trapFocus(event) {
            var items = Array.prototype.filter.call(
                drawer.querySelectorAll('a[href], button:not([disabled])'),
                function (el) {
                    return el.offsetParent !== null && !el.closest('[inert]');
                }
            );
            if (!items.length) {
                return;
            }
            var first = items[0];
            var last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function setDrawer(openIt) {
            if (!burger) {
                return;
            }
            nav.classList.toggle('commercegears-mm--drawer-open', openIt);
            burger.setAttribute('aria-expanded', openIt ? 'true' : 'false');
            document.documentElement.style.overflow = openIt ? 'hidden' : '';
            if (openIt) {
                var closeButton = drawer.querySelector('.commercegears-mm__close');
                window.setTimeout(function () {
                    (closeButton || firstFocusable(drawer)).focus();
                }, 30);
            } else {
                closeAll();
                burger.focus();
            }
        }

        function setAllOpen(openIt) {
            if (!allButton) {
                return;
            }
            if (openIt) {
                // room to the right of the list for the dropdowns
                var right = allButton.getBoundingClientRect().left + 280;
                nav.style.setProperty('--commercegears-mm-hb-space', Math.max(320, document.documentElement.clientWidth - right - 16) + 'px');
            }
            nav.classList.toggle('commercegears-mm--all-open', openIt);
            allButton.setAttribute('aria-expanded', openIt ? 'true' : 'false');
            if (!openIt) {
                closeAll();
            }
        }

        if (burger) {
            burger.addEventListener('click', function () {
                setDrawer(!nav.classList.contains('commercegears-mm--drawer-open'));
            });
            var closeButton = nav.querySelector('.commercegears-mm__close');
            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    setDrawer(false);
                });
            }
            var backdrop = nav.querySelector('.commercegears-mm__backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    setDrawer(false);
                });
            }
        }
        if (allButton) {
            allButton.addEventListener('click', function (event) {
                event.stopPropagation();
                setAllOpen(!nav.classList.contains('commercegears-mm--all-open'));
            });
        }
        if (theme === 'luma') {
            // The Luma menu widget is replaced, so this script opens Luma's off-canvas navigation.
            Array.prototype.forEach.call(document.querySelectorAll('[data-action="toggle-nav"]'), function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    var html = document.documentElement;
                    if (html.classList.contains('nav-open')) {
                        html.classList.remove('nav-open');
                        window.setTimeout(function () {
                            html.classList.remove('nav-before-open');
                        }, 300);
                        closeAll();
                    } else {
                        html.classList.add('nav-before-open');
                        window.setTimeout(function () {
                            html.classList.add('nav-open');
                        }, 42);
                    }
                });
            });
        }

        /* ---- layout switch ---- */
        function applyMode() {
            closeAll();
            setAllOpen(false);
            if (burger) {
                if (isDesktop()) {
                    nav.classList.remove('commercegears-mm--drawer-open');
                    burger.setAttribute('aria-expanded', 'false');
                    document.documentElement.style.overflow = '';
                    drawer.removeAttribute('role');
                    drawer.removeAttribute('aria-modal');
                } else {
                    drawer.setAttribute('role', 'dialog');
                    drawer.setAttribute('aria-modal', 'true');
                }
            }
        }
        if (mq.addEventListener) {
            mq.addEventListener('change', applyMode);
        } else if (mq.addListener) {
            mq.addListener(applyMode);
        }
        applyMode();

        /* ---- current page (done here, so the cached HTML is the same on every page) ---- */
        var here = normalise(window.location.href);
        var best = null;
        Array.prototype.forEach.call(nav.querySelectorAll('a[href]'), function (a) {
            if (normalise(a.href) === here && !best) {
                best = a;
            }
        });
        if (best) {
            best.setAttribute('aria-current', 'page');
            var top = best.closest('.commercegears-mm__top');
            if (top) {
                top.classList.add('commercegears-mm__top--current');
            }
        }
    }

    function normalise(url) {
        try {
            var parsed = new URL(url, window.location.href);
            return parsed.origin + parsed.pathname.replace(/\/+$/, '');
        } catch (e) {
            return url;
        }
    }

    function start() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-commercegears-mm]'), init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
