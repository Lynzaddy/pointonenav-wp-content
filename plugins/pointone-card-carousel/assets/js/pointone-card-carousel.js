/* pointone-card-carousel.js
   - .center: play video ONLY on active slide
   - .tall:   play ALL videos; extra clones + no-transform to fix left-edge gap
   - hide-nav, mobile controls hide, equal-heights kept
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

        /* ============== INIT: .center (unchanged) ============== */
        $(".slider.center")
            .not(".tall")
            .each(function () {
                const $el = $(this);
                if ($el.hasClass("slick-initialized")) return;

                const $bar = buildControlsBar($el);
                const $prev = $bar.find(".slick-prev");
                const $next = $bar.find(".slick-next");
                const $dots = $bar.find(".sc-dots");

                bindEqualizer($el);
                bindVideoHandlersActiveOnly($el);

                const seedPad = minPadForViewport();
                const wantsAutoplay = $el.hasClass("autoplay");

                $el.slick({
                    variableWidth: true,
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

                    responsive: [
                        { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true } },
                        { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true } },
                    ],
                });

                const updatePad = () => {
                    try {
                        $el.slick("slickSetOption", "centerPadding", minPadForViewport() + "px", false);
                        $el.slick("setPosition");
                    } catch (_) {}
                };
                $(window).on("resize", debounce(updatePad, 120));
                $el.on("breakpoint", updatePad);

                requestAnimationFrame(() => {
                    try {
                        $el.slick("setPosition");
                    } catch (_) {}
                });
            });

        /* ============== INIT: .tall (clone buffer + no-transform) ============== */
        $(".slider.tall").each(function () {
            const $el = $(this);
            if ($el.hasClass("slick-initialized")) return;

            const $bar = buildControlsBar($el);
            const $prev = $bar.find(".slick-prev");
            const $next = $bar.find(".slick-next");
            const $dots = $bar.find(".sc-dots");

            bindEqualizer($el);
            bindVideoHandlersPlayAll($el);

            const seedPad = minPadForViewport();

            $el.slick({
                variableWidth: true,
                centerMode: true,
                centerPadding: seedPad + "px",

                /* IMPORTANT: increase clone count so left edge never runs out */
                slidesToShow: 3, // with variableWidth this mainly affects clones
                slidesToScroll: 1,
                infinite: true,

                /* Prevent GPU rounding/flicker gaps on wrap */
                useTransform: false,

                speed: 300,
                cssEase: "ease",
                waitForAnimate: false,
                swipeToSlide: true,
                swipe: true,
                touchMove: true,
                draggable: true,
                respondTo: "window",
                touchThreshold: 10,

                arrows: true,
                dots: true,
                prevArrow: $prev,
                nextArrow: $next,
                appendDots: $dots,
                lazyLoad: "progressive",

                autoplay: false,

                responsive: [
                    /* keep clone buffer consistent across breakpoints */
                    { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true, slidesToShow: 3 } },
                    { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true, slidesToShow: 3 } },
                ],
            });

            const updatePad = () => {
                try {
                    $el.slick("slickSetOption", "centerPadding", minPadForViewport() + "px", false);
                    $el.slick("setPosition");
                } catch (_) {}
            };
            $(window).on("resize", debounce(updatePad, 120));
            $el.on("breakpoint", updatePad);

            requestAnimationFrame(() => {
                try {
                    $el.slick("setPosition");
                } catch (_) {}
            });
        });

        // Global settle pass
        requestAnimationFrame(() => {
            $(".slider.slick-initialized").each(function () {
                try {
                    $(this).slick("setPosition");
                } catch (_) {}
            });
        });
    });
})(jQuery);
