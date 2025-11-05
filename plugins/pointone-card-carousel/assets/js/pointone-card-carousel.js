/* pointone-card-carousel.js (≥1600px fix for .tall wrap + video seeding) */
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

    function minPadForViewport() {
        const w = window.innerWidth || document.documentElement.clientWidth;
        if (w >= 1025) return 160; // desktop peek
        if (w >= 768) return 100; // tablet peek
        return 60; // mobile peek
    }

    // For .tall: compute centerPadding so we show 4/3/2 with peeks
    function tallPadForViewport() {
        const w = window.innerWidth || document.documentElement.clientWidth;
        const slideW = 260; // fixed slide width for .tall
        const footprint = slideW + 40; // 40px total gap (20 L/R)
        let target = 2;
        if (w >= 768 && w < 1025) target = 3; // tablet
        else if (w >= 1025) target = 4; // desktop
        const contentWidth = target * footprint;
        const pad = Math.max(Math.round((w - contentWidth) / 2), 40);
        return pad;
    }

    /* ---------------- equal heights (keeps link rows aligned) ---------------- */
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

    /* ------------------------ video helpers ------------------------ */
    function ensureVideoAttrs($slider) {
        $slider.find("video").each(function () {
            this.muted = true;
            this.playsInline = true;
            this.loop = true;
            this.setAttribute("preload", "metadata");
        });
    }

    // Draw a visible first frame for non-active videos (including clones)
    function seedPosterFrames($slider, seek = 0.15) {
        const seedOne = (v, $slide) => {
            try {
                if (v.readyState >= 2) {
                    if (!$slide.hasClass("slick-active")) {
                        v.currentTime = seek; // nudge forward so a frame paints
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

        // All videos
        $slider.find("video").each(function () {
            seedOne(this, $(this).closest(".slick-slide"));
        });

        // Explicitly reseed all clones (helps on wrap at big widths)
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
        const $actives = $slider.find(".slick-active video");
        $actives.each(function () {
            const v = this;
            try {
                v.muted = true;
                v.playsInline = true;
                v.currentTime = 0; // restart for reliable loop
                const p = v.play();
                if (p && p.catch) p.catch(() => {});
            } catch (_) {}
        });
    }

    // Pre-seed the two left neighbors of the upcoming slide (handles wrap)
    function preseedLeftNeighbors($slider, nextIndex, seek = 0.15) {
        // Grab the two slides immediately to the left of where we’re going
        const $track = $slider.find(".slick-track");
        const $targets = $track.find(`[data-slick-index="${nextIndex - 1}"], [data-slick-index="${nextIndex - 2}"], [data-slick-index="-1"], [data-slick-index="-2"]`);
        $targets.find("video").each(function () {
            try {
                this.currentTime = seek;
                this.pause();
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
            // If we're wrapping (last -> 0), pre-seed clones that appear on the left
            if (next === 0 && current >= 0) {
                preseedLeftNeighbors($slider, next, 0.15);
            }
            pauseAllVideos($slider);
        });

        $slider.on("afterChange breakpoint setPosition", function (e, slick, current) {
            seedPosterFrames($slider, 0.15);
            playActiveVideos($slider);
        });
    }

    /* ---------------- controls: [Prev][Dots][Next] ---------------- */
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

    /* --------------------------- INIT HELPERS --------------------------- */
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
            useTransform: false,
            lazyLoad: "progressive",
            autoplay: isAuto,
            autoplaySpeed: 3000,
            pauseOnHover: true,
            pauseOnFocus: true,
            responsive: [
                { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true, useTransform: false } },
                { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true, useTransform: false } },
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
        const useXform = (window.innerWidth || document.documentElement.clientWidth) >= 1600; // transforms only at big widths

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
            useTransform: useXform, // key tweak: transforms ON at ≥1600px
            lazyLoad: "progressive",
            autoplay: isAuto,
            autoplaySpeed: 3000,
            pauseOnHover: true,
            pauseOnFocus: true,
            // no initialSlide (keeps clone order stable)
        });

        // Toggle transforms on resize around 1600px and re-seat padding
        const updatePadAndMode = () => {
            try {
                const w = window.innerWidth || document.documentElement.clientWidth;
                const wantTransform = w >= 1600;
                $el.slick("slickSetOption", "useTransform", wantTransform, false);
                $el.slick("slickSetOption", "centerPadding", tallPadForViewport() + "px", false);
                $el.slick("setPosition");
                seedPosterFrames($el, 0.15);
            } catch (_) {}
        };
        $(window).on("resize", debounce(updatePadAndMode, 120));
        $el.on("breakpoint", updatePadAndMode);

        // Double settle + seed
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

    /* --------------------------- INIT --------------------------- */
    $(".slider.center")
        .not(".tall")
        .each(function () {
            initCenter($(this));
        });
    $(".slider.tall").each(function () {
        initTall($(this));
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
