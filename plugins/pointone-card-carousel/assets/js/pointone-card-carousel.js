/* pointone-card-carousel.js
   - Standard carousels keep current behavior
   - .tall carousels get smooth, continuous scroll (linear, no stops)
   - .tall carousels play ALL videos continuously (no pause on slide change)
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

        /* ---------------- video handling (standard) ---------------- */
        function pauseAll($slider) {
            $slider.find("video").each(function () {
                try {
                    this.pause();
                } catch (_) {}
            });
        }
        function playActive($slider) {
            const $actives = $slider.find(".slick-active video");
            $actives.each(function () {
                try {
                    this.currentTime = 0;
                    const p = this.play();
                    if (p && p.catch) p.catch(() => {});
                } catch (_) {}
            });
        }
        function bindVideoHandlersStandard($slider) {
            $slider.find("video").attr({ preload: "metadata", playsInline: true, muted: true, loop: true });
            $slider.on("init reInit", function () {
                pauseAll($slider);
                playActive($slider);
            });
            $slider.on("beforeChange", function () {
                pauseAll($slider);
            });
            $slider.on("afterChange breakpoint", function () {
                playActive($slider);
            });
        }

        /* -------------- video handling (TALL: play all) -------------- */
        function bindVideoHandlersTall($slider) {
            $slider.find("video").attr({ preload: "auto", playsInline: true, muted: true, loop: true });
            // On init, try to play every video. Do NOT pause on slide changes.
            $slider.on("init reInit", function () {
                $slider.find("video").each(function () {
                    try {
                        this.currentTime = this.currentTime || 0;
                        const p = this.play();
                        if (p && p.catch) p.catch(() => {});
                    } catch (_) {}
                });
            });
            // Keep trying to play (some browsers block until user gesture)
            $slider.on("afterChange breakpoint", function () {
                $slider.find("video").each(function () {
                    try {
                        const p = this.play();
                        if (p && p.catch) p.catch(() => {});
                    } catch (_) {}
                });
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

        /* =======================
       STANDARD CENTER CAROUSELS
       (exclude .tall)
       ======================= */
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
                bindVideoHandlersStandard($el);

                const seedPad = minPadForViewport();

                $el.slick({
                    variableWidth: true,
                    centerMode: true,
                    centerPadding: seedPad + "px",
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    infinite: true,
                    speed: 300,
                    waitForAnimate: false,
                    swipeToSlide: true,
                    arrows: true,
                    dots: true,
                    prevArrow: $prev,
                    nextArrow: $next,
                    appendDots: $dots,
                    lazyLoad: "progressive",
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

        /* =======================
       TALL CAROUSELS — smooth ticker
       - continuous linear scroll
       - all videos play always
       ======================= */
        $(".slider.tall").each(function () {
            const $el = $(this);
            if ($el.hasClass("slick-initialized")) return;

            const $bar = buildControlsBar($el);
            const $prev = $bar.find(".slick-prev");
            const $next = $bar.find(".slick-next");
            const $dots = $bar.find(".sc-dots");

            bindEqualizer($el);
            bindVideoHandlersTall($el); // <<< play all videos

            const seedPad = minPadForViewport();

            $el.slick({
                variableWidth: true,
                centerMode: true, // keep peek style
                centerPadding: seedPad + "px",
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                arrows: true,
                dots: true,
                prevArrow: $prev,
                nextArrow: $next,
                appendDots: $dots,
                lazyLoad: "progressive",

                // >>> Smooth ticker settings
                autoplay: true,
                autoplaySpeed: 0, // no delay between moves
                speed: 10000, // duration of one “pass”
                cssEase: "linear", // smooth continuous movement
                pauseOnHover: false,
                pauseOnFocus: false,
                swipe: false, // avoid snapping
                touchMove: false,
                waitForAnimate: false,

                responsive: [
                    { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true } },
                    { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true } },
                ],
            });

            // keep peek padding responsive
            const updatePadTall = () => {
                try {
                    $el.slick("slickSetOption", "centerPadding", minPadForViewport() + "px", false);
                    $el.slick("setPosition");
                } catch (_) {}
            };
            $(window).on("resize", debounce(updatePadTall, 120));
            $el.on("breakpoint", updatePadTall);

            // first paint fix
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
