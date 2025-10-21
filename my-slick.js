$(function () {
    // Debounce utility
    function debounce(fn, wait = 120) {
        let t;
        return function () {
            clearTimeout(t);
            const ctx = this,
                args = arguments;
            t = setTimeout(() => fn.apply(ctx, args), wait);
        };
    }

    // Equalize slide heights so .link aligns bottom
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
        $slider.on("init reInit setPosition afterChange breakpoint", function () {
            setTimeout(() => equalizeHeights($slider), 0);
        });
        $slider.find("img").each(function () {
            if (!this.complete) $(this).one("load", () => equalizeHeights($slider));
        });
        $(window).on(
            "resize",
            debounce(() => {
                try {
                    $slider.slick("setPosition");
                } catch (_) {}
                equalizeHeights($slider);
            }, 140)
        );
    }

    // Play video only on active slide
    function handleVideoPlayback($slider) {
        const $videos = $slider.find("video");
        $videos.each(function () {
            this.pause();
        });
        $slider.find(".slick-active video").each(function () {
            this.currentTime = 0;
            this.play().catch(() => {});
        });
    }

    $(".slider").on("init afterChange", function (event, slick) {
        const $slider = $(slick.$slider);
        handleVideoPlayback($slider);
    });

    // Build controls [Prev][Dots][Next]
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

    // ==============================================================
    // CENTER MODE — consistent 40px gap + peek via centerPadding
    // ==============================================================
    $(".center").each(function () {
        const $el = $(this);
        if ($el.hasClass("slick-initialized")) return;

        const $bar = buildControlsBar($el);
        const $prev = $bar.find(".slick-prev");
        const $next = $bar.find(".slick-next");
        const $dots = $bar.find(".sc-dots");

        bindEqualizer($el);

        $el.slick({
            centerMode: true,
            centerPadding: "160px", // desktop peek
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
                { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px" } }, // tablet
                { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px" } }, // mobile
            ],
        });

        // Apply consistent 40px total gap (20px each side)
        $el.on("setPosition", function () {
            $el.find(".slick-slide").css("margin", "0 20px");
            $el.find(".slick-list").css("margin", "0 -20px");
        });
    });

    // ==============================================================
    // RESPONSIVE MODE — consistent 40px gap
    // ==============================================================
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
            slidesToScroll: 1,
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

        // Apply same 40px total gap
        $el.on("setPosition", function () {
            $el.find(".slick-slide").css("margin", "0 20px");
            $el.find(".slick-list").css("margin", "0 -20px");
        });
    });
});
