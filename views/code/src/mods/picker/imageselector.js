class ImageSelector {
    static instances = [];

    constructor(element, config = {}) {
        this.element = typeof element === 'string'
            ? document.querySelector(element)
            : element;

        if (!this.element) {
            throw new Error('Element not found');
        }

        this.config = {
            selection: config.selection || 'single',
            title: config.title || 'Select Image',
            accept: config.accept || 'image/*',
            maxSize: config.maxSize || 10 * 1024 * 1024,
            onSelect: config.onSelect || null,
            ...config
        };

        this._callbacks = [];
        this._initialized = false;
        this._value = this.element.value || '';
        this._isOpen = false;
        this._selectedFiles = [];

        this._originalName = this.element.getAttribute('name') || '';

        this._hiddenFileInput = null;

        this._overlay = null;
        this._modal = null;
        this._grid = null;
        this._searchInput = null;
        this._selectBtn = null;
        this._cancelBtn = null;
        this._addBtn = null;
        this._infoText = null;
        this._fileInput = null;
        this._previewOverlay = null;
        this._currentImageContainer = null;
        this._clickHandler = null;
        this._uploadArea = null;
        this._imageContainer = null;

        ImageSelector.instances.push(this);
    }

    /**
     * Initialize the image selector
     */
    init() {
        if (this._initialized) return this;

        this._ensureStyle();

        this._originalName = this.element.getAttribute('name') || '';

        this.element.removeAttribute('name');
        this.element.setAttribute("readonly", "");
        this.element.style.cursor = "pointer";

        this._createHiddenFileInput();

        this._clickHandler = (e) => {
            e.preventDefault();
            this.open();
        };

        this.element.addEventListener("click", this._clickHandler);
        this._initialized = true;
        return this;
    }

    /**
     * Create hidden file input that will submit the actual files
     * @private
     */
    _createHiddenFileInput() {
        if (this._hiddenFileInput) return;

        this._hiddenFileInput = document.createElement("input");
        this._hiddenFileInput.type = "file";
        this._hiddenFileInput.style.display = "none";
        this._hiddenFileInput.multiple = this.config.selection === "multiple";
        this._hiddenFileInput.accept = this.config.accept;

        this._hiddenFileInput.name = this._originalName;

        this.element.parentNode.insertBefore(this._hiddenFileInput, this.element.nextSibling);
    }

    /**
     * Ensure CSS styles are loaded
     * @private
     */
    _ensureStyle() {
        const styleId = "imageselector-style";
        if (document.getElementById(styleId)) return;

        const style = document.createElement("style");
        style.id = styleId;
        style.textContent = `
            .imageselector-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 9999999;
                opacity: 0;
                transition: opacity 0.3s ease;
                padding: 20px;
                max-height: 100%;
                overflow-y: scroll;
            }
            .imageselector-overlay.imageselector-show {
                display: flex;
                opacity: 1;
            }
            .imageselector-modal {
                width: 95%;
                max-width: 800px;
                max-height: 90vh;
                background: #ffffff;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 30px 80px rgba(0,0,0,0.2);
                display: flex;
                flex-direction: column;
                transform: scale(0.95) translateY(20px);
                opacity: 0;
                transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            }
            .imageselector-overlay.imageselector-show .imageselector-modal {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
            .imageselector-header {
                padding: 20px 28px;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-shrink: 0;
                flex-wrap: wrap;
                gap: 12px;
            }
            .imageselector-header-left {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .imageselector-header h2 {
                margin: 0;
                font-size: 20px;
                font-weight: 600;
                color: #212529;
                letter-spacing: 0.3px;
            }
            .imageselector-header-actions {
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
            }
            .imageselector-btn-add {
                padding: 8px 20px;
                border: none;
                border-radius: 8px;
                background: #28a745;
                color: #fff;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .imageselector-btn-add:hover {
                background: #218838;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(40,167,69,0.3);
            }
            .imageselector-close {
                border: none;
                background: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                font-size: 35px;
                cursor: pointer;
                color: #495057;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.3s ease;
            }
            .imageselector-close:hover {
                color: #212529;
                transform: rotate(90deg);
            }
            .imageselector-body {
                padding: 20px 24px;
                overflow-y: auto;
                flex: 1;
                background: #ffffff;
                max-height: 60vh;
            }
            .imageselector-body::-webkit-scrollbar {
                width: 6px;
            }
            .imageselector-body::-webkit-scrollbar-track {
                background: #f8f9fa;
            }
            .imageselector-body::-webkit-scrollbar-thumb {
                background: #dee2e6;
                border-radius: 10px;
            }
            .imageselector-upload-area {
                padding: 30px;
                background: #f8f9fa;
                border-radius: 12px;
                border: 2px dashed #dee2e6;
                margin-bottom: 20px;
                text-align: center;
                transition: all 0.3s ease;
            }
            .imageselector-upload-area.dragover {
                border-color: #0066ff;
                background: #f0f7ff;
            }
            .imageselector-upload-area input[type="file"] {
                display: none;
            }
            .imageselector-upload-label {
                display: inline-block;
                padding: 12px 32px;
                background: #0066ff;
                color: #fff;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            .imageselector-upload-label:hover {
                background: #0052cc;
                transform: translateY(-2px);
            }
            .imageselector-upload-text {
                color: #6c757d;
                margin: 12px 0;
                font-size: 14px;
            }
            .imageselector-current-image {
                display: none;
                margin-bottom: 20px;
                padding: 16px;
                background: #f8f9fa;
                border-radius: 12px;
                border: 2px solid #e9ecef;
            }
            .imageselector-current-image.imageselector-show {
                display: block;
            }
            .imageselector-current-image-wrapper {
                display: flex;
                gap: 12px;
                overflow-x: auto;
                padding: 8px 4px;
                scroll-behavior: smooth;
                max-width: 100%;
            }
            .imageselector-current-image-wrapper::-webkit-scrollbar {
                height: 6px;
            }
            .imageselector-current-image-wrapper::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }
            .imageselector-current-image-wrapper::-webkit-scrollbar-thumb {
                background: #dee2e6;
                border-radius: 10px;
            }
            .imageselector-current-image-item {
                position: relative;
                flex: 0 0 auto;
                width: 120px;
                height: 120px;
                border-radius: 8px;
                overflow: hidden;
                border: 2px solid #e9ecef;
                background: #ffffff;
                transition: all 0.2s ease;
            }
            .imageselector-current-image-item:hover {
                border-color: #0066ff;
                transform: scale(1.02);
            }
            .imageselector-current-image-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .imageselector-current-image-item .imageselector-current-image-actions {
                position: absolute;
                top: 4px;
                left: 4px;
                display: flex;
                gap: 4px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            .imageselector-current-image-item:hover .imageselector-current-image-actions {
                opacity: 1;
            }
            .imageselector-current-image-eye {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: rgba(0,0,0,0.6);
                border: none;
                color: #fff;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                transition: all 0.2s ease;
                backdrop-filter: blur(4px);
            }
            .imageselector-current-image-eye:hover {
                background: rgba(0,0,0,0.8);
                transform: scale(1.1);
            }
            .imageselector-current-image-label {
                font-size: 13px;
                color: #6c757d;
                font-weight: 500;
                margin-bottom: 8px;
            }
            .imageselector-current-image-name {
                font-size: 13px;
                color: #212529;
                font-weight: 500;
                margin-top: 8px;
                word-break: break-all;
            }
            .imageselector-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 16px;
            }
            .imageselector-item {
                background: #ffffff;
                border-radius: 10px;
                overflow: hidden;
                cursor: pointer;
                transition: all 0.25s ease;
                border: 2px solid #e9ecef;
                position: relative;
            }
            .imageselector-item:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                border-color: #dee2e6;
            }
            .imageselector-item.imageselector-selected {
                border-color: #0066ff;
                box-shadow: 0 0 0 3px rgba(0,102,255,0.2);
            }
            .imageselector-item img {
                width: 100%;
                height: 150px;
                object-fit: cover;
                display: block;
                background: #f8f9fa;
            }
            .imageselector-item-info {
                padding: 12px 14px;
                background: #ffffff;
            }
            .imageselector-item-name {
                font-size: 13px;
                color: #212529;
                font-weight: 500;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-bottom: 4px;
            }
            .imageselector-item-size {
                font-size: 11px;
                color: #6c757d;
            }
            .imageselector-item-check {
                position: absolute;
                bottom: 8px;
                right: 8px;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: #0066ff;
                color: #fff;
                display: none;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                font-weight: bold;
            }
            .imageselector-item.imageselector-selected .imageselector-item-check {
                display: flex;
            }
            .imageselector-item-actions {
                position: absolute;
                top: 8px;
                right: 8px;
                display: flex;
                gap: 6px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            .imageselector-item:hover .imageselector-item-actions {
                opacity: 1;
            }
            .imageselector-item-eye {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background: rgba(0,0,0,0.6);
                border: none;
                color: #fff;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                transition: all 0.2s ease;
                backdrop-filter: blur(4px);
            }
            .imageselector-item-eye:hover {
                background: rgba(0,0,0,0.8);
                transform: scale(1.1);
            }
            .imageselector-item-delete {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background: rgba(220, 53, 69, 0.85);
                border: none;
                color: #fff;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                transition: all 0.2s ease;
                backdrop-filter: blur(4px);
            }
            .imageselector-item-delete:hover {
                background: rgba(200, 35, 51, 0.95);
                transform: scale(1.1);
            }
            .imageselector-empty {
                grid-column: 1 / -1;
                text-align: center;
                padding: 60px 20px;
                color: #adb5bd;
                font-size: 16px;
            }
            .imageselector-footer {
                padding: 16px 28px;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-shrink: 0;
                flex-wrap: wrap;
                gap: 12px;
            }
            .imageselector-footer-info {
                color: #6c757d;
                font-size: 14px;
            }
            .imageselector-footer-info span {
                color: #212529;
                font-weight: 600;
            }
            .imageselector-footer-actions {
                display: flex;
                gap: 10px;
            }
            .imageselector-btn {
                padding: 10px 24px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: all 0.2s ease;
                font-family: inherit;
            }
            .imageselector-btn-cancel {
                background: #e9ecef;
                color: #495057;
            }
            .imageselector-btn-cancel:hover {
                background: #dee2e6;
                color: #212529;
            }
            .imageselector-btn-select {
                background: #0066ff;
                color: #fff;
            }
            .imageselector-btn-select:hover {
                background: #0052cc;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,102,255,0.3);
            }
            .imageselector-preview-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.9);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 99999999;
                padding: 20px;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            .imageselector-preview-overlay.imageselector-show {
                display: flex;
                opacity: 1;
            }
            .imageselector-preview-overlay img {
                max-width: 95%;
                max-height: 95%;
                object-fit: contain;
                border-radius: 4px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
                transform: scale(0.95);
                transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .imageselector-preview-overlay.imageselector-show img {
                transform: scale(1);
            }
            .imageselector-preview-close {
                position: absolute;
                top: 20px;
                right: 30px;
                font-size: 40px;
                color: #fff;
                cursor: pointer;
                background: none;
                border: none;
                padding: 10px;
                line-height: 1;
            }
            .imageselector-preview-close:hover {
                color: #ccc;
            }
            @media (max-width:768px) {
                .imageselector-modal {
                    width: 100%;
                    max-height: 100vh;
                    border-radius: 0;
                }
                .imageselector-header {
                    flex-direction: column;
                    align-items: stretch;
                    padding: 16px;
                }
                .imageselector-header-left {
                    flex-direction: row;
                    align-items: stretch;
                }
                .imageselector-header-actions {
                    flex-direction: column;
                }
                .imageselector-body {
                    padding: 12px;
                    max-height: 420px;
                }
                .imageselector-grid {
                    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                    gap: 10px;
                }
                .imageselector-item img {
                    height: 100px;
                }
                .imageselector-footer {
                    flex-direction: column;
                    padding: 16px;
                }
                .imageselector-footer-actions {
                    width: 100%;
                }
                .imageselector-footer-actions button {
                    flex: 1;
                }
                .imageselector-current-image-item {
                    width: 80px;
                    height: 80px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Open the image selector
     */
    open() {
        if (this._isOpen) return;
        if (!this._initialized) this.init();

        if (!this._overlay) {
            this._buildOverlay();
        }

        this._displayCurrentImage();
        this._overlay.classList.add("imageselector-show");
        this._isOpen = true;
        document.body.style.overflow = "hidden";
    }

    /**
     * Close the image selector
     */
    close() {
        if (!this._isOpen) return;
        this._overlay.classList.remove("imageselector-show");
        this._isOpen = false;
        document.body.style.overflow = "";
        this._closePreview();
    }

    /**
     * Get the current value (file names for display)
     */
    getValue() {
        return this._value || this.element.value || '';
    }

    /**
     * Get the hidden file input (for form submission)
     */
    getFileInput() {
        return this._hiddenFileInput;
    }

    /**
     * Get selected files (File objects)
     */
    getFiles() {
        return this._selectedFiles;
    }

    /**
     * Get file names as array
     */
    getFileNames() {
        const value = this.getValue();
        return value ? value.split('||').filter(p => p.trim()) : [];
    }

    /**
     * Set value (file name(s))
     */
    setValue(value) {
        if (Array.isArray(value)) {
            this._value = value.join('||');
        } else {
            this._value = value || '';
        }
        this.element.value = this._value;
        this.element.dispatchEvent(new Event('change', { bubbles: true }));
        this._triggerCallbacks();

        if (this._isOpen) {
            this._displayCurrentImage();
        }
    }

    /**
     * Clear the value
     */
    clear() {
        this.setValue('');
        this._selectedFiles.forEach(f => {
            if (f._url) URL.revokeObjectURL(f._url);
        });
        this._selectedFiles = [];
        if (this._fileInput) {
            this._fileInput.value = '';
        }
        if (this._hiddenFileInput) {
            this._hiddenFileInput.value = '';
        }
        if (this._grid) {
            this._renderGrid();
        }
        this._updateSelectionInfo();
    }

    /**
     * Check if empty
     */
    isEmpty() {
        const value = this.getValue();
        return !value || value.trim() === '';
    }

    /**
     * Get count of selected images
     */
    count() {
        return this.getFileNames().length;
    }

    /**
     * Register change callback
     */
    onChange(callback) {
        if (typeof callback !== 'function') {
            throw new Error('Callback must be a function');
        }
        this._callbacks.push(callback);
        return () => {
            const index = this._callbacks.indexOf(callback);
            if (index !== -1) {
                this._callbacks.splice(index, 1);
            }
        };
    }

    /**
     * Register select callback
     */
    onSelect(callback) {
        this.config.onSelect = callback;
        return this;
    }

    /**
     * Destroy instance
     */
    destroy() {
        if (this._clickHandler) {
            this.element.removeEventListener("click", this._clickHandler);
        }
        if (this._overlay && this._overlay.parentNode) {
            this._overlay.parentNode.removeChild(this._overlay);
        }
        if (this._previewOverlay && this._previewOverlay.parentNode) {
            this._previewOverlay.parentNode.removeChild(this._previewOverlay);
        }
        if (this._hiddenFileInput && this._hiddenFileInput.parentNode) {
            this._hiddenFileInput.parentNode.removeChild(this._hiddenFileInput);
        }
        this._selectedFiles.forEach(f => {
            if (f._url) URL.revokeObjectURL(f._url);
        });
        this._callbacks = [];
        this._initialized = false;

        const index = ImageSelector.instances.indexOf(this);
        if (index !== -1) {
            ImageSelector.instances.splice(index, 1);
        }
    }

    /**
     * Build overlay
     * @private
     */
    _buildOverlay() {
        const overlay = document.createElement("div");
        overlay.className = "imageselector-overlay";

        const modal = document.createElement("div");
        modal.className = "imageselector-modal";

        const header = document.createElement("div");
        header.className = "imageselector-header";

        const headerLeft = document.createElement("div");
        headerLeft.className = "imageselector-header-left";

        const title = document.createElement("h2");
        title.textContent = this.config.title;

        const addBtn = document.createElement("button");
        addBtn.className = "imageselector-btn-add";
        addBtn.innerHTML = "📁 Select Files";
        addBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            this._fileInput.click();
        });

        headerLeft.appendChild(title);
        headerLeft.appendChild(addBtn);

        const headerActions = document.createElement("div");
        headerActions.className = "imageselector-header-actions";

        const closeBtn = document.createElement("button");
        closeBtn.className = "imageselector-close";
        closeBtn.innerHTML = "×";
        closeBtn.addEventListener("click", () => this.close());

        headerActions.appendChild(closeBtn);
        header.appendChild(headerLeft);
        header.appendChild(headerActions);

        const body = document.createElement("div");
        body.className = "imageselector-body";

        const uploadArea = document.createElement("div");
        uploadArea.className = "imageselector-upload-area";

        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.multiple = this.config.selection === "multiple";
        fileInput.accept = this.config.accept;

        const uploadLabel = document.createElement("label");
        uploadLabel.className = "imageselector-upload-label";
        uploadLabel.textContent = "Choose Images";
        uploadLabel.appendChild(fileInput);

        const uploadText = document.createElement("div");
        uploadText.className = "imageselector-upload-text";
        uploadText.textContent = "or drag and drop images here";

        uploadArea.addEventListener("dragover", (e) => {
            e.preventDefault();
            uploadArea.classList.add("dragover");
        });
        uploadArea.addEventListener("dragleave", () => {
            uploadArea.classList.remove("dragover");
        });
        uploadArea.addEventListener("drop", (e) => {
            e.preventDefault();
            uploadArea.classList.remove("dragover");
            if (e.dataTransfer.files.length > 0) {
                this._handleFileSelect(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener("change", (e) => {
            if (e.target.files.length > 0) {
                this._handleFileSelect(e.target.files);
                e.target.value = '';
            }
        });

        uploadArea.appendChild(uploadLabel);
        uploadArea.appendChild(uploadText);

        const currentImageContainer = document.createElement("div");
        currentImageContainer.className = "imageselector-current-image";

        const currentLabel = document.createElement("div");
        currentLabel.className = "imageselector-current-image-label";
        currentLabel.textContent = "Currently selected:";

        const wrapper = document.createElement("div");
        wrapper.className = "imageselector-current-image-wrapper";
        wrapper.id = "imageselector-current-wrapper";

        const currentName = document.createElement("div");
        currentName.className = "imageselector-current-image-name";
        currentName.id = "imageselector-current-name";

        currentImageContainer.appendChild(currentLabel);
        currentImageContainer.appendChild(wrapper);
        currentImageContainer.appendChild(currentName);

        const grid = document.createElement("div");
        grid.className = "imageselector-grid";

        body.appendChild(uploadArea);
        body.appendChild(currentImageContainer);
        body.appendChild(grid);

        const footer = document.createElement("div");
        footer.className = "imageselector-footer";

        const info = document.createElement("div");
        info.className = "imageselector-footer-info";
        info.innerHTML = `Selected: <span id="imageselector-count">0</span>`;

        const footerActions = document.createElement("div");
        footerActions.className = "imageselector-footer-actions";

        const cancelBtn = document.createElement("button");
        cancelBtn.className = "imageselector-btn imageselector-btn-cancel";
        cancelBtn.textContent = "Clear";
        cancelBtn.addEventListener("click", () => {
            this.clear();
        });

        const selectBtn = document.createElement("button");
        selectBtn.className = "imageselector-btn imageselector-btn-select";
        selectBtn.textContent = "Select";
        selectBtn.addEventListener("click", () => this._confirmSelection());

        footerActions.appendChild(cancelBtn);
        footerActions.appendChild(selectBtn);
        footer.appendChild(info);
        footer.appendChild(footerActions);

        modal.appendChild(header);
        modal.appendChild(body);
        modal.appendChild(footer);
        overlay.appendChild(modal);

        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) this.close();
        });

        document.body.appendChild(overlay);

        const previewOverlay = document.createElement("div");
        previewOverlay.className = "imageselector-preview-overlay";

        const previewClose = document.createElement("button");
        previewClose.className = "imageselector-preview-close";
        previewClose.innerHTML = "×";
        previewClose.addEventListener("click", () => this._closePreview());

        const previewImg = document.createElement("img");
        previewImg.alt = "Preview";

        previewOverlay.appendChild(previewImg);
        previewOverlay.appendChild(previewClose);

        previewOverlay.addEventListener("click", (e) => {
            if (e.target === previewOverlay) this._closePreview();
        });

        document.body.appendChild(previewOverlay);

        this._overlay = overlay;
        this._modal = modal;
        this._grid = grid;
        this._selectBtn = selectBtn;
        this._cancelBtn = cancelBtn;
        this._addBtn = addBtn;
        this._infoText = info.querySelector("#imageselector-count");
        this._fileInput = fileInput;
        this._uploadArea = uploadArea;
        this._previewOverlay = previewOverlay;
        this._currentImageContainer = currentImageContainer;

        this._renderGrid();
    }

    /**
     * Handle file selection - syncs with hidden file input
     * @private
     */
    _handleFileSelect(files) {
        const validFiles = [];
        let hasError = false;

        if (this.config.selection === "single" && files.length > 1) {
            alert("Only one file can be selected");
            return;
        }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            if (file.size > this.config.maxSize) {
                alert(`File "${file.name}" exceeds maximum size of ${this._formatSize(this.config.maxSize)}`);
                hasError = true;
                continue;
            }

            if (!file.type.startsWith('image/')) {
                alert(`File "${file.name}" is not an image`);
                hasError = true;
                continue;
            }

            const url = URL.createObjectURL(file);
            file._url = url;
            validFiles.push(file);
        }

        if (hasError || validFiles.length === 0) {
            return;
        }

        if (this.config.selection === "single") {
            this._selectedFiles.forEach(f => {
                if (f._url) URL.revokeObjectURL(f._url);
            });
            this._selectedFiles = validFiles;
        } else {
            this._selectedFiles = [...this._selectedFiles, ...validFiles];
        }

        this._syncHiddenFileInput();

        this._renderGrid();
        this._updateSelectionInfo();

        if (typeof this.config.onSelect === "function") {
            this.config.onSelect(this._selectedFiles, this.element);
        }
    }

    /**
     * Sync the hidden file input with selected files
     * @private
     */
    _syncHiddenFileInput() {
        if (!this._hiddenFileInput) return;

        const dataTransfer = new DataTransfer();
        this._selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        this._hiddenFileInput.files = dataTransfer.files;
    }

    /**
     * Render grid
     * @private
     */
    _renderGrid() {
        if (!this._grid) return;

        this._grid.innerHTML = "";

        if (this._selectedFiles.length === 0) {
            const empty = document.createElement("div");
            empty.className = "imageselector-empty";
            empty.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 16px;">🖼️</div>
                <div>No images selected</div>
                <div style="font-size: 13px; margin-top: 8px; color: #adb5bd;">
                    Click "Select Files" to choose images
                </div>
            `;
            this._grid.appendChild(empty);
            return;
        }

        this._selectedFiles.forEach((file, index) => {
            const item = document.createElement("div");
            item.className = "imageselector-item imageselector-selected";

            const img = document.createElement("img");
            img.src = file._url;
            img.setAttribute("loading", "lazy");
            img.alt = file.name;

            const actions = document.createElement("div");
            actions.className = "imageselector-item-actions";

            const eyeBtn = document.createElement("button");
            eyeBtn.className = "imageselector-item-eye";
            eyeBtn.innerHTML = "👁";
            eyeBtn.title = "Preview";
            eyeBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                this._openPreview({ url: file._url, name: file.name });
            });

            const deleteBtn = document.createElement("button");
            deleteBtn.className = "imageselector-item-delete";
            deleteBtn.innerHTML = "✕";
            deleteBtn.title = "Remove";
            deleteBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                this._removeFile(index);
            });

            actions.appendChild(deleteBtn);
            actions.appendChild(eyeBtn);

            const check = document.createElement("div");
            check.className = "imageselector-item-check";
            check.textContent = "✓";

            const info = document.createElement("div");
            info.className = "imageselector-item-info";

            const name = document.createElement("div");
            name.className = "imageselector-item-name";
            name.textContent = file.name;

            const size = document.createElement("div");
            size.className = "imageselector-item-size";
            size.textContent = this._formatSize(file.size);

            info.appendChild(name);
            info.appendChild(size);
            item.appendChild(img);
            item.appendChild(actions);
            item.appendChild(check);
            item.appendChild(info);

            this._grid.appendChild(item);
        });

        this._updateSelectionInfo();
    }

    /**
     * Remove a file
     * @private
     */
    _removeFile(index) {
        if (this._selectedFiles[index] && this._selectedFiles[index]._url) {
            URL.revokeObjectURL(this._selectedFiles[index]._url);
        }
        this._selectedFiles.splice(index, 1);
        this._syncHiddenFileInput();
        this._renderGrid();
        this._updateSelectionInfo();

        const names = this._selectedFiles.map(f => f.name);
        if (this.config.selection === "single") {
            this.setValue(names[0] || '');
        } else {
            this.setValue(names.join('||'));
        }
    }

    /**
     * Update selection info
     * @private
     */
    _updateSelectionInfo() {
        const count = this._selectedFiles.length;
        if (this._infoText) {
            this._infoText.textContent = count;
        }
        if (this._selectBtn) {
            this._selectBtn.textContent = count > 0
                ? `Select ${count} image${count > 1 ? "s" : ""}`
                : "Okay";
        }
        this._displayCurrentImage();
    }

    /**
     * Display current image
     * @private
     */
    _displayCurrentImage() {
        if (!this._currentImageContainer) return;

        const currentValue = this.element.value;
        const wrapper = this._currentImageContainer.querySelector("#imageselector-current-wrapper");
        const nameEl = this._currentImageContainer.querySelector("#imageselector-current-name");

        wrapper.innerHTML = "";

        if (currentValue && currentValue.trim() !== "") {
            const names = currentValue.split('||').map(u => u.trim()).filter(u => u !== "");
            if (names.length > 0) {
                const files = names.map(name =>
                    this._selectedFiles.find(f => f.name === name)
                ).filter(f => f);

                if (files.length > 0) {
                    files.forEach((file) => {
                        const item = document.createElement("div");
                        item.className = "imageselector-current-image-item";

                        const img = document.createElement("img");
                        img.src = file._url;
                        img.alt = file.name;

                        const actions = document.createElement("div");
                        actions.className = "imageselector-current-image-actions";

                        const eyeBtn = document.createElement("button");
                        eyeBtn.className = "imageselector-current-image-eye";
                        eyeBtn.innerHTML = "👁";
                        eyeBtn.title = "Preview";
                        eyeBtn.addEventListener("click", (e) => {
                            e.stopPropagation();
                            this._openPreview({ url: file._url, name: file.name });
                        });

                        actions.appendChild(eyeBtn);
                        item.appendChild(img);
                        item.appendChild(actions);
                        wrapper.appendChild(item);
                    });

                    nameEl.textContent = `${files.length} image${files.length > 1 ? 's' : ''} selected`;
                    this._currentImageContainer.classList.add("imageselector-show");
                    return;
                }
            }
        }

        nameEl.textContent = "";
        this._currentImageContainer.classList.remove("imageselector-show");
    }

    /**
     * Confirm selection - updates the text input with file names
     * @private
     */
    _confirmSelection() {
        if (this._selectedFiles.length === 0) {
            this.setValue("");
            if (typeof this.config.onSelect === "function") {
                this.config.onSelect([], this.element);
            }
            this.close();
            return;
        }

        const names = this._selectedFiles.map(f => f.name);

        if (this.config.selection === "single") {
            this.setValue(names[0]);
        } else {
            this.setValue(names.join('||'));
        }

        this.close();
    }

    /**
     * Open preview
     * @private
     */
    _openPreview(image) {
        if (!this._previewOverlay) return;

        const img = this._previewOverlay.querySelector("img");
        if (img) {
            img.src = image.url || image.path || "";
        }

        this._previewOverlay.classList.add("imageselector-show");
        document.body.style.overflow = "hidden";
    }

    /**
     * Close preview
     * @private
     */
    _closePreview() {
        if (!this._previewOverlay) return;
        this._previewOverlay.classList.remove("imageselector-show");
        document.body.style.overflow = "";
    }

    /**
     * Format file size
     * @private
     */
    _formatSize(bytes) {
        if (bytes === 0) return "0 B";
        const k = 1024;
        const sizes = ["B", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    }

    /**
     * Trigger callbacks
     * @private
     */
    _triggerCallbacks() {
        const value = this.getValue();
        this._callbacks.forEach(callback => {
            try {
                callback(value);
            } catch (error) {
                console.error('Error in onChange callback:', error);
            }
        });
    }

    /**
     * Static init method
     */
    /**
 * Static init method - supports both single and multiple elements
 */
    static init(element, config = {}) {
        if (typeof element === 'string') {
            const elements = document.querySelectorAll(element);

            if (elements.length > 1) {
                return Array.from(elements).map(el => {
                    const instance = new ImageSelector(el, config);
                    instance.init();
                    return instance;
                });
            }

            if (elements.length === 0) {
                throw new Error('No elements found with selector: ' + element);
            }

            element = elements[0];
        }

        const instance = new ImageSelector(element, config);
        instance.init();
        return instance;
    }
}

export default ImageSelector;