// Rich Text Editor with Image Paste Support
class RichEditor {
    constructor(textareaId, options = {}) {
        this.textarea = document.getElementById(textareaId);
        this.options = {
            uploadUrl: options.uploadUrl || 'upload_image.php',
            maxFileSize: options.maxFileSize || 5 * 1024 * 1024, // 5MB
            allowedTypes: options.allowedTypes || ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            ...options
        };
        this.init();
    }

    init() {
        // Create editor container
        this.editorContainer = document.createElement('div');
        this.editorContainer.className = 'rich-editor-container';
        
        // Create toolbar
        this.toolbar = document.createElement('div');
        this.toolbar.className = 'rich-editor-toolbar';
        this.toolbar.innerHTML = `
            <button type="button" class="editor-btn" data-command="bold" title="Bold">
                <i class="fas fa-bold"></i>
            </button>
            <button type="button" class="editor-btn" data-command="italic" title="Italic">
                <i class="fas fa-italic"></i>
            </button>
            <button type="button" class="editor-btn" data-command="underline" title="Underline">
                <i class="fas fa-underline"></i>
            </button>
            <div class="toolbar-separator"></div>
            <button type="button" class="editor-btn" data-command="insertUnorderedList" title="Bullet List">
                <i class="fas fa-list-ul"></i>
            </button>
            <button type="button" class="editor-btn" data-command="insertOrderedList" title="Numbered List">
                <i class="fas fa-list-ol"></i>
            </button>
            <div class="toolbar-separator"></div>
            <button type="button" class="editor-btn" id="insertImageBtn" title="Insert Image">
                <i class="fas fa-image"></i>
            </button>
            <input type="file" id="imageFileInput" accept="image/*" multiple style="display: none;">
            <div class="toolbar-separator"></div>
            <button type="button" class="editor-btn" data-command="undo" title="Undo">
                <i class="fas fa-undo"></i>
            </button>
            <button type="button" class="editor-btn" data-command="redo" title="Redo">
                <i class="fas fa-redo"></i>
            </button>
        `;

        // Create editor content area
        this.editor = document.createElement('div');
        this.editor.className = 'rich-editor-content';
        this.editor.contentEditable = true;
        this.editor.innerHTML = this.textarea.value || '<p><br></p>';

        // Create image upload progress
        this.progressContainer = document.createElement('div');
        this.progressContainer.className = 'upload-progress-container';
        this.progressContainer.style.display = 'none';

        // Insert editor before textarea
        this.textarea.parentNode.insertBefore(this.editorContainer, this.textarea);
        this.editorContainer.appendChild(this.toolbar);
        this.editorContainer.appendChild(this.editor);
        this.editorContainer.appendChild(this.progressContainer);
        
        // Hide original textarea
        this.textarea.style.display = 'none';

        this.bindEvents();
        this.updateTextarea();
    }

    bindEvents() {
        // Toolbar buttons
        this.toolbar.addEventListener('click', (e) => {
            if (e.target.closest('.editor-btn')) {
                const btn = e.target.closest('.editor-btn');
                const command = btn.dataset.command;
                
                if (command) {
                    e.preventDefault();
                    document.execCommand(command, false, null);
                    this.editor.focus();
                } else if (btn.id === 'insertImageBtn') {
                    document.getElementById('imageFileInput').click();
                }
            }
        });

        // File input for images
        document.getElementById('imageFileInput').addEventListener('change', (e) => {
            this.handleFileSelect(e.target.files);
        });

        // Paste event for images
        this.editor.addEventListener('paste', (e) => {
            this.handlePaste(e);
        });

        // Drag and drop
        this.editor.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.editor.classList.add('drag-over');
        });

        this.editor.addEventListener('dragleave', (e) => {
            e.preventDefault();
            this.editor.classList.remove('drag-over');
        });

        this.editor.addEventListener('drop', (e) => {
            e.preventDefault();
            this.editor.classList.remove('drag-over');
            this.handleFileSelect(e.dataTransfer.files);
        });

        // Update textarea on content change
        this.editor.addEventListener('input', () => {
            this.updateTextarea();
        });

        // Prevent default drag behavior on images
        this.editor.addEventListener('dragstart', (e) => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
            }
        });
    }

    handlePaste(e) {
        const items = e.clipboardData.items;
        const files = [];

        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                files.push(items[i].getAsFile());
            }
        }

        if (files.length > 0) {
            e.preventDefault();
            this.handleFileSelect(files);
        }
    }

    handleFileSelect(files) {
        Array.from(files).forEach(file => {
            if (this.validateFile(file)) {
                this.uploadImage(file);
            }
        });
    }

    validateFile(file) {
        if (!this.options.allowedTypes.includes(file.type)) {
            alert('File type not supported. Please use: ' + this.options.allowedTypes.join(', '));
            return false;
        }

        if (file.size > this.options.maxFileSize) {
            alert('File size too large. Maximum size: ' + (this.options.maxFileSize / 1024 / 1024) + 'MB');
            return false;
        }

        return true;
    }

    uploadImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('action', 'upload_image');

        // Show progress
        this.showProgress();

        // Create placeholder
        const placeholder = document.createElement('div');
        placeholder.className = 'image-placeholder';
        placeholder.innerHTML = `
            <div class="placeholder-content">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Uploading ${file.name}...</span>
            </div>
        `;

        // Insert placeholder at cursor
        this.insertAtCursor(placeholder);

        fetch(this.options.uploadUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideProgress();
            
            if (data.success) {
                // Replace placeholder with actual image
                const img = document.createElement('img');
                img.src = data.url;
                img.alt = file.name;
                img.className = 'editor-image';
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                
                placeholder.parentNode.replaceChild(img, placeholder);
                this.updateTextarea();
            } else {
                placeholder.remove();
                alert('Upload failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            this.hideProgress();
            placeholder.remove();
            console.error('Upload error:', error);
            alert('Upload failed: ' + error.message);
        });
    }

    insertAtCursor(element) {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(element);
            
            // Move cursor after inserted element
            range.setStartAfter(element);
            range.setEndAfter(element);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            this.editor.appendChild(element);
        }
    }

    showProgress() {
        this.progressContainer.innerHTML = `
            <div class="upload-progress">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <span>Uploading images...</span>
            </div>
        `;
        this.progressContainer.style.display = 'block';
    }

    hideProgress() {
        this.progressContainer.style.display = 'none';
    }

    updateTextarea() {
        // Convert HTML to a format suitable for storage
        let content = this.editor.innerHTML;
        
        // Clean up empty paragraphs
        content = content.replace(/<p><br><\/p>/g, '');
        content = content.replace(/<p><\/p>/g, '');
        
        this.textarea.value = content;
    }

    getContent() {
        return this.editor.innerHTML;
    }

    setContent(html) {
        this.editor.innerHTML = html || '<p><br></p>';
        this.updateTextarea();
    }
}

// Initialize rich editors when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize for ticket description
    const ticketDescription = document.getElementById('description');
    if (ticketDescription) {
        new RichEditor('description', {
            uploadUrl: 'upload_image'
        });
    }

    // Initialize for ticket comments
    const commentTextarea = document.querySelector('textarea[name="comment"]');
    if (commentTextarea && commentTextarea.id) {
        new RichEditor(commentTextarea.id, {
            uploadUrl: 'upload_image'
        });
    }

    // Initialize for forum content
    const forumContent = document.getElementById('content');
    if (forumContent) {
        new RichEditor('content', {
            uploadUrl: 'upload_image'
        });
    }

    // Initialize for forum replies
    const replyContent = document.querySelector('textarea[name="content"]');
    if (replyContent && replyContent.getAttribute('name') === 'content' && !replyContent.id) {
        replyContent.id = 'reply-content-' + Date.now();
        new RichEditor(replyContent.id, {
            uploadUrl: 'upload_image'
        });
    }
});