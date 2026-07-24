(() => {
	'use strict';

	const fieldset = document.querySelector('.woostarter-personalization');
	if (!fieldset) {
		return;
	}

	const input = fieldset.querySelector('#ws-personalization-text');
	const counter = fieldset.querySelector('#ws-personalization-counter');
	if (!input || !counter) {
		return;
	}

	const maxLength = Number.parseInt(fieldset.dataset.maxLength, 10);
	const choice = fieldset.querySelector('#ws-personalization-choice');
	const textIsRequired = input.required;

	const updateCounter = () => {
		const length = Array.from(input.value).length;
		counter.textContent = `${length} / ${maxLength}`;

		if (choice) {
			choice.required = textIsRequired || input.value.trim() !== '';
		}
	};

	input.addEventListener('input', updateCounter);
	updateCounter();
})();
