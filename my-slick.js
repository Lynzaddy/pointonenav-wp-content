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
            $(this).on("loadedmetadata", () => equalizeHeights($slider));
        });
        $(window).on(
            "resize",
            debounce(() => {
                equalizeHeights($slider);
            }, 120)
        );
    }

    /* ---------------- video: only play on active ---------------- */
    function handleVideoPlayback($slider) {
        const $videos = $slider.find("video");
        $videos.attr("preload", "metadata");
        $videos.each(function () {
            this.pause();
        });
        $slider.find(".slick-active video").each(function () {
            try {
                this.currentTime = 0;
                this.play().catch(() => {});
            } catch (_) {}
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
    $(".center").each(function () {
        const $el = $(this);
        if ($el.hasClass("slick-initialized")) return;

        const $bar = buildControlsBar($el);
        const $prev = $bar.find(".slick-prev");
        const $next = $bar.find(".slick-next");
        const $dots = $bar.find(".sc-dots");

        bindEqualizer($el);

        // With variableWidth, slide width equals content width (CSS).
        // We only need the MIN peek padding by breakpoint.
        const seedPad = minPadForViewport();

        $el.on("init reInit afterChange breakpoint", function () {
            handleVideoPlayback($el);
        });

        $el.slick({
            variableWidth: true, // <= critical so slide = media width
            centerMode: true,
            centerPadding: seedPad + "px", // guarantees peek
            slidesToShow: 1,
            slidesToScroll: 1,
            infinite: true,
            speed: 300,
            arrows: true,
            dots: true,
            prevArrow: $prev,
            nextArrow: $next,
            appendDots: $dots,
            responsive: [
                { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true } },
                { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true } },
            ],
        });

        // Keep peek consistent on resize/breakpoint
        $(window).on(
            "resize",
            debounce(() => {
                try {
                    $el.slick("slickSetOption", "centerPadding", minPadForViewport() + "px", false);
                    $el.slick("setPosition");
                } catch (_) {}
            }, 120)
        );
        $el.on("breakpoint", function () {
            try {
                $el.slick("slickSetOption", "centerPadding", minPadForViewport() + "px", false);
            } catch (_) {}
        });
    });

    /* ------------------------ RESPONSIVE ------------------------- */
    $(".responsive").each(function () {
        const $el = $(this);
        if ($el.hasClass("slick-initialized")) return;

        const $bar = buildControlsBar($el);
        const $prev = $bar.find(".slick-prev");
        const $next = $bar.find(".slick-next");
        const $dots = $bar.find(".sc-dots");

        bindEqualizer($el);

        $el.slick({
            slidesToShow: 4,
            slidesToScroll: 1, // move one card at a time
            infinite: true,
            speed: 300,
            arrows: true,
            dots: true,
            prevArrow: $prev,
            nextArrow: $next,
            appendDots: $dots,
            responsive: [
                { breakpoint: 1280, settings: { slidesToShow: 3 } },
                { breakpoint: 1024, settings: { slidesToShow: 2 } },
                { breakpoint: 768, settings: { slidesToShow: 1 } },
            ],
        });
    });

    // Ensure a stable first paint (esp. with hot reload/live server)
    requestAnimationFrame(() => {
        $(".slider.slick-initialized").each(function () {
            try {
                $(this).slick("setPosition");
            } catch (_) {}
        });
    });
});
