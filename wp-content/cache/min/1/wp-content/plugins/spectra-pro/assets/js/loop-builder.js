function getQueryIdFromUrl(){const urlParams=new URLSearchParams(window.location.search);return urlParams.get('query-id')}
function scrollToQueryId(queryId){const targetElement=document.getElementById(queryId);if(targetElement){window.scrollTo({top:targetElement.offsetTop,behavior:'smooth'})}}
const queryId=getQueryIdFromUrl();scrollToQueryId(queryId)