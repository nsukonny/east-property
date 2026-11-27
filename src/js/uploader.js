import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import Sortable from 'sortablejs';

const {__} = window.wp.i18n;

document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	initUploader();
});

const initUploader = () => {
	const pondInput = document.querySelector('.filepond');
	if (!pondInput) return;

	const form = pondInput.closest('form');
	const uGrid = document.getElementById('user-grid');
	const pGrid = document.getElementById('presets-grid');
	const dropzone = document.getElementById('uploader-dropzone');
	const uHeader = document.getElementById('user-header');

	if (!uGrid || !pGrid) return;

	const getPresetsGrid = () => document.getElementById('presets-grid') || pGrid;

	// Ids are mixed on purpose: images already attached to the unit carry a numeric
	// attachment id, freshly uploaded ones carry FilePond's alphanumeric id, and
	// dataset values are always strings. Number() turned every FilePond id into NaN,
	// and NaN never equals NaN, so lookups silently found nothing.
	const sameId = (a, b) => String(a) === String(b);

	FilePond.registerPlugin(FilePondPluginImagePreview);

	let userFiles = [];
	if (defaultUserFiles) {
		userFiles = defaultUserFiles;
	}
	let presets = Array.from(pGrid.querySelectorAll('.uploader-item')).map(el => ({
		id: el.dataset.id,
		url: el.dataset.url,
		isSelected: el.classList.contains('is-selected'),
		canDelete: false
	}));

	let coverId = typeof defaultCoverId !== 'undefined' && defaultCoverId ? String(defaultCoverId) : null;

	/**
	 * Ids that may serve as a cover: uploaded pictures and selected presets.
	 */
	const coverPool = () => [
		...userFiles.map(f => f.id),
		...presets.filter(f => f.isSelected).map(f => f.id)
	];

	/**
	 * Keep the cover on a picture that is still in the gallery.
	 */
	const ensureCover = () => {
		const pool = coverPool();

		if (!pool.length) {
			coverId = null;

			return;
		}

		if (!pool.some(id => sameId(id, coverId))) coverId = pool[0];
	};

	const syncPresetsFromDom = () => {
		presets = Array.from(getPresetsGrid().querySelectorAll('.uploader-item'))
			.map((el) => {
				const id = el.dataset.id;
				const url = el.dataset.url;
				if (!id || !url) return null;

				return {
					id,
					url,
					isSelected: el.classList.contains('is-selected'),
					canDelete: false
				};
			})
			.filter(Boolean);
	};

	const syncToHidden = () => {
		if (!form) return;
		let hidden = form.querySelector('input[name="final_image_selection"]');
		if (!hidden) {
			hidden = document.createElement('input');
			hidden.type = 'hidden';
			hidden.name = 'final_image_selection';
			form.appendChild(hidden);
		}

		ensureCover();

		const coverFile = userFiles.find(f => sameId(f.id, coverId));
		const coverOut = coverFile ? (coverFile.attachmentId ?? coverFile.id) : coverId;
		const order = [
			...userFiles.map(f => f.attachmentId ?? f.id),
			...presets.filter(f => f.isSelected).map(f => f.id)
		];

		const payload = {
			user: userFiles.map(f => f.attachmentId ?? f.id),
			presets: presets.filter(f => f.isSelected).map(f => f.id),
			fullOrder: order.some(id => sameId(id, coverOut))
				? [coverOut, ...order.filter(id => !sameId(id, coverOut))]
				: order
		};

		hidden.value = JSON.stringify(payload);
		hidden.dispatchEvent(new Event('change', {bubbles: true}));
	};

	const createItemEl = (file) => {
		const isCover = sameId(file.id, coverId);
		const el = document.createElement('div');
		el.className = `uploader-item ${file.canDelete ? 'is-uploaded' : (file.isSelected ? 'is-selected' : '')}${isCover ? ' is-cover' : ''}`;
		el.dataset.id = file.id;
		el.dataset.url = file.url;

		const img = document.createElement('img');
		img.src = file.url;
		img.alt = 'Unit image';
		el.append(img);

		if (file.canDelete || file.isSelected) {
			const coverBtn = document.createElement('button');
			coverBtn.className = 'uploader-item-cover';
			coverBtn.type = 'button';
			coverBtn.title = isCover ? __('Cover', 'east-property') : __('Make cover', 'east-property');
			coverBtn.setAttribute('aria-label', coverBtn.title);
			coverBtn.setAttribute('aria-pressed', isCover ? 'true' : 'false');
			coverBtn.addEventListener('click', (e) => {
				e.stopPropagation();
				coverId = String(file.id);
				render();
			});
			el.append(coverBtn);

			if (isCover) {
				const coverLabel = document.createElement('span');
				coverLabel.className = 'uploader-item-cover-label';
				coverLabel.textContent = __('Cover', 'east-property');
				el.append(coverLabel);
			}
		}

		if (file.canDelete) {
			const delBtn = document.createElement('button');
			delBtn.className = 'uploader-item-delete';
			delBtn.type = 'button';
			delBtn.setAttribute('aria-label', 'Remove image');
			delBtn.addEventListener('click', (e) => {
				e.stopPropagation();
				pond.removeFile(file.id);
				if (file.isOldImage) {
					let item = document.querySelector('.uploader-item[data-id="' + file.id + '"]'),
						userThumbnailIds = document.getElementById('user-thumbnail-ids');
					item?.remove();
					userThumbnailIds.value = userThumbnailIds.value.replace(file.id, '');
					userFiles = userFiles.filter(f => f.id !== file.id);
					render();
				}
			});
			el.append(delBtn);
		}
		return el;
	};

	const render = () => {
		ensureCover();

		uGrid.innerHTML = '';
		userFiles.forEach(f => uGrid.append(createItemEl(f)));

		const hasUserFiles = userFiles.length > 0;
		if (uHeader) uHeader.classList.toggle('is-visible', hasUserFiles);
		if (dropzone) dropzone.classList.toggle('is-hidden', hasUserFiles);

		pGrid.innerHTML = '';
		presets.forEach(f => pGrid.append(createItemEl(f)));

		syncToHidden();
	};

	const pond = FilePond.create(pondInput, {
		allowMultiple: true,
		maxFiles: 50,
		labelIdle: 'Drag and drop images here or <span>Browse files</span>',
		instantUpload: true,
		storeAsFile: true,
	});

	if (ajax_object) {
		let userThumbnailIds = document.getElementById('user-thumbnail-ids');

		pond.setOptions({
			server: {
				process: {
					url: ajax_object.ajax_url,
					method: 'POST',
					withCredentials: false,
					ondata: (formData) => {
						formData.append('action', 'upload_user_images');
						return formData;
					},
					onload: (response) => {
						const data = JSON.parse(response);

						if (data.success && data.data && data.data.attachment_id) {
							userThumbnailIds.value += data.data.attachment_id + ',';

							userFiles.find(f => f.attachmentId === null).attachmentId = data.data.attachment_id;

							return String(data.data.attachment_id);
						}

						throw new Error('Upload failed');
					},
					onerror: (response) => {
						try {
							const data = JSON.parse(response);
							return data?.data?.message || 'Upload error';
						} catch (e) {
							return 'Upload error';
						}
					}
				},
			},
		});

		pond.on('addfilestart', () => {
			uGrid.classList.add('preloader');
		});

		pond.on('processfile', (error, file) => {
			uGrid.classList.remove('preloader');

			if (!error) render();
		});

		pond.on('processfileabort', () => {
			uGrid.classList.remove('preloader');
		});
	}

	pond.on('addfile', (err, file) => {
		if (err || userFiles.some(f => sameId(f.id, file.id))) return;
		userFiles.push({
			id: file.id,
			url: URL.createObjectURL(file.file),
			isSelected: true,
			canDelete: true,
			attachmentId: null,
		});
		render();
	});

	pond.on('removefile', (err, file) => {
		const idx = userFiles.findIndex(f => sameId(f.id, file.id));
		if (idx > -1) {
			uGrid.classList.add('preloader');
			if (ajax_object && userFiles[idx].attachmentId) {
				let formData = new FormData(),
					userThumbnailIds = document.getElementById('user-thumbnail-ids');

				formData.append('action', 'remove_user_images');
				formData.append('_ajax_nonce', ajax_object._ajax_nonce);
				formData.append('attachment_id', userFiles[idx].attachmentId);

				fetch(ajax_object.ajax_url, {
					method: 'POST',
					body: formData,
					headers: {
						'Accept': 'application/json'
					}
				})
					.then(response => response.json())
					.then(response => {
						if (response.success) {
							userThumbnailIds.value = userThumbnailIds.value.replace(userFiles[idx].attachmentId + ',', '');

							URL.revokeObjectURL(userFiles[idx].url);
							userFiles.splice(idx, 1);
							render();
						}
						setTimeout(() => {
							uGrid.classList.remove('preloader');
						}, 700);
					})
					.catch(error => {
						console.log(error);
						setTimeout(() => {
							uGrid.classList.remove('preloader');
						}, 700);
					});
			} else {
				URL.revokeObjectURL(userFiles[idx].url);
				userFiles.splice(idx, 1);
				render();
			}
		}
	});

	document.addEventListener('click', (e) => {
		const browseBtn = e.target.closest('#trigger-browse');
		if (browseBtn) {
			pond.browse();

			return;
		}

		const selectAllBtn = e.target.closest('#select-all-images');
		if (selectAllBtn) {
			syncPresetsFromDom();

			const areAllSelected = presets.every(f => f.isSelected);
			presets.forEach(f => f.isSelected = !areAllSelected);

			render();

			return;
		}

		const item = e.target.closest('.uploader-item');
		if (!item) return;

		const currentPGrid = getPresetsGrid();
		if (!currentPGrid.contains(item)) return;

		syncPresetsFromDom();

		const preset = presets.find((f) => sameId(f.id, item.dataset.id));

		if (!preset) return;

		preset.isSelected = !preset.isSelected;
		render();
	});

	[uGrid, pGrid].forEach(grid => {
		Sortable.create(grid, {
			animation: 150,
			ghostClass: 'sortable-ghost',
			onEnd: () => {
				const newOrderIds = Array.from(grid.querySelectorAll('.uploader-item')).map(el => el.dataset.id);
				if (grid === uGrid) {
					userFiles = newOrderIds.map(id => userFiles.find(f => sameId(f.id, id))).filter(Boolean);
				} else {
					presets = newOrderIds.map(id => presets.find(f => sameId(f.id, id))).filter(Boolean);
				}
				syncToHidden();
			}
		});
	});

	document.addEventListener('ajaxComplete', () => {
		syncPresetsFromDom();
		render();
	});

	render();
};