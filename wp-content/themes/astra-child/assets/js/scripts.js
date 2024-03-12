jQuery(document).ready(function(){
    var t = jQuery("body");
    var w = 1;
    jQuery(".city-perspective").addClass("build"), setTimeout(function() {
            jQuery(".building-1, .tree").addClass("build")
        }, 300), setTimeout(function() {
            jQuery(".building-2").addClass("build")
        }, 600), setTimeout(function() {
            jQuery(".building-3").addClass("build")
        }, 900), setTimeout(function() {
            jQuery(".building-4").addClass("build")
        }, 1200), setTimeout(function() {
            jQuery(".building-5").addClass("build")
        }, 1500), setTimeout(function() {
            jQuery(".building-6").addClass("build")
        }, 1800), setTimeout(function() {
            jQuery(".building-8").addClass("build")
        }, 900), setTimeout(function() {
            jQuery(".building-7, .wifi.floater, .envelope.floater").addClass("build")
        }, 2100), setTimeout(function() {
            jQuery(".band-top, .band-bottom").addClass("build")
        }, 3e3), setTimeout(function() {
            jQuery(".band-screen").addClass("build")
        }, 3300), setTimeout(function() {
            ! function e() {
                setTimeout(function() {
                    w < 21 ? jQuery(".block.q-" + w).addClass("build") : jQuery(".block.q-" + (w - 20)).addClass("build"), 4 < w && (backToStep = w - 4, jQuery(".block.q-" + backToStep).removeClass("build")), 24 < ++w && (w = 5), e()
                }, Math.floor(2e3 * Math.random()))
            }()
        }, 1e3);



    jQuery('.wp-block-categories li').each(function(){
        var postCount = jQuery(this).text().replace(/[^0-9]/gi, '');
        jQuery(this).prepend('<span class="cat-post-count">'+postCount+'</span>');
        
    }); 
    var scrollBar = jQuery('.techvertu-comment-holder');
    if(scrollBar.length > 0){
        scrollBar.scrollbar();
    }
    jQuery('.copy-link').click(function(event){
        var copyLink = jQuery(this).attr('href');
        navigator.clipboard.writeText(copyLink);
        alert("Copied the text");
        event.preventDefault();
    });
    jQuery('.top-menu-action').click(function(event){
        jQuery('.menu-top-menu-container').toggleClass('block');
    });
});