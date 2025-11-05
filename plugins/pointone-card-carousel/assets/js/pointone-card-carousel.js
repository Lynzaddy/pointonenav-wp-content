/* pointone-card-carousel.js — wide viewport (≥1600px) left-edge blank fix */
jQuery(function ($) {
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

    function vw() {
        return window.innerWidth || document.documentElement.clientWidth;
    }

    function minPadForViewport() {
        const w = vw();
        if (w >= 1025) return 160; // desktop peek
        if (w >= 768) return 100; // tablet peek
        return 60; // mobile peek
    }

    // .tall wants 4/3/2 slides visible with 40px total gap (20 each side)
    function tallPadForViewport() {
        const w = vw();
        const slideW = 260; // fixed slide width for .tall
        const footprint = slideW + 40; // slide + gap
        let target = 2;
        if (w >= 768 && w < 1025) target = 3;
        else if (w >= 1025) target = 4;
        const contentWidth = target * footprint;
        let pad = Math.max(Math.round((w - contentWidth) / 2), 40);

        // 🔧 Wide-canvas nudge: keep leftmost peek 1px inside the clip
        if (w >= 1600) pad = pad + 1;

        return pad;
    }

    /* ---------------- equal heights ---------------- */
    function equalizeHeights($slider) {
        if (!$slider.hasClass("slick-initialized")) return;
        const $slides = $slider.find(".slick-slide");
        $slides.css("min-height", "");
        const $vis = $slider.find(".slick-slide.slick-active");
        if ($vis.length === 0) return;
        let maxH = 0;
        $vis.each(function () {
            const h = $(this).outerHeight();
            if (h > maxH) maxH = h;
        });
        if (maxH > 0) $slides.css("min-height", maxH + "px");
    }
    function bindEqualizer($slider) {
        $slider.on("init reInit afterChange breakpoint setPosition", function () {
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
    function ensureVideoAttrs($slider) {
        $slider.find("video").each(function () {
            this.muted = true;
            this.playsInline = true;
            this.loop = true;
            this.setAttribute("preload", "metadata");
        });
    }

    // Paint a visible frame for all non-active videos (incl. clones)
    function seedPosterFrames($slider, seek = 0.15) {
        const seedOne = (v, $slide) => {
            try {
                if (v.readyState >= 2) {
                    if (!$slide.hasClass("slick-active")) {
                        v.currentTime = seek;
                        v.pause();
                    }
                } else {
                    v.addEventListener?.(
                        "loadeddata",
                        () => {
                            try {
                                if (!$slide.hasClass("slick-active")) {
                                    v.currentTime = seek;
                                    v.pause();
                                }
                            } catch (_) {}
                        },
                        { once: true }
                    );
                }
            } catch (_) {}
        };
        $slider.find("video").each(function () {
            seedOne(this, $(this).closest(".slick-slide"));
        });
        $slider.find(".slick-cloned video").each(function () {
            seedOne(this, $(this).closest(".slick-slide"));
        });
    }

    function pauseAllVideos($slider) {
        $slider.find("video").each(function () {
            try {
                this.pause();
            } catch (_) {}
        });
    }
    function playActiveVideos($slider) {
        $slider.find(".slick-active video").each(function () {
            try {
                this.currentTime = 0;
                const p = this.play();
                if (p && p.catch) p.catch(() => {});
            } catch (_) {}
        });
    }

    function bindVideoHandlers($slider) {
        ensureVideoAttrs($slider);

        $slider.on("init reInit", function () {
            seedPosterFrames($slider, 0.15);
            pauseAllVideos($slider);
            playActiveVideos($slider);
        });

        $slider.on("beforeChange", function (e, slick, current, next) {
            // On wrap to 0, pre-seed likely-left clones
            if (next === 0 && current >= 0) {
                $slider.find(".slick-cloned video").each(function () {
                    try {
                        this.currentTime = 0.15;
                        this.pause();
                    } catch (_) {}
                });
            }
            pauseAllVideos($slider);
        });

        $slider.on("afterChange breakpoint setPosition", function () {
            seedPosterFrames($slider, 0.15);
            playActiveVideos($slider);
        });
    }

    /* ---------------- controls bar ---------------- */
    function buildControlsBar($slider) {
        if ($slider.hasClass("hide-nav")) return null;
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

    /* ---------------- init: center (non-tall) ---------------- */
    function initCenter($el) {
        if ($el.hasClass("slick-initialized")) return;
        const hideNav = $el.hasClass("hide-nav");
        const $bar = buildControlsBar($el);
        const $prev = $bar ? $bar.find(".slick-prev") : $();
        const $next = $bar ? $bar.find(".slick-next") : $();
        const $dots = $bar ? $bar.find(".sc-dots") : $();

        bindEqualizer($el);
        bindVideoHandlers($el);

        const isAuto = $el.hasClass("autoplay");
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
            arrows: !hideNav,
            dots: !hideNav,
            prevArrow: $prev.length ? $prev : undefined,
            nextArrow: $next.length ? $next : undefined,
            appendDots: $dots.length ? $dots : undefined,
            useTransform: true,
            lazyLoad: "progressive",
            autoplay: isAuto,
            autoplaySpeed: 3000,
            pauseOnHover: true,
            pauseOnFocus: true,
            responsive: [
                { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true, useTransform: true } },
                { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true, useTransform: true } },
            ],
        });

        requestAnimationFrame(() => {
            try {
                $el.slick("setPosition");
            } catch (_) {}
        });
        requestAnimationFrame(() => {
            try {
                $el.slick("setPosition");
            } catch (_) {}
        });
    }

    /* ---------------- init: tall (peek 4/3/2) ---------------- */
    function initTall($el) {
        if ($el.hasClass("slick-initialized")) return;
        const hideNav = $el.hasClass("hide-nav");
        const $bar = buildControlsBar($el);
        const $prev = $bar ? $bar.find(".slick-prev") : $();
        const $next = $bar ? $bar.find(".slick-next") : $();
        const $dots = $bar ? $bar.find(".sc-dots") : $();

        bindEqualizer($el);
        bindVideoHandlers($el);

        const isAuto = $el.hasClass("autoplay");
        const seedPad = tallPadForViewport();

        $el.slick({
            variableWidth: true, // .tall .slide = 260px via CSS
            centerMode: true, // enables peeks
            centerPadding: seedPad + "px", // tuned to 4/3/2
            slidesToShow: 1,
            slidesToScroll: 1,
            infinite: true,
            speed: 300,
            waitForAnimate: false,
            swipeToSlide: true,
            arrows: !hideNav,
            dots: !hideNav,
            prevArrow: $prev.length ? $prev : undefined,
            nextArrow: $next.length ? $next : undefined,
            appendDots: $dots.length ? $dots : undefined,
            useTransform: true, // force transforms at all widths
            lazyLoad: "progressive",
            autoplay: isAuto,
            autoplaySpeed: 3000,
            pauseOnHover: true,
            pauseOnFocus: true,
            // no initialSlide
        });

        const updatePad = () => {
            try {
                $el.slick("slickSetOption", "centerPadding", tallPadForViewport() + "px", false);
                $el.slick("setPosition");
                seedPosterFrames($el, 0.15);
            } catch (_) {}
        };
        $(window).on("resize", debounce(updatePad, 120));
        $el.on("breakpoint", updatePad);

        // Extra settle passes + frame seeding
        requestAnimationFrame(() => {
            try {
                $el.slick("setPosition");
            } catch (_) {}
            seedPosterFrames($el, 0.15);
            requestAnimationFrame(() => {
                try {
                    $el.slick("setPosition");
                } catch (_) {}
                seedPosterFrames($el, 0.15);
            });
        });
    }

    /* ---------------- run ---------------- */
    $(".slider.center")
        .not(".tall")
        .each(function () {
            initCenter($(this));
        });
    $(".slider.tall").each(function () {
        initTall($(this));
    });

    requestAnimationFrame(() => {
        $(".slider.slick-initialized").each(function () {
            try {
                $(this).slick("setPosition");
            } catch (_) {}
        });
    });
});
