// Practitioner intro video form: YouTube/Vimeo link or a short uploaded clip.
// Checks size and length in the browser before uploading (the server checks again),
// then uploads with a progress bar so a 30 MB file doesn't look frozen.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('introVideo', ({ source = 'link', maxMb = 50, maxSeconds = 120 } = {}) => ({
        source,
        file: null,
        fileInfo: '',
        previewUrl: null,
        error: '',
        uploading: false,
        progress: 0,

        pick(event) {
            this.error = '';
            this.reset();
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > maxMb * 1024 * 1024) {
                this.error = `File size exceeds ${maxMb} MB. Please upload a shorter or compressed video.`;
                event.target.value = '';
                return;
            }

            // Read the length from the file itself; some formats can't be read in every browser,
            // in which case the server makes the final call.
            const url = URL.createObjectURL(file);
            const probe = document.createElement('video');
            probe.preload = 'metadata';
            probe.onloadedmetadata = () => {
                const secs = probe.duration;
                if (Number.isFinite(secs) && secs > maxSeconds + 1) {
                    this.error = `Your video is ${this.clock(secs)} long. Please keep it under ${Math.floor(maxSeconds / 60)} minutes.`;
                    event.target.value = '';
                    URL.revokeObjectURL(url);
                    return;
                }
                this.accept(file, url, secs);
            };
            probe.onerror = () => this.accept(file, url, null);
            probe.src = url;
        },

        accept(file, url, secs) {
            this.file = file;
            this.previewUrl = url;
            this.fileInfo = `${file.name} · ${(file.size / 1024 / 1024).toFixed(1)} MB${Number.isFinite(secs) ? ' · ' + this.clock(secs) : ''}`;
        },

        reset() {
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.file = null;
            this.previewUrl = null;
            this.fileInfo = '';
        },

        clock(secs) {
            const s = Math.round(secs);
            return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
        },

        submit(event) {
            if (this.source !== 'upload') return; // links post normally
            event.preventDefault();
            if (!this.file) {
                this.error = 'Please choose a video file.';
                return;
            }

            const form = event.target;
            const data = new FormData(form);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            this.uploading = true;
            this.progress = 0;
            this.error = '';

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) this.progress = Math.round((e.loaded / e.total) * 100);
            };
            xhr.onload = () => {
                let body = {};
                try { body = JSON.parse(xhr.responseText); } catch { /* non-JSON error page */ }
                if (xhr.status >= 200 && xhr.status < 300 && body.redirect) {
                    window.location.assign(body.redirect);
                    return;
                }
                this.uploading = false;
                const first = body.errors ? Object.values(body.errors)[0]?.[0] : null;
                this.error = first || body.message || 'The upload failed. Please check your connection and try again.';
            };
            xhr.onerror = () => {
                this.uploading = false;
                this.error = 'The upload failed. Please check your connection and try again.';
            };
            xhr.send(data);
        },
    }));
});
