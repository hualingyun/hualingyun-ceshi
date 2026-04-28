class ImageStyleConverter {
    constructor() {
        this.originalImage = null;
        this.currentImage = null;
        this.editedImage = null;
        this.styledImage = null;
        this.imageData = {
            width: 0,
            height: 0,
            format: '',
            size: 0
        };
        this.editSettings = {
            brightness: 100,
            contrast: 100,
            rotation: 0
        };
        this.selectedStyle = null;
        this.cropBox = {
            x: 0,
            y: 0,
            width: 0,
            height: 0
        };
        this.isCropping = false;
        this.sliderPosition = 50;

        this.initElements();
        this.initEventListeners();
    }

    initElements() {
        this.uploadArea = document.getElementById('upload-area');
        this.fileInput = document.getElementById('file-input');
        this.imageInfo = document.getElementById('image-info');
        this.imageSize = document.getElementById('image-size');
        this.imageFormat = document.getElementById('image-format');
        
        this.uploadSection = document.getElementById('upload-section');
        this.editorSection = document.getElementById('editor-section');
        this.styleSection = document.getElementById('style-section');
        this.compareSection = document.getElementById('compare-section');
        
        this.editorCanvas = document.getElementById('editor-canvas');
        this.editorCtx = this.editorCanvas.getContext('2d');
        
        this.brightnessSlider = document.getElementById('brightness');
        this.brightnessValue = document.getElementById('brightness-value');
        this.contrastSlider = document.getElementById('contrast');
        this.contrastValue = document.getElementById('contrast-value');
        this.rotateLeftBtn = document.getElementById('rotate-left');
        this.rotateRightBtn = document.getElementById('rotate-right');
        this.resetEditsBtn = document.getElementById('reset-edits');
        
        this.cropRatio = document.getElementById('crop-ratio');
        this.cropBtn = document.getElementById('crop-btn');
        this.cropOverlay = document.getElementById('crop-overlay');
        this.cropBoxEl = document.getElementById('crop-box');
        
        this.styleOptions = document.querySelectorAll('.style-option');
        this.convertBtn = document.getElementById('convert-btn');
        this.loading = document.getElementById('loading');
        
        this.compareSliderBtn = document.getElementById('compare-slider');
        this.compareSideBtn = document.getElementById('compare-side');
        this.compareSliderContainer = document.getElementById('compare-slider-container');
        this.compareSideContainer = document.getElementById('compare-side-container');
        this.sliderHandle = document.getElementById('slider-handle');
        
        this.compareOriginal = document.getElementById('compare-original');
        this.compareStyled = document.getElementById('compare-styled');
        this.sideOriginal = document.getElementById('side-original');
        this.sideStyled = document.getElementById('side-styled');
        
        this.downloadBtn = document.getElementById('download-btn');
    }

    initEventListeners() {
        this.uploadArea.addEventListener('click', () => this.fileInput.click());
        
        this.uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.uploadArea.classList.add('dragover');
        });
        
        this.uploadArea.addEventListener('dragleave', () => {
            this.uploadArea.classList.remove('dragover');
        });
        
        this.uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            this.uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        });
        
        this.fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleFile(e.target.files[0]);
            }
        });
        
        this.brightnessSlider.addEventListener('input', (e) => {
            this.editSettings.brightness = parseInt(e.target.value);
            this.brightnessValue.textContent = e.target.value;
            this.applyFilters();
        });
        
        this.contrastSlider.addEventListener('input', (e) => {
            this.editSettings.contrast = parseInt(e.target.value);
            this.contrastValue.textContent = e.target.value;
            this.applyFilters();
        });
        
        this.rotateLeftBtn.addEventListener('click', () => {
            this.editSettings.rotation = (this.editSettings.rotation - 90 + 360) % 360;
            this.applyRotation();
        });
        
        this.rotateRightBtn.addEventListener('click', () => {
            this.editSettings.rotation = (this.editSettings.rotation + 90) % 360;
            this.applyRotation();
        });
        
        this.resetEditsBtn.addEventListener('click', () => {
            this.resetEdits();
        });
        
        this.cropRatio.addEventListener('change', (e) => {
            if (e.target.value !== 'free') {
                this.startCropMode();
            }
        });
        
        this.cropBtn.addEventListener('click', () => {
            this.applyCrop();
        });
        
        this.styleOptions.forEach(option => {
            option.addEventListener('click', () => {
                this.selectStyle(option);
            });
        });
        
        this.convertBtn.addEventListener('click', () => {
            this.convertImage();
        });
        
        this.compareSliderBtn.addEventListener('click', () => {
            this.showCompareMode('slider');
        });
        
        this.compareSideBtn.addEventListener('click', () => {
            this.showCompareMode('side');
        });
        
        this.initSliderInteraction();
        
        this.downloadBtn.addEventListener('click', () => {
            this.downloadImage();
        });
    }

    handleFile(file) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            alert('请上传 JPG 或 PNG 格式的图片！');
            return;
        }
        
        if (file.size > 1024 * 1024) {
            alert('图片大小不能超过 1MB！');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                this.originalImage = img;
                this.currentImage = img;
                this.imageData = {
                    width: img.width,
                    height: img.height,
                    format: file.type,
                    size: file.size
                };
                
                this.showImageInfo();
                
                if (img.width > 500 || img.height > 500) {
                    this.compressImage(img);
                } else {
                    this.displayImage(img);
                }
                
                this.editorSection.classList.remove('hidden');
                this.styleSection.classList.remove('hidden');
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    showImageInfo() {
        this.imageInfo.classList.remove('hidden');
        this.imageSize.textContent = `${this.imageData.width} × ${this.imageData.height}`;
        const formatName = this.imageData.format === 'image/png' ? 'PNG' : 'JPG';
        this.imageFormat.textContent = formatName;
    }

    compressImage(img) {
        const maxSize = 500;
        let width = img.width;
        let height = img.height;
        
        if (width > height) {
            if (width > maxSize) {
                height = (height * maxSize) / width;
                width = maxSize;
            }
        } else {
            if (height > maxSize) {
                width = (width * maxSize) / height;
                height = maxSize;
            }
        }
        
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);
        
        const compressedImg = new Image();
        compressedImg.onload = () => {
            this.currentImage = compressedImg;
            this.displayImage(compressedImg);
        };
        compressedImg.src = canvas.toDataURL(this.imageData.format, 0.9);
    }

    displayImage(img) {
        this.editorCanvas.width = img.width;
        this.editorCanvas.height = img.height;
        this.editorCtx.drawImage(img, 0, 0);
        this.editedImage = img;
    }

    applyFilters() {
        if (!this.currentImage) return;
        
        const canvas = document.createElement('canvas');
        canvas.width = this.currentImage.width;
        canvas.height = this.currentImage.height;
        const ctx = canvas.getContext('2d');
        
        const brightness = this.editSettings.brightness / 100;
        const contrast = (this.editSettings.contrast / 100 - 0.5) * 2;
        
        ctx.filter = `brightness(${brightness}) contrast(${1 + contrast})`;
        ctx.drawImage(this.currentImage, 0, 0);
        
        const filteredImg = new Image();
        filteredImg.onload = () => {
            this.editedImage = filteredImg;
            this.editorCtx.clearRect(0, 0, this.editorCanvas.width, this.editorCanvas.height);
            this.editorCanvas.width = canvas.width;
            this.editorCanvas.height = canvas.height;
            this.editorCtx.drawImage(filteredImg, 0, 0);
        };
        filteredImg.src = canvas.toDataURL('image/png');
    }

    applyRotation() {
        if (!this.currentImage) return;
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        const isVertical = this.editSettings.rotation % 180 !== 0;
        if (isVertical) {
            canvas.width = this.currentImage.height;
            canvas.height = this.currentImage.width;
        } else {
            canvas.width = this.currentImage.width;
            canvas.height = this.currentImage.height;
        }
        
        ctx.translate(canvas.width / 2, canvas.height / 2);
        ctx.rotate((this.editSettings.rotation * Math.PI) / 180);
        ctx.drawImage(this.currentImage, -this.currentImage.width / 2, -this.currentImage.height / 2);
        
        const rotatedImg = new Image();
        rotatedImg.onload = () => {
            this.currentImage = rotatedImg;
            this.editedImage = rotatedImg;
            this.editorCtx.clearRect(0, 0, this.editorCanvas.width, this.editorCanvas.height);
            this.editorCanvas.width = canvas.width;
            this.editorCanvas.height = canvas.height;
            this.editorCtx.drawImage(rotatedImg, 0, 0);
        };
        rotatedImg.src = canvas.toDataURL('image/png');
    }

    resetEdits() {
        this.editSettings = {
            brightness: 100,
            contrast: 100,
            rotation: 0
        };
        
        this.brightnessSlider.value = 100;
        this.brightnessValue.textContent = '100';
        this.contrastSlider.value = 100;
        this.contrastValue.textContent = '100';
        this.cropRatio.value = 'free';
        
        this.currentImage = this.originalImage;
        
        if (this.originalImage.width > 500 || this.originalImage.height > 500) {
            this.compressImage(this.originalImage);
        } else {
            this.displayImage(this.originalImage);
        }
        
        this.cropOverlay.classList.add('hidden');
        this.isCropping = false;
    }

    startCropMode() {
        if (!this.editedImage) return;
        
        this.isCropping = true;
        this.cropOverlay.classList.remove('hidden');
        
        const ratio = this.cropRatio.value;
        let cropWidth, cropHeight;
        
        if (ratio === '1:1') {
            const minSize = Math.min(this.editorCanvas.width, this.editorCanvas.height);
            cropWidth = minSize * 0.8;
            cropHeight = minSize * 0.8;
        } else if (ratio === '4:3') {
            cropWidth = this.editorCanvas.width * 0.8;
            cropHeight = cropWidth * 0.75;
        } else if (ratio === '16:9') {
            cropWidth = this.editorCanvas.width * 0.8;
            cropHeight = cropWidth * 0.5625;
        } else {
            cropWidth = this.editorCanvas.width * 0.8;
            cropHeight = this.editorCanvas.height * 0.8;
        }
        
        this.cropBox = {
            x: (this.editorCanvas.width - cropWidth) / 2,
            y: (this.editorCanvas.height - cropHeight) / 2,
            width: cropWidth,
            height: cropHeight
        };
        
        this.updateCropBox();
        this.initCropBoxInteraction();
    }

    updateCropBox() {
        const canvasRect = this.editorCanvas.getBoundingClientRect();
        const scaleX = canvasRect.width / this.editorCanvas.width;
        const scaleY = canvasRect.height / this.editorCanvas.height;
        
        this.cropBoxEl.style.left = (this.cropBox.x * scaleX) + 'px';
        this.cropBoxEl.style.top = (this.cropBox.y * scaleY) + 'px';
        this.cropBoxEl.style.width = (this.cropBox.width * scaleX) + 'px';
        this.cropBoxEl.style.height = (this.cropBox.height * scaleY) + 'px';
    }

    initCropBoxInteraction() {
        let isDragging = false;
        let startX, startY;
        
        this.cropBoxEl.addEventListener('mousedown', (e) => {
            isDragging = true;
            const canvasRect = this.editorCanvas.getBoundingClientRect();
            const scaleX = this.editorCanvas.width / canvasRect.width;
            startX = e.clientX;
            startY = e.clientY;
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            const canvasRect = this.editorCanvas.getBoundingClientRect();
            const scaleX = this.editorCanvas.width / canvasRect.width;
            const scaleY = this.editorCanvas.height / canvasRect.height;
            
            const deltaX = (e.clientX - startX) * scaleX;
            const deltaY = (e.clientY - startY) * scaleY;
            
            let newX = this.cropBox.x + deltaX;
            let newY = this.cropBox.y + deltaY;
            
            newX = Math.max(0, Math.min(newX, this.editorCanvas.width - this.cropBox.width));
            newY = Math.max(0, Math.min(newY, this.editorCanvas.height - this.cropBox.height));
            
            this.cropBox.x = newX;
            this.cropBox.y = newY;
            
            startX = e.clientX;
            startY = e.clientY;
            
            this.updateCropBox();
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
        });
    }

    applyCrop() {
        if (!this.isCropping || !this.editedImage) return;
        
        const canvas = document.createElement('canvas');
        canvas.width = this.cropBox.width;
        canvas.height = this.cropBox.height;
        const ctx = canvas.getContext('2d');
        
        ctx.drawImage(
            this.editedImage,
            this.cropBox.x,
            this.cropBox.y,
            this.cropBox.width,
            this.cropBox.height,
            0,
            0,
            this.cropBox.width,
            this.cropBox.height
        );
        
        const croppedImg = new Image();
        croppedImg.onload = () => {
            this.currentImage = croppedImg;
            this.editedImage = croppedImg;
            this.displayImage(croppedImg);
            this.cropOverlay.classList.add('hidden');
            this.isCropping = false;
        };
        croppedImg.src = canvas.toDataURL('image/png');
    }

    selectStyle(option) {
        this.styleOptions.forEach(opt => opt.classList.remove('selected'));
        option.classList.add('selected');
        this.selectedStyle = option.dataset.style;
    }

    convertImage() {
        if (!this.selectedStyle) {
            alert('请先选择一个风格！');
            return;
        }
        
        this.loading.classList.remove('hidden');
        this.convertBtn.disabled = true;
        
        setTimeout(() => {
            this.applyStyleEffect();
            this.loading.classList.add('hidden');
            this.convertBtn.disabled = false;
            this.compareSection.classList.remove('hidden');
            this.showCompareMode('slider');
        }, 2000);
    }

    applyStyleEffect() {
        if (!this.editedImage) return;
        
        const canvas = document.createElement('canvas');
        canvas.width = this.editedImage.width;
        canvas.height = this.editedImage.height;
        const ctx = canvas.getContext('2d');
        
        ctx.drawImage(this.editedImage, 0, 0);
        
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        switch (this.selectedStyle) {
            case 'impressionism':
                this.applyImpressionismEffect(data, canvas.width, canvas.height);
                break;
            case 'abstract':
                this.applyAbstractEffect(data, canvas.width, canvas.height);
                break;
            case 'oil-painting':
                this.applyOilPaintingEffect(data, canvas.width, canvas.height);
                break;
            case 'watercolor':
                this.applyWatercolorEffect(data, canvas.width, canvas.height);
                break;
            case 'anime':
                this.applyAnimeEffect(data, canvas.width, canvas.height);
                break;
        }
        
        ctx.putImageData(imageData, 0, 0);
        
        const styledImg = new Image();
        styledImg.onload = () => {
            this.styledImage = styledImg;
            this.setupComparison();
        };
        styledImg.src = canvas.toDataURL('image/png');
    }

    applyImpressionismEffect(data, width, height) {
        const tempData = new Uint8ClampedArray(data);
        const radius = 3;
        
        for (let y = radius; y < height - radius; y++) {
            for (let x = radius; x < width - radius; x++) {
                let r = 0, g = 0, b = 0, count = 0;
                
                for (let dy = -radius; dy <= radius; dy++) {
                    for (let dx = -radius; dx <= radius; dx++) {
                        const idx = (y + dy) * width + (x + dx);
                        r += tempData[idx * 4];
                        g += tempData[idx * 4 + 1];
                        b += tempData[idx * 4 + 2];
                        count++;
                    }
                }
                
                const idx = y * width + x;
                data[idx * 4] = r / count;
                data[idx * 4 + 1] = g / count;
                data[idx * 4 + 2] = b / count;
            }
        }
        
        for (let i = 0; i < data.length; i += 4) {
            data[i] = Math.min(255, data[i] * 1.1);
            data[i + 1] = Math.min(255, data[i + 1] * 1.1);
            data[i + 2] = Math.min(255, data[i + 2] * 1.05);
        }
    }

    applyAbstractEffect(data, width, height) {
        for (let i = 0; i < data.length; i += 4) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            
            const gray = (r + g + b) / 3;
            
            data[i] = gray > 128 ? 255 : 0;
            data[i + 1] = gray > 128 ? 0 : 255;
            data[i + 2] = gray > 128 ? 128 : 255;
        }
    }

    applyOilPaintingEffect(data, width, height) {
        const tempData = new Uint8ClampedArray(data);
        const radius = 5;
        const intensityLevels = 24;
        
        for (let y = radius; y < height - radius; y++) {
            for (let x = radius; x < width - radius; x++) {
                const intensityBins = new Array(intensityLevels).fill(0).map(() => ({ r: 0, g: 0, b: 0, count: 0 }));
                
                for (let dy = -radius; dy <= radius; dy++) {
                    for (let dx = -radius; dx <= radius; dx++) {
                        const idx = (y + dy) * width + (x + dx);
                        const r = tempData[idx * 4];
                        const g = tempData[idx * 4 + 1];
                        const b = tempData[idx * 4 + 2];
                        
                        const intensity = Math.floor((r + g + b) / 3 * intensityLevels / 255);
                        intensityBins[intensity].r += r;
                        intensityBins[intensity].g += g;
                        intensityBins[intensity].b += b;
                        intensityBins[intensity].count++;
                    }
                }
                
                let maxCount = 0;
                let maxIdx = 0;
                for (let i = 0; i < intensityLevels; i++) {
                    if (intensityBins[i].count > maxCount) {
                        maxCount = intensityBins[i].count;
                        maxIdx = i;
                    }
                }
                
                const idx = y * width + x;
                data[idx * 4] = intensityBins[maxIdx].r / intensityBins[maxIdx].count;
                data[idx * 4 + 1] = intensityBins[maxIdx].g / intensityBins[maxIdx].count;
                data[idx * 4 + 2] = intensityBins[maxIdx].b / intensityBins[maxIdx].count;
            }
        }
    }

    applyWatercolorEffect(data, width, height) {
        const tempData = new Uint8ClampedArray(data);
        const radius = 4;
        
        for (let y = radius; y < height - radius; y++) {
            for (let x = radius; x < width - radius; x++) {
                let r = 0, g = 0, b = 0, count = 0;
                
                for (let dy = -radius; dy <= radius; dy++) {
                    for (let dx = -radius; dx <= radius; dx++) {
                        if (Math.random() > 0.5) {
                            const idx = (y + dy) * width + (x + dx);
                            r += tempData[idx * 4];
                            g += tempData[idx * 4 + 1];
                            b += tempData[idx * 4 + 2];
                            count++;
                        }
                    }
                }
                
                if (count > 0) {
                    const idx = y * width + x;
                    data[idx * 4] = r / count;
                    data[idx * 4 + 1] = g / count;
                    data[idx * 4 + 2] = b / count;
                }
            }
        }
        
        for (let i = 0; i < data.length; i += 4) {
            data[i] = Math.min(255, data[i] * 1.2);
            data[i + 1] = Math.min(255, data[i + 1] * 1.15);
            data[i + 2] = Math.min(255, data[i + 2] * 1.1);
        }
    }

    applyAnimeEffect(data, width, height) {
        const levels = 5;
        
        for (let i = 0; i < data.length; i += 4) {
            data[i] = Math.floor(data[i] / (256 / levels)) * (256 / levels);
            data[i + 1] = Math.floor(data[i + 1] / (256 / levels)) * (256 / levels);
            data[i + 2] = Math.floor(data[i + 2] / (256 / levels)) * (256 / levels);
        }
        
        for (let y = 1; y < height - 1; y++) {
            for (let x = 1; x < width - 1; x++) {
                const idx = (y * width + x) * 4;
                const idxTop = (y - 1) * width + x;
                const idxLeft = y * width + (x - 1);
                const idxRight = y * width + (x + 1);
                const idxBottom = (y + 1) * width + x;
                
                const edgeThreshold = 30;
                
                const grayCurrent = (data[idx] + data[idx + 1] + data[idx + 2]) / 3;
                const grayTop = (data[idxTop * 4] + data[idxTop * 4 + 1] + data[idxTop * 4 + 2]) / 3;
                const grayLeft = (data[idxLeft * 4] + data[idxLeft * 4 + 1] + data[idxLeft * 4 + 2]) / 3;
                const grayRight = (data[idxRight * 4] + data[idxRight * 4 + 1] + data[idxRight * 4 + 2]) / 3;
                const grayBottom = (data[idxBottom * 4] + data[idxBottom * 4 + 1] + data[idxBottom * 4 + 2]) / 3;
                
                if (Math.abs(grayCurrent - grayTop) > edgeThreshold ||
                    Math.abs(grayCurrent - grayLeft) > edgeThreshold ||
                    Math.abs(grayCurrent - grayRight) > edgeThreshold ||
                    Math.abs(grayCurrent - grayBottom) > edgeThreshold) {
                    data[idx] = Math.max(0, data[idx] - 50);
                    data[idx + 1] = Math.max(0, data[idx + 1] - 50);
                    data[idx + 2] = Math.max(0, data[idx + 2] - 50);
                }
            }
        }
    }

    setupComparison() {
        if (!this.editedImage || !this.styledImage) return;
        
        const maxWidth = 800;
        let scale = 1;
        
        if (this.editedImage.width > maxWidth) {
            scale = maxWidth / this.editedImage.width;
        }
        
        const displayWidth = Math.floor(this.editedImage.width * scale);
        const displayHeight = Math.floor(this.editedImage.height * scale);
        
        this.compareOriginal.width = displayWidth;
        this.compareOriginal.height = displayHeight;
        this.compareStyled.width = displayWidth;
        this.compareStyled.height = displayHeight;
        this.sideOriginal.width = displayWidth;
        this.sideOriginal.height = displayHeight;
        this.sideStyled.width = displayWidth;
        this.sideStyled.height = displayHeight;
        
        const ctxOriginal = this.compareOriginal.getContext('2d');
        const ctxStyled = this.compareStyled.getContext('2d');
        const ctxSideOriginal = this.sideOriginal.getContext('2d');
        const ctxSideStyled = this.sideStyled.getContext('2d');
        
        ctxOriginal.drawImage(this.editedImage, 0, 0, displayWidth, displayHeight);
        ctxStyled.drawImage(this.styledImage, 0, 0, displayWidth, displayHeight);
        ctxSideOriginal.drawImage(this.editedImage, 0, 0, displayWidth, displayHeight);
        ctxSideStyled.drawImage(this.styledImage, 0, 0, displayWidth, displayHeight);
    }

    initSliderInteraction() {
        let isDragging = false;
        
        this.sliderHandle.addEventListener('mousedown', () => {
            isDragging = true;
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            const wrapper = this.sliderHandle.parentElement;
            const rect = wrapper.getBoundingClientRect();
            const x = e.clientX - rect.left;
            this.sliderPosition = (x / rect.width) * 100;
            this.sliderPosition = Math.max(0, Math.min(100, this.sliderPosition));
            
            this.updateSliderPosition();
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
        });
        
        this.sliderHandle.addEventListener('touchstart', () => {
            isDragging = true;
        });
        
        document.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            const wrapper = this.sliderHandle.parentElement;
            const rect = wrapper.getBoundingClientRect();
            const x = e.touches[0].clientX - rect.left;
            this.sliderPosition = (x / rect.width) * 100;
            this.sliderPosition = Math.max(0, Math.min(100, this.sliderPosition));
            
            this.updateSliderPosition();
        });
        
        document.addEventListener('touchend', () => {
            isDragging = false;
        });
    }

    updateSliderPosition() {
        const overlay = this.sliderHandle.previousElementSibling;
        this.sliderHandle.style.left = this.sliderPosition + '%';
        overlay.style.width = this.sliderPosition + '%';
    }

    showCompareMode(mode) {
        if (mode === 'slider') {
            this.compareSliderContainer.classList.remove('hidden');
            this.compareSideContainer.classList.add('hidden');
            this.compareSliderBtn.classList.add('btn-primary');
            this.compareSliderBtn.classList.remove('btn-secondary');
            this.compareSideBtn.classList.add('btn-secondary');
            this.compareSideBtn.classList.remove('btn-primary');
        } else {
            this.compareSliderContainer.classList.add('hidden');
            this.compareSideContainer.classList.remove('hidden');
            this.compareSideBtn.classList.add('btn-primary');
            this.compareSideBtn.classList.remove('btn-secondary');
            this.compareSliderBtn.classList.add('btn-secondary');
            this.compareSliderBtn.classList.remove('btn-primary');
        }
    }

    downloadImage() {
        if (!this.styledImage) return;
        
        const link = document.createElement('a');
        link.download = `styled-image-${Date.now()}.png`;
        link.href = this.styledImage.src;
        link.click();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ImageStyleConverter();
});
