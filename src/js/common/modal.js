import {disableBodyScroll, enableBodyScroll, clearAllBodyScrollLocks} from 'body-scroll-lock';

document.addEventListener("DOMContentLoaded", () => {
	"use strict";
	new ModalManager();
});

class ModalManager {
	constructor() {
		this.ACTIVE_CLASS = "modal-wrapper-active";
		this.ATTRIBUTE_OPEN = "data-modal-open";
		this.ATTRIBUTE_ID = "data-modal-id";
		this.ATTRIBUTE_CLOSE = "data-modal-close";
		this.openModals = new Set();

		this.handleDocumentClick = this.handleDocumentClick.bind(this);
		this.handleDocumentKeydown = this.handleDocumentKeydown.bind(this);

		this.init();
	}

	init() {
		document.addEventListener("click", this.handleDocumentClick);
		document.addEventListener("keydown", this.handleDocumentKeydown);
	}

	getModalById(id) {
		if (!id) {
			return null;
		}

		return document.querySelector('.modal-wrapper[' + this.ATTRIBUTE_ID + '="' + id + '"]');
	}

	isModal(element) {
		return Boolean(element) && element.classList && element.classList.contains("modal-wrapper");
	}

	openModal(modal) {
		if (!this.isModal(modal)) {
			return;
		}

		if (this.openModals.has(modal)) {
			return;
		}

		modal.classList.add(this.ACTIVE_CLASS);
		try {
			disableBodyScroll(modal);
		} catch (error) {
		}

		this.openModals.add(modal);
	}

	closeModal(modal) {
		if (!this.isModal(modal)) {
			return;
		}

		const wasOpen = this.openModals.has(modal);
		modal.classList.remove(this.ACTIVE_CLASS);

		if (!wasOpen) {
			return;
		}

		try {
			enableBodyScroll(modal);
		} catch (error) {
		}

		this.openModals.delete(modal);

		if (!this.openModals.size) {
			try {
				clearAllBodyScrollLocks();
			} catch (error) {
			}
		}
	}

	closeTopModal() {
		if (!this.openModals.size) {
			return;
		}

		const modals = Array.from(this.openModals);
		const last = modals[modals.length - 1];

		if (last) {
			this.closeModal(last);
		}
	}

	handleDocumentClick(event) {
		const target = event.target;

		if (!target) {
			return;
		}

		const openTrigger = target.closest('[' + this.ATTRIBUTE_OPEN + ']');

		if (openTrigger) {
			const id = openTrigger.getAttribute(this.ATTRIBUTE_OPEN);
			const modal = this.getModalById(id);

			if (0 < this.openModals.size) {
				this.openModals.forEach((openModal) => {
					this.closeModal(openModal);
				});
			}

			if (modal) {
				this.openModal(modal);
				this.cloneAttributes(modal, event.target);
			}

			event.preventDefault();
			return;
		}

		const closeTrigger = target.closest('[' + this.ATTRIBUTE_CLOSE + ']');

		if (closeTrigger) {
			const modal = closeTrigger.closest(".modal-wrapper");

			if (modal) {
				this.closeModal(modal);
			}

			event.preventDefault();
			return;
		}

		const backdrop = target.closest('.modal-wrapper');
		const isModal = target.closest('.modal');

		if (backdrop && backdrop === target && !isModal) this.closeModal(backdrop);
	}

	handleDocumentKeydown(event) {
		if (event.key !== "Escape" && event.key !== "Esc") {
			return;
		}

		this.closeTopModal();
	}

	cloneAttributes(modal, target) {
		if (!modal || !target) {
			return;
		}

		const attributes = target.attributes;
		if (!attributes) {
			return;
		}

		for (let i = 0; i < attributes.length; i++) {
			const attribute = attributes[i];
			if (attribute.name.startsWith("data-") && attribute.name !== this.ATTRIBUTE_OPEN) {
				modal.setAttribute(attribute.name, attribute.value);
			}
		}
	}
}
