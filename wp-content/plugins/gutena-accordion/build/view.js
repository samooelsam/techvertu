document.addEventListener("DOMContentLoaded",(()=>{document.querySelectorAll(".gutena-accordion-block")?.forEach(((t,e)=>{const c=t?.querySelectorAll(":scope > .gutena-accordion-block__panel > .gutena-accordion-block__panel-title");c?.forEach(((t,e)=>{t?.addEventListener("click",(()=>{const n=t.closest(".gutena-accordion-block");t.closest(".gutena-accordion-block__panel")?.classList?.toggle("active");const l=t?.nextElementSibling;l.style.maxHeight?(l.style.maxHeight=l.scrollHeight+"px",setTimeout((function(){l.style.maxHeight=null}),50)):(l.style.maxHeight=l.scrollHeight+"px",setTimeout((function(){l.style.maxHeight="unset"}),200)),"true"===n?.getAttribute("data-single")&&c?.forEach(((t,c)=>{if(c!==e){const e=t.closest(".gutena-accordion-block__panel");if(e?.classList?.contains("active")){e?.classList?.remove("active");const c=t?.nextElementSibling;c.style.maxHeight?c.style.maxHeight=null:c.style.maxHeight=c.scrollHeight+"px"}}}))}))}));const n=t?.getAttribute("data-open");"none"!==n&&c?.[parseInt(n)]?.click()}))}));