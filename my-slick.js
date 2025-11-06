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

        // ---------------- 1) Smooth / near-continuous (SEAMLESS WRAP) ----------------
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
                autoplaySpeed: 50, // micro delay (near-continuous look)
                speed: 4000, // long step for silky motion
                cssEase: "linear",
                // We will manage hover/touch pause ourselves for reliable resume
                pauseOnHover: false,
                pauseOnFocus: false,
                pauseOnDotsHover: false,
            });

            // Seamless wrap: when we pass the first set (A), jump back by N (no animation)
            $el.on("afterChange", function (_e, _slick, current) {
                const n = $el.data("originalCount");
                if (typeof n === "number" && current >= n) {
                    $el.slick("slickGoTo", current - n, true); // true = no animation
                }
                // If not manually paused or interacting, ensure crawl continues
                if (!$el.data("paused") && !$el.data("interacting")) {
                    $el.slick("slickPlay");
                }
            });

            // ---------------- Interaction handling (guaranteed resume) ----------------
            const $list = $el.find(".slick-list"); // the draggable viewport

            // Helper to toggle manual pause via tap/click on a slide
            function togglePause($carousel) {
                const isPaused = $carousel.data("paused") === true;
                if (isPaused) {
                    $carousel.data("paused", false);
                    $carousel.slick("slickPlay");
                } else {
                    $carousel.data("paused", true);
                    $carousel.slick("slickPause");
                }
            }

            // Pause immediately when the user engages (so drag feels natural)
            $list.on("pointerdown pointerenter", function () {
                $el.data("interacting", true);
                $el.slick("slickPause");
            });

            // Resume as soon as the user disengages, unless manually paused
            function resumeIfAllowed() {
                $el.data("interacting", false);
                if (!$el.data("paused")) {
                    // tiny timeout helps avoid jitter with rapid event sequences
                    setTimeout(function () {
                        $el.slick("slickPlay");
                    }, 40);
                }
            }
            $list.on("pointerup pointercancel pointerleave", resumeIfAllowed);

            // Also keep autoplay alive after layout updates
            $el.on("setPosition", function () {
                if (!$el.data("paused") && !$el.data("interacting")) {
                    $el.slick("slickPlay");
                }
            });

            // Tap/click a slide to toggle manual pause; leaving won’t auto-resume in that case
            $el.on("click", ".slick-slide", function (e) {
                if ($(e.target).closest(".slick-dots, .slick-arrow").length) return;
                togglePause($el);
            });

            // Touch support (in addition to pointer events) for older browsers
            $el.on("touchstart", function () {
                $el.data("interacting", true);
                $el.slick("slickPause");
            });
            $el.on("touchend touchcancel", function () {
                resumeIfAllowed();
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
