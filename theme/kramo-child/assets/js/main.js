(() => {
	"use strict";

	document.documentElement.classList.add("js");

	const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	const supportsObserver = "IntersectionObserver" in window;
	const revealSelector = [
		".kramo-hero",
		".woocommerce ul.products li.product",
		".kramo-home-tiles__tile",
		".kramo-trust",
		".wp-block-heading",
		".kramo-faq__item",
		".kramo-recent__card",
	].join(", ");
	const fragmentSelector = "li.product, .kramo-recent__card";

	let observer = null;

	const getObserver = () => {
		if (observer) {
			return observer;
		}

		observer = new IntersectionObserver((entries) => {
			entries.forEach((entry, index) => {
				if (!entry.isIntersecting) {
					return;
				}

				window.setTimeout(() => {
					entry.target.classList.add("is-visible");
				}, index * 60);
				observer.unobserve(entry.target);
			});
		}, { rootMargin: "0px 0px -8% 0px" });

		return observer;
	};

	const reveal = (elements) => {
		if (reduced || !supportsObserver) {
			return;
		}

		Array.from(elements).forEach((element) => {
			if (element.classList.contains("kramo-reveal")) {
				return;
			}

			element.classList.add("kramo-reveal");
			getObserver().observe(element);
		});
	};

	const fadeInImages = (root) => {
		const images = root.querySelectorAll(
			".kramo-product-media .kramo-product-image--primary:not([fetchpriority])"
		);

		Array.from(images).forEach((image) => {
			if (image.complete || image.classList.contains("is-pending")) {
				return;
			}

			image.classList.add("is-pending");

			const show = () => image.classList.add("is-decoded");

			if (typeof image.decode === "function") {
				image.decode().then(show, show);
				return;
			}

			image.addEventListener("load", show, { once: true });
			image.addEventListener("error", show, { once: true });
		});
	};

	const productIdFrom = (element) => {
		const match = /(?:^|\s)post-(\d+)(?:\s|$)/.exec(element.className);

		return match ? match[1] : "";
	};

	const nameSingleProduct = () => {
		const product = document.querySelector("div.product[class*='post-']");
		const image = product?.querySelector(".woocommerce-product-gallery__image");
		const id = product ? productIdFrom(product) : "";

		if (image && id) {
			image.style.setProperty("--kramo-view-name", `kramo-product-${id}`);
		}
	};

	const nameClickedCard = (event) => {
		if (reduced || !("startViewTransition" in document)) {
			return;
		}

		const link = event.target.closest("li.product a");
		const card = link?.closest("li.product");
		const media = card?.querySelector(".kramo-product-media");
		const id = card ? productIdFrom(card) : "";

		if (!media || !id || link.classList.contains("add_to_cart_button")) {
			return;
		}

		document.querySelectorAll(".kramo-product-media").forEach((node) => {
			node.style.removeProperty("--kramo-view-name");
		});
		media.style.setProperty("--kramo-view-name", `kramo-product-${id}`);
	};

	const header = document.querySelector(".site-header");
	if (header) {
		const onScroll = () => {
			header.classList.toggle("is-stuck", window.scrollY > 24);
		};

		onScroll();
		window.addEventListener("scroll", onScroll, { passive: true });
	}

	document.addEventListener("click", nameClickedCard, true);

	document.addEventListener("kramo:catalog-updated", (event) => {
		const root = event.detail && event.detail.container
			? event.detail.container
			: document;

		reveal(root.querySelectorAll(fragmentSelector));
		fadeInImages(root);
	});

	const start = () => {
		reveal(document.querySelectorAll(revealSelector));
		fadeInImages(document);
		if (!reduced) {
			nameSingleProduct();
		}
	};

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", start);
	} else {
		start();
	}

	window.kramo = window.kramo || {};
	window.kramo.reveal = reveal;
	window.kramo.fadeInImages = fadeInImages;
	window.kramo.reducedMotion = reduced;
})();
