card carousel

    use normal cards
    wrap cards in a normal carousel
    check if parent block is a carousel

$("#vds-card-carousel .carousel-inner>.col-12").wrap( "<div class='carousel-item'></div>");
$(".carousel-item").first().addClass("active");
