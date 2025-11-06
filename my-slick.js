// my-slick.js
(function ($) {
    $(function () {
        // ---- Tuning knobs for the smooth slider ----
        // Increase these to slow down the movement.
        const SMOOTH_AUTOPLAY_DELAY = 180; // was ~50–80; try 120–250 for slow glide
        const SMOOTH_STEP_DURATION = 6000; // was ~800; try 1200–2200 for slower steps

        // ---- Shared/base settings for all sliders ----
        const base = {
            slidesToShow: 6,
            slidesToScroll: 1,
            infinite: true,
            arrows: true,
            dots: true,
            waitForAnimate: false, // allow arrows during animation
            swipeToSlide: true, // snappier drag
            touchThreshold: 8,
            responsive: [
                { breakpoint: 1024, settings: { slidesToShow: 3 } },
                { breakpoint: 640, settings: { slidesToShow: 2 } },
            ],
        };

        // ---- 1) Smooth-ish / near-continuous (.slider.smooth) ----
        // Slower, readable glide; still pauses cleanly and keeps controls.
        $(".slider.smooth")
            .not(".slick-initialized")
            .each(function () {
                $(this).slick({
                    ...base,
                    arrows: true,
                    dots: false,
                    autoplay: true,
                    autoplaySpeed: SMOOTH_AUTOPLAY_DELAY, // bigger = slower overall
                    speed: SMOOTH_STEP_DURATION, // bigger = slower step
                    cssEase: "linear", // steady motion
                    pauseOnHover: true,
                    pauseOnFocus: true,
                    pauseOnDotsHover: true,
                });
            });

        // ---- 2) Standard autoplay (.slider.autoplay) ----
        $(".slider.autoplay")
            .not(".slick-initialized")
            .each(function () {
                $(this).slick({
                    ...base,
                    autoplay: true,
                    autoplaySpeed: 3000,
                    speed: 400,
                    cssEase: "ease",
                    dots: true,
                });
            });

        // ---- 3) Plain/standard (.slider only) ----
        $(".slider")
            .filter(":not(.autoplay):not(.smooth)")
            .not(".slick-initialized")
            .each(function () {
                $(this).slick({
                    ...base,
                });
            });

        // --- Extra: mobile-friendly pause on touch for the smooth slider ---
        var $smooth = $(".slider.smooth");
        $smooth.on("touchstart", function () {
            $(this).slick("slickPause");
        });
        $smooth.on("touchend touchcancel", function () {
            $(this).slick("slickPlay");
        });
    });
})(jQuery);
