(() => {
	"use strict";

	const button = document.querySelector(".kramo-select-video");
	const input = document.querySelector("#_kramo_product_video");
	if (!button || !input || typeof wp === "undefined" || !wp.media) {
		return;
	}

	button.addEventListener("click", () => {
		const frame = wp.media({
			title: kramoVideoAdmin.title,
			button: { text: kramoVideoAdmin.button },
			library: { type: "video/mp4" },
			multiple: false,
		});

		frame.on("select", () => {
			const attachment = frame.state().get("selection").first().toJSON();
			input.value = attachment.url;
			input.dispatchEvent(new Event("change", { bubbles: true }));
		});

		frame.open();
	});
})();
