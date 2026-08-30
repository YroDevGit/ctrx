import Ctr from "../ctr";

class CImagePicker {
    static styleId = "cimagepicker-style-ctrx";
    static instances = [];

    static ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("link");
        style.id = this.styleId;
        style.setAttribute("rel", "stylesheet");
        style.setAttribute("href", "/views/code/src/style/imagepicker.css");
        document.head.appendChild(style);
    }

    static async fetchImages(path = "public", action = "0", postdata = {}) {
        try {
            const response = await fetch(`/ctrx.yro.ctrstorage.images/getall?action=${action}&dir=${encodeURIComponent(path)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(postdata)
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message ?? "Failed to fetch images");
            return data.images || [];
        } catch (error) {
            console.error("CImagePicker: Error fetching images", error);
            return [];
        }
    }

    static async uploadImage(file, path = "public", onProgress = null, action = "0", data = undefined) {
        const formData = new FormData();
        formData.append("image", file);
        formData.append("path", path);
        if (data) {
            for (let dt in data) {
                let rw = data[dt];
                formData.append(dt, rw);
            }
        }

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", `/ctrx.yro.ctrstorage.images/uploadHere?action=${action}&dir=` + path);

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
                    const data = JSON.parse(xhr.responseText);
                    reject(new Error(data.message ?? "Upload failed"));
                }
            };

            xhr.onerror = () => reject(new Error("Network error"));
            xhr.send(formData);
        });
    }

    static async deleteImage(filename, path = "public", action = "0", postData = {}) {
        try {
            const response = await fetch(`/ctrx.yro.ctrstorage.images/deleteImg?action=${action}&dir=${encodeURIComponent(path)}&filename=${encodeURIComponent(filename)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(
                    postData
                )
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message ?? "Failed to delete image");
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

    static compressImage(file, quality = 0.7, maxWidth = 1920, maxHeight = 1920) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = (event) => {
                const img = new Image();
                img.src = event.target.result;
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;

                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, 0, 0, width, height);

                    const format = 'image/jpeg';
                    const name = file.name.replace(/\.[^.]+$/, '.jpg');

                    canvas.toBlob((blob) => {
                        if (blob) {
                            if (blob.size < file.size) {
                                const compressedFile = new File([blob], name, {
                                    type: format,
                                    lastModified: Date.now()
                                });
                                resolve(compressedFile);
                            } else {
                                resolve(file);
                            }
                        } else {
                            reject(new Error('Compression failed'));
                        }
                    }, format, quality);
                };
                img.onerror = () => reject(new Error('Failed to load image'));
            };
            reader.onerror = () => reject(new Error('Failed to read file'));
        });
    }

    static init(element, config = {}) {
        this.ensureStyle();

        const instances = [];
        let elements = [];

        config.element = element;

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
            instanceConfig.post = config.post ?? config.body;
            instanceConfig.quality = config.quality ?? 95;
            instanceConfig.maxWidth = config.maxWidth ?? 1920;
            instanceConfig.maxHeight = config.maxHeight ?? 1920;
            instanceConfig.compressThreshold = config.compressThreshold ?? 100;

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
                nameProgress: null,
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
                    header.className = "cimagepicker-header cimagepicker-sensitive-load";

                    const mainHead = document.createElement("div");
                    mainHead.style.display = "grid";
                    mainHead.style.gap = "10px";

                    const headerLeft = document.createElement("div");
                    headerLeft.className = "cimagepicker-header-left";

                    const title = document.createElement("h2");
                    title.textContent = instanceConfig.title || "Select Image(s)";

                    const addBtn = document.createElement("button");
                    addBtn.className = "cimagepicker-btn-add";
                    addBtn.innerHTML = "➕ Add Image";
                    addBtn.addEventListener("click", () => {
                        this.toggleUpload();
                        body.scrollTo({
                            top: "0",
                            behavior: "smooth"
                        });
                    });

                    const closeMe = document.createElement("button");
                    closeMe.className = "cimagepicker-btn cimagepicker-btn-close";
                    closeMe.textContent = "Close";
                    closeMe.addEventListener("click", () => this.close());

                    mainHead.appendChild(title);
                    headerLeft.appendChild(addBtn);
                    headerLeft.appendChild(closeMe);

                    mainHead.appendChild(headerLeft);

                    const headerActions = document.createElement("div");
                    headerActions.className = "cimagepicker-header-actions";

                    const search = document.createElement("input");
                    search.type = "text";
                    search.className = "cimagepicker-search";
                    search.placeholder = "Search images...";
                    search.style.display = "none";
                    search.addEventListener("input", (e) => {
                        this.filterImages(e.target.value);
                    });

                    const closeBtn = document.createElement("button");
                    closeBtn.className = "cimagepicker-close";
                    closeBtn.innerHTML = "×";
                    closeBtn.addEventListener("click", () => this.close());

                    headerActions.appendChild(search);
                    headerActions.appendChild(closeBtn);
                    header.appendChild(mainHead);
                    header.appendChild(headerActions);

                    const body = document.createElement("div");
                    body.className = "cimagepicker-body";
                    body.id = "cimagepicker-body-id";

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

                    const nameProgress = document.createElement("div");
                    nameProgress.style.display = 'none';
                    nameProgress.innerText = "Uploading, please wait...";
                    const progressBar = document.createElement("div");
                    progressBar.className = "cimagepicker-upload-progress-bar";
                    progressWrapper.appendChild(progressBar);


                    uploadLabel.appendChild(fileInput);
                    uploadArea.appendChild(uploadLabel);
                    uploadArea.appendChild(uploadText);
                    uploadArea.appendChild(progressWrapper);

                    uploadArea.appendChild(nameProgress);
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
                    grid.className = "cimagepicker-grid cimagepicker-sensitive-load";

                    body.appendChild(grid);

                    const footer = document.createElement("div");
                    footer.className = "cimagepicker-footer cimagepicker-sensitive-load";

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
                    this.nameProgress = nameProgress;
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

                    let fileToUpload = file;
                    const quality = instanceConfig.quality ?? 95;
                    const maxWidth = instanceConfig.maxWidth ?? 1920;
                    const maxHeight = instanceConfig.maxHeight ?? 1920;
                    const compressThreshold = instanceConfig.compressThreshold ?? 100;

                    if (quality < 100 && file.size > (compressThreshold * 1024)) {
                        try {
                            const qualityValue = quality / 100;
                            const clampedQuality = Math.max(0.1, Math.min(0.99, qualityValue));
                            this.progressBar.style.width = "1%";
                            fileToUpload = await CImagePicker.compressImage(file, clampedQuality, maxWidth, maxHeight);
                        } catch (error) {
                            console.warn("Compression failed, using original file", error);
                            fileToUpload = file;
                        }
                    }

                    this.isUploading = true;
                    this.fileInput.disabled = true;
                    this.addBtn.disabled = true;
                    this.progressBar.parentElement.classList.add("cimagepicker-show");
                    this.progressBar.style.width = "1%";
                    this.nameProgress.style.display = "";
                    Ctr.set_loading(true, ".cimagepicker-sensitive-load", 33);

                    try {
                        const result = await CImagePicker.uploadImage(
                            fileToUpload,
                            instanceConfig.path || "public",
                            (percent) => {
                                this.progressBar.style.width = percent + "%";
                            },
                            instanceConfig.action,
                            instanceConfig.post
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
                        this.progressBar.parentElement.classList.remove("cimagepicker-show");
                        alert("Upload failed: " + error.message);
                    }

                    this.nameProgress.style.display = "none";
                    this.isUploading = false;
                    this.fileInput.disabled = false;
                    this.addBtn.disabled = false;
                    Ctr.set_loading(false, ".cimagepicker-sensitive-load");
                },

                async handleDelete(image, event) {
                    event.stopPropagation();

                    if (!confirm(`Are you sure you want to delete "${image.name}"?`)) {
                        return;
                    }

                    try {
                        let resDel = await CImagePicker.deleteImage(image.name, image.source_dir || "public", instanceConfig.action, this?.config?.post ?? {});

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
                    const isMultiple = instanceConfig.selection === "multiple" || instanceConfig.multiple;

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

                    const isMultiple = instanceConfig.selection === "multiple" || instanceConfig.multiple;

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
                    this.images = await CImagePicker.fetchImages(instanceConfig.path || "public", instanceConfig.action, instanceConfig.post ?? {});

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

export default CImagePicker;