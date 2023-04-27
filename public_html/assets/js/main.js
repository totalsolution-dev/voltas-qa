// VDS COMPONENT JAVASCRIPTS
var clog = function(message) {
    console.log('=========| ' + message + ' |=========');
};

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
NAVIGATION / Sticky
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
// When the user scrolls the page, execute stickyNav
window.onscroll = function() {stickyNav()};

// Get the navbar
var navbar = document.getElementById("navbar");

// Get the offset position of the navbar
var sticky = navbar.offsetTop;

// Add the sticky class to the navbar when you reach its scroll position. Remove "sticky" when you leave the scroll position
function stickyNav() {
    if (window.pageYOffset >= sticky) {
      navbar.classList.add("sticky")
    } else {
      navbar.classList.remove("sticky");
    }
  }


/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
NAVIGATION / Search
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

// Store jQuery objects in variables
// nav bar
var $navbar_nav = $('.navbar-nav');
// nav search desktop
var $nav_search = $('.vds-nav-search');
var $nav_search_form = $('.vds-nav-search #search-form');
// nav search mobile
var $nav_search_m_submit = $('.vds-nav-search-m #m-submit');
var $nav_search_m_form = $('.vds-nav-search-m #search-m-form');

// bind form submit to click on mobile submit icon
$nav_search_m_submit.on('click', function(event) {
    $nav_search_m_form.first().submit();
});

// bind click function on desktop to open search box
$nav_search.find('i').on('click', function(event) {
    if ($('.vds-nav-search .form-search-input').val() != '') {} else {
        $nav_search
            .addClass('ml-auto')
            .find('.form-search-input')
            .focus()
            .addClass('visible');
        $navbar_nav.hide();
    }
});

// bind closing of search box to form blur event
$nav_search.find('.form-search-input').on('blur', function(event) {
    $(this).removeClass('visible');
    $navbar_nav.fadeIn();
    $nav_search.removeClass('ml-auto');
});

// SEARCH RESULTS PAGE SCRIPTS
// Remove slashes from search result text
$('.vds-search')
    .find('h2 span')
    .each(function() {
        var search_text = $(this).text();
        var trimmed = search_text.slice(1, -1);
        var space_added = trimmed.replace(/\//g, ' / ');
        $(this).text(space_added);
    });

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
GRID / CARD CAROUSEL
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

// add the carousel-item class to all cards inside card carousel
$('.vds-card-carousel .carousel-inner').each(function() {
    $(this).children().addClass('carousel-item');
});

// swap controls if cart is inverted
$('.vds-card-carousel .carousel-inner')
    .find('.order-last')
    .each(function() {
        // find buttons and create clones to then swap them out
        var $prev_button = $(this).find('.vds-carousel-control-prev').first();
        var $prev_button_c = $prev_button.clone();
        var $next_button = $(this)
            .next()
            .find('.vds-carousel-control-next')
            .first();
        var $next_button_c = $next_button.clone();
        // swap both buttons by replacing them with opposite clone
        $prev_button.replaceWith($next_button_c);
        $next_button.replaceWith($prev_button_c);
    });

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
HEADER CAROUSEL
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

//  Initiate each carousel object in the page
$('.vds-carousel').each(function() {
    // current carousel object
    var $this_carousel = $(this);
    // current slide / set intitial slide value to one
    var carousel_play = 1;
    // total number of slides
    var total_slides = $(this).find('.carousel-item').length;
    // slide number container (in the carousel caption)
    var $this_carousel_slidenumber = $(this).find('.vds-carousel-slidenumber');
    // Set initial value for slide 1 on load.
    $this_carousel_slidenumber.html('1 / ' + total_slides);

    // Hide carousel controls and page indicator if single slide
    if (total_slides == 1) {
        $this_carousel.find('.vds-carousel-controls').hide();
    }

    $this_carousel.find('.carousel-item').first().addClass('active');

    // initiate carousel / cycle automatically
    if ($this_carousel.hasClass('slide')) {
        // $this_carousel.carousel("cycle");
    } else {
        $this_carousel.carousel('pause');
    }
    // $this_carousel.carousel({
    //     pause: "hover"
    // });
    // bind click function to the prev/next buttons
    $this_carousel.find('.vds-carousel-control-prev').click(function(event) {
        event.preventDefault();
        $this_carousel.carousel('prev');
    });
    $this_carousel.find('.vds-carousel-control-next').click(function(event) {
        event.preventDefault();
        $this_carousel.carousel('next');
    });

    // bind click function to the play button (currently unused)

    // $this_carousel.find(".vds-carousel-control-play").click(function (event) {
    //     event.preventDefault();
    //     var $this_icon = $(this).find("i");

    //     if(carousel_play == 0){
    //         $this_carousel.carousel("cycle");
    //         carousel_play = 1;
    //         console.log("0");
    //         console.log($this_icon);
    //         $this_icon.addClass("fa-pause-circle").removeClass("fa-play-circle")
    //     }
    //     else if(carousel_play == 1){
    //         $this_carousel.carousel("pause");
    //         carousel_play = 0;
    //         console.log("1");
    //         console.log($this_icon);
    //         $this_icon.addClass("fa-play-circle").removeClass("fa-pause-circle")
    //     }
    // });

    // Bind updating of slide number to the sliding completed event
    $this_carousel.on('slid.bs.carousel', function(event) {
        $this_carousel_slidenumber.html(event.to + 1 + ' / ' + total_slides);
    });
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
MARKET TICKER / API INTEGRATION
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
// Get the date and put into required format
function format_date(date) {
    var months = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];
    month = date.getMonth();
    month = months[month]; //javascript date goes from 0 to 11
    // if (month < 10) month = "0" + month; //adding the prefix

    year = date.getFullYear();
    day = date.getDate();
    hour = date.getHours();
    minutes = date.getMinutes();
    seconds = date.getSeconds();

    return month + ' ' + day + ' ' + year;
}

// MyDate = new Date(value);
// formatedDate = format_date(MyDate);

// Call API on Total Solution Server.
var jqxhr = $.getJSON(
        'https://voltas.totalsolution.net.in/appapi/api/ticker',
        function() {
            // for local testing:
            // var jqxhr = $.getJSON( "ticker.json", function() {
            // console.log("Stock data loaded succesfully.");
        }
    )
    .done(function(data) {
        // Update BSE values ===================================

        // Check for price increase / decrease and update arrows
        if (data[0].Price >= data[0].Prev) {
            // console.log("price up");
            $('#bse-indicator-arrow').addClass('fa-arrow-up');
        } else {
            // console.log("price down");
            $('#bse-indicator-arrow').addClass('fa-arrow-down');
        }

        // Update ticket values
        $('#bse-current').html(data[0].Price);
        $('#bse-high').html(data[0].High);
        $('#bse-low').html(data[0].Low);
        $('#bse-change').html(data[0].Change);
        $('#bse-volumep').html(data[0].ChangePercent);
        $('#bse-time').html(data[0].Time);
        $('#bse-volume').html(data[0].Volume);

        MyDate = new Date(data[0].Timestamp);
        // console.log(data[0].Timestamp);
        formatedDate = format_date(MyDate);
        let newDate = data[0].Timestamp.split(' ');
        $('#bse-date').html(newDate[0]);

        // Update NSE values ===================================

        // Check for price increase / decrease and update arrows
        let prevPrice = data[1].Prev.replace(/,/g, '');
        // console.log(prevPrice);
        if (data[1].Price >= prevPrice) {
            // console.log("price up");
            $('#nse-indicator-arrow').addClass('fa-arrow-up');
        } else {
            // console.log("price down");
            $('#nse-indicator-arrow').addClass('fa-arrow-down');
        }

        // Update ticket values
        $('#nse-current').html(data[1].Price);
        $('#nse-high').html(data[1].High);
        $('#nse-low').html(data[1].Low);
        $('#nse-change').html(data[1].Change);
        $('#nse-volumep').html(data[1].ChangePercent);
        $('#nse-time').html(data[1].Time);
        $('#nse-volume').html(data[1].Volume);

        MyDate = new Date(data[1].Timestamp);
        formatedDate = format_date(MyDate);
        $('#nse-date').html(formatedDate);
        let newDate1 = data[1].Timestamp.split(' ');
        $('#nse-date').html(newDate1[0]);
    })
    .fail(function() {
        // Remove ticket from page in case API fails.
        $('.voltas-card-market').parent().remove();
    })
    .always(function() {
        // console.log( "complete" );
    });

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
IN THE NEWS / CARD FILTER SEARCH
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
var value = 0;
$('#card-filter-input').on({
    keyup: function() {
        // console.log("key-up");
        value = $(this).val().toLowerCase();
        // console.log(value);
        $('#in-the-news-accordion .collapse .col-12')
            .addClass('not-result')
            .filter(function() {
                $(this).toggleClass(
                    'not-result',
                    $(this).text().toLowerCase().indexOf(value) == -1
                );
            });
    },
    focus: function() {
        // open all accordions on focus of search
        $('#in-the-news-accordion .collapse').collapse('show');
    },
    blur: function() {
        // collapse all except for the first one on blur if search box is empty
        // Otherwise leave them open so visitor can access the results.
        var value_length = value.length;
        // console.log(value_length);
        if (value_length == 0 || value_length == null) {
            $('#in-the-news-accordion .collapse')
                .collapse('hide')
                .on('hidden.bs.collapse', function() {
                    $('.item-1').collapse('show');
                });
        }
    }
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
TABBED FILTER
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
if ($('.filter-group').length) {
    $('.filter-group').each(function() {
        //console.log($(this).find(".filter-labels a"));
        var $filter_items = $(this).find('.filter-items');
        // console.log($filter_items);
        // console.log( $(this).find(".filter-labels a"));
        $(this)
            .find('.filter-labels a')
            .each(function() {
                // count the number of cards for each filter
                var card_count = $filter_items.children(
                    '.' + $(this).data('filter')
                ).length;
                // append the count (12) to the tab lable
                $(this).append(' (' + card_count + ')');
                // hide the tab if there are cards == 0
                if (card_count == 0) {
                    $(this).closest('.nav-item').hide();
                }
            });
        $(this)
            .find('.filter-labels')
            .on('click', 'a', function(event) {
                event.preventDefault();
                // console.log($(this).data("filter"));
                $filter_items.children().show();
                $filter_items
                    .children()
                    .not('.' + $(this).data('filter'))
                    .hide();
            });
    });
}

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
TESTIMONIAL COLLAPSE
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

$('.card-collapse').each(function() {
    $this_collapse = $(this);

    $this_collapse.find('.card-collapse-button').on('click', function(event) {
        $this_collapse_intro = $(this)
            .parent()
            .parent()
            .find('.card-collapse-intro');
        $this_collapse_body = $(this).parent().parent().find('.card-collapse-body');
        // console.log($this_collapse_intro);
        // console.log($this_collapse_body);
        $this_collapse_intro.toggleClass('hidden');
        $this_collapse_body.toggleClass('hidden');
    });
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
LEADERSHIP COLLAPSE
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

// monitor the window size to change clear expanded cards
$(window).resize(function() {
    if ($(window).width() < 768) {
        // change functionality for smaller screens
        clog('small screen');
        $('.leadership-collapse-card').remove();
    } else {
        // change functionality for larger screens
        clog('large screen');
        $('.card-collapse-body').addClass('hidden');
        $('.card-collapse-intro').removeClass('hidden');
    }
});

$('.vds-row-leadership').each(function() {
    $this_leadership_row = $(this);

    // appened containers after every 4th card and the last for the collapse text
    $this_leadership_row
        .find('.col-12:nth-child(4n), .col-12:last-child')
        .after('<div class="col-12 collapse-container"></div>');

    // on click, fetch the collapse content from the card and place in nearest next container
    $this_leadership_row.find('.leadership-card').each(function() {
        $this_leadership_card = $(this);

        // bind click event
        $this_leadership_card
            .find('.card-collapse-button')
            .on('click', function(event) {
                // fetch intro and body in var
                $this_collapse_intro = $(this)
                    .parent()
                    .parent()
                    .find('.card-collapse-intro');
                $this_collapse_body = $(this)
                    .parent()
                    .parent()
                    .find('.card-collapse-body');
                // expand card inside or in collapse container depending on screen width
                if ($(window).width() < 768) {
                    // change functionality for smaller screens
                    clog('small screen');
                    // toggle the content in the card
                    $this_collapse_intro.toggleClass('hidden');
                    $this_collapse_body.toggleClass('hidden');
                } else {
                    // change functionality for larger screens
                    clog('large screen');
                    // find nearest collapse container to place the content
                    var colllapse_content = $(this)
                        .closest('.col-12')
                        .nextAll('.collapse-container')
                        .first();
                    // close content to place in container
                    var cloned_body = $this_collapse_body.clone();
                    // create a card to wrap the content in and append content
                    var card = $(
                        '<div class="card voltas-card leadership-collapse-card bg-slate-primary"></div>'
                    ).html(cloned_body);
                    // add content to container
                    colllapse_content.html(card);
                    // bind close button function to remove content on close
                    colllapse_content
                        .find('.card-collapse-button')
                        .on('click', function(event) {
                            $(this).closest('.leadership-collapse-card').remove();
                        });
                }
            });
    });
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
CARD COLLAPSE
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

// monitor the window size to change clear expanded cards
$(window).resize(function() {
    if ($(window).width() < 768) {
        // change functionality for smaller screens
        clog('small screen');
        $('.card-collapse-card').remove();
    } else {
        // change functionality for larger screens
        clog('large screen');
        $('.card-collapse-body').addClass('hidden');
        $('.card-collapse-intro').removeClass('hidden');
    }
});

$('.vds-row-card_collapse').each(function() {
    $this_leadership_row = $(this);
    clog($this_leadership_row);
    console.log($this_leadership_row);

    // appened containers after every 4th card and the last for the collapse text
    $this_leadership_row
        .find('.col-12:nth-child(2n), .col-12:last-child')
        .after('<div class="col-12 collapse-container"></div>');

    // on click, fetch the collapse content from the card and place in nearest next container
    $this_leadership_row.find('.voltas-collapse-card').each(function() {
        $this_leadership_card = $(this);

        // bind click event
        $this_leadership_card
            .find('.card-collapse-button')
            .on('click', function(event) {
                // fetch intro and body in var
                $this_collapse_intro = $(this)
                    .parent()
                    .parent()
                    .find('.card-collapse-intro');
                $this_collapse_body = $(this)
                    .parent()
                    .parent()
                    .find('.card-collapse-body');
                // expand card inside or in collapse container depending on screen width
                if ($(window).width() < 768) {
                    // change functionality for smaller screens
                    clog('small screen');
                    // toggle the content in the card
                    $this_collapse_intro.toggleClass('hidden');
                    $this_collapse_body.toggleClass('hidden');
                } else {
                    // change functionality for larger screens
                    clog('large screen');
                    // find nearest collapse container to place the content
                    var colllapse_content = $(this)
                        .closest('.col-12')
                        .nextAll('.collapse-container')
                        .first();
                    // close content to place in container
                    var cloned_body = $this_collapse_body.clone();
                    // create a card to wrap the content in and append content
                    var card = $(
                        '<div class="card voltas-card card-collapse-card bg-slate-primary"></div>'
                    ).html(cloned_body);
                    // add content to container
                    colllapse_content.html(card);
                    // bind close button function to remove content on close
                    colllapse_content
                        .find('.card-collapse-button')
                        .on('click', function(event) {
                            $(this).closest('.card-collapse-card').remove();
                        });
                }
            });
    });
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
ACCORDION CUSTOM ICONS
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
$(document).ready(function() {
    // Add minus icon for collapse element which is open by default
    $('.collapse.show').each(function() {
        $(this)
            .prev('.card-header')
            .find('.fa')
            .addClass('fa-minus')
            .removeClass('fa-plus');
    });

    // Toggle plus minus icon on show hide of collapse element
    $('.collapse')
        .on('show.bs.collapse', function() {
            $(this)
                .prev('.card-header')
                .find('.fa')
                .removeClass('fa-plus')
                .addClass('fa-minus');
        })
        .on('hide.bs.collapse', function() {
            $(this)
                .prev('.card-header')
                .find('.fa')
                .removeClass('fa-minus')
                .addClass('fa-plus');
        });
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
LOGO CAROUSEL
— Uses slick js carousel plugin
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
$(document).ready(function() {
    $('.your-class').slick({
        dots: false,
        infinite: true,
        speed: 300,
        slidesToShow: 4,
        slidesToScroll: 4,
        prevArrow: '<i class="fa fa-arrow-left logo-prev" aria-hidden="true"></i>',
        nextArrow: '<i class="fa fa-arrow-right logo-next" aria-hidden="true"></i>',
        responsive: [{
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    infinite: true,
                    dots: false
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
FORM FOCUS HANDLING
— To enable material design like labels
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
// on input focus label add class
$('.form-group input, .form-group select').on('focus', function() {
    $(this).siblings('label').addClass('focus');
});
$('.form-group input, .form-group select').on('blur', function() {
    if ($(this).val() == '') {
        $(this).siblings('label').removeClass('focus');
    }
});
$('.form-group textarea').on('focus', function() {
    // $(this).siblings("label").hide();
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
ENQUIRY MODAL
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

$('.card-enquiry.preview .nav-item')
    .first()
    .on('click', function() {
        // fetch the parent card and card body
        var $this = $(this).closest('.card-enquiry.preview');
        var $card_body = $this.find('.card-body');

        // swapp classes and slide down effect for card body
        $this.removeClass('preview');
        $card_body.hide().removeClass('d-none').slideDown();

        // bind a click event to the close button
        $this
            .find('.close-icon')
            .first()
            .on('click', function() {
                // swap class
                $this.addClass('preview');
                // slide up effect whike closing.
                $card_body.slideUp();
            });
    });

if ($('.errors.help-block').length != 0) {
    $('.card-enquiry.preview .nav-item').first().click();
}

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
INVESTOR TOOLKIT
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
$('.card-investor_toolkit .card-body').hide();

$('.card-investor_toolkit .card-header').click(function() {
    var $toolkit = $(this).closest('.card-investor_toolkit');
    var $card_body = $(this).siblings('.card-body');
    if ($toolkit.hasClass('preview')) {
        $toolkit.toggleClass('preview');
        $card_body.delay(300).slideToggle(300);
    } else {
        $card_body
            .slideToggle(300)
            .promise()
            .done(function() {
                $toolkit.toggleClass('preview');
            });
    }
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
FOOTER NAVIGATION
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
$('.root-nav li a').siblings('ul').hide();

$('.root-nav li a').click(function(event) {
    if ($(this).siblings('ul').length) {
        event.preventDefault();
    }
    $(this).siblings('ul').first().slideToggle();
});

/*
::::::::::::::::::::::::::::::::::::::::::::::::::::
FINANCIAL SNAPSHOT DROPDOWN
::::::::::::::::::::::::::::::::::::::::::::::::::::
*/
// Find the dropdown items and bind click function
$('.dropdown-item').on('click', function() {
    // Find the relationship of the dropdown menu from the parent element and store in variable
    var dropdown_menu = $(this).closest('.dropdown-menu');
    var dropdown_scope = dropdown_menu.attr('aria-controls');
    // Fetch the button text and replace it with the text of the selected dropdown item
    var dropdown_btn_text = dropdown_menu
        .siblings('button')
        .first()
        .find('.dropdown-label')
        .html($(this).text());
    // Fetch the tab id from the href
    var tab_id = $(this).attr('href');
    // Remove the active class from the dropdown items
    $(this)
        .closest('.dropdown-menu')
        .find('.dropdown-item')
        .removeClass('active');
    // Hide the previously opened tab panes within the scope of the dropdown
    $('#' + dropdown_scope + ' .tab-pane').removeClass('show');
    // Show the selected tab pane
    $(tab_id).addClass('show'); //# is added in the a link href
    //$('#' + tab_id).addClass('show');
	$(dropdown_menu).removeClass('show');
});


function showlink(obj){
	console.log('s');
	console.log($(this));
	$(obj).next().addClass('showsubmenu');
	return false;
//	console.log("link clicked");
	
}

$('.dropdown-menu').on('click', function (e) {
   e.stopPropagation();
});