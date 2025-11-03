(function ($) {
    "use strict";

    /* ---------------------- utilities ---------------------- */
    function debounce(fn, wait) {
        var t;
        wait = wait || 120;
        return function () {
            clearTimeout(t);
            var ctx = this,
                args = arguments;
            t = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait);
        };
    }

    function minPadForViewport() {
        var w = window.innerWidth || document.documentElement.clientWidth;
        if (w >= 1025) return 160; // desktop
        if (w >= 768) return 100; // tablet
        return 60; // mobile
    }

    /* ------------- equal heights (keeps links aligned) ------------- */
    function equalizeHeights($slider) {
        if (!$slider.hasClass("slick-initialized")) return;
        var $slides = $slider.find(".slick-slide");
        $slides.css("min-height", "");
        var $visible = $slider.find(".slick-slide.slick-active");
        if ($visible.length === 0) return;
        var maxH = 0;
        $visible.each(function () {
            var h = $(this).outerHeight();
            if (h > maxH) maxH = h;
        });
        if (maxH > 0) $slides.css("min-height", maxH + "px");
    }

    function bindEqualizer($slider) {
        $slider.on("init reInit afterChange breakpoint", function () {
            setTimeout(function () {
                equalizeHeights($slider);
            }, 0);
        });

        $slider.find("img, video").each(function () {
            var el = this;
            if (!el.complete && $(el).one)
                $(el).one("load", function () {
                    equalizeHeights($slider);
                });
            if (el.addEventListener)
                el.addEventListener(
                    "loadedmetadata",
                    function () {
                        equalizeHeights($slider);
                    },
                    { once: true }
                );
        });

        $(window).on(
            "resize",
            debounce(function () {
                equalizeHeights($slider);
            }, 120)
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
        var $actives = $slider.find(".slick-active video");
        $actives.each(function () {
            try {
                this.currentTime = 0;
                var p = this.play();
                if (p && p.catch) p.catch(function () {}); // ignore autoplay block
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
        var $bar = $slider.next(".slick-controls");
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
    function initCenter($el) {
        if ($el.hasClass("slick-initialized")) {
            try {
                $el.slick("unslick");
            } catch (_) {}
        }

        var $bar = buildControlsBar($el);
        var $prev = $bar.find(".slick-prev");
        var $next = $bar.find(".slick-next");
        var $dots = $bar.find(".sc-dots");

        bindEqualizer($el);
        bindVideoHandlers($el);

        var seedPad = minPadForViewport();

        $el.slick({
            variableWidth: true, // slide width comes from CSS (center .slide uses CSS var)
            centerMode: true,
            centerPadding: seedPad + "px",
            slidesToShow: 1,
            slidesToScroll: 1,
            infinite: true,
            speed: 300,
            waitForAnimate: false, // smoother rapid clicks
            swipeToSlide: true,
            arrows: true,
            dots: true,
            prevArrow: $prev,
            nextArrow: $next,
            appendDots: $dots,
            lazyLoad: "progressive",
            responsive: [
                { breakpoint: 1024, settings: { centerMode: true, centerPadding: "100px", variableWidth: true } },
                { breakpoint: 768, settings: { centerMode: true, centerPadding: "60px", variableWidth: true } },
            ],
        });

        function updatePad() {
            try {
                $el.slick("slickSetOption", "centerPadding", minPadForViewport() + "px", false);
                $el.slick("setPosition");
            } catch (_) {}
        }
        $(window).on("resize", debounce(updatePad, 120));
        $el.on("breakpoint", updatePad);

        // first paint fix
        requestAnimationFrame(function () {
            try {
                $el.slick("setPosition");
            } catch (_) {}
        });
    }

    /* ------------------------ RESPONSIVE (unchanged) ------------------------- */
    function initResponsive($el) {
        if ($el.hasClass("slick-initialized")) {
            try {
                $el.slick("unslick");
            } catch (_) {}
        }

        var $bar = buildControlsBar($el);
        var $prev = $bar.find(".slick-prev");
        var $next = $bar.find(".slick-next");
        var $dots = $bar.find(".sc-dots");

        bindEqualizer($el);
        bindVideoHandlers($el);

        $el.slick({
            slidesToShow: 4,
            slidesToScroll: 1, // move one card at a time
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
            responsive: [
                { breakpoint: 1280, settings: { slidesToShow: 3 } },
                { breakpoint: 1024, settings: { slidesToShow: 2 } },
                { breakpoint: 768, settings: { slidesToShow: 1 } },
            ],
        });

        requestAnimationFrame(function () {
            try {
                $el.slick("setPosition");
            } catch (_) {}
        });
    }

    function initAll($scope) {
        $scope.find(".slider.center").each(function () {
            initCenter($(this));
        });
        $scope.find(".slider.responsive").each(function () {
            initResponsive($(this));
        });
    }

    $(function () {
        initAll($(document));
    });

    $(window).on("elementor/frontend/init", function () {
        try {
            elementorFrontend.hooks.addAction("frontend/element_ready/global", function ($scope) {
                initAll($scope);
            });
        } catch (e) {}
    });

    // settle pass
    requestAnimationFrame(function () {
        $(".slider.slick-initialized").each(function () {
            try {
                $(this).slick("setPosition");
            } catch (_) {}
        });
    });
})(jQuery);
