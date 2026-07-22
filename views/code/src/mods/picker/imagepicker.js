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
                top: 8px;
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

            .cimagepicker-btn-select:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
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
            }
        `;
        document.head.appendChild(style);
    }

    static async fetchImages() {
        try {
            let path = "";
            const response = await fetch(`ctrx.yro.public.images/getall?action=list&path=${encodeURIComponent(path)}`);
            if (!response.ok) throw new Error("Failed to fetch images");
            const data = await response.json();
            return data.images || [];
        } catch (error) {
            console.error("CImagePicker: Error fetching images", error);
            return [];
        }
    }

    static async uploadImage(file, path = "", onProgress = null) {
        const formData = new FormData();
        formData.append("image", file);
        formData.append("path", path);

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ctrx.yro.public.images/uploadHere?action=upload");

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

    static formatSize(bytes) {
        if (bytes === 0) return "0 B";
        const k = 1024;
        const sizes = ["B", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    }

    static init(config = {}) {
        this.ensureStyle();

        const input = typeof config.id === "string" && config.id.startsWith("#")
            ? document.querySelector(config.id)
            : document.getElementById(config.id);

        if (!input) {
            console.error("CImagePicker: Input element not found");
            return null;
        }
        input.setAttribute("readonly", "");

        const instance = {
            input: input,
            config: config,
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
            isOpen: false,
            isUploading: false,

            buildOverlay() {
                const overlay = document.createElement("div");
                overlay.className = "cimagepicker-overlay";
                overlay.id = `cimagepicker-${Date.now()}`;

                const modal = document.createElement("div");
                modal.className = "cimagepicker-modal";

                const header = document.createElement("div");
                header.className = "cimagepicker-header";

                const headerLeft = document.createElement("div");
                headerLeft.className = "cimagepicker-header-left";

                const title = document.createElement("h2");
                title.textContent = config.title || "Select Image(s)";

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

                const grid = document.createElement("div");
                grid.className = "cimagepicker-grid";

                body.appendChild(uploadArea);
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
                cancelBtn.textContent = "Cancel";
                cancelBtn.addEventListener("click", () => this.close());

                const selectBtn = document.createElement("button");
                selectBtn.className = "cimagepicker-btn cimagepicker-btn-select";
                selectBtn.textContent = "Select";
                selectBtn.disabled = true;
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

                return overlay;
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

                const allowedTypes = this.config.type || this.config.types || "*";
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
                        this.config.path || "views/core/partials/storage/public",
                        (percent) => {
                            this.progressBar.style.width = percent + "%";
                        }
                    );

                    if (result.success && result.image) {
                        this.images.unshift(result.image);
                        this.filteredImages.unshift(result.image);
                        this.renderGrid();

                        if (this.config.selection !== "multiple") {
                            this.selectedImages = [result.image];
                        } else {
                            this.selectedImages.push(result.image);
                        }

                        this.updateSelectionInfo();

                        this.uploadArea.classList.remove("cimagepicker-show");
                        this.fileInput.value = "";
                        this.progressBar.style.width = "0%";
                        this.progressBar.parentElement.classList.remove("cimagepicker-show");

                        if (typeof this.config.onUpload === "function") {
                            this.config.onUpload(result.image, this);
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
                const isMultiple = this.config.selection === "multiple";

                if (!isMultiple) {
                    this.selectedImages = [];
                    document.querySelectorAll(".cimagepicker-item.cimagepicker-selected")
                        .forEach(el => el.classList.remove("cimagepicker-selected"));
                }

                const index = this.selectedImages.findIndex(f => f.name === image.name);

                if (index !== -1) {
                    this.selectedImages.splice(index, 1);
                    item.classList.remove("cimagepicker-selected");
                } else {
                    if (!isMultiple && this.selectedImages.length > 0) {
                        this.selectedImages = [];
                        document.querySelectorAll(".cimagepicker-item.cimagepicker-selected")
                            .forEach(el => el.classList.remove("cimagepicker-selected"));
                    }
                    this.selectedImages.push(image);
                    item.classList.add("cimagepicker-selected");
                }

                this.updateSelectionInfo();
            },

            updateSelectionInfo() {
                const count = this.selectedImages.length;
                if (this.infoText) {
                    this.infoText.textContent = count;
                }
                if (this.selectBtn) {
                    this.selectBtn.disabled = count === 0;
                    this.selectBtn.textContent = count > 0
                        ? `Select ${count} image${count > 1 ? "s" : ""}`
                        : "Select";
                }
            },

            confirmSelection() {
                if (this.selectedImages.length === 0) return;

                const isMultiple = this.config.selection === "multiple";

                if (isMultiple) {
                    const urls = this.selectedImages.map(f => f.url || f.path);
                    this.input.value = urls.join(",");
                    this.input.dispatchEvent(new Event("change", { bubbles: true }));
                } else {
                    const image = this.selectedImages[0];
                    this.input.value = image.url || image.path || image.name;
                    this.input.dispatchEvent(new Event("change", { bubbles: true }));
                }

                if (typeof this.config.onSelect === "function") {
                    this.config.onSelect(this.selectedImages, this.input);
                }

                this.close();
            },

            open() {
                if (this.isOpen) return;

                if (!this.overlay) {
                    this.buildOverlay();
                }

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
            },

            async loadImages() {
                this.images = await CImagePicker.fetchImages();

                if (this.config.type && this.config.type !== "*") {
                    const allowed = this.config.type.split("|").map(t => t.trim().toLowerCase());
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

        return instance;
    }
}

if (typeof window !== "undefined") {
    window.CImagePicker = CImagePicker;
}

export default CImagePicker;