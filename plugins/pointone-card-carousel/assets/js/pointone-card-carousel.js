/* pointone-card-carousel.js
   Fix for variableWidth+centerMode first-card gap at large viewports:
   - Force double reflow + hard goTo on init and when crossing >=1600px.
   - Keep your existing behaviors (.center vs .tall, autoplay opt-in).
*/
(function ($) {
    $(function () {
        /* ---------------------- utilities ---------------------- */
        function debounce(fn, wait = 120) {
            let t;
            return function () {
                clearTimeout(t);
                const ctx = this,
                    args = arguments;
                t = setTimeout(() => fn.apply(ctx, args), wait);
            };
        }

        function minPadForViewport() {
            const w = window.innerWidth || document.documentElement.clientWidth;
            if (w >= 1600) return 160; // big desktop
            if (w >= 1025) return 160; // desktop
            if (w >= 768) return 100; // tablet
            return 60; // mobile
        }

        /* ---------------------- equal heights ---------------------- */
        function equalizeHeights($slider) {
            if (!$slider.hasClass("slick-initialized")) return;
            const $slides = $slider.find(".slick-slide");
            $slides.css("min-height", "");
            const $visible = $slider.find(".slick-slide.slick-active");
            if ($visible.length === 0) return;
            let maxH = 0;
            $visible.each(function () {
                const h = $(this).outerHeight();
                if (h > maxH) maxH = h;
            });
            if (maxH > 0) $slides.css("min-height", maxH + "px");
        }

        function bindEqualizer($slider) {
            $slider.on("init reInit afterChange breakpoint", function () {
                setTimeout(() => equalizeHeights($slider), 0);
            });

            $slider.find("img, video").each(function () {
                if (!this.complete) $(this).one("load", () => equalizeHeights($slider));
                $(this).on && $(this).on("loadedmetadata", () => equalizeHeights($slider));
            });

            $(window).on(
                "resize",
                debounce(() => equalizeHeights($slider), 120)
            );
        }

        /* ---------------- video helpers ---------------- */
        function attrVideos($scope) {
            $scope.find("video").attr({
                preload: "metadata",
                playsInline: true,
                muted: true,
                loop: true,
            });
        }
        function tryPlay(v) {
            try {
                v.currentTime = 0;
                const p = v.play();
                if (p && p.catch) p.catch(() => {});
            } catch (_) {}
        }

        /* ---- Behavior A: only active (for .center) ---- */
        function bindVideoHandlersActiveOnly($slider) {
            attrVideos($slider);
            function pauseAll() {
                $slider.find("video").each(function () {
                    try {
                        this.pause();
                    } catch (_) {}
                });
            }
            function playActive() {
                $slider.find(".slick-active video").each(function () {
                    tryPlay(this);
                });
            }
            $slider.on("init reInit", function () {
                pauseAll();
                playActive();
            });
            $slider.on("beforeChange", function () {
                pauseAll();
            });
            $slider.on("afterChange breakpoint", function () {
                playActive();
            });
        }

        /* ---- Behavior B: play everything (for .tall) ---- */
        function bindVideoHandlersPlayAll($slider) {
            attrVideos($slider);
            function playAll() {
                $slider.find("video").each(function () {
                    tryPlay(this);
                });
            }
            $slider.on("init reInit afterChange breakpoint", function () {
                setTimeout(playAll, 0);
            });
            $slider.find("video").each(function () {
                const v = this;
                if (!v.readyState || v.readyState < 2) {
                    $(v).on("loadedmetadata", () => tryPlay(v));
                }
            });
        }

        /* -------------- controls: [Prev][Dots][Next] -------------- */
        function buildControlsBar($slider) {
            let $bar = $slider.next(".slick-controls");
            if ($bar.length) return $bar;
            $bar = $(`
        <div class="slick-controls" aria-label="carousel controls">
          <button type="button" class="slick-prev slick-arrow-btn" aria-label="Previous"><span class="icon"></span></button>
          <div class="sc-dots" role="tablist"></div>
          <button type="button" class="slick-next slick-arrow-btn" aria-label="Next"><span class="icon"></span></button>
        </div>
      `);
            $slider.after($bar);
            return $bar;
        }

        /* ---------------------- hard fix helpers ---------------------- */
        function hardRealign($el) {
            // Force Slick to recalc widths/positions and snap back to current slide
            try {
                $el.slick("setPosition");
                const idx = $el.slick("slickCurrentSlide");
                // goTo with 'dontAnimate' true prevents visible jump
                $el.slick("slickGoTo", idx, true);
                $el.slick("setPosition");
            } catch (_) {}
        }

        // Track whether we are in big-desktop to trigger realign only when crossing the threshold
        let wasBigDesktop = window.innerWidth >= 1600;

        /* =======================
       SHARED INIT for .center and .tall
       ======================= */
        $(".slider.center, .slider.tall").each(function () {
            const $el = $(this);
            if ($el.hasClass("slick-initialized")) return;

            const $bar = buildControlsBar($el);
            const $prev = $bar.find(".slick-prev");
            const $next = $bar.find(".slick-next");
            const $dots = $bar.find(".sc-dots");

            bindEqualizer($el);

            if ($el.hasClass("tall")) {
                // For .tall we want every video rolling
                bindVideoHandlersPlayAll($el);
            } else {
                bindVideoHandlersActiveOnly($el);
            }

            const seedPad = minPadForViewport();
            const wantsAutoplay = $el.hasClass("autoplay");

            $el.slick({
                variableWidth: true, // width from CSS
                centerMode: true,
                centerPadding: seedPad + "px",
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                speed: 300,
                cssEase: "ease",
                waitForAnimate: false,
                swipeToSlide: true,
                swipe: true,
                touchMove: true,
                draggable: true,
                respondTo: "window",
                arrows: true,
                dots: true,
                prevArrow: $prev,
                nextArrow: $next,
                appendDots: $dots,
                lazyLoad: "progressive",
                autoplay: wantsAutoplay,
                autoplaySpeed: 3000,
                pauseOnHover: true,
                pauseOnFocus: true,
                // A tiny edgeFriction helps left-swipe at large widths feel right
                edgeFriction: 0.15,

                responsive: [
                    { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true } },
                    { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true } },
                ],
            });

            // Initial double realign (fix first-card gap on load, esp. >=1600px)
            // 1) next tick
            requestAnimationFrame(() => hardRealign($el));
            // 2) after media settles
            setTimeout(() => hardRealign($el), 120);

            // Keep peek padding responsive
            const updatePad = () => {
                try {
                    $el.slick("slickSetOption", "centerPadding", minPadForViewport() + "px", false);
                    hardRealign($el);
                } catch (_) {}
            };
            $(window).on(
                "resize",
                debounce(() => {
                    const nowBig = window.innerWidth >= 1600;
                    // If we crossed the 1600px boundary in either direction, do a hard realign
                    if (nowBig !== wasBigDesktop) {
                        wasBigDesktop = nowBig;
                        updatePad();
                    } else {
                        // normal resize, still do pad+realign to avoid fractional rounding gaps
                        updatePad();
                    }
                }, 120)
            );

            // Also realign when Slick flips breakpoints
            $el.on("breakpoint", () => hardRealign($el));
        });

        // Global settle pass (covers weird delayed fonts/media)
        requestAnimationFrame(() => {
            $(".slider.slick-initialized").each(function () {
                hardRealign($(this));
            });
        });
    });
})(jQuery);
