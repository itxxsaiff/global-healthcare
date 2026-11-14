{{-- Optional intro video (About step). Separate form so it never blocks saving the bio. --}}
@php
    $video = $profile->introVideo();
    $maxMb = (int) \App\Models\Setting::get('intro_video_max_mb', 50);
    $maxSeconds = (int) \App\Models\Setting::get('intro_video_max_seconds', 120);
    $startSource = old('source', $profile->intro_video_path ? 'upload' : 'link');
@endphp
<section id="intro-video" class="card-soft p-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div class="d-flex gap-3">
            <span class="icon-tile sm tone-coral"><i class="ri-video-on-line fs-5"></i></span>
            <div>
                <h2 class="h5 mb-1">Introduction video <span class="badge rounded-pill text-bg-light border fw-medium align-middle ms-1">Optional</span></h2>
                <p class="small text-muted mb-0">A short hello helps patients feel comfortable before they book. 30–90 seconds is ideal: who you are, who you help and what a first session is like.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @if ($video)
            <div class="col-lg-5">
                <div class="small fw-semibold mb-2">On your profile now</div>
                <x-intro-video :video="$video" :name="$profile->displayName()" />
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                    <span class="small text-muted">
                        @if ($video['type'] === 'file')
                            <i class="ri-upload-cloud-2-line"></i> Uploaded video @if ($video['length']) · {{ $video['length'] }} @endif
                        @else
                            <i class="ri-{{ $video['type'] }}-line"></i> {{ $video['type'] === 'youtube' ? 'YouTube' : 'Vimeo' }} link
                        @endif
                    </span>
                    @if ($editable)
                        <button type="submit" form="intro-video-remove" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove your introduction video from your profile?')"><i class="ri-delete-bin-line"></i> Remove</button>
                    @endif
                </div>
            </div>
        @endif

        <div class="{{ $video ? 'col-lg-7' : 'col-12' }}">
            <form method="POST" action="{{ route('practitioner.video.update') }}" enctype="multipart/form-data"
                  x-data="introVideo({ source: @js($startSource), maxMb: {{ $maxMb }}, maxSeconds: {{ $maxSeconds }} })" @submit="submit">
                @csrf
                <div class="small fw-semibold mb-2">{{ $video ? 'Replace it with' : 'Add a video' }}</div>
                <div class="segmented mb-3">
                    <input type="radio" name="source" id="vs-link" value="link" x-model="source">
                    <label for="vs-link"><i class="ri-links-line"></i> YouTube or Vimeo link</label>
                    <input type="radio" name="source" id="vs-upload" value="upload" x-model="source">
                    <label for="vs-upload"><i class="ri-upload-2-line"></i> Upload a file</label>
                </div>

                <div x-show="source === 'link'">
                    <label class="form-label small" for="intro_video_url">Video link</label>
                    <input id="intro_video_url" name="intro_video_url" type="url" inputmode="url"
                           value="{{ old('intro_video_url', $profile->intro_video_url) }}"
                           class="form-control @error('intro_video_url') is-invalid @enderror" placeholder="https://youtu.be/… or https://vimeo.com/…">
                    <x-field-error name="intro_video_url" />
                    <p class="form-hint mb-0 mt-1">Recommended: no size limit and it plays smoothly on every connection. Set the YouTube video to <em>Public</em> or <em>Unlisted</em>.</p>
                </div>

                <div x-show="source === 'upload'" x-cloak>
                    <div class="upload-box text-start">
                        <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" class="form-control @error('video') is-invalid @enderror" @change="pick" :disabled="uploading">
                        <x-field-error name="video" />
                        <template x-if="previewUrl">
                            <div class="mt-3">
                                <div class="ratio ratio-16x9 rounded-3 overflow-hidden border bg-dark" style="max-width: 360px"><video :src="previewUrl" controls playsinline muted></video></div>
                                <div class="small text-muted mt-1" x-text="fileInfo"></div>
                            </div>
                        </template>
                        <p class="form-hint mb-0 mt-2">MP4, WebM or MOV · up to {{ $maxMb }} MB · up to {{ intdiv($maxSeconds, 60) }} minutes. Film in landscape, in a quiet, well-lit room.</p>
                    </div>
                    <div class="mt-3" x-show="uploading" x-cloak>
                        <div class="d-flex justify-content-between small mb-1"><span>Uploading…</span><span x-text="progress + '%'"></span></div>
                        <div class="progress" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100" style="height: 8px">
                            <div class="progress-bar bg-success" :style="`width: ${progress}%`"></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-danger small py-2 mt-3 mb-0" x-show="error" x-text="error" x-cloak role="alert"></div>

                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" :disabled="uploading"><i class="ri-save-3-line"></i> <span x-text="uploading ? 'Uploading…' : 'Save video'">Save video</span></button>
                </div>
            </form>
        </div>
    </div>

    @if ($video && $editable)
        <form id="intro-video-remove" method="POST" action="{{ route('practitioner.video.destroy') }}" class="d-none">@csrf @method('DELETE')</form>
    @endif
</section>
