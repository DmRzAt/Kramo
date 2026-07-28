(() => {
	"use strict";

	const shake = (row) => {
		if (!row || row.classList.contains("kramo-shake")) {
			return;
		}

		row.classList.add("kramo-shake");
		row.addEventListener(
			"animationend",
			() => row.classList.remove("kramo-shake"),
			{ once: true }
		);
	};

	const shakeInvalidRows = (root) => {
		root.querySelectorAll(".form-row.woocommerce-invalid").forEach(shake);
	};

	const form = document.querySelector("form.checkout, form.woocommerce-checkout");

	if (!form) {
		return;
	}

	form.addEventListener(
		"invalid",
		(event) => {
			const row = event.target.closest(".form-row");

			if (row) {
				row.classList.add("woocommerce-invalid");
				shake(row);
			}
		},
		true
	);

	if (window.jQuery) {
		window.jQuery(document.body).on("checkout_error", () => {
			shakeInvalidRows(form);
			form.querySelector(".form-row.woocommerce-invalid input, .form-row.woocommerce-invalid select")?.focus();
		});
	}

	new MutationObserver((records) => {
		records.forEach((record) => {
			const target = record.target;

			if (
				target instanceof HTMLElement
				&& target.classList.contains("form-row")
				&& target.classList.contains("woocommerce-invalid")
			) {
				shake(target);
			}
		});
	}).observe(form, {
		attributeFilter: ["class"],
		attributes: true,
		subtree: true,
	});
})();
