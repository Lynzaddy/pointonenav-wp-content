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

    /* --------------------------- CENTER --------------------------- */
    $(".slider.center").each(function () {
        const $el = $(this);
        if ($el.hasClass("slick-initialized")) return;

        const $bar = buildControlsBar($el);
        const $prev = $bar.find(".slick-prev");
        const $next = $bar.find(".slick-next");
        const $dots = $bar.find(".sc-dots");

        bindEqualizer($el);
        bindVideoHandlers($el);

        const seedPad = minPadForViewport();

        // Check for autoplay class
        const isAuto = $el.hasClass("autoplay");

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
            autoplay: isAuto, // <— enabled if class present
            autoplaySpeed: 4000, // 4s per slide (adjust as needed)
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

    /* ------------------------ RESPONSIVE ------------------------- */
    $(".slider.responsive").each(function () {
        const $el = $(this);
        if ($el.hasClass("slick-initialized")) return;

        const $bar = buildControlsBar($el);
        const $prev = $bar.find(".slick-prev");
        const $next = $bar.find(".slick-next");
        const $dots = $bar.find(".sc-dots");

        bindEqualizer($el);
        bindVideoHandlers($el);

        // Check for autoplay class
        const isAuto = $el.hasClass("autoplay");

        $el.slick({
            slidesToShow: 4,
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
            autoplay: isAuto, // <— enabled if class present
            autoplaySpeed: 4000,
            pauseOnHover: true,
            pauseOnFocus: true,
            responsive: [
                { breakpoint: 1280, settings: { slidesToShow: 3 } },
                { breakpoint: 1024, settings: { slidesToShow: 2 } },
                { breakpoint: 768, settings: { slidesToShow: 1 } },
            ],
        });

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
