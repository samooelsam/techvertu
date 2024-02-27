    // Function to extract the query ID from the URL
    function getQueryIdFromUrl() {
        const urlParams = new URLSearchParams( window.location.search );
        return urlParams.get( 'query-id' );
    }

    // Function to scroll to the div element with the query ID
    function scrollToQueryId( queryId ) {
        const targetElement = document.getElementById( queryId );
        if ( targetElement ) {
            window.scrollTo( {
            top: targetElement.offsetTop,
            behavior: 'smooth'
            } );
        }
    }

    // Get the query ID from the URL
    const queryId = getQueryIdFromUrl();

    // Scroll to the div element with the query ID
    scrollToQueryId( queryId );