(() => {
	"use strict";

	document.documentElement.classList.add("js");
})();

(function () {
	"use strict";

	var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	var targets = document.querySelectorAll(
		".kramo-hero, .woocommerce ul.products li.product, .kramo-trust, .wp-block-heading, .kramo-faq__item"
	);

	if (!reduced && "IntersectionObserver" in window) {
		targets.forEach(function (el) {
			el.classList.add("kramo-reveal");
		});

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry, index) {
				if (!entry.isIntersecting) {
					return;
				}
				window.setTimeout(function () {
					entry.target.classList.add("is-visible");
				}, index * 60);
				observer.unobserve(entry.target);
			});
		}, { rootMargin: "0px 0px -8% 0px" });

		targets.forEach(function (el) {
			observer.observe(el);
		});
	}

	var header = document.querySelector(".site-header");
	if (header) {
		var onScroll = function () {
			header.classList.toggle("is-stuck", window.scrollY > 24);
		};
		onScroll();
		window.addEventListener("scroll", onScroll, { passive: true });
	}
})();
