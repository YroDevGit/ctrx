class CImagePicker {
    static styleId = "cimagepicker-style";
    static instances = [];

    static ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("style");
        style.id = this.styleId;
        style.textContent = `
            .cimagepicker-overlay {
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
            }

            .cimagepicker-overlay.cimagepicker-show {
                display: flex;
                opacity: 1;
            }

            .cimagepicker-modal {
                width: 95%;
                max-width: 1100px;
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

            .cimagepicker-overlay.cimagepicker-show .cimagepicker-modal {
                transform: scale(1) translateY(0);
                opacity: 1;
            }

            .cimagepicker-header {
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

            .cimagepicker-header-left {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .cimagepicker-header h2 {
                margin: 0;
                font-size: 20px;
                font-weight: 600;
                color: #212529;
                letter-spacing: 0.3px;
            }

            .cimagepicker-header-actions {
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
            }

            .cimagepicker-search {
                padding: 8px 16px;
                border-radius: 8px;
                border: 1px solid #dee2e6;
                background: #ffffff;
                color: #212529;
                font-size: 14px;
                width: 220px;
                transition: all 0.2s ease;
                outline: none;
            }

            .cimagepicker-search:focus {
                border-color: #0066ff;
                box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
            }

            .cimagepicker-search::placeholder {
                color: #adb5bd;
            }

            .cimagepicker-btn-add {
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

            .cimagepicker-btn-add:hover {
                background: #218838;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(40,167,69,0.3);
            }

            .cimagepicker-close {
                border: none;
                background: #e9ecef;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                font-size: 22px;
                cursor: pointer;
                color: #495057;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                line-height: 1;
            }

            .cimagepicker-close:hover {
                background: #dee2e6;
                color: #212529;
                transform: rotate(90deg);
            }

            .cimagepicker-body {
                padding: 20px 24px;
                overflow-y: auto;
                flex: 1;
                background: #ffffff;
            }

            .cimagepicker-body::-webkit-scrollbar {
                width: 6px;
            }

            .cimagepicker-body::-webkit-scrollbar-track {
                background: #f8f9fa;
            }

            .cimagepicker-body::-webkit-scrollbar-thumb {
                background: #dee2e6;
                border-radius: 10px;
            }

            .cimagepicker-body::-webkit-scrollbar-thumb:hover {
                background: #ced4da;
            }

            .cimagepicker-current-image {
                display: none;
                margin-bottom: 20px;
                padding: 16px;
                background: #f8f9fa;
                border-radius: 12px;
                border: 2px solid #e9ecef;
            }

            .cimagepicker-current-image.cimagepicker-show {
                display: block;
            }

            .cimagepicker-current-image-wrapper {
                display: flex;
                gap: 12px;
                overflow-x: auto;
                padding: 8px 4px;
                scroll-behavior: smooth;
                max-width: 100%;
            }

            .cimagepicker-current-image-wrapper::-webkit-scrollbar {
                height: 6px;
            }

            .cimagepicker-current-image-wrapper::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }

            .cimagepicker-current-image-wrapper::-webkit-scrollbar-thumb {
                background: #dee2e6;
                border-radius: 10px;
            }

            .cimagepicker-current-image-wrapper::-webkit-scrollbar-thumb:hover {
                background: #ced4da;
            }

            .cimagepicker-current-image-item {
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

            .cimagepicker-current-image-item:hover {
                border-color: #0066ff;
                transform: scale(1.02);
            }

            .cimagepicker-current-image-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .cimagepicker-current-image-item .cimagepicker-current-image-actions {
                position: absolute;
                top: 4px;
                left: 4px;
                display: flex;
                gap: 4px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .cimagepicker-current-image-item:hover .cimagepicker-current-image-actions {
                opacity: 1;
            }

            .cimagepicker-current-image-eye {
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

            .cimagepicker-current-image-eye:hover {
                background: rgba(0,0,0,0.8);
                transform: scale(1.1);
            }

            .cimagepicker-current-image-label {
                font-size: 13px;
                color: #6c757d;
                font-weight: 500;
                margin-bottom: 8px;
            }

            .cimagepicker-current-image-name {
                font-size: 13px;
                color: #212529;
                font-weight: 500;
                margin-top: 8px;
                word-break: break-all;
            }

            .cimagepicker-upload-area {
                display: none;
                padding: 30px;
                background: #f8f9fa;
                border-radius: 12px;
                border: 2px dashed #dee2e6;
                margin-bottom: 20px;
                text-align: center;
                transition: all 0.3s ease;
            }

            .cimagepicker-upload-area.cimagepicker-show {
                display: block;
            }

            .cimagepicker-upload-area.dragover {
                border-color: #0066ff;
                background: #f0f7ff;
            }

            .cimagepicker-upload-area input[type="file"] {
                display: none;
            }

            .cimagepicker-upload-label {
                display: inline-block;
                padding: 12px 32px;
                background: #0066ff;
                color: #fff;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.2s ease;
            }

            .cimagepicker-upload-label:hover {
                background: #0052cc;
                transform: translateY(-2px);
            }

            .cimagepicker-upload-text {
                color: #6c757d;
                margin: 12px 0;
                font-size: 14px;
            }

            .cimagepicker-upload-progress {
                display: none;
                margin-top: 16px;
                height: 4px;
                background: #e9ecef;
                border-radius: 2px;
                overflow: hidden;
            }

            .cimagepicker-upload-progress.cimagepicker-show {
                display: block;
            }

            .cimagepicker-upload-progress-bar {
                height: 100%;
                background: #0066ff;
                width: 0%;
                transition: width 0.3s ease;
            }

            .cimagepicker-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 16px;
            }

            .cimagepicker-item {
                background: #ffffff;
                border-radius: 10px;
                overflow: hidden;
                cursor: pointer;
                transition: all 0.25s ease;
                border: 2px solid #e9ecef;
                position: relative;
            }

            .cimagepicker-item:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                border-color: #dee2e6;
            }

            .cimagepicker-item.cimagepicker-selected {
                border-color: #0066ff;
                box-shadow: 0 0 0 3px rgba(0,102,255,0.2);
            }

            .cimagepicker-item img {
                width: 100%;
                height: 160px;
                object-fit: cover;
                display: block;
                background: #f8f9fa;
            }

            .cimagepicker-item-info {
                padding: 12px 14px;
                background: #ffffff;
            }

            .cimagepicker-item-name {
                font-size: 13px;
                color: #212529;
                font-weight: 500;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-bottom: 4px;
            }

            .cimagepicker-item-size {
                font-size: 11px;
                color: #6c757d;
            }

            .cimagepicker-item-check {
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

            .cimagepicker-item.cimagepicker-selected .cimagepicker-item-check {
                display: flex;
            }

            .cimagepicker-item-actions {
                position: absolute;
                top: 8px;
                left: 8px;
                display: flex;
                gap: 6px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .cimagepicker-item:hover .cimagepicker-item-actions {
                opacity: 1;
            }

            .cimagepicker-item-eye {
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
                font-size: 14px;
                transition: all 0.2s ease;
                backdrop-filter: blur(4px);
            }

            .cimagepicker-item-eye:hover {
                background: rgba(0,0,0,0.8);
                transform: scale(1.1);
            }

            .cimagepicker-item-delete {
                width: 24px;
                height: 24px;
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

            .cimagepicker-item-delete:hover {
                background: rgba(200, 35, 51, 0.95);
                transform: scale(1.1);
            }

            .cimagepicker-empty {
                grid-column: 1 / -1;
                text-align: center;
                padding: 60px 20px;
                color: #adb5bd;
                font-size: 16px;
            }

            .cimagepicker-empty svg {
                display: block;
                margin: 0 auto 16px;
                opacity: 0.3;
            }

            .cimagepicker-footer {
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

            .cimagepicker-footer-info {
                color: #6c757d;
                font-size: 14px;
            }

            .cimagepicker-footer-info span {
                color: #212529;
                font-weight: 600;
            }

            .cimagepicker-footer-actions {
                display: flex;
                gap: 10px;
            }

            .cimagepicker-btn {
                padding: 10px 24px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: all 0.2s ease;
                font-family: inherit;
            }

            .cimagepicker-btn-cancel {
                background: #e9ecef;
                color: #495057;
            }

            .cimagepicker-btn-cancel:hover {
                background: #dee2e6;
                color: #212529;
            }

            .cimagepicker-btn-select {
                background: #0066ff;
                color: #fff;
            }

            .cimagepicker-btn-select:hover {
                background: #0052cc;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,102,255,0.3);
            }

            .cimagepicker-preview-overlay {
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

            .cimagepicker-preview-overlay.cimagepicker-show {
                display: flex;
                opacity: 1;
            }

            .cimagepicker-preview-overlay img {
                max-width: 95%;
                max-height: 95%;
                object-fit: contain;
                border-radius: 4px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
                transform: scale(0.95);
                transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            .cimagepicker-preview-overlay.cimagepicker-show img {
                transform: scale(1);
            }

            .cimagepicker-preview-close {
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

            .cimagepicker-preview-close:hover {
                color: #ccc;
            }

            @media (max-width: 640px) {
                .cimagepicker-modal {
                    width: 100%;
                    max-height: 100vh;
                    border-radius: 0;
                }

                .cimagepicker-header {
                    flex-direction: column;
                    align-items: stretch;
                    padding: 16px;
                }

                .cimagepicker-header-left {
                    flex-direction: column;
                    align-items: stretch;
                }

                .cimagepicker-header-actions {
                    flex-direction: column;
                }

                .cimagepicker-search {
                    width: 100%;
                }

                .cimagepicker-body {
                    padding: 12px;
                }

                .cimagepicker-grid {
                    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                    gap: 10px;
                }

                .cimagepicker-item img {
                    height: 120px;
                }

                .cimagepicker-footer {
                    flex-direction: column;
                    padding: 16px;
                }

                .cimagepicker-footer-actions {
                    width: 100%;
                }

                .cimagepicker-footer-actions button {
                    flex: 1;
                }

                .cimagepicker-current-image-item {
                    width: 80px;
                    height: 80px;
                }
            }

            @media (max-width: 480px) {
                .cimagepicker-current-image-item {
                    width: 60px;
                    height: 60px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    static async fetchImages(path = "public") {
        try {
            const response = await fetch(`/ctrx.yro.ctrstorage.images/getall?action=list&dir=${encodeURIComponent(path)}`);
            if (!response.ok) throw new Error("Failed to fetch images");
            const data = await response.json();
            return data.images || [];
        } catch (error) {
            console.error("CImagePicker: Error fetching images", error);
            return [];
        }
    }

    static async uploadImage(file, path = "public", onProgress = null) {
        const formData = new FormData();
        formData.append("image", file);
        formData.append("path", path);

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/ctrx.yro.ctrstorage.images/uploadHere?action=upload&dir=" + path);

            if (onProgress && typeof onProgress === "function") {
                xhr.upload.addEventListener("progress", (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        onProgress(percent);
                    }
                });
            }

            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        resolve(data);
                    } catch (e) {
                        reject(new Error("Invalid response"));
                    }
                } else {
                    reject(new Error("Upload failed"));
                }
            };

            xhr.onerror = () => reject(new Error("Network error"));
            xhr.send(formData);
        });
    }

    static async deleteImage(filename, path = "public") {
        try {
            const response = await fetch(`/ctrx.yro.ctrstorage.images/deleteImg?action=delete&dir=${encodeURIComponent(path)}&filename=${encodeURIComponent(filename)}`);
            if (!response.ok) throw new Error("Failed to delete image");
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("CImagePicker: Error deleting image", error);
            throw error;
        }
    }

    static formatSize(bytes) {
        if (bytes === 0) return "0 B";
        const k = 1024;
        const sizes = ["B", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    }

    static init(config = {}) {
        this.ensureStyle();

        const instances = [];
        let elements = [];

        if (config.element) {
            if (typeof config.element === 'string') {
                if (config.element.startsWith('.')) {
                    elements = document.querySelectorAll(config.element);
                } else if (config.element.startsWith('#')) {
                    const el = document.querySelector(config.element);
                    if (el) elements = [el];
                } else {
                    const el = document.getElementById(config.element);
                    if (el) elements = [el];
                }
            } else if (config.element instanceof HTMLElement) {
                elements = [config.element];
            } else if (config.element instanceof NodeList || Array.isArray(config.element)) {
                elements = Array.from(config.element);
            }
        }

        if (elements.length === 0) {
            console.error("CImagePicker: No input elements found");
            return null;
        }

        elements.forEach(input => {
            const instanceConfig = { ...config };
            instanceConfig.id = input.id || input.className || `cimagepicker-${Date.now()}-${Math.random()}`;
            instanceConfig.path = config.path ?? config.dir ?? config.directory ?? "public";

            input.setAttribute("readonly", "");

            const instance = {
                input: input,
                config: instanceConfig,
                selectedImages: [],
                images: [],
                filteredImages: [],
                overlay: null,
                modal: null,
                grid: null,
                searchInput: null,
                selectBtn: null,
                cancelBtn: null,
                addBtn: null,
                infoText: null,
                uploadArea: null,
                fileInput: null,
                progressBar: null,
                previewOverlay: null,
                currentImageContainer: null,
                isOpen: false,
                isUploading: false,

                buildOverlay() {
                    const overlay = document.createElement("div");
                    overlay.className = "cimagepicker-overlay";
                    overlay.id = `cimagepicker-${Date.now()}-${Math.random()}`;

                    const modal = document.createElement("div");
                    modal.className = "cimagepicker-modal";

                    const header = document.createElement("div");
                    header.className = "cimagepicker-header";

                    const headerLeft = document.createElement("div");
                    headerLeft.className = "cimagepicker-header-left";

                    const title = document.createElement("h2");
                    title.textContent = instanceConfig.title || "Select Image(s)";

                    const addBtn = document.createElement("button");
                    addBtn.className = "cimagepicker-btn-add";
                    addBtn.innerHTML = "➕ Add Image";
                    addBtn.addEventListener("click", () => this.toggleUpload());

                    headerLeft.appendChild(title);
                    headerLeft.appendChild(addBtn);

                    const headerActions = document.createElement("div");
                    headerActions.className = "cimagepicker-header-actions";

                    const search = document.createElement("input");
                    search.type = "text";
                    search.className = "cimagepicker-search";
                    search.placeholder = "Search images...";
                    search.addEventListener("input", (e) => {
                        this.filterImages(e.target.value);
                    });

                    const closeBtn = document.createElement("button");
                    closeBtn.className = "cimagepicker-close";
                    closeBtn.innerHTML = "×";
                    closeBtn.addEventListener("click", () => this.close());

                    headerActions.appendChild(search);
                    headerActions.appendChild(closeBtn);
                    header.appendChild(headerLeft);
                    header.appendChild(headerActions);

                    const body = document.createElement("div");
                    body.className = "cimagepicker-body";

                    const uploadArea = document.createElement("div");
                    uploadArea.className = "cimagepicker-upload-area";

                    const uploadLabel = document.createElement("label");
                    uploadLabel.className = "cimagepicker-upload-label";
                    uploadLabel.textContent = "Choose Image";

                    const fileInput = document.createElement("input");
                    fileInput.type = "file";
                    fileInput.multiple = false;
                    fileInput.accept = "image/*";

                    const uploadText = document.createElement("div");
                    uploadText.className = "cimagepicker-upload-text";
                    uploadText.textContent = "or drag and drop here";

                    const progressWrapper = document.createElement("div");
                    progressWrapper.className = "cimagepicker-upload-progress";

                    const progressBar = document.createElement("div");
                    progressBar.className = "cimagepicker-upload-progress-bar";
                    progressWrapper.appendChild(progressBar);

                    uploadLabel.appendChild(fileInput);
                    uploadArea.appendChild(uploadLabel);
                    uploadArea.appendChild(uploadText);
                    uploadArea.appendChild(progressWrapper);

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
                            this.handleUpload(e.dataTransfer.files[0]);
                        }
                    });

                    fileInput.addEventListener("change", (e) => {
                        if (e.target.files.length > 0) {
                            this.handleUpload(e.target.files[0]);
                        }
                    });

                    const currentImageContainer = document.createElement("div");
                    currentImageContainer.className = "cimagepicker-current-image";

                    const currentLabel = document.createElement("div");
                    currentLabel.className = "cimagepicker-current-image-label";
                    currentLabel.textContent = "Currently selected:";

                    const wrapper = document.createElement("div");
                    wrapper.className = "cimagepicker-current-image-wrapper";
                    wrapper.id = "cimagepicker-current-wrapper";

                    const currentName = document.createElement("div");
                    currentName.className = "cimagepicker-current-image-name";
                    currentName.id = "cimagepicker-current-name";

                    currentImageContainer.appendChild(currentLabel);
                    currentImageContainer.appendChild(wrapper);
                    currentImageContainer.appendChild(currentName);

                    body.appendChild(uploadArea);
                    body.appendChild(currentImageContainer);

                    const grid = document.createElement("div");
                    grid.className = "cimagepicker-grid";

                    body.appendChild(grid);

                    const footer = document.createElement("div");
                    footer.className = "cimagepicker-footer";

                    const info = document.createElement("div");
                    info.className = "cimagepicker-footer-info";
                    info.innerHTML = `Selected: <span id="cimagepicker-count">0</span>`;

                    const footerActions = document.createElement("div");
                    footerActions.className = "cimagepicker-footer-actions";

                    const cancelBtn = document.createElement("button");
                    cancelBtn.className = "cimagepicker-btn cimagepicker-btn-cancel";
                    cancelBtn.textContent = "Clear";
                    cancelBtn.addEventListener("click", () => {
                        this.selectedImages = [];
                        this.updateSelectionInfo();
                        let allPick = document.querySelectorAll(".cimagepicker-item");
                        allPick.forEach(element => {
                            element.classList.remove("cimagepicker-selected");
                        });
                    });

                    const selectBtn = document.createElement("button");
                    selectBtn.className = "cimagepicker-btn cimagepicker-btn-select";
                    selectBtn.textContent = "Select";
                    selectBtn.addEventListener("click", () => this.confirmSelection());

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
                    previewOverlay.className = "cimagepicker-preview-overlay";
                    previewOverlay.id = `cimagepicker-preview-${Date.now()}-${Math.random()}`;

                    const previewClose = document.createElement("button");
                    previewClose.className = "cimagepicker-preview-close";
                    previewClose.innerHTML = "×";
                    previewClose.addEventListener("click", () => this.closePreview());

                    const previewImg = document.createElement("img");
                    previewImg.alt = "Preview";

                    previewOverlay.appendChild(previewImg);
                    previewOverlay.appendChild(previewClose);

                    previewOverlay.addEventListener("click", (e) => {
                        if (e.target === previewOverlay) this.closePreview();
                    });

                    document.body.appendChild(previewOverlay);

                    this.overlay = overlay;
                    this.modal = modal;
                    this.grid = grid;
                    this.searchInput = search;
                    this.selectBtn = selectBtn;
                    this.cancelBtn = cancelBtn;
                    this.addBtn = addBtn;
                    this.infoText = info.querySelector("#cimagepicker-count");
                    this.uploadArea = uploadArea;
                    this.fileInput = fileInput;
                    this.progressBar = progressBar;
                    this.previewOverlay = previewOverlay;
                    this.currentImageContainer = currentImageContainer;

                    return overlay;
                },

                displayCurrentImage() {
                    if (!this.currentImageContainer) return;

                    const currentValue = this.input.value;
                    const wrapper = this.currentImageContainer.querySelector("#cimagepicker-current-wrapper");
                    const nameEl = this.currentImageContainer.querySelector("#cimagepicker-current-name");

                    wrapper.innerHTML = "";

                    if (currentValue && currentValue.trim() !== "") {
                        const urls = currentValue.split('||').map(u => u.trim()).filter(u => u !== "");

                        if (urls.length > 0) {
                            urls.forEach((url, index) => {
                                const item = document.createElement("div");
                                item.className = "cimagepicker-current-image-item";

                                const img = document.createElement("img");
                                img.src = url;
                                img.alt = `Image ${index + 1}`;
                                img.onerror = function () { this.style.display = "none"; };

                                const actions = document.createElement("div");
                                actions.className = "cimagepicker-current-image-actions";

                                const eyeBtn = document.createElement("button");
                                eyeBtn.className = "cimagepicker-current-image-eye";
                                eyeBtn.innerHTML = "👁";
                                eyeBtn.title = "Preview";
                                eyeBtn.addEventListener("click", (e) => {
                                    e.stopPropagation();
                                    this.openPreview({ url: url, name: `Image ${index + 1}` }, e);
                                });

                                actions.appendChild(eyeBtn);
                                item.appendChild(img);
                                item.appendChild(actions);
                                wrapper.appendChild(item);
                            });

                            nameEl.textContent = `${urls.length} image${urls.length > 1 ? 's' : ''} selected`;
                            this.currentImageContainer.classList.add("cimagepicker-show");
                            return;
                        }
                    }

                    nameEl.textContent = "";
                    this.currentImageContainer.classList.remove("cimagepicker-show");
                },

                toggleUpload() {
                    if (this.uploadArea) {
                        this.uploadArea.classList.toggle("cimagepicker-show");
                        if (!this.uploadArea.classList.contains("cimagepicker-show")) {
                            this.fileInput.value = "";
                            this.progressBar.style.width = "0%";
                            this.progressBar.parentElement.classList.remove("cimagepicker-show");
                            this.isUploading = false;
                        }
                    }
                },

                async handleUpload(file) {
                    if (this.isUploading) return;

                    if (!file.type.startsWith("image/")) {
                        alert("Please upload an image file");
                        return;
                    }

                    const allowedTypes = instanceConfig.type || instanceConfig.types || "*";
                    if (allowedTypes !== "*") {
                        const ext = file.name.split(".").pop().toLowerCase();
                        const allowed = allowedTypes.split("|").map(t => t.trim().toLowerCase());
                        if (!allowed.includes(ext)) {
                            alert(`Image type not allowed. Allowed: ${allowedTypes}`);
                            return;
                        }
                    }

                    this.isUploading = true;
                    this.fileInput.disabled = true;
                    this.progressBar.parentElement.classList.add("cimagepicker-show");
                    this.progressBar.style.width = "0%";

                    try {
                        const result = await CImagePicker.uploadImage(
                            file,
                            instanceConfig.path || "public",
                            (percent) => {
                                this.progressBar.style.width = percent + "%";
                            }
                        );

                        if (result.success && result.image) {
                            this.images.unshift(result.image);
                            this.filteredImages.unshift(result.image);
                            this.renderGrid();

                            this.updateSelectionInfo();

                            this.uploadArea.classList.remove("cimagepicker-show");
                            this.fileInput.value = "";
                            this.progressBar.style.width = "0%";
                            this.progressBar.parentElement.classList.remove("cimagepicker-show");

                            if (typeof instanceConfig.onUpload === "function") {
                                instanceConfig.onUpload(result.image, this);
                            }
                        } else {
                            this.progressBar.parentElement.classList.remove("cimagepicker-show");
                            alert(result.message ?? "Failed to upload image");
                        }
                    } catch (error) {
                        alert("Upload failed: " + error.message);
                    }

                    this.isUploading = false;
                    this.fileInput.disabled = false;
                },

                async handleDelete(image, event) {
                    event.stopPropagation();

                    if (!confirm(`Are you sure you want to delete "${image.name}"?`)) {
                        return;
                    }

                    try {
                        let resDel = await CImagePicker.deleteImage(image.name, instanceConfig.path || "public");

                        if (resDel.success) {
                            alert("Image deleted successfully");
                        } else {
                            alert(resDel.message ?? "Failed to delete image");
                            return;
                        }
                        const index = this.images.findIndex(f => f.name === image.name);
                        if (index !== -1) {
                            this.images.splice(index, 1);
                        }

                        const filteredIndex = this.filteredImages.findIndex(f => f.name === image.name);
                        if (filteredIndex !== -1) {
                            this.filteredImages.splice(filteredIndex, 1);
                        }

                        const selectedIndex = this.selectedImages.findIndex(f => f.name === image.name);
                        if (selectedIndex !== -1) {
                            this.selectedImages.splice(selectedIndex, 1);
                        }

                        this.renderGrid();
                        this.updateSelectionInfo();

                        if (typeof instanceConfig.onDelete === "function") {
                            instanceConfig.onDelete(image, this);
                        }
                    } catch (error) {
                        alert("Failed to delete image: " + error.message);
                    }
                },

                openPreview(image, event) {
                    event.stopPropagation();

                    if (!this.previewOverlay) return;

                    const img = this.previewOverlay.querySelector("img");
                    if (img) {
                        img.src = image.url || image.path || "";
                    }

                    this.previewOverlay.classList.add("cimagepicker-show");
                    document.body.style.overflow = "hidden";
                },

                closePreview() {
                    if (!this.previewOverlay) return;
                    this.previewOverlay.classList.remove("cimagepicker-show");
                    document.body.style.overflow = "";
                },

                filterImages(query) {
                    const q = query.toLowerCase().trim();
                    if (!q) {
                        this.filteredImages = [...this.images];
                    } else {
                        this.filteredImages = this.images.filter(f =>
                            f.name.toLowerCase().includes(q)
                        );
                    }
                    this.renderGrid();
                },

                renderGrid() {
                    this.grid.innerHTML = "";

                    if (this.filteredImages.length === 0) {
                        const empty = document.createElement("div");
                        empty.className = "cimagepicker-empty";
                        empty.innerHTML = `
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#adb5bd" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <path d="M3 9h18M9 21V9"/>
                            </svg>
                            <div>No images found</div>
                        `;
                        this.grid.appendChild(empty);
                        return;
                    }

                    this.filteredImages.forEach(image => {
                        const item = document.createElement("div");
                        item.className = "cimagepicker-item";
                        item.dataset.filename = image.name;

                        const img = document.createElement("img");
                        img.src = image.url || image.path || "";
                        img.setAttribute("loading", "lazy");
                        img.alt = image.name;
                        img.loading = "lazy";
                        img.onerror = function () {
                            this.style.display = "none";
                        };

                        const actions = document.createElement("div");
                        actions.className = "cimagepicker-item-actions";

                        const eyeBtn = document.createElement("button");
                        eyeBtn.className = "cimagepicker-item-eye";
                        eyeBtn.innerHTML = "👁";
                        eyeBtn.title = "Preview";
                        eyeBtn.addEventListener("click", (e) => this.openPreview(image, e));

                        const deleteBtn = document.createElement("button");
                        deleteBtn.className = "cimagepicker-item-delete";
                        deleteBtn.innerHTML = "✕";
                        deleteBtn.title = "Delete";
                        deleteBtn.addEventListener("click", (e) => this.handleDelete(image, e));

                        actions.appendChild(deleteBtn);
                        actions.appendChild(eyeBtn);

                        const check = document.createElement("div");
                        check.className = "cimagepicker-item-check";
                        check.textContent = "✓";

                        const info = document.createElement("div");
                        info.className = "cimagepicker-item-info";

                        const name = document.createElement("div");
                        name.className = "cimagepicker-item-name";
                        name.textContent = image.name;

                        const size = document.createElement("div");
                        size.className = "cimagepicker-item-size";
                        size.textContent = image.size ? CImagePicker.formatSize(image.size) : "";

                        info.appendChild(name);
                        info.appendChild(size);
                        item.appendChild(img);
                        item.appendChild(actions);
                        item.appendChild(check);
                        item.appendChild(info);

                        const isSelected = this.selectedImages.some(f => f.name === image.name);
                        if (isSelected) {
                            item.classList.add("cimagepicker-selected");
                        }

                        item.addEventListener("click", () => {
                            this.toggleImage(image, item);
                        });

                        this.grid.appendChild(item);
                    });

                    this.updateSelectionInfo();
                },

                toggleImage(image, item) {
                    const isMultiple = instanceConfig.selection === "multiple";

                    if (!isMultiple) {
                        if (this.selectedImages.length === 1 && this.selectedImages[0].name === image.name) {
                            this.selectedImages = [];
                            item.classList.remove("cimagepicker-selected");
                        } else {
                            this.selectedImages = [];
                            document.querySelectorAll(".cimagepicker-item.cimagepicker-selected")
                                .forEach(el => el.classList.remove("cimagepicker-selected"));
                            this.selectedImages.push(image);
                            item.classList.add("cimagepicker-selected");
                        }
                    } else {
                        const index = this.selectedImages.findIndex(f => f.name === image.name);
                        if (index !== -1) {
                            this.selectedImages.splice(index, 1);
                            item.classList.remove("cimagepicker-selected");
                        } else {
                            this.selectedImages.push(image);
                            item.classList.add("cimagepicker-selected");
                        }
                    }

                    this.updateSelectionInfo();
                },

                updateSelectionInfo() {
                    const count = this.selectedImages.length;
                    if (this.infoText) {
                        this.infoText.textContent = count;
                    }
                    if (this.selectBtn) {
                        this.selectBtn.textContent = count > 0
                            ? `Select ${count} image${count > 1 ? "s" : ""}`
                            : "Okay";
                    }
                    this.displayCurrentImage();
                },

                confirmSelection() {
                    if (this.selectedImages.length === 0) {
                        this.input.value = "";
                        this.input.dispatchEvent(new Event("change", { bubbles: true }));
                        if (typeof instanceConfig.onSelect === "function") {
                            instanceConfig.onSelect([], this.input);
                        }
                        this.close();
                        return;
                    }

                    const isMultiple = instanceConfig.selection === "multiple";

                    if (isMultiple) {
                        const urls = this.selectedImages.map(f => f.url || f.path);
                        this.input.value = urls.join("||");
                        this.input.dispatchEvent(new Event("change", { bubbles: true }));
                    } else {
                        const image = this.selectedImages[0];
                        this.input.value = image.url || image.path || image.name;
                        this.input.dispatchEvent(new Event("change", { bubbles: true }));
                    }

                    if (typeof instanceConfig.onSelect === "function") {
                        instanceConfig.onSelect(this.selectedImages, this.input);
                    }

                    this.close();
                },

                open() {
                    if (this.isOpen) return;

                    if (!this.overlay) {
                        this.buildOverlay();
                    }

                    this.displayCurrentImage();
                    this.overlay.classList.add("cimagepicker-show");
                    this.isOpen = true;
                    document.body.style.overflow = "hidden";

                    this.loadImages();
                },

                close() {
                    if (!this.isOpen) return;
                    this.overlay.classList.remove("cimagepicker-show");
                    this.isOpen = false;
                    document.body.style.overflow = "";
                    if (this.uploadArea) {
                        this.uploadArea.classList.remove("cimagepicker-show");
                    }
                    this.closePreview();
                },

                async loadImages() {
                    this.images = await CImagePicker.fetchImages(instanceConfig.path || "public");

                    if (instanceConfig.type && instanceConfig.type !== "*") {
                        const allowed = instanceConfig.type.split("|").map(t => t.trim().toLowerCase());
                        this.images = this.images.filter(f => {
                            const ext = f.extension || f.name.split(".").pop().toLowerCase();
                            return allowed.includes(ext);
                        });
                    }

                    this.filteredImages = [...this.images];
                    this.renderGrid();
                },

                destroy() {
                    if (this.overlay && this.overlay.parentNode) {
                        this.overlay.parentNode.removeChild(this.overlay);
                    }
                    if (this.previewOverlay && this.previewOverlay.parentNode) {
                        this.previewOverlay.parentNode.removeChild(this.previewOverlay);
                    }
                    this.input.removeEventListener("click", this._clickHandler);
                    const index = CImagePicker.instances.indexOf(this);
                    if (index !== -1) CImagePicker.instances.splice(index, 1);
                }
            };

            const clickHandler = (e) => {
                e.preventDefault();
                instance.open();
            };

            instance._clickHandler = clickHandler;
            input.addEventListener("click", clickHandler);

            CImagePicker.instances.push(instance);
            instances.push(instance);
        });

        return instances.length === 1 ? instances[0] : instances;
    }
}

if (typeof window !== "undefined") {
    window.CImagePicker = CImagePicker;
}

export default CImagePicker;