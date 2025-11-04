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
        if (w >= 1025) return 160; // desktop
        if (w >= 768) return 100; // tablet
        return 60; // mobile
    }

    /* NEW: padding calculator for .tall to target 4/3/2 visible slides (with peeks) */
    function tallPadForViewport() {
        const w = window.innerWidth || document.documentElement.clientWidth;
        const slideW = Math.min(260, Math.max(200, Math.round(w * 0.24))); // mirrors CSS clamp(200px, 24vw, 260px)
        const footprint = slideW + 40; // 40px total gap
        let targetCount = 2;
        if (w >= 768 && w < 1025) targetCount = 3; // tablet
        else if (w >= 1025) targetCount = 4; // desktop
        const contentWidth = targetCount * footprint;
        const pad = Math.max(Math.round((w - contentWidth) / 2), 40); // ensure a peek even when tight
        return pad;
    }

    /* ------------- equal heights (keeps links aligned) ------------- */
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

    /* ---------------- video: play only on active ---------------- */
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
    function bindVideoHandlers($slider) {
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

    /* -------------- controls: [Prev][Dots][Next] -------------- */
    function buildControlsBar($slider) {
        if ($slider.hasClass("hide-nav")) return null; // do not create nav at all
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

    /* ---------- shared first-paint stabilizer for center/variableWidth ---------- */
    function stabilizeFirstPaint($el, calcPadFn) {
        const updatePad = () => {
            try {
                $el.slick("slickSetOption", "centerPadding", calcPadFn() + "px", false);
                $el.slick("setPosition");
            } catch (_) {}
        };

        $el.on("init", function () {
            // Recompute padding immediately
            updatePad();

            // Desktop-only “jump” fix to ensure left peek exists on first paint:
            // start at slide 1 then snap back to 0 without animation.
            const w = window.innerWidth || document.documentElement.clientWidth;
            if (w >= 1025) {
                try {
                    // if we started at 1, jump back to 0; if not, do a noop roundtrip
                    const cur = $el.slick("slickCurrentSlide");
                    if (cur === 1) {
                        $el.slick("slickGoTo", 0, true);
                    } else {
                        $el.slick("slickGoTo", 1, true);
                        $el.slick("slickGoTo", 0, true);
                    }
                } catch (_) {}
            }

            // Double-set after paint to fully settle track/clones
            requestAnimationFrame(() => {
                updatePad();
                requestAnimationFrame(updatePad);
            });
        });

        // Keep peek consistent on resize/breakpoint
        $(window).on("resize", debounce(updatePad, 120));
        $el.on("breakpoint", updatePad);
    }

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

        // Desktop boot at slide 1 to ensure left clone exists, we’ll snap back in stabilizeFirstPaint
        const initialSlide = (window.innerWidth || document.documentElement.clientWidth) >= 1025 ? 1 : 0;

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
            lazyLoad: "progressive",
            autoplay: isAuto,
            autoplaySpeed: 4000,
            pauseOnHover: true,
            pauseOnFocus: true,
            initialSlide: initialSlide,
            responsive: [
                { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true, initialSlide: 0 } },
                { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true, initialSlide: 0 } },
            ],
        });

        stabilizeFirstPaint($el, minPadForViewport);

        // extra settle
        requestAnimationFrame(() => {
            try {
                $el.slick("setPosition");
            } catch (_) {}
        });
    }

    /* ====== NEW: TALL VARIANT ====== */
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

        // Desktop boot at slide 1 to guarantee left peek on first paint
        const initialSlide = (window.innerWidth || document.documentElement.clientWidth) >= 1025 ? 1 : 0;

        $el.slick({
            variableWidth: true, // width from CSS (.tall .slide)
            centerMode: true, // enable peeking edges
            centerPadding: seedPad + "px", // dynamic padding for ~4/3/2 visible
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
            lazyLoad: "progressive",
            autoplay: isAuto,
            autoplaySpeed: 4000,
            pauseOnHover: true,
            pauseOnFocus: true,
            initialSlide: initialSlide,
        });

        stabilizeFirstPaint($el, tallPadForViewport);

        // extra settle
        requestAnimationFrame(() => {
            try {
                $el.slick("setPosition");
            } catch (_) {}
        });
    }

    /* --------------------------- INIT --------------------------- */
    // Initialize by variant
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
