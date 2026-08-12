(function($){
    $(function(){
        var DEFAULTS = {
            desktop_visible: 3,
            tablet_visible: 2,
            mobile_visible: 1,
            tablet_breakpoint: 1024,
            mobile_breakpoint: 768,
            autoplay: false,
            autoplay_interval: 5000,
            loop: true
        };

        var settingsFromWP = window.SventesTeaserSettings || {};
        for (var k in DEFAULTS) {
            if (typeof settingsFromWP[k] === 'undefined') settingsFromWP[k] = DEFAULTS[k];
        }

        $('.js-sventes-teaser').each(function(){
            var $carousel = $(this);
            var $inner = $carousel.find('.sventes-teaser-inner');
            var $prev = $carousel.find('.sventes-prev');
            var $next = $carousel.find('.sventes-next');
            var $dots = $carousel.find('.sventes-dot');
            var $dotsPrev = $carousel.find('.sventes-dots-prev');
            var $dotsNext = $carousel.find('.sventes-dots-next');
            var $live = $carousel.find('.sventes-live');

            var autoplayTimer = null;
            var local_settings = $.extend({}, settingsFromWP);
            var originalTotal = $inner.find('.sventes-teaser-item').length;
            var cloneCount = 0;
            var currentPos = 0;
            var currentRealIndex = 0;
            var loopPrepared = false;
            var transitionResetting = false;

            try {
                var ds = $carousel.data('settings');
                if (typeof ds === 'object') {
                    local_settings = $.extend(local_settings, ds);
                } else {
                    var dsRaw = $carousel.attr('data-settings');
                    if (dsRaw) {
                        var parsedSettings = JSON.parse(dsRaw);
                        local_settings = $.extend(local_settings, parsedSettings);
                    }
                }
            } catch (e) {
                console.warn('Error parsing carousel settings:', e);
                // ignore parse errors but continue with existing settings
            }

            local_settings.tablet_breakpoint = parseInt(local_settings.tablet_breakpoint, 10) || 1024;
            local_settings.mobile_breakpoint = parseInt(local_settings.mobile_breakpoint, 10) || 768;
            local_settings.desktop_visible = parseInt(local_settings.desktop_visible, 10) || 3;
            local_settings.tablet_visible = parseInt(local_settings.tablet_visible, 10) || 2;
            local_settings.mobile_visible = parseInt(local_settings.mobile_visible, 10) || 1;
            // Ensure loop and autoplay are proper booleans
            local_settings.loop = local_settings.loop === true || local_settings.loop === 'true' || local_settings.loop === 1 || local_settings.loop === '1';
            local_settings.autoplay = local_settings.autoplay === true || local_settings.autoplay === 'true' || local_settings.autoplay === 1 || local_settings.autoplay === '1';

            var $originalItems = $inner.find('.sventes-teaser-item');
            $originalItems.each(function(i){
                $(this).attr('id', 'sventes-slide-' + i).attr('data-real-index', i);
            });

            function deviceType() {
                var w = window.innerWidth;
                if (w <= local_settings.mobile_breakpoint) return 'mobile';
                if (w <= local_settings.tablet_breakpoint) return 'tablet';
                return 'desktop';
            }

            function visibleCount() {
                var dt = deviceType();
                var count = dt === 'mobile' ? local_settings.mobile_visible : (dt === 'tablet' ? local_settings.tablet_visible : local_settings.desktop_visible);
                return Math.max(1, Math.min(originalTotal, count));
            }

            function updateCssVar() {
                $carousel[0].style.setProperty('--visible-count', visibleCount());
            }

            function getItems() {
                return $inner.find('.sventes-teaser-item');
            }

            function realIndexFromPos(pos) {
                if (!loopPrepared) {
                    return Math.max(0, Math.min(Math.max(0, originalTotal - 1), pos));
                }
                return ((pos - cloneCount) % originalTotal + originalTotal) % originalTotal;
            }

            function getOffsetForPos(pos) {
                var $items = getItems();
                if (!$items.length) return 0;
                pos = Math.max(0, Math.min($items.length - 1, pos));
                return $items.get(pos).offsetLeft;
            }

            function updateAriaVisibility() {
                var vc = visibleCount();
                var firstVisible = realIndexFromPos(currentPos);
                getItems().each(function(i){
                    var $it = $(this);
                    if ($it.hasClass('is-clone')) {
                        $it.attr('aria-hidden', 'true');
                        return;
                    }
                    if (loopPrepared) {
                        var real = parseInt($it.attr('data-real-index'), 10);
                        var inView = false;
                        for (var offset = 0; offset < vc; offset++) {
                            if (((firstVisible + offset) % originalTotal) === real) {
                                inView = true;
                                break;
                            }
                        }
                        $it.attr('aria-hidden', inView ? 'false' : 'true');
                    } else {
                        $it.attr('aria-hidden', (i >= currentPos && i <= currentPos + vc - 1) ? 'false' : 'true');
                    }
                });
            }

            function applyTranslate(skipTransition) {
                var x = -getOffsetForPos(currentPos);
                if (skipTransition) {
                    transitionResetting = true;
                    $inner.css('transition', 'none');
                    $inner.css('transform', 'translateX(' + x + 'px)');
                    $inner[0].offsetHeight;
                    $inner.css('transition', '');
                    setTimeout(function(){ transitionResetting = false; }, 0);
                } else {
                    $inner.css('transform', 'translateX(' + x + 'px)');
                }
            }

            function updateDots() {
                currentRealIndex = realIndexFromPos(currentPos);
                $dots.removeClass('is-active').attr('aria-selected', 'false');
                $dots.eq(currentRealIndex).addClass('is-active').attr('aria-selected', 'true');
            }

            function moveTo(targetPos, announce, skipTransition) {
                var vc = visibleCount();
                if (loopPrepared) {
                    var maxPos = originalTotal + (cloneCount * 2) - 1;
                    // Allow currentPos to go negative or beyond maxPos temporarily
                    // so transitionend handler can properly wrap around
                    currentPos = targetPos;
                } else {
                    currentPos = Math.max(0, Math.min(Math.max(0, originalTotal - vc), targetPos));
                }
                applyTranslate(!!skipTransition);
                updateDots();
                updateAriaVisibility();

                if (announce && $live.length) {
                    $live.text('Slide ' + (currentRealIndex + 1) + ' of ' + originalTotal);
                }
            }

            function syncLoopStructure() {
                var vc = visibleCount();
                var preserveReal = currentRealIndex;
                $inner.find('.sventes-teaser-item.is-clone').remove();
                loopPrepared = !!local_settings.loop && originalTotal > vc && originalTotal > 1;
                cloneCount = loopPrepared ? Math.min(vc, originalTotal) : 0;

                if (!loopPrepared) {
                    currentPos = Math.max(0, Math.min(preserveReal, Math.max(0, originalTotal - vc)));
                    return;
                }

                var prependNodes = [];
                var appendNodes = [];
                for (var i = originalTotal - cloneCount; i < originalTotal; i++) {
                    var $cloneStart = $originalItems.eq(i).clone();
                    $cloneStart.removeAttr('id').addClass('is-clone').attr('aria-hidden', 'true');
                    prependNodes.push($cloneStart.get(0));
                }
                for (var j = 0; j < cloneCount; j++) {
                    var $cloneEnd = $originalItems.eq(j).clone();
                    $cloneEnd.removeAttr('id').addClass('is-clone').attr('aria-hidden', 'true');
                    appendNodes.push($cloneEnd.get(0));
                }

                if (prependNodes.length) {
                    $inner.prepend(prependNodes);
                }
                if (appendNodes.length) {
                    $inner.append(appendNodes);
                }

                currentPos = cloneCount + preserveReal;
            }

            function updateControlsVisibility() {
                var showControls = visibleCount() < originalTotal;
                $carousel.toggleClass('sventes-has-nav', showControls);
                if ($carousel.find('.sventes-dots').length) {
                    if (showControls) $carousel.find('.sventes-dots').css('display', 'flex');
                    else $carousel.find('.sventes-dots').hide();
                }
            }

            function next() {
                moveTo(currentPos + 1, true, false);
            }

            function prev() {
                moveTo(currentPos - 1, true, false);
            }

            function startAutoplay() {
                if (!local_settings.autoplay) return;
                stopAutoplay();
                autoplayTimer = setInterval(function(){
                    if (!loopPrepared && !local_settings.loop && currentPos >= originalTotal - visibleCount()) {
                        stopAutoplay();
                        return;
                    }
                    next();
                }, Math.max(1000, parseInt(local_settings.autoplay_interval, 10) || 5000));
            }

            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }

            function resetAutoplay() {
                stopAutoplay();
                startAutoplay();
            }

            $next.on('click', function(e){
                e.preventDefault();
                if (!$carousel.hasClass('sventes-has-nav')) return;
                if (!local_settings.loop && !loopPrepared && currentPos >= originalTotal - visibleCount()) return;
                next();
                resetAutoplay();
            });

            $prev.on('click', function(e){
                e.preventDefault();
                if (!$carousel.hasClass('sventes-has-nav')) return;
                if (!local_settings.loop && !loopPrepared && currentPos <= 0) return;
                prev();
                resetAutoplay();
            });

            $dots.on('click', function(e){
                e.preventDefault();
                var i = parseInt($(this).data('index'), 10);
                if (isNaN(i)) return;
                moveTo(loopPrepared ? cloneCount + i : i, true, false);
                resetAutoplay();
            });

            $dotsNext.on('click', function(e){
                e.preventDefault();
                next();
                resetAutoplay();
            });

            $dotsPrev.on('click', function(e){
                e.preventDefault();
                prev();
                resetAutoplay();
            });

            $inner.on('keydown', function(e){
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    if (!local_settings.loop && !loopPrepared && currentPos <= 0) return;
                    prev();
                    resetAutoplay();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    if (!local_settings.loop && !loopPrepared && currentPos >= originalTotal - visibleCount()) return;
                    next();
                    resetAutoplay();
                }
            });

            var startX = null;
            $inner.on('touchstart', function(e){
                if (e.originalEvent.touches && e.originalEvent.touches.length === 1) {
                    startX = e.originalEvent.touches[0].clientX;
                }
            });

            $inner.on('touchend', function(e){
                if (startX === null) return;
                var endX = e.originalEvent.changedTouches[0].clientX;
                var diff = startX - endX;
                if (Math.abs(diff) > 40) {
                    if (diff > 0) next();
                    else prev();
                    resetAutoplay();
                }
                startX = null;
            });

            $carousel.on('wheel', function(e){
                var ev = e.originalEvent;
                var deltaX = ev.deltaX || 0;
                var deltaY = ev.deltaY || 0;
                if (Math.abs(deltaX) > Math.abs(deltaY)) {
                    e.preventDefault();
                    if (deltaX > 0) next();
                    else prev();
                    resetAutoplay();
                }
            });

            $carousel.on('mouseenter focusin', function(){
                stopAutoplay();
            });

            $carousel.on('mouseleave focusout', function(){
                startAutoplay();
            });

            $inner.on('transitionend', function(e){
                if (e.target !== $inner[0] || transitionResetting || !loopPrepared) return;

                var maxLoopPos = cloneCount + originalTotal - 1;
                var jumpTo = null;

                if (currentPos > maxLoopPos) {
                    jumpTo = cloneCount + (currentPos - (maxLoopPos + 1));
                } else if (currentPos < cloneCount) {
                    // When in prepended clone section, map back to original items
                    jumpTo = originalTotal + currentPos;
                }

                if (jumpTo !== null) {
                    moveTo(jumpTo, false, true);
                }
            });

            function onResize() {
                updateCssVar();
                syncLoopStructure();
                updateControlsVisibility();
                moveTo(currentPos, false, true);
            }

            $dots.each(function(i){
                $(this).attr('aria-controls', 'sventes-slide-' + i);
            });

            onResize();
            startAutoplay();

            $(window).on('resize', function(){
                clearTimeout($carousel.data('resizeTimeout'));
                $carousel.data('resizeTimeout', setTimeout(onResize, 120));
            });
        });
    });
})(jQuery);
