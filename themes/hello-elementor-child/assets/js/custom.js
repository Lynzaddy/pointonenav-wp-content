jQuery(document).ready(function ($) {
    $(".testimonial-slider").slick({
        centerMode: true,
        slidesToShow: 3,
        centerPadding: "20px",
        infinite: true,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    centerPadding: "0px",
                },
            },
        ],
    });
});
