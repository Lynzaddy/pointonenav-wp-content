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

    /* padding calculator for .tall to target 4/3/2 visible slides (with peeks) */
    function tallPadForViewport() {
        const w = window.innerWidth || document.documentElement.clientWidth;
        const slideW = 260; // fixed desktop width for .tall
        const footprint = slideW + 40; // 40px total gap (20 L/R)
        let target = 2;
        if (w >= 768 && w < 1025) target = 3; // tablet
        else if (w >= 1025) target = 4; // desktop
        const contentWidth = target * footprint;
        const pad = Math.max(Math.round((w - contentWidth) / 2), 40);
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
            useTransform: false, // stability
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
    }

    function initTall($el) {
        if ($el.hasClass("slick-initialized")) return;

        const hideNav = $el.hasClass("hide-nav");
        const $bar = buildControlsBar($el);
        const $prev = $bar ? $bar.find(".slick-prev") : $();
        const $next = $bar ? $bar.find(".slick-next") : $();
        const $dots = $bar ? $bar.find(".sc-dots") : $();

        bindEqualizer($el);

        const isAuto = $el.hasClass("autoplay");
        const seedPad = tallPadForViewport();

        $el.slick({
            variableWidth: true, // width from CSS (.tall .slide => 260px)
            centerMode: true, // enables peeks
            centerPadding: seedPad + "px", // dynamic padding = peeks tuned to 4/3/2
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
            useTransform: false, // avoid GPU rounding on peeks/clones
            lazyLoad: "progressive",
            autoplay: isAuto,
            autoplaySpeed: 3000,
            pauseOnHover: true,
            pauseOnFocus: true,
            /* 👇 IMPORTANT: start on a safe index so the left peek is never the fragile original index 0 */
            initialSlide: 2,
        });

        const updatePad = () => {
            try {
                $el.slick("slickSetOption", "centerPadding", tallPadForViewport() + "px", false);
                $el.slick("setPosition");
            } catch (_) {}
        };
        $(window).on("resize", debounce(updatePad, 120));
        $el.on("breakpoint", updatePad);

        // settle layout twice post-init (no refresh; won't break autoplay)
        requestAnimationFrame(() => {
            try {
                $el.slick("setPosition");
            } catch (_) {}
            requestAnimationFrame(() => {
                try {
                    $el.slick("setPosition");
                } catch (_) {}
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
