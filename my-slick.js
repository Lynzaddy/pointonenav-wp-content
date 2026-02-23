// my-slick.js
(function ($) {
    $(function () {
        // ---------------- Shared/base settings ----------------
        const base = {
            slidesToShow: 6,
            slidesToScroll: 1,
            infinite: true, // default; overridden for .smooth
            arrows: false,
            dots: false,
            waitForAnimate: false,
            swipeToSlide: true,
            touchThreshold: 8,
            responsive: [
                { breakpoint: 1024, settings: { slidesToShow: 2 } },
                { breakpoint: 640, settings: { slidesToShow: 1 } },
            ],
        };

        // ---------------- 1) Smooth / marquee (SEAMLESS, NON-INTERACTIVE) ----------------
        $(".slider.smooth").each(function () {
            const $el = $(this);

            // Duplicate slides once (A + B) for seamless wrap
            if (!$el.data("duped")) {
                const originalCount = $el.children(".slide").length;
                $el.data("originalCount", originalCount);
                $el.append($el.children(".slide").clone(true, true));
                $el.data("duped", true);
            }

            // Init with infinite:false so we control the wrap (no visible jump)
            $el.not(".slick-initialized").slick({
                ...base,
                infinite: false, // key to seamless wrap
                arrows: false,
                dots: false,
                autoplay: true,
                autoplaySpeed: 0, // no delay between steps (true continuous)
                speed: 15000, // long, steady crawl; adjust to taste
                cssEase: "linear",
                pauseOnHover: false, // no pausing
                pauseOnFocus: false,
                pauseOnDotsHover: false,

                // Make it non-interactive
                draggable: false,
                swipe: false,
                touchMove: false,
                accessibility: false,
            });

            // Seamless wrap: when we pass the first set (A), jump back by N (no animation)
            $el.on("afterChange", function (_e, _slick, current) {
                const n = $el.data("originalCount");
                if (typeof n === "number" && current >= n) {
                    $el.slick("slickGoTo", current - n, true); // true = no animation, no flicker
                }
            });
        });

        // ---------------- 2) Standard autoplay ----------------
        $(".slider.autoplay")
            .not(".slick-initialized")
            .each(function () {
                $(this).slick({
                    ...base,
                    autoplay: true,
                    autoplaySpeed: 3000,
                    speed: 400,
                    cssEase: "ease",
                    dots: false,
                });
            });

        // ---------------- 3) Plain/standard ----------------
        $(".slider")
            .filter(":not(.autoplay):not(.smooth)")
            .not(".slick-initialized")
            .each(function () {
                $(this).slick({ ...base });
            });
    });
})(jQuery);
