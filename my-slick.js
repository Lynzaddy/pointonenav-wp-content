// my-slick.js
(function ($) {
    $(function () {
        // ---- Shared/base settings for all sliders ----
        const base = {
            slidesToShow: 6,
            slidesToScroll: 1,
            infinite: true,
            arrows: true,
            dots: true,
            waitForAnimate: false, // allow arrows during animation
            swipeToSlide: true, // makes drag feel snappier
            touchThreshold: 8, // lower = more responsive drag
            responsive: [
                { breakpoint: 1024, settings: { slidesToShow: 2 } },
                { breakpoint: 640, settings: { slidesToShow: 1 } },
            ],
        };

        // ---- 1) Smooth-ish / near-continuous (.slider.smooth) ----
        // Looks continuous but pauses cleanly (no jump) and keeps full controls.
        $(".slider.smooth")
            .not(".slick-initialized")
            .each(function () {
                $(this).slick({
                    ...base,
                    arrows: false,
                    dots: false,
                    autoplay: true,
                    autoplaySpeed: 50, // tiny delay between micro-steps (try 30–80)
                    speed: 4000, // each micro-move duration (try 600–1000)
                    cssEase: "linear", // steady motion
                    pauseOnHover: true, // now pauses cleanly without snapping
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
